<?php

namespace App\Services\SundaySchool;

use App\Models\SundaySchoolNotification;
use Illuminate\Support\Facades\DB;

final class NotificationWriteService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function queueNotification(array $data, int $adminId): int
    {
        $channel = in_array($data['channel'] ?? '', ['email', 'sms', 'whatsapp'], true)
            ? $data['channel']
            : 'email';

        $notification = SundaySchoolNotification::query()->create([
            'notification_type' => trim((string) ($data['notification_type'] ?? 'announcement')),
            'recipient_type' => in_array($data['recipient_type'] ?? '', ['student', 'teacher', 'parent', 'class', 'all'], true)
                ? $data['recipient_type']
                : 'all',
            'recipient_id' => ($data['recipient_id'] ?? '') !== '' ? (int) $data['recipient_id'] : null,
            'channel' => $channel,
            'subject' => trim((string) ($data['subject'] ?? 'Sunday School Update')),
            'body' => trim((string) ($data['body'] ?? '')),
            'status' => 'pending',
            'created_by' => $adminId,
            'created_at' => now(),
        ]);

        return (int) $notification->id;
    }

    public function sendPendingNotifications(int $adminId): int
    {
        $rows = SundaySchoolNotification::query()
            ->where('status', 'pending')
            ->where('channel', 'email')
            ->limit(20)
            ->get();

        $sent = 0;

        foreach ($rows as $row) {
            $emails = $this->resolveNotificationRecipients($row->toArray());
            $ok = false;

            foreach ($emails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $ok = @mail($email, (string) $row->subject, (string) $row->body) || $ok;
                }
            }

            if ($emails === []) {
                $ok = true;
            }

            $row->update([
                'status' => $ok ? 'sent' : 'failed',
                'sent_at' => now(),
            ]);

            if ($ok) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    private function resolveNotificationRecipients(array $row): array
    {
        $type = (string) ($row['recipient_type'] ?? 'all');
        $recipientId = (int) ($row['recipient_id'] ?? 0);

        if ($type === 'student' && $recipientId > 0) {
            $email = DB::table('sunday_school_students')->where('id', $recipientId)->value('parent_email');

            return $email ? [(string) $email] : [];
        }

        if ($type === 'teacher' && $recipientId > 0) {
            $email = DB::table('sunday_school_teachers')->where('id', $recipientId)->value('email');

            return $email ? [(string) $email] : [];
        }

        if ($type === 'class' && $recipientId > 0) {
            return DB::table('sunday_school_students')
                ->where('class_id', $recipientId)
                ->where('status', 'active')
                ->whereNotNull('parent_email')
                ->pluck('parent_email')
                ->filter(static fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
                ->map(static fn ($email) => (string) $email)
                ->unique()
                ->values()
                ->all();
        }

        if ($type === 'all') {
            return DB::table('sunday_school_students')
                ->where('status', 'active')
                ->whereNotNull('parent_email')
                ->pluck('parent_email')
                ->filter(static fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
                ->map(static fn ($email) => (string) $email)
                ->unique()
                ->values()
                ->all();
        }

        return [];
    }
}
