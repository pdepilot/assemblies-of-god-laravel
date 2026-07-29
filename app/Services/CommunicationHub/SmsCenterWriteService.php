<?php

namespace App\Services\CommunicationHub;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class SmsCenterWriteService
{
    public function __construct(
        private readonly SmsGatewayService $gateway,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{status: string, phone: string, provider: string, error: ?string}
     */
    public function compose(array $data, int $adminId): array
    {
        if (! Schema::hasTable('sms_queue') || ! Schema::hasTable('sms_logs')) {
            throw new InvalidArgumentException('SMS tables are not available. Run migrations first.');
        }

        $settings = $this->gateway->settings();
        $provider = $settings['provider'] !== '' ? $settings['provider'] : 'termii';
        $rawPhone = trim((string) ($data['phone'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));
        $name = trim((string) ($data['name'] ?? '')) ?: null;

        if ($rawPhone === '' || $message === '') {
            throw new InvalidArgumentException('Phone and message are required.');
        }

        $phone = $this->gateway->normalizePhone($rawPhone, $provider);
        if ($phone === '') {
            throw new InvalidArgumentException('Enter a valid phone number (e.g. 08012345678).');
        }

        $priority = in_array(($data['priority'] ?? 'normal'), ['low', 'normal', 'high'], true)
            ? (string) $data['priority']
            : 'normal';

        $scheduledAt = trim((string) ($data['scheduled_at'] ?? ''));
        $scheduleValue = $scheduledAt !== ''
            ? date('Y-m-d H:i:s', strtotime($scheduledAt) ?: time())
            : now()->format('Y-m-d H:i:s');
        $immediate = strtotime($scheduleValue) <= time();

        $queueId = DB::table('sms_queue')->insertGetId([
            'recipient_phone' => $phone,
            'recipient_name' => $name,
            'message_body' => $message,
            'provider' => $provider,
            'priority' => $priority,
            'scheduled_at' => $scheduleValue,
            'status' => $immediate ? 'processing' : 'pending',
            'attempts' => $immediate ? 1 : 0,
            'sent_by' => $adminId > 0 ? $adminId : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (! $immediate) {
            DB::table('sms_logs')->insert([
                'queue_id' => $queueId,
                'recipient_phone' => $phone,
                'recipient_name' => $name,
                'message_body' => $message,
                'provider' => $provider,
                'status' => 'queued',
                'sent_by' => $adminId > 0 ? $adminId : null,
                'created_at' => now(),
            ]);

            return [
                'status' => 'scheduled',
                'phone' => $phone,
                'provider' => $provider,
                'error' => null,
            ];
        }

        $result = $this->gateway->send($phone, $message);
        $status = $result['success'] ? 'sent' : 'failed';

        DB::table('sms_queue')->where('id', $queueId)->update([
            'status' => $status,
            'error_message' => $result['error'],
            'provider_response' => $result['response'],
            'provider' => $result['provider'],
            'updated_at' => now(),
        ]);

        DB::table('sms_logs')->insert([
            'queue_id' => $queueId,
            'recipient_phone' => $phone,
            'recipient_name' => $name,
            'message_body' => $message,
            'provider' => $result['provider'],
            'status' => $status,
            'provider_message_id' => $result['message_id'],
            'provider_response' => $result['response'],
            'error_message' => $result['error'],
            'sent_by' => $adminId > 0 ? $adminId : null,
            'sent_at' => $status === 'sent' ? now() : null,
            'created_at' => now(),
        ]);

        if (Schema::hasTable('communication_logs')) {
            DB::table('communication_logs')->insert([
                'channel' => 'sms',
                'direction' => 'outbound',
                'subject' => mb_substr($message, 0, 120),
                'recipient' => $phone,
                'recipient_name' => $name,
                'status' => $status,
                'priority' => $priority,
                'gateway_response' => $result['response'],
                'error_message' => $result['error'],
                'ref_table' => 'sms_logs',
                'sent_by' => $adminId > 0 ? $adminId : null,
                'created_at' => now(),
            ]);
        }

        return [
            'status' => $status,
            'phone' => $phone,
            'provider' => $result['provider'],
            'error' => $result['error'],
        ];
    }
}
