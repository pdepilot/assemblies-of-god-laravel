<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

if (! Schema::hasTable('device_bans')) {
    echo "device_bans table missing\n";
    exit(1);
}

$active = DB::table('device_bans')
    ->where('is_active', 1)
    ->orderByDesc('id')
    ->get();

echo "Active bans: ".$active->count()."\n";
foreach ($active as $ban) {
    echo json_encode([
        'id' => $ban->id,
        'source' => $ban->source ?? null,
        'ip' => $ban->ip_address,
        'fingerprint' => $ban->device_fingerprint,
        'level' => $ban->ban_level,
        'expires' => $ban->ban_expires,
        'created' => $ban->created_at,
    ], JSON_UNESCAPED_SLASHES)."\n";
}

$recent = DB::table('login_attempts')
    ->orderByDesc('id')
    ->limit(15)
    ->get(['id', 'email_attempted', 'success', 'failure_reason', 'ip_address', 'device_fingerprint', 'source', 'created_at']);

echo "\nRecent login attempts:\n";
foreach ($recent as $row) {
    echo json_encode($row, JSON_UNESCAPED_SLASHES)."\n";
}
