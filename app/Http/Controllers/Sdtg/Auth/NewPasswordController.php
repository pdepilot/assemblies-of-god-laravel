<?php

namespace App\Http\Controllers\Sdtg\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

final class NewPasswordController extends Controller
{
    public function create(Request $request): View
    {
        return view('sdtg.auth.reset-password', [
            'request' => $request,
            'email' => (string) $request->string('email'),
            'token' => (string) $request->route('token'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email', 'max:191'],
            'password' => ['required', 'confirmed', 'min:8', Rules\Password::defaults()],
        ]);

        $status = Password::broker('sdtg_admins')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Admin $admin) use ($request): void {
                if (! $admin->canAccessPlatform(Admin::PLATFORM_SDTG)) {
                    return;
                }

                $admin->forceFill([
                    'password_hash' => Hash::make((string) $request->input('password')),
                    'force_password_change' => false,
                    'updated_at' => now(),
                ])->save();

                event(new PasswordReset($admin));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('sdtg.login')->with('status', 'Your password has been reset. You can sign in with your new password.')
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }
}
