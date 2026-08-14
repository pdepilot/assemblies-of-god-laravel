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
    })->create();
