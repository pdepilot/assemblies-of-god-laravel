<?php

use App\Http\Controllers\Sdtg\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Sdtg\Auth\NewPasswordController;
use App\Http\Controllers\Sdtg\Auth\PasswordResetLinkController;
use App\Http\Controllers\Sdtg\Auth\SessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest:sdtg')->prefix('sdtg')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('sdtg.login');

    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->name('sdtg.login.store');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('sdtg.password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('sdtg.password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('sdtg.password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('sdtg.password.store');
});

Route::middleware(['auth:sdtg', 'sdtg.idle'])->prefix('sdtg')->group(function () {
    Route::match(['get', 'post'], 'logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('sdtg.logout');

    Route::match(['get', 'post'], 'handlers/session-handler', SessionController::class)
        ->name('sdtg.session');
});
