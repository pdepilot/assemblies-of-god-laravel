<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$svc = app(App\Services\PublicSite\PublicHomepageReadService::class);
$ref = new ReflectionClass($svc);
$method = $ref->getMethod('church');
$method->setAccessible(true);
$church = $method->invoke($svc);

echo 'config short_name: '.config('identity.public.short_name').PHP_EOL;
echo 'church short_name: '.($church['short_name'] ?? '').PHP_EOL;
echo 'church church_name: '.($church['church_name'] ?? '').PHP_EOL;
