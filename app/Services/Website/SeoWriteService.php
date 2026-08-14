<?php

namespace App\Services\Website;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class SeoWriteService
{
    private const MAX_IMAGE_BYTES = 5242880;

    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * @param  array<string, mixed>  $page
     */
    public function savePage(array $page, ?UploadedFile $ogImage = null, bool $removeOgImage = false): void
    {
        $key = trim((string) ($page['key'] ?? ''));
        if ($key === '') {
            return;
        }

        $path = storage_path('app/website/seo-pages-ag.json');
        $dir = storage_path('app/website');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $data = ['pages' => []];
        if (File::exists($path)) {
            $json = json_decode((string) File::get($path), true);
            if (is_array($json) && isset($json['pages']) && is_array($json['pages'])) {
                $data['pages'] = $json['pages'];
            }
        }

        $existing = null;
        $existingIndex = null;
        foreach ($data['pages'] as $index => $row) {
            if (($row['key'] ?? '') === $key) {
                $existing = $row;
                $existingIndex = $index;
                break;
            }
        }

        $currentOg = trim((string) ($existing['og_image'] ?? ''));
        if ($ogImage !== null) {
            $uploaded = $this->storeImage($ogImage, 'uploads/website/seo');
            $this->deleteUploadedImage($currentOg);
            $currentOg = $uploaded;
        } elseif ($removeOgImage) {
            $this->deleteUploadedImage($currentOg);
            $currentOg = '';
        }

        $payload = [
            'key' => $key,
            'site' => (string) ($page['site'] ?? 'ag'),
            'title' => trim((string) ($page['title'] ?? '')),
            'meta_description' => trim((string) ($page['meta_description'] ?? '')),
            'og_image' => $currentOg,
            'include_in_sitemap' => (bool) ($page['include_in_sitemap'] ?? true),
            'robots_notes' => trim((string) ($page['robots_notes'] ?? '')),
        ];

        if ($existingIndex !== null) {
            $data['pages'][$existingIndex] = array_replace($existing ?? [], $payload);
        } else {
            $data['pages'][] = $payload;
        }

        File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
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
