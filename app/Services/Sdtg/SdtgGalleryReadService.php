<?php

namespace App\Services\Sdtg;

use App\Services\PublicSite\PublicAssetResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SdtgGalleryReadService
{
    public const CATEGORIES = [
        'worship', 'ministers', 'choir', 'congregation', 'prayer', 'healing', 'highlights', 'behind', 'videos',
    ];

    public const LAYOUTS = ['sm', 'md', 'lg', 'wide'];

    public const MEDIA_TYPES = ['photo', 'video'];

    public function __construct(
        private readonly PublicAssetResolver $assets,
    ) {}

    /**
     * @return array{
     *     total_items: int,
     *     photos: int,
     *     videos: int,
     *     albums: int,
     *     published: int,
     *     unpublished: int,
     *     highlights: int
     * }
     */
    public function stats(): array
    {
        if (! Schema::hasTable('sdtg_gallery_items')) {
            return [
                'total_items' => 0,
                'photos' => 0,
                'videos' => 0,
                'albums' => 0,
                'published' => 0,
                'unpublished' => 0,
                'highlights' => 0,
                'featured' => 0,
            ];
        }

        $total = (int) DB::table('sdtg_gallery_items')->count();
        $published = (int) DB::table('sdtg_gallery_items')->where('is_published', 1)->count();

        return [
            'total_items' => $total,
            'photos' => (int) DB::table('sdtg_gallery_items')->where('media_type', 'photo')->count(),
            'videos' => (int) DB::table('sdtg_gallery_items')->where('media_type', 'video')->count(),
            'albums' => Schema::hasTable('sdtg_gallery_albums')
                ? (int) DB::table('sdtg_gallery_albums')->count()
                : 0,
            'published' => $published,
            'unpublished' => max(0, $total - $published),
            'highlights' => (int) DB::table('sdtg_gallery_items')->where('is_speakers_highlight', 1)->count(),
            'featured' => (int) DB::table('sdtg_gallery_items')->where('is_featured', 1)->count(),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function listAlbums(?int $year = null): array
    {
        if (! Schema::hasTable('sdtg_gallery_albums')) {
            return [];
        }

        $query = DB::table('sdtg_gallery_albums')->orderBy('sort_order')->orderBy('title');

        if ($year !== null && $year > 0) {
            $query->where('crusade_year', $year);
        }

        return $query->get()->map(function ($row) {
            return $this->formatAlbum((array) $row);
        })->all();
    }

    /** @return array<string, mixed>|null */
    public function getAlbum(int $id): ?array
    {
        $row = DB::table('sdtg_gallery_albums')->where('id', $id)->first();

        return $row ? $this->formatAlbum((array) $row) : null;
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public function listItems(
        string $query,
        int $albumId,
        string $category,
        int $year,
        string $mediaType,
        int $page,
        int $perPage = 24,
        bool $featuredOnly = false,
    ): array {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        if (! Schema::hasTable('sdtg_gallery_items')) {
            return ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'per_page' => $perPage];
        }

        $builder = DB::table('sdtg_gallery_items as gi')
            ->leftJoin('sdtg_gallery_albums as ga', 'ga.id', '=', 'gi.album_id')
            ->select('gi.*', 'ga.title as album_title');

        if ($query !== '') {
            $builder->where(function ($q) use ($query) {
                $q->where('gi.title', 'like', '%'.$query.'%')
                    ->orWhere('gi.caption', 'like', '%'.$query.'%')
                    ->orWhere('gi.tags', 'like', '%'.$query.'%');
            });
        }
        if ($albumId > 0) {
            $builder->where('gi.album_id', $albumId);
        }
        if ($category !== '' && in_array($category, self::CATEGORIES, true)) {
            $builder->where('gi.category', $category);
        }
        if ($year > 0) {
            $builder->where('gi.crusade_year', $year);
        }
        if ($mediaType !== '' && in_array($mediaType, self::MEDIA_TYPES, true)) {
            $builder->where('gi.media_type', $mediaType);
        }
        if ($featuredOnly) {
            $builder->where('gi.is_featured', 1);
        }

        $total = (int) (clone $builder)->count();
        $items = $builder->orderBy('gi.sort_order')
            ->orderByDesc('gi.id')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => $this->formatItem((array) $row))
            ->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'per_page' => $perPage,
        ];
    }

    /** @return array<string, mixed>|null */
    public function getItem(int $id): ?array
    {
        $row = DB::table('sdtg_gallery_items as gi')
            ->leftJoin('sdtg_gallery_albums as ga', 'ga.id', '=', 'gi.album_id')
            ->select('gi.*', 'ga.title as album_title')
            ->where('gi.id', $id)
            ->first();

        return $row ? $this->formatItem((array) $row) : null;
    }

    public function mediaUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (preg_match('#^(https?:)?//#i', $path) === 1) {
            return $path;
        }

        $url = $this->assets->uploadUrl($path);

        return $url !== '' ? $url : null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function formatAlbum(array $row): array
    {
        $row['item_count'] = isset($row['item_count'])
            ? (int) $row['item_count']
            : (int) DB::table('sdtg_gallery_items')->where('album_id', $row['id'] ?? 0)->count();
        $row['cover_url'] = $this->mediaUrl($row['cover_path'] ?? null);

        return $row;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function formatItem(array $row): array
    {
        $imagePath = trim((string) ($row['external_url'] ?? '')) !== ''
            ? (string) $row['external_url']
            : (string) ($row['file_path'] ?? '');
        $thumbPath = trim((string) ($row['thumbnail_path'] ?? '')) !== ''
            ? (string) $row['thumbnail_path']
            : $imagePath;

        $row['image_url'] = $this->mediaUrl($imagePath !== '' ? $imagePath : null);
        $row['thumbnail_url'] = $this->mediaUrl($thumbPath !== '' ? $thumbPath : null) ?? $row['image_url'];

        return $row;
    }
}
