<?php

use App\Models\Admin;
use App\Services\Analytics\TrafficSupport;
use Illuminate\Support\Facades\DB;

test('content editor can view site traffic dashboard', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    DB::table('site_sessions')->insert([
        'session_key' => 'sess-ui-1',
        'visitor_key' => 'vis-ui-1',
        'started_at' => now()->subMinutes(10),
        'last_seen_at' => now(),
        'duration_seconds' => 120,
        'device_type' => 'mobile',
        'browser' => 'Chrome',
        'os' => 'Android',
        'country' => 'Nigeria',
        'city' => 'Owerri',
        'region' => 'Imo',
        'ip_hash' => 'hash-ui-1',
        'site_area' => 'ag',
        'pageview_count' => 2,
        'created_at' => now(),
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('analytics.site-traffic.index'));

    $response->assertOk();
    $response->assertSee('Site Traffic');
    $response->assertSee('Visitors');
    $response->assertSee('Devices');
    $response->assertSee('Top locations');
    $response->assertSee('Recent sessions');
    $response->assertSee('Nigeria');
    $response->assertSee('Owerri');
    $response->assertSee('Mobile');
});

test('site traffic paginates top pages and recent sessions five per page', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $now = now();
    for ($i = 1; $i <= 7; $i++) {
        $sessionId = DB::table('site_sessions')->insertGetId([
            'session_key' => 'sess-page-'.$i,
            'visitor_key' => 'vis-page-'.$i,
            'started_at' => $now->copy()->subMinutes(70 - $i),
            'last_seen_at' => $now->copy()->subMinutes(70 - $i),
            'duration_seconds' => 30 + $i,
            'device_type' => 'desktop',
            'browser' => 'Chrome',
            'os' => 'Windows',
            'country' => 'Nigeria',
            'city' => 'City '.$i,
            'region' => 'Imo',
            'ip_hash' => 'hash-page-'.$i,
            'site_area' => 'ag',
            'pageview_count' => 1,
            'created_at' => $now,
        ]);

        DB::table('site_pageviews')->insert([
            'session_id' => $sessionId,
            'pageview_key' => 'pv-page-'.$i,
            'site_area' => 'ag',
            'path' => '/unique-path-'.$i,
            'page_title' => 'Page '.$i,
            'entered_at' => $now->copy()->subMinutes(70 - $i),
            'duration_seconds' => 20,
            'is_exit' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    $page1 = $this->actingAs($admin, 'admin')->get(route('analytics.site-traffic.index'));
    $page1->assertOk();
    $page1->assertSee('5 per page');
    $page1->assertViewHas('topPages', function ($paginator): bool {
        return $paginator->total() === 7
            && $paginator->perPage() === 5
            && $paginator->count() === 5;
    });
    $page1->assertViewHas('recentSessions', function ($paginator): bool {
        return $paginator->total() === 7
            && $paginator->perPage() === 5
            && $paginator->count() === 5;
    });

    $pagesPage2 = $this->actingAs($admin, 'admin')->get(route('analytics.site-traffic.index', [
        'pages_page' => 2,
    ]));
    $pagesPage2->assertOk();
    $pagesPage2->assertViewHas('topPages', function ($paginator): bool {
        $paths = collect($paginator->items())->pluck('path')->all();

        return $paginator->currentPage() === 2
            && $paginator->count() === 2
            && count($paths) === 2;
    });

    $sessionsPage2 = $this->actingAs($admin, 'admin')->get(route('analytics.site-traffic.index', [
        'sessions_page' => 2,
    ]));
    $sessionsPage2->assertOk();
    $sessionsPage2->assertViewHas('recentSessions', function ($paginator): bool {
        return $paginator->currentPage() === 2
            && $paginator->count() === 2;
    });
});

test('public POST track-traffic records session without auth', function () {
    $visitorKey = TrafficSupport::newUuid();
    $sessionKey = TrafficSupport::newUuid();

    $response = $this->postJson('/api/track-traffic', [
        'event' => 'pageview',
        'visitor_key' => $visitorKey,
        'session_key' => $sessionKey,
        'path' => '/about',
        'title' => 'About Us',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'session_key' => $sessionKey,
        ])
        ->assertJsonStructure(['pageview_key']);

    $this->assertDatabaseHas('site_sessions', [
        'session_key' => $sessionKey,
        'visitor_key' => $visitorKey,
        'site_area' => 'ag',
    ]);

    $this->assertDatabaseHas('site_pageviews', [
        'path' => '/about',
        'page_title' => 'About Us',
    ]);
});

test('church admin can generate membership CSV report', function () {
    $admin = Admin::factory()->create(['role' => 'church_administrator']);

    DB::table('members')->insert([
        'member_code' => 'MEM-TEST-001',
        'full_name' => 'Test Member',
        'phone' => '08000000000',
        'address_line1' => '1 Test Street',
        'city' => 'Owerri',
        'state' => 'Imo',
        'department' => 'youth',
        'status' => 'active',
        'joined_date' => now()->format('Y-m-d'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')->post(route('analytics.reports.generate'), [
        'type' => 'membership',
        'period' => 'all_time',
        'format' => 'csv',
    ])->assertRedirect(route('analytics.reports.index'));

    $this->assertDatabaseHas('generated_reports', [
        'report_type' => 'membership',
        'format' => 'csv',
        'generated_by' => $admin->id,
    ]);
});

test('ss teacher is forbidden from reports', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);

    $this->actingAs($admin, 'admin')->get(route('analytics.reports.index'))
        ->assertForbidden();
});

test('cutover page loads for admin', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin, 'admin')->get(route('analytics.cutover.index'));

    $response->assertOk();
    $response->assertSee('Migration Cutover');
    $response->assertSee('M10');
    $response->assertSee('Staging validation');
    $response->assertSee('Beacon ingest smoke test');
});

test('track-traffic OPTIONS returns CORS headers', function () {
    config(['traffic.beacon_cors_origins' => ['*']]);

    $response = $this->withHeader('Origin', 'http://legacy.test')
        ->options('/api/track-traffic');

    $response->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', '*')
        ->assertHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
});

test('track-traffic CORS respects configured origins', function () {
    config(['traffic.beacon_cors_origins' => ['http://legacy.test']]);

    $this->withHeader('Origin', 'http://legacy.test')
        ->options('/api/track-traffic')
        ->assertHeader('Access-Control-Allow-Origin', 'http://legacy.test');

    $this->withHeader('Origin', 'http://evil.test')
        ->options('/api/track-traffic')
        ->assertHeader('Access-Control-Allow-Origin', '');
});

test('validate-cutover command runs readiness checks', function () {
    config(['app.url' => 'http://staging.agikenebgu.test']);

    $this->artisan('analytics:validate-cutover')
        ->assertExitCode(0)
        ->expectsOutputToContain('Running cutover validation')
        ->expectsOutputToContain('All cutover checks passed');
});

test('content editor can view session visit detail with pages and duration', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $sessionId = DB::table('site_sessions')->insertGetId([
        'session_key' => 'sess-detail-1',
        'visitor_key' => 'vis-detail-1',
        'started_at' => now()->subMinutes(30),
        'last_seen_at' => now()->subMinutes(5),
        'duration_seconds' => 1500,
        'device_type' => 'mobile',
        'browser' => 'Chrome',
        'os' => 'Android',
        'country' => 'Nigeria',
        'city' => 'Owerri',
        'region' => 'Imo',
        'ip_hash' => 'hash-detail-1',
        'site_area' => 'ag',
        'pageview_count' => 2,
        'created_at' => now(),
    ]);

    DB::table('site_pageviews')->insert([
        [
            'session_id' => $sessionId,
            'pageview_key' => 'pv-detail-1',
            'site_area' => 'ag',
            'path' => '/',
            'page_title' => 'Home',
            'entered_at' => now()->subMinutes(30),
            'duration_seconds' => 900,
            'is_exit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'session_id' => $sessionId,
            'pageview_key' => 'pv-detail-2',
            'site_area' => 'ag',
            'path' => '/about',
            'page_title' => 'About Us',
            'entered_at' => now()->subMinutes(15),
            'duration_seconds' => 600,
            'is_exit' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $response = $this->actingAs($admin, 'admin')
        ->getJson(route('analytics.site-traffic.session', ['sessionKey' => 'sess-detail-1']));

    $response->assertOk()
        ->assertJsonPath('session_key', 'sess-detail-1')
        ->assertJsonPath('visitor_key', 'vis-detail-1')
        ->assertJsonPath('duration_seconds', 1500)
        ->assertJsonPath('pageview_count', 2)
        ->assertJsonCount(2, 'pages')
        ->assertJsonPath('pages.0.path', '/')
        ->assertJsonPath('pages.1.path', '/about')
        ->assertJsonPath('pages.1.duration_seconds', 600);
});

test('content editor can view visitor device history across sessions', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);
    $now = now();

    foreach ([1, 2] as $i) {
        $sessionId = DB::table('site_sessions')->insertGetId([
            'session_key' => 'sess-vis-'.$i,
            'visitor_key' => 'vis-shared-1',
            'started_at' => $now->copy()->subHours($i),
            'last_seen_at' => $now->copy()->subHours($i)->addMinutes(10),
            'duration_seconds' => 600 * $i,
            'device_type' => 'desktop',
            'browser' => 'Firefox',
            'os' => 'Windows',
            'country' => 'Nigeria',
            'city' => 'Owerri',
            'region' => 'Imo',
            'ip_hash' => 'hash-vis-'.$i,
            'site_area' => 'ag',
            'pageview_count' => 1,
            'created_at' => $now,
        ]);

        DB::table('site_pageviews')->insert([
            'session_id' => $sessionId,
            'pageview_key' => 'pv-vis-'.$i,
            'site_area' => 'ag',
            'path' => '/sermons',
            'page_title' => 'Sermons',
            'entered_at' => $now->copy()->subHours($i),
            'duration_seconds' => 300 * $i,
            'is_exit' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    $response = $this->actingAs($admin, 'admin')
        ->getJson(route('analytics.site-traffic.visitor', [
            'visitorKey' => 'vis-shared-1',
            'from' => $now->copy()->subDays(7)->format('Y-m-d'),
            'to' => $now->format('Y-m-d'),
        ]));

    $response->assertOk()
        ->assertJsonPath('visitor_key', 'vis-shared-1')
        ->assertJsonPath('session_count', 2)
        ->assertJsonPath('total_duration_seconds', 1800)
        ->assertJsonCount(2, 'sessions');
});

test('site traffic index exposes clickable recent sessions', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    DB::table('site_sessions')->insert([
        'session_key' => 'sess-click-1',
        'visitor_key' => 'vis-click-1',
        'started_at' => now()->subMinutes(8),
        'last_seen_at' => now(),
        'duration_seconds' => 480,
        'device_type' => 'tablet',
        'browser' => 'Safari',
        'os' => 'iOS',
        'country' => 'Nigeria',
        'city' => 'Owerri',
        'region' => 'Imo',
        'ip_hash' => 'hash-click-1',
        'site_area' => 'ag',
        'pageview_count' => 1,
        'created_at' => now(),
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('analytics.site-traffic.index'));

    $response->assertOk();
    $response->assertSee('Click a row to see pages visited and time spent');
    $response->assertSee('openSession');
});
