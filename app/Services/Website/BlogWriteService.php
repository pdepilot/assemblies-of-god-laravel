<?php

namespace App\Services\Website;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class BlogWriteService
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
    public function save(array $data, int $adminId, ?UploadedFile $featuredImage = null, bool $removeFeaturedImage = false): array
    {
        $id = (int) ($data['id'] ?? 0);
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new InvalidArgumentException('Post title is required.');
        }

        $category = (string) ($data['category'] ?? 'church-news');
        if (! isset(BlogReadService::categoryLabels()[$category])) {
            $category = 'church-news';
        }

        $currentImage = '';
        if ($id > 0) {
            $currentImage = trim((string) (DB::table('ag_blog_posts')->where('id', $id)->value('featured_image') ?? ''));
        }

        if ($featuredImage !== null) {
            $uploaded = $this->storeImage($featuredImage, 'uploads/website/blog');
            $this->deleteUploadedImage($currentImage);
            $currentImage = $uploaded;
        } elseif ($removeFeaturedImage) {
            $this->deleteUploadedImage($currentImage);
            $currentImage = '';
        }

        $payload = [
            'title' => $title,
            'excerpt' => trim((string) ($data['excerpt'] ?? '')),
            'body_html' => (string) ($data['body_html'] ?? ''),
            'author' => trim((string) ($data['author'] ?? 'AGC Ikenegbu')),
            'category' => $category,
            'meta_description' => trim((string) ($data['meta_description'] ?? '')),
            'seo_title' => trim((string) ($data['seo_title'] ?? '')),
            'featured_image' => $currentImage,
            'featured_image_alt' => trim((string) ($data['featured_image_alt'] ?? '')),
            'is_published' => (bool) ($data['is_published'] ?? false),
            'updated_by' => $adminId > 0 ? $adminId : null,
            'updated_at' => now(),
        ];

        $tags = $data['tags'] ?? [];
        if (is_string($tags)) {
            $tags = preg_split('/[,]+/', $tags) ?: [];
        }
        if (is_array($tags)) {
            $payload['tags'] = json_encode(array_values(array_filter(array_map(
                static fn ($t) => Str::slug(trim((string) $t)),
                $tags
            ))));
        }

        $bodyText = trim(strip_tags((string) ($payload['body_html'] ?? '')));
        $words = $bodyText === '' ? 0 : count(preg_split('/\s+/u', $bodyText) ?: []);
        $payload['reading_time_minutes'] = max(1, (int) ceil($words / 200));

        if ($id > 0) {
            DB::table('ag_blog_posts')->where('id', $id)->update($payload);
        } else {
            $slug = $this->slugify((string) ($data['slug'] ?? $title));
            DB::table('ag_blog_posts')->insert($payload + [
                'slug' => $slug,
                'view_count' => 0,
                'created_by' => $adminId > 0 ? $adminId : null,
                'created_at' => now(),
            ]);
            $id = (int) DB::getPdo()->lastInsertId();
        }

        $row = DB::table('ag_blog_posts')->where('id', $id)->first();

        return $row ? (array) $row : ['id' => $id];
    }

    public function setPublished(int $id, bool $published, int $adminId): void
    {
        DB::table('ag_blog_posts')->where('id', $id)->update([
            'is_published' => $published,
            'published_at' => $published ? now() : null,
            'updated_by' => $adminId > 0 ? $adminId : null,
            'updated_at' => now(),
        ]);
    }

    private function slugify(string $value): string
    {
        $slug = Str::slug($value);
        if ($slug === '') {
            $slug = 'post';
        }

        $base = $slug;
        $suffix = 1;
        while (DB::table('ag_blog_posts')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
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
