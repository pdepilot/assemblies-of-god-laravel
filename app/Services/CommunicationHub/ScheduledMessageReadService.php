<?php

namespace App\Services\CommunicationHub;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ScheduledMessageReadService
{
    public const CHANNELS = ['email', 'sms', 'whatsapp', 'push', 'in_app', 'multi'];

    public const STATUSES = ['scheduled', 'processing', 'sent', 'cancelled', 'failed'];

    public const RECURRENCES = ['none', 'daily', 'weekly', 'monthly', 'yearly'];

    public const PRIORITIES = ['high', 'normal', 'low'];

    /** @return list<array<string, mixed>> */
    public function listUpcoming(int $limit = 100): array
    {
        $items = [];

        if (Schema::hasTable('scheduled_messages')) {
            $rows = DB::table('scheduled_messages')
                ->whereIn('status', ['scheduled', 'processing'])
                ->orderBy('scheduled_at')
                ->limit($limit)
                ->get();

            foreach ($rows as $row) {
                $items[] = $this->formatRow((array) $row, 'scheduled_messages');
            }
        }

        if (Schema::hasTable('email_history')) {
            $emails = DB::table('email_history')
                ->where(function ($q) {
                    $q->where('status', 'scheduled')
                        ->orWhere(function ($q2) {
                            $q2->whereNotNull('scheduled_at')
                                ->whereIn('status', ['queued', 'pending', 'scheduled']);
                        });
                })
                ->orderBy('scheduled_at')
                ->limit(50)
                ->get();

            foreach ($emails as $row) {
                $items[] = [
                    'id' => (int) $row->id,
                    'message_code' => 'EMAIL-'.$row->id,
                    'channel' => 'email',
                    'subject' => (string) ($row->subject ?? ''),
                    'preview' => (string) ($row->subject ?? ''),
                    'body_html' => '',
                    'body_text' => '',
                    'recipient_group_key' => null,
                    'priority' => 'normal',
                    'recurrence' => 'none',
                    'scheduled_at' => (string) ($row->scheduled_at ?? $row->created_at ?? ''),
                    'next_run_at' => null,
                    'status' => (string) ($row->status ?? 'scheduled'),
                    'source' => 'email_history',
                    'created_at' => (string) ($row->created_at ?? ''),
                ];
            }
        }

        usort($items, static function (array $a, array $b): int {
            return strcmp((string) ($a['scheduled_at'] ?? ''), (string) ($b['scheduled_at'] ?? ''));
        });

        return array_slice($items, 0, $limit);
    }

    /** @return array<string, mixed>|null */
    public function getMessage(int $id): ?array
    {
        if (! Schema::hasTable('scheduled_messages')) {
            return null;
        }

        $row = DB::table('scheduled_messages')->where('id', $id)->first();

        return $row ? $this->formatRow((array) $row, 'scheduled_messages') : null;
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function formatRow(array $row, string $source): array
    {
        $subject = (string) ($row['subject'] ?? '');
        $bodyText = (string) ($row['body_text'] ?? '');
        $preview = $subject !== '' ? $subject : mb_substr(strip_tags($bodyText ?: (string) ($row['body_html'] ?? '')), 0, 80);

        return [
            'id' => (int) $row['id'],
            'message_code' => (string) ($row['message_code'] ?? ''),
            'channel' => (string) ($row['channel'] ?? 'email'),
            'subject' => $subject,
            'preview' => $preview !== '' ? $preview : '—',
            'body_html' => (string) ($row['body_html'] ?? ''),
            'body_text' => $bodyText,
            'template_slug' => $row['template_slug'] ?? null,
            'recipient_group_key' => $row['recipient_group_key'] ?? null,
            'priority' => (string) ($row['priority'] ?? 'normal'),
            'recurrence' => (string) ($row['recurrence'] ?? 'none'),
            'scheduled_at' => (string) ($row['scheduled_at'] ?? ''),
            'next_run_at' => $row['next_run_at'] ?? null,
            'status' => (string) ($row['status'] ?? 'scheduled'),
            'campaign_id' => $row['campaign_id'] ?? null,
            'created_by' => $row['created_by'] ?? null,
            'source' => $source,
            'created_at' => (string) ($row['created_at'] ?? ''),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
        ];
    }
}
