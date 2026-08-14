<?php

namespace App\Services\Ministries;

use App\Models\MinistryAgeTransfer;
use App\Models\MinistryRosterPerson;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Automatic Children → Teens (age 13+) and Teens → Youth (age 20+) transfers.
 */
final class MinistryAgeTransferService
{
    /** @var list<string> */
    public const LADDER_KEYS = ['children', 'teens', 'youths'];

    /** @var array<string, string> */
    private const DISPLAY_NAMES = [
        'children' => 'Children Ministry',
        'teens' => 'Teen Ministry',
        'youths' => 'Youth Ministry',
    ];

    public function __construct(
        private readonly MinistrySettingsReadService $settings,
    ) {}

    /**
     * @return array{
     *   due: list<array<string, mixed>>,
     *   missing_dob: list<array<string, mixed>>,
     *   counts: array{children_to_teens: int, teens_to_youths: int, missing_dob: int}
     * }
     */
    public function preview(): array
    {
        $due = [];
        $missingDob = [];
        $seen = [];

        foreach ($this->collectCandidates() as $candidate) {
            $fingerprint = $this->fingerprint($candidate);
            if (isset($seen[$fingerprint])) {
                continue;
            }
            $seen[$fingerprint] = true;

            $age = $this->settings->memberAge($candidate['date_of_birth'] ?? null);
            if ($age === null) {
                $missingDob[] = $candidate;
                continue;
            }

            $toKey = $this->targetKeyFor((string) $candidate['from_key'], $age);
            if ($toKey === null) {
                continue;
            }

            $due[] = array_merge($candidate, [
                'age' => $age,
                'to_key' => $toKey,
                'to_name' => self::DISPLAY_NAMES[$toKey] ?? $toKey,
            ]);
        }

        $childrenToTeens = count(array_filter($due, static fn (array $r): bool => ($r['from_key'] ?? '') === 'children'));
        $teensToYouths = count(array_filter($due, static fn (array $r): bool => ($r['from_key'] ?? '') === 'teens'));

        return [
            'due' => $due,
            'missing_dob' => $missingDob,
            'counts' => [
                'children_to_teens' => $childrenToTeens,
                'teens_to_youths' => $teensToYouths,
                'missing_dob' => count($missingDob),
            ],
        ];
    }

    /**
     * @return array{transferred: int, skipped: int, items: list<array<string, mixed>>}
     */
    public function run(string $source, ?int $adminId = null, bool $dryRun = false): array
    {
        $source = in_array($source, ['schedule', 'on_save', 'manual'], true) ? $source : 'manual';
        $preview = $this->preview();
        $items = [];
        $transferred = 0;

        if ($dryRun) {
            return [
                'transferred' => 0,
                'skipped' => count($preview['missing_dob']),
                'items' => $preview['due'],
                'dry_run' => true,
                'counts' => $preview['counts'],
            ];
        }

        foreach ($preview['due'] as $row) {
            DB::transaction(function () use ($row, $source, $adminId, &$items, &$transferred): void {
                $this->applyTransfer($row, $source, $adminId);
                $items[] = $row;
                $transferred++;
            });
        }

        return [
            'transferred' => $transferred,
            'skipped' => count($preview['missing_dob']),
            'items' => $items,
            'dry_run' => false,
            'counts' => $preview['counts'],
        ];
    }

    /**
     * Sync a single church member after create/update.
     *
     * @return array<string, mixed>|null Transfer row if moved
     */
    public function syncMember(int $memberId, string $source = 'on_save', ?int $adminId = null): ?array
    {
        if (! Schema::hasTable('members')) {
            return null;
        }

        $member = DB::table('members')->where('id', $memberId)->first();
        if (! $member) {
            return null;
        }

        $fromKey = $this->resolveMemberMinistryKey((array) $member);
        if ($fromKey === null || ! in_array($fromKey, ['children', 'teens'], true)) {
            return null;
        }

        $dob = $member->date_of_birth
            ? Carbon::parse($member->date_of_birth)->toDateString()
            : null;
        $age = $this->settings->memberAge($dob);
        $toKey = $age !== null ? $this->targetKeyFor($fromKey, $age) : null;
        if ($toKey === null) {
            return null;
        }

        $row = [
            'member_id' => $memberId,
            'roster_person_id' => null,
            'from_key' => $fromKey,
            'to_key' => $toKey,
            'to_name' => self::DISPLAY_NAMES[$toKey] ?? $toKey,
            'age' => $age,
            'full_name' => (string) ($member->full_name ?? ''),
            'date_of_birth' => $dob,
        ];

        DB::transaction(fn () => $this->applyTransfer($row, $source, $adminId));

        return $row;
    }

    /**
     * Sync a single roster person after register.
     *
     * @return array<string, mixed>|null
     */
    public function syncRosterPerson(int $rosterPersonId, string $source = 'on_save', ?int $adminId = null): ?array
    {
        $person = MinistryRosterPerson::query()->find($rosterPersonId);
        if (! $person || (string) $person->status !== 'active') {
            return null;
        }

        $fromKey = (string) $person->ministry_key;
        if (! in_array($fromKey, ['children', 'teens'], true)) {
            return null;
        }

        $dob = $person->date_of_birth?->toDateString();
        $age = $this->settings->memberAge($dob);
        $toKey = $age !== null ? $this->targetKeyFor($fromKey, $age) : null;
        if ($toKey === null) {
            return null;
        }

        $row = [
            'member_id' => $person->linked_member_id ? (int) $person->linked_member_id : null,
            'roster_person_id' => (int) $person->id,
            'from_key' => $fromKey,
            'to_key' => $toKey,
            'to_name' => self::DISPLAY_NAMES[$toKey] ?? $toKey,
            'age' => $age,
            'full_name' => (string) $person->full_name,
            'date_of_birth' => $dob,
        ];

        DB::transaction(fn () => $this->applyTransfer($row, $source, $adminId));

        return $row;
    }

    public function targetKeyFor(string $fromKey, int $age): ?string
    {
        if ($fromKey === 'children' && $age >= 13) {
            return 'teens';
        }

        if ($fromKey === 'teens' && $age >= 20) {
            return 'youths';
        }

        return null;
    }

    /** @return list<array<string, mixed>> */
    private function collectCandidates(): array
    {
        $out = [];

        if (Schema::hasTable('ministry_roster_people')) {
            $rows = MinistryRosterPerson::query()
                ->whereIn('ministry_key', ['children', 'teens'])
                ->where('status', 'active')
                ->get();

            foreach ($rows as $person) {
                $out[] = [
                    'member_id' => $person->linked_member_id ? (int) $person->linked_member_id : null,
                    'roster_person_id' => (int) $person->id,
                    'from_key' => (string) $person->ministry_key,
                    'from_name' => self::DISPLAY_NAMES[(string) $person->ministry_key] ?? (string) $person->ministry_key,
                    'full_name' => (string) $person->full_name,
                    'date_of_birth' => $person->date_of_birth?->toDateString(),
                    'source_store' => 'roster',
                ];
            }
        }

        if (Schema::hasTable('ministry_members') && Schema::hasTable('members')) {
            $rows = DB::table('ministry_members as mm')
                ->join('members as m', 'm.id', '=', 'mm.member_id')
                ->whereIn('mm.ministry_key', ['children', 'teens'])
                ->where('mm.ministry_status', 'active')
                ->where('m.status', '<>', 'deceased')
                ->select([
                    'm.id as member_id',
                    'mm.ministry_key as from_key',
                    'm.full_name',
                    'm.date_of_birth',
                ])
                ->get();

            foreach ($rows as $row) {
                $out[] = [
                    'member_id' => (int) $row->member_id,
                    'roster_person_id' => null,
                    'from_key' => (string) $row->from_key,
                    'from_name' => self::DISPLAY_NAMES[(string) $row->from_key] ?? (string) $row->from_key,
                    'full_name' => (string) $row->full_name,
                    'date_of_birth' => $row->date_of_birth
                        ? Carbon::parse($row->date_of_birth)->toDateString()
                        : null,
                    'source_store' => 'ministry_members',
                ];
            }
        }

        if (Schema::hasTable('members')) {
            $childrenAliases = $this->departmentAliases('children');
            $teensAliases = $this->departmentAliases('teens');

            $members = DB::table('members')
                ->where('status', '<>', 'deceased')
                ->whereNotNull('department')
                ->where('department', '<>', '')
                ->get(['id', 'full_name', 'date_of_birth', 'department']);

            foreach ($members as $member) {
                $dept = trim((string) $member->department);
                $fromKey = null;
                if ($this->departmentMatches($dept, $childrenAliases)) {
                    $fromKey = 'children';
                } elseif ($this->departmentMatches($dept, $teensAliases)) {
                    $fromKey = 'teens';
                }
                if ($fromKey === null) {
                    continue;
                }

                $out[] = [
                    'member_id' => (int) $member->id,
                    'roster_person_id' => null,
                    'from_key' => $fromKey,
                    'from_name' => self::DISPLAY_NAMES[$fromKey] ?? $fromKey,
                    'full_name' => (string) $member->full_name,
                    'date_of_birth' => $member->date_of_birth
                        ? Carbon::parse($member->date_of_birth)->toDateString()
                        : null,
                    'source_store' => 'members.department',
                ];
            }
        }

        return $out;
    }

    /** @param array<string, mixed> $row */
    private function applyTransfer(array $row, string $source, ?int $adminId): void
    {
        $fromKey = (string) $row['from_key'];
        $toKey = (string) $row['to_key'];
        $memberId = isset($row['member_id']) ? (int) $row['member_id'] : null;
        $rosterPersonId = isset($row['roster_person_id']) ? (int) $row['roster_person_id'] : null;
        if ($memberId === 0) {
            $memberId = null;
        }
        if ($rosterPersonId === 0) {
            $rosterPersonId = null;
        }

        $displayName = self::DISPLAY_NAMES[$toKey] ?? $toKey;

        if ($memberId !== null && Schema::hasTable('members')) {
            DB::table('members')->where('id', $memberId)->update([
                'department' => $displayName,
                'updated_at' => now(),
            ]);
        }

        if ($memberId !== null && Schema::hasTable('ministry_members')) {
            DB::table('ministry_members')
                ->where('member_id', $memberId)
                ->where('ministry_key', $fromKey)
                ->where('ministry_status', 'active')
                ->update([
                    'ministry_status' => 'inactive',
                    'updated_at' => now(),
                ]);

            DB::table('ministry_members')->updateOrInsert(
                ['member_id' => $memberId, 'ministry_key' => $toKey],
                [
                    'ministry_status' => 'active',
                    'join_date' => now()->toDateString(),
                    'notes' => 'Auto age transfer from '.$fromKey,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        if (Schema::hasTable('ministry_roster_people')) {
            $rosterQuery = MinistryRosterPerson::query()->where('status', 'active');
            if ($rosterPersonId !== null) {
                $rosterQuery->where('id', $rosterPersonId);
            } elseif ($memberId !== null) {
                $rosterQuery->where('linked_member_id', $memberId)->where('ministry_key', $fromKey);
            } else {
                $rosterQuery = null;
            }

            if ($rosterQuery !== null) {
                $rosterQuery->get()->each(function (MinistryRosterPerson $person) use ($toKey): void {
                    $person->update(['ministry_key' => $toKey]);
                });
            }
        }

        MinistryAgeTransfer::query()->create([
            'member_id' => $memberId,
            'roster_person_id' => $rosterPersonId,
            'from_key' => $fromKey,
            'to_key' => $toKey,
            'age_at_transfer' => (int) ($row['age'] ?? 0),
            'full_name' => (string) ($row['full_name'] ?? ''),
            'date_of_birth' => $row['date_of_birth'] ?? null,
            'source' => $source,
            'admin_id' => $adminId,
            'transferred_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $member */
    private function resolveMemberMinistryKey(array $member): ?string
    {
        $memberId = (int) ($member['id'] ?? 0);

        if ($memberId > 0 && Schema::hasTable('ministry_members')) {
            $key = DB::table('ministry_members')
                ->where('member_id', $memberId)
                ->whereIn('ministry_key', ['children', 'teens'])
                ->where('ministry_status', 'active')
                ->value('ministry_key');
            if (is_string($key) && $key !== '') {
                return $key;
            }
        }

        $dept = trim((string) ($member['department'] ?? ''));
        if ($dept === '') {
            return null;
        }
        if ($this->departmentMatches($dept, $this->departmentAliases('children'))) {
            return 'children';
        }
        if ($this->departmentMatches($dept, $this->departmentAliases('teens'))) {
            return 'teens';
        }

        return null;
    }

    /** @return list<string> */
    private function departmentAliases(string $ministryKey): array
    {
        $name = self::DISPLAY_NAMES[$ministryKey] ?? $ministryKey;
        $byKey = [
            'children' => ['Children Ministry', 'Children'],
            'teens' => ['Teen Ministry', 'Teens', 'Teen'],
            'youths' => ['Youth Ministry', 'Youth', 'Youths'],
        ];

        return array_values(array_unique(array_merge([$name], $byKey[$ministryKey] ?? [])));
    }

    /** @param list<string> $aliases */
    private function departmentMatches(string $department, array $aliases): bool
    {
        foreach ($aliases as $alias) {
            if (strcasecmp(trim($department), trim($alias)) === 0) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, mixed> $candidate */
    private function fingerprint(array $candidate): string
    {
        $memberId = (int) ($candidate['member_id'] ?? 0);
        $rosterId = (int) ($candidate['roster_person_id'] ?? 0);
        $from = (string) ($candidate['from_key'] ?? '');

        if ($memberId > 0) {
            return 'm:'.$memberId.':'.$from;
        }
        if ($rosterId > 0) {
            return 'r:'.$rosterId.':'.$from;
        }

        return 'n:'.md5(strtolower((string) ($candidate['full_name'] ?? '')).'|'.($candidate['date_of_birth'] ?? '').'|'.$from);
    }
}
