<?php

namespace App\Services\Sermons;

use Illuminate\Support\Facades\DB;

final class SermonReadService
{
    public const STATUSES = ['draft', 'published', 'scheduled', 'archived'];

    public const TYPES = ['video', 'audio', 'text', 'pdf', 'live'];

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public function listSermons(string $query, string $status, int $page, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $builder = DB::table('sermons');
        if ($query !== '') {
            $builder->where(function ($q) use ($query) {
                $q->where('title', 'like', '%'.$query.'%')
                    ->orWhere('minister_name', 'like', '%'.$query.'%')
                    ->orWhere('sermon_code', 'like', '%'.$query.'%');
            });
        }
        if ($status !== '' && in_array($status, self::STATUSES, true)) {
            $builder->where('status', $status);
        }

        $total = (int) $builder->count();
        $items = (clone $builder)
            ->orderByDesc('sermon_date')
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'per_page' => $perPage,
        ];
    }

    /** @return array<string, mixed>|null */
    public function getSermon(int $id): ?array
    {
        $row = DB::table('sermons')->where('id', $id)->first();

        return $row ? (array) $row : null;
    }

    /** @return list<array<string, mixed>> */
    public function listCategories(): array
    {
        return DB::table('sermon_categories')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public function listSeries(): array
    {
        return DB::table('sermon_series')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }
}
