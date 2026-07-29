<?php

use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$keys = DB::table('role_permissions as rp')
    ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
    ->where('rp.role_id', 3)
    ->orderBy('p.permission_key')
    ->pluck('p.permission_key');

echo "finance_administrator (#3) permissions (".count($keys)."):\n";
foreach ($keys as $key) {
    echo " - {$key}\n";
}

$raymond = DB::table('admins')->where('id', 4)->first();
echo "\nRaymond assignments:\n";
$assigned = DB::table('admin_role_assignments as ara')
    ->join('roles as r', 'r.id', '=', 'ara.role_id')
    ->where('ara.admin_id', 4)
    ->get(['r.id', 'r.slug', 'r.name']);
print_r($assigned->toArray());
