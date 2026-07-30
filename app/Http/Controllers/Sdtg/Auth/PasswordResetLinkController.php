<?php

namespace App\Http\Controllers\Sdtg\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

final class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('sdtg.auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:191'],
        ]);

        $email = strtolower(trim((string) $request->input('email')));
        $genericStatus = 'If that email matches an active SDTG administrator account, a password reset link has been sent.';

        $admin = Admin::query()
            ->where('email', $email)
            ->where('is_active', 1)
            ->where('account_status', 'active')
            ->first();

        if ($admin === null || ! $admin->canAccessPlatform(Admin::PLATFORM_SDTG)) {
            return back()->with('status', $genericStatus);
        }

        app()->instance('auth.password_reset_platform', Admin::PLATFORM_SDTG);

        $status = Password::broker('sdtg_admins')->sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
        }

        return back()->with('status', $genericStatus);
    }
}
