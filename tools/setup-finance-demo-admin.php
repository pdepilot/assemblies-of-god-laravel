<?php

use App\Models\Admin;
use App\Services\Auth\RbacNavAccessService;
use App\Services\Auth\RbacReadService;
use App\Services\Portal\PortalNavService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$email = 'finance.demo@agikenegbu.com';
$password = 'FinanceDemo1!';

$needed = [
    'dashboard.view',
    'donations.view',
    'recurring_donations.view',
    'partnerships.view',
    'financial_erp.view',
    'reports.view',
];

$permIds = [];
foreach ($needed as $key) {
    $id = DB::table('permissions')->where('permission_key', $key)->value('id');
    if (! $id) {
        [$module, $action] = explode('.', $key, 2);
        $id = DB::table('permissions')->insertGetId([
            'module' => $module,
            'action' => $action,
            'permission_key' => $key,
            'label' => ucwords(str_replace(['_', '.'], ' ', $key)),
            'created_at' => now(),
        ]);
    }
    $permIds[$key] = (int) $id;
}

$role = DB::table('roles')->where('slug', 'finance_demo')->first();
if (! $role) {
    $roleId = DB::table('roles')->insertGetId([
        'slug' => 'finance_demo',
        'name' => 'Finance Demo (scoped)',
        'description' => 'Sample role: dashboard + giving modules only',
        'dashboard_type' => 'finance',
        'is_system' => false,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
} else {
    $roleId = (int) $role->id;
}

DB::table('role_permissions')->where('role_id', $roleId)->delete();
foreach ($needed as $key) {
    DB::table('role_permissions')->insert([
        'role_id' => $roleId,
        'permission_id' => $permIds[$key],
        'granted_at' => now(),
    ]);
}

// admins.role is a legacy ENUM — use "admin" string, but set role_id so
// hasLegacyFullAccess() is false and only assigned permissions apply.
$admin = Admin::query()->where('email', $email)->first();
$payload = [
    'username' => 'finance_demo',
    'password_hash' => Hash::make($password),
    'full_name' => 'Finance Demo User',
    'department' => 'finance',
    'position' => 'Finance Officer',
    'ui_theme' => 'gold',
    'ui_mode' => 'dark',
    'role' => 'admin',
    'role_id' => $roleId,
    'is_active' => 1,
    'account_status' => 'active',
    'force_password_change' => 0,
    'updated_at' => now(),
];

if (! $admin) {
    $payload['email'] = $email;
    $payload['created_at'] = now();
    $adminId = DB::table('admins')->insertGetId($payload);
} else {
    DB::table('admins')->where('id', $admin->id)->update($payload);
    $adminId = (int) $admin->id;
}

$admin = Admin::query()->findOrFail($adminId);

DB::table('admin_role_assignments')->where('admin_id', $admin->id)->delete();
DB::table('admin_role_assignments')->insert([
    'admin_id' => $admin->id,
    'role_id' => $roleId,
    'assigned_at' => now(),
]);

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

$rbac = app(RbacReadService::class);
$access = app(RbacNavAccessService::class);
$nav = app(PortalNavService::class)->navForAdmin($admin);

$ids = [];
foreach ($nav as $item) {
    if (($item['type'] ?? null) === 'group') {
        $ids[] = '## '.($item['label'] ?? $item['id']);
        foreach ($item['children'] ?? [] as $child) {
            $ids[] = '   - '.$child['label'].' ('.$child['id'].')';
        }
    } else {
        $ids[] = '- '.$item['label'].' ('.$item['id'].')';
    }
}

echo "=== Demo account ready ===\n";
echo "Email:    {$email}\n";
echo "Password: {$password}\n";
echo "Role slug: finance_demo (#{$roleId})\n";
echo "Legacy full access: ".($rbac->hasLegacyFullAccess($admin) ? 'YES (bad)' : 'no')."\n";
echo "Super admin: ".($rbac->isSuperAdmin($admin) ? 'yes' : 'no')."\n";
echo "RBAC enforcement: ".($access->isEnforcementEnabled() ? 'ON' : 'OFF')."\n";
echo "\n=== Sidebar this user will see ===\n";
echo implode("\n", $ids)."\n";
echo "\n=== Module checks ===\n";
foreach (['dashboard', 'donations', 'members', 'settings', 'communication_hub'] as $module) {
    echo str_pad($module, 22).($access->canViewModule($admin, $module) ? 'ALLOW' : 'deny')."\n";
}
echo "\nLogin: ".url('/admin/login')."\n";
echo "Your super admin (admin@agikenegbu.com) still sees everything.\n";
echo "To turn enforcement off: Settings → Roles → uncheck Enable RBAC enforcement.\n";
