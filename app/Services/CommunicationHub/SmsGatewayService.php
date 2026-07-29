<?php

namespace App\Services\CommunicationHub;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

final class SmsGatewayService
{
    /**
     * @return array{provider: string, enabled: bool, sender_id: string, api_key: string, api_secret: string, base_url: string}
     */
    public function settings(): array
    {
        $row = Schema::hasTable('communication_settings')
            ? DB::table('communication_settings')->where('id', 1)->first()
            : null;

        return [
            'provider' => strtolower((string) ($row->sms_provider ?? 'termii')),
            'enabled' => (bool) ($row->sms_enabled ?? false),
            'sender_id' => (string) ($row->sms_sender_id ?? 'AGIKENEGBU'),
            'api_key' => (string) ($row->sms_api_key ?? ''),
            'api_secret' => (string) ($row->sms_api_secret ?? ''),
            'base_url' => (string) ($row->sms_base_url ?? 'https://api.ng.termii.com'),
        ];
    }

    /**
     * @return array{success: bool, message_id: ?string, response: ?string, error: ?string, provider: string}
     */
    public function send(string $to, string $message, ?string $senderOverride = null): array
    {
        $settings = $this->settings();
        $provider = $settings['provider'] !== '' ? $settings['provider'] : 'termii';

        if (! $settings['enabled']) {
            return $this->fail($provider, 'SMS sending is disabled in Communication Hub settings.');
        }

        if ($provider === 'twilio') {
            return $this->sendTwilio($settings, $to, $message, $senderOverride);
        }

        return $this->sendTermii($settings, $to, $message, $senderOverride);
    }

    /**
     * @return array{balance: ?float, currency: string, provider: string}
     */
    public function balance(): array
    {
        $settings = $this->settings();
        $provider = $settings['provider'] !== '' ? $settings['provider'] : 'termii';

        if ($provider === 'twilio') {
            if ($settings['api_key'] === '' || $settings['api_secret'] === '') {
                return ['balance' => null, 'currency' => 'USD', 'provider' => $provider];
            }

            try {
                $response = Http::withBasicAuth($settings['api_key'], $settings['api_secret'])
                    ->timeout(20)
                    ->get('https://api.twilio.com/2010-04-01/Accounts/'.rawurlencode($settings['api_key']).'/Balance.json');
                $data = $response->json();

                return [
                    'balance' => isset($data['balance']) ? (float) $data['balance'] : null,
                    'currency' => (string) ($data['currency_iso'] ?? 'USD'),
                    'provider' => $provider,
                ];
            } catch (\Throwable) {
                return ['balance' => null, 'currency' => 'USD', 'provider' => $provider];
            }
        }

        if ($settings['api_key'] === '') {
            return ['balance' => null, 'currency' => 'NGN', 'provider' => $provider];
        }

        $base = rtrim($settings['base_url'] !== '' ? $settings['base_url'] : 'https://api.ng.termii.com', '/');

        try {
            $response = Http::timeout(20)->get($base.'/api/get-balance', ['api_key' => $settings['api_key']]);
            $data = $response->json();

            return [
                'balance' => isset($data['balance']) ? (float) $data['balance'] : null,
                'currency' => (string) ($data['currency'] ?? 'NGN'),
                'provider' => $provider,
            ];
        } catch (\Throwable) {
            return ['balance' => null, 'currency' => 'NGN', 'provider' => $provider];
        }
    }

    public function normalizePhone(string $phone, string $provider): string
    {
        if ($provider === 'twilio') {
            return $this->normalizeE164($phone);
        }

        return $this->normalizeNgDigits($phone);
    }

    private function sendTermii(array $settings, string $to, string $message, ?string $senderOverride): array
    {
        if ($settings['api_key'] === '') {
            return $this->fail('termii', 'Termii API key is missing. Configure it under Email / SMS settings.');
        }

        $phone = $this->normalizeNgDigits($to);
        if ($phone === '' || trim($message) === '') {
            return $this->fail('termii', 'SMS requires a phone number and message body.');
        }

        $base = rtrim($settings['base_url'] !== '' ? $settings['base_url'] : 'https://api.ng.termii.com', '/');
        $sender = trim((string) ($senderOverride ?: $settings['sender_id'])) ?: 'AGIKENEGBU';

        try {
            $response = Http::timeout(30)
                ->acceptJson()
                ->asJson()
                ->post($base.'/api/sms/send', [
                    'to' => $phone,
                    'from' => $sender,
                    'sms' => $message,
                    'type' => 'plain',
                    'channel' => 'generic',
                    'api_key' => $settings['api_key'],
                ]);

            $data = $response->json();
            $ok = $response->successful();

            return [
                'success' => $ok,
                'message_id' => is_array($data) ? (string) ($data['message_id'] ?? $data['messageId'] ?? '') ?: null : null,
                'response' => $response->body(),
                'error' => $ok ? null : (is_array($data) ? (string) ($data['message'] ?? 'Termii send failed') : 'Termii send failed'),
                'provider' => 'termii',
            ];
        } catch (\Throwable $e) {
            return $this->fail('termii', 'Termii connection failed: '.$e->getMessage());
        }
    }

    private function sendTwilio(array $settings, string $to, string $message, ?string $senderOverride): array
    {
        if ($settings['api_key'] === '' || $settings['api_secret'] === '') {
            return $this->fail('twilio', 'Twilio Account SID and Auth Token are required.');
        }

        $from = $this->normalizeE164((string) ($senderOverride ?: $settings['sender_id']));
        $phone = $this->normalizeE164($to);
        if ($from === '') {
            return $this->fail('twilio', 'Twilio From must be your Twilio phone number in E.164 format (e.g. +15551234567).');
        }
        if ($phone === '' || trim($message) === '') {
            return $this->fail('twilio', 'Twilio requires a destination number and message body.');
        }

        try {
            $response = Http::withBasicAuth($settings['api_key'], $settings['api_secret'])
                ->asForm()
                ->timeout(30)
                ->post('https://api.twilio.com/2010-04-01/Accounts/'.rawurlencode($settings['api_key']).'/Messages.json', [
                    'To' => $phone,
                    'From' => $from,
                    'Body' => $message,
                ]);

            $data = $response->json();
            $ok = $response->successful();
            $error = null;
            if (! $ok && is_array($data)) {
                $error = (string) ($data['message'] ?? $data['error_message'] ?? 'Twilio send failed');
                if (! empty($data['code'])) {
                    $error .= ' (code '.$data['code'].')';
                }
            }

            return [
                'success' => $ok,
                'message_id' => is_array($data) ? ((string) ($data['sid'] ?? '') ?: null) : null,
                'response' => $response->body(),
                'error' => $ok ? null : ($error ?: 'Twilio send failed'),
                'provider' => 'twilio',
            ];
        } catch (\Throwable $e) {
            return $this->fail('twilio', 'Twilio connection failed: '.$e->getMessage());
        }
    }

    private function normalizeNgDigits(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '234') && strlen($digits) >= 13) {
            return $digits;
        }
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '234'.substr($digits, 1);
        }
        if (strlen($digits) === 10 && preg_match('/^[789]/', $digits)) {
            return '234'.$digits;
        }

        return $digits;
    }

    private function normalizeE164(string $phone): string
    {
        $trimmed = trim($phone);
        if ($trimmed === '') {
            return '';
        }

        $digits = $this->normalizeNgDigits($trimmed);
        if ($digits === '') {
            return '';
        }

        return '+'.ltrim($digits, '+');
    }

    /** @return array{success: bool, message_id: null, response: null, error: string, provider: string} */
    private function fail(string $provider, string $error): array
    {
        return [
            'success' => false,
            'message_id' => null,
            'response' => null,
            'error' => $error,
            'provider' => $provider,
        ];
    }
}
