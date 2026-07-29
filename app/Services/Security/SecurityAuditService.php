<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Central writer for security_logs entries that power the CMS notification bell.
 */
final class SecurityAuditService
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function log(
        string $eventType,
        string $message,
        ?int $adminId = null,
        string $severity = 'info',
        ?array $metadata = null,
    ): void {
        if (! Schema::hasTable('security_logs')) {
            return;
        }

        $eventType = trim($eventType);
        $message = trim($message);
        if ($eventType === '' || $message === '') {
            return;
        }

        $payload = [
            'event_type' => mb_substr($eventType, 0, 80),
            'severity' => mb_substr($severity !== '' ? $severity : 'info', 0, 20),
            'admin_id' => $adminId !== null && $adminId > 0 ? $adminId : null,
            'device_fingerprint' => null,
            'ip_address' => request()?->ip(),
            'user_agent' => request() ? (string) request()->userAgent() : null,
            'message' => mb_substr($message, 0, 2000),
            'metadata' => null,
            'created_at' => now(),
        ];

        if ($metadata !== null && Schema::hasColumn('security_logs', 'metadata')) {
            try {
                $payload['metadata'] = json_encode($metadata, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $payload['metadata'] = null;
            }
        }

        DB::table('security_logs')->insert($payload);
    }
}
