<?php

namespace App\Services\Events;

use App\Services\PublicSite\PublicAssetResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class EventReadService
{
    public function __construct(
        private readonly PublicAssetResolver $assets,
    ) {}
    public const CATEGORIES = ['service', 'program', 'outreach', 'sdtg', 'other'];

    public const STATUSES = ['upcoming', 'completed', 'cancelled'];

    public const ICONS = [
        'fa-church', 'fa-bible', 'fa-praying-hands', 'fa-calendar-days', 'fa-bolt',
        'fa-people-group', 'fa-music', 'fa-child', 'fa-users', 'fa-heart',
    ];

    public const IMAGES = [
        'img/events-1.jpg',
        'img/events-2.jpg',
        'img/events-3.jpg',
    ];

    /** @return array<string, string> */
    public static function categoryLabels(): array
    {
        return [
            'service' => 'Sunday Service',
            'program' => 'Program',
            'outreach' => 'Outreach',
            'sdtg' => 'SDTG',
            'other' => 'Other',
        ];
    }

    /** @return array<string, string> */
    public static function publicCategoryLabels(): array
    {
        return [
            'service' => 'Worship',
            'program' => 'Program',
            'outreach' => 'Outreach',
            'sdtg' => 'SDTG',
            'other' => 'Event',
        ];
    }

    public function syncPublicEventState(): void
    {
        $today = now()->toDateString();

        DB::table('church_events')
            ->where('is_recurring', false)
            ->whereNotNull('recurrence_label')
            ->where('recurrence_label', '!=', '')
            ->update(['is_recurring' => true]);

        DB::table('church_events')
            ->where('is_published', true)
            ->where('status', 'upcoming')
            ->where('is_recurring', false)
            ->whereDate('event_date', '<', $today)
            ->where(function ($q) {
                $q->whereRaw('LOWER(title) LIKE ?', ['%sunday%'])
                    ->orWhereRaw('LOWER(title) LIKE ?', ['%midweek%'])
                    ->orWhereRaw('LOWER(title) LIKE ?', ['%bible study%'])
                    ->orWhereRaw('LOWER(title) LIKE ?', ['%prayer%'])
                    ->orWhere('category', 'service');
            })
            ->get(['id', 'recurrence_label'])
            ->each(function ($row) {
                $label = trim((string) $row->recurrence_label);

                DB::table('church_events')->where('id', $row->id)->update([
                    'is_recurring' => true,
                    'recurrence_label' => $label !== '' ? $label : 'Every Sunday',
                ]);
            });

        $stale = DB::table('church_events')
            ->where('is_recurring', true)
            ->whereDate('event_date', '<', $today)
            ->get(['id', 'event_date']);

        foreach ($stale as $event) {
            $date = Carbon::parse((string) $event->event_date);
            while ($date->toDateString() < $today) {
                $date->addDays(7);
            }
            DB::table('church_events')->where('id', $event->id)->update([
                'event_date' => $date->toDateString(),
            ]);
        }
    }

    /** @return array<string, int> */
    public function getStats(): array
    {
        $this->syncPublicEventState();

        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $weekEnd = now()->addDays(7)->toDateString();

        $upcoming = (int) DB::table('church_events')
            ->where('status', 'upcoming')
            ->where(function ($q) use ($today) {
                $q->where('is_recurring', true)
                    ->orWhereNotNull('recurrence_label')
                    ->where('recurrence_label', '!=', '')
                    ->orWhereDate('event_date', '>=', $today);
            })
            ->count();

        $published = (int) DB::table('church_events')
            ->where('is_published', true)
            ->where('status', 'upcoming')
            ->where(function ($q) use ($today) {
                $q->where('is_recurring', true)
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('recurrence_label')->where('recurrence_label', '!=', '');
                    })
                    ->orWhereDate('event_date', '>=', $today);
            })
            ->count();

        return [
            'upcoming' => $upcoming,
            'published' => $published,
            'this_month' => (int) DB::table('church_events')
                ->whereBetween('event_date', [$monthStart, $monthEnd])
                ->count(),
            'this_week' => (int) DB::table('church_events')
                ->where('status', 'upcoming')
                ->whereBetween('event_date', [$today, $weekEnd])
                ->count(),
            'sdtg_programs' => (int) DB::table('church_events')
                ->where('category', 'sdtg')
                ->where('status', 'upcoming')
                ->count(),
            'expected_attendance' => (int) DB::table('church_events')
                ->where('status', 'upcoming')
                ->sum('expected_attendance'),
            'total' => (int) DB::table('church_events')->count(),
        ];
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, pages: int}
     */
    public function listEvents(string $category, string $status, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $base = DB::table('church_events');

        if ($category !== '' && in_array($category, self::CATEGORIES, true)) {
            $base->where('category', $category);
        }

        if ($status !== '' && in_array($status, self::STATUSES, true)) {
            $base->where('status', $status);
        }

        $total = (int) (clone $base)->count();

        $items = (clone $base)
            ->orderBy('event_date')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(fn ($row) => $this->formatEvent((array) $row))
            ->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => max(1, (int) ceil($total / max(1, $perPage))),
        ];
    }

    /** @return array<string, mixed>|null */
    public function getEvent(int $id): ?array
    {
        $row = DB::table('church_events')->where('id', $id)->first();

        return $row ? $this->formatEvent((array) $row) : null;
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function formatEvent(array $row): array
    {
        $labels = self::categoryLabels();
        $publicLabels = self::publicCategoryLabels();
        $imagePath = (string) ($row['image_path'] ?? self::IMAGES[0]);
        if ($imagePath === '') {
            $imagePath = self::IMAGES[0];
        }

        return [
            'id' => (int) $row['id'],
            'event_code' => (string) $row['event_code'],
            'title' => (string) $row['title'],
            'description' => (string) ($row['description'] ?? ''),
            'event_date' => (string) $row['event_date'],
            'event_time' => $row['event_time'],
            'location' => (string) ($row['location'] ?? ''),
            'category' => (string) $row['category'],
            'category_label' => $labels[(string) $row['category']] ?? (string) $row['category'],
            'image_path' => $imagePath,
            'image_url' => $this->imageUrl($imagePath),
            'is_custom_image' => str_starts_with($imagePath, 'uploads/events/'),
            'recurrence_label' => (string) ($row['recurrence_label'] ?? ''),
            'schedule_display' => (string) ($row['schedule_display'] ?? ''),
            'public_category_label' => (string) ($row['public_category_label'] ?? ($publicLabels[(string) $row['category']] ?? 'Event')),
            'icon_class' => (string) ($row['icon_class'] ?? 'fa-church'),
            'cta_text' => (string) ($row['cta_text'] ?? 'Learn more'),
            'cta_url' => (string) ($row['cta_url'] ?? 'contact'),
            'is_recurring' => (bool) ($row['is_recurring'] ?? false),
            'is_published' => (bool) ($row['is_published'] ?? true),
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'expected_attendance' => (int) ($row['expected_attendance'] ?? 0),
            'status' => (string) $row['status'],
        ];
    }

    public function imageUrl(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', trim($path)), '/');
        if ($path === '') {
            return $this->assets->url(self::IMAGES[0]);
        }

        if (str_starts_with($path, 'uploads/')) {
            return $this->assets->uploadUrl($path);
        }

        return $this->assets->url($path);
    }
}
