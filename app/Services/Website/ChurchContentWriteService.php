<?php

namespace App\Services\Website;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class ChurchContentWriteService
{
    private const MAX_IMAGE_BYTES = 5242880;

    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public function __construct(
        private readonly ChurchContentReadService $read,
    ) {}

    /**
     * @param  array<string, mixed>  $value
     * @param  array<string, UploadedFile|null>  $uploads  keys: gallery_0, gallery_1, gallery_2, highlight
     * @return array<string, mixed>
     */
    public function saveSection(string $sectionKey, array $value, int $adminId, array $uploads = []): array
    {
        if (! in_array($sectionKey, ChurchContentReadService::SECTIONS, true)) {
            throw new InvalidArgumentException('Unknown content section.');
        }

        // Never trust client-submitted image paths/URLs — keep stored paths and replace only via upload.
        $value = $this->preserveExistingImages($value, $this->read->getSection($sectionKey));
        $value = $this->applyUploads($value, $uploads);
        $value = $this->sanitizeSection($sectionKey, $value);
        $json = json_encode($value, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('Unable to encode content.');
        }

        $existing = DB::table('ag_site_content')->where('section_key', $sectionKey)->first();
        $payload = [
            'content_json' => $json,
            'updated_by' => $adminId > 0 ? $adminId : null,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('ag_site_content')->where('section_key', $sectionKey)->update($payload);
        } else {
            DB::table('ag_site_content')->insert($payload + [
                'section_key' => $sectionKey,
                'created_at' => now(),
            ]);
        }

        return $this->read->getSection($sectionKey);
    }

    /** @return array<string, mixed> */
    public function resetSection(string $sectionKey, int $adminId): array
    {
        if (! in_array($sectionKey, ChurchContentReadService::SECTIONS, true)) {
            throw new InvalidArgumentException('Unknown content section.');
        }

        $defaults = $this->read->defaults()[$sectionKey] ?? [];

        return $this->saveSection($sectionKey, $defaults, $adminId);
    }

    /**
     * @param  array<string, mixed>  $value
     * @param  array<string, mixed>  $existing
     * @return array<string, mixed>
     */
    private function preserveExistingImages(array $value, array $existing): array
    {
        $existingGallery = is_array($existing['gallery'] ?? null) ? $existing['gallery'] : [];
        $requestGallery = is_array($value['gallery'] ?? null) ? $value['gallery'] : [];
        $gallery = [];
        for ($i = 0; $i < 3; $i++) {
            $requestItem = is_array($requestGallery[$i] ?? null) ? $requestGallery[$i] : [];
            $existingItem = is_array($existingGallery[$i] ?? null) ? $existingGallery[$i] : [];
            $gallery[$i] = [
                'image' => trim((string) ($existingItem['image'] ?? '')),
                'alt' => trim((string) ($requestItem['alt'] ?? $existingItem['alt'] ?? '')),
            ];
        }
        $value['gallery'] = $gallery;

        $existingHighlight = is_array($existing['highlight'] ?? null) ? $existing['highlight'] : [];
        $requestHighlight = is_array($value['highlight'] ?? null) ? $value['highlight'] : [];
        $value['highlight'] = [
            'image' => trim((string) ($existingHighlight['image'] ?? '')),
            'image_alt' => trim((string) ($requestHighlight['image_alt'] ?? $existingHighlight['image_alt'] ?? '')),
            'quote' => (string) ($requestHighlight['quote'] ?? $existingHighlight['quote'] ?? ''),
            'stat_value' => (string) ($requestHighlight['stat_value'] ?? $existingHighlight['stat_value'] ?? ''),
            'stat_label' => (string) ($requestHighlight['stat_label'] ?? $existingHighlight['stat_label'] ?? ''),
        ];

        return $value;
    }

    /**
     * @param  array<string, mixed>  $value
     * @param  array<string, UploadedFile|null>  $uploads
     * @return array<string, mixed>
     */
    private function applyUploads(array $value, array $uploads): array
    {
        $gallery = is_array($value['gallery'] ?? null) ? $value['gallery'] : [];
        for ($i = 0; $i < 3; $i++) {
            $file = $uploads['gallery_'.$i] ?? null;
            if ($file instanceof UploadedFile) {
                $gallery[$i] = is_array($gallery[$i] ?? null) ? $gallery[$i] : ['image' => '', 'alt' => ''];
                $gallery[$i]['image'] = $this->storeImage($file);
            }
        }
        $value['gallery'] = $gallery;

        $highlight = is_array($value['highlight'] ?? null) ? $value['highlight'] : [];
        $highlightFile = $uploads['highlight'] ?? null;
        if ($highlightFile instanceof UploadedFile) {
            $highlight['image'] = $this->storeImage($highlightFile);
        }
        $value['highlight'] = $highlight;

        return $value;
    }

    private function storeImage(UploadedFile $image): string
    {
        if (! isset(self::ALLOWED_IMAGE_TYPES[$image->getMimeType() ?? ''])) {
            throw new InvalidArgumentException('Image must be JPG, PNG, WebP, or GIF.');
        }
        if ($image->getSize() > self::MAX_IMAGE_BYTES) {
            throw new InvalidArgumentException('Image must be 5 MB or smaller.');
        }

        $ext = self::ALLOWED_IMAGE_TYPES[$image->getMimeType()];
        $filename = Str::random(32).'.'.$ext;
        $directory = public_path('site/uploads/church-content');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create upload directory.');
        }
        if (! $image->move($directory, $filename)) {
            throw new RuntimeException('Unable to save uploaded image.');
        }

        return 'uploads/church-content/'.$filename;
    }

    /**
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    private function sanitizeSection(string $key, array $value): array
    {
        $defaults = $this->read->defaults()[$key] ?? [];
        $clean = array_replace_recursive($defaults, $value);

        foreach (['eyebrow', 'title', 'intro', 'vision_title', 'vision_text', 'mission_title', 'mission_text'] as $field) {
            if (array_key_exists($field, $clean)) {
                $clean[$field] = trim((string) $clean[$field]);
            }
        }

        $clean['gallery'] = $this->sanitizeGallery($clean['gallery'] ?? []);
        $clean['highlight'] = $this->sanitizeHighlight($clean['highlight'] ?? []);
        $clean['features'] = $this->sanitizeFeatures($clean['features'] ?? []);

        if ($key === 'homepage_about') {
            $clean['scripture_banner'] = $this->sanitizeScriptureBanner($clean['scripture_banner'] ?? []);
        }

        if ($key === 'about_page') {
            $hero = is_array($clean['hero'] ?? null) ? $clean['hero'] : [];
            $clean['hero'] = [
                'title' => trim((string) ($hero['title'] ?? 'About Us')),
                'breadcrumb_home_label' => trim((string) ($hero['breadcrumb_home_label'] ?? 'Home')),
                'breadcrumb_home_url' => trim((string) ($hero['breadcrumb_home_url'] ?? './')),
                'breadcrumb_parent_label' => trim((string) ($hero['breadcrumb_parent_label'] ?? 'Pages')),
                'breadcrumb_parent_url' => trim((string) ($hero['breadcrumb_parent_url'] ?? '#')),
                'breadcrumb_current' => trim((string) ($hero['breadcrumb_current'] ?? 'About')),
            ];
            $clean['cta_banner'] = $this->sanitizeCtaBanner($clean['cta_banner'] ?? []);
        }

        return $clean;
    }

    /** @return list<array{image: string, alt: string}> */
    private function sanitizeGallery(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $items = [];
        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }
            $image = $this->normalizeStoredImagePath((string) ($item['image'] ?? ''));
            if ($image === '') {
                continue;
            }
            $items[] = [
                'image' => $image,
                'alt' => trim((string) ($item['alt'] ?? '')),
            ];
        }

        return array_slice($items, 0, 3);
    }

    /** @return array{image: string, image_alt: string, quote: string, stat_value: string, stat_label: string} */
    private function sanitizeHighlight(mixed $raw): array
    {
        $raw = is_array($raw) ? $raw : [];

        return [
            'image' => $this->normalizeStoredImagePath((string) ($raw['image'] ?? '')),
            'image_alt' => trim((string) ($raw['image_alt'] ?? '')),
            'quote' => trim((string) ($raw['quote'] ?? '')),
            'stat_value' => trim((string) ($raw['stat_value'] ?? '')),
            'stat_label' => trim((string) ($raw['stat_label'] ?? '')),
        ];
    }

    private function normalizeStoredImagePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        if ($path === '' || str_contains($path, '://') || str_starts_with($path, '//') || str_contains($path, '..')) {
            return '';
        }

        return ltrim($path, '/');
    }

    /** @return list<string> */
    private function sanitizeFeatures(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        }
        if (! is_array($raw)) {
            return [];
        }

        $items = [];
        foreach ($raw as $item) {
            $text = trim(is_string($item) ? $item : (string) ($item['text'] ?? ''));
            if ($text !== '') {
                $items[] = $text;
            }
        }

        return array_slice($items, 0, 8);
    }

    /** @return array{cta_label: string, cta_url: string, verses: list<array{quote: string, reference: string}>} */
    private function sanitizeScriptureBanner(mixed $raw): array
    {
        $raw = is_array($raw) ? $raw : [];
        $verses = [];
        foreach ($raw['verses'] ?? [] as $verse) {
            if (! is_array($verse)) {
                continue;
            }
            $quote = trim((string) ($verse['quote'] ?? ''));
            $reference = trim((string) ($verse['reference'] ?? ''));
            if ($quote === '' && $reference === '') {
                continue;
            }
            $verses[] = ['quote' => $quote, 'reference' => $reference];
        }

        return [
            'cta_label' => trim((string) ($raw['cta_label'] ?? 'Learn More')),
            'cta_url' => trim((string) ($raw['cta_url'] ?? 'about')),
            'verses' => array_slice($verses, 0, 8),
        ];
    }

    /** @return array{title: string, cta_label: string, cta_url: string} */
    private function sanitizeCtaBanner(mixed $raw): array
    {
        $raw = is_array($raw) ? $raw : [];

        return [
            'title' => trim((string) ($raw['title'] ?? '')),
            'cta_label' => trim((string) ($raw['cta_label'] ?? 'Learn More')),
            'cta_url' => trim((string) ($raw['cta_url'] ?? '')),
        ];
    }
}
