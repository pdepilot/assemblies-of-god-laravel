<?php

use App\Services\Security\DeviceBanService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== All device_bans (latest 20) ===\n";
$rows = DB::table('device_bans')->orderByDesc('id')->limit(20)->get();
foreach ($rows as $row) {
    echo json_encode([
        'id' => $row->id,
        'active' => (int) $row->is_active,
        'level' => $row->ban_level,
        'source' => $row->source ?? null,
        'ip' => $row->ip_address,
        'fp' => substr((string) $row->device_fingerprint, 0, 16).'…',
        'expires' => $row->ban_expires,
        'lifted_at' => $row->lifted_at,
    ], JSON_UNESCAPED_SLASHES)."\n";
}

$bans = app(DeviceBanService::class);
$adminId = (int) (DB::table('admins')->where('role', 'super_admin')->orderBy('id')->value('id') ?: 1);

// Lift every currently active ban
$active = DB::table('device_bans')->where('is_active', 1)->get();
echo "\nActive to lift: ".$active->count()."\n";
foreach ($active as $ban) {
    $ok = $bans->liftBan((int) $ban->id, $adminId);
    echo 'Lifted #'.$ban->id.' level='.$ban->ban_level.' => '.($ok ? 'ok' : 'fail')."\n";
}

// Also force-clear any still marked active (safety net)
$forced = DB::table('device_bans')->where('is_active', 1)->update([
    'is_active' => 0,
    'lifted_at' => now(),
]);
echo "Force-cleared remaining active: {$forced}\n";

// Clear custom rate_limits table + Laravel limiter keys
if (Schema::hasTable('rate_limits')) {
    echo 'rate_limits wiped: '.DB::table('rate_limits')->delete()."\n";
}

$emails = [
    'finance.demo@agikenegbu.com',
    'finance.demo@agikenegbu.local',
    'demo@agikenegbu.com',
    'demo@agikenegbu.local',
    'admin@agikenegbu.com',
    'mrrayjohnson2@gmail.com',
];
foreach ($emails as $email) {
    foreach (['127.0.0.1', '::1'] as $ip) {
        RateLimiter::clear(Str::transliterate(Str::lower($email).'|'.$ip));
    }
}

// Show what getActiveBan would return for recent fingerprints
$fps = DB::table('device_bans')
    ->orderByDesc('id')
    ->limit(5)
    ->pluck('device_fingerprint')
    ->unique()
    ->values();

echo "\ngetActiveBan checks:\n";
foreach ($fps as $fp) {
    foreach (DeviceBanService::SOURCES as $source) {
        $hit = $bans->getActiveBan((string) $fp, $source);
        echo substr((string) $fp, 0, 12)."… source={$source} => ".($hit ? ('BAN#'.$hit['id'].' lvl'.$hit['ban_level']) : 'clear')."\n";
    }
}

echo "\nActive bans now: ".DB::table('device_bans')->where('is_active', 1)->count()."\n";
echo "Login: ".url('/admin/login')."\n";
echo "finance.demo@agikenegbu.com / FinanceDemo1!\n";
