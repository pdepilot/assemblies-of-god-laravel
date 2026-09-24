<?php

namespace App\Services\Sermons;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class SermonWriteService
{
    private const MAX_IMAGE_BYTES = 2097152;

    private const MAX_VIDEO_BYTES = 15728640;

    /** @var array<string, string> */
    private const IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /** @var array<string, string> */
    private const VIDEO_TYPES = [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/quicktime' => 'mov',
        'video/x-m4v' => 'm4v',
    ];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function save(
        array $data,
        int $adminId,
        ?UploadedFile $image = null,
        ?UploadedFile $video = null,
        bool $removeImage = false,
        bool $removeVideo = false,
    ): array {
        $id = (int) ($data['id'] ?? 0);
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new InvalidArgumentException('Sermon title is required.');
        }

        $status = (string) ($data['status'] ?? 'draft');
        if (! in_array($status, SermonReadService::STATUSES, true)) {
            $status = 'draft';
        }

        $sermonType = (string) ($data['sermon_type'] ?? 'audio');
        if ($video instanceof UploadedFile) {
            $sermonType = 'video';
        }
        if (! in_array($sermonType, SermonReadService::TYPES, true)) {
            $sermonType = 'audio';
        }

        $existing = $id > 0 ? DB::table('sermons')->where('id', $id)->first() : null;
        if ($id > 0 && ! $existing) {
            throw new InvalidArgumentException('Sermon not found.');
        }

        $imagePath = $existing->featured_image ?? null;
        $videoPath = $existing->video_file_path ?? null;

        if ($image instanceof UploadedFile) {
            $uploaded = $this->storeUpload($image, 'images', self::IMAGE_TYPES, self::MAX_IMAGE_BYTES, 'Cover image must be JPG, PNG, or WebP up to 2 MB.');
            if (is_string($imagePath) && $imagePath !== '') {
                $this->deleteUpload($imagePath);
            }
            $imagePath = $uploaded;
        } elseif ($removeImage && is_string($imagePath) && $imagePath !== '') {
            $this->deleteUpload($imagePath);
            $imagePath = null;
        }

        if ($video instanceof UploadedFile) {
            $uploaded = $this->storeUpload($video, 'videos', self::VIDEO_TYPES, self::MAX_VIDEO_BYTES, 'Sermon video must be MP4, WebM, or MOV and 15 MB or smaller.');
            if (is_string($videoPath) && $videoPath !== '') {
                $this->deleteUpload($videoPath);
            }
            $videoPath = $uploaded;
        } elseif ($removeVideo && is_string($videoPath) && $videoPath !== '') {
            $this->deleteUpload($videoPath);
            $videoPath = null;
        }

        $payload = [
            'title' => $title,
            'description' => (string) ($data['description'] ?? ''),
            'content_html' => (string) ($data['content_html'] ?? ''),
            'scripture_refs' => trim((string) ($data['scripture_refs'] ?? '')),
            'sermon_date' => (string) ($data['sermon_date'] ?? now()->toDateString()),
            'minister_name' => trim((string) ($data['minister_name'] ?? '')),
            'minister_position' => trim((string) ($data['minister_position'] ?? '')),
            'category_id' => ($data['category_id'] ?? null) ? (int) $data['category_id'] : null,
            'series_id' => ($data['series_id'] ?? null) ? (int) $data['series_id'] : null,
            'sermon_type' => $sermonType,
            'status' => $status,
            'featured_image' => $imagePath,
            'video_file_path' => $videoPath,
            'youtube_url' => trim((string) ($data['youtube_url'] ?? '')),
            'audio_stream_url' => trim((string) ($data['audio_stream_url'] ?? '')),
            'seo_title' => trim((string) ($data['seo_title'] ?? '')),
            'seo_description' => trim((string) ($data['seo_description'] ?? '')),
            'seo_keywords' => trim((string) ($data['seo_keywords'] ?? '')),
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

        if ($id > 0) {
            DB::table('sermons')->where('id', $id)->update($payload);
        } else {
            $slug = $this->slugify((string) ($data['slug'] ?? $title));
            DB::table('sermons')->insert($payload + [
                'sermon_code' => $this->generateSermonCode(),
                'slug' => $slug,
                'created_by' => $adminId > 0 ? $adminId : null,
                'created_at' => now(),
            ]);
            $id = (int) DB::getPdo()->lastInsertId();
        }

        $row = DB::table('sermons')->where('id', $id)->first();

        return $row ? (array) $row : ['id' => $id];
    }

    public function publish(int $id, int $adminId): void
    {
        DB::table('sermons')->where('id', $id)->update([
            'status' => 'published',
            'published_at' => now(),
            'updated_by' => $adminId > 0 ? $adminId : null,
            'updated_at' => now(),
        ]);
    }

    public function archive(int $id, int $adminId): void
    {
        DB::table('sermons')->where('id', $id)->update([
            'status' => 'archived',
            'archived_at' => now(),
            'updated_by' => $adminId > 0 ? $adminId : null,
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, string>  $allowedTypes
     */
    private function storeUpload(UploadedFile $file, string $folder, array $allowedTypes, int $maxBytes, string $invalidMessage): string
    {
        $mime = (string) ($file->getMimeType() ?? '');
        $ext = $allowedTypes[$mime] ?? null;
        if ($ext === null) {
            $fromName = strtolower((string) $file->getClientOriginalExtension());
            if (in_array($fromName, $allowedTypes, true)) {
                $ext = $fromName;
            }
        }
        if ($ext === null || $file->getSize() > $maxBytes) {
            throw new InvalidArgumentException($invalidMessage);
        }

        $filename = Str::random(32).'.'.$ext;
        $relativePath = 'uploads/sermons/'.$folder.'/'.$filename;
        $directory = public_path('site/uploads/sermons/'.$folder);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create sermon upload directory.');
        }

        if (! $file->move($directory, $filename)) {
            throw new RuntimeException('Unable to save the sermon file.');
        }

        return $relativePath;
    }

    private function deleteUpload(string $path): void
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if ($path === '' || ! str_starts_with($path, 'uploads/sermons/')) {
            return;
        }

        $sitePath = public_path('site/'.$path);
        if (is_file($sitePath)) {
            @unlink($sitePath);
        }
    }

    private function generateSermonCode(): string
    {
        do {
            $code = 'SRM-'.strtoupper(Str::random(8));
        } while (DB::table('sermons')->where('sermon_code', $code)->exists());

        return $code;
    }

    private function slugify(string $value): string
    {
        $slug = Str::slug($value);
        if ($slug === '') {
            $slug = 'sermon';
        }

        $base = $slug;
        $suffix = 1;
        while (DB::table('sermons')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
