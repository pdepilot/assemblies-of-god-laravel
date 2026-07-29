<?php

use App\Models\Admin;
use Illuminate\Support\Facades\DB;

test('super admin can open roles tab with assignment and role list', function () {
    $admin = Admin::factory()->create(['role' => 'super_admin']);

    $roleId = DB::table('roles')->insertGetId([
        'slug' => 'youth_test_role',
        'name' => 'Youth Test Role',
        'description' => 'Test',
        'dashboard_type' => 'youth',
        'is_system' => false,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $permissionId = DB::table('permissions')->insertGetId([
        'module' => 'youths',
        'action' => 'view',
        'permission_key' => 'youths.view.test',
        'label' => 'View Youths Test',
        'created_at' => now(),
    ]);

    DB::table('role_permissions')->insert([
        'role_id' => $roleId,
        'permission_id' => $permissionId,
        'granted_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('settings.index', ['tab' => 'roles']))
        ->assertOk()
        ->assertSee('Assign roles to an administrator')
        ->assertSee('Youth Test Role')
        ->assertSee('Create role');
});

test('super admin can sync admin role assignments', function () {
    $super = Admin::factory()->create(['role' => 'super_admin']);
    $target = Admin::factory()->create(['role' => 'content_editor', 'full_name' => 'Editor Target']);

    $roleId = DB::table('roles')->insertGetId([
        'slug' => 'content_editor_rbac',
        'name' => 'Content Editor RBAC',
        'dashboard_type' => 'communications',
        'is_system' => false,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($super, 'admin')
        ->post(route('settings.rbac.assignments.sync'), [
            'admin_id' => $target->id,
            'role_ids' => [$roleId],
        ])
        ->assertRedirect(route('settings.index', ['tab' => 'roles', 'assign_admin' => $target->id]));

    $this->assertDatabaseHas('admin_role_assignments', [
        'admin_id' => $target->id,
        'role_id' => $roleId,
    ]);
});

test('super admin can create a role with permissions', function () {
    $super = Admin::factory()->create(['role' => 'super_admin']);

    $permissionId = DB::table('permissions')->insertGetId([
        'module' => 'events',
        'action' => 'view',
        'permission_key' => 'events.view.rbac_test',
        'label' => 'View Events Test',
        'created_at' => now(),
    ]);

    $this->actingAs($super, 'admin')
        ->post(route('settings.rbac.roles.store'), [
            'name' => 'Events Helper',
            'slug' => 'events_helper',
            'dashboard_type' => 'events',
            'is_active' => 1,
            'permission_ids' => [$permissionId],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('roles', [
        'slug' => 'events_helper',
        'name' => 'Events Helper',
        'dashboard_type' => 'events',
    ]);

    $roleId = (int) DB::table('roles')->where('slug', 'events_helper')->value('id');
    $this->assertDatabaseHas('role_permissions', [
        'role_id' => $roleId,
        'permission_id' => $permissionId,
    ]);
});

test('rbac settings save redirects back to roles tab', function () {
    $super = Admin::factory()->create(['role' => 'super_admin']);

    $this->actingAs($super, 'admin')
        ->post(route('settings.group.update'), [
            'group' => 'rbac',
            'enforcement_enabled' => 1,
            'debug_enabled' => 0,
        ])
        ->assertRedirect(route('settings.index', ['tab' => 'roles']));
});
