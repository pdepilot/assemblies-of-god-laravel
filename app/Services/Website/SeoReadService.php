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
        $pageKey = strtolower(trim($pageKey));
        if ($pageKey === '') {
            $pageKey = 'home';
        }

        $page = collect($this->listPages())->firstWhere('key', $pageKey);
        if (! is_array($page)) {
            $page = [
                'key' => $pageKey,
                'site' => 'ag',
                'title' => 'AG Ikenebgu | Assemblies of God Church Owerri',
                'meta_description' => 'AG Ikenebgu Assemblies of God in Owerri, Nigeria — worship, teaching, and community.',
                'og_image' => '',
            ];
        }

        $title = trim((string) ($page['title'] ?? ''));
        $description = trim((string) ($page['meta_description'] ?? ''));
        $ogImage = trim((string) ($page['og_image'] ?? ''));

        if ($title === '') {
            $title = 'AG Ikenebgu | Assemblies of God Church Owerri';
        }
        if ($description === '') {
            $description = 'AG Ikenebgu Assemblies of God in Owerri, Nigeria — spirit-filled worship, Bible teaching, and community outreach.';
        }

        return [
            'key' => $pageKey,
            'site' => (string) ($page['site'] ?? 'ag'),
            'title' => $title,
            'meta_description' => $description,
            'og_image' => $ogImage,
            'canonical' => $canonical ?: $this->defaultCanonical($pageKey),
        ];
    }

    public function keyFromLegacyPath(string $legacyPath, string $area = 'ag'): string
    {
        $path = strtolower(trim(str_replace('\\', '/', $legacyPath), '/'));
        $path = preg_replace('/\.php$/i', '', $path) ?? $path;

        if ($area === 'sdtg' || str_starts_with($path, 'sdgt')) {
            return 'sdtg';
        }

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
            'sdtg' => url('/sdgt'),
            default => url('/'.$pageKey),
        };
    }

    /** @return list<array<string, mixed>> */
    private function defaultPages(): array
    {
        return [
            ['key' => 'home', 'site' => 'ag', 'title' => 'AG Ikenebgu | Assemblies of God Church Owerri — Worship & Community', 'meta_description' => 'AG Ikenebgu Assemblies of God in Owerri, Nigeria — spirit-filled worship, Bible teaching, family ministries, and community outreach. Join us Sundays 8:00 AM & 10:30 AM.', 'og_image' => ''],
            ['key' => 'about', 'site' => 'ag', 'title' => 'About Us | AG Ikenebgu Assemblies of God', 'meta_description' => 'Learn about AG Ikenebgu — our vision, mission, and church family in Ikenegbu, Owerri.', 'og_image' => ''],
            ['key' => 'contact', 'site' => 'ag', 'title' => 'Contact | AG Ikenebgu', 'meta_description' => 'Contact AG Ikenebgu Assemblies of God in Owerri. Plan your visit or reach our church office.', 'og_image' => ''],
            ['key' => 'blog', 'site' => 'ag', 'title' => 'Blog | AG Ikenebgu', 'meta_description' => 'Church news, devotionals, and updates from AG Ikenebgu Assemblies of God.', 'og_image' => ''],
            ['key' => 'activity', 'site' => 'ag', 'title' => 'Ministries | AG Ikenebgu', 'meta_description' => 'Explore ministries and activities at AG Ikenebgu — serving every generation in Christ.', 'og_image' => ''],
            ['key' => 'event', 'site' => 'ag', 'title' => 'Events | AG Ikenebgu', 'meta_description' => 'Upcoming worship services, programs, and church events at AG Ikenebgu.', 'og_image' => ''],
            ['key' => 'donate', 'site' => 'ag', 'title' => 'Give | AG Ikenebgu', 'meta_description' => 'Support the mission of AG Ikenebgu through tithes, offerings, and special gifts.', 'og_image' => ''],
            ['key' => 'privacy', 'site' => 'ag', 'title' => 'Privacy Policy | AG Ikenebgu', 'meta_description' => 'Privacy policy for the AG Ikenebgu church website.', 'og_image' => ''],
            ['key' => 'terms', 'site' => 'ag', 'title' => 'Terms of Use | AG Ikenebgu', 'meta_description' => 'Terms of use for the AG Ikenebgu church website.', 'og_image' => ''],
            ['key' => 'sermons', 'site' => 'ag', 'title' => 'Sermons | AG Ikenebgu', 'meta_description' => 'Watch and listen to sermons from AG Ikenebgu Assemblies of God.', 'og_image' => ''],
            ['key' => 'sdtg', 'site' => 'ag', 'title' => 'Send Down Thy Glory | AG Ikenebgu', 'meta_description' => 'Send Down Thy Glory International Music Crusade — worship, registration, and livestream.', 'og_image' => ''],
        ];
    }
}
