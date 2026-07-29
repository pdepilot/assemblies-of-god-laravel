<?php

namespace App\Services\Website;

use Illuminate\Support\Facades\DB;

final class BlogReadService
{
    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public function listPosts(string $query, string $category, string $status, int $page, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $builder = DB::table('ag_blog_posts');
        if ($query !== '') {
            $builder->where(function ($q) use ($query) {
                $q->where('title', 'like', '%'.$query.'%')
                    ->orWhere('excerpt', 'like', '%'.$query.'%')
                    ->orWhere('author', 'like', '%'.$query.'%');
            });
        }
        if ($category !== '' && isset(self::categoryLabels()[$category])) {
            $builder->where('category', $category);
        }
        if ($status === 'published') {
            $builder->where('is_published', true);
        } elseif ($status === 'draft') {
            $builder->where('is_published', false);
        }

        $total = (int) $builder->count();
        $items = (clone $builder)
            ->orderByDesc('published_at')
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
    public function getPost(int $id): ?array
    {
        $row = DB::table('ag_blog_posts')->where('id', $id)->first();

        return $row ? (array) $row : null;
    }

    /** @return array<string, mixed>|null */
    public function getPublishedBySlug(string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        $row = DB::table('ag_blog_posts')
            ->where('slug', $slug)
            ->where('is_published', true)
            ->first();

        return $row ? (array) $row : null;
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public function listPublished(string $query = '', string $category = '', int $page = 1, int $perPage = 9): array
    {
        return $this->listPosts($query, $category, 'published', $page, $perPage);
    }

    /** @return array<string, int> */
    public function getStats(): array
    {
        $row = DB::table('ag_blog_posts')
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_published = 1 THEN 1 ELSE 0 END) as published, SUM(CASE WHEN is_published = 0 THEN 1 ELSE 0 END) as drafts')
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'published' => (int) ($row->published ?? 0),
            'drafts' => (int) ($row->drafts ?? 0),
        ];
    }

    /** @return array<string, string> */
    public static function categoryLabels(): array
    {
        return [
            'sermons' => 'Sermons',
            'devotionals' => 'Devotionals',
            'church-news' => 'Church News',
            'testimonies' => 'Testimonies',
        ];
    }
}
