<?php

namespace App\Services\Members;

use App\Models\Member;
use App\Models\MemberStatusHistory;
use App\Services\Ministries\MinistryAgeTransferService;
use App\Services\Security\SecurityAuditService;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class MemberWriteService
{
    private const MEMBER_CODE_PREFIX = 'AGCI';

    private const ALLOWED_PHOTO_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    private const MAX_PHOTO_BYTES = 2097152;

    public function __construct(
        private readonly MemberReadService $read,
        private readonly SecurityAuditService $audit,
        private readonly MinistryAgeTransferService $ageTransfers,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(array $data, ?UploadedFile $photo, int $adminId): array
    {
        $validated = $this->validateMemberData($data);
        $memberCode = $this->generateMemberCode();
        $photoPath = $this->storePhoto($photo);

        $member = Member::query()->create([
            ...$validated,
            'member_code' => $memberCode,
            'photo_path' => $photoPath,
            'created_by' => $adminId,
        ]);
        $this->syncPortalAccess($member, $data);

        $this->logStatusChange(
            (int) $member->id,
            null,
            $validated['status'],
            'Initial member registration',
            'system',
            $adminId,
        );

        $this->audit->log(
            'member_created',
            'New member '.$validated['full_name'].' ('.$memberCode.') was registered.',
            $adminId,
            'info',
            ['member_id' => (int) $member->id, 'member_code' => $memberCode],
        );

        $this->ageTransfers->syncMember((int) $member->id, 'on_save', $adminId);

        return $this->read->getMember((int) $member->id) ?? $member->fresh()->toArray();
    }

    /**
     * Create a full member from a promoted church visitor.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createFromVisitor(array $data, ?string $existingPhotoPath, int $adminId): array
    {
        $validated = $this->validateMemberData(array_merge($data, ['status' => 'full_member']));
        $memberCode = $this->generateMemberCode();

        $member = Member::query()->create([
            ...$validated,
            'member_code' => $memberCode,
            'photo_path' => $existingPhotoPath,
            'created_by' => $adminId,
        ]);

        $this->logStatusChange(
            (int) $member->id,
            null,
            'full_member',
            'Promoted from church visitor',
            'system',
            $adminId,
        );

        $this->ageTransfers->syncMember((int) $member->id, 'on_save', $adminId);

        return $this->read->getMember((int) $member->id) ?? $member->fresh()->toArray();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(int $id, array $data, ?UploadedFile $photo, bool $removePhoto, int $adminId): array
    {
        $member = Member::query()->findOrFail($id);
        $previousStatus = (string) $member->status;
        $validated = $this->validateMemberData($data);

        $photoPath = $member->photo_path;
        if ($removePhoto && $photoPath) {
            $this->deletePhotoFile($photoPath);
            $photoPath = null;
        }
        if ($photo) {
            if ($photoPath) {
                $this->deletePhotoFile($photoPath);
            }
            $photoPath = $this->storePhoto($photo);
        }

        $member->update([
            ...$validated,
            'photo_path' => $photoPath,
        ]);
        $this->syncPortalAccess($member->fresh() ?? $member, $data);

        if ($previousStatus !== $validated['status']) {
            $this->logStatusChange(
                $id,
                $previousStatus,
                $validated['status'],
                'Member status updated',
                'manual',
                $adminId,
            );
        }

        if ($validated['status'] === 'deceased' && $previousStatus !== 'deceased') {
            $this->audit->log(
                'member_death_recorded',
                'Death recorded for member '.$validated['full_name'].'.',
                $adminId,
                'warning',
                ['member_id' => $id],
            );
        } else {
            $this->audit->log(
                'member_updated',
                'Member '.$validated['full_name'].' was updated.',
                $adminId,
                'info',
                ['member_id' => $id],
            );
        }

        $this->ageTransfers->syncMember($id, 'on_save', $adminId);

        return $this->read->getMember($id) ?? $member->fresh()->toArray();
    }

    /**
     * Mark an existing living member as deceased (no new registration).
     *
     * @return array<string, mixed>
     */
    public function markAsDeceased(int $memberId, string $dateOfDeath, ?string $deathNotes, int $adminId): array
    {
        $member = Member::query()->findOrFail($memberId);
        $previousStatus = (string) $member->status;

        if ($previousStatus === 'deceased') {
            throw new InvalidArgumentException('This member is already marked as deceased.');
        }

        $joinedDate = $member->joined_date?->format('Y-m-d');
        $death = $this->validateDeathFields($dateOfDeath, $deathNotes, $joinedDate);

        $member->update([
            'status' => 'deceased',
            'date_of_death' => $death['date_of_death'],
            'death_notes' => $death['death_notes'],
        ]);

        $this->logStatusChange(
            $memberId,
            $previousStatus,
            'deceased',
            'Death recorded from deceased dashboard',
            'manual',
            $adminId,
        );

        $this->audit->log(
            'member_death_recorded',
            'Death recorded for member '.$member->full_name.'.',
            $adminId,
            'warning',
            ['member_id' => $memberId],
        );

        $this->ageTransfers->syncMember($memberId, 'on_save', $adminId);

        return $this->read->getMember($memberId) ?? $member->fresh()->toArray();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validateMemberData(array $data): array
    {
        $fullName = trim((string) ($data['full_name'] ?? ''));
        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));
        if ($firstName !== '' || $lastName !== '') {
            $fullName = trim($firstName.' '.$lastName);
        }
        if ($fullName === '' || mb_strlen($fullName) > 255) {
            throw new InvalidArgumentException('Full name is required (max 255 characters).');
        }
        if ($firstName === '') {
            $firstName = trim(explode(' ', $fullName, 2)[0] ?? $fullName);
        }
        if ($lastName === '') {
            $parts = explode(' ', $fullName, 2);
            $lastName = trim($parts[1] ?? '');
        }

        $gender = (string) ($data['gender'] ?? 'unspecified');
        if (! in_array($gender, ['male', 'female', 'other', 'unspecified'], true)) {
            $gender = 'unspecified';
        }

        $dateOfBirth = trim((string) ($data['date_of_birth'] ?? ''));
        if ($dateOfBirth !== '') {
            $dob = DateTimeImmutable::createFromFormat('Y-m-d', $dateOfBirth);
            if (! $dob || $dob->format('Y-m-d') !== $dateOfBirth || $dob > new DateTimeImmutable('today')) {
                throw new InvalidArgumentException('Date of birth must be a valid past date (YYYY-MM-DD).');
            }
        } else {
            $dateOfBirth = null;
        }

        $maritalStatus = strtolower(trim((string) ($data['marital_status'] ?? 'unspecified')));
        $allowedMarital = ['single', 'married', 'divorced', 'widowed', 'widow', 'widower', 'separated', 'unspecified'];
        if (! in_array($maritalStatus, $allowedMarital, true)) {
            $maritalStatus = 'unspecified';
        }

        if ($maritalStatus === 'widow' && in_array($gender, ['unspecified', 'other'], true)) {
            $gender = 'female';
        }
        if ($maritalStatus === 'widower' && in_array($gender, ['unspecified', 'other'], true)) {
            $gender = 'male';
        }

        $weddingDate = null;
        if ($maritalStatus === 'married') {
            $weddingRaw = trim((string) ($data['wedding_date'] ?? ''));
            if ($weddingRaw === '') {
                throw new InvalidArgumentException('Wedding date is required when marital status is Married.');
            }
            $wed = DateTimeImmutable::createFromFormat('Y-m-d', $weddingRaw);
            if (! $wed || $wed->format('Y-m-d') !== $weddingRaw || $wed > new DateTimeImmutable('today')) {
                throw new InvalidArgumentException('Wedding date must be a valid past or today date (YYYY-MM-DD).');
            }
            if ($dateOfBirth !== null) {
                $dobCompare = DateTimeImmutable::createFromFormat('Y-m-d', $dateOfBirth);
                if ($dobCompare && $wed < $dobCompare) {
                    throw new InvalidArgumentException('Wedding date cannot be before date of birth.');
                }
            }
            $weddingDate = $weddingRaw;
        }

        $parentEmail = trim((string) ($data['parent_email'] ?? ''));
        if ($parentEmail !== '' && ! filter_var($parentEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Please enter a valid parent email address.');
        }

        $parentPhone = $this->normalizePhone((string) ($data['parent_phone'] ?? ''), true);

        $email = trim((string) ($data['email'] ?? ''));
        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Please enter a valid email address.');
        }

        $phone = $this->normalizePhone((string) ($data['phone'] ?? ''), false);
        $phoneAlt = $this->normalizePhone((string) ($data['phone_alt'] ?? ''), true);

        $addressLine1 = trim((string) ($data['address_line1'] ?? ''));
        if ($addressLine1 === '') {
            throw new InvalidArgumentException('Street address is required.');
        }

        $city = trim((string) ($data['city'] ?? ''));
        if ($city === '') {
            throw new InvalidArgumentException('City is required.');
        }

        $state = trim((string) ($data['state'] ?? ''));
        if ($state === '') {
            throw new InvalidArgumentException('State is required.');
        }

        $department = $this->read->canonicalDepartmentLabel((string) ($data['department'] ?? ''));
        if ($department === '' || ! in_array($department, $this->read->listDepartmentOptions(), true)) {
            throw new InvalidArgumentException('Please select a valid department.');
        }

        $status = (string) ($data['status'] ?? 'active');
        if (! in_array($status, MemberReadService::STATUSES, true)) {
            $status = 'active';
        }

        $joinedDate = trim((string) ($data['joined_date'] ?? ''));
        if ($joinedDate === '') {
            $joinedDate = now()->toDateString();
        }
        $joined = DateTimeImmutable::createFromFormat('Y-m-d', $joinedDate);
        if (! $joined || $joined->format('Y-m-d') !== $joinedDate) {
            throw new InvalidArgumentException('Joined date must be in YYYY-MM-DD format.');
        }

        $deathNotes = null;
        if ($status === 'deceased') {
            $deathNotes = trim((string) ($data['death_notes'] ?? '')) ?: null;
        }

        $payload = [
            'first_name' => $firstName,
            'last_name' => $lastName !== '' ? $lastName : null,
            'full_name' => $fullName,
            'email' => $email !== '' ? strtolower($email) : null,
            'phone' => $phone,
            'phone_alt' => $phoneAlt,
            'gender' => $gender,
            'date_of_birth' => $dateOfBirth,
            'marital_status' => $maritalStatus,
            'wedding_date' => $weddingDate,
            'parent_name' => trim((string) ($data['parent_name'] ?? '')) ?: null,
            'parent_email' => $parentEmail !== '' ? strtolower($parentEmail) : null,
            'parent_phone' => $parentPhone,
            'address_line1' => $addressLine1,
            'address_line2' => trim((string) ($data['address_line2'] ?? '')) ?: null,
            'city' => $city,
            'state' => $state,
            'postal_code' => trim((string) ($data['postal_code'] ?? '')) ?: null,
            'country' => trim((string) ($data['country'] ?? 'Nigeria')) ?: 'Nigeria',
            'department' => $department,
            'status' => $status,
            'joined_date' => $joinedDate,
            'death_notes' => $deathNotes,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
        ];

        if (Schema::hasColumn('members', 'occupation')) {
            $payload['occupation'] = $this->nullableOccupation((string) ($data['occupation'] ?? ''));
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncPortalAccess(Member $member, array $data): void
    {
        if (! Schema::hasColumn('members', 'portal_enabled')) {
            return;
        }

        $updates = [];

        if (array_key_exists('portal_enabled', $data)) {
            $updates['portal_enabled'] = $this->isTruthy($data['portal_enabled']) ? 1 : 0;
        }

        $password = trim((string) ($data['portal_password'] ?? ''));
        if ($password !== '') {
            if (mb_strlen($password) < 6) {
                throw new InvalidArgumentException('Member portal password must be at least 6 characters.');
            }
            if (Schema::hasColumn('members', 'portal_password_hash')) {
                $updates['portal_password_hash'] = Hash::make($password);
            }
            if (! array_key_exists('portal_enabled', $data)) {
                $updates['portal_enabled'] = 1;
            }
        }

        if ($updates !== []) {
            $member->update($updates);
        }
    }

    private function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * @return array{date_of_death: string, death_notes: string|null}
     */
    private function validateDeathFields(string $dateOfDeath, ?string $deathNotes, ?string $joinedDate): array
    {
        $dateOfDeath = trim($dateOfDeath);
        if ($dateOfDeath === '') {
            throw new InvalidArgumentException('Date of death is required.');
        }

        $death = DateTimeImmutable::createFromFormat('Y-m-d', $dateOfDeath);
        if (! $death || $death->format('Y-m-d') !== $dateOfDeath) {
            throw new InvalidArgumentException('Date of death must be in YYYY-MM-DD format.');
        }

        if ($joinedDate) {
            $joined = DateTimeImmutable::createFromFormat('Y-m-d', $joinedDate);
            if ($joined && $death < $joined) {
                throw new InvalidArgumentException('Date of death cannot be before the joined date.');
            }
        }

        if ($death > new DateTimeImmutable('today')) {
            throw new InvalidArgumentException('Date of death cannot be in the future.');
        }

        return [
            'date_of_death' => $dateOfDeath,
            'death_notes' => trim((string) ($deathNotes ?? '')) ?: null,
        ];
    }

    private function nullableOccupation(string $occupation): ?string
    {
        $occupation = trim($occupation);
        if ($occupation === '') {
            return null;
        }
        if (mb_strlen($occupation) > 120) {
            throw new InvalidArgumentException('Occupation must be 120 characters or less.');
        }

        return $occupation;
    }

    private function normalizePhone(string $phone, bool $optional): ?string
    {
        $phone = trim($phone);
        if ($phone === '') {
            return $optional ? null : throw new InvalidArgumentException('Primary phone number is required.');
        }

        $clean = preg_replace('/[^\d+]/', '', $phone) ?? '';
        if ($clean === '' || strlen($clean) < 7 || strlen($clean) > 20) {
            throw new InvalidArgumentException('Phone number must be 7–20 digits.');
        }

        return $clean;
    }

    private function generateMemberCode(): string
    {
        $prefix = self::MEMBER_CODE_PREFIX;
        $last = DB::table('members')
            ->where('member_code', 'like', $prefix.'-%')
            ->orderByDesc('id')
            ->value('member_code');

        $next = 1;
        if (is_string($last) && preg_match('/(\d+)$/', $last, $matches)) {
            $next = (int) $matches[1] + 1;
        }

        for ($i = 0; $i < 5000; $i++) {
            $code = $prefix.'-'.str_pad((string) ($next + $i), 5, '0', STR_PAD_LEFT);
            if (! DB::table('members')->where('member_code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('Unable to allocate a unique member ID.');
    }

    private function storePhoto(?UploadedFile $photo): ?string
    {
        if (! $photo) {
            return null;
        }

        if (! in_array($photo->getMimeType(), self::ALLOWED_PHOTO_MIMES, true)) {
            throw new InvalidArgumentException('Photo must be JPG, PNG, or WebP.');
        }
        if ($photo->getSize() > self::MAX_PHOTO_BYTES) {
            throw new InvalidArgumentException('Photo must be 2 MB or smaller.');
        }

        $ext = match ($photo->getMimeType()) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $path = 'uploads/members/'.Str::random(40).'.'.$ext;
        Storage::disk('public')->put($path, $photo->get());

        return $path;
    }

    private function deletePhotoFile(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function logStatusChange(
        int $memberId,
        ?string $previousStatus,
        string $newStatus,
        string $reason,
        string $changeType,
        ?int $changedBy,
    ): void {
        MemberStatusHistory::query()->create([
            'member_id' => $memberId,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'status_reason' => $reason,
            'change_type' => $changeType,
            'changed_by' => $changedBy,
            'event_date' => now()->toDateString(),
        ]);
    }
}
