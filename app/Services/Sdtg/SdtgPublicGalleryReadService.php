<?php

namespace App\Services\Sdtg;

use App\Services\Settings\PlatformSettingsReadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Public SDTG gallery payload with Laravel-resolved media URLs.
 * Powers /sdgt/gallery bootstrap + /api/sdtg-gallery.
 */
final class SdtgPublicGalleryReadService
{
    public function __construct(
        private readonly SdtgGalleryReadService $gallery,
        private readonly PlatformSettingsReadService $platformSettings,
    ) {}

    /** @return array<string, mixed> */
    public function bootstrap(): array
    {
        $photos = [];
        $videos = [];
        $featured = [];
        $highlights = [];
        $photoPreviewUrls = [];

        if (Schema::hasTable('sdtg_gallery_items')) {
            $rows = DB::table('sdtg_gallery_items as gi')
                ->leftJoin('sdtg_gallery_albums as ga', 'ga.id', '=', 'gi.album_id')
                ->select('gi.*', 'ga.title as album_title')
                ->where('gi.is_published', 1)
                ->orderBy('gi.sort_order')
                ->orderByDesc('gi.id')
                ->get();

            foreach ($rows as $row) {
                $public = $this->toPublicItem((array) $row);
                if ($public['media_type'] === 'video') {
                    $videos[] = $public;
                } else {
                    $photos[] = $public;
                    if ($public['image'] || $public['thumbnail']) {
                        $photoPreviewUrls[] = $public['thumbnail'] ?: $public['image'];
                    }
                }

                if ($public['is_featured']) {
                    $featured[] = $public;
                }

                if (! empty($row->is_speakers_highlight) && ($public['media_type'] ?? '') === 'photo') {
                    $highlights[] = [
                        'id' => $public['id'],
                        'title' => $public['title'],
                        'tag' => ucfirst($public['category']),
                        'image' => $public['image'],
                        'link' => 'gallery#photos',
                    ];
                }
            }
        }

        $albums = array_values(array_filter(
            $this->gallery->listAlbums(),
            static fn (array $album): bool => ! empty($album['is_published']),
        ));

        usort($albums, static fn (array $a, array $b): int => ((int) ($b['crusade_year'] ?? 0)) <=> ((int) ($a['crusade_year'] ?? 0)));

        $albumPayload = array_map(static function (array $album): array {
            return [
                'id' => (int) $album['id'],
                'title' => (string) $album['title'],
                'slug' => (string) ($album['slug'] ?? ''),
                'description' => $album['description'] ?? null,
                'crusade_year' => (int) ($album['crusade_year'] ?? 0),
                'cover' => $album['cover_url'] ?? null,
                'cover_url' => $album['cover_url'] ?? null,
                'item_count' => (int) ($album['item_count'] ?? 0),
                'is_published' => ! empty($album['is_published']),
            ];
        }, $albums);

        $timeline = $this->timeline();
        $editions = $this->editionOptions();
        $editionYears = count($editions) > 0
            ? count(array_filter($editions, static fn (array $e): bool => ($e['value'] ?? '') !== 'other'))
            : 0;
        $itemYears = collect(array_merge($photos, $videos))->pluck('year')->filter()->unique()->count();

        return [
            'photos' => $photos,
            'videos' => $videos,
            'featured' => $featured,
            'highlights' => array_slice($highlights, 0, 8),
            'albums' => $albumPayload,
            'timeline' => $timeline,
            'stats' => [
                'photos' => count($photos),
                'videos' => count($videos),
                'years' => max((int) $itemYears, $editionYears),
                'albums' => count($albumPayload),
            ],
            'categories' => SdtgGalleryReadService::CATEGORIES,
            'editions' => $editions,
            'social' => $this->socialWall($photoPreviewUrls),
            'community' => $this->communityStories(),
            'csrf_token' => csrf_token(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function toPublicItem(array $data): array
    {
        $imagePath = trim((string) ($data['external_url'] ?? '')) !== ''
            ? (string) $data['external_url']
            : (string) ($data['file_path'] ?? '');
        $thumbPath = trim((string) ($data['thumbnail_path'] ?? '')) !== ''
            ? (string) $data['thumbnail_path']
            : $imagePath;

        $imageUrl = $this->gallery->mediaUrl($imagePath !== '' ? $imagePath : null);
        $thumbUrl = $this->gallery->mediaUrl($thumbPath !== '' ? $thumbPath : null) ?? $imageUrl;

        $videoSrc = (string) ($data['video_src'] ?? '');
        if (($data['video_type'] ?? '') === 'local' && $videoSrc !== '') {
            $videoSrc = $this->gallery->mediaUrl($videoSrc) ?? $videoSrc;
        }

        return [
            'id' => (int) $data['id'],
            'media_type' => (string) ($data['media_type'] ?? 'photo'),
            'title' => (string) ($data['title'] ?? ''),
            'caption' => (string) ($data['caption'] ?? ''),
            'category' => (string) ($data['category'] ?? 'highlights'),
            'layout_size' => (string) ($data['layout_size'] ?? 'md'),
            'image' => $imageUrl,
            'thumbnail' => $thumbUrl,
            'video_type' => $data['video_type'] ?? null,
            'video_src' => $videoSrc,
            'year' => (string) ($data['crusade_year'] ?? ''),
            'year_label' => 'SDTG '.($data['crusade_year'] ?? ''),
            'album_id' => isset($data['album_id']) ? (int) $data['album_id'] : null,
            'album_title' => $data['album_title'] ?? null,
            'is_featured' => ! empty($data['is_featured']),
            'from_community' => ! empty($data['source_memory_id']),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function timeline(): array
    {
        if (! Schema::hasTable('sdtg_crusade_editions')) {
            return [];
        }

        $editions = DB::table('sdtg_crusade_editions')
            ->where('is_published', 1)
            ->orderBy('crusade_year')
            ->orderBy('sort_order')
            ->get();

        $timeline = [];
        foreach ($editions as $edition) {
            $year = (int) $edition->crusade_year;
            $cover = null;
            if (Schema::hasTable('sdtg_gallery_items')) {
                $row = DB::table('sdtg_gallery_items')
                    ->where('is_published', 1)
                    ->where('media_type', 'photo')
                    ->where('crusade_year', $year)
                    ->orderByDesc('is_featured')
                    ->orderBy('sort_order')
                    ->first(['file_path', 'external_url', 'thumbnail_path']);
                if ($row) {
                    $path = trim((string) ($row->external_url ?: $row->file_path ?: $row->thumbnail_path));
                    $cover = $this->gallery->mediaUrl($path !== '' ? $path : null);
                }
            }

            $timeline[] = [
                'year' => (string) $year,
                'theme' => (string) $edition->theme,
                'highlights' => (string) $edition->highlights,
                'image' => $cover,
            ];
        }

        return $timeline;
    }

    /** @return list<array{value: string, label: string}> */
    private function editionOptions(): array
    {
        $options = [];
        if (Schema::hasTable('sdtg_crusade_editions')) {
            $rows = DB::table('sdtg_crusade_editions')
                ->where('is_published', 1)
                ->orderByDesc('crusade_year')
                ->get(['crusade_year', 'theme']);
            foreach ($rows as $row) {
                $year = (string) $row->crusade_year;
                $theme = trim((string) $row->theme);
                $options[] = [
                    'value' => $year,
                    'label' => $theme !== '' ? "SDTG {$year} — {$theme}" : "SDTG {$year}",
                ];
            }
        }

        $options[] = ['value' => 'other', 'label' => 'Other / Multiple Editions'];

        return $options;
    }

    /**
     * @param  list<string|null>  $photos
     * @return array{cards: list<array<string, mixed>>, photos: list<string>}
     */
    private function socialWall(array $photos): array
    {
        $photos = array_values(array_filter($photos));
        $church = $this->platformSettings->getGroup('church');
        $social = [
            'facebook' => (string) ($church['social_facebook'] ?? ''),
            'instagram' => (string) ($church['social_instagram'] ?? ''),
            'youtube' => (string) ($church['social_youtube'] ?? ''),
            'twitter' => (string) ($church['social_twitter'] ?? $church['social_x'] ?? ''),
        ];

        $platforms = [
            ['key' => 'facebook', 'label' => 'Facebook', 'icon' => 'fab fa-facebook-f', 'class' => 'social-card--fb'],
            ['key' => 'instagram', 'label' => 'Instagram', 'icon' => 'fab fa-instagram', 'class' => 'social-card--ig'],
            ['key' => 'youtube', 'label' => 'YouTube', 'icon' => 'fab fa-youtube', 'class' => 'social-card--yt'],
            ['key' => 'twitter', 'label' => 'X / Twitter', 'icon' => 'fab fa-twitter', 'class' => 'social-card--tt'],
        ];

        $fallback = asset('site/sdgt/img/lifted_hands.jpeg');
        $cards = [];
        foreach ($platforms as $index => $platform) {
            $href = trim($social[$platform['key']] ?? '');
            if ($href === '' || $href === '#') {
                $href = url('/sdgt/gallery').'#photos';
            }
            $preview = [];
            for ($i = 0; $i < 3; $i++) {
                $preview[] = $photos[($index * 3 + $i) % max(count($photos), 1)] ?? $fallback;
            }
            $cards[] = [
                'platform' => $platform['label'],
                'icon' => $platform['icon'],
                'class' => $platform['class'],
                'url' => $href,
                'preview' => $preview,
                'delay' => $index * 100,
            ];
        }

        return ['cards' => $cards, 'photos' => array_slice($photos, 0, 12)];
    }

    /** @return list<array<string, mixed>> */
    private function communityStories(): array
    {
        if (! Schema::hasTable('sdtg_memory_submissions')) {
            return [];
        }

        $rows = DB::table('sdtg_memory_submissions')
            ->where('status', 'featured')
            ->orderByDesc('published_to_gallery_at')
            ->orderByDesc('id')
            ->limit(24)
            ->get();

        $stories = [];
        foreach ($rows as $row) {
            $photos = json_decode((string) ($row->photo_paths ?? '[]'), true);
            $videos = json_decode((string) ($row->video_paths ?? '[]'), true);
            $photos = is_array($photos) ? $photos : [];
            $videos = is_array($videos) ? $videos : [];
            $edition = trim((string) ($row->edition ?? ''));
            $yearLabel = $edition !== '' && $edition !== 'other'
                ? (preg_match('/^\d{4}$/', $edition) ? 'SDTG '.$edition : $edition)
                : '';

            $stories[] = [
                'id' => (int) $row->id,
                'name' => (string) $row->full_name,
                'testimony' => (string) ($row->testimony_text ?? ''),
                'edition' => $edition,
                'year_label' => $yearLabel,
                'photo_urls' => array_values(array_filter(array_map(
                    fn ($p) => $this->gallery->mediaUrl((string) $p),
                    $photos,
                ))),
                'video_urls' => array_values(array_filter(array_map(
                    fn ($p) => $this->gallery->mediaUrl((string) $p),
                    $videos,
                ))),
                'published_at' => (string) ($row->published_to_gallery_at ?? ''),
            ];
        }

        return $stories;
    }
}
