<?php

namespace App\Services\CommunicationHub;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SmsCenterReadService
{
    public function __construct(
        private readonly SmsGatewayService $gateway,
    ) {}

    /**
     * @return array{
     *   items: list<array<string, mixed>>,
     *   total: int,
     *   page: int,
     *   pages: int,
     *   stats: array<string, int>,
     *   balance: array{balance: ?float, currency: string, provider: string},
     *   settings: array<string, mixed>
     * }
     */
    public function board(string $status = '', int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $settings = $this->gateway->settings();

        if (! Schema::hasTable('sms_logs')) {
            return [
                'items' => [],
                'total' => 0,
                'page' => $page,
                'pages' => 1,
                'stats' => ['pending' => 0, 'sent_today' => 0, 'failed_today' => 0],
                'balance' => ['balance' => null, 'currency' => 'NGN', 'provider' => $settings['provider']],
                'settings' => $settings,
            ];
        }

        $query = DB::table('sms_logs');
        if ($status !== '') {
            $query->where('status', $status);
        }

        $total = (clone $query)->count();
        $items = $query
            ->orderByDesc('id')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'recipient_phone' => (string) $row->recipient_phone,
                'recipient_name' => (string) ($row->recipient_name ?? ''),
                'message_body' => (string) $row->message_body,
                'provider' => (string) ($row->provider ?? ''),
                'status' => (string) $row->status,
                'error_message' => (string) ($row->error_message ?? ''),
                'sent_at' => (string) ($row->sent_at ?? ''),
                'created_at' => (string) ($row->created_at ?? ''),
            ])
            ->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => (int) max(1, ceil($total / $perPage)),
            'stats' => $this->stats(),
            'balance' => $this->gateway->balance(),
            'settings' => $settings,
        ];
    }

    /** @return array{pending: int, sent_today: int, failed_today: int} */
    public function stats(): array
    {
        $pending = 0;
        $sentToday = 0;
        $failedToday = 0;

        if (Schema::hasTable('sms_queue')) {
            $pending = (int) DB::table('sms_queue')->where('status', 'pending')->count();
        }
        if (Schema::hasTable('sms_logs')) {
            $sentToday = (int) DB::table('sms_logs')
                ->where('status', 'sent')
                ->whereDate('created_at', now()->toDateString())
                ->count();
            $failedToday = (int) DB::table('sms_logs')
                ->where('status', 'failed')
                ->whereDate('created_at', now()->toDateString())
                ->count();
        }

        return [
            'pending' => $pending,
            'sent_today' => $sentToday,
            'failed_today' => $failedToday,
        ];
    }

    /** @return list<array{id: int, name: string, body_text: string}> */
    public function smsTemplates(): array
    {
        if (! Schema::hasTable('communication_templates')) {
            return [];
        }

        return DB::table('communication_templates')
            ->where('channel', 'sms')
            ->where('status', 'published')
            ->orderBy('name')
            ->get(['id', 'name', 'body_text', 'body_html'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'body_text' => (string) ($row->body_text ?: $row->body_html ?: ''),
            ])
            ->all();
    }
}
