<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Hostinger public_html front controller
|--------------------------------------------------------------------------
|
| Hostinger Web Hosting cannot change the domain document root. This file
| lives next to artisan in the application root (public_html) and boots
| Laravel without using ../ paths.
|
| Keep public/index.php as well: php artisan serve and local setups still
| use Laravel's public directory.
|
*/

if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
