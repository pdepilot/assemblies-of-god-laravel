<?php

namespace App\Services\Sdtg;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class SdtgGalleryWriteService
{
    private const MAX_BYTES = 10 * 1024 * 1024;

    private const MAX_VIDEO_BYTES = 80 * 1024 * 1024;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function saveAlbum(array $data, ?UploadedFile $cover = null, bool $removeCover = false): array
    {
        if (! Schema::hasTable('sdtg_gallery_albums')) {
            throw new InvalidArgumentException('Gallery albums table is not available.');
        }

        $id = (int) ($data['id'] ?? 0);
        $title = trim((string) ($data['title'] ?? ''));
        $year = (int) ($data['crusade_year'] ?? 0);

        if ($title === '') {
            throw new InvalidArgumentException('Album title is required.');
        }
        if ($year < 2000 || $year > 2100) {
            throw new InvalidArgumentException('A valid crusade year is required.');
        }

        $existing = $id > 0 ? DB::table('sdtg_gallery_albums')->where('id', $id)->first() : null;
        $coverPath = $existing->cover_path ?? null;

        if ($removeCover && $coverPath) {
            $this->deleteFile((string) $coverPath);
            $coverPath = null;
        }

        if ($cover) {
            $stored = $this->storeImage($cover);
            if ($coverPath) {
                $this->deleteFile((string) $coverPath);
            }
            $coverPath = $stored;
        }

        $payload = [
            'title' => $title,
            'slug' => $this->uniqueAlbumSlug($title, $id > 0 ? $id : null),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'crusade_year' => $year,
            'cover_path' => $coverPath,
            'is_published' => ! empty($data['is_published']),
            'sort_order' => max(0, (int) ($data['sort_order'] ?? 0)),
            'updated_at' => now(),
        ];

        if ($id > 0) {
            DB::table('sdtg_gallery_albums')->where('id', $id)->update($payload);
        } else {
            $id = (int) DB::table('sdtg_gallery_albums')->insertGetId($payload + ['created_at' => now()]);
        }

        return (array) DB::table('sdtg_gallery_albums')->where('id', $id)->first();
    }

    public function deleteAlbum(int $id): void
    {
        $items = DB::table('sdtg_gallery_items')->where('album_id', $id)->get();
        foreach ($items as $item) {
            $this->deleteItem((int) $item->id);
        }

        $album = DB::table('sdtg_gallery_albums')->where('id', $id)->first();
        if ($album && $album->cover_path) {
            $this->deleteFile((string) $album->cover_path);
        }

        DB::table('sdtg_gallery_albums')->where('id', $id)->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function saveItem(
        array $data,
        ?UploadedFile $media = null,
        ?UploadedFile $thumbnail = null,
        bool $removeMedia = false,
    ): array {
        if (! Schema::hasTable('sdtg_gallery_items')) {
            throw new InvalidArgumentException('Gallery items table is not available.');
        }

        $id = (int) ($data['id'] ?? 0);
        $title = trim((string) ($data['title'] ?? ''));
        $year = (int) ($data['crusade_year'] ?? date('Y'));
        $mediaType = (string) ($data['media_type'] ?? 'photo');
        $category = (string) ($data['category'] ?? 'highlights');
        $layout = (string) ($data['layout_size'] ?? 'md');

        if ($title === '') {
            throw new InvalidArgumentException('Item title is required.');
        }
        if (! in_array($mediaType, SdtgGalleryReadService::MEDIA_TYPES, true)) {
            throw new InvalidArgumentException('Invalid media type.');
        }
        if (! in_array($category, SdtgGalleryReadService::CATEGORIES, true)) {
            throw new InvalidArgumentException('Invalid category.');
        }
        if (! in_array($layout, SdtgGalleryReadService::LAYOUTS, true)) {
            $layout = 'md';
        }

        $existing = $id > 0 ? DB::table('sdtg_gallery_items')->where('id', $id)->first() : null;
        $filePath = $existing->file_path ?? null;
        $thumbPath = $existing->thumbnail_path ?? null;
        $fileSize = $existing->file_size ?? null;

        if ($removeMedia && $filePath) {
            $this->deleteFile((string) $filePath);
            $filePath = null;
            $fileSize = null;
        }

        if ($media) {
            $fileSize = $media->getSize() ?: null;
            $stored = $mediaType === 'video'
                ? $this->storeMediaFile($media, 'video')
                : $this->storeMediaFile($media, 'image');
            if ($filePath) {
                $this->deleteFile((string) $filePath);
            }
            $filePath = $stored;
            if ($mediaType === 'video') {
                $data['video_type'] = 'local';
                if (trim((string) ($data['video_src'] ?? '')) === '') {
                    $data['video_src'] = $stored;
                }
            }
        }

        if ($thumbnail) {
            $storedThumb = $this->storeMediaFile($thumbnail, 'image');
            if ($thumbPath) {
                $this->deleteFile((string) $thumbPath);
            }
            $thumbPath = $storedThumb;
        }

        $albumId = (int) ($data['album_id'] ?? 0);
        $videoSrc = $mediaType === 'video' ? (trim((string) ($data['video_src'] ?? '')) ?: null) : null;
        if ($mediaType === 'video' && $videoSrc === null && is_string($filePath)) {
            $videoSrc = $filePath;
        }
        $payload = [
            'album_id' => $albumId > 0 ? $albumId : null,
            'media_type' => $mediaType,
            'title' => $title,
            'caption' => trim((string) ($data['caption'] ?? '')) ?: null,
            'category' => $category,
            'layout_size' => $layout,
            'file_path' => $filePath,
            'external_url' => null,
            'video_type' => $mediaType === 'video' ? ((string) ($data['video_type'] ?? 'youtube') ?: null) : null,
            'video_src' => $videoSrc,
            'thumbnail_path' => $thumbPath,
            'crusade_year' => $year,
            'tags' => trim((string) ($data['tags'] ?? '')) ?: null,
            'is_featured' => ! empty($data['is_featured']),
            'is_speakers_highlight' => ! empty($data['is_speakers_highlight']),
            'is_published' => ! empty($data['is_published']),
            'sort_order' => max(0, (int) ($data['sort_order'] ?? 0)),
            'file_size' => $fileSize,
            'updated_at' => now(),
        ];

        if ($id > 0) {
            DB::table('sdtg_gallery_items')->where('id', $id)->update($payload);
        } else {
            $id = (int) DB::table('sdtg_gallery_items')->insertGetId($payload + ['created_at' => now()]);
        }

        return (array) DB::table('sdtg_gallery_items')->where('id', $id)->first();
    }

    public function deleteItem(int $id): void
    {
        $item = DB::table('sdtg_gallery_items')->where('id', $id)->first();
        if (! $item) {
            return;
        }

        // Memory-sourced items keep original files elsewhere.
        if (empty($item->source_memory_id)) {
            if ($item->file_path) {
                $this->deleteFile((string) $item->file_path);
            }
            if ($item->thumbnail_path) {
                $this->deleteFile((string) $item->thumbnail_path);
            }
        }

        DB::table('sdtg_gallery_items')->where('id', $id)->delete();
    }

    private function storeMediaFile(UploadedFile $file, string $kind): string
    {
        $max = $kind === 'video' ? self::MAX_VIDEO_BYTES : self::MAX_BYTES;
        if ($file->getSize() !== null && $file->getSize() > $max) {
            throw new InvalidArgumentException($kind === 'video'
                ? 'Video must be 80MB or smaller.'
                : 'Image must be 10MB or smaller.');
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: ($kind === 'video' ? 'mp4' : 'jpg'));
        $allowed = $kind === 'video'
            ? ['mp4', 'webm', 'mov']
            : ['jpg', 'jpeg', 'png', 'webp'];
        if (! in_array($ext, $allowed, true)) {
            throw new InvalidArgumentException($kind === 'video'
                ? 'Only MP4, WebM, and MOV videos are allowed.'
                : 'Only JPG, PNG, and WebP images are allowed.');
        }

        $name = bin2hex(random_bytes(16)).'.'.$ext;
        $relativePath = 'uploads/sdtg-gallery/'.$name;
        $directory = public_path('site/uploads/sdtg-gallery');

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new InvalidArgumentException('Unable to create gallery upload directory.');
        }

        if (! $file->move($directory, $name)) {
            throw new InvalidArgumentException('Unable to save uploaded '.$kind.'.');
        }

        return $relativePath;
    }

    private function storeImage(UploadedFile $file): string
    {
        return $this->storeMediaFile($file, 'image');
    }

    private function deleteFile(string $path): void
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if ($path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        $sitePath = public_path('site/'.$path);
        if (is_file($sitePath)) {
            @unlink($sitePath);

            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function uniqueAlbumSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'album';
        $slug = $base;
        $suffix = 1;

        while (
            DB::table('sdtg_gallery_albums')
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '<>', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
