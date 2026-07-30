<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sdtg', 'sdtg.idle'])->group(function () {
    Route::get('/sdtg', [\App\Http\Controllers\Sdtg\DashboardController::class, 'index'])
        ->name('sdtg.dashboard');
    Route::get('/sdtg/registrations', [\App\Http\Controllers\Sdtg\RegistrationsController::class, 'index'])
        ->name('sdtg.registrations.index');
    Route::get('/sdtg/registrations/{registration}', [\App\Http\Controllers\Sdtg\RegistrationsController::class, 'show'])
        ->name('sdtg.registrations.show');
    Route::put('/sdtg/registrations/{registration}', [\App\Http\Controllers\Sdtg\RegistrationsController::class, 'update'])
        ->name('sdtg.registrations.update');
    Route::post('/sdtg/registrations/{registration}/approve-volunteer', [\App\Http\Controllers\Sdtg\RegistrationsController::class, 'approveVolunteer'])
        ->name('sdtg.registrations.approve-volunteer');
    Route::post('/sdtg/registrations/{registration}/decline-volunteer', [\App\Http\Controllers\Sdtg\RegistrationsController::class, 'declineVolunteer'])
        ->name('sdtg.registrations.decline-volunteer');

    Route::get('/sdtg/speakers', [\App\Http\Controllers\Sdtg\SpeakersController::class, 'index'])
        ->name('sdtg.speakers.index');
    Route::get('/sdtg/speakers/create', [\App\Http\Controllers\Sdtg\SpeakersController::class, 'create'])
        ->name('sdtg.speakers.create');
    Route::post('/sdtg/speakers', [\App\Http\Controllers\Sdtg\SpeakersController::class, 'store'])
        ->name('sdtg.speakers.store');
    Route::get('/sdtg/speakers/{speaker}', [\App\Http\Controllers\Sdtg\SpeakersController::class, 'show'])
        ->name('sdtg.speakers.show');
    Route::get('/sdtg/speakers/{speaker}/edit', [\App\Http\Controllers\Sdtg\SpeakersController::class, 'edit'])
        ->name('sdtg.speakers.edit');
    Route::put('/sdtg/speakers/{speaker}', [\App\Http\Controllers\Sdtg\SpeakersController::class, 'update'])
        ->name('sdtg.speakers.update');

    Route::get('/sdtg/gallery', [\App\Http\Controllers\Sdtg\GalleryController::class, 'index'])
        ->name('sdtg.gallery.index');
    Route::get('/sdtg/gallery/albums/create', [\App\Http\Controllers\Sdtg\GalleryController::class, 'createAlbum'])
        ->name('sdtg.gallery.albums.create');
    Route::post('/sdtg/gallery/albums', [\App\Http\Controllers\Sdtg\GalleryController::class, 'storeAlbum'])
        ->name('sdtg.gallery.albums.store');
    Route::get('/sdtg/gallery/albums/{album}/edit', [\App\Http\Controllers\Sdtg\GalleryController::class, 'editAlbum'])
        ->name('sdtg.gallery.albums.edit');
    Route::put('/sdtg/gallery/albums/{album}', [\App\Http\Controllers\Sdtg\GalleryController::class, 'updateAlbum'])
        ->name('sdtg.gallery.albums.update');
    Route::delete('/sdtg/gallery/albums/{album}', [\App\Http\Controllers\Sdtg\GalleryController::class, 'destroyAlbum'])
        ->name('sdtg.gallery.albums.destroy');
    Route::get('/sdtg/gallery/items/create', [\App\Http\Controllers\Sdtg\GalleryController::class, 'createItem'])
        ->name('sdtg.gallery.items.create');
    Route::post('/sdtg/gallery/items', [\App\Http\Controllers\Sdtg\GalleryController::class, 'storeItem'])
        ->name('sdtg.gallery.items.store');
    Route::get('/sdtg/gallery/items/{item}/edit', [\App\Http\Controllers\Sdtg\GalleryController::class, 'editItem'])
        ->name('sdtg.gallery.items.edit');
    Route::put('/sdtg/gallery/items/{item}', [\App\Http\Controllers\Sdtg\GalleryController::class, 'updateItem'])
        ->name('sdtg.gallery.items.update');
    Route::delete('/sdtg/gallery/items/{item}', [\App\Http\Controllers\Sdtg\GalleryController::class, 'destroyItem'])
        ->name('sdtg.gallery.items.destroy');
    Route::post('/sdtg/gallery/editions', [\App\Http\Controllers\Sdtg\GalleryController::class, 'storeEdition'])
        ->name('sdtg.gallery.editions.store');
    Route::put('/sdtg/gallery/editions/{edition}', [\App\Http\Controllers\Sdtg\GalleryController::class, 'updateEdition'])
        ->name('sdtg.gallery.editions.update');
    Route::delete('/sdtg/gallery/editions/{edition}', [\App\Http\Controllers\Sdtg\GalleryController::class, 'destroyEdition'])
        ->name('sdtg.gallery.editions.destroy');
    Route::put('/sdtg/gallery/memories/{memory}', [\App\Http\Controllers\Sdtg\GalleryController::class, 'updateMemory'])
        ->name('sdtg.gallery.memories.update');

    Route::get('/sdtg/media-library', [\App\Http\Controllers\Sdtg\MediaLibraryController::class, 'index'])
        ->name('sdtg.media-library.index');
    Route::post('/sdtg/media-library/folders', [\App\Http\Controllers\Sdtg\MediaLibraryController::class, 'storeFolder'])
        ->name('sdtg.media-library.folders.store');
    Route::delete('/sdtg/media-library/folders/{folder}', [\App\Http\Controllers\Sdtg\MediaLibraryController::class, 'destroyFolder'])
        ->name('sdtg.media-library.folders.destroy');
    Route::get('/sdtg/media-library/assets/create', [\App\Http\Controllers\Sdtg\MediaLibraryController::class, 'createAsset'])
        ->name('sdtg.media-library.assets.create');
    Route::post('/sdtg/media-library/assets', [\App\Http\Controllers\Sdtg\MediaLibraryController::class, 'storeAsset'])
        ->name('sdtg.media-library.assets.store');
    Route::get('/sdtg/media-library/assets/{asset}/edit', [\App\Http\Controllers\Sdtg\MediaLibraryController::class, 'editAsset'])
        ->name('sdtg.media-library.assets.edit');
    Route::put('/sdtg/media-library/assets/{asset}', [\App\Http\Controllers\Sdtg\MediaLibraryController::class, 'updateAsset'])
        ->name('sdtg.media-library.assets.update');
    Route::delete('/sdtg/media-library/assets/{asset}', [\App\Http\Controllers\Sdtg\MediaLibraryController::class, 'destroyAsset'])
        ->name('sdtg.media-library.assets.destroy');

    Route::get('/sdtg/announcements', [\App\Http\Controllers\Sdtg\AnnouncementsController::class, 'index'])
        ->name('sdtg.announcements.index');
    Route::get('/sdtg/announcements/create', [\App\Http\Controllers\Sdtg\AnnouncementsController::class, 'create'])
        ->name('sdtg.announcements.create');
    Route::post('/sdtg/announcements', [\App\Http\Controllers\Sdtg\AnnouncementsController::class, 'store'])
        ->name('sdtg.announcements.store');
    Route::get('/sdtg/announcements/{announcement}', [\App\Http\Controllers\Sdtg\AnnouncementsController::class, 'show'])
        ->name('sdtg.announcements.show');
    Route::get('/sdtg/announcements/{announcement}/edit', [\App\Http\Controllers\Sdtg\AnnouncementsController::class, 'edit'])
        ->name('sdtg.announcements.edit');
    Route::put('/sdtg/announcements/{announcement}', [\App\Http\Controllers\Sdtg\AnnouncementsController::class, 'update'])
        ->name('sdtg.announcements.update');

    Route::get('/sdtg/content', [\App\Http\Controllers\Sdtg\ContentController::class, 'index'])
        ->name('sdtg.content.index');
    Route::get('/sdtg/content/{section}/edit', [\App\Http\Controllers\Sdtg\ContentController::class, 'edit'])
        ->where('section', '[a-z0-9_]+')
        ->name('sdtg.content.edit');
    Route::put('/sdtg/content/{section}', [\App\Http\Controllers\Sdtg\ContentController::class, 'update'])
        ->where('section', '[a-z0-9_]+')
        ->name('sdtg.content.update');
    Route::post('/sdtg/content/{section}/reset', [\App\Http\Controllers\Sdtg\ContentController::class, 'reset'])
        ->where('section', '[a-z0-9_]+')
        ->name('sdtg.content.reset');

    Route::get('/sdtg/community', [\App\Http\Controllers\Sdtg\CommunityController::class, 'index'])
        ->name('sdtg.community.index');
    Route::post('/sdtg/community/testimonials/{testimonial}', [\App\Http\Controllers\Sdtg\CommunityController::class, 'updateTestimonial'])
        ->name('sdtg.community.testimonials.update');
    Route::post('/sdtg/community/prayer/{prayer}', [\App\Http\Controllers\Sdtg\CommunityController::class, 'updatePrayer'])
        ->name('sdtg.community.prayer.update');
    Route::put('/sdtg/community/memories/{memory}', [\App\Http\Controllers\Sdtg\CommunityController::class, 'updateMemory'])
        ->name('sdtg.community.memories.update');

    Route::get('/sdtg/livestream', [\App\Http\Controllers\Sdtg\LivestreamController::class, 'index'])
        ->name('sdtg.livestream.index');
    Route::post('/sdtg/livestream/toggle', [\App\Http\Controllers\Sdtg\LivestreamController::class, 'toggleLive'])
        ->name('sdtg.livestream.toggle');
    Route::post('/sdtg/livestream/viewers', [\App\Http\Controllers\Sdtg\LivestreamController::class, 'updateViewers'])
        ->name('sdtg.livestream.viewers');
    Route::post('/sdtg/livestream/settings', [\App\Http\Controllers\Sdtg\LivestreamController::class, 'updateSettings'])
        ->name('sdtg.livestream.settings');

    Route::get('/sdtg/admins', [\App\Http\Controllers\Sdtg\AdminsController::class, 'index'])
        ->name('sdtg.admins.index');
    Route::put('/sdtg/admins/{admin}/platform-access', [\App\Http\Controllers\Sdtg\AdminsController::class, 'updatePlatformAccess'])
        ->whereNumber('admin')
        ->name('sdtg.admins.platform-access');
});
