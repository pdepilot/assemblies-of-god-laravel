<?php

namespace App\Services\Sdtg;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class SdtgMediaWriteService
{
    private const MAX_IMAGE_BYTES = 10 * 1024 * 1024;

    private const MAX_AUDIO_BYTES = 40 * 1024 * 1024;

    private const MAX_VIDEO_BYTES = 200 * 1024 * 1024;

    private const IMAGE_EXTS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    private const AUDIO_EXTS = ['mp3', 'wav', 'ogg', 'm4a'];

    private const VIDEO_EXTS = ['mp4', 'webm', 'mov'];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function saveFolder(array $data): array
    {
        if (! Schema::hasTable('sdtg_media_folders')) {
            throw new InvalidArgumentException('Media folders table is not available.');
        }

        $id = (int) ($data['id'] ?? 0);
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Folder name is required.');
        }

        $payload = [
            'name' => $name,
            'slug' => $this->uniqueFolderSlug($name, $id > 0 ? $id : null),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'sort_order' => max(0, (int) ($data['sort_order'] ?? 0)),
            'updated_at' => now(),
        ];

        if ($id > 0) {
            DB::table('sdtg_media_folders')->where('id', $id)->update($payload);
        } else {
            $id = (int) DB::table('sdtg_media_folders')->insertGetId($payload + ['created_at' => now()]);
        }

        return (array) DB::table('sdtg_media_folders')->where('id', $id)->first();
    }

    public function deleteFolder(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Folder not found.');
        }

        DB::table('sdtg_media_assets')->where('folder_id', $id)->update(['folder_id' => null]);
        DB::table('sdtg_media_folders')->where('id', $id)->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function saveAsset(
        array $data,
        ?UploadedFile $media = null,
        ?UploadedFile $thumbnail = null,
        bool $removeMedia = false,
        bool $removeThumb = false,
    ): array {
        if (! Schema::hasTable('sdtg_media_assets')) {
            throw new InvalidArgumentException('Media assets table is not available.');
        }

        $id = (int) ($data['id'] ?? 0);
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new InvalidArgumentException('Title is required.');
        }

        $mediaType = (string) ($data['media_type'] ?? 'image');
        if (! in_array($mediaType, SdtgMediaReadService::MEDIA_TYPES, true)) {
            $mediaType = 'image';
        }

        $status = (string) ($data['status'] ?? 'draft');
        if (! in_array($status, SdtgMediaReadService::STATUSES, true)) {
            $status = 'draft';
        }

        $existing = $id > 0 ? DB::table('sdtg_media_assets')->where('id', $id)->first() : null;
        $filePath = $existing->file_path ?? null;
        $thumbPath = $existing->thumbnail_path ?? null;
        $fileSize = $existing->file_size ?? null;

        $folderId = (int) ($data['folder_id'] ?? 0);
        $folderId = $folderId > 0 ? $folderId : null;

        $year = (int) ($data['crusade_year'] ?? date('Y'));
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

        if ($removeMedia && $filePath) {
            $this->deleteFile((string) $filePath);
            $filePath = null;
            $fileSize = null;
        }

        if ($media) {
            $fileSize = $media->getSize() ?: null;
            $stored = $this->storeFile($media, $mediaType);
            if ($filePath) {
                $this->deleteFile((string) $filePath);
            }
            $filePath = $stored;
        }

        if ($removeThumb && $thumbPath) {
            $this->deleteFile((string) $thumbPath);
            $thumbPath = null;
        }

        if ($thumbnail) {
            $storedThumb = $this->storeFile($thumbnail, 'image');
            if ($thumbPath) {
                $this->deleteFile((string) $thumbPath);
            }
            $thumbPath = $storedThumb;
        }

        $videoType = null;
        $videoSrc = null;
        if ($mediaType === 'video') {
            $videoType = (string) ($data['video_type'] ?? 'local');
            if (! in_array($videoType, ['local', 'youtube', 'vimeo'], true)) {
                $videoType = 'local';
            }
            $videoSrc = trim((string) ($data['video_src'] ?? '')) ?: null;
        }

        if (! $filePath && ! ($mediaType === 'video' && $videoSrc) && ! $existing) {
            throw new InvalidArgumentException('Upload a file (or provide a YouTube/Vimeo source for videos).');
        }

        $payload = [
            'folder_id' => $folderId,
            'media_type' => $mediaType,
            'title' => $title,
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'tags' => trim((string) ($data['tags'] ?? '')) ?: null,
            'file_path' => $filePath,
            'external_url' => null,
            'thumbnail_path' => $thumbPath,
            'video_type' => $videoType,
            'video_src' => $videoSrc,
            'duration_label' => trim((string) ($data['duration_label'] ?? '')) ?: null,
            'crusade_year' => $year,
            'status' => $status,
            'file_size' => $fileSize,
            'sort_order' => max(0, (int) ($data['sort_order'] ?? 0)),
            'updated_at' => now(),
        ];

        if ($id > 0) {
            DB::table('sdtg_media_assets')->where('id', $id)->update($payload);
        } else {
            $id = (int) DB::table('sdtg_media_assets')->insertGetId($payload + ['created_at' => now()]);
        }

        return (array) DB::table('sdtg_media_assets')->where('id', $id)->first();
    }

    public function deleteAsset(int $id): void
    {
        $asset = DB::table('sdtg_media_assets')->where('id', $id)->first();
        if (! $asset) {
            return;
        }

        if ($asset->file_path) {
            $this->deleteFile((string) $asset->file_path);
        }
        if ($asset->thumbnail_path && ! str_starts_with((string) $asset->thumbnail_path, 'http')) {
            $this->deleteFile((string) $asset->thumbnail_path);
        }

        DB::table('sdtg_media_assets')->where('id', $id)->delete();
    }

    private function storeFile(UploadedFile $file, string $mediaType): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        $allowed = match ($mediaType) {
            'video' => self::VIDEO_EXTS,
            'audio' => self::AUDIO_EXTS,
            default => self::IMAGE_EXTS,
        };
        $max = match ($mediaType) {
            'video' => self::MAX_VIDEO_BYTES,
            'audio' => self::MAX_AUDIO_BYTES,
            default => self::MAX_IMAGE_BYTES,
        };

        if (! in_array($ext, $allowed, true)) {
            throw new InvalidArgumentException('Unsupported file type for '.$mediaType.' upload.');
        }

        $size = $file->getSize();
        if ($size !== null && $size > $max) {
            throw new InvalidArgumentException('File exceeds the maximum allowed size for this media type.');
        }

        $name = bin2hex(random_bytes(16)).'.'.$ext;
        $relativePath = 'uploads/sdtg-media/'.$name;
        $directory = public_path('site/uploads/sdtg-media');

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new InvalidArgumentException('Unable to create media upload directory.');
        }

        if (! $file->move($directory, $name)) {
            throw new InvalidArgumentException('Unable to save uploaded file.');
        }

        return $relativePath;
    }

    private function deleteFile(string $path): void
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if ($path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }
        if (! str_starts_with($path, 'uploads/sdtg-media/') && ! str_starts_with($path, 'uploads/sdtg-gallery/')) {
            return;
        }

        $sitePath = public_path('site/'.$path);
        if (is_file($sitePath)) {
            @unlink($sitePath);
        }
    }

    private function uniqueFolderSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'folder';
        $slug = $base;
        $suffix = 1;

        while (
            DB::table('sdtg_media_folders')
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
