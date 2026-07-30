<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('api', \App\Http\Middleware\TrafficBeaconCors::class);
        $middleware->alias([
            'admin.idle' => \App\Http\Middleware\EnforceAdminIdleTimeout::class,
            'sdtg.idle' => \App\Http\Middleware\EnforceSdtgIdleTimeout::class,
            'admin.rbac' => \App\Http\Middleware\EnforceRbacPageAccess::class,
        ]);
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin/sdtg') || $request->is('admin/sdtg/*')) {
                return route('sdtg.login');
            }

            return route('login');
        });
        $middleware->redirectUsersTo(function (Request $request) {
            if ($request->is('admin/sdtg/login') || $request->is('admin/sdtg/forgot-password') || $request->is('admin/sdtg/reset-password*')) {
                return route('sdtg.dashboard');
            }

            $admin = auth('admin')->user();
            if ($admin instanceof \App\Models\Admin) {
                return app(\App\Services\Portal\PortalNavService::class)->homeHrefForAdmin($admin);
            }

            if (auth('sdtg')->check()) {
                return route('sdtg.dashboard');
            }

            return route('dashboard');
        });
        $middleware->validateCsrfTokens(except: [
            'api/member-portal-*.php',
            'api/member-self-register.php',
            'api/sdtg-memory',
            'api/submit-sdtg-memory.php',
            'api/submit-portal-registration.php',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
