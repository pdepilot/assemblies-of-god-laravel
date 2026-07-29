<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ban = DB::table('device_bans')->where('id', 1)->first();
if ($ban) {
    echo "Ban #1 is_active=".((int) $ban->is_active).' lifted_at='.($ban->lifted_at ?? 'null')."\n";
}

// Clear Laravel rate limiter keys for local IPs / known emails
$emails = [
    'finance.demo@agikenegbu.com',
    'demo@agikenegbu.com',
    'demo@agikenegbu.local',
    'finance.demo@agikenegbu.local',
    'admin@agikenegbu.com',
];
$ips = ['127.0.0.1', '::1'];
foreach ($emails as $email) {
    foreach ($ips as $ip) {
        $key = Str::transliterate(Str::lower($email).'|'.$ip);
        RateLimiter::clear($key);
    }
}

if (Schema::hasTable('rate_limits')) {
    $deleted = DB::table('rate_limits')->delete();
    echo "Cleared rate_limits rows: {$deleted}\n";
}

echo "Active bans: ".DB::table('device_bans')->where('is_active', 1)->count()."\n";
echo "Ready. Login: ".url('/admin/login')."\n";
echo "finance.demo@agikenegbu.com / FinanceDemo1!\n";
