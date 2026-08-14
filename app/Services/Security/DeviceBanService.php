<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class DeviceBanService
{
    public const SOURCE_ADMIN_LOGIN = 'admin/login';

    public const SOURCE_MEMBER_PORTAL_LOGIN = 'member-portal/login';

    /** @var list<string> */
    public const SOURCES = [
        self::SOURCE_ADMIN_LOGIN,
        self::SOURCE_MEMBER_PORTAL_LOGIN,
    ];

    public function __construct(
        private readonly IpGeoLookupService $geo,
    ) {}

    public static function normalizeSource(?string $source): string
    {
        $source = trim((string) $source);

        return in_array($source, self::SOURCES, true)
            ? $source
            : self::SOURCE_ADMIN_LOGIN;
    }

    public static function sourceLabel(string $source): string
    {
        return match (self::normalizeSource($source)) {
            self::SOURCE_MEMBER_PORTAL_LOGIN => 'Member portal login',
            default => 'Admin login',
        };
    }

    public function expireBans(): void
    {
        if (! Schema::hasTable('device_bans')) {
            return;
        }

        DB::table('device_bans')
            ->where('is_active', 1)
            ->where('ban_expires', '<=', now())
            ->update([
                'is_active' => 0,
                'lifted_at' => now(),
            ]);
    }

    /** @return array<string, mixed>|null */
    public function getActiveBan(string $fingerprint, string $source = self::SOURCE_ADMIN_LOGIN): ?array
    {
        if (! Schema::hasTable('device_bans')) {
            return null;
        }

        $this->expireBans();
        $source = self::normalizeSource($source);

        $query = DB::table('device_bans')
            ->where('device_fingerprint', $fingerprint)
            ->where('is_active', 1)
            ->where('ban_expires', '>', now());

        if ($this->hasSourceColumn()) {
            $query->where('source', $source);
        }

        $row = $query->orderByDesc('ban_expires')->first();

        return $row ? $this->formatBan((array) $row) : null;
    }

    /**
     * @return array{items: list<array<string, mixed>>, active: int, expired: int, total: int, page: int, pages: int}
     */
    public function listBans(
        string $status = 'active',
        string $query = '',
        int $page = 1,
        int $perPage = 25,
        string $source = 'all',
    ): array {
        if (! Schema::hasTable('device_bans')) {
            return ['items' => [], 'active' => 0, 'expired' => 0, 'total' => 0, 'page' => 1, 'pages' => 1];
        }

        $this->expireBans();

        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $builder = DB::table('device_bans');

        if ($status === 'active') {
            $builder->where('is_active', 1)->where('ban_expires', '>', now());
        } elseif ($status === 'lifted') {
            $builder->where(function ($q) {
                $q->where('is_active', 0)
                    ->orWhere('ban_expires', '<=', now());
            });
        }

        if ($this->hasSourceColumn() && in_array($source, self::SOURCES, true)) {
            $builder->where('source', $source);
        }

        $query = trim($query);
        if ($query !== '') {
            $like = '%'.$query.'%';
            $builder->where(function ($q) use ($like) {
                $q->where('ip_address', 'like', $like)
                    ->orWhere('browser_info', 'like', $like)
                    ->orWhere('user_agent', 'like', $like)
                    ->orWhere('device_fingerprint', 'like', $like);
            });
        }

        $total = (int) (clone $builder)->count();
        $rows = (clone $builder)
            ->orderByDesc('created_at')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn ($row) => $this->formatBan((array) $row, true))
            ->all();

        $activeQuery = DB::table('device_bans')
            ->where('is_active', 1)
            ->where('ban_expires', '>', now());
        $allQuery = DB::table('device_bans');
        if ($this->hasSourceColumn() && in_array($source, self::SOURCES, true)) {
            $activeQuery->where('source', $source);
            $allQuery->where('source', $source);
        }

        $active = (int) $activeQuery->count();
        $all = (int) $allQuery->count();

        return [
            'items' => $rows,
            'active' => $active,
            'expired' => max(0, $all - $active),
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /** @return array<string, mixed>|null */
    public function getBan(int $id): ?array
    {
        if (! Schema::hasTable('device_bans')) {
            return null;
        }

        $this->expireBans();
        $row = DB::table('device_bans')->where('id', $id)->first();

        return $row ? $this->formatBan((array) $row, true, true) : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function applyBan(
        string $fingerprint,
        string $ip,
        string $userAgent,
        ?string $browserInfo,
        ?string $emailAttempted = null,
        string $source = self::SOURCE_ADMIN_LOGIN,
    ): array {
        if (! Schema::hasTable('device_bans')) {
            throw new InvalidArgumentException('Ban storage is not available.');
        }

        $source = self::normalizeSource($source);

        $previousQuery = DB::table('device_bans')->where('device_fingerprint', $fingerprint);
        if ($this->hasSourceColumn()) {
            $previousQuery->where('source', $source);
        }
        $previous = (int) $previousQuery->count();
        $banLevel = $previous >= 1 ? 2 : 1;
        $days = $banLevel === 2
            ? (int) config('portal.ban_days_level_2', 90)
            : (int) config('portal.ban_days_level_1', 15);

        $start = now();
        $expires = now()->addDays($days);

        $payload = [
            'device_fingerprint' => $fingerprint,
            'ip_address' => $ip,
            'user_agent' => $userAgent !== '' ? $userAgent : null,
            'browser_info' => $browserInfo,
            'ban_level' => $banLevel,
            'ban_count' => $previous + 1,
            'ban_start' => $start,
            'ban_expires' => $expires,
            'is_active' => 1,
            'lifted_at' => null,
            'created_at' => $start,
        ];
        if ($this->hasSourceColumn()) {
            $payload['source'] = $source;
        }

        $id = (int) DB::table('device_bans')->insertGetId($payload);

        if (Schema::hasTable('security_logs')) {
            DB::table('security_logs')->insert([
                'event_type' => 'device_banned',
                'severity' => 'critical',
                'admin_id' => null,
                'device_fingerprint' => $fingerprint,
                'ip_address' => $ip,
                'user_agent' => $userAgent !== '' ? $userAgent : null,
                'message' => 'Device banned after repeated failed login attempts ('.self::sourceLabel($source).').',
                'metadata' => json_encode([
                    'ban_id' => $id,
                    'ban_level' => $banLevel,
                    'ban_days' => $days,
                    'email_attempted' => $emailAttempted,
                    'source' => $source,
                ]),
                'created_at' => $start,
            ]);
        }

        $message = $banLevel === 2
            ? 'This device has been blocked for 90 days due to repeated unauthorized access attempts.'
            : 'This device has been temporarily restricted for security reasons. Access will be restored automatically after '.$days.' days.';

        return [
            'banned' => true,
            'ban_id' => $id,
            'ban_level' => $banLevel,
            'ban_days' => $days,
            'ban_expires' => $expires->toIso8601String(),
            'ban_expires_formatted' => $expires->format('F j, Y \a\t g:i A'),
            'source' => $source,
            'message' => $message,
        ];
    }

    public function liftBan(int $banId, int $adminId): bool
    {
        if (! Schema::hasTable('device_bans')) {
            return false;
        }

        $updated = DB::table('device_bans')
            ->where('id', $banId)
            ->where('is_active', 1)
            ->update([
                'is_active' => 0,
                'lifted_at' => now(),
            ]);

        if ($updated === 0) {
            return false;
        }

        $ban = DB::table('device_bans')->where('id', $banId)->first();

        if (Schema::hasTable('security_logs')) {
            DB::table('security_logs')->insert([
                'event_type' => 'ban_lifted',
                'severity' => 'info',
                'admin_id' => $adminId > 0 ? $adminId : null,
                'device_fingerprint' => $ban->device_fingerprint ?? null,
                'ip_address' => $ban->ip_address ?? null,
                'user_agent' => $ban->user_agent ?? null,
                'message' => 'Device ban manually lifted by administrator.',
                'metadata' => json_encode([
                    'ban_id' => $banId,
                    'source' => $ban->source ?? self::SOURCE_ADMIN_LOGIN,
                ]),
                'created_at' => now(),
            ]);
        }

        return true;
    }

    public function consecutiveFailures(string $fingerprint, string $source = self::SOURCE_ADMIN_LOGIN): int
    {
        if (! Schema::hasTable('login_attempts')) {
            return 0;
        }

        $source = self::normalizeSource($source);
        $query = DB::table('login_attempts')
            ->where('device_fingerprint', $fingerprint);

        if ($this->hasLoginAttemptSourceColumn()) {
            $query->where('source', $source);
        }

        $rows = $query
            ->orderByDesc('created_at')
            ->limit(20)
            ->pluck('success');

        $count = 0;
        foreach ($rows as $success) {
            if ((int) $success === 1) {
                break;
            }
            $count++;
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function formatBan(array $row, bool $withGeo = false, bool $withAttempts = false): array
    {
        $active = ! empty($row['is_active']) && strtotime((string) $row['ban_expires']) > time();
        $level = (int) ($row['ban_level'] ?? 1);
        $source = self::normalizeSource((string) ($row['source'] ?? self::SOURCE_ADMIN_LOGIN));
        $formatted = [
            'id' => (int) $row['id'],
            'device_fingerprint' => (string) $row['device_fingerprint'],
            'fingerprint_short' => $this->shortFingerprint((string) $row['device_fingerprint']),
            'source' => $source,
            'source_label' => self::sourceLabel($source),
            'ip_address' => (string) $row['ip_address'],
            'user_agent' => (string) ($row['user_agent'] ?? ''),
            'browser_info' => (string) ($row['browser_info'] ?? 'Unknown device'),
            'ban_level' => $level,
            'ban_level_label' => $level >= 2 ? '90-day ban' : '15-day ban',
            'ban_count' => (int) ($row['ban_count'] ?? 1),
            'ban_start' => (string) $row['ban_start'],
            'ban_expires' => (string) $row['ban_expires'],
            'is_active' => $active,
            'status_label' => $active ? 'Active' : (! empty($row['lifted_at']) ? 'Lifted' : 'Expired'),
            'lifted_at' => $row['lifted_at'] ?? null,
            'created_at' => (string) ($row['created_at'] ?? ''),
            'location' => null,
            'recent_attempts' => [],
        ];

        if ($withGeo) {
            $formatted['location'] = $this->geo->lookup($formatted['ip_address']);
        }

        if ($withAttempts && Schema::hasTable('login_attempts')) {
            $attempts = DB::table('login_attempts')
                ->where('device_fingerprint', $formatted['device_fingerprint']);
            if ($this->hasLoginAttemptSourceColumn()) {
                $attempts->where('source', $source);
            }
            $formatted['recent_attempts'] = $attempts
                ->orderByDesc('created_at')
                ->limit(20)
                ->get()
                ->map(fn ($attempt) => (array) $attempt)
                ->all();
        }

        return $formatted;
    }

    private function shortFingerprint(string $fp): string
    {
        if (strlen($fp) < 12) {
            return $fp !== '' ? $fp : '—';
        }

        return substr($fp, 0, 8).'…'.substr($fp, -6);
    }

    private function hasSourceColumn(): bool
    {
        return Schema::hasColumn('device_bans', 'source');
    }

    private function hasLoginAttemptSourceColumn(): bool
    {
        return Schema::hasColumn('login_attempts', 'source');
    }
}
