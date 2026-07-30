<?php

namespace App\Services\Website;

use Illuminate\Support\Facades\File;

final class WebsitePagesReadService
{
    /**
     * Catalog of pages editable from Website → Pages.
     *
     * @return array<string, array{label: string, type: string, public_route: string|null, description: string}>
     */
    public function pageCatalog(): array
    {
        return [
            'home' => [
                'label' => 'Homepage sections',
                'type' => 'home',
                'public_route' => 'public.home',
                'description' => 'Section titles for ministries, events, and worship on the homepage.',
            ],
            'about' => [
                'label' => 'About',
                'type' => 'header',
                'public_route' => 'public.about',
                'description' => 'Page header shown on /about (detailed About content is under Website → About).',
            ],
            'activity' => [
                'label' => 'Ministries',
                'type' => 'header',
                'public_route' => 'public.activity',
                'description' => 'Ministries / activities page header and intro.',
            ],
            'event' => [
                'label' => 'Events',
                'type' => 'header',
                'public_route' => 'public.event',
                'description' => 'Events page header and intro.',
            ],
            'blog' => [
                'label' => 'Blog',
                'type' => 'header',
                'public_route' => 'public.blog',
                'description' => 'Blog listing page header.',
            ],
            'sermons' => [
                'label' => 'Sermons',
                'type' => 'header',
                'public_route' => 'public.sermons',
                'description' => 'Sermons page header and intro.',
            ],
            'contact' => [
                'label' => 'Contact',
                'type' => 'content',
                'public_route' => 'public.contact',
                'description' => 'Contact page header and body content.',
            ],
            'donate' => [
                'label' => 'Give / Donate',
                'type' => 'content',
                'public_route' => 'public.donate',
                'description' => 'Giving page header and body content.',
            ],
            'privacy' => [
                'label' => 'Privacy Policy',
                'type' => 'content',
                'public_route' => 'public.privacy',
                'description' => 'Privacy policy page content.',
            ],
            'terms' => [
                'label' => 'Terms of Use',
                'type' => 'content',
                'public_route' => 'public.terms',
                'description' => 'Terms of use page content.',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function getBootstrap(): array
    {
        $settings = $this->readSettings();
        $pages = [];
        foreach ($this->pageCatalog() as $key => $meta) {
            $pages[$key] = $this->getPage($key);
        }

        return [
            'ag' => [
                'hero' => $this->resolveAgHero($settings),
                'pages' => $pages,
            ],
            'sdtg' => [
                'hero' => $settings['sdtg']['hero'] ?? [],
                'pages' => $settings['sdtg']['pages'] ?? [],
            ],
            'custom_pages' => array_values($settings['custom_pages'] ?? []),
            'catalog' => $this->pageCatalog(),
        ];
    }

    /** @return array<string, mixed> */
    public function getPage(string $pageKey): array
    {
        $pageKey = strtolower(trim($pageKey));
        $catalog = $this->pageCatalog();
        if (! isset($catalog[$pageKey])) {
            return [];
        }

        $defaults = $this->defaultPageContent($pageKey);
        $settings = $this->readSettings();
        $stored = is_array($settings['ag']['pages'][$pageKey] ?? null)
            ? $settings['ag']['pages'][$pageKey]
            : [];

        return array_replace_recursive($defaults, $stored);
    }

    public function isEditablePage(string $pageKey): bool
    {
        return isset($this->pageCatalog()[strtolower(trim($pageKey))]);
    }

    /** @return array<string, mixed> */
    public function defaultPageContent(string $pageKey): array
    {
        $shortName = (string) config('identity.public.short_name', 'AG Ikenebgu');
        $meta = $this->pageCatalog()[$pageKey] ?? null;
        $type = is_array($meta) ? (string) $meta['type'] : 'content';
        $label = is_array($meta) ? (string) $meta['label'] : ucfirst($pageKey);

        return match ($type) {
            'home' => [
                'ministries_eyebrow' => 'Ministries',
                'ministries_title' => 'Serving God Through Every Season of Life',
                'events_eyebrow' => 'Gather With Us',
                'events_title' => 'Upcoming Events',
                'events_intro' => 'Worship, study, and prayer — rhythm of life together at '.$shortName.'. Mark your calendar and bring someone along.',
                'worship_eyebrow' => 'Plan Your Visit',
                'worship_title' => 'Join Us In Worship',
                'worship_intro' => 'Everyone is welcome. Come worship with us at '.$shortName.' - Ikenegbu Layout, Owerri.',
            ],
            'header' => [
                'heading' => $label,
                'eyebrow' => '',
                'intro' => '',
                'hero_image' => '',
                'cta_label' => '',
                'cta_url' => '',
            ],
            default => [
                'heading' => $label,
                'eyebrow' => '',
                'intro' => '',
                'body_html' => '',
                'hero_image' => '',
                'cta_label' => '',
                'cta_url' => '',
            ],
        };
    }

    /** @param array<string, mixed> $settings @return array<string, mixed> */
    private function resolveAgHero(array $settings): array
    {
        $hero = is_array($settings['ag']['hero'] ?? null) ? $settings['ag']['hero'] : [];
        $slide = $settings['ag']['homepage_content']['hero']['slides'][0] ?? null;
        if (is_array($slide)) {
            if (trim((string) ($hero['headline'] ?? '')) === '' && trim((string) ($slide['headline'] ?? '')) !== '') {
                $hero['headline'] = (string) $slide['headline'];
            }
            if (trim((string) ($hero['subheadline'] ?? '')) === '' && trim((string) ($slide['tagline'] ?? '')) !== '') {
                $hero['subheadline'] = (string) $slide['tagline'];
            }
            if (trim((string) ($hero['cta_label'] ?? '')) === '' && trim((string) ($slide['primary_label'] ?? '')) !== '') {
                $hero['cta_label'] = (string) $slide['primary_label'];
            }
            if (trim((string) ($hero['cta_url'] ?? '')) === '' && trim((string) ($slide['primary_url'] ?? '')) !== '') {
                $hero['cta_url'] = (string) $slide['primary_url'];
            }
            if (trim((string) ($hero['background_image'] ?? '')) === '' && trim((string) ($slide['image'] ?? '')) !== '') {
                $hero['background_image'] = (string) $slide['image'];
            }
        }

        return $hero !== [] ? $hero : $this->defaultAgHero();
    }

    /** @return array<string, mixed> */
    private function readSettings(): array
    {
        $path = storage_path('app/website/website-pages.json');
        if (! File::exists($path)) {
            return [];
        }

        $json = json_decode((string) File::get($path), true);

        return is_array($json) ? $json : [];
    }

    /** @return array<string, mixed> */
    private function defaultAgHero(): array
    {
        return [
            'headline' => 'Welcome to Assemblies of God Ikenebgu',
            'subheadline' => 'A place of worship, fellowship, and transformation.',
            'cta_label' => 'Plan Your Visit',
            'cta_url' => '/contact',
            'background_image' => '',
        ];
    }
}
