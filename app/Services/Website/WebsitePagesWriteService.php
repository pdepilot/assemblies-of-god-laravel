<?php

namespace App\Services\Website;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class WebsitePagesWriteService
{
    public const HERO_SECTION_KEY = 'homepage_hero';

    private const MAX_IMAGE_BYTES = 5242880;

    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function saveAgHero(array $payload, ?UploadedFile $backgroundImage = null, bool $removeBackground = false, int $adminId = 0): array
    {
        $settings = $this->readSettings();
        $current = is_array($settings['ag']['hero'] ?? null) ? $settings['ag']['hero'] : [];
        $fromDb = $this->readHeroFromDatabase();
        if ($fromDb !== null) {
            $current = $fromDb;
        }
        $backgroundPath = trim((string) ($current['background_image'] ?? ''));

        if ($backgroundImage !== null) {
            $uploaded = $this->storeImage($backgroundImage, 'uploads/website');
            $this->deleteUploadedImage($backgroundPath);
            $backgroundPath = $uploaded;
        } elseif ($removeBackground) {
            $this->deleteUploadedImage($backgroundPath);
            $backgroundPath = '';
        }

        $settings['ag']['hero'] = [
            'headline' => trim((string) ($payload['headline'] ?? '')),
            'subheadline' => trim((string) ($payload['subheadline'] ?? '')),
            'cta_label' => trim((string) ($payload['cta_label'] ?? '')),
            'cta_url' => trim((string) ($payload['cta_url'] ?? '')),
            'background_image' => $backgroundPath,
        ];

        // Keep the public multi-slide hero in sync (admin form edits slide 1).
        $slides = $settings['ag']['homepage_content']['hero']['slides'] ?? [];
        if (! is_array($slides) || $slides === []) {
            $slides = [[
                'image' => 'images/main1.jpg',
                'tagline' => 'Assemblies of God · Ikenebgu',
                'headline' => $settings['ag']['hero']['headline'],
                'primary_label' => $settings['ag']['hero']['cta_label'] !== '' ? $settings['ag']['hero']['cta_label'] : 'Plan Your Visit',
                'primary_url' => $settings['ag']['hero']['cta_url'] !== '' ? ltrim($settings['ag']['hero']['cta_url'], '/') : 'contact',
                'secondary_label' => 'About Our Church',
                'secondary_url' => 'about',
            ]];
        }

        $first = is_array($slides[0] ?? null) ? $slides[0] : [];
        $first['headline'] = $settings['ag']['hero']['headline'];
        if ($settings['ag']['hero']['subheadline'] !== '') {
            $first['tagline'] = $settings['ag']['hero']['subheadline'];
        }
        if ($settings['ag']['hero']['cta_label'] !== '') {
            $first['primary_label'] = $settings['ag']['hero']['cta_label'];
        }
        if ($settings['ag']['hero']['cta_url'] !== '') {
            $cta = $settings['ag']['hero']['cta_url'];
            $first['primary_url'] = str_starts_with($cta, 'http') ? $cta : ltrim($cta, '/');
        }
        if ($backgroundPath !== '') {
            $first['image'] = $backgroundPath;
        }
        $slides[0] = $first;
        $settings['ag']['homepage_content']['hero']['slides'] = array_values($slides);
        if (! isset($settings['ag']['homepage_content']['hero']['interval_ms'])) {
            $settings['ag']['homepage_content']['hero']['interval_ms'] = 7000;
        }

        $this->persistHeroToDatabase($settings['ag']['hero'], $adminId);
        $this->writeSettings($settings);

        return $settings['ag']['hero'];
    }

    /** @param array<string, mixed> $payload */
    public function savePageOverride(
        string $pageKey,
        array $payload,
        ?UploadedFile $heroImage = null,
        bool $removeHeroImage = false,
    ): array {
        $pageKey = strtolower(trim($pageKey));
        $reader = new WebsitePagesReadService;
        if (! $reader->isEditablePage($pageKey)) {
            throw new InvalidArgumentException('Unknown page key.');
        }

        $settings = $this->readSettings();
        $current = is_array($settings['ag']['pages'][$pageKey] ?? null)
            ? $settings['ag']['pages'][$pageKey]
            : [];
        $defaults = $reader->defaultPageContent($pageKey);
        $type = (string) ($reader->pageCatalog()[$pageKey]['type'] ?? 'content');

        $heroPath = trim((string) ($current['hero_image'] ?? ''));
        if ($heroImage !== null) {
            $uploaded = $this->storeImage($heroImage, 'uploads/website/pages');
            $this->deleteUploadedImage($heroPath);
            $heroPath = $uploaded;
        } elseif ($removeHeroImage) {
            $this->deleteUploadedImage($heroPath);
            $heroPath = '';
        }

        $saved = match ($type) {
            'home' => [
                'ministries_eyebrow' => trim((string) ($payload['ministries_eyebrow'] ?? $defaults['ministries_eyebrow'])),
                'ministries_title' => trim((string) ($payload['ministries_title'] ?? $defaults['ministries_title'])),
                'events_eyebrow' => trim((string) ($payload['events_eyebrow'] ?? $defaults['events_eyebrow'])),
                'events_title' => trim((string) ($payload['events_title'] ?? $defaults['events_title'])),
                'events_intro' => trim((string) ($payload['events_intro'] ?? $defaults['events_intro'])),
                'worship_eyebrow' => trim((string) ($payload['worship_eyebrow'] ?? $defaults['worship_eyebrow'])),
                'worship_title' => trim((string) ($payload['worship_title'] ?? $defaults['worship_title'])),
                'worship_intro' => trim((string) ($payload['worship_intro'] ?? $defaults['worship_intro'])),
                'sermons_eyebrow' => trim((string) ($payload['sermons_eyebrow'] ?? $defaults['sermons_eyebrow'])),
                'sermons_title' => trim((string) ($payload['sermons_title'] ?? $defaults['sermons_title'])),
                'team_eyebrow' => trim((string) ($payload['team_eyebrow'] ?? $defaults['team_eyebrow'])),
                'team_title' => trim((string) ($payload['team_title'] ?? $defaults['team_title'])),
                'testimonials_eyebrow' => trim((string) ($payload['testimonials_eyebrow'] ?? $defaults['testimonials_eyebrow'])),
                'testimonials_title' => trim((string) ($payload['testimonials_title'] ?? $defaults['testimonials_title'])),
                'testimonials_intro' => trim((string) ($payload['testimonials_intro'] ?? $defaults['testimonials_intro'])),
            ],
            'header' => [
                'heading' => trim((string) ($payload['heading'] ?? $defaults['heading'])),
                'eyebrow' => trim((string) ($payload['eyebrow'] ?? '')),
                'intro' => trim((string) ($payload['intro'] ?? '')),
                'hero_image' => $heroPath,
                'cta_label' => trim((string) ($payload['cta_label'] ?? '')),
                'cta_url' => trim((string) ($payload['cta_url'] ?? '')),
            ],
            'donate' => $this->donatePayload($payload, $defaults),
            'worship' => [],
            default => [
                'heading' => trim((string) ($payload['heading'] ?? $defaults['heading'])),
                'eyebrow' => trim((string) ($payload['eyebrow'] ?? '')),
                'intro' => trim((string) ($payload['intro'] ?? '')),
                'body_html' => (string) ($payload['body_html'] ?? ''),
                'hero_image' => $heroPath,
                'cta_label' => trim((string) ($payload['cta_label'] ?? '')),
                'cta_url' => trim((string) ($payload['cta_url'] ?? '')),
            ],
        };

        $settings['ag']['pages'][$pageKey] = $saved;
        $this->writeSettings($settings);

        return $saved;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $defaults
     * @return array<string, string>
     */
    private function donatePayload(array $payload, array $defaults): array
    {
        $keys = [
            'hero_badge', 'hero_title', 'hero_scripture', 'hero_ref', 'hero_cta_label',
            'categories_eyebrow', 'categories_title', 'categories_lead',
            'online_eyebrow', 'online_title', 'online_lead',
            'pledge_eyebrow', 'pledge_title', 'pledge_lead',
            'sponsorship_eyebrow', 'sponsorship_title', 'sponsorship_lead',
            'trust_eyebrow', 'trust_title',
            'impact_eyebrow', 'impact_title', 'impact_lead',
            'donors_eyebrow', 'donors_title', 'donors_lead',
            'stories_eyebrow', 'stories_title', 'stories_lead',
            'final_cta_title', 'final_cta_text', 'final_cta_label',
        ];

        $out = [];
        foreach ($keys as $key) {
            $fallback = (string) ($defaults[$key] ?? '');
            $out[$key] = trim((string) ($payload[$key] ?? $fallback));
        }

        return $out;
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

    /** @param array<string, mixed> $settings */
    private function writeSettings(array $settings): void
    {
        $dir = storage_path('app/website');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put(
            $dir.'/website-pages.json',
            json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /** @return array<string, mixed>|null */
    private function readHeroFromDatabase(): ?array
    {
        if (! Schema::hasTable('ag_site_content')) {
            return null;
        }

        $row = DB::table('ag_site_content')
            ->where('section_key', self::HERO_SECTION_KEY)
            ->first();
        if ($row === null) {
            return null;
        }

        $decoded = json_decode((string) ($row->content_json ?? ''), true);

        return is_array($decoded) ? $decoded : null;
    }

    /** @param array<string, mixed> $hero */
    private function persistHeroToDatabase(array $hero, int $adminId): void
    {
        if (! Schema::hasTable('ag_site_content')) {
            return;
        }

        $json = json_encode($hero, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('Unable to encode homepage hero.');
        }

        $existing = DB::table('ag_site_content')
            ->where('section_key', self::HERO_SECTION_KEY)
            ->first();
        $payload = [
            'content_json' => $json,
            'updated_by' => $adminId > 0 ? $adminId : null,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('ag_site_content')
                ->where('section_key', self::HERO_SECTION_KEY)
                ->update($payload);
        } else {
            DB::table('ag_site_content')->insert($payload + [
                'section_key' => self::HERO_SECTION_KEY,
                'created_at' => now(),
            ]);
        }
    }

    private function storeImage(UploadedFile $image, string $directory): string
    {
        if (! isset(self::ALLOWED_IMAGE_TYPES[$image->getMimeType() ?? ''])) {
            throw new InvalidArgumentException('Image must be JPG, PNG, or WebP.');
        }
        if ($image->getSize() > self::MAX_IMAGE_BYTES) {
            throw new InvalidArgumentException('Image must be 5 MB or smaller.');
        }

        $ext = self::ALLOWED_IMAGE_TYPES[$image->getMimeType()];
        $filename = Str::random(32).'.'.$ext;
        $relativePath = trim($directory, '/').'/'.$filename;
        $absoluteDir = public_path('site/'.trim($directory, '/'));

        if (! is_dir($absoluteDir) && ! mkdir($absoluteDir, 0755, true) && ! is_dir($absoluteDir)) {
            throw new RuntimeException('Unable to create upload directory.');
        }

        if (! $image->move($absoluteDir, $filename)) {
            throw new RuntimeException('Unable to save uploaded image.');
        }

        return $relativePath;
    }

    private function deleteUploadedImage(string $path): void
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if ($path === '' || ! str_starts_with($path, 'uploads/website/')) {
            return;
        }

        $sitePath = public_path('site/'.$path);
        if (is_file($sitePath)) {
            @unlink($sitePath);
        }
    }
}
