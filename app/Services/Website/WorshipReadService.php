<?php

namespace App\Services\Website;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class WorshipReadService
{
    public const SECTION_KEY = 'homepage_worship';

    public const SLOT_COUNT = 6;

    public const DEFAULT_MAP_QUERY = '11 Archdeacon Dennis Street, Ikenegbu, Owerri, Imo, Nigeria';

    /** @return list<array<string, mixed>> */
    public function programs(): array
    {
        $stored = $this->storedPayload()['programs'];
        $visible = array_values(array_filter(
            $stored,
            static fn (array $item): bool => trim((string) ($item['title'] ?? '')) !== ''
                || trim((string) ($item['day'] ?? '')) !== ''
        ));

        return $visible !== [] ? $visible : $this->defaultPrograms();
    }

    /** @return list<array<string, mixed>> */
    public function editorSlots(): array
    {
        $slots = $this->storedPayload()['programs'];
        if ($slots === []) {
            $slots = $this->defaultPrograms();
        }

        while (count($slots) < self::SLOT_COUNT) {
            $slots[] = $this->emptyProgram();
        }

        return array_slice($slots, 0, self::SLOT_COUNT);
    }

    /** @return array{map_query: string, embed_src: string} */
    public function location(): array
    {
        $query = $this->normalizeLocation($this->storedPayload()['location'] ?? [])['map_query'];

        return [
            'map_query' => $query,
            'embed_src' => 'https://maps.google.com/maps?q='.rawurlencode($query).'&z=16&ie=UTF8&iwloc=&output=embed',
        ];
    }

    /** @param array<string, mixed> $item @return array{map_query: string} */
    public function normalizeLocation(array $item): array
    {
        $query = trim((string) ($item['map_query'] ?? ''));

        return [
            'map_query' => $query !== '' ? $query : self::DEFAULT_MAP_QUERY,
        ];
    }

    /** @return array{programs: list<array<string, mixed>>, location: array<string, mixed>} */
    public function storedPayload(): array
    {
        if (! Schema::hasTable('ag_site_content')) {
            return ['programs' => [], 'location' => ['map_query' => self::DEFAULT_MAP_QUERY]];
        }

        $row = DB::table('ag_site_content')->where('section_key', self::SECTION_KEY)->first();
        if ($row === null) {
            return ['programs' => [], 'location' => ['map_query' => self::DEFAULT_MAP_QUERY]];
        }

        $decoded = json_decode((string) ($row->content_json ?? '{}'), true);
        $programs = is_array($decoded['programs'] ?? null) ? $decoded['programs'] : [];

        $normalized = [];
        foreach ($programs as $item) {
            if (! is_array($item)) {
                continue;
            }
            $normalized[] = $this->normalize($item);
        }

        return [
            'programs' => $normalized,
            'location' => $this->normalizeLocation(is_array($decoded['location'] ?? null) ? $decoded['location'] : []),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function defaultPrograms(): array
    {
        return [
            [
                'day' => 'Every Sunday',
                'icon' => 'fa-calendar-day',
                'title' => 'Sunday Worship Services',
                'time_primary' => '8:00 AM',
                'note_primary' => 'First Service — Prayer & Praise',
                'time_secondary' => '10:30 AM',
                'note_secondary' => 'Main Service — Word & Communion',
                'body' => "Children's church available during both services.",
                'cta_label' => '',
                'cta_url' => '',
            ],
            [
                'day' => 'Every Wednesday',
                'icon' => 'fa-bible',
                'title' => 'Midweek Bible Study',
                'time_primary' => '6:00 PM',
                'note_primary' => '',
                'time_secondary' => '',
                'note_secondary' => '',
                'body' => 'In-depth teaching, small-group discussion, and fellowship for adults and youth.',
                'cta_label' => 'Bible Study Resources',
                'cta_url' => 'blog',
            ],
            [
                'day' => 'Every Friday',
                'icon' => 'fa-praying-hands',
                'title' => 'Prayer & Intercession',
                'time_primary' => '6:00 PM',
                'note_primary' => '',
                'time_secondary' => '',
                'note_secondary' => '',
                'body' => 'Corporate prayer for families, the church, and our nation. Submit requests anytime.',
                'cta_label' => 'Prayer Requests',
                'cta_url' => 'contact',
            ],
        ];
    }

    /** @param array<string, mixed> $item @return array<string, mixed> */
    public function normalize(array $item): array
    {
        $empty = $this->emptyProgram();

        return [
            'day' => trim((string) ($item['day'] ?? '')),
            'icon' => $this->safeIcon((string) ($item['icon'] ?? $empty['icon'])),
            'title' => trim((string) ($item['title'] ?? '')),
            'time_primary' => trim((string) ($item['time_primary'] ?? '')),
            'note_primary' => trim((string) ($item['note_primary'] ?? '')),
            'time_secondary' => trim((string) ($item['time_secondary'] ?? '')),
            'note_secondary' => trim((string) ($item['note_secondary'] ?? '')),
            'body' => trim((string) ($item['body'] ?? '')),
            'cta_label' => trim((string) ($item['cta_label'] ?? '')),
            'cta_url' => trim((string) ($item['cta_url'] ?? '')),
        ];
    }

    /** @return array<string, string> */
    public function emptyProgram(): array
    {
        return [
            'day' => '',
            'icon' => 'fa-church',
            'title' => '',
            'time_primary' => '',
            'note_primary' => '',
            'time_secondary' => '',
            'note_secondary' => '',
            'body' => '',
            'cta_label' => '',
            'cta_url' => '',
        ];
    }

    private function safeIcon(string $icon): string
    {
        $icon = trim($icon);
        $allowed = [
            'fa-calendar-day',
            'fa-bible',
            'fa-praying-hands',
            'fa-church',
            'fa-book-bible',
            'fa-music',
            'fa-users',
            'fa-clock',
        ];

        return in_array($icon, $allowed, true) ? $icon : 'fa-church';
    }
}
