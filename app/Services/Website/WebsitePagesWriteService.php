<?php

namespace App\Services\Website;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class WebsitePagesWriteService
{
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
    public function saveAgHero(array $payload, ?UploadedFile $backgroundImage = null, bool $removeBackground = false): array
    {
        $settings = $this->readSettings();
        $current = is_array($settings['ag']['hero'] ?? null) ? $settings['ag']['hero'] : [];
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
            ],
            'header' => [
                'heading' => trim((string) ($payload['heading'] ?? $defaults['heading'])),
                'eyebrow' => trim((string) ($payload['eyebrow'] ?? '')),
                'intro' => trim((string) ($payload['intro'] ?? '')),
                'hero_image' => $heroPath,
                'cta_label' => trim((string) ($payload['cta_label'] ?? '')),
                'cta_url' => trim((string) ($payload['cta_url'] ?? '')),
            ],
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
