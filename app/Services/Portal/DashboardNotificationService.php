<?php

namespace App\Services\Portal;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class DashboardNotificationService
{
    /** @var list<string> */
    private const NOTIFICATION_EVENTS = [
        'member_created',
        'member_updated',
        'member_deleted',
        'member_death_recorded',
        'visitor_created',
        'visitor_updated',
        'visitor_deleted',
        'visitor_return_visit',
        'visitor_promoted',
        'attendance_recorded',
        'donation_recorded',
        'event_created',
        'session_timeout',
        'login_success',
        'login_failed',
        'device_banned',
        'erp_alert',
        'hub_notification',
        'ss_attendance',
        'newsletter_subscriber',
        'contact_submission',
        'site_testimony_submitted',
    ];

    /** @return array{notifications: list<array<string, mixed>>, unread_count: int, latest_id: int, last_read_id: int} */
    public function getNotifications(int $adminId, int $limit = 12, ?int $sinceId = null): array
    {
        if (! Schema::hasTable('security_logs')) {
            return [
                'notifications' => [],
                'unread_count' => 0,
                'latest_id' => 0,
                'last_read_id' => 0,
            ];
        }

        $lastReadId = $this->getLastReadLogId($adminId);
        $query = DB::table('security_logs as sl')
            ->leftJoin('admins as a', 'a.id', '=', 'sl.admin_id')
            ->whereIn('sl.event_type', self::NOTIFICATION_EVENTS)
            ->select([
                'sl.id',
                'sl.event_type',
                'sl.severity',
                'sl.message',
                'sl.created_at',
                'a.full_name as admin_name',
            ]);

        if ($sinceId !== null && $sinceId > 0) {
            $query->where('sl.id', '>', $sinceId);
        }

        $rows = $query
            ->orderByDesc('sl.id')
            ->limit(max(1, min(50, $limit)))
            ->get();

        $notifications = [];
        $latestId = $lastReadId;

        foreach ($rows as $row) {
            $entry = $this->formatNotification((array) $row);
            $entry['is_unread'] = ((int) $row->id) > $lastReadId;
            $notifications[] = $entry;
            $latestId = max($latestId, (int) $row->id);
        }

        // Full list refreshes also surface Communication Hub items in the same bell.
        if ($sinceId === null && Schema::hasTable('notification_center')) {
            $hubItems = $this->hubNotifications($adminId, max(1, min(20, $limit)));
            $notifications = $this->mergeByTime($notifications, $hubItems, $limit);
        }

        $unreadCount = (int) DB::table('security_logs')
            ->where('id', '>', $lastReadId)
            ->whereIn('event_type', self::NOTIFICATION_EVENTS)
            ->count();

        if ($sinceId === null && Schema::hasTable('notification_center')) {
            $unreadCount += $this->hubUnreadCount($adminId);
        }

        return [
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'latest_id' => $latestId,
            'last_read_id' => $lastReadId,
        ];
    }

    public function markNotificationsRead(int $adminId, int $lastLogId): void
    {
        if ($lastLogId < 1 || ! Schema::hasTable('admin_notification_reads')) {
            return;
        }

        $current = (int) DB::table('admin_notification_reads')
            ->where('admin_id', $adminId)
            ->value('last_read_log_id');

        DB::table('admin_notification_reads')->updateOrInsert(
            ['admin_id' => $adminId],
            ['last_read_log_id' => max($current, $lastLogId), 'updated_at' => now()],
        );

        if (Schema::hasTable('notification_center')) {
            DB::table('notification_center')
                ->where(function ($q) use ($adminId) {
                    $q->whereNull('admin_id')->orWhere('admin_id', $adminId);
                })
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
        }
    }

    private function getLastReadLogId(int $adminId): int
    {
        if (! Schema::hasTable('admin_notification_reads')) {
            return 0;
        }

        $value = DB::table('admin_notification_reads')
            ->where('admin_id', $adminId)
            ->value('last_read_log_id');

        return $value !== null ? (int) $value : 0;
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function formatNotification(array $row): array
    {
        $formatted = [
            'id' => (int) $row['id'],
            'title' => $this->notificationTitle($row),
            'text' => (string) ($row['message'] ?? ''),
            'time' => $this->relativeTime((string) ($row['created_at'] ?? '')),
            'time_iso' => (string) ($row['created_at'] ?? ''),
            'icon' => $this->eventIcon((string) ($row['event_type'] ?? ''), (string) ($row['severity'] ?? 'info')),
            'event_type' => (string) ($row['event_type'] ?? ''),
        ];

        return $formatted;
    }

    /** @param array<string, mixed> $row */
    private function notificationTitle(array $row): string
    {
        $custom = $this->eventTitle((string) ($row['event_type'] ?? ''));
        if ($custom !== 'System Activity') {
            return $custom;
        }

        return (string) ($row['admin_name'] ?? 'System');
    }

    private function eventTitle(string $eventType): string
    {
        return match ($eventType) {
            'member_created' => 'New Member',
            'member_updated' => 'Member Updated',
            'member_deleted' => 'Member Removed',
            'member_death_recorded' => 'Death Recorded',
            'visitor_created' => 'New Visitor',
            'visitor_updated' => 'Visitor Updated',
            'visitor_deleted' => 'Visitor Removed',
            'visitor_return_visit' => 'Return Visit',
            'visitor_promoted' => 'Promoted to Member',
            'attendance_recorded' => 'Attendance Recorded',
            'donation_recorded' => 'Donation Recorded',
            'event_created' => 'Event Scheduled',
            'session_timeout' => 'Session Expired',
            'login_success' => 'Admin Login',
            'login_failed' => 'Failed Login',
            'device_banned' => 'Security Alert',
            'erp_alert' => 'Financial ERP Alert',
            'hub_notification' => 'Communication Hub',
            'ss_attendance' => 'Sunday School Attendance',
            'newsletter_subscriber' => 'Newsletter',
            'contact_submission' => 'Contact Message',
            'site_testimony_submitted' => 'New Testimony',
            default => 'System Activity',
        };
    }

    private function eventIcon(string $eventType, string $severity): string
    {
        if (in_array($severity, ['critical', 'warning'], true)) {
            return 'gold';
        }

        return match ($eventType) {
            'member_created', 'member_updated', 'member_death_recorded', 'visitor_promoted',
            'attendance_recorded', 'donation_recorded', 'event_created',
            'ss_attendance', 'newsletter_subscriber' => 'green',
            'visitor_created', 'visitor_return_visit', 'visitor_updated', 'hub_notification',
            'contact_submission', 'site_testimony_submitted' => 'blue',
            'login_failed', 'device_banned', 'erp_alert' => 'gold',
            default => 'gold',
        };
    }

    /** @return list<array<string, mixed>> */
    private function hubNotifications(int $adminId, int $limit): array
    {
        return DB::table('notification_center')
            ->where(function ($q) use ($adminId) {
                $q->whereNull('admin_id')->orWhere('admin_id', $adminId);
            })
            ->where('is_archived', false)
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $created = (string) ($row->created_at ?? '');

                return [
                    'id' => 'hub-'.(int) $row->id,
                    'title' => (string) ($row->title ?? 'Communication Hub'),
                    'text' => (string) ($row->body ?? $row->message ?? ''),
                    'time' => $this->relativeTime($created),
                    'time_iso' => $created,
                    'icon' => ((string) ($row->priority ?? '')) === 'high' ? 'gold' : 'blue',
                    'event_type' => 'hub_notification',
                    'is_unread' => ! (bool) ($row->is_read ?? false),
                ];
            })
            ->all();
    }

    private function hubUnreadCount(int $adminId): int
    {
        return (int) DB::table('notification_center')
            ->where(function ($q) use ($adminId) {
                $q->whereNull('admin_id')->orWhere('admin_id', $adminId);
            })
            ->where('is_archived', false)
            ->where('is_read', false)
            ->count();
    }

    /**
     * @param  list<array<string, mixed>>  $primary
     * @param  list<array<string, mixed>>  $secondary
     * @return list<array<string, mixed>>
     */
    private function mergeByTime(array $primary, array $secondary, int $limit): array
    {
        $merged = array_merge($primary, $secondary);
        usort($merged, static function (array $a, array $b): int {
            return strcmp((string) ($b['time_iso'] ?? ''), (string) ($a['time_iso'] ?? ''));
        });

        return array_values(array_slice($merged, 0, max(1, $limit)));
    }

    private function relativeTime(string $datetime): string
    {
        $time = strtotime($datetime);
        if ($time === false) {
            return $datetime;
        }

        $diff = time() - $time;
        if ($diff < 60) {
            return 'Just now';
        }
        if ($diff < 3600) {
            $mins = (int) floor($diff / 60);

            return $mins.' minute'.($mins !== 1 ? 's' : '').' ago';
        }
        if ($diff < 86400) {
            $hours = (int) floor($diff / 3600);

            return $hours.' hour'.($hours !== 1 ? 's' : '').' ago';
        }
        if ($diff < 604800) {
            $days = (int) floor($diff / 86400);

            return $days.' day'.($days !== 1 ? 's' : '').' ago';
        }

        return date('j M Y, g:i A', $time);
    }
}
