<?php

namespace App\Providers;

use App\Auth\AdminUserProvider;
use App\Services\Website\PromotionReadService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('legacy-admin', function ($app, array $config) {
            return new AdminUserProvider($app['hash'], $config['model']);
        });

        View::composer('public.home', function ($view) {
            $view->with('promotionBanner', app(PromotionReadService::class)->activeBanner());
        });
    }
}
