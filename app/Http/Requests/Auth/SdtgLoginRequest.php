<?php

namespace App\Http\Requests\Auth;

use App\Models\Admin;
use App\Services\Auth\LoginAuditWriteService;
use App\Services\Security\DeviceBanService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SdtgLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $audit = app(LoginAuditWriteService::class);
        $bans = app(DeviceBanService::class);
        $fingerprint = $audit->deviceFingerprint($this);

        $source = DeviceBanService::SOURCE_SDTG_LOGIN;
        $activeBan = $bans->getActiveBan($fingerprint, $source);
        if ($activeBan !== null) {
            $expires = \Illuminate\Support\Carbon::parse((string) $activeBan['ban_expires'])->format('F j, Y \a\t g:i A');
            $message = ((int) $activeBan['ban_level'] >= 2)
                ? 'This device has been blocked for 90 days due to repeated unauthorized access attempts.'
                : 'This device has been temporarily blocked for security reasons. Access will be restored on '.$expires.'.';

            throw ValidationException::withMessages(['email' => $message]);
        }

        $credentials = [
            'email' => $this->string('email')->lower()->value(),
            'password' => $this->string('password')->value(),
            'is_active' => 1,
            'account_status' => 'active',
        ];

        if (! Auth::guard('sdtg')->attempt($credentials, false)) {
            RateLimiter::hit($this->throttleKey());

            $admin = Admin::query()->where('email', $credentials['email'])->first();

            if ($admin !== null && ((string) $admin->account_status === 'suspended' || ! $admin->is_active)) {
                $audit->recordAttempt($this, $admin, false, 'account_suspended', $source);

                throw ValidationException::withMessages([
                    'email' => 'This administrator account has been suspended. Contact a Super Admin.',
                ]);
            }

            $banResult = $audit->recordAttempt($this, $admin, false, 'invalid_credentials', $source);

            if (is_array($banResult) && ! empty($banResult['banned'])) {
                throw ValidationException::withMessages([
                    'email' => (string) ($banResult['message'] ?? 'This device has been temporarily restricted for security reasons.'),
                ]);
            }

            $failures = $bans->consecutiveFailures($fingerprint, $source);
            $warningAt = max(1, (int) config('portal.warning_at_attempt', 3));
            if ($failures === $warningAt) {
                throw ValidationException::withMessages([
                    'email' => 'Warning: One login attempt remaining before temporary device restriction.',
                ]);
            }

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        /** @var Admin $admin */
        $admin = Auth::guard('sdtg')->user();
        if (! $admin->canAccessPlatform(Admin::PLATFORM_SDTG)) {
            Auth::guard('sdtg')->logout();
            RateLimiter::hit($this->throttleKey());
            $audit->recordAttempt($this, $admin, false, 'platform_denied', $source);

            throw ValidationException::withMessages([
                'email' => 'This account is not authorized for the SDTG Management Platform.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        $audit->recordSuccessfulLogin($admin, $this);
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate('sdtg|'.Str::lower($this->string('email')).'|'.$this->ip());
    }
}
