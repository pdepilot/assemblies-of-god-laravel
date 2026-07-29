<?php

namespace App\Services\CommunicationHub;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class ScheduledMessageWriteService
{
    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function save(array $input, int $adminId): array
    {
        if (! Schema::hasTable('scheduled_messages')) {
            throw new InvalidArgumentException('Scheduled messages table is not available.');
        }

        $id = (int) ($input['id'] ?? 0);
        $channel = (string) ($input['channel'] ?? 'email');
        if (! in_array($channel, ScheduledMessageReadService::CHANNELS, true)) {
            $channel = 'email';
        }

        $subject = trim((string) ($input['subject'] ?? ''));
        $bodyHtml = (string) ($input['body_html'] ?? '');
        $bodyText = trim((string) ($input['body_text'] ?? '')) ?: strip_tags($bodyHtml);

        if ($subject === '' && $bodyText === '') {
            throw new InvalidArgumentException('Subject or message body is required.');
        }

        $scheduledAt = $this->normalizeDateTime((string) ($input['scheduled_at'] ?? ''));
        if ($scheduledAt === null) {
            throw new InvalidArgumentException('Schedule date/time is required.');
        }

        $priority = (string) ($input['priority'] ?? 'normal');
        if (! in_array($priority, ScheduledMessageReadService::PRIORITIES, true)) {
            $priority = 'normal';
        }

        $recurrence = (string) ($input['recurrence'] ?? 'none');
        if (! in_array($recurrence, ScheduledMessageReadService::RECURRENCES, true)) {
            $recurrence = 'none';
        }

        $status = (string) ($input['status'] ?? 'scheduled');
        if (! in_array($status, ScheduledMessageReadService::STATUSES, true)) {
            $status = 'scheduled';
        }

        $payload = [
            'channel' => $channel,
            'subject' => $subject !== '' ? $subject : null,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
            'template_slug' => trim((string) ($input['template_slug'] ?? '')) ?: null,
            'recipient_group_key' => trim((string) ($input['recipient_group_key'] ?? '')) ?: null,
            'priority' => $priority,
            'recurrence' => $recurrence,
            'scheduled_at' => $scheduledAt,
            'next_run_at' => $scheduledAt,
            'status' => $status,
            'updated_at' => now(),
        ];

        if ($id > 0) {
            $existing = DB::table('scheduled_messages')->where('id', $id)->first();
            if (! $existing) {
                throw new InvalidArgumentException('Scheduled message not found.');
            }
            DB::table('scheduled_messages')->where('id', $id)->update($payload);
        } else {
            $id = (int) DB::table('scheduled_messages')->insertGetId($payload + [
                'message_code' => 'SCH'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
                'created_by' => $adminId > 0 ? $adminId : null,
                'created_at' => now(),
            ]);
        }

        return (new ScheduledMessageReadService)->getMessage($id) ?? [];
    }

    /** @return array<string, mixed> */
    public function cancel(int $id): array
    {
        if (! Schema::hasTable('scheduled_messages')) {
            throw new InvalidArgumentException('Scheduled messages table is not available.');
        }

        $existing = DB::table('scheduled_messages')->where('id', $id)->first();
        if (! $existing) {
            throw new InvalidArgumentException('Scheduled message not found.');
        }

        DB::table('scheduled_messages')->where('id', $id)->update([
            'status' => 'cancelled',
            'updated_at' => now(),
        ]);

        return (new ScheduledMessageReadService)->getMessage($id) ?? [];
    }

    public function delete(int $id): void
    {
        if (! Schema::hasTable('scheduled_messages')) {
            throw new InvalidArgumentException('Scheduled messages table is not available.');
        }

        $existing = DB::table('scheduled_messages')->where('id', $id)->first();
        if (! $existing) {
            throw new InvalidArgumentException('Scheduled message not found.');
        }

        DB::table('scheduled_messages')->where('id', $id)->delete();
    }

    private function normalizeDateTime(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $value = str_replace('T', ' ', $value);
        if (strlen($value) === 16) {
            $value .= ':00';
        }

        return $value;
    }
}
