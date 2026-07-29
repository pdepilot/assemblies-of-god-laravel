<?php

namespace App\Services\Sdtg;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class SdtgLivestreamWriteService
{
    public function __construct(
        private readonly SdtgLivestreamReadService $read,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function setLiveState(bool $isLive, int $viewers, string $sessionName, int $adminId): array
    {
        $sessionName = trim($sessionName) !== '' ? trim($sessionName) : 'SDTG Livestream';
        $viewers = max(0, $viewers);

        $patch = [
            'is_live' => $isLive,
            'current_viewers' => $isLive ? $viewers : 0,
            'active_session_name' => $sessionName,
            'session_started_at' => $isLive ? now()->toIso8601String() : null,
        ];

        if ($isLive && $viewers > 0) {
            $this->upsertPeak($viewers, $sessionName);
        }

        $this->saveLivestreamGroup($patch, $adminId);

        return $this->read->getAdminStats();
    }

    /**
     * @return array<string, mixed>
     */
    public function updateViewers(int $viewers, int $adminId): array
    {
        $stats = $this->read->getAdminStats();
        if (empty($stats['is_live'])) {
            throw new InvalidArgumentException('Stream is not live.');
        }

        $viewers = max(0, $viewers);
        $sessionName = trim((string) ($stats['session_name'] ?? 'SDTG Livestream'));
        if ($sessionName === '') {
            $sessionName = 'SDTG Livestream';
        }

        if ($viewers > 0) {
            $this->upsertPeak($viewers, $sessionName);
        }

        $this->saveLivestreamGroup(['current_viewers' => $viewers], $adminId);

        return $this->read->getAdminStats();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function savePlatformSettings(array $payload, int $adminId): array
    {
        $patch = [
            'youtube_enabled' => ! empty($payload['youtube_enabled']),
            'youtube_channel' => trim((string) ($payload['youtube_channel'] ?? '')),
            'facebook_enabled' => ! empty($payload['facebook_enabled']),
            'facebook_page' => trim((string) ($payload['facebook_page'] ?? '')),
            'vimeo_enabled' => ! empty($payload['vimeo_enabled']),
            'vimeo_url' => trim((string) ($payload['vimeo_url'] ?? '')),
            'audio_stream_url' => trim((string) ($payload['audio_stream_url'] ?? '')),
            'default_platform' => in_array(($payload['default_platform'] ?? ''), ['youtube', 'facebook', 'vimeo', 'custom'], true)
                ? (string) $payload['default_platform']
                : 'youtube',
        ];

        return $this->saveLivestreamGroup($patch, $adminId);
    }

    public function upsertPeak(int $viewerCount, string $sessionName): int
    {
        if (! Schema::hasTable('sdtg_livestream_stats')) {
            return $viewerCount;
        }

        $viewerCount = max(0, $viewerCount);
        $sessionName = trim($sessionName) !== '' ? trim($sessionName) : 'SDTG Livestream';
        $today = now()->toDateString();

        $row = DB::table('sdtg_livestream_stats')
            ->where('stat_date', $today)
            ->orderByDesc('id')
            ->first();

        if ($row) {
            $newPeak = max((int) $row->peak_viewers, $viewerCount);
            DB::table('sdtg_livestream_stats')->where('id', $row->id)->update([
                'peak_viewers' => $newPeak,
                'session_name' => $sessionName,
            ]);

            return $newPeak;
        }

        DB::table('sdtg_livestream_stats')->insert([
            'session_name' => $sessionName,
            'peak_viewers' => $viewerCount,
            'stat_date' => $today,
            'created_at' => now(),
        ]);

        return $viewerCount;
    }

    /**
     * @param  array<string, mixed>  $patch
     * @return array<string, mixed>
     */
    private function saveLivestreamGroup(array $patch, int $adminId): array
    {
        if (! Schema::hasTable('platform_setting_groups')) {
            throw new InvalidArgumentException('Platform settings storage is unavailable.');
        }

        $merged = array_replace_recursive($this->read->getPlatformSettings(), $patch);
        $json = json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new InvalidArgumentException('Unable to encode livestream settings.');
        }

        DB::table('platform_setting_groups')->updateOrInsert(
            ['group_key' => 'livestream'],
            [
                'settings' => $json,
                'updated_by' => $adminId,
                'updated_at' => now(),
            ]
        );

        return $merged;
    }
}
