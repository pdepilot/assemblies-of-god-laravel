<?php

namespace App\Services\Sdtg;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class SdtgAnnouncementWriteService
{
    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function save(array $data): array
    {
        $id = (int) ($data['id'] ?? 0);
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new InvalidArgumentException('Announcement title is required.');
        }

        $payload = [
            'title' => $title,
            'excerpt' => (string) ($data['excerpt'] ?? ''),
            'body' => (string) ($data['body'] ?? ''),
            'category' => (string) ($data['category'] ?? 'Announcement'),
            'link_url' => trim((string) ($data['link_url'] ?? '')) ?: null,
            'is_published' => (bool) ($data['is_published'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'published_at' => ($data['published_at'] ?? null) ?: now(),
            'updated_at' => now(),
        ];

        if ($id > 0) {
            DB::table('sdtg_announcements')->where('id', $id)->update($payload);
        } else {
            $slug = Str::slug($title);
            DB::table('sdtg_announcements')->insert($payload + [
                'slug' => $slug,
                'created_at' => now(),
            ]);
            $id = (int) DB::getPdo()->lastInsertId();
        }

        $row = DB::table('sdtg_announcements')->where('id', $id)->first();

        return $row ? (array) $row : ['id' => $id];
    }
}
