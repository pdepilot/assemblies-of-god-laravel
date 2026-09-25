<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->prependToGroup('web', \App\Http\Middleware\AlignPublicRootUrl::class);
        $middleware->prependToGroup('api', \App\Http\Middleware\AlignPublicRootUrl::class);
        $middleware->appendToGroup('api', \App\Http\Middleware\TrafficBeaconCors::class);
        $middleware->alias([
            'admin.idle' => \App\Http\Middleware\EnforceAdminIdleTimeout::class,
            'admin.rbac' => \App\Http\Middleware\EnforceRbacPageAccess::class,
        ]);
        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));
        $middleware->redirectUsersTo(function (Request $request) {
            $admin = auth('admin')->user();
            if ($admin instanceof \App\Models\Admin) {
                return app(\App\Services\Portal\PortalNavService::class)->homeHrefForAdmin($admin);
            }

            return route('dashboard');
        });
        $middleware->validateCsrfTokens(except: [
            'api/member-portal-*.php',
            'api/member-self-register.php',
            'api/submit-portal-registration.php',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            if ($request->is('admin/*', 'erp/*')) {
                return null;
            }

            $payload = app(\App\Services\PublicSite\PublicHomepageReadService::class)->chrome();
            $seo = app(\App\Services\Website\SeoReadService::class)->forKey('home', url('/'));
            $seo['title'] = 'Page Not Found | '.config('identity.public.short_name', 'AGC Ikenegbu');
            $seo['meta_description'] = 'The page you requested could not be found on the church website.';

            return response()->view('errors.404', [
                'allowAds' => false,
                'church' => $payload['church'],
                'seo' => $seo,
                'schemaGraphs' => [],
                'legacy_api_base' => $payload['legacy_api_base'] ?? '',
                'traffic_beacon_url' => $payload['traffic_beacon_url'] ?? '',
                'testimonySourcePage' => '404',
            ], 404);
        });
    })->create();
