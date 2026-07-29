<?php

namespace App\Services\Testimonies;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SiteTestimonyReadService
{
    public const STATUSES = ['pending', 'approved', 'rejected'];

    /** @var array<string, string> */
    public const SOURCE_PAGES = [
        'index' => 'Homepage',
        'activity' => 'Ministries',
        'event' => 'Events',
        'about' => 'About Us',
        'contact' => 'Contact',
        'donate' => 'Give / Donate',
    ];

    /** @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int} */
    public function listTestimonies(
        string $status = '',
        string $query = '',
        string $sourcePage = '',
        int $page = 1,
        int $perPage = 12,
    ): array {
        if (! Schema::hasTable('site_testimonies')) {
            return ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'per_page' => $perPage];
        }

        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $builder = DB::table('site_testimonies');
        $this->applyFilters($builder, $status, $query, $sourcePage);

        $total = (clone $builder)->count();
        $items = $builder->orderByDesc('created_at')
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(fn ($row) => $this->formatRow((array) $row))
            ->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => (int) max(1, ceil($total / $perPage)),
            'per_page' => $perPage,
        ];
    }

    /** @return array<string, mixed>|null */
    public function getTestimony(int $id): ?array
    {
        if (! Schema::hasTable('site_testimonies')) {
            return null;
        }

        $row = DB::table('site_testimonies')->where('id', $id)->first();

        return $row ? $this->formatRow((array) $row) : null;
    }

    /** @return array<string, int> */
    public function getStats(): array
    {
        if (! Schema::hasTable('site_testimonies')) {
            return ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'featured' => 0];
        }

        return [
            'total' => (int) DB::table('site_testimonies')->count(),
            'pending' => (int) DB::table('site_testimonies')->where('status', 'pending')->count(),
            'approved' => (int) DB::table('site_testimonies')->where('status', 'approved')->count(),
            'rejected' => (int) DB::table('site_testimonies')->where('status', 'rejected')->count(),
            'featured' => (int) DB::table('site_testimonies')
                ->where('status', 'approved')
                ->where('is_featured', 1)
                ->count(),
        ];
    }

    /** @param \Illuminate\Database\Query\Builder $builder */
    private function applyFilters($builder, string $status, string $query, string $sourcePage): void
    {
        if ($status === 'featured') {
            $builder->where('status', 'approved')->where('is_featured', 1);
        } elseif ($status !== '' && in_array($status, self::STATUSES, true)) {
            $builder->where('status', $status);
        }

        if ($sourcePage !== '' && $sourcePage !== 'all') {
            $builder->where('source_page', $sourcePage);
        }

        if ($query !== '') {
            $builder->where(function ($q) use ($query) {
                $q->where('full_name', 'like', '%'.$query.'%')
                    ->orWhere('email', 'like', '%'.$query.'%')
                    ->orWhere('testimony_text', 'like', '%'.$query.'%');
            });
        }
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function formatRow(array $row): array
    {
        $sourcePage = (string) ($row['source_page'] ?? 'index');
        $photoPath = $row['photo_path'] ?? null;

        return [
            'id' => (int) $row['id'],
            'full_name' => (string) $row['full_name'],
            'email' => (string) $row['email'],
            'role_title' => $row['role_title'] ?? null,
            'testimony_text' => (string) $row['testimony_text'],
            'photo_path' => $photoPath,
            'photo_url' => $this->photoUrl(is_string($photoPath) ? $photoPath : null),
            'source_page' => $sourcePage,
            'source_label' => self::SOURCE_PAGES[$sourcePage] ?? ucfirst(str_replace('_', ' ', $sourcePage)),
            'category_slug' => $row['category_slug'] ?? null,
            'amount' => $row['amount'] !== null ? (float) $row['amount'] : null,
            'status' => (string) $row['status'],
            'is_featured' => ! empty($row['is_featured']),
            'ip_address' => $row['ip_address'] ?? null,
            'reviewed_by' => $row['reviewed_by'] ?? null,
            'reviewed_at' => $row['reviewed_at'] ?? null,
            'created_at' => (string) $row['created_at'],
        ];
    }

    private function photoUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return rtrim((string) config('portal.legacy_admin_base'), '/').'/'.ltrim(str_replace('\\', '/', $path), '/');
    }
}
