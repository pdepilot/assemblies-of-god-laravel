<?php

/**
 * Print Site Traffic dashboard KPIs for today (no admin login required).
 * Usage: php tools/check-site-traffic-dashboard.php
 */

use App\Services\Analytics\SiteTrafficReadService;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = $app->make(SiteTrafficReadService::class);
$today = now()->format('Y-m-d');
$dashboard = $service->getDashboard($today, $today, 'all');

echo json_encode([
    'date' => $today,
    'kpis' => $dashboard['kpis'],
    'recent_sessions' => array_slice($dashboard['recent_sessions'], 0, 5),
    'top_pages' => array_slice($dashboard['top_pages'], 0, 5),
], JSON_PRETTY_PRINT) . PHP_EOL;
