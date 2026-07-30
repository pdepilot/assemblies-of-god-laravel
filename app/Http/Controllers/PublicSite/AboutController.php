<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Services\PublicSite\PublicAssetResolver;
use App\Services\PublicSite\PublicHomepageReadService;
use App\Services\Website\ChurchContentReadService;
use App\Services\Website\SeoReadService;
use App\Services\Website\WebsitePagesReadService;
use Illuminate\View\View;

final class AboutController extends Controller
{
    public function __construct(
        private readonly ChurchContentReadService $content,
        private readonly PublicHomepageReadService $homepage,
        private readonly WebsitePagesReadService $pages,
        private readonly PublicAssetResolver $assets,
        private readonly SeoReadService $seo,
    ) {}

    public function show(): View
    {
        $brandShortName = (string) config('identity.public.short_name', 'AG Ikenebgu');
        $about = $this->resolveMedia($this->content->getSection('about_page'));
        $payload = $this->homepage->payload();
        $page = $this->hydratePageChrome($this->pages->getPage('about'));
        $seo = $this->seo->forKey('about', url('/about'));
        $defaultHeading = (string) ($this->pages->defaultPageContent('about')['heading'] ?? 'About');
        $pageHeading = trim((string) ($page['heading'] ?? ''));
        if ($pageHeading !== '' && strcasecmp($pageHeading, $defaultHeading) !== 0) {
            $seo['title'] = $pageHeading.' | '.$brandShortName;
        }
        if (trim((string) ($page['intro'] ?? '')) !== '') {
            $seo['meta_description'] = \Illuminate\Support\Str::limit(strip_tags((string) $page['intro']), 160, '');
        }
        if (trim((string) ($page['hero_image'] ?? '')) !== '') {
            $seo['og_image'] = (string) $page['hero_image'];
        }

        return view('public.about', [
            'church' => $payload['church'],
            'about' => $about,
            'page' => $page,
            'legacy_api_base' => $payload['legacy_api_base'],
            'traffic_beacon_url' => $payload['traffic_beacon_url'],
            'seo' => $seo,
            'testimonySourcePage' => 'about',
        ]);
    }

    /** @param array<string, mixed> $page @return array<string, mixed> */
    private function hydratePageChrome(array $page): array
    {
        $hero = trim((string) ($page['hero_image'] ?? ''));
        $page['hero_image_url'] = $hero !== '' ? $this->assets->url($hero) : null;

        return $page;
    }

    /** @param array<string, mixed> $content @return array<string, mixed> */
    private function resolveMedia(array $content): array
    {
        if (is_array($content['gallery'] ?? null)) {
            $content['gallery'] = array_map(function ($item) {
                if (is_array($item) && ! empty($item['image'])) {
                    $item['image'] = $this->assets->url((string) $item['image']);
                }

                return $item;
            }, $content['gallery']);
        }

        if (is_array($content['highlight'] ?? null) && ! empty($content['highlight']['image'])) {
            $content['highlight']['image'] = $this->assets->url((string) $content['highlight']['image']);
        }

        return $content;
    }
}
