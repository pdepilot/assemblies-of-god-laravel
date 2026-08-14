<?php

namespace App\Services\Website;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SitemapReadService
{
    /**
     * @return list<array{loc: string, lastmod?: string}>
     */
    public function pageUrls(): array
    {
        $pages = app(WebsitePagesReadService::class)->pageCatalog();
        $seoByKey = [];
        foreach (app(SeoReadService::class)->listPages() as $seoPage) {
            $key = (string) ($seoPage['key'] ?? '');
            if ($key !== '') {
                $seoByKey[$key] = $seoPage;
            }
        }

        $urls = [];
        foreach ($pages as $pageKey => $meta) {
            $seo = $seoByKey[$pageKey] ?? null;
            if (is_array($seo) && array_key_exists('include_in_sitemap', $seo) && ! $seo['include_in_sitemap']) {
                continue;
            }

            $route = $meta['public_route'] ?? null;
            if (! is_string($route) || $route === '') {
                continue;
            }
            try {
                $urls[] = ['loc' => route($route)];
            } catch (\Throwable) {
                continue;
            }
        }

        $urls[] = ['loc' => route('public.home')];
        $urls[] = ['loc' => url('/sitemap')];
        $urls[] = ['loc' => url('/feed')];

        return $this->uniqueByLoc($urls);
    }

    /**
     * @return list<array{loc: string, lastmod?: string}>
     */
    public function blogUrls(): array
    {
        if (! Schema::hasTable('ag_blog_posts')) {
            return [];
        }

        return DB::table('ag_blog_posts')
            ->where('is_published', true)
            ->orderByDesc('published_at')
            ->get(['slug', 'updated_at', 'published_at'])
            ->map(function ($row): array {
                $last = $row->updated_at ?? $row->published_at;

                return [
                    'loc' => route('public.blog.show', ['slug' => $row->slug]),
                    'lastmod' => $last ? date('Y-m-d', strtotime((string) $last)) : null,
                ];
            })
            ->filter(fn (array $u) => ($u['loc'] ?? '') !== '')
            ->map(function (array $u): array {
                if (empty($u['lastmod'])) {
                    unset($u['lastmod']);
                }

                return $u;
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{loc: string, lastmod?: string}>
     */
    public function sermonUrls(): array
    {
        if (! Schema::hasTable('sermons')) {
            return [];
        }

        return DB::table('sermons')
            ->where('status', 'published')
            ->orderByDesc('sermon_date')
            ->get(['slug', 'updated_at', 'published_at', 'sermon_date'])
            ->map(function ($row): array {
                $last = $row->updated_at ?? $row->published_at ?? $row->sermon_date;

                return [
                    'loc' => route('public.sermons.show', ['slug' => $row->slug]),
                    'lastmod' => $last ? date('Y-m-d', strtotime((string) $last)) : null,
                ];
            })
            ->map(function (array $u): array {
                if (empty($u['lastmod'])) {
                    unset($u['lastmod']);
                }

                return $u;
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{loc: string, image: string, title?: string}>
     */
    public function imageEntries(): array
    {
        $entries = [];
        $resolver = app(\App\Services\PublicSite\PublicAssetResolver::class);

        if (Schema::hasTable('ag_blog_posts')) {
            foreach (DB::table('ag_blog_posts')->where('is_published', true)->whereNotNull('featured_image')->where('featured_image', '!=', '')->get(['slug', 'title', 'featured_image']) as $row) {
                $entries[] = [
                    'loc' => route('public.blog.show', ['slug' => $row->slug]),
                    'image' => $resolver->url((string) $row->featured_image),
                    'title' => (string) $row->title,
                ];
            }
        }

        if (Schema::hasTable('sermons')) {
            foreach (DB::table('sermons')->where('status', 'published')->whereNotNull('featured_image')->where('featured_image', '!=', '')->get(['slug', 'title', 'featured_image']) as $row) {
                $entries[] = [
                    'loc' => route('public.sermons.show', ['slug' => $row->slug]),
                    'image' => $resolver->url((string) $row->featured_image),
                    'title' => (string) $row->title,
                ];
            }
        }

        return $entries;
    }

    /**
     * @return list<array{title: string, url: string, group: string}>
     */
    public function htmlSitemapGroups(): array
    {
        $items = [];
        foreach ($this->pageUrls() as $u) {
            $items[] = [
                'title' => $this->labelFromUrl($u['loc']),
                'url' => $u['loc'],
                'group' => 'Pages',
            ];
        }
        foreach ($this->blogUrls() as $u) {
            $items[] = [
                'title' => basename(parse_url($u['loc'], PHP_URL_PATH) ?: 'post'),
                'url' => $u['loc'],
                'group' => 'Blog',
            ];
        }
        foreach ($this->sermonUrls() as $u) {
            $items[] = [
                'title' => basename(parse_url($u['loc'], PHP_URL_PATH) ?: 'sermon'),
                'url' => $u['loc'],
                'group' => 'Sermons',
            ];
        }

        return $items;
    }

    /**
     * @param  list<array{loc: string, lastmod?: string|null}>  $urls
     * @return list<array{loc: string, lastmod?: string}>
     */
    private function uniqueByLoc(array $urls): array
    {
        $seen = [];
        $out = [];
        foreach ($urls as $u) {
            $loc = (string) ($u['loc'] ?? '');
            if ($loc === '' || isset($seen[$loc])) {
                continue;
            }
            $seen[$loc] = true;
            if (empty($u['lastmod'])) {
                unset($u['lastmod']);
            }
            $out[] = $u;
        }

        return $out;
    }

    private function labelFromUrl(string $url): string
    {
        $path = trim((string) (parse_url($url, PHP_URL_PATH) ?? ''), '/');
        if ($path === '') {
            return 'Home';
        }

        return ucwords(str_replace(['-', '/'], [' ', ' / '], $path));
    }
}
