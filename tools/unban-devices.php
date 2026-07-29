<?php

use App\Services\Security\DeviceBanService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$bans = app(DeviceBanService::class);
$adminId = (int) (DB::table('admins')->where('role', 'super_admin')->orderBy('id')->value('id') ?: 1);

$active = DB::table('device_bans')->where('is_active', 1)->get();
if ($active->isEmpty()) {
    echo "No active bans to lift.\n";
    exit(0);
}

foreach ($active as $ban) {
    $ok = $bans->liftBan((int) $ban->id, $adminId);
    echo 'Ban #'.$ban->id.' ('.$ban->ip_address.', '.$ban->source.'): '.($ok ? 'lifted' : 'failed')."\n";
}

// Clear common login rate-limit keys for local testing emails
foreach ([
    'finance.demo@agikenegbu.com',
    'finance.demo@agikenegbu.local',
    'demo@agikenegbu.com',
    'demo@agikenegbu.local',
    'admin@agikenegbu.com',
] as $email) {
    RateLimiter::clear(strtolower($email).'|127.0.0.1');
    RateLimiter::clear(strtolower($email).'|::1');
}

$remaining = DB::table('device_bans')->where('is_active', 1)->count();
echo "Active bans remaining: {$remaining}\n";
echo "You can log in again at ".url('/admin/login')."\n";
echo "Use: finance.demo@agikenegbu.com / FinanceDemo1!\n";
