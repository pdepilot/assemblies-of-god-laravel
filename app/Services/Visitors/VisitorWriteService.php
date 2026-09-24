<?php

namespace App\Services\Visitors;

use App\Models\Visitor;
use App\Services\Members\MemberReadService;
use App\Services\Members\MemberWriteService;
use App\Services\Security\SecurityAuditService;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class VisitorWriteService
{
    private const ALLOWED_PHOTO_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    private const MAX_PHOTO_BYTES = 2097152;

    public function __construct(
        private readonly VisitorReadService $read,
        private readonly MemberReadService $members,
        private readonly MemberWriteService $memberWrite,
        private readonly SecurityAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(array $data, ?UploadedFile $photo, int $adminId): array
    {
        $validated = $this->validateVisitorData($data, true);
        $visitorCode = $this->generateVisitorCode();
        $photoPath = $this->storePhoto($photo);

        $visitor = Visitor::query()->create([
            ...$validated,
            'visitor_code' => $visitorCode,
            'photo_path' => $photoPath,
            'created_by' => $adminId,
        ]);

        $this->audit->log(
            'visitor_created',
            'New visitor '.$validated['full_name'].' ('.$visitorCode.') was registered.',
            $adminId,
            'info',
            ['visitor_id' => (int) $visitor->id, 'visitor_code' => $visitorCode],
        );

        return $this->read->getVisitor((int) $visitor->id) ?? $visitor->toArray();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(int $id, array $data, ?UploadedFile $photo, bool $removePhoto): array
    {
        $visitor = Visitor::query()->findOrFail($id);

        if ((string) $visitor->follow_up_status === 'promoted') {
            throw new InvalidArgumentException('Promoted visitors cannot be edited. View the member record instead.');
        }

        $validated = $this->validateVisitorData($data, false);
        $photoPath = $visitor->photo_path;

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

        $visitor->update([
            ...$validated,
            'photo_path' => $photoPath,
        ]);

        return $this->read->getVisitor($id) ?? $visitor->fresh()->toArray();
    }

    /** @return array<string, mixed> */
    public function recordReturnVisit(int $id, ?string $visitDate, ?string $serviceAttended): array
    {
        $visitor = Visitor::query()->find($id);
        if (! $visitor) {
            throw new InvalidArgumentException('Visitor not found.');
        }

        if ((string) $visitor->follow_up_status === 'promoted') {
            throw new InvalidArgumentException('This visitor has already been promoted to membership.');
        }

        $date = trim((string) ($visitDate ?? ''));
        if ($date === '') {
            $date = now()->toDateString();
        }

        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        if (! $parsed || $parsed->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('Visit date must be in YYYY-MM-DD format.');
        }

        $service = trim((string) ($serviceAttended ?? ''));
        if ($service !== '' && ! in_array($service, VisitorReadService::SERVICES, true)) {
            throw new InvalidArgumentException('Please select a valid service.');
        }

        $status = (string) $visitor->follow_up_status;
        if (in_array($status, ['new', 'inactive'], true)) {
            $status = 'contacted';
        }

        $visitor->update([
            'visit_count' => ((int) $visitor->visit_count) + 1,
            'last_visit_date' => $date,
            'service_attended' => $service !== '' ? $service : $visitor->service_attended,
            'follow_up_status' => $status,
        ]);

        $this->audit->log(
            'visitor_return_visit',
            'Return visit recorded for '.$visitor->full_name.'.',
            null,
            'info',
            ['visitor_id' => $id],
        );

        return $this->read->getVisitor($id) ?? $visitor->fresh()->toArray();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{visitor: array<string, mixed>, member: array<string, mixed>}
     */
    public function promoteToMember(int $id, array $data, int $adminId): array
    {
        $visitor = Visitor::query()->find($id);
        if (! $visitor) {
            throw new InvalidArgumentException('Visitor not found.');
        }

        if ((string) $visitor->follow_up_status === 'promoted') {
            throw new InvalidArgumentException('This visitor has already been promoted.');
        }

        if (empty($data['confirm_membership'])) {
            throw new InvalidArgumentException('Please confirm full membership before promoting this visitor.');
        }

        $department = $this->members->canonicalDepartmentLabel((string) ($data['department'] ?? ''));
        if ($department === '') {
            $department = $this->members->canonicalDepartmentLabel((string) ($visitor->interested_department ?? ''));
        }
        if ($department === '' || ! in_array($department, $this->members->listDepartmentOptions(), true)) {
            $department = 'Member';
        }

        $joinedDate = trim((string) ($data['joined_date'] ?? ''));
        if ($joinedDate === '') {
            $joinedDate = now()->toDateString();
        }

        $notes = trim((string) ($data['notes'] ?? ''));
        if ($notes === '' && $visitor->notes) {
            $notes = 'Promoted from visitor '.$visitor->visitor_code.'. '.$visitor->notes;
        } elseif ($notes === '') {
            $notes = 'Promoted from visitor '.$visitor->visitor_code.'.';
        }

        $memberData = [
            'full_name' => trim((string) ($data['full_name'] ?? $visitor->full_name)),
            'email' => trim((string) ($data['email'] ?? $visitor->email ?? '')),
            'phone' => (string) ($data['phone'] ?? $visitor->phone),
            'phone_alt' => (string) ($data['phone_alt'] ?? $visitor->phone_alt ?? ''),
            'gender' => (string) ($data['gender'] ?? $visitor->gender),
            'address_line1' => trim((string) ($data['address_line1'] ?? $visitor->address_line1 ?? '')),
            'address_line2' => trim((string) ($data['address_line2'] ?? $visitor->address_line2 ?? '')),
            'city' => trim((string) ($data['city'] ?? $visitor->city ?? '')),
            'state' => trim((string) ($data['state'] ?? $visitor->state ?? '')),
            'postal_code' => trim((string) ($data['postal_code'] ?? $visitor->postal_code ?? '')),
            'country' => trim((string) ($data['country'] ?? $visitor->country ?? 'Nigeria')),
            'department' => $department,
            'joined_date' => $joinedDate,
            'notes' => $notes,
        ];

        return DB::transaction(function () use ($visitor, $memberData, $adminId, $id) {
            $member = $this->memberWrite->createFromVisitor(
                $memberData,
                $visitor->photo_path,
                $adminId,
            );

            $visitor->update([
                'follow_up_status' => 'promoted',
                'promoted_member_id' => $member['id'],
                'promoted_at' => now(),
            ]);

            $updatedVisitor = $this->read->getVisitor($id);
            if (! $updatedVisitor) {
                throw new RuntimeException('Visitor was promoted but could not be loaded.');
            }

            $this->audit->log(
                'visitor_promoted',
                'Visitor '.$visitor->full_name.' was promoted to membership.',
                $adminId,
                'info',
                ['visitor_id' => $id, 'member_id' => $member['id'] ?? null],
            );

            return [
                'visitor' => $updatedVisitor,
                'member' => $member,
            ];
        });
    }

    public function delete(int $id): void
    {
        $visitor = Visitor::query()->find($id);
        if (! $visitor) {
            throw new InvalidArgumentException('Visitor not found.');
        }

        $name = (string) $visitor->full_name;
        if ($visitor->photo_path) {
            $this->deletePhotoFile($visitor->photo_path);
        }

        $visitor->delete();

        $this->audit->log(
            'visitor_deleted',
            'Visitor '.$name.' was removed.',
            null,
            'warning',
            ['visitor_id' => $id],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validateVisitorData(array $data, bool $isCreate): array
    {
        $fullName = trim((string) ($data['full_name'] ?? ''));
        if ($fullName === '' || mb_strlen($fullName) > 255) {
            throw new InvalidArgumentException('Full name is required (max 255 characters).');
        }

        $email = trim((string) ($data['email'] ?? ''));
        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Please enter a valid email address.');
        }

        $phone = $this->normalizePhone((string) ($data['phone'] ?? ''));
        $phoneAlt = $this->normalizePhone((string) ($data['phone_alt'] ?? ''), true);

        $gender = (string) ($data['gender'] ?? 'unspecified');
        if (! in_array($gender, ['male', 'female', 'other', 'unspecified'], true)) {
            $gender = 'unspecified';
        }

        $firstVisitDate = trim((string) ($data['first_visit_date'] ?? ''));
        if ($firstVisitDate === '') {
            $firstVisitDate = now()->toDateString();
        }
        $firstVisit = DateTimeImmutable::createFromFormat('Y-m-d', $firstVisitDate);
        if (! $firstVisit || $firstVisit->format('Y-m-d') !== $firstVisitDate) {
            throw new InvalidArgumentException('First visit date must be in YYYY-MM-DD format.');
        }

        $lastVisitDate = trim((string) ($data['last_visit_date'] ?? ''));
        $lastVisit = null;
        if ($lastVisitDate !== '') {
            $parsedLast = DateTimeImmutable::createFromFormat('Y-m-d', $lastVisitDate);
            if (! $parsedLast || $parsedLast->format('Y-m-d') !== $lastVisitDate) {
                throw new InvalidArgumentException('Last visit date must be in YYYY-MM-DD format.');
            }
            $lastVisit = $lastVisitDate;
        }

        $visitCount = (int) ($data['visit_count'] ?? 1);
        if ($visitCount < 1) {
            $visitCount = 1;
        }

        $serviceAttended = trim((string) ($data['service_attended'] ?? ''));
        if ($serviceAttended !== '' && ! in_array($serviceAttended, VisitorReadService::SERVICES, true)) {
            throw new InvalidArgumentException('Please select a valid service attended.');
        }

        $howHeard = trim((string) ($data['how_heard'] ?? ''));
        if ($howHeard !== '' && ! in_array($howHeard, VisitorReadService::HOW_HEARD, true)) {
            throw new InvalidArgumentException('Please select how they heard about the church.');
        }

        $interestedDepartment = $this->members->canonicalDepartmentLabel((string) ($data['interested_department'] ?? ''));
        if ($interestedDepartment !== '' && ! in_array($interestedDepartment, $this->members->listDepartmentOptions(), true)) {
            throw new InvalidArgumentException('Please select a valid interested department.');
        }

        $followUp = (string) ($data['follow_up_status'] ?? 'new');
        if (! in_array($followUp, VisitorReadService::FOLLOW_UP_STATUSES, true) || $followUp === 'promoted') {
            $followUp = 'new';
        }

        return [
            'full_name' => $fullName,
            'email' => $email !== '' ? strtolower($email) : null,
            'phone' => $phone,
            'phone_alt' => $phoneAlt,
            'gender' => $gender,
            'address_line1' => trim((string) ($data['address_line1'] ?? '')),
            'address_line2' => trim((string) ($data['address_line2'] ?? '')) ?: null,
            'city' => trim((string) ($data['city'] ?? '')),
            'state' => trim((string) ($data['state'] ?? '')),
            'postal_code' => trim((string) ($data['postal_code'] ?? '')) ?: null,
            'country' => trim((string) ($data['country'] ?? 'Nigeria')) ?: 'Nigeria',
            'first_visit_date' => $firstVisitDate,
            'last_visit_date' => $lastVisit,
            'visit_count' => $visitCount,
            'service_attended' => $serviceAttended !== '' ? $serviceAttended : null,
            'how_heard' => $howHeard !== '' ? $howHeard : null,
            'interested_department' => $interestedDepartment !== '' ? $interestedDepartment : null,
            'follow_up_status' => $followUp,
            'prayer_request' => trim((string) ($data['prayer_request'] ?? '')) ?: null,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
        ];
    }

    private function normalizePhone(string $phone, bool $optional = false): ?string
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

    private function generateVisitorCode(): string
    {
        $next = (int) DB::table('visitors')->max('id') + 1;

        for ($i = 0; $i < 5000; $i++) {
            $code = 'V'.str_pad((string) ($next + $i), 3, '0', STR_PAD_LEFT);
            if (! DB::table('visitors')->where('visitor_code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('Unable to allocate a unique visitor code.');
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

        $path = 'uploads/visitors/'.Str::random(40).'.'.$ext;
        Storage::disk('public')->put($path, $photo->get());

        return $path;
    }

    private function deletePhotoFile(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
