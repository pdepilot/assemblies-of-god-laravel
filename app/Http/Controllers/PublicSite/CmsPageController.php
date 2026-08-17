<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Services\PublicSite\PublicAssetResolver;
use App\Services\PublicSite\PublicHomepageReadService;
use App\Services\Website\SchemaBuilder;
use App\Services\Website\SeoReadService;
use App\Services\Website\WebsitePagesReadService;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CmsPageController extends Controller
{
    public function __construct(
        private readonly WebsitePagesReadService $pages,
        private readonly PublicHomepageReadService $homepage,
        private readonly PublicAssetResolver $assets,
        private readonly SeoReadService $seo,
        private readonly SchemaBuilder $schema,
    ) {}

    public function show(string $pageKey): View
    {
        $brandShortName = (string) config('identity.public.short_name', 'AGC Ikenegbu');
        $pageKey = strtolower(trim($pageKey));
        if (! $this->pages->isEditablePage($pageKey)) {
            throw new NotFoundHttpException('Page not found.');
        }

        $catalog = $this->pages->pageCatalog()[$pageKey];
        $type = (string) ($catalog['type'] ?? 'content');
        if ($type === 'home') {
            throw new NotFoundHttpException('Page not found.');
        }

        $page = $this->hydratePage($this->pages->getPage($pageKey));
        $payload = $this->homepage->payload();
        $canonical = null;
        try {
            $routeName = (string) ($catalog['public_route'] ?? '');
            $canonical = $routeName !== '' ? route($routeName) : url('/'.$pageKey);
        } catch (\Throwable) {
            $canonical = url('/'.$pageKey);
        }
        $seo = $this->seo->forKey($pageKey, $canonical);
        // Prefer SEO manager / pages SEO tab. Only fill empties from page chrome.
        if (trim((string) ($seo['title'] ?? '')) === '' && trim((string) ($page['heading'] ?? '')) !== '') {
            $seo['title'] = (string) $page['heading'].' | '.$brandShortName;
        }
        if (trim((string) ($seo['meta_description'] ?? '')) === '' && trim((string) ($page['intro'] ?? '')) !== '') {
            $seo['meta_description'] = \Illuminate\Support\Str::limit(strip_tags((string) $page['intro']), 160, '');
        }
        if (trim((string) ($seo['og_image'] ?? '')) === '' && trim((string) ($page['hero_image'] ?? '')) !== '') {
            $seo['og_image'] = (string) $page['hero_image'];
        }

        $noAdsPages = [
            'privacy',
            'terms',
            'cookie-policy',
            'disclaimer',
            'accessibility',
            'editorial-policy',
        ];

        $viewData = [
            'church' => $payload['church'],
            'page' => $page,
            'pageKey' => $pageKey,
            'navActive' => $this->navActive($pageKey),
            'legacy_api_base' => $payload['legacy_api_base'],
            'traffic_beacon_url' => $payload['traffic_beacon_url'],
            'seo' => $seo,
            'schemaGraphs' => $this->schemaGraphsForPage($pageKey, $page, $canonical),
            'testimonySourcePage' => $pageKey,
            'allowAds' => ! in_array($pageKey, $noAdsPages, true),
        ];

        if ($type === 'header') {
            $viewData['activities'] = $pageKey === 'activity' ? $payload['activities'] : [];
            $viewData['events'] = $pageKey === 'event' ? $payload['events'] : [];
            $sermons = $payload['sermons'] ?? [];
            if ($pageKey === 'sermons' && is_array($sermons)) {
                if (trim((string) ($page['eyebrow'] ?? '')) !== '') {
                    $sermons['eyebrow'] = (string) $page['eyebrow'];
                }
                if (trim((string) ($page['heading'] ?? '')) !== '') {
                    $sermons['title'] = (string) $page['heading'];
                }
            }
            $viewData['sermons'] = $pageKey === 'sermons' ? $sermons : ['cards' => []];
            $viewData['legacy_base'] = $payload['legacy_base'] ?? url('/');
            $viewData['asset_base'] = $payload['asset_base'] ?? asset('site');

            return view('public.pages.listing', $viewData);
        }

        return view('public.pages.content', $viewData);
    }

    /** @param array<string, mixed> $page @return array<string, mixed> */
    private function hydratePage(array $page): array
    {
        $hero = trim((string) ($page['hero_image'] ?? ''));
        $page['hero_image_url'] = $hero !== '' ? $this->assets->url($hero) : null;

        return $page;
    }

    private function navActive(string $pageKey): string
    {
        return match ($pageKey) {
            'activity' => 'ministries',
            'event' => 'events',
            'sermons' => 'sermons',
            'blog' => 'blog',
            'about' => 'about',
            'contact' => 'contact',
            default => '',
        };
    }

    /**
     * @param  array<string, mixed>  $page
     * @return list<array<string, mixed>>
     */
    private function schemaGraphsForPage(string $pageKey, array $page, string $canonical): array
    {
        $graphs = $this->schema->organizationAndChurch();
        $graphs[] = $this->schema->breadcrumbs([
            ['name' => 'Home', 'url' => url('/')],
            ['name' => (string) ($page['heading'] ?? ucfirst($pageKey)), 'url' => $canonical],
        ]);

        if ($pageKey === 'faq') {
            $faqs = $this->extractFaqs((string) ($page['body_html'] ?? ''));
            $faqSchema = $this->schema->faqPage($faqs);
            if ($faqSchema !== null) {
                $graphs[] = $faqSchema;
            }
        }

        return $graphs;
    }

    /** @return list<array{question: string, answer: string}> */
    private function extractFaqs(string $html): array
    {
        $faqs = [];
        if (preg_match_all('/<h2[^>]*>(.*?)<\/h2>\s*<p[^>]*>(.*?)<\/p>/is', $html, $matches, PREG_SET_ORDER) === false) {
            return [];
        }
        foreach ($matches as $match) {
            $q = trim(strip_tags($match[1]));
            $a = trim(strip_tags($match[2]));
            if ($q !== '' && $a !== '') {
                $faqs[] = ['question' => $q, 'answer' => $a];
            }
        }

        return $faqs;
    }
}
