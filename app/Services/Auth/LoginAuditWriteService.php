<?php

namespace App\Services\Auth;

use App\Models\Admin;
use App\Services\Security\DeviceBanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class LoginAuditWriteService
{
    public function __construct(
        private readonly DeviceBanService $bans,
    ) {}

    /**
     * @return array<string, mixed>|null Ban payload when a ban was applied
     */
    public function recordAttempt(
        Request $request,
        ?Admin $admin,
        bool $success,
        ?string $failureReason = null,
        string $source = DeviceBanService::SOURCE_ADMIN_LOGIN,
        ?string $identifier = null,
    ): ?array {
        if (! Schema::hasTable('login_attempts')) {
            return null;
        }

        $source = DeviceBanService::normalizeSource($source);
        $fingerprint = $this->deviceFingerprint($request);
        $ip = (string) $request->ip();
        $userAgent = (string) $request->userAgent();
        $browser = $this->browserInfo($userAgent);
        $emailAttempted = $identifier !== null
            ? trim($identifier)
            : trim((string) $request->input('email', $request->input('identifier', '')));

        $payload = [
            'admin_id' => $admin?->id,
            'email_attempted' => $emailAttempted,
            'device_fingerprint' => $fingerprint,
            'ip_address' => $ip,
            'user_agent' => $userAgent !== '' ? $userAgent : null,
            'browser_info' => $browser,
            'success' => $success,
            'failure_reason' => $failureReason,
            'created_at' => now(),
        ];
        if (Schema::hasColumn('login_attempts', 'source')) {
            $payload['source'] = $source;
        }

        DB::table('login_attempts')->insert($payload);

        if (Schema::hasTable('security_logs')) {
            DB::table('security_logs')->insert([
                'event_type' => $success ? 'login_success' : 'login_failed',
                'severity' => $success ? 'info' : 'warning',
                'admin_id' => $admin?->id,
                'device_fingerprint' => $fingerprint,
                'ip_address' => $ip,
                'user_agent' => $userAgent !== '' ? $userAgent : null,
                'message' => $success
                    ? 'Admin login successful'.($emailAttempted !== '' ? ': '.$emailAttempted : '.')
                    : 'Failed login attempt'.($emailAttempted !== '' ? ' for '.$emailAttempted : '').($failureReason ? ' ('.$failureReason.')' : ''),
                'metadata' => null,
                'created_at' => now(),
            ]);
        }

        if ($success) {
            return null;
        }

        $failures = $this->bans->consecutiveFailures($fingerprint, $source);
        $max = max(1, (int) config('portal.max_failed_attempts', 4));

        if ($failures >= $max) {
            return $this->bans->applyBan(
                $fingerprint,
                $ip,
                $userAgent,
                $browser,
                $emailAttempted,
                $source,
            );
        }

        return null;
    }

    public function recordSuccessfulLogin(Admin $admin, Request $request): void
    {
        $admin->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => (string) $request->ip(),
        ])->save();

        $this->recordAttempt($request, $admin, true, null, DeviceBanService::SOURCE_ADMIN_LOGIN);
    }

    public function deviceFingerprint(Request $request): string
    {
        return hash('sha256', implode('|', [
            (string) $request->userAgent(),
            (string) $request->header('Accept-Language', ''),
            (string) $request->ip(),
        ]));
    }

    public function browserInfo(string $userAgent): ?string
    {
        if ($userAgent === '') {
            return null;
        }

        if (preg_match('/(Chrome|Firefox|Safari|Edg|OPR)[\/\s]([\d.]+)/i', $userAgent, $matches)) {
            return trim($matches[1].' '.$matches[2]);
        }

        return null;
    }
}
