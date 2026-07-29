<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:191'],
        ]);

        $email = strtolower(trim((string) $request->input('email')));
        $genericStatus = 'If that email matches an active administrator account, a password reset link has been sent.';

        $admin = Admin::query()
            ->where('email', $email)
            ->where('is_active', 1)
            ->where('account_status', 'active')
            ->first();

        if ($admin === null) {
            return back()->with('status', $genericStatus);
        }

        $status = Password::broker('admins')->sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
        }

        return back()->with('status', $genericStatus);
    }
}
