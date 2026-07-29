<?php

namespace App\Services\Sdtg;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class SdtgEditionsWriteService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function save(array $data): array
    {
        if (! Schema::hasTable('sdtg_crusade_editions')) {
            throw new InvalidArgumentException('Crusade editions table is not available.');
        }

        $id = (int) ($data['id'] ?? 0);
        $year = (int) ($data['crusade_year'] ?? 0);
        $theme = trim((string) ($data['theme'] ?? ''));
        $speakers = trim((string) ($data['speakers_summary'] ?? ''));
        $highlights = trim((string) ($data['highlights'] ?? ''));

        if ($year < 2000 || $year > 2100) {
            throw new InvalidArgumentException('A valid crusade year is required.');
        }
        if ($theme === '') {
            throw new InvalidArgumentException('Edition theme is required.');
        }
        if ($speakers === '') {
            throw new InvalidArgumentException('Speakers summary is required.');
        }

        $exists = DB::table('sdtg_crusade_editions')
            ->where('crusade_year', $year)
            ->when($id > 0, fn ($q) => $q->where('id', '<>', $id))
            ->exists();
        if ($exists) {
            throw new InvalidArgumentException('An edition for that year already exists.');
        }

        $payload = [
            'crusade_year' => $year,
            'theme' => $theme,
            'speakers_summary' => $speakers,
            'highlights' => $highlights,
            'event_start_at' => $this->normalizeDateTime($data['event_start_at'] ?? null),
            'event_end_at' => $this->normalizeDateTime($data['event_end_at'] ?? null),
            'venue' => trim((string) ($data['venue'] ?? 'Owerri, Nigeria')) ?: 'Owerri, Nigeria',
            'is_next_crusade' => ! empty($data['is_next_crusade']),
            'sort_order' => max(0, (int) ($data['sort_order'] ?? 0)),
            'is_published' => ! empty($data['is_published']),
            'updated_at' => now(),
        ];

        if ($id > 0) {
            DB::table('sdtg_crusade_editions')->where('id', $id)->update($payload);
        } else {
            $id = (int) DB::table('sdtg_crusade_editions')->insertGetId($payload + ['created_at' => now()]);
        }

        if (! empty($data['is_next_crusade'])) {
            DB::table('sdtg_crusade_editions')->where('id', '<>', $id)->update(['is_next_crusade' => false]);
        }

        return (array) DB::table('sdtg_crusade_editions')->where('id', $id)->first();
    }

    public function delete(int $id): void
    {
        DB::table('sdtg_crusade_editions')->where('id', $id)->delete();
    }

    private function normalizeDateTime(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $ts = strtotime($value);

        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }
}
