<?php

use App\Http\Controllers\Api\TrackTrafficController;
use Illuminate\Support\Facades\Route;

Route::post('/track-traffic', [TrackTrafficController::class, 'store']);
Route::post('/track-traffic.php', [TrackTrafficController::class, 'store']);
Route::options('/track-traffic', fn () => response()->noContent());
Route::options('/track-traffic.php', fn () => response()->noContent());
