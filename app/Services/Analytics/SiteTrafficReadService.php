<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\DB;

final class SiteTrafficReadService
{
    /**
     * @return array<string, mixed>
     */
    public function getDashboard(string $from, string $to, string $siteArea = 'all'): array
    {
        [$fromDt, $toDt] = $this->normalizeRange($from, $to);
        $area = $this->normalizeAreaFilter($siteArea);

        return [
            'filters' => [
                'from' => $fromDt->format('Y-m-d'),
                'to' => $toDt->format('Y-m-d'),
                'site_area' => $area,
            ],
            'kpis' => $this->kpis($fromDt, $toDt, $area),
            'traffic_over_time' => $this->trafficOverTime($fromDt, $toDt, $area),
            'top_pages' => $this->topPages($fromDt, $toDt, $area),
            'devices' => $this->devices($fromDt, $toDt, $area),
            'locations' => $this->locations($fromDt, $toDt, $area),
            'recent_sessions' => $this->recentSessions($fromDt, $toDt, $area),
            'by_site_area' => $this->bySiteArea($fromDt, $toDt),
        ];
    }

    /**
     * Full visit detail for one session (pages + time spent).
     *
     * @return array<string, mixed>|null
     */
    public function getSessionDetail(string $sessionKey): ?array
    {
        $sessionKey = trim($sessionKey);
        if ($sessionKey === '') {
            return null;
        }

        $row = DB::table('site_sessions')->where('session_key', $sessionKey)->first();
        if (! $row) {
            return null;
        }

        $pages = DB::table('site_pageviews')
            ->where('session_id', (int) $row->id)
            ->orderBy('entered_at')
            ->get()
            ->map(fn ($pv) => [
                'path' => (string) $pv->path,
                'title' => (string) ($pv->page_title ?? ''),
                'site_area' => (string) $pv->site_area,
                'entered_at' => (string) $pv->entered_at,
                'duration_seconds' => (int) $pv->duration_seconds,
                'is_exit' => (bool) $pv->is_exit,
            ])
            ->all();

        return $this->mapSessionDetail($row, $pages);
    }

    /**
     * All sessions for one visitor (same browser/device fingerprint) in a date range.
     *
     * @return array{visitor_key: string, filters: array{from: string, to: string}, total_duration_seconds: int, session_count: int, sessions: list<array<string, mixed>>}
     */
    public function getVisitorDetail(string $visitorKey, string $from = '', string $to = ''): array
    {
        $visitorKey = trim($visitorKey);
        [$fromDt, $toDt] = $this->normalizeRange($from, $to);

        if ($visitorKey === '') {
            return [
                'visitor_key' => '',
                'filters' => [
                    'from' => $fromDt->format('Y-m-d'),
                    'to' => $toDt->format('Y-m-d'),
                ],
                'total_duration_seconds' => 0,
                'session_count' => 0,
                'sessions' => [],
            ];
        }

        $sessions = DB::table('site_sessions')
            ->where('visitor_key', $visitorKey)
            ->where('started_at', '>=', $fromDt->format('Y-m-d 00:00:00'))
            ->where('started_at', '<', $toDt->modify('+1 day')->format('Y-m-d 00:00:00'))
            ->orderByDesc('last_seen_at')
            ->limit(50)
            ->get();

        if ($sessions->isEmpty()) {
            return [
                'visitor_key' => $visitorKey,
                'filters' => [
                    'from' => $fromDt->format('Y-m-d'),
                    'to' => $toDt->format('Y-m-d'),
                ],
                'total_duration_seconds' => 0,
                'session_count' => 0,
                'sessions' => [],
            ];
        }

        $ids = $sessions->pluck('id')->all();
        $pageviews = DB::table('site_pageviews')
            ->whereIn('session_id', $ids)
            ->orderBy('entered_at')
            ->get()
            ->groupBy('session_id');

        $mapped = $sessions->map(function ($row) use ($pageviews) {
            $pages = ($pageviews[$row->id] ?? collect())
                ->map(fn ($pv) => [
                    'path' => (string) $pv->path,
                    'title' => (string) ($pv->page_title ?? ''),
                    'site_area' => (string) $pv->site_area,
                    'entered_at' => (string) $pv->entered_at,
                    'duration_seconds' => (int) $pv->duration_seconds,
                    'is_exit' => (bool) $pv->is_exit,
                ])
                ->values()
                ->all();

            return $this->mapSessionDetail($row, $pages);
        })->all();

        return [
            'visitor_key' => $visitorKey,
            'filters' => [
                'from' => $fromDt->format('Y-m-d'),
                'to' => $toDt->format('Y-m-d'),
            ],
            'total_duration_seconds' => array_sum(array_column($mapped, 'duration_seconds')),
            'session_count' => count($mapped),
            'sessions' => $mapped,
        ];
    }

    /**
     * @param  object  $row
     * @param  list<array<string, mixed>>  $pages
     * @return array<string, mixed>
     */
    private function mapSessionDetail(object $row, array $pages): array
    {
        return [
            'session_key' => (string) $row->session_key,
            'visitor_key' => (string) $row->visitor_key,
            'started_at' => (string) $row->started_at,
            'last_seen_at' => (string) $row->last_seen_at,
            'duration_seconds' => (int) $row->duration_seconds,
            'device_type' => (string) $row->device_type,
            'browser' => (string) $row->browser,
            'os' => (string) $row->os,
            'country' => (string) $row->country,
            'city' => (string) $row->city,
            'site_area' => (string) $row->site_area,
            'pageview_count' => (int) $row->pageview_count,
            'referrer' => (string) ($row->referrer ?? ''),
            'pages' => $pages,
        ];
    }

    /** @return array{deleted_sessions: int, deleted_pageviews: int, retention_days: int} */
    public function purgeOlderThan(?int $days = null): array
    {
        $days = max(7, $days ?? (int) config('traffic.retention_days', 90));
        $cutoff = now()->subDays($days)->format('Y-m-d H:i:s');

        $sessions = (int) DB::table('site_sessions')->where('started_at', '<', $cutoff)->count();
        $pageviews = (int) DB::table('site_pageviews')->where('entered_at', '<', $cutoff)->count();

        DB::table('site_sessions')->where('started_at', '<', $cutoff)->delete();
        DB::table('site_geo_cache')->where('fetched_at', '<', $cutoff)->delete();

        return [
            'deleted_sessions' => $sessions,
            'deleted_pageviews' => $pageviews,
            'retention_days' => $days,
        ];
    }

    /** @return array<string, mixed> */
    private function kpis(\DateTimeImmutable $from, \DateTimeImmutable $to, string $area): array
    {
        $query = $this->sessionQuery($from, $to, $area);

        $row = $query->selectRaw('
            COUNT(*) AS sessions,
            COUNT(DISTINCT visitor_key) AS visitors,
            COALESCE(SUM(pageview_count), 0) AS pageviews,
            COALESCE(AVG(duration_seconds), 0) AS avg_duration,
            COALESCE(SUM(CASE WHEN pageview_count <= 1 THEN 1 ELSE 0 END), 0) AS bounces
        ')->first();

        $sessions = (int) ($row->sessions ?? 0);
        $bounces = (int) ($row->bounces ?? 0);

        return [
            'visitors' => (int) ($row->visitors ?? 0),
            'sessions' => $sessions,
            'pageviews' => (int) ($row->pageviews ?? 0),
            'avg_duration_seconds' => (int) round((float) ($row->avg_duration ?? 0)),
            'bounce_rate' => $sessions > 0 ? round(($bounces / $sessions) * 100, 1) : 0.0,
        ];
    }

    /** @return list<array{date: string, sessions: int, pageviews: int, visitors: int}> */
    private function trafficOverTime(\DateTimeImmutable $from, \DateTimeImmutable $to, string $area): array
    {
        $rows = $this->sessionQuery($from, $to, $area)
            ->selectRaw('DATE(started_at) AS d, COUNT(*) AS sessions, COUNT(DISTINCT visitor_key) AS visitors, COALESCE(SUM(pageview_count), 0) AS pageviews')
            ->groupByRaw('DATE(started_at)')
            ->orderByRaw('d ASC')
            ->get();

        return $rows->map(fn ($row) => [
            'date' => (string) $row->d,
            'sessions' => (int) $row->sessions,
            'visitors' => (int) $row->visitors,
            'pageviews' => (int) $row->pageviews,
        ])->all();
    }

    /** @return list<array<string, mixed>> */
    private function topPages(\DateTimeImmutable $from, \DateTimeImmutable $to, string $area): array
    {
        $query = DB::table('site_pageviews')
            ->where('entered_at', '>=', $from->format('Y-m-d 00:00:00'))
            ->where('entered_at', '<', $to->modify('+1 day')->format('Y-m-d 00:00:00'));

        if ($area !== 'all') {
            $query->where('site_area', $area);
        }

        return $query
            ->selectRaw('path, site_area, COUNT(*) AS views, COALESCE(AVG(duration_seconds), 0) AS avg_duration, MAX(page_title) AS page_title')
            ->groupBy('path', 'site_area')
            ->orderByDesc('views')
            ->limit(100)
            ->get()
            ->map(fn ($row) => [
                'path' => (string) $row->path,
                'site_area' => (string) $row->site_area,
                'page_title' => (string) ($row->page_title ?? ''),
                'views' => (int) $row->views,
                'avg_duration_seconds' => (int) round((float) $row->avg_duration),
            ])
            ->all();
    }

    /** @return list<array{device_type: string, sessions: int}> */
    private function devices(\DateTimeImmutable $from, \DateTimeImmutable $to, string $area): array
    {
        return $this->sessionQuery($from, $to, $area)
            ->selectRaw('device_type, COUNT(*) AS sessions')
            ->groupBy('device_type')
            ->orderByDesc('sessions')
            ->get()
            ->map(fn ($row) => [
                'device_type' => (string) $row->device_type,
                'sessions' => (int) $row->sessions,
            ])
            ->all();
    }

    /** @return list<array<string, mixed>> */
    private function locations(\DateTimeImmutable $from, \DateTimeImmutable $to, string $area): array
    {
        return $this->sessionQuery($from, $to, $area)
            ->selectRaw('country, city, COUNT(*) AS sessions, COUNT(DISTINCT visitor_key) AS visitors')
            ->groupBy('country', 'city')
            ->orderByDesc('sessions')
            ->limit(100)
            ->get()
            ->map(fn ($row) => [
                'country' => (string) $row->country,
                'city' => (string) $row->city,
                'sessions' => (int) $row->sessions,
                'visitors' => (int) $row->visitors,
            ])
            ->all();
    }

    /** @return list<array<string, mixed>> */
    private function recentSessions(\DateTimeImmutable $from, \DateTimeImmutable $to, string $area): array
    {
        $sessions = $this->sessionQuery($from, $to, $area)
            ->select([
                'id', 'session_key', 'visitor_key', 'started_at', 'last_seen_at', 'duration_seconds', 'device_type',
                'browser', 'os', 'country', 'city', 'site_area', 'pageview_count', 'referrer',
            ])
            ->orderByDesc('last_seen_at')
            ->limit(100)
            ->get();

        if ($sessions->isEmpty()) {
            return [];
        }

        $ids = $sessions->pluck('id')->all();
        $pageviews = DB::table('site_pageviews')
            ->whereIn('session_id', $ids)
            ->orderBy('entered_at')
            ->get()
            ->groupBy('session_id');

        return $sessions->map(function ($row) use ($pageviews) {
            $pages = ($pageviews[$row->id] ?? collect())
                ->map(fn ($pv) => [
                    'path' => (string) $pv->path,
                    'title' => (string) ($pv->page_title ?? ''),
                    'duration' => (int) $pv->duration_seconds,
                ])
                ->values()
                ->all();

            return [
                'session_key' => (string) $row->session_key,
                'visitor_key' => (string) $row->visitor_key,
                'started_at' => (string) $row->started_at,
                'last_seen_at' => (string) $row->last_seen_at,
                'duration_seconds' => (int) $row->duration_seconds,
                'device_type' => (string) $row->device_type,
                'browser' => (string) $row->browser,
                'os' => (string) $row->os,
                'country' => (string) $row->country,
                'city' => (string) $row->city,
                'site_area' => (string) $row->site_area,
                'pageview_count' => (int) $row->pageview_count,
                'referrer' => (string) ($row->referrer ?? ''),
                'pages' => $pages,
            ];
        })->all();
    }

    /** @return list<array<string, mixed>> */
    private function bySiteArea(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return DB::table('site_sessions')
            ->where('started_at', '>=', $from->format('Y-m-d 00:00:00'))
            ->where('started_at', '<', $to->modify('+1 day')->format('Y-m-d 00:00:00'))
            ->selectRaw('site_area, COUNT(*) AS sessions, COUNT(DISTINCT visitor_key) AS visitors, COALESCE(SUM(pageview_count), 0) AS pageviews')
            ->groupBy('site_area')
            ->orderByDesc('sessions')
            ->get()
            ->map(fn ($row) => [
                'site_area' => (string) $row->site_area,
                'sessions' => (int) $row->sessions,
                'visitors' => (int) $row->visitors,
                'pageviews' => (int) $row->pageviews,
            ])
            ->all();
    }

    /** @return \Illuminate\Database\Query\Builder */
    private function sessionQuery(\DateTimeImmutable $from, \DateTimeImmutable $to, string $area)
    {
        $query = DB::table('site_sessions')
            ->where('started_at', '>=', $from->format('Y-m-d 00:00:00'))
            ->where('started_at', '<', $to->modify('+1 day')->format('Y-m-d 00:00:00'));

        if ($area !== 'all') {
            $query->where('site_area', $area);
        }

        return $query;
    }

    /** @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable} */
    private function normalizeRange(string $from, string $to): array
    {
        try {
            $fromDt = new \DateTimeImmutable($from !== '' ? $from : 'today -29 days');
        } catch (\Exception) {
            $fromDt = new \DateTimeImmutable('today -29 days');
        }

        try {
            $toDt = new \DateTimeImmutable($to !== '' ? $to : 'today');
        } catch (\Exception) {
            $toDt = new \DateTimeImmutable('today');
        }

        if ($fromDt > $toDt) {
            [$fromDt, $toDt] = [$toDt, $fromDt];
        }

        if ($fromDt->diff($toDt)->days > 366) {
            $fromDt = $toDt->modify('-365 days');
        }

        return [$fromDt, $toDt];
    }

    private function normalizeAreaFilter(string $area): string
    {
        $area = strtolower(trim($area));

        return in_array($area, ['ag', 'sdgt', 'sermon', 'register'], true) ? $area : 'all';
    }
}
