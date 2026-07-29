<?php

namespace App\Services\Security;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ActivityLogService
{
    /** @return list<array{id: string, label: string}> */
    public function modules(): array
    {
        return [
            ['id' => 'all', 'label' => 'All Modules'],
            ['id' => 'security', 'label' => 'Security'],
            ['id' => 'members', 'label' => 'Members'],
            ['id' => 'visitors', 'label' => 'Visitors'],
            ['id' => 'attendance', 'label' => 'Attendance'],
            ['id' => 'events', 'label' => 'Events'],
            ['id' => 'departments', 'label' => 'Departments'],
            ['id' => 'donations', 'label' => 'Donations'],
            ['id' => 'stewardship', 'label' => 'Stewardship'],
            ['id' => 'partnerships', 'label' => 'Partnerships'],
            ['id' => 'sdtg', 'label' => 'SDTG'],
            ['id' => 'seo', 'label' => 'SEO'],
            ['id' => 'sermons', 'label' => 'Sermons'],
            ['id' => 'sunday_school', 'label' => 'Sunday School'],
            ['id' => 'testimonies', 'label' => 'Testimonies'],
            ['id' => 'messages', 'label' => 'Send Emails'],
            ['id' => 'settings', 'label' => 'Settings'],
            ['id' => 'system', 'label' => 'System'],
        ];
    }

    /** @return array{date_from: string, date_to: string} */
    public function defaultDates(): array
    {
        return [
            'date_from' => now()->subDays(30)->toDateString(),
            'date_to' => now()->toDateString(),
        ];
    }

    /** @return list<array{id: int, full_name: string, email: string}> */
    public function admins(): array
    {
        if (! Schema::hasTable('admins')) {
            return [];
        }

        return DB::table('admins')
            ->where('is_active', 1)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'email'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'full_name' => (string) $row->full_name,
                'email' => (string) $row->email,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{total: int, today: int, warnings: int, admins: int}
     */
    public function stats(array $filters): array
    {
        if (! Schema::hasTable('security_logs')) {
            return ['total' => 0, 'today' => 0, 'warnings' => 0, 'admins' => 0];
        }

        $base = $this->filteredQuery([
            'date_from' => $filters['date_from'] ?? '',
            'date_to' => $filters['date_to'] ?? '',
        ]);

        $total = (int) (clone $base)->count();
        $today = (int) (clone $base)
            ->whereBetween('sl.created_at', [
                now()->startOfDay()->toDateTimeString(),
                now()->endOfDay()->toDateTimeString(),
            ])
            ->count();
        $warnings = (int) (clone $base)
            ->whereIn('sl.severity', ['warning', 'critical'])
            ->count();
        $admins = (clone $base)
            ->whereNotNull('sl.admin_id')
            ->pluck('sl.admin_id')
            ->unique()
            ->count();

        return [
            'total' => $total,
            'today' => $today,
            'warnings' => $warnings,
            'admins' => $admins,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public function list(array $filters): array
    {
        if (! Schema::hasTable('security_logs')) {
            return ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'per_page' => 20];
        }

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 20)));
        $query = $this->filteredQuery($filters);

        $total = (int) (clone $query)->count();
        $rows = (clone $query)
            ->orderByDesc('sl.created_at')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get([
                'sl.*',
                'a.email as admin_email',
                'a.full_name as admin_name',
            ]);

        return [
            'items' => $rows->map(fn ($row) => $this->formatRow((array) $row))->all(),
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'per_page' => $perPage,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCsv(array $filters, int $maxRows = 10000): string
    {
        $filters['page'] = 1;
        $filters['per_page'] = $maxRows;
        $result = $this->list($filters);

        $lines = ['Timestamp,User,Email,Module,Action,Severity,Details,IP Address,Event Type'];
        foreach ($result['items'] as $row) {
            $lines[] = implode(',', array_map(
                static fn (string $value): string => '"'.str_replace('"', '""', $value).'"',
                [
                    (string) ($row['timestamp_label'] ?? ''),
                    (string) ($row['user'] ?? ''),
                    (string) ($row['user_email'] ?? ''),
                    (string) ($row['module_label'] ?? ''),
                    (string) ($row['action'] ?? ''),
                    (string) ($row['severity'] ?? ''),
                    (string) ($row['details'] ?? ''),
                    (string) ($row['ip'] ?? ''),
                    (string) ($row['event_type'] ?? ''),
                ]
            ));
        }

        return implode("\r\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        $query = DB::table('security_logs as sl')
            ->leftJoin('admins as a', 'a.id', '=', 'sl.admin_id');

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '' && $this->isValidDate($dateFrom)) {
            $query->where('sl.created_at', '>=', $dateFrom.' 00:00:00');
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '' && $this->isValidDate($dateTo)) {
            $query->where('sl.created_at', '<=', $dateTo.' 23:59:59');
        }

        $adminId = (int) ($filters['admin_id'] ?? 0);
        if ($adminId > 0) {
            $query->where('sl.admin_id', $adminId);
        }

        $severity = trim((string) ($filters['severity'] ?? ''));
        if (in_array($severity, ['info', 'warning', 'critical'], true)) {
            $query->where('sl.severity', $severity);
        }

        $module = trim((string) ($filters['module'] ?? 'all'));
        if ($module !== '' && $module !== 'all') {
            $this->applyModuleFilter($query, $module);
        }

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function (Builder $q) use ($like) {
                $q->where('sl.message', 'like', $like)
                    ->orWhere('sl.event_type', 'like', $like)
                    ->orWhere('a.full_name', 'like', $like)
                    ->orWhere('a.email', 'like', $like)
                    ->orWhere('sl.ip_address', 'like', $like);
            });
        }

        return $query;
    }

    private function applyModuleFilter(Builder $query, string $module): void
    {
        match ($module) {
            'security' => $query->where(function (Builder $q) {
                $q->where('sl.event_type', 'like', 'login_%')
                    ->orWhere('sl.event_type', 'like', 'logout%')
                    ->orWhereIn('sl.event_type', [
                        'access_denied', 'device_banned', 'ban_lifted', 'brute_force', 'rate_limit',
                        'session_timeout', 'login_warning', 'session_expired',
                    ]);
            }),
            'members' => $query->where('sl.event_type', 'like', 'member_%'),
            'visitors' => $query->where('sl.event_type', 'like', 'visitor_%'),
            'attendance' => $query->where('sl.event_type', 'like', 'attendance_%'),
            'events' => $query->where('sl.event_type', 'like', 'event_%'),
            'departments' => $query->where('sl.event_type', 'like', 'activity_%'),
            'donations' => $query->where(function (Builder $q) {
                $q->where('sl.event_type', 'like', 'donation_%')
                    ->orWhere('sl.event_type', 'pledge_created');
            }),
            'stewardship' => $query->where(function (Builder $q) {
                $q->where('sl.event_type', 'create_purpose')
                    ->orWhere('sl.event_type', 'like', 'stewardship_%');
            }),
            'partnerships' => $query->whereIn('sl.event_type', [
                'create_partner', 'update_partner', 'delete_partner',
                'create_campaign', 'update_campaign', 'delete_campaign',
            ]),
            'sdtg' => $query->where('sl.event_type', 'like', 'sdtg_%'),
            'seo' => $query->where('sl.event_type', 'seo'),
            'sermons' => $query->where(function (Builder $q) {
                $q->where('sl.event_type', 'like', 'sermon_%')
                    ->orWhere('sl.event_type', 'like', 'stream_%');
            }),
            'sunday_school' => $query->where('sl.event_type', 'like', 'ss_%'),
            'testimonies' => $query->where('sl.event_type', 'like', 'site_testimony%'),
            'messages' => $query->where('sl.event_type', 'email_send'),
            'settings' => $query->where(function (Builder $q) {
                $q->where('sl.event_type', 'like', 'admin_%')
                    ->orWhere('sl.event_type', 'like', 'role_%')
                    ->orWhere('sl.event_type', 'like', 'password_%')
                    ->orWhere('sl.event_type', 'like', 'settings_%')
                    ->orWhereIn('sl.event_type', ['backup_created', 'email_test', 'email_template_updated']);
            }),
            'system' => $query->where(function (Builder $q) {
                $q->where('sl.event_type', 'not like', 'login_%')
                    ->where('sl.event_type', 'not like', 'logout%')
                    ->where('sl.event_type', 'not like', 'member_%')
                    ->where('sl.event_type', 'not like', 'visitor_%')
                    ->where('sl.event_type', 'not like', 'attendance_%')
                    ->where('sl.event_type', 'not like', 'event_%')
                    ->where('sl.event_type', 'not like', 'activity_%')
                    ->where('sl.event_type', 'not like', 'donation_%')
                    ->where('sl.event_type', 'not like', 'sdtg_%')
                    ->where('sl.event_type', 'not like', 'sermon_%')
                    ->where('sl.event_type', 'not like', 'stream_%')
                    ->where('sl.event_type', 'not like', 'ss_%')
                    ->where('sl.event_type', 'not like', 'site_testimony%')
                    ->where('sl.event_type', 'not like', 'admin_%')
                    ->where('sl.event_type', 'not like', 'role_%')
                    ->where('sl.event_type', 'not like', 'password_%')
                    ->where('sl.event_type', 'not like', 'settings_%')
                    ->whereNotIn('sl.event_type', [
                        'access_denied', 'device_banned', 'ban_lifted', 'brute_force', 'rate_limit',
                        'session_timeout', 'login_warning', 'session_expired', 'pledge_created',
                        'create_purpose', 'seo', 'email_send', 'backup_created', 'email_test',
                        'email_template_updated',
                    ]);
            }),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function formatRow(array $row): array
    {
        $eventType = (string) ($row['event_type'] ?? '');
        $module = $this->moduleForEventType($eventType);
        $createdAt = (string) ($row['created_at'] ?? '');

        return [
            'id' => (int) ($row['id'] ?? 0),
            'timestamp' => $createdAt,
            'timestamp_label' => $this->formatDateTime($createdAt),
            'user' => (string) ($row['admin_name'] ?? 'System'),
            'user_email' => (string) ($row['admin_email'] ?? ''),
            'module' => $module,
            'module_label' => $this->moduleLabel($module),
            'action' => $this->actionLabel($eventType),
            'details' => (string) ($row['message'] ?? ''),
            'severity' => (string) ($row['severity'] ?? 'info'),
            'ip' => $this->maskIp(isset($row['ip_address']) ? (string) $row['ip_address'] : null),
            'event_type' => $eventType,
        ];
    }

    private function moduleForEventType(string $eventType): string
    {
        if (
            str_starts_with($eventType, 'login_')
            || str_starts_with($eventType, 'logout')
            || in_array($eventType, [
                'access_denied', 'device_banned', 'ban_lifted', 'brute_force', 'rate_limit',
                'session_timeout', 'login_warning', 'session_expired',
            ], true)
        ) {
            return 'security';
        }

        if (str_starts_with($eventType, 'member_')) {
            return 'members';
        }
        if (str_starts_with($eventType, 'visitor_')) {
            return 'visitors';
        }
        if (str_starts_with($eventType, 'attendance_')) {
            return 'attendance';
        }
        if (str_starts_with($eventType, 'event_')) {
            return 'events';
        }
        if (str_starts_with($eventType, 'activity_')) {
            return 'departments';
        }
        if (str_starts_with($eventType, 'donation_') || $eventType === 'pledge_created') {
            return 'donations';
        }
        if ($eventType === 'create_purpose' || str_starts_with($eventType, 'stewardship_')) {
            return 'stewardship';
        }
        if (in_array($eventType, [
            'create_partner', 'update_partner', 'delete_partner',
            'create_campaign', 'update_campaign', 'delete_campaign',
        ], true)) {
            return 'partnerships';
        }
        if (str_starts_with($eventType, 'sdtg_')) {
            return 'sdtg';
        }
        if ($eventType === 'seo') {
            return 'seo';
        }
        if (str_starts_with($eventType, 'sermon_') || str_starts_with($eventType, 'stream_')) {
            return 'sermons';
        }
        if (str_starts_with($eventType, 'ss_')) {
            return 'sunday_school';
        }
        if (str_starts_with($eventType, 'site_testimony')) {
            return 'testimonies';
        }
        if ($eventType === 'email_send') {
            return 'messages';
        }
        if (
            str_starts_with($eventType, 'admin_')
            || str_starts_with($eventType, 'role_')
            || str_starts_with($eventType, 'password_')
            || str_starts_with($eventType, 'settings_')
            || in_array($eventType, ['backup_created', 'email_test', 'email_template_updated'], true)
        ) {
            return 'settings';
        }

        return 'system';
    }

    private function moduleLabel(string $module): string
    {
        foreach ($this->modules() as $item) {
            if ($item['id'] === $module) {
                return $item['label'];
            }
        }

        return ucwords(str_replace('_', ' ', $module));
    }

    private function actionLabel(string $eventType): string
    {
        $labels = [
            'login_success' => 'Login Success',
            'login_failed' => 'Login Failed',
            'logout' => 'Logout',
            'access_denied' => 'Access Denied',
            'device_banned' => 'Device Banned',
            'ban_lifted' => 'Ban Lifted',
            'member_created' => 'Member Added',
            'member_updated' => 'Member Updated',
            'member_deleted' => 'Member Deleted',
            'member_death_recorded' => 'Death Recorded',
            'visitor_created' => 'Visitor Registered',
            'visitor_updated' => 'Visitor Updated',
            'visitor_deleted' => 'Visitor Deleted',
            'visitor_return_visit' => 'Return Visit',
            'attendance_recorded' => 'Attendance Submitted',
            'attendance_updated' => 'Attendance Updated',
            'event_created' => 'Event Created',
            'event_updated' => 'Event Updated',
            'event_deleted' => 'Event Deleted',
            'donation_recorded' => 'Donation Recorded',
            'pledge_created' => 'Pledge Created',
            'sermon_saved' => 'Sermon Saved',
            'sermon_deleted' => 'Sermon Deleted',
            'stream_saved' => 'Stream Updated',
            'email_send' => 'Email Sent',
            'settings_updated' => 'Settings Updated',
            'admin_created' => 'Admin Created',
            'admin_updated' => 'Admin Updated',
            'admin_deleted' => 'Admin Deleted',
            'role_saved' => 'Role Saved',
            'role_deleted' => 'Role Deleted',
            'password_reset' => 'Password Reset',
            'session_timeout' => 'Session Timeout',
        ];

        return $labels[$eventType] ?? ucwords(str_replace('_', ' ', $eventType));
    }

    private function maskIp(?string $ip): string
    {
        if ($ip === null || $ip === '') {
            return '-';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);

            return ($parts[0] ?? '*').'.'.($parts[1] ?? '*').'.*.*';
        }

        return substr($ip, 0, 8).'...';
    }

    private function isValidDate(string $value): bool
    {
        try {
            return Carbon::createFromFormat('Y-m-d', $value)?->format('Y-m-d') === $value;
        } catch (\Throwable) {
            return false;
        }
    }

    private function formatDateTime(string $value): string
    {
        if ($value === '') {
            return '-';
        }

        try {
            return Carbon::parse($value)->format('M j, Y g:i A');
        } catch (\Throwable) {
            return $value;
        }
    }
}
