<?php

use App\Models\Admin;
use App\Models\Member;
use App\Services\Security\DeviceBanService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

test('admin can view banned ips list and details then unban', function () {
    /** @var \Tests\TestCase $this */
    Http::fake([
        'ip-api.com/*' => Http::response([
            'status' => 'success',
            'country' => 'Nigeria',
            'regionName' => 'Imo',
            'city' => 'Owerri',
            'isp' => 'Example ISP',
            'query' => '203.0.113.10',
        ]),
    ]);

    $admin = Admin::factory()->create(['role' => 'admin']);
    $banId = DB::table('device_bans')->insertGetId([
        'device_fingerprint' => hash('sha256', 'test-device'),
        'source' => DeviceBanService::SOURCE_ADMIN_LOGIN,
        'ip_address' => '203.0.113.10',
        'user_agent' => 'Mozilla/5.0 Chrome/120.0',
        'browser_info' => 'Chrome 120.0',
        'ban_level' => 1,
        'ban_count' => 1,
        'ban_start' => now()->subDay(),
        'ban_expires' => now()->addDays(15),
        'is_active' => 1,
        'lifted_at' => null,
        'created_at' => now()->subDay(),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('security.bans.index'))
        ->assertOk()
        ->assertSee('Banned IPs')
        ->assertSee('203.0.113.10')
        ->assertSee('Admin login')
        ->assertSee('Owerri')
        ->assertSee('Chrome 120.0');

    $this->actingAs($admin, 'admin')
        ->get(route('security.bans.show', $banId))
        ->assertOk()
        ->assertSee('203.0.113.10')
        ->assertSee('Admin login')
        ->assertSee('Unban');

    $this->actingAs($admin, 'admin')
        ->delete(route('security.bans.destroy', $banId))
        ->assertRedirect(route('security.bans.index'));

    $this->assertDatabaseHas('device_bans', [
        'id' => $banId,
        'is_active' => 0,
    ]);
});

test('failed logins apply a device ban after max attempts', function () {
    config([
        'portal.max_failed_attempts' => 2,
        'portal.warning_at_attempt' => 1,
        'portal.ban_days_level_1' => 15,
    ]);

    $service = app(DeviceBanService::class);
    $fp = hash('sha256', 'ban-flow-device');
    $source = DeviceBanService::SOURCE_ADMIN_LOGIN;

    DB::table('login_attempts')->insert([
        [
            'admin_id' => null,
            'email_attempted' => 'attacker@example.com',
            'device_fingerprint' => $fp,
            'source' => $source,
            'ip_address' => '203.0.113.20',
            'user_agent' => 'Mozilla/5.0',
            'browser_info' => 'Chrome',
            'success' => 0,
            'failure_reason' => 'invalid_credentials',
            'created_at' => now()->subMinute(),
        ],
        [
            'admin_id' => null,
            'email_attempted' => 'attacker@example.com',
            'device_fingerprint' => $fp,
            'source' => $source,
            'ip_address' => '203.0.113.20',
            'user_agent' => 'Mozilla/5.0',
            'browser_info' => 'Chrome',
            'success' => 0,
            'failure_reason' => 'invalid_credentials',
            'created_at' => now(),
        ],
    ]);

    expect($service->consecutiveFailures($fp, $source))->toBe(2);

    $result = $service->applyBan($fp, '203.0.113.20', 'Mozilla/5.0', 'Chrome', 'attacker@example.com', $source);
    expect($result['banned'])->toBeTrue();
    expect($result['source'])->toBe($source);
    expect($service->getActiveBan($fp, $source))->not->toBeNull();
    expect($service->getActiveBan($fp, DeviceBanService::SOURCE_MEMBER_PORTAL_LOGIN))->toBeNull();
});

test('member portal failed logins apply a sourced ban and can be filtered', function () {
    /** @var \Tests\TestCase $this */
    Http::fake([
        'ip-api.com/*' => Http::response([
            'status' => 'success',
            'country' => 'Nigeria',
            'regionName' => 'Lagos',
            'city' => 'Lagos',
            'isp' => 'Member ISP',
            'query' => '198.51.100.8',
        ]),
        '*' => Http::response(['success' => false, 'message' => 'Invalid credentials'], 200),
    ]);

    config([
        'portal.max_failed_attempts' => 2,
        'portal.warning_at_attempt' => 1,
        'portal.legacy_public_base' => 'http://legacy.test',
    ]);

    $admin = Admin::factory()->create(['role' => 'admin']);
    $source = DeviceBanService::SOURCE_MEMBER_PORTAL_LOGIN;

    $this->post('/api/member-portal-login.php', [
        'identifier' => 'member@example.com',
        'password' => 'wrong-password',
    ])->assertOk()->assertJsonPath('success', false);

    $this->post('/api/member-portal-login.php', [
        'identifier' => 'member@example.com',
        'password' => 'wrong-password',
    ])->assertForbidden()->assertJsonPath('banned', true);

    $this->assertDatabaseHas('device_bans', [
        'source' => $source,
        'is_active' => 1,
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('security.bans.index', ['source' => $source]))
        ->assertOk()
        ->assertSee('Member portal login')
        ->assertSee('15-day ban');

    $this->actingAs($admin, 'admin')
        ->get(route('security.bans.index', ['source' => DeviceBanService::SOURCE_ADMIN_LOGIN]))
        ->assertOk()
        ->assertSee('No banned accounts found for this filter.');
});

test('super admin can search a banned member by email or phone and unban them', function () {
    /** @var \Tests\TestCase $this */
    Http::fake([
        'ip-api.com/*' => Http::response([
            'status' => 'success',
            'country' => 'Nigeria',
            'regionName' => 'Imo',
            'city' => 'Owerri',
            'isp' => 'Example ISP',
            'query' => '203.0.113.44',
        ]),
    ]);

    $super = Admin::factory()->create(['role' => 'super_admin']);
    Member::factory()->create([
        'full_name' => 'Banned Member',
        'email' => 'banned.member@example.com',
        'phone' => '08034095171',
    ]);
    $fp = hash('sha256', 'member-ban-search');
    $source = DeviceBanService::SOURCE_MEMBER_PORTAL_LOGIN;
    $banId = DB::table('device_bans')->insertGetId([
        'device_fingerprint' => $fp,
        'source' => $source,
        'ip_address' => '203.0.113.44',
        'user_agent' => 'Mozilla/5.0',
        'browser_info' => 'Chrome',
        'ban_level' => 1,
        'ban_count' => 1,
        'ban_start' => now()->subHour(),
        'ban_expires' => now()->addDays(15),
        'is_active' => 1,
        'lifted_at' => null,
        'created_at' => now()->subHour(),
    ]);
    DB::table('login_attempts')->insert([
        'admin_id' => null,
        'email_attempted' => 'banned.member@example.com',
        'device_fingerprint' => $fp,
        'source' => $source,
        'ip_address' => '203.0.113.44',
        'user_agent' => 'Mozilla/5.0',
        'browser_info' => 'Chrome',
        'success' => 0,
        'failure_reason' => 'invalid_credentials',
        'created_at' => now()->subMinutes(5),
    ]);

    $this->actingAs($super, 'admin')
        ->get(route('security.bans.index', ['q' => 'banned.member@example.com']))
        ->assertOk()
        ->assertSee('Banned Member')
        ->assertSee('banned.member@example.com')
        ->assertSee('Unban');

    $this->actingAs($super, 'admin')
        ->get(route('security.bans.index', ['q' => '08034095171']))
        ->assertOk()
        ->assertSee('Banned Member')
        ->assertSee('203.0.113.44');

    $this->actingAs($super, 'admin')
        ->delete(route('security.bans.destroy', $banId))
        ->assertRedirect(route('security.bans.index'));

    $this->assertDatabaseHas('device_bans', [
        'id' => $banId,
        'is_active' => 0,
    ]);

    $service = app(DeviceBanService::class);
    expect($service->getActiveBan($fp, $source))->toBeNull();
    expect($service->consecutiveFailures($fp, $source))->toBe(0);
});

test('super admin can search a banned admin by email or phone and unban them', function () {
    /** @var \Tests\TestCase $this */
    Http::fake([
        'ip-api.com/*' => Http::response(['status' => 'fail']),
    ]);

    $super = Admin::factory()->create(['role' => 'super_admin']);
    $blocked = Admin::factory()->create([
        'role' => 'admin',
        'full_name' => 'Locked Pastor',
        'email' => 'locked.pastor@example.com',
        'phone' => '08011112222',
    ]);
    $fp = hash('sha256', 'admin-ban-search');
    $source = DeviceBanService::SOURCE_ADMIN_LOGIN;
    $banId = DB::table('device_bans')->insertGetId([
        'device_fingerprint' => $fp,
        'source' => $source,
        'ip_address' => '203.0.113.55',
        'user_agent' => 'Mozilla/5.0',
        'browser_info' => 'Firefox',
        'ban_level' => 1,
        'ban_count' => 1,
        'ban_start' => now()->subHour(),
        'ban_expires' => now()->addDays(15),
        'is_active' => 1,
        'lifted_at' => null,
        'created_at' => now()->subHour(),
    ]);
    DB::table('login_attempts')->insert([
        'admin_id' => $blocked->id,
        'email_attempted' => 'locked.pastor@example.com',
        'device_fingerprint' => $fp,
        'source' => $source,
        'ip_address' => '203.0.113.55',
        'user_agent' => 'Mozilla/5.0',
        'browser_info' => 'Firefox',
        'success' => 0,
        'failure_reason' => 'invalid_credentials',
        'created_at' => now()->subMinutes(3),
    ]);

    $this->actingAs($super, 'admin')
        ->get(route('security.bans.index', ['q' => '08011112222']))
        ->assertOk()
        ->assertSee('Locked Pastor')
        ->assertSee('Unban');

    $this->actingAs($super, 'admin')
        ->delete(route('security.bans.destroy', $banId))
        ->assertRedirect(route('security.bans.index'));

    $this->assertDatabaseHas('device_bans', [
        'id' => $banId,
        'is_active' => 0,
    ]);
});

