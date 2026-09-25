<?php

namespace App\Services\Website;

use Illuminate\Support\Facades\File;

final class SeoReadService
{
    /** @return list<array<string, mixed>> */
    public function listPages(): array
    {
        $defaults = $this->defaultPages();
        $storedByKey = [];

        foreach ($this->readStoredPages() as $page) {
            $key = trim((string) ($page['key'] ?? ''));
            if ($key !== '') {
                $storedByKey[$key] = $page;
            }
        }

        $merged = [];
        foreach ($defaults as $default) {
            $key = (string) $default['key'];
            $merged[] = isset($storedByKey[$key])
                ? array_replace($default, $storedByKey[$key])
                : $default;
            unset($storedByKey[$key]);
        }

        foreach ($storedByKey as $extra) {
            $merged[] = array_replace([
                'site' => 'ag',
                'title' => '',
                'meta_description' => '',
                'og_image' => '',
            ], $extra);
        }

        return $merged;
    }

    /**
     * Resolve SEO for a public page key.
     *
     * @return array{key: string, site: string, title: string, meta_description: string, og_image: string, canonical: string}
     */
    public function forKey(string $pageKey, ?string $canonical = null): array
    {
        $defaults = $this->defaults();
        $pageKey = strtolower(trim($pageKey));
        if ($pageKey === '') {
            $pageKey = 'home';
        }

        $page = collect($this->listPages())->firstWhere('key', $pageKey);
        if (! is_array($page)) {
            $page = [
                'key' => $pageKey,
                'site' => 'ag',
                'title' => $defaults['title'],
                'meta_description' => $defaults['description'],
                'og_image' => '',
            ];
        }

        $title = trim((string) ($page['title'] ?? ''));
        $description = trim((string) ($page['meta_description'] ?? ''));
        $ogImage = trim((string) ($page['og_image'] ?? ''));

        if ($title === '') {
            $title = $defaults['title'];
        }
        if ($description === '') {
            $description = $defaults['description'];
        }

        return [
            'key' => $pageKey,
            'site' => (string) ($page['site'] ?? 'ag'),
            'title' => $title,
            'meta_description' => $description,
            'og_image' => $ogImage,
            'canonical' => $canonical ?: $this->defaultCanonical($pageKey),
            'include_in_sitemap' => array_key_exists('include_in_sitemap', $page)
                ? (bool) $page['include_in_sitemap']
                : true,
            'robots_notes' => trim((string) ($page['robots_notes'] ?? '')),
        ];
    }

    public function keyFromLegacyPath(string $legacyPath, string $area = 'ag'): string
    {
        $path = strtolower(trim(str_replace('\\', '/', $legacyPath), '/'));
        $path = preg_replace('/\.php$/i', '', $path) ?? $path;

        return match (true) {
            $path === '' || $path === 'index' => 'home',
            str_starts_with($path, 'about') => 'about',
            str_starts_with($path, 'contact') => 'contact',
            str_starts_with($path, 'blog') => 'blog',
            str_starts_with($path, 'activity') || str_starts_with($path, 'ministr') => 'activity',
            str_starts_with($path, 'event') => 'event',
            str_starts_with($path, 'donate') => 'donate',
            str_starts_with($path, 'privacy') => 'privacy',
            str_starts_with($path, 'terms') => 'terms',
            str_starts_with($path, 'sermon') => 'sermons',
            default => 'home',
        };
    }

    /** @return list<array<string, mixed>> */
    private function readStoredPages(): array
    {
        $path = storage_path('app/website/seo-pages-ag.json');
        if (! File::exists($path)) {
            return [];
        }

        $json = json_decode((string) File::get($path), true);
        $pages = $json['pages'] ?? null;

        return is_array($pages) ? array_values($pages) : [];
    }

    private function defaultCanonical(string $pageKey): string
    {
        return match ($pageKey) {
            'home' => url('/'),
            'about' => url('/about'),
            'contact' => url('/contact'),
            'blog' => url('/blog'),
            'activity' => url('/activity'),
            'event' => url('/event'),
            'donate' => url('/donate'),
            'privacy' => url('/privacy'),
            'terms' => url('/terms'),
            'sermons' => url('/sermons'),
            'cookie-policy' => url('/cookie-policy'),
            'leadership' => url('/leadership'),
            'statement-of-faith' => url('/statement-of-faith'),
            'mission-vision' => url('/mission-vision'),
            'editorial-policy' => url('/editorial-policy'),
            'accessibility' => url('/accessibility'),
            'disclaimer' => url('/disclaimer'),
            'faq' => url('/faq'),
            default => url('/'.$pageKey),
        };
    }

    /** @return list<array<string, mixed>> */
    private function defaultPages(): array
    {
        $identity = config('identity.public');
        $shortName = (string) ($identity['short_name'] ?? 'AGC Ikenegbu');
        $siteName = (string) ($identity['site_name'] ?? 'AGC Ikenegbu Assemblies of God');
        $defaultTitle = (string) ($identity['default_title'] ?? 'AGC Ikenegbu | Assemblies of God Church, Owerri');
        $defaultDescription = (string) ($identity['default_description'] ?? 'AGC Ikenegbu is an Assemblies of God church in Ikenegbu, Owerri, Imo State. Join us for Sunday worship, sermons, ministries, events, and a welcoming Christian community.');

        $page = static fn (string $key, string $title, string $description): array => [
            'key' => $key,
            'site' => 'ag',
            'title' => $title,
            'meta_description' => $description,
            'og_image' => '',
            'include_in_sitemap' => true,
            'robots_notes' => '',
        ];

        return [
            $page('home', $defaultTitle, $defaultDescription),
            $page('about', 'About Us | '.$siteName, 'Learn about '.$shortName.' — our vision, mission, and church family in Ikenegbu, Owerri.'),
            $page('contact', 'Contact | '.$shortName, 'Contact '.$siteName.' in Owerri. Plan your visit or reach our church office.'),
            $page('blog', 'Blog | '.$shortName, 'Church news, devotionals, and updates from '.$siteName.'.'),
            $page('activity', 'Ministries | '.$shortName, 'Explore ministries and activities at '.$shortName.' — serving every generation in Christ.'),
            $page('event', 'Events | '.$shortName, 'Upcoming worship services, programs, and church events at '.$shortName.'.'),
            $page('donate', 'Give | '.$shortName, 'Support the mission of '.$shortName.' through tithes, offerings, and special gifts.'),
            $page('privacy', 'Privacy Policy | '.$shortName, 'Privacy policy for the '.$shortName.' website.'),
            $page('terms', 'Terms of Use | '.$shortName, 'Terms of use for the '.$shortName.' website.'),
            $page('sermons', 'Sermons | '.$shortName, 'Watch and listen to sermons from '.$siteName.'.'),
            $page('cookie-policy', 'Cookie Policy | '.$shortName, 'How '.$shortName.' uses cookies and how you can manage preferences.'),
            $page('leadership', 'Leadership Team | '.$shortName, 'Meet the pastoral and ministry leaders serving '.$shortName.'.'),
            $page('statement-of-faith', 'Statement of Faith | '.$shortName, 'What '.$shortName.' believes — Assemblies of God doctrines and our confession of faith.'),
            $page('mission-vision', 'Mission & Vision | '.$shortName, 'The mission and vision of '.$shortName.' in Owerri and beyond.'),
            $page('editorial-policy', 'Editorial Policy | '.$shortName, 'How '.$shortName.' reviews and publishes website content.'),
            $page('accessibility', 'Accessibility | '.$shortName, 'Accessibility commitments for the '.$shortName.' public website.'),
            $page('disclaimer', 'Disclaimer | '.$shortName, 'Website disclaimer for '.$shortName.' ministry content.'),
            $page('faq', 'FAQ | '.$shortName, 'Frequently asked questions about visiting and worshipping with '.$shortName.'.'),
        ];
    }

    /** @return array{title: string, description: string} */
    private function defaults(): array
    {
        return [
            'title' => (string) config('identity.public.default_og_title', 'AGC Ikenegbu | Assemblies of God Church Owerri'),
            'description' => (string) config('identity.public.default_og_description', 'AGC Ikenegbu Assemblies of God in Owerri, Nigeria — spirit-filled worship, Bible teaching, and community outreach.'),
        ];
    }
}
