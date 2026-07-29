<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class AdminSessionController
{
    public function __invoke(Request $request): JsonResponse
    {
        $action = (string) ($request->query('action') ?? $request->input('action') ?? 'ping');
        $lifetime = max(60, (int) config('portal.session_lifetime_seconds', 1800));
        $warning = max(30, (int) config('portal.session_warning_seconds', 1500));
        $loginRedirect = route('login', ['reason' => 'inactivity']);

        if ($action === 'ping') {
            if (! Auth::guard('admin')->check()) {
                return response()->json([
                    'success' => false,
                    'authenticated' => false,
                    'redirect' => $loginRedirect,
                ], 401);
            }

            $last = (int) $request->session()->get('admin_last_activity', time());
            $elapsed = max(0, time() - $last);
            $remaining = max(0, $lifetime - $elapsed);

            return response()->json([
                'success' => true,
                'authenticated' => true,
                'remaining' => $remaining,
                'warning_in' => max(0, $warning - $elapsed),
                'lifetime' => $lifetime,
                'warning_at' => $warning,
            ]);
        }

        if ($request->method() !== 'POST') {
            return response()->json(['success' => false, 'message' => 'Method not allowed.'], 405);
        }

        if ($action === 'timeout_logout') {
            if (Auth::guard('admin')->check()) {
                Auth::guard('admin')->logout();
            }
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'success' => true,
                'redirect' => $loginRedirect,
            ]);
        }

        if ($action === 'extend') {
            if (! Auth::guard('admin')->check()) {
                return response()->json([
                    'success' => false,
                    'authenticated' => false,
                    'redirect' => $loginRedirect,
                ], 401);
            }

            $request->session()->put('admin_last_activity', time());

            return response()->json([
                'success' => true,
                'message' => 'Session extended.',
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Unknown action.'], 400);
    }
}
