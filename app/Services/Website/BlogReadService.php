<?php

namespace App\Services\Website;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class BlogReadService
{
    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public function listPosts(string $query, string $category, string $status, int $page, int $perPage = 20, string $tag = ''): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $builder = DB::table('ag_blog_posts');
        if ($query !== '') {
            $builder->where(function ($q) use ($query) {
                $q->where('title', 'like', '%'.$query.'%')
                    ->orWhere('excerpt', 'like', '%'.$query.'%')
                    ->orWhere('author', 'like', '%'.$query.'%')
                    ->orWhere('body_html', 'like', '%'.$query.'%');
            });
        }
        if ($category !== '' && isset(self::categoryLabels()[$category])) {
            $builder->where('category', $category);
        }
        if ($tag !== '' && Schema::hasColumn('ag_blog_posts', 'tags')) {
            $builder->where('tags', 'like', '%"'.$tag.'"%');
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
            ->map(fn ($row) => $this->normalizeRow((array) $row))
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

        return $row ? $this->normalizeRow((array) $row) : null;
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

        return $row ? $this->normalizeRow((array) $row) : null;
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public function listPublished(string $query = '', string $category = '', int $page = 1, int $perPage = 9, string $tag = ''): array
    {
        return $this->listPosts($query, $category, 'published', $page, $perPage, $tag);
    }

    /** @return list<array<string, mixed>> */
    public function relatedPosts(array $post, int $limit = 3): array
    {
        $id = (int) ($post['id'] ?? 0);
        $category = (string) ($post['category'] ?? '');
        $tags = is_array($post['tags'] ?? null) ? $post['tags'] : [];

        $builder = DB::table('ag_blog_posts')
            ->where('is_published', true)
            ->where('id', '<>', $id);

        if ($category !== '') {
            $builder->where('category', $category);
        }

        $items = $builder->orderByDesc('published_at')->limit($limit * 3)->get()
            ->map(fn ($row) => $this->normalizeRow((array) $row))
            ->all();

        if ($tags !== []) {
            usort($items, static function (array $a, array $b) use ($tags): int {
                $aScore = count(array_intersect($tags, is_array($a['tags'] ?? null) ? $a['tags'] : []));
                $bScore = count(array_intersect($tags, is_array($b['tags'] ?? null) ? $b['tags'] : []));

                return $bScore <=> $aScore;
            });
        }

        return array_slice($items, 0, $limit);
    }

    /** @return array{previous: ?array<string, mixed>, next: ?array<string, mixed>} */
    public function adjacentPosts(array $post): array
    {
        $publishedAt = (string) ($post['published_at'] ?? '');
        $id = (int) ($post['id'] ?? 0);

        $previous = DB::table('ag_blog_posts')
            ->where('is_published', true)
            ->where(function ($q) use ($publishedAt, $id) {
                $q->where('published_at', '<', $publishedAt)
                    ->orWhere(function ($inner) use ($publishedAt, $id) {
                        $inner->where('published_at', $publishedAt)->where('id', '<', $id);
                    });
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();

        $next = DB::table('ag_blog_posts')
            ->where('is_published', true)
            ->where(function ($q) use ($publishedAt, $id) {
                $q->where('published_at', '>', $publishedAt)
                    ->orWhere(function ($inner) use ($publishedAt, $id) {
                        $inner->where('published_at', $publishedAt)->where('id', '>', $id);
                    });
            })
            ->orderBy('published_at')
            ->orderBy('id')
            ->first();

        return [
            'previous' => $previous ? $this->normalizeRow((array) $previous) : null,
            'next' => $next ? $this->normalizeRow((array) $next) : null,
        ];
    }

    /** @return list<array<string, mixed>> */
    public function popular(int $limit = 5): array
    {
        $builder = DB::table('ag_blog_posts')->where('is_published', true);
        if (Schema::hasColumn('ag_blog_posts', 'view_count')) {
            $builder->orderByDesc('view_count');
        } else {
            $builder->orderByDesc('published_at');
        }

        return $builder->limit($limit)->get()->map(fn ($row) => $this->normalizeRow((array) $row))->all();
    }

    /** @return list<array<string, mixed>> */
    public function recent(int $limit = 5): array
    {
        return DB::table('ag_blog_posts')
            ->where('is_published', true)
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => $this->normalizeRow((array) $row))
            ->all();
    }

    public function incrementViews(int $id): void
    {
        if (! Schema::hasColumn('ag_blog_posts', 'view_count')) {
            return;
        }

        DB::table('ag_blog_posts')->where('id', $id)->increment('view_count');
    }

    /**
     * @return list<array{id: string, text: string}>
     */
    public function tableOfContents(string $html): array
    {
        $toc = [];
        if (preg_match_all('/<h([2-3])[^>]*>(.*?)<\/h\1>/is', $html, $matches, PREG_SET_ORDER) === false) {
            return [];
        }

        foreach ($matches as $i => $match) {
            $text = trim(strip_tags($match[2]));
            if ($text === '') {
                continue;
            }
            $id = 'section-'.($i + 1).'-'.Str::slug(Str::limit($text, 40, ''));
            $toc[] = ['id' => $id, 'text' => $text, 'level' => (int) $match[1]];
        }

        return $toc;
    }

    public function injectHeadingIds(string $html, array $toc): string
    {
        $index = 0;

        return (string) preg_replace_callback('/<h([2-3])([^>]*)>(.*?)<\/h\1>/is', function (array $m) use (&$index, $toc): string {
            $entry = $toc[$index] ?? null;
            $index++;
            if ($entry === null) {
                return $m[0];
            }
            $attrs = $m[2];
            if (! str_contains($attrs, 'id=')) {
                $attrs .= ' id="'.e($entry['id']).'"';
            }

            return '<h'.$m[1].$attrs.'>'.$m[3].'</h'.$m[1].'>';
        }, $html) ?? $html;
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
            'church-news' => 'Church News',
            'devotionals' => 'Daily Devotionals',
            'bible-study' => 'Bible Study',
            'sermon-article' => 'Sermon Articles',
            'sermons' => 'Sermons',
            'ministry-update' => 'Ministry Updates',
            'pastors-message' => "Pastor's Messages",
            'testimonies' => 'Testimonies',
            'prayer' => 'Prayer',
            'youth' => 'Youth Ministry',
            'children' => "Children's Ministry",
            'women' => "Women's Ministry",
            'men' => "Men's Fellowship",
            'choir' => 'Choir News',
            'marriage-family' => 'Marriage & Family',
            'leadership' => 'Leadership',
            'christian-living' => 'Christian Living',
            'faith-discipleship' => 'Faith & Discipleship',
            'missions' => 'Mission Reports',
            'evangelism' => 'Evangelism',
            'bulletins' => 'Weekly Bulletins',
            'events' => 'Event Announcements',
            'press' => 'Press Releases',
            'notices' => 'Public Notices',
        ];
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function normalizeRow(array $row): array
    {
        $tags = $row['tags'] ?? [];
        if (is_string($tags)) {
            $decoded = json_decode($tags, true);
            $tags = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($tags)) {
            $tags = [];
        }
        $row['tags'] = array_values(array_filter(array_map(static fn ($t) => Str::slug((string) $t), $tags)));

        return $row;
    }
}
