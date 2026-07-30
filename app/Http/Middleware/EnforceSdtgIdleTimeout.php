<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnforceSdtgIdleTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('sdtg')->check()) {
            return $next($request);
        }

        $lifetime = max(60, (int) config('portal.session_lifetime_seconds', 1800));
        $lastActivity = (int) $request->session()->get('sdtg_last_activity', 0);
        $action = (string) ($request->query('action') ?? $request->input('action') ?? '');
        $isSessionPing = $request->routeIs('sdtg.session') && $action === 'ping';
        $isTimeoutLogout = $request->routeIs('sdtg.session') && $action === 'timeout_logout';

        if ($lastActivity > 0 && (time() - $lastActivity) > $lifetime && ! $isTimeoutLogout) {
            Auth::guard('sdtg')->logout();
            $request->session()->forget('sdtg_last_activity');

            if ($request->expectsJson() || $request->ajax() || $request->routeIs('sdtg.session')) {
                return response()->json([
                    'success' => false,
                    'authenticated' => false,
                    'message' => 'Session expired due to inactivity.',
                    'redirect' => route('sdtg.login', ['reason' => 'inactivity']),
                ], 401);
            }

            return redirect()->route('sdtg.login', ['reason' => 'inactivity']);
        }

        if (! $isSessionPing && ! $isTimeoutLogout) {
            $request->session()->put('sdtg_last_activity', time());
        }

        return $next($request);
    }
}
