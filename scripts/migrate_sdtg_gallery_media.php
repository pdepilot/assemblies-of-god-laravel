<?php

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

if (! is_link(public_path('storage')) && ! is_dir(public_path('storage'))) {
    Artisan::call('storage:link');
    echo Artisan::output();
}

$destDir = public_path('site/uploads/sdtg-gallery');
File::ensureDirectoryExists($destDir);

$srcDir = storage_path('app/public/sdtg-gallery');
if (is_dir($srcDir)) {
    foreach (File::files($srcDir) as $file) {
        $target = $destDir.DIRECTORY_SEPARATOR.$file->getFilename();
        if (! is_file($target)) {
            File::copy($file->getPathname(), $target);
        }
    }
}

$items = DB::update("UPDATE sdtg_gallery_items SET file_path = CONCAT('uploads/', file_path) WHERE file_path LIKE 'sdtg-gallery/%'");
$thumbs = DB::update("UPDATE sdtg_gallery_items SET thumbnail_path = CONCAT('uploads/', thumbnail_path) WHERE thumbnail_path LIKE 'sdtg-gallery/%'");
$covers = DB::update("UPDATE sdtg_gallery_albums SET cover_path = CONCAT('uploads/', cover_path) WHERE cover_path LIKE 'sdtg-gallery/%'");

echo "Migrated paths: items={$items} thumbs={$thumbs} covers={$covers}\n";
