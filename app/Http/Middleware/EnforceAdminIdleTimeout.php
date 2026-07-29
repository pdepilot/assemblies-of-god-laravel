<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnforceAdminIdleTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('admin')->check()) {
            return $next($request);
        }

        $lifetime = max(60, (int) config('portal.session_lifetime_seconds', 1800));
        $lastActivity = (int) $request->session()->get('admin_last_activity', 0);
        $action = (string) ($request->query('action') ?? $request->input('action') ?? '');
        $isSessionPing = $request->routeIs('admin.session') && $action === 'ping';
        $isTimeoutLogout = $request->routeIs('admin.session') && $action === 'timeout_logout';

        if ($lastActivity > 0 && (time() - $lastActivity) > $lifetime && ! $isTimeoutLogout) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson() || $request->ajax() || $request->routeIs('admin.session')) {
                return response()->json([
                    'success' => false,
                    'authenticated' => false,
                    'message' => 'Session expired due to inactivity.',
                    'redirect' => route('login', ['reason' => 'inactivity']),
                ], 401);
            }

            return redirect()->route('login', ['reason' => 'inactivity']);
        }

        if (! $isSessionPing && ! $isTimeoutLogout) {
            $request->session()->put('admin_last_activity', time());
        }

        return $next($request);
    }
}
