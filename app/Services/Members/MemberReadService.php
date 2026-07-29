<?php

namespace App\Services\Members;

use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class MemberReadService
{
    public const DEPARTMENTS = [
        'Member', 'Pastor', 'Reverend', 'Leadership', 'Leadership Team', 'Workers',
        'Children', 'Children Ministry', 'Sunday School', 'Teen Ministry', 'Youth', 'Youth Ministry',
        "Men's Ministry", "Women's Ministry", 'Widowers Ministry', 'Widows Ministry',
        'Music', 'Music Department', 'Choir', 'Ushering', 'Media', 'Media Team',
        'Prayer', 'Prayer Chain', 'Welfare', 'Welfare Ministry', 'Discipleship',
        'Evangelism', 'Evangelism & Outreach', 'SDTG / Crusade Ministry',
    ];

    public const STATUSES = [
        'new_member', 'active', 'full_member', 'inactive', 'visitor',
        'baptized', 'unbaptized', 'worker', 'suspended', 'restored', 'transferred', 'deceased',
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

        return $row ? $this->formatMember((array) $row) : null;
    }

    /**
     * @return array<string, int>
     */
    public function getStats(): array
    {
        $today = now()->toDateString();
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
            'widowers' => (int) DB::table('members')
                ->whereNotIn('status', ['deceased', 'inactive'])
                ->where(function ($q) {
                    $q->where('marital_status', 'widower')
                        ->orWhere(function ($q2) {
                            $q2->where('marital_status', 'widowed')->where('gender', 'male');
                        });
                })->count(),
        ];
    }

    /** @return list<string> */
    public function listDepartmentOptions(): array
    {
        $options = self::DEPARTMENTS;

        if (Schema::hasTable('ministry_settings')) {
            $fromSettings = DB::table('ministry_settings')
                ->where('is_enabled', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->pluck('name')
                ->map(static fn ($v) => trim((string) $v))
                ->filter()
                ->all();
            $options = array_merge($options, $fromSettings);
        }

        if (Schema::hasTable('members')) {
            $fromMembers = DB::table('members')
                ->where('department', '<>', '')
                ->distinct()
                ->orderBy('department')
                ->pluck('department')
                ->all();
            $options = array_merge($options, $fromMembers);
        }

        if (Schema::hasTable('sunday_school_students')) {
            $fromStudents = DB::table('sunday_school_students')
                ->whereNotNull('department')
                ->where('department', '<>', '')
                ->distinct()
                ->orderBy('department')
                ->pluck('department')
                ->all();
            $options = array_merge($options, $fromStudents);
        }

        $options = array_values(array_unique(array_filter(array_map('strval', $options))));
        natcasesort($options);

        return array_values($options);
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

        return [
            ...$row,
            'id' => (int) $row['id'],
            'age' => $age,
            'address' => implode(', ', $addressParts),
            'photo_url' => $this->photoUrl($row['photo_path'] ?? null),
        ];
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
