<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$email = $argv[1] ?? 'admin@agikenegbu.com';
$hash = (string) Illuminate\Support\Facades\DB::table('admins')
    ->where('email', $email)
    ->value('password_hash');

echo json_encode([
    'email' => $email,
    'length' => strlen($hash),
    'prefix' => substr($hash, 0, 7),
    'is_bcrypt' => str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2a$') || str_starts_with($hash, '$2b$'),
    'is_argon2' => str_starts_with($hash, '$argon2'),
], JSON_PRETTY_PRINT) . PHP_EOL;
