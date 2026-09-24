<?php

use App\Models\Admin;
use App\Services\Auth\RbacNavAccessService;
use App\Services\Portal\PortalNavService;
use Illuminate\Support\Facades\DB;

function enableRbacEnforcement(): void
{
    DB::table('platform_setting_groups')->updateOrInsert(
        ['group_key' => 'rbac'],
        [
            'settings' => json_encode([
                'enforcement_enabled' => true,
                'debug_enabled' => false,
            ]),
            'updated_at' => now(),
        ],
    );
}

function assignScopedDonationsAdmin(): Admin
{
    $admin = Admin::factory()->create([
        'role' => 'admin',
        'role_id' => null,
    ]);

    $roleId = DB::table('roles')->insertGetId([
        'slug' => 'scoped_finance_nav_'.uniqid(),
        'name' => 'Scoped Finance',
        'dashboard_type' => 'finance',
        'is_system' => false,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('admins')->where('id', $admin->id)->update(['role_id' => $roleId]);

    $permissionId = DB::table('permissions')->where('permission_key', 'donations.view')->value('id');
    if (! $permissionId) {
        $permissionId = DB::table('permissions')->insertGetId([
            'module' => 'donations',
            'action' => 'view',
            'permission_key' => 'donations.view',
            'label' => 'View Donations',
            'created_at' => now(),
        ]);
    }

    DB::table('admin_role_assignments')->insert([
        'admin_id' => $admin->id,
        'role_id' => $roleId,
        'assigned_at' => now(),
    ]);

    DB::table('role_permissions')->insert([
        'role_id' => $roleId,
        'permission_id' => $permissionId,
        'granted_at' => now(),
    ]);

    return $admin->fresh();
}

test('when rbac enforcement is off full sidebar is returned', function () {
    $admin = Admin::factory()->create(['role' => 'admin', 'role_id' => null]);

    $nav = app(PortalNavService::class)->cmsConfig($admin, \App\Support\RbacPlatform::AG)['nav'];
    $access = app(RbacNavAccessService::class)->getNavAccess($admin, \App\Support\RbacPlatform::AG);

    expect($access['rbac_enabled'])->toBeFalse();
    expect($access['fail_open'])->toBeTrue();
    expect(collect($nav)->firstWhere('id', 'ag'))->not->toBeNull();
    expect(collect($nav)->firstWhere('id', 'system'))->not->toBeNull();
});

test('scoped admin only sees allowed sidebar modules when enforcement is on', function () {
    enableRbacEnforcement();
    $admin = assignScopedDonationsAdmin();

    $nav = app(PortalNavService::class)->navForAdmin($admin);
    $ids = collect($nav)
        ->flatMap(function (array $item) {
            if (($item['type'] ?? null) === 'group') {
                return collect($item['children'] ?? [])->pluck('id');
            }

            return [$item['id']];
        })
        ->values()
        ->all();

    expect($ids)->toContain('donations');
    expect($ids)->toContain('stewardship');
    expect($ids)->not->toContain('members');
    expect($ids)->not->toContain('settings');
    expect($ids)->not->toContain('security-bans');

    $access = app(RbacNavAccessService::class)->getNavAccess($admin);
    expect($access['rbac_enabled'])->toBeTrue();
    expect($access['fail_open'])->toBeFalse();
    expect($access['items']['donations'])->toBeTrue();
    expect($access['items']['members'])->toBeFalse();
});

test('scoped admin is redirected away from denied pages when enforcement is on', function () {
    /** @var \Tests\TestCase $this */
    enableRbacEnforcement();
    $admin = assignScopedDonationsAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('members.index'))
        ->assertRedirect();

    $this->actingAs($admin, 'admin')
        ->get(route('donations.index'))
        ->assertOk();
});

test('legacy admin still sees full nav when enforcement is on', function () {
    /** @var \Tests\TestCase $this */
    enableRbacEnforcement();
    $admin = Admin::factory()->create([
        'role' => 'admin',
        'role_id' => null,
    ]);

    $nav = app(PortalNavService::class)->navForAdmin($admin);
    expect(collect($nav)->firstWhere('id', 'system'))->not->toBeNull();

    $this->actingAs($admin, 'admin')
        ->get(route('members.index'))
        ->assertOk();
});

test('scoped finance role lands on donations home not admin dashboard', function () {
    /** @var \Tests\TestCase $this */
    enableRbacEnforcement();
    $admin = assignScopedDonationsAdmin();

    $home = app(PortalNavService::class)->homeHrefForAdmin($admin);
    expect($home)->toBe(route('donations.index'));
    expect($home)->not->toBe(route('dashboard'));

    $this->actingAs($admin, 'admin')
        ->get(route('dashboard'))
        ->assertRedirect(route('donations.index'));

    $this->post('/admin/logout');

    $this->post('/portal/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('donations.index'));
});

test('scoped sidebar hides main dashboard when role has a module home', function () {
    enableRbacEnforcement();
    $admin = assignScopedDonationsAdmin();

    $ids = collect(app(PortalNavService::class)->navForAdmin($admin))
        ->flatMap(function (array $item) {
            if (($item['type'] ?? null) === 'group') {
                return collect($item['children'] ?? [])->pluck('id');
            }

            return [$item['id']];
        })
        ->values()
        ->all();

    expect($ids)->not->toContain('dashboard');
    expect($ids)->toContain('donations');
});
