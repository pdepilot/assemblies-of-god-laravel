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

    /** @return array<string, mixed>|null */
    public function getPublishedBySlug(string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        $candidates = array_values(array_unique(array_filter([
            $slug,
            str_replace('_', '-', $slug),
            str_replace('-', '_', $slug),
        ])));

        $row = DB::table('sermons')
            ->where('status', 'published')
            ->whereIn('slug', $candidates)
            ->first();

        return $row ? (array) $row : null;
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public function listPublishedForPublic(string $query, string $type, int $page, int $perPage = 9): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(24, $perPage));
        $offset = ($page - 1) * $perPage;
        $query = trim($query);
        $type = strtolower(trim($type));

        $builder = DB::table('sermons')->where('status', 'published');

        if ($query !== '') {
            $builder->where(function ($q) use ($query) {
                $q->where('title', 'like', '%'.$query.'%')
                    ->orWhere('minister_name', 'like', '%'.$query.'%')
                    ->orWhere('scripture_refs', 'like', '%'.$query.'%')
                    ->orWhere('description', 'like', '%'.$query.'%');
            });
        }

        if ($type === 'video') {
            $builder->where(function ($q) {
                $q->where('sermon_type', 'video')
                    ->orWhere('sermon_type', 'live')
                    ->orWhere('youtube_url', '!=', '')
                    ->orWhere('vimeo_url', '!=', '')
                    ->orWhere('facebook_video_url', '!=', '')
                    ->orWhereNotNull('video_file_path')
                    ->orWhereNotNull('video_embed_code');
            });
        } elseif ($type === 'audio') {
            $builder->where(function ($q) {
                $q->where('sermon_type', 'audio')
                    ->orWhere('audio_stream_url', '!=', '')
                    ->orWhereNotNull('audio_file_path');
            });
        } elseif ($type === 'notes') {
            $builder->where(function ($q) {
                $q->where('sermon_type', 'pdf')
                    ->orWhere('sermon_type', 'text')
                    ->orWhereNotNull('pdf_file_path');
            });
        }

        $total = (int) $builder->count();
        $items = (clone $builder)
            ->orderByDesc('is_featured')
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

    /** @return list<array<string, mixed>> */
    public function related(array $sermon, int $limit = 3): array
    {
        $id = (int) ($sermon['id'] ?? 0);
        $categoryId = $sermon['category_id'] ?? null;
        $minister = trim((string) ($sermon['minister_name'] ?? ''));

        $builder = DB::table('sermons')
            ->where('status', 'published')
            ->where('id', '<>', $id);

        if ($categoryId) {
            $builder->where('category_id', (int) $categoryId);
        } elseif ($minister !== '') {
            $builder->where('minister_name', $minister);
        }

        return $builder->orderByDesc('sermon_date')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function incrementViews(int $id): void
    {
        DB::table('sermons')->where('id', $id)->increment('view_count');
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
