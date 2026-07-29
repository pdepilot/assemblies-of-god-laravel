<?php

namespace App\Services\CommunicationHub;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class QueueWriteService
{
    public function __construct(
        private readonly SmsGatewayService $sms,
        private readonly HubMailConfigurator $mail,
        private readonly EmailTemplateBodyService $bodies,
    ) {}

    /**
     * @return array{email_sent: int, sms_sent: int, email_failed: int, sms_failed: int}
     */
    public function process(int $limit = 50): array
    {
        $limit = max(1, min(100, $limit));

        $email = $this->processEmailQueue($limit);
        $history = $this->processEmailHistory($limit);
        $sms = $this->processSmsQueue($limit);

        return [
            'email_sent' => $email['sent'] + $history['sent'],
            'sms_sent' => $sms['sent'],
            'email_failed' => $email['failed'] + $history['failed'],
            'sms_failed' => $sms['failed'],
        ];
    }

    /**
     * @return array{email_deleted: int, sms_deleted: int}
     */
    public function deleteFailed(): array
    {
        $emailDeleted = 0;
        $smsDeleted = 0;

        if (Schema::hasTable('sms_queue')) {
            $smsDeleted += (int) DB::table('sms_queue')->where('status', 'failed')->delete();
        }

        if (Schema::hasTable('email_queue')) {
            $emailDeleted += (int) DB::table('email_queue')->where('status', 'failed')->delete();
        }

        if (Schema::hasTable('email_history')) {
            $emailDeleted += (int) DB::table('email_history')->where('status', 'failed')->delete();
        }

        return [
            'email_deleted' => $emailDeleted,
            'sms_deleted' => $smsDeleted,
        ];
    }

    /**
     * @return array{sent: int, failed: int}
     */
    private function processEmailQueue(int $limit): array
    {
        $sent = 0;
        $failed = 0;

        if (! Schema::hasTable('email_queue')) {
            return ['sent' => 0, 'failed' => 0];
        }

        $rows = DB::table('email_queue')
            ->where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();

        foreach ($rows as $row) {
            DB::table('email_queue')->where('id', $row->id)->update([
                'status' => 'processing',
                'attempts' => ((int) $row->attempts) + 1,
            ]);

            try {
                $this->mail->sendHtml(
                    [
                        'email' => (string) $row->recipient,
                        'name' => (string) ($row->recipient_name ?? ''),
                    ],
                    (string) $row->subject,
                    (string) $row->body_html
                );
                DB::table('email_queue')->where('id', $row->id)->update([
                    'status' => 'sent',
                    'error_message' => null,
                    'processed_at' => now(),
                ]);
                $sent++;
            } catch (Throwable $e) {
                DB::table('email_queue')->where('id', $row->id)->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'processed_at' => now(),
                ]);
                $failed++;
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * @return array{sent: int, failed: int}
     */
    private function processEmailHistory(int $limit): array
    {
        $sent = 0;
        $failed = 0;

        if (! Schema::hasTable('email_history')) {
            return ['sent' => 0, 'failed' => 0];
        }

        $rows = DB::table('email_history')
            ->where(function ($q) {
                $q->whereIn('status', ['queued', 'pending'])
                    ->orWhere(function ($q2) {
                        $q2->where('status', 'scheduled')
                            ->whereNotNull('scheduled_at')
                            ->where('scheduled_at', '<=', now());
                    });
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($rows as $row) {
            $body = $this->resolveHistoryBody($row);
            if ($body === '') {
                DB::table('email_history')->where('id', $row->id)->update([
                    'status' => 'failed',
                    'error_message' => 'No message body available for this scheduled email.',
                ]);
                $failed++;
                continue;
            }

            try {
                $this->mail->sendHtml(
                    [
                        'email' => (string) $row->recipient,
                        'name' => (string) ($row->recipient_name ?? ''),
                    ],
                    (string) $row->subject,
                    $body
                );
                DB::table('email_history')->where('id', $row->id)->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'error_message' => null,
                ]);
                $sent++;
            } catch (Throwable $e) {
                DB::table('email_history')->where('id', $row->id)->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    private function resolveHistoryBody(object $row): string
    {
        $templateId = (int) ($row->template_id ?? 0);
        $templateSlug = trim((string) ($row->template_slug ?? ''));

        if ($templateId > 0 && Schema::hasTable('email_templates')) {
            $tpl = DB::table('email_templates')->where('id', $templateId)->first();
            if ($tpl) {
                return $this->bodies->toHtml((string) ($tpl->body_html ?: $tpl->body_text ?: ''));
            }
        }

        if ($templateSlug !== '' && Schema::hasTable('communication_templates')) {
            $tpl = DB::table('communication_templates')
                ->where('channel', 'email')
                ->where('slug', $templateSlug)
                ->first();
            if ($tpl) {
                return $this->bodies->toHtml((string) ($tpl->body_html ?: $tpl->body_text ?: ''));
            }
        }

        return '';
    }

    /**
     * @return array{sent: int, failed: int}
     */
    private function processSmsQueue(int $limit): array
    {
        $sent = 0;
        $failed = 0;

        if (! Schema::hasTable('sms_queue')) {
            return ['sent' => 0, 'failed' => 0];
        }

        $rows = DB::table('sms_queue')
            ->where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();

        foreach ($rows as $row) {
            DB::table('sms_queue')->where('id', $row->id)->update([
                'status' => 'processing',
                'attempts' => ((int) $row->attempts) + 1,
                'updated_at' => now(),
            ]);

            $result = $this->sms->send((string) $row->recipient_phone, (string) $row->message_body);
            $status = $result['success'] ? 'sent' : 'failed';

            DB::table('sms_queue')->where('id', $row->id)->update([
                'status' => $status,
                'provider' => $result['provider'],
                'error_message' => $result['error'],
                'provider_response' => $result['response'],
                'updated_at' => now(),
            ]);

            if (Schema::hasTable('sms_logs')) {
                DB::table('sms_logs')->insert([
                    'queue_id' => $row->id,
                    'recipient_phone' => $row->recipient_phone,
                    'recipient_name' => $row->recipient_name,
                    'message_body' => $row->message_body,
                    'provider' => $result['provider'],
                    'status' => $status,
                    'provider_message_id' => $result['message_id'],
                    'provider_response' => $result['response'],
                    'error_message' => $result['error'],
                    'sent_by' => $row->sent_by,
                    'sent_at' => $status === 'sent' ? now() : null,
                    'created_at' => now(),
                ]);
            }

            if ($status === 'sent') {
                $sent++;
            } else {
                $failed++;
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }
}
