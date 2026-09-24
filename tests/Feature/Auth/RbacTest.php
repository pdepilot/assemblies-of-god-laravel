<?php

use App\Models\Admin;
use App\Services\Auth\RbacReadService;
use Illuminate\Support\Facades\DB;

test('super admin has wildcard permissions via role string', function () {
    $admin = Admin::factory()->create(['role' => 'super_admin']);

    $rbac = app(RbacReadService::class);

    expect($rbac->isSuperAdmin($admin))->toBeTrue();
    expect($rbac->can($admin, 'members.delete'))->toBeTrue();
});

test('legacy full access admin can access any permission', function () {
    $admin = Admin::factory()->create([
        'role' => 'admin',
        'role_id' => null,
    ]);

    $rbac = app(RbacReadService::class);

    expect($rbac->hasLegacyFullAccess($admin))->toBeTrue();
    expect($rbac->can($admin, 'financial_erp.view'))->toBeTrue();
});

test('admin with assigned role permissions is scoped correctly', function () {
    $admin = Admin::factory()->create([
        'role' => 'finance',
        'role_id' => null,
    ]);

    $roleId = DB::table('roles')->insertGetId([
        'slug' => 'finance_administrator',
        'name' => 'Finance Administrator',
        'is_system' => true,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $permissionId = DB::table('permissions')->insertGetId([
        'module' => 'donations',
        'action' => 'view',
        'permission_key' => 'donations.view',
        'label' => 'View Donations',
        'created_at' => now(),
    ]);

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

    $rbac = app(RbacReadService::class);

    expect($rbac->can($admin, 'donations.view'))->toBeTrue();
    expect($rbac->can($admin, 'members.delete'))->toBeFalse();
});

test('successful login writes audit row when login_attempts table exists', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create([
        'email' => 'audit@example.com',
        'role' => 'admin',
    ]);

    $response = $this->post('/portal/login', [
        'email' => 'audit@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));

    $this->assertDatabaseHas('login_attempts', [
        'admin_id' => $admin->id,
        'email_attempted' => 'audit@example.com',
        'success' => 1,
    ]);

    expect(Admin::query()->find($admin->id)?->last_login_ip)->not->toBeNull();
});

test('failed login writes audit row', function () {
    /** @var \Tests\TestCase $this */
    Admin::factory()->create([
        'email' => 'fail@example.com',
    ]);

    $this->post('/portal/login', [
        'email' => 'fail@example.com',
        'password' => 'wrong-password',
    ]);

    $this->assertDatabaseHas('login_attempts', [
        'email_attempted' => 'fail@example.com',
        'success' => 0,
    ]);
});
