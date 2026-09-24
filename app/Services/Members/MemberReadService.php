<?php

namespace App\Services\Members;

use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class MemberReadService
{
    public const DEPARTMENTS = [
        'Member', 'Pastor', 'Reverend', 'Leadership', 'Workers', 'Sunday School',
    ];

    public const STATUSES = [
        'new_member', 'active', 'full_member', 'inactive', 'visitor',
        'baptized', 'unbaptized', 'worker', 'suspended', 'restored', 'transferred', 'deceased',
    ];

    /** Statuses shown on the public join form (same living options as add-member). */
    public const PUBLIC_JOIN_STATUSES = [
        'full_member', 'baptized', 'visitor', 'unbaptized', 'active', 'worker', 'inactive',
    ];

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, pages: int}
     */
    public function listMembers(string $query, string $department, string $status, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $base = DB::table('members');

        if ($query !== '') {
            $like = '%'.$query.'%';
            $base->where(function ($q) use ($like) {
                $q->where('full_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('member_code', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('phone_alt', 'like', $like);
            });
        }

        if ($department !== '') {
            $base->where('department', $department);
        }

        if ($status !== '' && in_array($status, self::STATUSES, true)) {
            $base->where('status', $status);
        }

        $total = (int) (clone $base)->count();

        $items = (clone $base)
            ->orderBy('full_name')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(fn ($row) => $this->formatMember((array) $row))
            ->all();

        $items = $this->attachChildren($items);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => max(1, (int) ceil($total / max(1, $perPage))),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMember(int $id): ?array
    {
        $row = DB::table('members')->where('id', $id)->first();
        if (! $row) {
            return null;
        }

        $member = $this->formatMember((array) $row);
        $withChildren = $this->attachChildren([$member]);

        return $withChildren[0];
    }

    /**
     * @return array<string, int>
     */
    public function getStats(): array
    {
        $monthStart = now()->startOfMonth()->toDateString();

        return [
            'total' => (int) DB::table('members')->count(),
            'active' => (int) DB::table('members')->whereIn('status', ['active', 'full_member', 'baptized', 'worker', 'restored'])->count(),
            'full_member' => (int) DB::table('members')->where('status', 'full_member')->count(),
            'baptized' => (int) DB::table('members')->where('status', 'baptized')->count(),
            'unbaptized' => (int) DB::table('members')->where('status', 'unbaptized')->count(),
            'visitors' => (int) DB::table('members')->where('status', 'visitor')->count(),
            'inactive' => (int) DB::table('members')->where('status', 'inactive')->count(),
            'deceased' => (int) DB::table('members')->where('status', 'deceased')->count(),
            'new_this_month' => (int) DB::table('members')->where('joined_date', '>=', $monthStart)->count(),
            'departments' => (int) DB::table('members')->where('department', '<>', '')->selectRaw('COUNT(DISTINCT department) as c')->value('c'),
            'widows' => (int) DB::table('members')
                ->whereNotIn('status', ['deceased', 'inactive'])
                ->where(function ($q) {
                    $q->where('marital_status', 'widow')
                        ->orWhere(function ($q2) {
                            $q2->where('marital_status', 'widowed')->where('gender', 'female');
                        });
                })->count(),
        ];
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, pages: int}
     */
    public function listDeceased(string $query = '', int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $base = DB::table('members')->where('status', 'deceased');

        if ($query !== '') {
            $like = '%'.$query.'%';
            $base->where(function ($q) use ($like): void {
                $q->where('full_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('member_code', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('department', 'like', $like)
                    ->orWhere('death_notes', 'like', $like);
            });
        }

        $total = (int) (clone $base)->count();

        $items = (clone $base)
            ->orderByRaw('CASE WHEN date_of_death IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('date_of_death')
            ->orderBy('full_name')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(fn ($row) => $this->formatDeceasedMember((array) $row))
            ->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => max(1, (int) ceil($total / max(1, $perPage))),
        ];
    }

    /**
     * Living (non-deceased) members for the "record deceased" picker.
     *
     * @return list<array{id: int, member_code: string, full_name: string, department: string, phone: string, status: string}>
     */
    public function listSelectableLivingMembers(?string $query = null): array
    {
        $base = DB::table('members')
            ->where('status', '<>', 'deceased')
            ->orderBy('full_name');

        $query = trim((string) $query);
        if ($query !== '') {
            $like = '%'.$query.'%';
            $base->where(function ($q) use ($like): void {
                $q->where('full_name', 'like', $like)
                    ->orWhere('member_code', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('department', 'like', $like);
            });
        }

        return $base
            ->limit(500)
            ->get(['id', 'member_code', 'full_name', 'department', 'phone', 'status'])
            ->map(static fn ($row): array => [
                'id' => (int) $row->id,
                'member_code' => (string) $row->member_code,
                'full_name' => (string) $row->full_name,
                'department' => (string) ($row->department ?? ''),
                'phone' => (string) ($row->phone ?? ''),
                'status' => (string) $row->status,
            ])
            ->all();
    }

    /**
     * @return array{
     *   total: int,
     *   this_year: int,
     *   this_month: int,
     *   with_memorial_notes: int,
     *   missing_date_of_death: int
     * }
     */
    public function getDeceasedStats(): array
    {
        $yearStart = now()->startOfYear()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        return [
            'total' => (int) DB::table('members')->where('status', 'deceased')->count(),
            'this_year' => (int) DB::table('members')
                ->where('status', 'deceased')
                ->whereNotNull('date_of_death')
                ->where('date_of_death', '>=', $yearStart)
                ->count(),
            'this_month' => (int) DB::table('members')
                ->where('status', 'deceased')
                ->whereNotNull('date_of_death')
                ->where('date_of_death', '>=', $monthStart)
                ->count(),
            'with_memorial_notes' => (int) DB::table('members')
                ->where('status', 'deceased')
                ->whereNotNull('death_notes')
                ->where('death_notes', '<>', '')
                ->count(),
            'missing_date_of_death' => (int) DB::table('members')
                ->where('status', 'deceased')
                ->where(function ($q): void {
                    $q->whereNull('date_of_death')->orWhere('date_of_death', '');
                })
                ->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function formatDeceasedMember(array $row): array
    {
        $member = $this->formatMember($row);
        $deathDate = $row['date_of_death'] ?? null;
        $displayDeath = '—';
        if ($deathDate) {
            try {
                $displayDeath = Carbon::parse((string) $deathDate)->format('d M Y');
            } catch (\Throwable) {
                $displayDeath = (string) $deathDate;
            }
        }

        $member['date_of_death_display'] = $displayDeath;
        $member['memorial_notes'] = trim((string) ($row['death_notes'] ?? '')) ?: null;

        return $member;
    }

    public function canonicalDepartmentLabel(string $name): string
    {
        $name = trim(preg_replace('/\s+/', ' ', $name) ?? $name);
        if ($name === '') {
            return '';
        }

        $map = $this->departmentCanonicalMap();
        $lower = mb_strtolower($name);
        $stem = $this->departmentDedupeKey($name);

        return $map[$lower] ?? $map[$stem] ?? $name;
    }

    /** @return list<string> */
    public function listDepartmentOptions(): array
    {
        $seen = [];
        $options = [];
        $add = function (mixed $raw) use (&$seen, &$options): void {
            $name = $this->canonicalDepartmentLabel((string) $raw);
            if ($name === '') {
                return;
            }
            $key = $this->departmentDedupeKey($name);
            if ($key === '' || isset($seen[$key])) {
                return;
            }
            $seen[$key] = true;
            $options[] = $name;
        };

        if (Schema::hasTable('ministry_settings')) {
            DB::table('ministry_settings')
                ->where('is_enabled', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->pluck('name')
                ->each($add);
        }

        foreach (self::DEPARTMENTS as $dept) {
            $add($dept);
        }

        if (Schema::hasTable('members')) {
            DB::table('members')
                ->where('department', '<>', '')
                ->distinct()
                ->orderBy('department')
                ->pluck('department')
                ->each($add);
        }

        if (Schema::hasTable('sunday_school_students')) {
            DB::table('sunday_school_students')
                ->whereNotNull('department')
                ->where('department', '<>', '')
                ->distinct()
                ->orderBy('department')
                ->pluck('department')
                ->each($add);
        }

        natcasesort($options);

        return array_values($options);
    }

    private function departmentDedupeKey(string $name): string
    {
        $key = mb_strtolower(trim($name));
        $key = strtr($key, [
            "'" => '',
            '’' => '',
            '`' => '',
            '-' => ' ',
            '&' => ' and ',
        ]);
        $key = preg_replace('/\s+/', ' ', $key) ?? $key;
        $key = preg_replace('/\s+(ministry|department|team|chain|group|unit)$/u', '', $key) ?? $key;

        return trim($key);
    }

    /** @return array<string, string> */
    private function departmentCanonicalMap(): array
    {
        $map = [
            'children' => 'Children Ministry',
            'youth' => 'Youth Ministry',
            'youths' => 'Youth Ministry',
            'teen' => 'Teen Ministry',
            'teens' => 'Teen Ministry',
            'men' => "Men's Ministry",
            'women' => "Women's Ministry",
            'widows ministry' => 'Widows',
            'widowers' => "Men's Ministry",
            'widowers ministry' => "Men's Ministry",
            'music' => 'Music Department',
            'media' => 'Media Team',
            'ushers' => 'Ushering',
            'leadership team' => 'Leadership',
            'welfare ministry' => 'Welfare',
            'evangelism & outreach' => 'Evangelism',
            'evangelism and outreach' => 'Evangelism',
            'sdtg / crusade ministry' => 'Crusade Ministry',
            'sdtg/crusade ministry' => 'Crusade Ministry',
            'sdtg' => 'Crusade Ministry',
        ];

        $aliasesByKey = [
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

        if (Schema::hasTable('ministry_settings')) {
            $rows = DB::table('ministry_settings')
                ->where('is_enabled', true)
                ->get(['ministry_key', 'name']);

            foreach ($rows as $row) {
                $canonical = trim((string) ($row->name ?? ''));
                if ($canonical === '') {
                    continue;
                }
                $map[mb_strtolower($canonical)] = $canonical;
                $ministryKey = (string) ($row->ministry_key ?? '');
                foreach ($aliasesByKey[$ministryKey] ?? [] as $alias) {
                    $map[mb_strtolower($alias)] = $canonical;
                }
            }
        }

        return $map;
    }

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return [
            'new_member' => 'New Member',
            'active' => 'Active',
            'full_member' => 'Full Member',
            'baptized' => 'Baptized',
            'unbaptized' => 'Unbaptized',
            'visitor' => 'Visitor',
            'worker' => 'Worker',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
            'restored' => 'Restored',
            'transferred' => 'Transferred',
            'deceased' => 'Deceased',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $members
     * @return list<array<string, mixed>>
     */
    private function attachChildren(array $members): array
    {
        if ($members === [] || ! Schema::hasColumn('members', 'parent_name')) {
            return array_map(static function (array $member): array {
                $member['children'] = $member['children'] ?? [];

                return $member;
            }, $members);
        }

        $candidates = DB::table('members')
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->whereNotNull('parent_name')->where('parent_name', '!=', '');
                })->orWhere(function ($inner) {
                    $inner->whereNotNull('parent_phone')->where('parent_phone', '!=', '');
                })->orWhere(function ($inner) {
                    $inner->whereNotNull('parent_email')->where('parent_email', '!=', '');
                });
            })
            ->orderBy('full_name')
            ->get();

        $childrenByParent = [];
        foreach ($members as $parent) {
            $childrenByParent[(int) $parent['id']] = [];
        }

        foreach ($candidates as $candidateRow) {
            $child = $this->formatMember((array) $candidateRow);
            if (! $this->looksLikeDependent($child)) {
                continue;
            }

            foreach ($members as $parent) {
                if ((int) $child['id'] === (int) $parent['id'] || ! $this->childBelongsToParent($child, $parent)) {
                    continue;
                }
                $parentId = (int) $parent['id'];
                $childrenByParent[$parentId][] = [
                    'id' => (int) $child['id'],
                    'full_name' => (string) ($child['full_name'] ?? ''),
                    'member_code' => (string) ($child['member_code'] ?? ''),
                    'photo_url' => $child['photo_url'] ?? null,
                    'age' => $child['age'] ?? null,
                ];
            }
        }

        return array_map(static function (array $member) use ($childrenByParent): array {
            $member['children'] = $childrenByParent[(int) $member['id']] ?? [];

            return $member;
        }, $members);
    }

    /**
     * @param  array<string, mixed>  $member
     */
    private function looksLikeDependent(array $member): bool
    {
        $age = $member['age'] ?? null;
        if (is_int($age) && $age > 19) {
            return false;
        }

        return filled($member['parent_name'] ?? null)
            || filled($member['parent_phone'] ?? null)
            || filled($member['parent_email'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $child
     * @param  array<string, mixed>  $parent
     */
    private function childBelongsToParent(array $child, array $parent): bool
    {
        $parentName = $this->nameKey((string) ($parent['full_name'] ?? ''));
        $childParentName = $this->nameKey((string) ($child['parent_name'] ?? ''));
        if ($parentName !== '' && $childParentName !== '' && $parentName === $childParentName) {
            return true;
        }

        $parentPhones = array_filter([
            $this->phoneKey((string) ($parent['phone'] ?? '')),
            $this->phoneKey((string) ($parent['phone_alt'] ?? '')),
        ]);
        $childPhone = $this->phoneKey((string) ($child['parent_phone'] ?? ''));
        if ($childPhone !== '' && in_array($childPhone, $parentPhones, true)) {
            return true;
        }

        $parentEmail = $this->emailKey((string) ($parent['email'] ?? ''));
        $childEmail = $this->emailKey((string) ($child['parent_email'] ?? ''));

        return $parentEmail !== '' && $childEmail !== '' && $parentEmail === $childEmail;
    }

    private function nameKey(string $value): string
    {
        $value = mb_strtolower(trim(preg_replace('/\s+/', ' ', $value) ?? ''));

        return $value;
    }

    private function phoneKey(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if (strlen($digits) >= 10) {
            return substr($digits, -10);
        }

        return $digits;
    }

    private function emailKey(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function formatMember(array $row): array
    {
        $dob = $row['date_of_birth'] ?? null;
        $age = null;
        if ($dob) {
            try {
                $age = Carbon::parse((string) $dob)->age;
            } catch (\Throwable) {
                $age = null;
            }
        }

        $addressParts = array_filter([
            $row['address_line1'] ?? '',
            $row['address_line2'] ?? '',
            trim(($row['city'] ?? '').', '.($row['state'] ?? ''), ', '),
            $row['postal_code'] ?? '',
            $row['country'] ?? '',
        ]);

        $formatted = [
            ...$row,
            'id' => (int) $row['id'],
            'age' => $age,
            'occupation' => filled($row['occupation'] ?? null) ? (string) $row['occupation'] : null,
            'address' => implode(', ', $addressParts),
            'photo_url' => $this->photoUrl($row['photo_path'] ?? null),
            'portal_enabled' => (int) ($row['portal_enabled'] ?? 0) === 1,
        ];
        unset($formatted['portal_password_hash']);

        return $formatted;
    }

    public function photoUrl(mixed $path): ?string
    {
        $path = trim(str_replace('\\', '/', (string) $path));
        if ($path === '') {
            return null;
        }

        if (preg_match('#^(https?:)?//#i', $path) === 1) {
            return $path;
        }

        $relative = ltrim($path, '/');

        // Laravel-uploaded copies live on the public disk.
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($relative)) {
            return asset('storage/'.$relative);
        }

        // Shared DB photos from the legacy portal: portal/uploads/members/...
        $legacyAdmin = rtrim((string) config('portal.legacy_admin_base'), '/');

        return $legacyAdmin.'/'.$relative;
    }
}
