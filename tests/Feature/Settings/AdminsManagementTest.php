<?php

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

function seedAssignableRole(string $slug = 'church_editor', string $name = 'Church Editor'): int
{
    return (int) DB::table('roles')->insertGetId([
        'slug' => $slug.'_'.uniqid(),
        'name' => $name,
        'dashboard_type' => 'church_admin',
        'is_system' => false,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('super admin can open administrators settings tab', function () {
    /** @var \Tests\TestCase $this */
    $super = Admin::factory()->create(['role' => 'super_admin']);

    $this->actingAs($super, 'admin')
        ->get(route('settings.index', ['tab' => 'admins']))
        ->assertOk()
        ->assertSee('Administrators')
        ->assertSee('Add admin');
});

test('non super admin cannot manage administrators', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'admin', 'role_id' => 1]);

    $this->actingAs($admin, 'admin')
        ->get(route('settings.admins.create'))
        ->assertForbidden();
});

test('add admin form lists all project roles', function () {
    /** @var \Tests\TestCase $this */
    $super = Admin::factory()->create(['role' => 'super_admin']);
    $roleName = 'Finance Steward '.uniqid();
    seedAssignableRole('finance_steward', $roleName);

    $this->actingAs($super, 'admin')
        ->get(route('settings.admins.create'))
        ->assertOk()
        ->assertSee('Role')
        ->assertSee($roleName);
});

test('super admin can create edit and delete an administrator', function () {
    /** @var \Tests\TestCase $this */
    $super = Admin::factory()->create(['role' => 'super_admin']);
    $roleId = seedAssignableRole();

    $this->actingAs($super, 'admin')
        ->post(route('settings.admins.store'), [
            'full_name' => 'Scoped Editor',
            'email' => 'scoped.editor@example.com',
            'username' => 'scoped_editor',
            'rbac_role_id' => $roleId,
            'account_status' => 'active',
            'password' => 'Password123!',
            'force_password_change' => 0,
        ])
        ->assertRedirect();

    $created = Admin::query()->where('email', 'scoped.editor@example.com')->first();
    expect($created)->not->toBeNull();
    expect((int) $created->role_id)->toBe($roleId);
    expect(DB::table('admin_role_assignments')->where('admin_id', $created->id)->where('role_id', $roleId)->exists())->toBeTrue();

    $this->actingAs($super, 'admin')
        ->put(route('settings.admins.update', $created->id), [
            'full_name' => 'Scoped Editor Updated',
            'email' => 'scoped.editor@example.com',
            'username' => 'scoped_editor',
            'rbac_role_id' => $roleId,
            'account_status' => 'suspended',
            'force_password_change' => 0,
            'password' => '',
        ])
        ->assertRedirect(route('settings.index', ['tab' => 'admins']));

    $created->refresh();
    expect($created->full_name)->toBe('Scoped Editor Updated');
    expect($created->account_status)->toBe('suspended');
    expect((bool) $created->is_active)->toBeFalse();

    $this->actingAs($super, 'admin')
        ->delete(route('settings.admins.destroy', $created->id))
        ->assertRedirect(route('settings.index', ['tab' => 'admins']));

    expect(Admin::query()->where('email', 'scoped.editor@example.com')->exists())->toBeFalse();
});

test('super admin cannot delete own account or another super admin', function () {
    /** @var \Tests\TestCase $this */
    $super = Admin::factory()->create(['role' => 'super_admin', 'email' => 'super1@example.com']);
    $otherSuper = Admin::factory()->create(['role' => 'super_admin', 'email' => 'super2@example.com']);

    $this->actingAs($super, 'admin')
        ->delete(route('settings.admins.destroy', $super->id))
        ->assertRedirect(route('settings.index', ['tab' => 'admins']))
        ->assertSessionHasErrors('admin');

    $this->actingAs($super, 'admin')
        ->delete(route('settings.admins.destroy', $otherSuper->id))
        ->assertRedirect(route('settings.index', ['tab' => 'admins']))
        ->assertSessionHasErrors('admin');

    expect(Admin::query()->whereKey([$super->id, $otherSuper->id])->count())->toBe(2);
});

test('updated admin password can authenticate', function () {
    /** @var \Tests\TestCase $this */
    $super = Admin::factory()->create(['role' => 'super_admin']);
    $roleId = seedAssignableRole('password_reset_role', 'Password Reset Role');
    $target = Admin::factory()->create([
        'role' => 'admin',
        'role_id' => $roleId,
        'email' => 'reset.me@example.com',
        'password_hash' => Hash::make('OldPassword1!'),
    ]);

    $this->actingAs($super, 'admin')
        ->put(route('settings.admins.update', $target->id), [
            'full_name' => $target->full_name,
            'email' => $target->email,
            'username' => $target->username,
            'rbac_role_id' => $roleId,
            'account_status' => 'active',
            'password' => 'NewPassword9!',
            'force_password_change' => 0,
        ])
        ->assertRedirect();

    $this->post('/admin/logout');

    $this->post('/portal/login', [
        'email' => 'reset.me@example.com',
        'password' => 'NewPassword9!',
    ])->assertRedirect();

    $this->assertAuthenticatedAs($target->fresh(), 'admin');
});

test('super admin can view permissions assigned to an admin', function () {
    /** @var \Tests\TestCase $this */
    $super = Admin::factory()->create(['role' => 'super_admin']);
    $target = Admin::factory()->create([
        'role' => 'admin',
        'role_id' => null,
        'email' => 'perms.view@example.com',
    ]);

    $roleId = DB::table('roles')->insertGetId([
        'slug' => 'viewer_role_'.uniqid(),
        'name' => 'Viewer Role',
        'dashboard_type' => 'church_admin',
        'is_system' => false,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $permissionId = DB::table('permissions')->where('permission_key', 'members.view')->value('id');
    if (! $permissionId) {
        $permissionId = DB::table('permissions')->insertGetId([
            'module' => 'members',
            'action' => 'view',
            'permission_key' => 'members.view',
            'label' => 'View Members',
            'created_at' => now(),
        ]);
    }

    DB::table('admins')->where('id', $target->id)->update(['role_id' => $roleId]);
    DB::table('admin_role_assignments')->insert([
        'admin_id' => $target->id,
        'role_id' => $roleId,
        'assigned_at' => now(),
    ]);
    DB::table('role_permissions')->insert([
        'role_id' => $roleId,
        'permission_id' => $permissionId,
        'granted_at' => now(),
    ]);

    $this->actingAs($super, 'admin')
        ->get(route('settings.admins.permissions', $target->id))
        ->assertOk()
        ->assertSee('Permissions · '.$target->full_name)
        ->assertSee('Viewer Role')
        ->assertSee('members.view')
        ->assertSee('View Members');
});

test('super admin can suspend an administrator and block login', function () {
    /** @var \Tests\TestCase $this */
    $super = Admin::factory()->create(['role' => 'super_admin']);
    $target = Admin::factory()->create([
        'role' => 'admin',
        'role_id' => 42,
        'email' => 'to.suspend@example.com',
        'password_hash' => Hash::make('Password123!'),
        'is_active' => true,
        'account_status' => 'active',
    ]);

    $this->actingAs($super, 'admin')
        ->post(route('settings.admins.status', $target->id), [
            'account_status' => 'suspended',
        ])
        ->assertRedirect(route('settings.index', ['tab' => 'admins']));

    $target->refresh();
    expect($target->account_status)->toBe('suspended');
    expect((bool) $target->is_active)->toBeFalse();

    $this->post('/admin/logout');

    $this->post('/portal/login', [
        'email' => 'to.suspend@example.com',
        'password' => 'Password123!',
    ])->assertSessionHasErrors('email');

    $this->assertGuest('admin');

    $this->actingAs($super, 'admin')
        ->post(route('settings.admins.status', $target->id), [
            'account_status' => 'active',
        ])
        ->assertRedirect(route('settings.index', ['tab' => 'admins']));

    $target->refresh();
    expect($target->account_status)->toBe('active');
    expect((bool) $target->is_active)->toBeTrue();
});
