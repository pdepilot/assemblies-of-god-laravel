<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Services\PublicSite\PublicAssetResolver;
use App\Services\PublicSite\PublicHomepageReadService;
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
    ) {}

    public function show(string $pageKey): View
    {
        $brandShortName = (string) config('identity.public.short_name', 'AG Ikenebgu');
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
        $seo = $this->seo->forKey($pageKey, url('/'.$pageKey));
        if (trim((string) ($page['heading'] ?? '')) !== '') {
            $seo['title'] = (string) $page['heading'].' | '.$brandShortName;
        }
        if (trim((string) ($page['intro'] ?? '')) !== '') {
            $seo['meta_description'] = \Illuminate\Support\Str::limit(strip_tags((string) $page['intro']), 160, '');
        }
        if (trim((string) ($page['hero_image'] ?? '')) !== '') {
            $seo['og_image'] = (string) $page['hero_image'];
        }

        $viewData = [
            'church' => $payload['church'],
            'page' => $page,
            'pageKey' => $pageKey,
            'navActive' => $this->navActive($pageKey),
            'legacy_api_base' => $payload['legacy_api_base'],
            'traffic_beacon_url' => $payload['traffic_beacon_url'],
            'seo' => $seo,
            'testimonySourcePage' => $pageKey,
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
}
