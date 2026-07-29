<?php

namespace App\Services\Sdtg;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SdtgLivestreamReadService
{
    public function __construct(
        private readonly SdtgContentReadService $content = new SdtgContentReadService(),
    ) {}

    /** @return array<string, mixed> */
    public function getAdminStats(): array
    {
        $peakMeta = $this->resolvePeakMeta();
        $runtime = $this->runtimeState();

        return [
            'current_viewers' => (int) ($runtime['current_viewers'] ?? 0),
            'is_live' => ! empty($runtime['is_live']),
            'session_started_at' => $runtime['session_started_at'] ?? null,
            'session_name' => (string) ($runtime['active_session_name'] ?? ''),
            'peak_all_time' => (int) $peakMeta['peak_all_time'],
            'peak_year' => (int) $peakMeta['peak_year'],
            'peak_year_label' => (int) $peakMeta['peak_year_label'],
            'sessions' => $this->listSessions(12),
        ];
    }

    /** @return array<string, mixed> */
    public function getPlatformSettings(): array
    {
        return $this->readLivestreamGroup();
    }

    /** @return list<array<string, mixed>> */
    public function listSessions(int $limit = 10): array
    {
        if (! Schema::hasTable('sdtg_livestream_stats')) {
            return [];
        }

        $limit = max(1, min(50, $limit));

        return DB::table('sdtg_livestream_stats')
            ->orderByDesc('stat_date')
            ->orderByDesc('peak_viewers')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'session_name', 'peak_viewers', 'stat_date'])
            ->map(static fn ($row): array => [
                'id' => (int) $row->id,
                'session_name' => (string) $row->session_name,
                'peak_viewers' => (int) $row->peak_viewers,
                'stat_date' => (string) $row->stat_date,
            ])
            ->all();
    }

    /**
     * The livestream page content always merged with defaults, never null/empty.
     *
     * @return array<string, mixed>
     */
    public function getPageContent(): array
    {
        return $this->content->getSection('livestream_page');
    }

    /** Whether an admin has ever saved custom livestream page content. */
    public function hasSavedPageContent(): bool
    {
        return $this->content->getRawStored('livestream_page') !== null;
    }

    public function peakViewers(?int $year = null): int
    {
        if (! Schema::hasTable('sdtg_livestream_stats')) {
            return 0;
        }

        $query = DB::table('sdtg_livestream_stats');
        if ($year !== null && $year > 0) {
            $query->whereBetween('stat_date', [
                sprintf('%04d-01-01', $year),
                sprintf('%04d-12-31', $year),
            ]);
        }

        return (int) $query->max('peak_viewers');
    }

    /** @return array{peak_all_time: int, peak_year: int, peak_year_label: int} */
    private function resolvePeakMeta(): array
    {
        $allTime = $this->peakViewers();
        $topRow = Schema::hasTable('sdtg_livestream_stats')
            ? DB::table('sdtg_livestream_stats')
                ->orderByDesc('peak_viewers')
                ->orderByDesc('stat_date')
                ->first(['peak_viewers', 'stat_date'])
            : null;

        $peakYear = $topRow ? (int) substr((string) $topRow->stat_date, 0, 4) : 0;
        if ($peakYear < 1) {
            $peakYear = (int) now()->format('Y');
        }

        return [
            'peak_all_time' => $allTime,
            'peak_year' => $this->peakViewers($peakYear),
            'peak_year_label' => $peakYear,
        ];
    }

    /** @return array<string, mixed> */
    private function runtimeState(): array
    {
        $settings = $this->readLivestreamGroup();

        return [
            'is_live' => ! empty($settings['is_live']),
            'current_viewers' => max(0, (int) ($settings['current_viewers'] ?? 0)),
            'session_started_at' => $settings['session_started_at'] ?? null,
            'active_session_name' => (string) ($settings['active_session_name'] ?? ''),
        ];
    }

    /** @return array<string, mixed> */
    private function readLivestreamGroup(): array
    {
        $defaults = $this->defaults();
        if (! Schema::hasTable('platform_setting_groups')) {
            return $defaults;
        }

        $raw = DB::table('platform_setting_groups')->where('group_key', 'livestream')->value('settings');
        if (! is_string($raw) || $raw === '') {
            return $defaults;
        }

        try {
            $stored = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return $defaults;
        }

        return is_array($stored) ? array_replace_recursive($defaults, $stored) : $defaults;
    }

    /** @return array<string, mixed> */
    private function defaults(): array
    {
        return [
            'youtube_enabled' => true,
            'youtube_channel' => '',
            'facebook_enabled' => true,
            'facebook_page' => '',
            'vimeo_enabled' => false,
            'vimeo_url' => '',
            'zoom_enabled' => false,
            'zoom_url' => '',
            'audio_stream_url' => '',
            'default_platform' => 'youtube',
            'is_live' => false,
            'current_viewers' => 0,
            'session_started_at' => null,
            'active_session_name' => '',
        ];
    }
}
