<?php

namespace App\Services\Sdtg;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class SdtgSpeakerWriteService
{
    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function save(array $data): array
    {
        $id = (int) ($data['id'] ?? 0);
        $fullName = trim((string) ($data['full_name'] ?? ''));
        if ($fullName === '') {
            throw new InvalidArgumentException('Speaker name is required.');
        }

        $payload = [
            'full_name' => $fullName,
            'ministry' => trim((string) ($data['ministry'] ?? '')),
            'country' => trim((string) ($data['country'] ?? 'Nigeria')),
            'crusade_year' => (int) ($data['crusade_year'] ?? date('Y')),
            'speaker_type' => (string) ($data['speaker_type'] ?? 'upcoming'),
            'status' => (string) ($data['status'] ?? 'pending'),
            'bio' => (string) ($data['bio'] ?? ''),
            'topic' => trim((string) ($data['topic'] ?? '')) ?: null,
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'is_published' => (bool) ($data['is_published'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'updated_at' => now(),
        ];

        if ($id > 0) {
            DB::table('sdtg_speakers')->where('id', $id)->update($payload);
        } else {
            $slug = $this->slugify((string) ($data['slug'] ?? $fullName));
            DB::table('sdtg_speakers')->insert($payload + [
                'slug' => $slug,
                'created_at' => now(),
            ]);
            $id = (int) DB::getPdo()->lastInsertId();
        }

        $row = DB::table('sdtg_speakers')->where('id', $id)->first();

        return $row ? (array) $row : ['id' => $id];
    }

    private function slugify(string $value): string
    {
        $slug = Str::slug($value);
        if ($slug === '') {
            $slug = 'speaker';
        }

        $base = $slug;
        $suffix = 1;
        while (DB::table('sdtg_speakers')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
