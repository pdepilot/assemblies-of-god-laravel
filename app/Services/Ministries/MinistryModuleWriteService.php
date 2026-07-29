<?php

namespace App\Services\Ministries;

use App\Models\MinistryRosterPerson;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class MinistryModuleWriteService
{
    /** @var list<string> */
    private const PARENT_REQUIRED_KEYS = ['children', 'teens'];

    public function __construct(
        private readonly MinistrySettingsReadService $settings,
    ) {}

    /**
     * Register a person on this ministry roster only — does not write to church `members`.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function register(string $ministryKey, array $data, int $adminId): array
    {
        $setting = $this->settings->getByKey($ministryKey);
        if (! $setting || ! ($setting['is_enabled'] ?? false)) {
            throw new InvalidArgumentException('This ministry is not enabled.');
        }

        $fullName = trim((string) ($data['full_name'] ?? ''));
        if ($fullName === '') {
            throw new InvalidArgumentException('Full name is required.');
        }

        $phone = trim((string) ($data['phone'] ?? ''));
        $address = trim((string) ($data['address_line1'] ?? ''));

        $parentName = trim((string) ($data['parent_name'] ?? ''));
        $parentPhone = trim((string) ($data['parent_phone'] ?? ''));
        $parentEmail = trim((string) ($data['parent_email'] ?? ''));

        if (in_array($ministryKey, self::PARENT_REQUIRED_KEYS, true)) {
            if ($parentName === '' || $parentPhone === '') {
                throw new InvalidArgumentException('Parent name and parent phone are required for this ministry.');
            }
        }

        $person = MinistryRosterPerson::query()->create([
            'ministry_key' => $ministryKey,
            'person_code' => $this->nextPersonCode($ministryKey),
            'full_name' => $fullName,
            'first_name' => $this->nullableString($data['first_name'] ?? null) ?? $this->splitName($fullName)[0],
            'last_name' => $this->nullableString($data['last_name'] ?? null) ?? $this->splitName($fullName)[1],
            'date_of_birth' => $this->nullableString($data['date_of_birth'] ?? null),
            'gender' => $this->nullableString($data['gender'] ?? null),
            'phone' => $phone !== '' ? $phone : null,
            'email' => $this->nullableString($data['email'] ?? null),
            'address_line1' => $address !== '' ? $address : null,
            'city' => $this->nullableString($data['city'] ?? null),
            'state' => $this->nullableString($data['state'] ?? null),
            'parent_name' => $parentName !== '' ? $parentName : null,
            'parent_phone' => $parentPhone !== '' ? $parentPhone : null,
            'parent_email' => $parentEmail !== '' ? $parentEmail : null,
            'group_name' => $this->nullableString($data['group_name'] ?? null),
            'role_note' => $this->nullableString($data['role_note'] ?? null),
            'status' => 'active',
            'joined_date' => $this->nullableString($data['joined_date'] ?? null) ?: now()->toDateString(),
            'notes' => $this->nullableString($data['notes'] ?? null),
            'linked_member_id' => null,
        ]);

        return $person->fresh()->toArray();
    }

    /**
     * Snapshot a church member into this ministry roster (does not change the members table).
     *
     * @return array<string, mixed>
     */
    public function importFromChurchMember(string $ministryKey, int $memberId, ?string $notes, int $adminId): array
    {
        $setting = $this->settings->getByKey($ministryKey);
        if (! $setting || ! ($setting['is_enabled'] ?? false)) {
            throw new InvalidArgumentException('This ministry is not enabled.');
        }

        $member = DB::table('members')->where('id', $memberId)->first();
        if (! $member) {
            throw new InvalidArgumentException('Church member not found.');
        }

        $already = false;
        if (Schema::hasTable('ministry_members')) {
            $already = DB::table('ministry_members')
                ->where('ministry_key', $ministryKey)
                ->where('member_id', $memberId)
                ->where('ministry_status', 'active')
                ->exists();
        }

        if (! $already) {
            $already = MinistryRosterPerson::query()
                ->where('ministry_key', $ministryKey)
                ->where('linked_member_id', $memberId)
                ->where('status', 'active')
                ->exists();
        }

        if ($already) {
            throw new InvalidArgumentException('That church member is already on this ministry roster.');
        }

        if (Schema::hasTable('ministry_members')) {
            DB::table('ministry_members')->updateOrInsert(
                ['member_id' => $memberId, 'ministry_key' => $ministryKey],
                [
                    'ministry_status' => 'active',
                    'join_date' => now()->toDateString(),
                    'notes' => $notes,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $member = DB::table('members')->where('id', $memberId)->first();

            return [
                'id' => $memberId,
                'source' => 'member',
                'full_name' => (string) ($member->full_name ?? ''),
                'member_id' => $memberId,
            ];
        }

        $person = MinistryRosterPerson::query()->create([
            'ministry_key' => $ministryKey,
            'person_code' => $this->nextPersonCode($ministryKey),
            'full_name' => (string) $member->full_name,
            'first_name' => $member->first_name ?? null,
            'last_name' => $member->last_name ?? null,
            'date_of_birth' => $member->date_of_birth ?? null,
            'gender' => $member->gender ?? null,
            'phone' => $member->phone ?? null,
            'email' => $member->email ?? null,
            'address_line1' => $member->address_line1 ?? null,
            'city' => $member->city ?? null,
            'state' => $member->state ?? null,
            'parent_name' => $member->parent_name ?? null,
            'parent_phone' => $member->parent_phone ?? null,
            'parent_email' => $member->parent_email ?? null,
            'status' => 'active',
            'joined_date' => now()->toDateString(),
            'notes' => $notes,
            'linked_member_id' => $memberId,
        ]);

        return $person->fresh()->toArray();
    }

    public function archive(string $ministryKey, int $personId): void
    {
        $person = MinistryRosterPerson::query()
            ->where('id', $personId)
            ->where('ministry_key', $ministryKey)
            ->first();

        if ($person) {
            $person->update(['status' => 'inactive']);

            return;
        }

        if (Schema::hasTable('ministry_members')) {
            $updated = DB::table('ministry_members')
                ->where('member_id', $personId)
                ->where('ministry_key', $ministryKey)
                ->where('ministry_status', 'active')
                ->update([
                    'ministry_status' => 'inactive',
                    'updated_at' => now(),
                ]);

            if ($updated > 0) {
                return;
            }
        }

        throw new InvalidArgumentException('Roster person not found in this ministry.');
    }

    public function recordAttendance(
        string $ministryKey,
        string $source,
        int $personId,
        string $serviceDate,
        bool $present,
        int $adminId,
        ?string $notes = null,
    ): void {
        if ($source === 'member' && Schema::hasTable('ministry_attendance')) {
            $enrolled = DB::table('ministry_members')
                ->where('member_id', $personId)
                ->where('ministry_key', $ministryKey)
                ->where('ministry_status', 'active')
                ->exists();

            if (! $enrolled) {
                throw new InvalidArgumentException('Member not found in this ministry.');
            }

            DB::table('ministry_attendance')->updateOrInsert(
                [
                    'member_id' => $personId,
                    'ministry_type' => $ministryKey,
                    'service_date' => $serviceDate,
                    'service_type' => 'weekly',
                ],
                [
                    'present' => $present,
                    'notes' => $notes,
                    'recorded_by' => $adminId,
                ]
            );

            return;
        }

        $this->recordRosterAttendance($ministryKey, $personId, $serviceDate, $present, $adminId, $notes);
    }

    private function recordRosterAttendance(
        string $ministryKey,
        int $rosterPersonId,
        string $serviceDate,
        bool $present,
        int $adminId,
        ?string $notes = null,
    ): void {
        $enrolled = MinistryRosterPerson::query()
            ->where('id', $rosterPersonId)
            ->where('ministry_key', $ministryKey)
            ->where('status', 'active')
            ->exists();

        if (! $enrolled) {
            throw new InvalidArgumentException('Person not found on this ministry roster.');
        }

        DB::table('ministry_roster_attendance')->updateOrInsert(
            [
                'roster_person_id' => $rosterPersonId,
                'ministry_key' => $ministryKey,
                'service_date' => $serviceDate,
                'service_type' => 'weekly',
            ],
            [
                'present' => $present,
                'notes' => $notes,
                'recorded_by' => $adminId,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    private function nextPersonCode(string $ministryKey): string
    {
        $prefix = 'MR-'.Str::upper(Str::substr($ministryKey, 0, 3));
        $seq = (int) DB::table('ministry_roster_people')
            ->where('ministry_key', $ministryKey)
            ->count() + 1;

        do {
            $code = sprintf('%s-%05d', $prefix, $seq);
            $exists = DB::table('ministry_roster_people')
                ->where('ministry_key', $ministryKey)
                ->where('person_code', $code)
                ->exists();
            $seq++;
        } while ($exists);

        return $code;
    }

    /** @return array{0: ?string, 1: ?string} */
    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2) ?: [];

        return [$parts[0] ?? null, $parts[1] ?? null];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
