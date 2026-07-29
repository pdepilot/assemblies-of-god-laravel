<?php

namespace App\Services\Sermons;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class SermonWriteService
{
    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function save(array $data, int $adminId): array
    {
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
        if (! in_array($sermonType, SermonReadService::TYPES, true)) {
            $sermonType = 'audio';
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
            'youtube_url' => trim((string) ($data['youtube_url'] ?? '')),
            'audio_stream_url' => trim((string) ($data['audio_stream_url'] ?? '')),
            'updated_by' => $adminId > 0 ? $adminId : null,
            'updated_at' => now(),
        ];

        if ($id > 0) {
            $existing = DB::table('sermons')->where('id', $id)->first();
            if (! $existing) {
                throw new InvalidArgumentException('Sermon not found.');
            }
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
