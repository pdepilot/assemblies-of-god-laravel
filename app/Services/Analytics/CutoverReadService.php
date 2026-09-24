<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class CutoverReadService
{
    public function __construct(
        private readonly CutoverValidationService $validation,
    ) {}

    /** @return array<string, mixed> */
    public function getStatus(): array
    {
        $validation = $this->validation->run();

        return [
            'milestones' => $this->milestones(),
            'checklist' => $this->checklist($validation),
            'api_bridge' => $this->apiBridgeNotes(),
            'validation' => $validation,
            'beacon_url' => $this->validation->recommendedBeaconUrl(),
            'legacy_config_example' => $this->legacyConfigExample(),
        ];
    }

    /** @param array{passed: bool, checks: list<array{key: string, passed: bool}>} $validation @return list<array{key: string, label: string, done: bool}> */
    private function checklist(array $validation): array
    {
        $passed = collect($validation['checks'])->pluck('passed', 'key');

        return [
            ['key' => 'parallel_run', 'label' => 'Run Laravel admin beside legacy portal on clone DB', 'done' => false],
            ['key' => 'beacon_url', 'label' => 'Point public traffic beacon to Laravel /api/track-traffic', 'done' => (bool) ($passed['recent_traffic'] ?? false)],
            ['key' => 'validate_reports', 'label' => 'Compare generated CSV reports against legacy exports', 'done' => $this->hasGeneratedReports()],
            ['key' => 'validate_traffic', 'label' => 'Verify session/pageview counts in Site Traffic dashboard', 'done' => (bool) ($passed['recent_traffic'] ?? false)],
            ['key' => 'rbac_smoke', 'label' => 'Smoke-test RBAC for analytics and reports roles', 'done' => (bool) ($passed['beacon_ingest'] ?? false)],
            ['key' => 'staging_validation', 'label' => 'Run php artisan analytics:validate-cutover on staging', 'done' => (bool) $validation['passed']],
            ['key' => 'dns_defer', 'label' => 'Defer DNS cutover until validation sign-off', 'done' => false],
            ['key' => 'rollback_plan', 'label' => 'Confirm mysqldump rollback path documented', 'done' => true],
        ];
    }

    private function hasGeneratedReports(): bool
    {
        if (! Schema::hasTable('generated_reports')) {
            return false;
        }

        return (int) DB::table('generated_reports')->count() > 0;
    }

    private function legacyConfigExample(): string
    {
        return <<<'PHP'
<?php
// config/cutover.local.php (legacy site root — copy from cutover.local.php.example)
return [
    'laravel_traffic_beacon_url' => 'http://127.0.0.1:8000/api/track-traffic',
];
PHP;
    }

    /** @return list<array{key: string, label: string, status: string}> */
    private function milestones(): array
    {
        $items = [
            ['key' => 'M0', 'label' => 'Database assessment', 'status' => 'done'],
            ['key' => 'M1', 'label' => 'Shared DB connection', 'status' => 'done'],
            ['key' => 'M2', 'label' => 'Auth & RBAC foundation', 'status' => 'done'],
            ['key' => 'M3', 'label' => 'Members & ministries', 'status' => 'done'],
            ['key' => 'M4', 'label' => 'Sunday School', 'status' => 'done'],
            ['key' => 'M5', 'label' => 'Events & registration', 'status' => 'done'],
            ['key' => 'M6', 'label' => 'Donations & stewardship', 'status' => 'done'],
            ['key' => 'M7', 'label' => 'Financial ERP', 'status' => 'done'],
            ['key' => 'M8', 'label' => 'Communication hub', 'status' => 'done'],
            ['key' => 'M9', 'label' => 'Media & website', 'status' => 'done'],
            ['key' => 'M10', 'label' => 'Analytics & cutover', 'status' => 'done'],
        ];

        return $items;
    }

    /** @return list<array{title: string, detail: string}> */
    private function apiBridgeNotes(): array
    {
        return [
            [
                'title' => 'Traffic beacon',
                'detail' => 'POST /api/track-traffic (alias /api/track-traffic.php) accepts pageview, heartbeat, and exit JSON payloads. Legacy sites can switch the beacon URL without schema changes.',
            ],
            [
                'title' => 'Rate limiting',
                'detail' => 'Uses existing rate_limits table (120 hits / 5 minutes per IP) via TrafficRateLimitService.',
            ],
            [
                'title' => 'Geo lookup',
                'detail' => 'Private IPs resolve to Local. External geo uses site_geo_cache; live ip-api lookup runs in production or when TRAFFIC_GEO_LOOKUP=true.',
            ],
            [
                'title' => 'Admin modules',
                'detail' => 'Site Traffic, Reports Hub, and Migration Cutover dashboards are available under /analytics/* for authorized admins.',
            ],
        ];
    }
}
