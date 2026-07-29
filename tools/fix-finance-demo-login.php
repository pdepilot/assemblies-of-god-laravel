<?php

/**
 * Fix finance demo login: set a normal email + known password.
 * Does not touch other admins.
 */

use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$oldEmail = 'finance.demo@agikenegbu.local';
$newEmail = 'finance.demo@agikenegbu.com';
$password = 'FinanceDemo1!';

$admin = Admin::query()->where('email', $oldEmail)->first()
    ?? Admin::query()->where('email', $newEmail)->first();

if (! $admin) {
    fwrite(STDERR, "Demo admin not found.\n");
    exit(1);
}

$roleId = (int) (DB::table('roles')->where('slug', 'finance_demo')->value('id') ?: $admin->role_id);

DB::table('admins')->where('id', $admin->id)->update([
    'email' => $newEmail,
    'username' => 'finance_demo',
    'password_hash' => Hash::make($password),
    'role' => 'admin',
    'role_id' => $roleId ?: null,
    'is_active' => 1,
    'account_status' => 'active',
    'force_password_change' => 0,
    'locked_at' => null,
    'updated_at' => now(),
]);

if ($roleId > 0) {
    DB::table('admin_role_assignments')->where('admin_id', $admin->id)->delete();
    DB::table('admin_role_assignments')->insert([
        'admin_id' => $admin->id,
        'role_id' => $roleId,
        'assigned_at' => now(),
    ]);
}

$admin = Admin::query()->findOrFail($admin->id);
$ok = Auth::guard('admin')->attempt([
    'email' => $newEmail,
    'password' => $password,
    'is_active' => 1,
    'account_status' => 'active',
], false);

echo "Updated admin #{$admin->id}\n";
echo "Email: {$newEmail}\n";
echo "Password: {$password}\n";
echo 'Auth::attempt: '.($ok ? 'YES' : 'NO')."\n";
echo 'Login URL: '.url('/admin/login')."\n";
echo "Use the Laravel app (port 8000), not the old XAMPP portal login.\n";

if ($ok) {
    Auth::guard('admin')->logout();
}
