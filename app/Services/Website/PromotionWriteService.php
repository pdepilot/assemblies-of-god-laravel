<?php

namespace App\Services\Website;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class PromotionWriteService
{
    private const MAX_IMAGE_BYTES = 5242880;

    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function save(array $data, ?UploadedFile $image = null, bool $removeImage = false): array
    {
        $id = (int) ($data['id'] ?? 0);
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new InvalidArgumentException('Promotion title is required.');
        }

        $existing = $id > 0
            ? DB::table('website_promotions')->where('id', $id)->first()
            : null;
        $imagePath = $existing?->image_path ? (string) $existing->image_path : null;

        if ($image !== null) {
            $uploaded = $this->storeImage($image);
            if ($imagePath) {
                $this->deleteImage($imagePath);
            }
            $imagePath = $uploaded;
        } elseif ($removeImage && $imagePath) {
            $this->deleteImage($imagePath);
            $imagePath = null;
        }

        $payload = [
            'title' => $title,
            'eyebrow' => $this->nullableString($data['eyebrow'] ?? null, 120),
            'body' => $this->nullableString($data['body'] ?? null, 4000),
            'image_path' => $imagePath,
            'cta_label' => $this->nullableString($data['cta_label'] ?? null, 80),
            'cta_url' => $this->sanitizeCtaUrl((string) ($data['cta_url'] ?? '')),
            'starts_at' => $this->nullableTimestamp($data['starts_at'] ?? null),
            'ends_at' => $this->nullableTimestamp($data['ends_at'] ?? null),
            'sort_order' => max(0, (int) ($data['sort_order'] ?? 0)),
            'is_active' => ! empty($data['is_active']) && (string) $data['is_active'] !== '0',
            'show_every_visit' => ! empty($data['show_every_visit']) && (string) $data['show_every_visit'] !== '0',
            'updated_at' => now(),
        ];

        if ($id > 0) {
            DB::table('website_promotions')->where('id', $id)->update($payload);
        } else {
            DB::table('website_promotions')->insert($payload + [
                'created_at' => now(),
            ]);
            $id = (int) DB::getPdo()->lastInsertId();
        }

        $row = DB::table('website_promotions')->where('id', $id)->first();

        return $row ? (array) $row : ['id' => $id];
    }

    public function delete(int $id): void
    {
        $row = DB::table('website_promotions')->where('id', $id)->first();
        if (! $row) {
            return;
        }

        if (! empty($row->image_path)) {
            $this->deleteImage((string) $row->image_path);
        }

        DB::table('website_promotions')->where('id', $id)->delete();
    }

    private function storeImage(UploadedFile $image): string
    {
        if (! isset(self::ALLOWED_IMAGE_TYPES[$image->getMimeType() ?? ''])) {
            throw new InvalidArgumentException('Promotion image must be JPG, PNG, or WebP.');
        }
        if ($image->getSize() > self::MAX_IMAGE_BYTES) {
            throw new InvalidArgumentException('Promotion image must be 5 MB or smaller.');
        }

        $ext = self::ALLOWED_IMAGE_TYPES[$image->getMimeType()];
        $filename = Str::random(32).'.'.$ext;
        $relativePath = 'uploads/website/promotions/'.$filename;
        $directory = public_path('site/uploads/website/promotions');

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create promotion image upload directory.');
        }

        if (! $image->move($directory, $filename)) {
            throw new RuntimeException('Unable to save promotion image.');
        }

        return $relativePath;
    }

    private function deleteImage(string $path): void
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if ($path === '' || ! str_starts_with($path, 'uploads/website/promotions/')) {
            return;
        }

        $sitePath = public_path('site/'.$path);
        if (is_file($sitePath)) {
            @unlink($sitePath);
        }
    }

    private function nullableString(mixed $value, int $max): ?string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        return mb_substr($text, 0, $max);
    }

    private function sanitizeCtaUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        $lower = strtolower($url);
        if (str_starts_with($lower, 'javascript:') || str_starts_with($lower, 'data:')) {
            throw new InvalidArgumentException('Promotion link is not allowed.');
        }

        return mb_substr($url, 0, 500);
    }

    private function nullableTimestamp(mixed $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->toDateTimeString();
        } catch (\Throwable) {
            throw new InvalidArgumentException('Promotion schedule date is invalid.');
        }
    }
}
