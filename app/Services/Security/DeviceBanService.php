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
            $identityFingerprints = $this->fingerprintsForIdentitySearch($query);
            $builder->where(function ($q) use ($like, $identityFingerprints) {
                $q->where('ip_address', 'like', $like)
                    ->orWhere('browser_info', 'like', $like)
                    ->orWhere('user_agent', 'like', $like)
                    ->orWhere('device_fingerprint', 'like', $like);
                if ($identityFingerprints !== []) {
                    $q->orWhereIn('device_fingerprint', $identityFingerprints);
                }
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
        $rows = $this->attachIdentities($rows);

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

        if ($row === null) {
            return null;
        }

        $formatted = $this->attachIdentities([$this->formatBan((array) $row, true, true)]);

        return $formatted[0] ?? null;
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

        $liftedAt = $this->latestLiftedAt($fingerprint, $source);
        if ($liftedAt !== null) {
            $query->where('created_at', '>', $liftedAt);
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
            'identifier_tried' => null,
            'account_name' => null,
            'account_contact' => null,
            'account_kind' => null,
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

    /**
     * @return list<string>
     */
    private function fingerprintsForIdentitySearch(string $query): array
    {
        $needle = trim($query);
        if ($needle === '') {
            return [];
        }

        $like = '%'.$needle.'%';
        $digits = preg_replace('/\D+/', '', $needle) ?? '';
        $last10 = strlen($digits) >= 7 ? substr($digits, -10) : '';
        $fingerprints = [];

        if (Schema::hasTable('login_attempts')) {
            $attempts = DB::table('login_attempts')->where('email_attempted', 'like', $like);
            if ($last10 !== '') {
                $attempts->orWhere('email_attempted', 'like', '%'.$last10.'%');
            }
            $fingerprints = array_merge($fingerprints, $attempts->limit(500)->pluck('device_fingerprint')->all());
        }

        $identifiers = $this->identifiersMatchingDirectory($like, $last10);
        if ($identifiers !== [] && Schema::hasTable('login_attempts')) {
            $related = DB::table('login_attempts')
                ->where(function ($q) use ($identifiers, $last10) {
                    $q->whereIn('email_attempted', $identifiers);
                    if ($last10 !== '') {
                        $q->orWhere('email_attempted', 'like', '%'.$last10.'%');
                    }
                })
                ->limit(500)
                ->pluck('device_fingerprint')
                ->all();
            $fingerprints = array_merge($fingerprints, $related);
        }

        return array_values(array_unique(array_filter($fingerprints, static fn ($fp) => is_string($fp) && $fp !== '')));
    }

    /**
     * @return list<string>
     */
    private function identifiersMatchingDirectory(string $like, string $last10): array
    {
        $identifiers = [];

        if (Schema::hasTable('members')) {
            $members = DB::table('members')
                ->where(function ($q) use ($like, $last10) {
                    $q->where('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('phone_alt', 'like', $like)
                        ->orWhere('full_name', 'like', $like)
                        ->orWhere('member_code', 'like', $like);
                    if ($last10 !== '') {
                        $q->orWhere('phone', 'like', '%'.$last10.'%')
                            ->orWhere('phone_alt', 'like', '%'.$last10.'%');
                    }
                })
                ->limit(100)
                ->get(['email', 'phone', 'phone_alt', 'member_code']);

            foreach ($members as $member) {
                foreach ([(string) ($member->email ?? ''), (string) ($member->phone ?? ''), (string) ($member->phone_alt ?? ''), (string) ($member->member_code ?? '')] as $value) {
                    $value = trim($value);
                    if ($value !== '') {
                        $identifiers[] = $value;
                    }
                }
            }
        }

        if (Schema::hasTable('admins')) {
            $admins = DB::table('admins')
                ->where(function ($q) use ($like, $last10) {
                    $q->where('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('full_name', 'like', $like)
                        ->orWhere('username', 'like', $like);
                    if (Schema::hasColumn('admins', 'recovery_email')) {
                        $q->orWhere('recovery_email', 'like', $like);
                    }
                    if ($last10 !== '') {
                        $q->orWhere('phone', 'like', '%'.$last10.'%');
                        if (Schema::hasColumn('admins', 'recovery_phone')) {
                            $q->orWhere('recovery_phone', 'like', '%'.$last10.'%');
                        }
                    }
                })
                ->limit(100)
                ->get(['email', 'phone', 'username', 'recovery_email', 'recovery_phone']);

            foreach ($admins as $admin) {
                foreach ([(string) ($admin->email ?? ''), (string) ($admin->phone ?? ''), (string) ($admin->username ?? ''), (string) ($admin->recovery_email ?? ''), (string) ($admin->recovery_phone ?? '')] as $value) {
                    $value = trim($value);
                    if ($value !== '') {
                        $identifiers[] = $value;
                    }
                }
            }
        }

        return array_values(array_unique($identifiers));
    }

    /**
     * @param  list<array<string, mixed>>  $bans
     * @return list<array<string, mixed>>
     */
    private function attachIdentities(array $bans): array
    {
        if ($bans === [] || ! Schema::hasTable('login_attempts')) {
            return $bans;
        }

        $fingerprints = array_values(array_unique(array_filter(array_map(
            static fn (array $ban): string => (string) ($ban['device_fingerprint'] ?? ''),
            $bans,
        ))));
        if ($fingerprints === []) {
            return $bans;
        }

        $attempts = DB::table('login_attempts')
            ->whereIn('device_fingerprint', $fingerprints)
            ->orderByDesc('created_at')
            ->get(['device_fingerprint', 'email_attempted', 'source', 'admin_id']);

        $latestByFingerprint = [];
        foreach ($attempts as $attempt) {
            $fp = (string) $attempt->device_fingerprint;
            if (! isset($latestByFingerprint[$fp])) {
                $latestByFingerprint[$fp] = trim((string) ($attempt->email_attempted ?? ''));
            }
        }

        $identifiers = array_values(array_unique(array_filter($latestByFingerprint)));
        $directory = $this->directoryByIdentifier($identifiers);

        foreach ($bans as $index => $ban) {
            $fp = (string) ($ban['device_fingerprint'] ?? '');
            $identifier = $latestByFingerprint[$fp] ?? '';
            $match = $identifier !== '' ? ($directory[$this->identityKey($identifier)] ?? null) : null;
            $bans[$index]['identifier_tried'] = $identifier !== '' ? $identifier : null;
            $bans[$index]['account_name'] = $match['name'] ?? null;
            $bans[$index]['account_contact'] = $match['contact'] ?? ($identifier !== '' ? $identifier : null);
            $bans[$index]['account_kind'] = $match['kind'] ?? null;
        }

        return $bans;
    }

    /**
     * @param  list<string>  $identifiers
     * @return array<string, array{name: string, contact: string, kind: string}>
     */
    private function directoryByIdentifier(array $identifiers): array
    {
        $map = [];
        if ($identifiers === []) {
            return $map;
        }

        $digitKeys = [];
        foreach ($identifiers as $identifier) {
            $digits = $this->phoneKey($identifier);
            if ($digits !== '') {
                $digitKeys[$digits] = $identifier;
            }
        }

        if (Schema::hasTable('members')) {
            $members = DB::table('members')
                ->where(function ($q) use ($identifiers, $digitKeys) {
                    $q->whereIn('email', $identifiers)
                        ->orWhereIn('phone', $identifiers)
                        ->orWhereIn('phone_alt', $identifiers)
                        ->orWhereIn('member_code', $identifiers);
                    foreach (array_keys($digitKeys) as $digits) {
                        $q->orWhere('phone', 'like', '%'.$digits)
                            ->orWhere('phone_alt', 'like', '%'.$digits);
                    }
                })
                ->limit(200)
                ->get(['full_name', 'email', 'phone', 'phone_alt', 'member_code']);

            foreach ($members as $member) {
                $contact = trim((string) ($member->email ?: $member->phone ?: $member->phone_alt ?: $member->member_code));
                $entry = [
                    'name' => (string) ($member->full_name ?? 'Member'),
                    'contact' => $contact,
                    'kind' => 'member',
                ];
                foreach ([(string) $member->email, (string) $member->phone, (string) $member->phone_alt, (string) $member->member_code] as $value) {
                    $key = $this->identityKey($value);
                    if ($key !== '') {
                        $map[$key] = $entry;
                    }
                }
            }
        }

        if (Schema::hasTable('admins')) {
            $admins = DB::table('admins')
                ->where(function ($q) use ($identifiers, $digitKeys) {
                    $q->whereIn('email', $identifiers)
                        ->orWhereIn('phone', $identifiers)
                        ->orWhereIn('username', $identifiers);
                    if (Schema::hasColumn('admins', 'recovery_email')) {
                        $q->orWhereIn('recovery_email', $identifiers);
                    }
                    foreach (array_keys($digitKeys) as $digits) {
                        $q->orWhere('phone', 'like', '%'.$digits);
                    }
                })
                ->limit(200)
                ->get(['full_name', 'email', 'phone', 'username']);

            foreach ($admins as $admin) {
                $contact = trim((string) ($admin->email ?: $admin->phone ?: $admin->username));
                $entry = [
                    'name' => (string) ($admin->full_name ?: $admin->email ?: 'Admin'),
                    'contact' => $contact,
                    'kind' => 'admin',
                ];
                foreach ([(string) $admin->email, (string) $admin->phone, (string) $admin->username] as $value) {
                    $key = $this->identityKey($value);
                    if ($key !== '') {
                        $map[$key] = $entry;
                    }
                }
            }
        }

        return $map;
    }

    private function identityKey(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (str_contains($value, '@')) {
            return 'e:'.mb_strtolower($value);
        }

        $phone = $this->phoneKey($value);
        if ($phone !== '') {
            return 'p:'.$phone;
        }

        return 'e:'.mb_strtolower($value);
    }

    private function phoneKey(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if (strlen($digits) >= 7) {
            return substr($digits, -10);
        }

        return '';
    }

    private function latestLiftedAt(string $fingerprint, string $source): ?string
    {
        if (! Schema::hasTable('device_bans')) {
            return null;
        }

        $query = DB::table('device_bans')
            ->where('device_fingerprint', $fingerprint)
            ->whereNotNull('lifted_at');
        if ($this->hasSourceColumn()) {
            $query->where('source', $source);
        }

        $value = $query->orderByDesc('lifted_at')->value('lifted_at');

        return $value ? (string) $value : null;
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
