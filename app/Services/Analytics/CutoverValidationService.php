<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class CutoverValidationService
{
    public function __construct(
        private readonly SiteTrafficIngestService $ingest,
    ) {}

    /** @return array{passed: bool, checks: list<array{key: string, label: string, passed: bool, detail: string}>} */
    public function run(): array
    {
        $checks = [
            $this->checkDatabase(),
            $this->checkTrafficTables(),
            $this->checkReportsTable(),
            $this->checkReportsStorage(),
            $this->checkBeaconIngest(),
            $this->checkRecentTraffic(),
            $this->checkAppUrl(),
        ];

        $passed = collect($checks)->every(fn (array $c) => $c['passed']);

        return [
            'passed' => $passed,
            'checks' => $checks,
        ];
    }

    /** @return array{key: string, label: string, passed: bool, detail: string} */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return $this->ok('database', 'Database connection', 'Connected to ' . config('database.default'));
        } catch (\Throwable $e) {
            return $this->fail('database', 'Database connection', $e->getMessage());
        }
    }

    /** @return array{key: string, label: string, passed: bool, detail: string} */
    private function checkTrafficTables(): array
    {
        $required = ['site_sessions', 'site_pageviews', 'site_geo_cache'];
        $missing = array_values(array_filter($required, fn (string $t) => ! Schema::hasTable($t)));

        if ($missing !== []) {
            return $this->fail('traffic_tables', 'Site traffic tables', 'Missing: ' . implode(', ', $missing));
        }

        return $this->ok('traffic_tables', 'Site traffic tables', 'All required tables present');
    }

    /** @return array{key: string, label: string, passed: bool, detail: string} */
    private function checkReportsTable(): array
    {
        if (! Schema::hasTable('generated_reports')) {
            return $this->fail('reports_table', 'Reports metadata table', 'generated_reports not found — run php artisan migrate');
        }

        return $this->ok('reports_table', 'Reports metadata table', 'generated_reports ready');
    }

    /** @return array{key: string, label: string, passed: bool, detail: string} */
    private function checkReportsStorage(): array
    {
        $dir = storage_path('app/reports');
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            return $this->fail('reports_storage', 'Reports storage', 'Cannot create ' . $dir);
        }
        if (! is_writable($dir)) {
            return $this->fail('reports_storage', 'Reports storage', 'Directory not writable: ' . $dir);
        }

        return $this->ok('reports_storage', 'Reports storage', $dir);
    }

    /** @return array{key: string, label: string, passed: bool, detail: string} */
    private function checkBeaconIngest(): array
    {
        try {
            $visitor = (string) Str::uuid();
            $session = (string) Str::uuid();
            $pageview = (string) Str::uuid();

            $this->ingest->ingest([
                'event' => 'pageview',
                'visitor_key' => $visitor,
                'session_key' => $session,
                'pageview_key' => $pageview,
                'path' => '/cutover-validation',
                'title' => 'Cutover validation probe',
                'site_area' => 'ag',
            ], '127.0.0.1', 'CutoverValidation/1.0');

            $exists = DB::table('site_sessions')->where('session_key', $session)->exists();

            if (! $exists) {
                return $this->fail('beacon_ingest', 'Beacon ingest smoke test', 'Session row was not created');
            }

            return $this->ok('beacon_ingest', 'Beacon ingest smoke test', 'POST /api/track-traffic pipeline OK');
        } catch (\Throwable $e) {
            return $this->fail('beacon_ingest', 'Beacon ingest smoke test', $e->getMessage());
        }
    }

    /** @return array{key: string, label: string, passed: bool, detail: string} */
    private function checkRecentTraffic(): array
    {
        if (! Schema::hasTable('site_sessions')) {
            return $this->fail('recent_traffic', 'Recent traffic data', 'site_sessions missing');
        }

        $count = (int) DB::table('site_sessions')
            ->where('started_at', '>=', now()->subDay())
            ->count();

        if ($count === 0) {
            return $this->fail(
                'recent_traffic',
                'Recent traffic data',
                'No sessions in the last 24h — point legacy beacon to ' . $this->recommendedBeaconUrl()
            );
        }

        return $this->ok('recent_traffic', 'Recent traffic data', $count . ' session(s) in the last 24 hours');
    }

    /** @return array{key: string, label: string, passed: bool, detail: string} */
    private function checkAppUrl(): array
    {
        $url = rtrim((string) config('app.url'), '/');
        if ($url === '' || $url === 'http://localhost') {
            return $this->fail(
                'app_url',
                'APP_URL configured',
                'Set APP_URL in .env so legacy beacon can target ' . $url . '/api/track-traffic'
            );
        }

        return $this->ok('app_url', 'APP_URL configured', $url);
    }

    public function recommendedBeaconUrl(): string
    {
        return rtrim((string) config('app.url'), '/') . '/api/track-traffic';
    }

    /** @return array{key: string, label: string, passed: bool, detail: string} */
    private function ok(string $key, string $label, string $detail): array
    {
        return ['key' => $key, 'label' => $label, 'passed' => true, 'detail' => $detail];
    }

    /** @return array{key: string, label: string, passed: bool, detail: string} */
    private function fail(string $key, string $label, string $detail): array
    {
        return ['key' => $key, 'label' => $label, 'passed' => false, 'detail' => $detail];
    }
}
