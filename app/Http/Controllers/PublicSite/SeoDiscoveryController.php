<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Services\PublicSite\PublicHomepageReadService;
use App\Services\Website\BlogReadService;
use App\Services\Website\SchemaBuilder;
use App\Services\Website\SeoReadService;
use App\Services\Website\SitemapReadService;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class SeoDiscoveryController extends Controller
{
    public function __construct(
        private readonly SitemapReadService $sitemaps,
        private readonly BlogReadService $blog,
        private readonly SeoReadService $seo,
        private readonly SchemaBuilder $schema,
        private readonly PublicHomepageReadService $homepage,
    ) {}

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /admin/',
            'Disallow: /erp',
            'Disallow: /erp/',
            'Disallow: /member-portal',
            'Disallow: /member-portal/',
            'Disallow: /api/',
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function sitemapIndex(): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
            .$this->sitemapEntry(url('/sitemap-pages.xml'))
            .$this->sitemapEntry(url('/sitemap-blog.xml'))
            .$this->sitemapEntry(url('/sitemap-sermons.xml'))
            .$this->sitemapEntry(url('/sitemap-images.xml'))
            .'</sitemapindex>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function sitemapPages(): Response
    {
        return $this->urlset($this->sitemaps->pageUrls());
    }

    public function sitemapBlog(): Response
    {
        return $this->urlset($this->sitemaps->blogUrls());
    }

    public function sitemapSermons(): Response
    {
        return $this->urlset($this->sitemaps->sermonUrls());
    }

    public function sitemapImages(): Response
    {
        $entries = $this->sitemaps->imageEntries();
        $body = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'."\n";

        foreach ($entries as $entry) {
            $body .= "  <url>\n";
            $body .= '    <loc>'.e($entry['loc'])."</loc>\n";
            $body .= "    <image:image>\n";
            $body .= '      <image:loc>'.e($entry['image'])."</image:loc>\n";
            if (! empty($entry['title'])) {
                $body .= '      <image:title>'.e($entry['title'])."</image:title>\n";
            }
            $body .= "    </image:image>\n";
            $body .= "  </url>\n";
        }

        $body .= '</urlset>';

        return response($body, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function feed(): Response
    {
        $posts = $this->blog->listPublished('', '', 1, 25);
        $channelTitle = (string) config('identity.public.site_name', 'AGC Ikenegbu');
        $channelLink = url('/');
        $channelDesc = (string) config('identity.public.default_description', 'Church news and devotionals');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<rss version="2.0">'."\n"
            ."<channel>\n"
            .'<title>'.e($channelTitle)."</title>\n"
            .'<link>'.e($channelLink)."</link>\n"
            .'<description>'.e($channelDesc)."</description>\n";

        foreach ($posts['items'] as $post) {
            $link = route('public.blog.show', ['slug' => $post['slug']]);
            $xml .= "<item>\n"
                .'<title>'.e((string) ($post['title'] ?? ''))."</title>\n"
                .'<link>'.e($link)."</link>\n"
                .'<guid>'.e($link)."</guid>\n"
                .'<description>'.e((string) ($post['excerpt'] ?? $post['meta_description'] ?? ''))."</description>\n";
            if (! empty($post['published_at'])) {
                $xml .= '<pubDate>'.e(date(DATE_RSS, strtotime((string) $post['published_at'])))."</pubDate>\n";
            }
            $xml .= "</item>\n";
        }

        $xml .= "</channel>\n</rss>";

        return response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }

    public function htmlSitemap(): View
    {
        $payload = $this->homepage->payload();
        $items = $this->sitemaps->htmlSitemapGroups();
        $groups = [];
        foreach ($items as $item) {
            $groups[$item['group']][] = $item;
        }

        $seo = $this->seo->forKey('home', url('/sitemap'));
        $seo['title'] = 'Sitemap | '.config('identity.public.short_name', 'AGC Ikenegbu');
        $seo['meta_description'] = 'Browse all public pages, blog posts, and sermons on the church website.';

        return view('public.sitemap', [
            'church' => $payload['church'],
            'groups' => $groups,
            'legacy_api_base' => $payload['legacy_api_base'],
            'traffic_beacon_url' => $payload['traffic_beacon_url'],
            'seo' => $seo,
            'schemaGraphs' => array_merge(
                $this->schema->organizationAndChurch(),
                [$this->schema->breadcrumbs([
                    ['name' => 'Home', 'url' => url('/')],
                    ['name' => 'Sitemap', 'url' => url('/sitemap')],
                ])],
            ),
            'testimonySourcePage' => 'sitemap',
        ]);
    }

    /**
     * @param  list<array{loc: string, lastmod?: string}>  $urls
     */
    private function urlset(array $urls): Response
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $u) {
            $body .= "  <url>\n";
            $body .= '    <loc>'.e($u['loc'])."</loc>\n";
            if (! empty($u['lastmod'])) {
                $body .= '    <lastmod>'.e($u['lastmod'])."</lastmod>\n";
            }
            $body .= "  </url>\n";
        }

        $body .= '</urlset>';

        return response($body, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function sitemapEntry(string $loc): string
    {
        return "  <sitemap>\n    <loc>".e($loc)."</loc>\n  </sitemap>\n";
    }
}
