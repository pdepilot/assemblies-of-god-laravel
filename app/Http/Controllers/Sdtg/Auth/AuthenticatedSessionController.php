<?php

namespace App\Http\Controllers\Sdtg\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SdtgLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('sdtg.auth.login');
    }

    public function store(SdtgLoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        $request->session()->put('sdtg_last_activity', time());

        return redirect()->intended(route('sdtg.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('sdtg')->logout();
        $request->session()->forget('sdtg_last_activity');
        $request->session()->regenerateToken();

        $reason = (string) $request->query('reason', '');

        return $reason !== ''
            ? redirect()->route('sdtg.login', ['reason' => $reason])
            : redirect()->route('sdtg.login');
    }
}
