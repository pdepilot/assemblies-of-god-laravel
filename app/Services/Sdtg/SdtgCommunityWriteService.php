<?php

namespace App\Services\Sdtg;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class SdtgCommunityWriteService
{
    public function updateTestimonialStatus(int $id, string $status): void
    {
        DB::table('sdtg_testimonials')->where('id', $id)->update([
            'status' => $status,
            'reviewed_at' => now(),
        ]);
    }

    public function updatePrayerStatus(int $id, string $status): void
    {
        DB::table('sdtg_prayer_requests')->where('id', $id)->update([
            'status' => $status,
        ]);
    }

    public function updateMemoryStatus(int $id, string $status): void
    {
        if (! in_array($status, ['pending', 'featured', 'rejected'], true)) {
            throw new InvalidArgumentException('Invalid memory status.');
        }

        $memory = DB::table('sdtg_memory_submissions')->where('id', $id)->first();
        if (! $memory) {
            throw new InvalidArgumentException('Memory submission not found.');
        }

        DB::table('sdtg_memory_submissions')->where('id', $id)->update([
            'status' => $status,
        ]);

        if ($status === 'featured') {
            $this->publishMemoryToGallery($id);
        } elseif ($status === 'rejected') {
            $this->unpublishMemoryFromGallery($id);
        }
    }

    public function publishMemoryToGallery(int $memoryId): array
    {
        $row = DB::table('sdtg_memory_submissions')->where('id', $memoryId)->first();
        if (! $row) {
            throw new InvalidArgumentException('Memory submission not found.');
        }

        $existingIds = DB::table('sdtg_gallery_items')
            ->where('source_memory_id', $memoryId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($existingIds !== []) {
            DB::table('sdtg_gallery_items')->where('source_memory_id', $memoryId)->update([
                'is_published' => true,
                'updated_at' => now(),
            ]);
            DB::table('sdtg_memory_submissions')->where('id', $memoryId)->update([
                'status' => 'featured',
                'published_to_gallery_at' => $row->published_to_gallery_at ?? now(),
            ]);

            return ['gallery_item_ids' => $existingIds, 'republished' => true];
        }

        $photos = json_decode((string) ($row->photo_paths ?? '[]'), true);
        $videos = json_decode((string) ($row->video_paths ?? '[]'), true);
        $photos = is_array($photos) ? $photos : [];
        $videos = is_array($videos) ? $videos : [];

        $name = (string) $row->full_name;
        $testimony = trim((string) ($row->testimony_text ?? ''));
        $edition = trim((string) ($row->edition ?? ''));
        $year = preg_match('/^\d{4}$/', $edition) ? (int) $edition : (int) date('Y');
        $albumId = $this->ensureCommunityMemoriesAlbum();
        $caption = $testimony !== '' ? $testimony : 'Shared by '.$name.' from the SDTG community.';
        $category = $testimony !== '' ? 'healing' : 'highlights';
        $layouts = SdtgGalleryReadService::LAYOUTS;
        $galleryItemIds = [];
        $sortBase = 9000 + $memoryId * 10;

        foreach ($photos as $index => $path) {
            $path = trim((string) $path);
            if ($path === '') {
                continue;
            }
            $galleryItemIds[] = (int) DB::table('sdtg_gallery_items')->insertGetId([
                'album_id' => $albumId,
                'source_memory_id' => $memoryId,
                'media_type' => 'photo',
                'title' => $name.' — SDTG Memory',
                'caption' => $caption,
                'category' => $category,
                'layout_size' => $layouts[$index % count($layouts)],
                'file_path' => $path,
                'crusade_year' => $year,
                'tags' => 'community,memory:'.$memoryId,
                'is_featured' => $index === 0,
                'is_speakers_highlight' => false,
                'is_published' => true,
                'sort_order' => $sortBase + $index,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($videos as $index => $path) {
            $path = trim((string) $path);
            if ($path === '') {
                continue;
            }
            $galleryItemIds[] = (int) DB::table('sdtg_gallery_items')->insertGetId([
                'album_id' => $albumId,
                'source_memory_id' => $memoryId,
                'media_type' => 'video',
                'title' => $name.' — SDTG Video Memory',
                'caption' => $caption,
                'category' => 'videos',
                'layout_size' => 'wide',
                'file_path' => $path,
                'video_type' => 'local',
                'video_src' => $path,
                'thumbnail_path' => $path,
                'crusade_year' => $year,
                'tags' => 'community,memory:'.$memoryId,
                'is_featured' => false,
                'is_speakers_highlight' => false,
                'is_published' => true,
                'sort_order' => $sortBase + 100 + $index,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($galleryItemIds === [] && $testimony !== '') {
            $galleryItemIds[] = (int) DB::table('sdtg_gallery_items')->insertGetId([
                'album_id' => $albumId,
                'source_memory_id' => $memoryId,
                'media_type' => 'photo',
                'title' => $name.' — SDTG Memory',
                'caption' => $caption,
                'category' => $category,
                'layout_size' => 'md',
                'external_url' => null,
                'file_path' => null,
                'crusade_year' => $year,
                'tags' => 'community,memory:'.$memoryId,
                'is_featured' => true,
                'is_speakers_highlight' => false,
                'is_published' => true,
                'sort_order' => $sortBase,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('sdtg_memory_submissions')->where('id', $memoryId)->update([
            'status' => 'featured',
            'published_to_gallery_at' => now(),
        ]);

        return ['gallery_item_ids' => $galleryItemIds, 'republished' => false];
    }

    public function unpublishMemoryFromGallery(int $memoryId): void
    {
        DB::table('sdtg_gallery_items')->where('source_memory_id', $memoryId)->update([
            'is_published' => false,
            'updated_at' => now(),
        ]);
        DB::table('sdtg_memory_submissions')->where('id', $memoryId)->update([
            'published_to_gallery_at' => null,
        ]);
    }

    /**
     * Public share-form submission from /sdgt/gallery.
     *
     * @param  array<string, mixed>  $input
     * @param  list<UploadedFile>  $photos
     * @param  list<UploadedFile>  $videos
     * @return array{id: int}
     */
    public function submitPublicMemory(array $input, array $photos = [], array $videos = [], ?string $ip = null): array
    {
        if (! Schema::hasTable('sdtg_memory_submissions')) {
            throw new InvalidArgumentException('Memory submissions are not available.');
        }

        $name = trim((string) ($input['full_name'] ?? $input['name'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $testimony = trim((string) ($input['testimony_text'] ?? $input['testimony'] ?? $input['message'] ?? ''));
        $edition = trim((string) ($input['edition'] ?? $input['shareEdition'] ?? ''));

        if ($name === '' || mb_strlen($name) < 2) {
            throw new InvalidArgumentException('Please enter your full name.');
        }
        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Please enter a valid email address.');
        }
        if ($testimony === '' && $photos === [] && $videos === []) {
            throw new InvalidArgumentException('Please share a testimony, photo, or video.');
        }

        $photoPaths = [];
        foreach (array_slice($photos, 0, 6) as $file) {
            if ($file instanceof UploadedFile) {
                $photoPaths[] = $this->storeMemoryFile($file, 'image');
            }
        }
        $videoPaths = [];
        foreach (array_slice($videos, 0, 3) as $file) {
            if ($file instanceof UploadedFile) {
                $videoPaths[] = $this->storeMemoryFile($file, 'video');
            }
        }

        $id = (int) DB::table('sdtg_memory_submissions')->insertGetId([
            'full_name' => $name,
            'email' => $email !== '' ? $email : 'anonymous@agikenebgu.local',
            'edition' => $edition !== '' ? $edition : null,
            'testimony_text' => $testimony !== '' ? $testimony : null,
            'photo_paths' => json_encode($photoPaths),
            'video_paths' => json_encode($videoPaths),
            'status' => 'pending',
            'ip_address' => $ip,
            'created_at' => now(),
        ]);

        return ['id' => $id];
    }

    private function ensureCommunityMemoriesAlbum(): int
    {
        $existing = DB::table('sdtg_gallery_albums')->where('slug', 'community-memories')->value('id');
        if ($existing) {
            return (int) $existing;
        }

        return (int) DB::table('sdtg_gallery_albums')->insertGetId([
            'title' => 'Community Memories',
            'slug' => 'community-memories',
            'description' => 'Stories and media shared by the SDTG family.',
            'crusade_year' => (int) date('Y'),
            'is_published' => true,
            'sort_order' => 9999,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function storeMemoryFile(UploadedFile $file, string $kind): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: ($kind === 'video' ? 'mp4' : 'jpg'));
        $allowed = $kind === 'video' ? ['mp4', 'webm', 'mov'] : ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (! in_array($ext, $allowed, true)) {
            throw new InvalidArgumentException('Unsupported '.$kind.' file type.');
        }

        $name = bin2hex(random_bytes(16)).'.'.$ext;
        $relative = 'uploads/sdtg-media/'.$name;
        $dir = public_path('site/uploads/sdtg-media');
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new InvalidArgumentException('Unable to store uploaded media.');
        }
        if (! $file->move($dir, $name)) {
            throw new InvalidArgumentException('Unable to save uploaded media.');
        }

        return $relative;
    }
}
