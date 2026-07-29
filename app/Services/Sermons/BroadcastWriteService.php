<?php

namespace App\Services\Sermons;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class BroadcastWriteService
{
    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function save(array $data, int $adminId): array
    {
        $id = (int) ($data['id'] ?? 0);
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new InvalidArgumentException('Broadcast title is required.');
        }

        $status = (string) ($data['status'] ?? 'scheduled');
        if (! in_array($status, BroadcastReadService::STATUSES, true)) {
            $status = 'scheduled';
        }

        $payload = [
            'title' => $title,
            'description' => (string) ($data['description'] ?? ''),
            'minister_name' => trim((string) ($data['minister_name'] ?? '')),
            'broadcast_type' => (string) ($data['broadcast_type'] ?? 'video'),
            'stream_date' => (string) ($data['stream_date'] ?? now()->toDateString()),
            'start_time' => (string) ($data['start_time'] ?? '09:00:00'),
            'end_time' => ($data['end_time'] ?? null) ?: null,
            'platform' => (string) ($data['platform'] ?? 'youtube'),
            'embed_url' => trim((string) ($data['embed_url'] ?? '')),
            'stream_url' => trim((string) ($data['stream_url'] ?? '')),
            'audio_stream_url' => trim((string) ($data['audio_stream_url'] ?? '')),
            'video_stream_url' => trim((string) ($data['video_stream_url'] ?? '')),
            'status' => $status,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'updated_at' => now(),
        ];

        if ($id > 0) {
            $existing = DB::table('live_streams')->where('id', $id)->first();
            if (! $existing) {
                throw new InvalidArgumentException('Broadcast not found.');
            }
            DB::table('live_streams')->where('id', $id)->update($payload);
        } else {
            $slug = $this->slugify((string) ($data['slug'] ?? $title));
            DB::table('live_streams')->insert($payload + [
                'stream_code' => $this->generateStreamCode(),
                'slug' => $slug,
                'created_by' => $adminId > 0 ? $adminId : null,
                'created_at' => now(),
            ]);
            $id = (int) DB::getPdo()->lastInsertId();
        }

        $row = DB::table('live_streams')->where('id', $id)->first();

        return $row ? (array) $row : ['id' => $id];
    }

    public function startStream(int $id): void
    {
        DB::table('live_streams')->where('id', $id)->update([
            'status' => 'live',
            'is_active' => true,
            'started_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function stopStream(int $id): void
    {
        DB::table('live_streams')->where('id', $id)->update([
            'status' => 'ended',
            'is_active' => false,
            'ended_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function generateStreamCode(): string
    {
        do {
            $code = 'LVS-'.strtoupper(Str::random(8));
        } while (DB::table('live_streams')->where('stream_code', $code)->exists());

        return $code;
    }

    private function slugify(string $value): string
    {
        $slug = Str::slug($value);
        if ($slug === '') {
            $slug = 'stream';
        }

        $base = $slug;
        $suffix = 1;
        while (DB::table('live_streams')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
