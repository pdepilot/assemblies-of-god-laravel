<?php

namespace App\Services\Website;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ActivityReadService
{
    public const SLOT_COUNT = 8;

    /** @return list<array<string, mixed>> */
    public function published(): array
    {
        if (! Schema::hasTable('church_activities')) {
            return [];
        }

        return DB::table('church_activities')
            ->where('is_published', 1)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->map(fn ($row) => $this->normalize((array) $row))
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public function editorSlots(): array
    {
        $slots = [];
        if (Schema::hasTable('church_activities')) {
            $slots = DB::table('church_activities')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn ($row) => $this->normalize((array) $row))
                ->all();
        }

        if ($slots === []) {
            $slots = $this->defaultActivities();
        }

        while (count($slots) < self::SLOT_COUNT) {
            $slots[] = $this->emptyActivity();
        }

        return array_slice($slots, 0, self::SLOT_COUNT);
    }

    /** @param array<string, mixed> $item @return array<string, mixed> */
    public function normalize(array $item): array
    {
        $empty = $this->emptyActivity();
        $published = $item['is_published'] ?? true;

        return [
            'id' => (int) ($item['id'] ?? 0),
            'title' => trim((string) ($item['title'] ?? '')),
            'description' => trim((string) ($item['description'] ?? '')),
            'icon_class' => $this->safeIcon((string) ($item['icon_class'] ?? $empty['icon_class'])),
            'meeting_schedule' => trim((string) ($item['meeting_schedule'] ?? '')),
            'read_more_url' => trim((string) ($item['read_more_url'] ?? '')),
            'is_published' => filter_var($published, FILTER_VALIDATE_BOOLEAN) || $published === 1 || $published === '1',
        ];
    }

    /** @return array<string, mixed> */
    public function emptyActivity(): array
    {
        return [
            'id' => 0,
            'title' => '',
            'description' => '',
            'icon_class' => 'fa-church',
            'meeting_schedule' => '',
            'read_more_url' => '',
            'is_published' => true,
        ];
    }

    /** @return list<array<string, mixed>> */
    public function defaultActivities(): array
    {
        return [
            [
                'id' => 0,
                'title' => 'Sunday Worship',
                'description' => 'Lift your voice in praise and receive the Word that stirs faith. Every Sunday we encounter God\'s presence together as one family.',
                'icon_class' => 'fa-church',
                'meeting_schedule' => 'Sunday 8:00 AM & 10:30 AM',
                'read_more_url' => '',
                'is_published' => true,
            ],
            [
                'id' => 0,
                'title' => 'Community Outreach',
                'description' => 'We carry Christ\'s compassion to the streets—feeding the hungry, visiting the lonely, and sharing the hope of the Gospel with practical love.',
                'icon_class' => 'fa-donate',
                'meeting_schedule' => 'Monthly outreach',
                'read_more_url' => '',
                'is_published' => true,
            ],
            [
                'id' => 0,
                'title' => 'Bible Study',
                'description' => 'The Bible is our lamp and light. Midweek study deepens understanding, builds disciples, and equips saints for victorious living.',
                'icon_class' => 'fa-bible',
                'meeting_schedule' => 'Wednesday 6:00 PM',
                'read_more_url' => '',
                'is_published' => true,
            ],
            [
                'id' => 0,
                'title' => 'Prayer Ministry',
                'description' => 'Through united prayer we stand in the gap for families, nations, and the sick—believing God hears and answers according to His will.',
                'icon_class' => 'fa-book',
                'meeting_schedule' => 'Daily 5:00 AM',
                'read_more_url' => '',
                'is_published' => true,
            ],
            [
                'id' => 0,
                'title' => 'Family Ministries',
                'description' => 'Strong families strengthen the church. We mentor couples and parents to build godly homes rooted in prayer, purity, and the Word.',
                'icon_class' => 'fa-book-open',
                'meeting_schedule' => 'Monthly fellowship',
                'read_more_url' => '',
                'is_published' => true,
            ],
            [
                'id' => 0,
                'title' => 'Youth & Children',
                'description' => 'Children and youth discover Jesus in a safe, joyful environment—worshipping, learning Scripture, and growing as bold witnesses for Christ.',
                'icon_class' => 'fa-hands',
                'meeting_schedule' => 'Friday 6:00 PM',
                'read_more_url' => '',
                'is_published' => true,
            ],
        ];
    }

    private function safeIcon(string $icon): string
    {
        $icon = trim($icon);
        if ($icon !== '' && ! str_starts_with($icon, 'fa-')) {
            $icon = 'fa-'.ltrim($icon, 'fa-');
        }

        $allowed = [
            'fa-church',
            'fa-donate',
            'fa-bible',
            'fa-book',
            'fa-book-open',
            'fa-hands',
            'fa-praying-hands',
            'fa-users',
            'fa-music',
            'fa-child',
            'fa-home',
            'fa-heart',
            'fa-cross',
            'fa-handshake',
            'fa-graduation-cap',
            'fa-globe',
        ];

        return in_array($icon, $allowed, true) ? $icon : 'fa-church';
    }
}
