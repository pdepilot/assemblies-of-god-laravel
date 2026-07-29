<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\DB;

final class SiteTrafficIngestService
{
    private const MAX_TITLE = 255;

    private const MAX_REFERRER = 500;

    private const MAX_UA = 500;

    /**
     * @param  array<string, mixed>  $payload
     * @return array{session_key: string, pageview_key: string}
     */
    public function ingest(array $payload, string $clientIp, string $userAgent): array
    {
        $event = strtolower(trim((string) ($payload['event'] ?? 'pageview')));

        if (! in_array($event, ['pageview', 'heartbeat', 'exit'], true)) {
            throw new \InvalidArgumentException('Unknown traffic event.');
        }

        $visitorKey = TrafficSupport::normalizeUuid((string) ($payload['visitor_key'] ?? ''));
        $sessionKey = TrafficSupport::normalizeUuid((string) ($payload['session_key'] ?? ''));
        $pageviewKey = TrafficSupport::normalizeUuid((string) ($payload['pageview_key'] ?? ''));

        if ($visitorKey === '' || $sessionKey === '') {
            throw new \InvalidArgumentException('Missing visitor or session key.');
        }

        $path = TrafficSupport::normalizePath((string) ($payload['path'] ?? '/'));

        if (TrafficSupport::isInternalPath($path)) {
            throw new \InvalidArgumentException('Internal paths are not tracked.');
        }

        $siteArea = TrafficSupport::resolveSiteArea($path, (string) ($payload['site_area'] ?? ''));
        $title = mb_substr(trim((string) ($payload['title'] ?? '')), 0, self::MAX_TITLE);
        $referrer = mb_substr(trim((string) ($payload['referrer'] ?? '')), 0, self::MAX_REFERRER);
        $duration = max(0, min(86400, (int) ($payload['duration_seconds'] ?? 0)));
        $ua = mb_substr($userAgent !== '' ? $userAgent : (string) ($payload['user_agent'] ?? ''), 0, self::MAX_UA);
        $deviceHints = is_array($payload['device'] ?? null) ? $payload['device'] : [];

        $parsed = TrafficSupport::parseUserAgent($ua, $deviceHints);
        $ipHash = TrafficSupport::hashIp($clientIp);
        $geo = $this->resolveGeo($clientIp, $ipHash);
        $now = now()->format('Y-m-d H:i:s');

        $session = $this->findSession($sessionKey);

        if (! $session) {
            if ($event !== 'pageview') {
                $event = 'pageview';
            }

            $sessionId = $this->createSession([
                'session_key' => $sessionKey,
                'visitor_key' => $visitorKey,
                'started_at' => $now,
                'last_seen_at' => $now,
                'duration_seconds' => $duration,
                'device_type' => $parsed['device_type'],
                'browser' => $parsed['browser'],
                'os' => $parsed['os'],
                'country' => $geo['country'],
                'city' => $geo['city'],
                'region' => $geo['region'],
                'ip_hash' => $ipHash,
                'site_area' => $siteArea,
                'referrer' => $referrer !== '' ? $referrer : null,
                'user_agent' => $ua !== '' ? $ua : null,
            ]);

            $session = $this->findSession($sessionKey) ?: [
                'id' => $sessionId,
                'duration_seconds' => 0,
                'pageview_count' => 0,
            ];
        } else {
            $sessionId = (int) $session['id'];
            $this->touchSession(
                $sessionId,
                $now,
                max((int) ($session['duration_seconds'] ?? 0), $duration),
                $geo,
                $parsed
            );
        }

        if ($pageviewKey === '') {
            $pageviewKey = TrafficSupport::newUuid();
        }

        $pageview = $this->findPageview($pageviewKey);

        if (! $pageview && $event === 'pageview') {
            $this->createPageview([
                'session_id' => $sessionId,
                'pageview_key' => $pageviewKey,
                'site_area' => $siteArea,
                'path' => $path,
                'page_title' => $title !== '' ? $title : null,
                'entered_at' => $now,
                'duration_seconds' => $duration,
                'is_exit' => 0,
            ]);

            DB::table('site_sessions')->where('id', $sessionId)->increment('pageview_count');
        } elseif ($pageview) {
            $this->updatePageview(
                (int) $pageview['id'],
                max((int) ($pageview['duration_seconds'] ?? 0), $duration),
                $event === 'exit' ? 1 : (int) ($pageview['is_exit'] ?? 0)
            );
        } else {
            $this->createPageview([
                'session_id' => $sessionId,
                'pageview_key' => $pageviewKey,
                'site_area' => $siteArea,
                'path' => $path,
                'page_title' => $title !== '' ? $title : null,
                'entered_at' => $now,
                'duration_seconds' => $duration,
                'is_exit' => $event === 'exit' ? 1 : 0,
            ]);

            DB::table('site_sessions')->where('id', $sessionId)->increment('pageview_count');
        }

        return [
            'session_key' => $sessionKey,
            'pageview_key' => $pageviewKey,
        ];
    }

    /** @return array{country: string, city: string, region: ?string} */
    private function resolveGeo(string $ip, string $ipHash): array
    {
        $unknown = ['country' => 'Unknown', 'city' => 'Unknown', 'region' => null];

        if ($ipHash === '' || TrafficSupport::isPrivateIp($ip)) {
            return ['country' => 'Local', 'city' => 'Local network', 'region' => null];
        }

        $cached = DB::table('site_geo_cache')->where('ip_hash', $ipHash)->first();

        if ($cached) {
            $fetched = strtotime((string) $cached->fetched_at) ?: 0;
            $ttlDays = (int) config('traffic.geo_ttl_days', 30);

            if ($fetched > time() - ($ttlDays * 86400)) {
                return [
                    'country' => (string) ($cached->country ?: 'Unknown'),
                    'city' => (string) ($cached->city ?: 'Unknown'),
                    'region' => $cached->region !== null && $cached->region !== '' ? (string) $cached->region : null,
                ];
            }
        }

        if (! $this->shouldLookupGeo()) {
            return $unknown;
        }

        $looked = $this->lookupGeoIp($ip);

        if ($looked === null) {
            return $unknown;
        }

        $now = now()->format('Y-m-d H:i:s');

        DB::table('site_geo_cache')->updateOrInsert(
            ['ip_hash' => $ipHash],
            [
                'country' => $looked['country'],
                'city' => $looked['city'],
                'region' => $looked['region'],
                'fetched_at' => $now,
            ]
        );

        return $looked;
    }

    private function shouldLookupGeo(): bool
    {
        // Match legacy: look up public IPs in every environment.
        // Private/local IPs are still skipped inside lookupGeoIp().
        if ((bool) config('traffic.geo_lookup_enabled', true)) {
            return true;
        }

        return app()->environment('production');
    }

    /** @return array{country: string, city: string, region: ?string}|null */
    private function lookupGeoIp(string $ip): ?array
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP) || TrafficSupport::isPrivateIp($ip)) {
            return null;
        }

        $url = 'http://ip-api.com/json/'.rawurlencode($ip).'?fields=status,country,regionName,city';
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 2,
                'header' => "Accept: application/json\r\n",
            ],
        ]);

        $raw = @file_get_contents($url, false, $ctx);

        if ($raw === false) {
            return null;
        }

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        if (($data['status'] ?? '') !== 'success') {
            return null;
        }

        return [
            'country' => trim((string) ($data['country'] ?? '')) ?: 'Unknown',
            'city' => trim((string) ($data['city'] ?? '')) ?: 'Unknown',
            'region' => trim((string) ($data['regionName'] ?? '')) ?: null,
        ];
    }

    /** @return array<string, mixed>|null */
    private function findSession(string $sessionKey): ?array
    {
        $row = DB::table('site_sessions')->where('session_key', $sessionKey)->first();

        return $row ? (array) $row : null;
    }

    /** @return array<string, mixed>|null */
    private function findPageview(string $pageviewKey): ?array
    {
        $row = DB::table('site_pageviews')->where('pageview_key', $pageviewKey)->first();

        return $row ? (array) $row : null;
    }

    /** @param  array<string, mixed>  $data */
    private function createSession(array $data): int
    {
        return (int) DB::table('site_sessions')->insertGetId(array_merge($data, [
            'pageview_count' => 0,
            'created_at' => now(),
        ]));
    }

    /** @param  array<string, mixed>  $geo @param  array<string, string>  $parsed */
    private function touchSession(int $id, string $now, int $duration, array $geo, array $parsed): void
    {
        $existing = DB::table('site_sessions')->where('id', $id)->first();
        $country = $existing->country ?? 'Unknown';
        $city = $existing->city ?? 'Unknown';

        if ($country === 'Unknown' || $country === '') {
            $country = $geo['country'];
        }

        if ($city === 'Unknown' || $city === '') {
            $city = $geo['city'];
        }

        DB::table('site_sessions')->where('id', $id)->update([
            'last_seen_at' => $now,
            'duration_seconds' => $duration,
            'device_type' => $parsed['device_type'],
            'browser' => $parsed['browser'],
            'os' => $parsed['os'],
            'country' => $country,
            'city' => $city,
            'region' => $existing->region ?? $geo['region'],
        ]);
    }

    /** @param  array<string, mixed>  $data */
    private function createPageview(array $data): void
    {
        DB::table('site_pageviews')->insert(array_merge($data, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    private function updatePageview(int $id, int $duration, int $isExit): void
    {
        DB::table('site_pageviews')->where('id', $id)->update([
            'duration_seconds' => $duration,
            'is_exit' => $isExit,
            'updated_at' => now(),
        ]);
    }
}
