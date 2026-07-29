<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Admin;
use App\Services\Portal\PortalNavService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, PortalNavService $nav): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        $request->session()->put('admin_last_activity', time());

        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();
        $home = $nav->homeHrefForAdmin($admin);

        return redirect()->intended($home);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        $reason = (string) $request->query('reason', '');

        return $reason !== ''
            ? redirect()->route('login', ['reason' => $reason])
            : redirect()->route('login');
    }
}
