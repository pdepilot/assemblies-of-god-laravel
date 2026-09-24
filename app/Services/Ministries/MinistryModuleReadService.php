<?php

namespace App\Services\Ministries;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class MinistryModuleReadService
{
    public function __construct(
        private readonly MinistrySettingsReadService $settings,
    ) {}

    /** @return array<string, mixed>|null */
    public function getSetting(string $ministryKey): ?array
    {
        return $this->settings->getByKey($ministryKey);
    }

    public function requiresParentDetails(string $ministryKey): bool
    {
        return in_array($ministryKey, ['children', 'teens'], true);
    }

    /**
     * Headcounts per enabled ministry from church members.department
     * (plus any active ministry-roster-only people), and a grand total.
     *
     * @return array{ministries: list<array{key: string, name: string, total: int, department: string}>, grand_total: int}
     */
    public function getAllRosterTotals(): array
    {
        $enabled = $this->settings->enabled();
        $departmentCounts = $this->memberCountsByDepartment();
        $rosterCounts = $this->activeRosterCountsByKey();

        $ministries = [];
        $grandTotal = 0;

        foreach ($enabled as $setting) {
            $key = (string) ($setting['ministry_key'] ?? '');
            if ($key === '') {
                continue;
            }

            $name = (string) ($setting['name'] ?? $key);
            $aliases = $this->departmentAliasesFor($key, $name);
            $fromMembers = $this->sumMatchingDepartments($departmentCounts, $aliases);
            $fromRoster = (int) ($rosterCounts[$key] ?? 0);
            // Prefer church-directory department counts; add roster-only if that source is empty.
            $total = $fromMembers > 0 ? $fromMembers : $fromRoster;
            $grandTotal += $total;

            $ministries[] = [
                'key' => $key,
                'name' => $name,
                'department' => $this->preferredDepartmentFilter($departmentCounts, $aliases, $name),
                'total' => $total,
            ];
        }

        return [
            'ministries' => $ministries,
            'grand_total' => $grandTotal,
        ];
    }

    /**
     * @param  array<string, int>  $departmentCounts
     * @param  list<string>  $aliases
     */
    private function preferredDepartmentFilter(array $departmentCounts, array $aliases, string $fallback): string
    {
        foreach ($aliases as $alias) {
            foreach ($departmentCounts as $department => $count) {
                if ((int) $count > 0 && strcasecmp(trim((string) $department), trim($alias)) === 0) {
                    return (string) $department;
                }
            }
        }

        return $fallback;
    }

    /**
     * @return array<string, int> department label => count
     */
    private function memberCountsByDepartment(): array
    {
        if (! Schema::hasTable('members')) {
            return [];
        }

        return DB::table('members')
            ->whereNotNull('department')
            ->where('department', '<>', '')
            ->where('status', '<>', 'deceased')
            ->selectRaw('department, COUNT(*) as total')
            ->groupBy('department')
            ->pluck('total', 'department')
            ->map(static fn ($v): int => (int) $v)
            ->all();
    }

    /**
     * @return array<string, int> ministry_key => count
     */
    private function activeRosterCountsByKey(): array
    {
        if (! Schema::hasTable('ministry_roster_people')) {
            return [];
        }

        return DB::table('ministry_roster_people')
            ->where('status', 'active')
            ->selectRaw('ministry_key, COUNT(*) as total')
            ->groupBy('ministry_key')
            ->pluck('total', 'ministry_key')
            ->map(static fn ($v): int => (int) $v)
            ->all();
    }

    /**
     * @param  array<string, int>  $departmentCounts
     * @param  list<string>  $aliases
     */
    private function sumMatchingDepartments(array $departmentCounts, array $aliases): int
    {
        $normalized = [];
        foreach ($aliases as $alias) {
            $key = mb_strtolower(trim($alias));
            if ($key !== '') {
                $normalized[$key] = true;
            }
        }

        $total = 0;
        foreach ($departmentCounts as $department => $count) {
            if (isset($normalized[mb_strtolower(trim((string) $department))])) {
                $total += (int) $count;
            }
        }

        return $total;
    }

    /** @return list<string> */
    private function departmentAliasesFor(string $ministryKey, string $settingName): array
    {
        $aliases = [$settingName];

        $byKey = [
            'children' => ['Children Ministry', 'Children'],
            'teens' => ['Teen Ministry', 'Teens', 'Teen'],
            'youths' => ['Youth Ministry', 'Youth', 'Youths'],
            'men' => ["Men's Ministry", 'Men'],
            'women' => ["Women's Ministry", 'Women'],
            'widows' => ['Widows', 'Widows Ministry'],
            'music' => ['Music Department', 'Music'],
            'choir' => ['Choir'],
            'ushers' => ['Ushering', 'Ushers'],
            'media' => ['Media Team', 'Media'],
        ];

        foreach ($byKey[$ministryKey] ?? [] as $alias) {
            $aliases[] = $alias;
        }

        return array_values(array_unique($aliases));
    }

    /** @return array<string, int> */
    public function getStats(string $ministryKey): array
    {
        $monthStart = now()->startOfMonth()->toDateString();

        if ($this->hasLegacyMemberships($ministryKey)) {
            $total = (int) DB::table('ministry_members')
                ->where('ministry_key', $ministryKey)
                ->where('ministry_status', 'active')
                ->count();

            $newMonth = (int) DB::table('ministry_members')
                ->where('ministry_key', $ministryKey)
                ->where('join_date', '>=', $monthStart)
                ->count();

            $attendanceMonth = Schema::hasTable('ministry_attendance')
                ? (int) DB::table('ministry_attendance')
                    ->where('ministry_type', $ministryKey)
                    ->where('service_date', '>=', $monthStart)
                    ->where('present', true)
                    ->count()
                : 0;
        } else {
            $total = (int) DB::table('ministry_roster_people')
                ->where('ministry_key', $ministryKey)
                ->where('status', 'active')
                ->count();

            $newMonth = (int) DB::table('ministry_roster_people')
                ->where('ministry_key', $ministryKey)
                ->where('joined_date', '>=', $monthStart)
                ->count();

            $attendanceMonth = (int) DB::table('ministry_roster_attendance')
                ->where('ministry_key', $ministryKey)
                ->where('service_date', '>=', $monthStart)
                ->where('present', true)
                ->count();
        }

        $birthdays = $this->upcomingBirthdays($ministryKey, 30);
        $todayBirthdays = count(array_filter($birthdays, static fn (array $r): bool => ! empty($r['is_today'])));

        return [
            'total' => $total,
            'new_this_month' => $newMonth,
            'today_birthdays' => $todayBirthdays,
            'upcoming_birthdays' => count($birthdays),
            'attendance_month' => $attendanceMonth,
        ];
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, pages: int}
     */
    public function listMembers(string $ministryKey, string $query, int $page, int $perPage): array
    {
        if ($this->hasLegacyMemberships($ministryKey)) {
            return $this->listLegacyMembers($ministryKey, $query, $page, $perPage);
        }

        return $this->listRosterMembers($ministryKey, $query, $page, $perPage);
    }

    /**
     * All active people for attendance / select dropdowns (not paginated).
     *
     * @return list<array<string, mixed>>
     */
    public function listMembersForSelect(string $ministryKey): array
    {
        if ($this->hasLegacyMemberships($ministryKey)) {
            return $this->legacyMembersQuery($ministryKey)->get()->map(
                fn ($row) => $this->formatLegacyMemberRow((array) $row, $ministryKey)
            )->all();
        }

        return DB::table('ministry_roster_people')
            ->where('ministry_key', $ministryKey)
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get()
            ->map(fn ($row) => $this->formatRow((array) $row + ['source' => 'roster']))
            ->all();
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, pages: int}
     */
    private function listLegacyMembers(string $ministryKey, string $query, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $base = $this->legacyMembersQuery($ministryKey);

        if ($query !== '') {
            $like = '%'.$query.'%';
            $base->where(function ($q) use ($like) {
                $q->where('m.full_name', 'like', $like)
                    ->orWhere('m.member_code', 'like', $like)
                    ->orWhere('m.parent_name', 'like', $like)
                    ->orWhere('m.phone', 'like', $like)
                    ->orWhere('m.address_line1', 'like', $like);
            });
        }

        $total = (int) (clone $base)->count();

        $rows = (clone $base)
            ->orderBy('m.full_name')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        $items = $rows->map(fn ($row) => $this->formatLegacyMemberRow((array) $row, $ministryKey))->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => max(1, (int) ceil($total / max(1, $perPage))),
        ];
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, pages: int}
     */
    private function listRosterMembers(string $ministryKey, string $query, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $base = DB::table('ministry_roster_people')
            ->where('ministry_key', $ministryKey)
            ->where('status', 'active');

        if ($query !== '') {
            $like = '%'.$query.'%';
            $base->where(function ($q) use ($like) {
                $q->where('full_name', 'like', $like)
                    ->orWhere('person_code', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('parent_name', 'like', $like)
                    ->orWhere('parent_phone', 'like', $like)
                    ->orWhere('address_line1', 'like', $like);
            });
        }

        $total = (int) (clone $base)->count();

        $rows = (clone $base)
            ->orderBy('full_name')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        $items = $rows->map(fn ($row) => $this->formatRow((array) $row + ['source' => 'roster']))->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => max(1, (int) ceil($total / max(1, $perPage))),
        ];
    }

    private function hasLegacyMemberships(string $ministryKey): bool
    {
        if (! Schema::hasTable('ministry_members') || ! Schema::hasTable('members')) {
            return false;
        }

        return DB::table('ministry_members')
            ->where('ministry_key', $ministryKey)
            ->where('ministry_status', 'active')
            ->exists();
    }

    /** @return \Illuminate\Database\Query\Builder */
    private function legacyMembersQuery(string $ministryKey)
    {
        return DB::table('ministry_members as mm')
            ->join('members as m', 'm.id', '=', 'mm.member_id')
            ->where('mm.ministry_key', $ministryKey)
            ->where('mm.ministry_status', 'active')
            ->select([
                'm.id',
                'm.full_name',
                'm.first_name',
                'm.last_name',
                'm.member_code',
                'm.date_of_birth',
                'm.gender',
                'm.phone',
                'm.email',
                'm.address_line1',
                'm.city',
                'm.state',
                'm.parent_name',
                'm.parent_phone',
                'm.parent_email',
                'mm.join_date',
                'mm.notes as ministry_notes',
            ]);
    }

    /**
     * Active church members available to copy into a ministry roster (excludes those already linked).
     *
     * @return list<array{id: int, full_name: string, member_code: string|null, phone: string|null, department: string|null}>
     */
    public function listChurchMembersForImport(string $ministryKey): array
    {
        if (! Schema::hasTable('members')) {
            return [];
        }

        $alreadyEnrolled = [];
        if (Schema::hasTable('ministry_members')) {
            $alreadyEnrolled = DB::table('ministry_members')
                ->where('ministry_key', $ministryKey)
                ->where('ministry_status', 'active')
                ->pluck('member_id')
                ->map(static fn ($id): int => (int) $id)
                ->all();
        }

        $alreadyLinked = DB::table('ministry_roster_people')
            ->where('ministry_key', $ministryKey)
            ->where('status', 'active')
            ->whereNotNull('linked_member_id')
            ->pluck('linked_member_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $exclude = array_values(array_unique(array_merge($alreadyEnrolled, $alreadyLinked)));

        $query = DB::table('members')
            ->where('status', '<>', 'deceased')
            ->orderBy('full_name');

        if ($exclude !== []) {
            $query->whereNotIn('id', $exclude);
        }

        return $query
            ->get(['id', 'full_name', 'member_code', 'phone', 'department'])
            ->map(static fn ($row): array => [
                'id' => (int) $row->id,
                'full_name' => (string) $row->full_name,
                'member_code' => $row->member_code ?? null,
                'phone' => $row->phone ?? null,
                'department' => $row->department ?? null,
            ])
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public function upcomingBirthdays(string $ministryKey, int $withinDays = 30): array
    {
        $today = now()->startOfDay();
        $results = [];

        if ($this->hasLegacyMemberships($ministryKey)) {
            $people = $this->legacyMembersQuery($ministryKey)
                ->whereNotNull('m.date_of_birth')
                ->get();
        } else {
            $people = DB::table('ministry_roster_people')
                ->where('ministry_key', $ministryKey)
                ->where('status', 'active')
                ->whereNotNull('date_of_birth')
                ->get();
        }

        foreach ($people as $person) {
            $person = (array) $person;
            $dob = Carbon::parse((string) ($person['date_of_birth'] ?? ''));
            $nextBirthday = $dob->copy()->year($today->year);
            if ($nextBirthday->lt($today)) {
                $nextBirthday->addYear();
            }

            $daysUntil = (int) $today->diffInDays($nextBirthday, false);
            if ($daysUntil < 0 || $daysUntil > $withinDays) {
                continue;
            }

            $results[] = [
                'id' => (int) ($person['id'] ?? 0),
                'full_name' => (string) ($person['full_name'] ?? ''),
                'person_code' => (string) ($person['member_code'] ?? $person['person_code'] ?? ''),
                'date_of_birth' => (string) ($person['date_of_birth'] ?? ''),
                'days_until' => $daysUntil,
                'is_today' => $daysUntil === 0,
            ];
        }

        usort($results, static fn (array $a, array $b) => $a['days_until'] <=> $b['days_until']);

        return $results;
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function formatLegacyMemberRow(array $row, string $ministryKey): array
    {
        $age = $this->settings->memberAge($row['date_of_birth'] ?? null);
        $address = trim(implode(', ', array_filter([
            $row['address_line1'] ?? null,
            $row['city'] ?? null,
            $row['state'] ?? null,
        ])));

        return [
            'id' => (int) $row['id'],
            'source' => 'member',
            'ministry_key' => $ministryKey,
            'person_code' => (string) ($row['member_code'] ?? ''),
            'full_name' => (string) $row['full_name'],
            'first_name' => $row['first_name'] ?? null,
            'last_name' => $row['last_name'] ?? null,
            'date_of_birth' => $row['date_of_birth'] ?? null,
            'gender' => $row['gender'] ?? null,
            'phone' => $row['phone'] ?? null,
            'email' => $row['email'] ?? null,
            'address' => $address !== '' ? $address : null,
            'address_line1' => $row['address_line1'] ?? null,
            'city' => $row['city'] ?? null,
            'state' => $row['state'] ?? null,
            'parent_name' => $row['parent_name'] ?? null,
            'parent_phone' => $row['parent_phone'] ?? null,
            'parent_email' => $row['parent_email'] ?? null,
            'group_name' => null,
            'role_note' => null,
            'status' => 'active',
            'joined_date' => $row['join_date'] ?? null,
            'notes' => $row['ministry_notes'] ?? null,
            'linked_member_id' => (int) $row['id'],
            'age' => $age,
        ];
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function formatRow(array $row): array
    {
        $age = $this->settings->memberAge($row['date_of_birth'] ?? null);
        $address = trim(implode(', ', array_filter([
            $row['address_line1'] ?? null,
            $row['city'] ?? null,
            $row['state'] ?? null,
        ])));

        return [
            'id' => (int) $row['id'],
            'source' => (string) ($row['source'] ?? 'roster'),
            'ministry_key' => (string) ($row['ministry_key'] ?? ''),
            'person_code' => (string) $row['person_code'],
            'full_name' => (string) $row['full_name'],
            'first_name' => $row['first_name'] ?? null,
            'last_name' => $row['last_name'] ?? null,
            'date_of_birth' => $row['date_of_birth'] ?? null,
            'gender' => $row['gender'] ?? null,
            'phone' => $row['phone'] ?? null,
            'email' => $row['email'] ?? null,
            'address' => $address !== '' ? $address : null,
            'address_line1' => $row['address_line1'] ?? null,
            'city' => $row['city'] ?? null,
            'state' => $row['state'] ?? null,
            'parent_name' => $row['parent_name'] ?? null,
            'parent_phone' => $row['parent_phone'] ?? null,
            'parent_email' => $row['parent_email'] ?? null,
            'group_name' => $row['group_name'] ?? null,
            'role_note' => $row['role_note'] ?? null,
            'status' => (string) ($row['status'] ?? 'active'),
            'joined_date' => $row['joined_date'] ?? null,
            'notes' => $row['notes'] ?? null,
            'linked_member_id' => isset($row['linked_member_id']) ? (int) $row['linked_member_id'] : null,
            'age' => $age,
        ];
    }
}
