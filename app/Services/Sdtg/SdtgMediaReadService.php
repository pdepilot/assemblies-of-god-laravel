<?php

namespace App\Services\Sdtg;

use App\Services\PublicSite\PublicAssetResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SdtgMediaReadService
{
    public const MEDIA_TYPES = ['image', 'video', 'audio'];

    public const STATUSES = ['draft', 'published', 'featured'];

    public function __construct(
        private readonly PublicAssetResolver $assets,
    ) {}

    /** @return array<string, int> */
    public function stats(): array
    {
        if (! Schema::hasTable('sdtg_media_assets')) {
            return [
                'total' => 0,
                'images' => 0,
                'videos' => 0,
                'audio' => 0,
                'folders' => 0,
                'published' => 0,
                'bytes' => 0,
            ];
        }

        return [
            'total' => (int) DB::table('sdtg_media_assets')->count(),
            'images' => (int) DB::table('sdtg_media_assets')->where('media_type', 'image')->count(),
            'videos' => (int) DB::table('sdtg_media_assets')->where('media_type', 'video')->count(),
            'audio' => (int) DB::table('sdtg_media_assets')->where('media_type', 'audio')->count(),
            'folders' => Schema::hasTable('sdtg_media_folders')
                ? (int) DB::table('sdtg_media_folders')->count()
                : 0,
            'published' => (int) DB::table('sdtg_media_assets')->where('status', 'published')->count(),
            'bytes' => (int) DB::table('sdtg_media_assets')->sum('file_size'),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function listFolders(): array
    {
        if (! Schema::hasTable('sdtg_media_folders')) {
            return [];
        }

        return DB::table('sdtg_media_folders')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function ($row) {
                $data = (array) $row;
                $data['asset_count'] = (int) DB::table('sdtg_media_assets')
                    ->where('folder_id', $row->id)
                    ->count();

                return $data;
            })
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function getFolder(int $id): ?array
    {
        $row = DB::table('sdtg_media_folders')->where('id', $id)->first();

        return $row ? (array) $row : null;
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int, total_bytes: int}
     */
    public function listAssets(
        string $query = '',
        int $folderId = 0,
        string $mediaType = '',
        int $year = 0,
        string $status = '',
        int $page = 1,
        int $perPage = 24,
    ): array {
        $page = max(1, $page);
        $perPage = max(1, min(48, $perPage));

        if (! Schema::hasTable('sdtg_media_assets')) {
            return ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'per_page' => $perPage, 'total_bytes' => 0];
        }

        $builder = DB::table('sdtg_media_assets as a')
            ->leftJoin('sdtg_media_folders as f', 'f.id', '=', 'a.folder_id')
            ->select('a.*', 'f.name as folder_name');

        if ($query !== '') {
            $builder->where(function ($q) use ($query) {
                $q->where('a.title', 'like', '%'.$query.'%')
                    ->orWhere('a.description', 'like', '%'.$query.'%')
                    ->orWhere('a.tags', 'like', '%'.$query.'%')
                    ->orWhere('a.file_path', 'like', '%'.$query.'%');
            });
        }
        if ($folderId > 0) {
            $builder->where('a.folder_id', $folderId);
        }
        if ($mediaType !== '' && in_array($mediaType, self::MEDIA_TYPES, true)) {
            $builder->where('a.media_type', $mediaType);
        }
        if ($year > 0) {
            $builder->where('a.crusade_year', $year);
        }
        if ($status !== '' && in_array($status, self::STATUSES, true)) {
            $builder->where('a.status', $status);
        }

        $total = (int) (clone $builder)->count();
        $totalBytes = (int) (clone $builder)->sum('a.file_size');
        $items = $builder->orderBy('a.sort_order')
            ->orderByDesc('a.id')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => $this->formatAsset((array) $row))
            ->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'per_page' => $perPage,
            'total_bytes' => $totalBytes,
        ];
    }

    /** @return array<string, mixed>|null */
    public function getAsset(int $id): ?array
    {
        $row = DB::table('sdtg_media_assets as a')
            ->leftJoin('sdtg_media_folders as f', 'f.id', '=', 'a.folder_id')
            ->select('a.*', 'f.name as folder_name')
            ->where('a.id', $id)
            ->first();

        return $row ? $this->formatAsset((array) $row) : null;
    }

    /** @return list<int> */
    public function listYears(): array
    {
        if (! Schema::hasTable('sdtg_media_assets')) {
            return [];
        }

        return DB::table('sdtg_media_assets')
            ->where('crusade_year', '>', 0)
            ->distinct()
            ->orderByDesc('crusade_year')
            ->pluck('crusade_year')
            ->map(fn ($y) => (int) $y)
            ->all();
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
    private function formatAsset(array $row): array
    {
        $filePath = trim((string) ($row['external_url'] ?? '')) !== ''
            ? (string) $row['external_url']
            : (string) ($row['file_path'] ?? '');
        $thumbPath = trim((string) ($row['thumbnail_path'] ?? '')) !== ''
            ? (string) $row['thumbnail_path']
            : $filePath;

        $row['file_url'] = $this->mediaUrl($filePath !== '' ? $filePath : null);
        $row['thumbnail_url'] = $this->mediaUrl($thumbPath !== '' ? $thumbPath : null) ?? $row['file_url'];
        $row['file_size_label'] = $this->formatBytes((int) ($row['file_size'] ?? 0));

        return $row;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '—';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $value = (float) $bytes;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return round($value, $i === 0 ? 0 : 1).' '.$units[$i];
    }
}
