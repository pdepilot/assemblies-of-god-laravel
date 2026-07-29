<?php

namespace App\Services\CommunicationHub;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class EmailCenterReadService
{
    public function __construct(
        private readonly EmailTemplateBodyService $bodies,
    ) {}

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public function listHistory(string $folder = '', int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        if (! Schema::hasTable('email_history')) {
            return ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'per_page' => $perPage];
        }

        $query = DB::table('email_history');
        $folder = strtolower(trim($folder));

        if ($folder === 'sent') {
            $query->whereIn('status', ['sent', 'delivered', 'opened', 'clicked']);
        } elseif ($folder === 'failed') {
            $query->where('status', 'failed');
        } elseif ($folder === 'draft') {
            $query->where('status', 'draft');
        } elseif ($folder === 'scheduled') {
            $query->where(function ($q) {
                $q->where('status', 'scheduled')
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('scheduled_at')
                            ->whereIn('status', ['queued', 'pending', 'scheduled']);
                    });
            });
        } elseif ($folder !== '') {
            $query->where('status', $folder);
        }

        $total = (clone $query)->count();
        $items = $query->orderByDesc('id')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'subject' => (string) ($row->subject ?? ''),
                'recipient' => (string) ($row->recipient ?? ''),
                'recipient_name' => (string) ($row->recipient_name ?? ''),
                'status' => (string) ($row->status ?? ''),
                'created_at' => (string) ($row->created_at ?? ''),
                'sent_at' => (string) ($row->sent_at ?? ''),
                'scheduled_at' => (string) ($row->scheduled_at ?? ''),
                'error_message' => (string) ($row->error_message ?? ''),
            ])
            ->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => (int) max(1, ceil($total / $perPage)),
            'per_page' => $perPage,
        ];
    }

    /** @return list<array{id: int, name: string, subject: string, body_html: string, body_text: string}> */
    public function listEmailTemplates(): array
    {
        $templates = [];

        if (Schema::hasTable('email_templates')) {
            foreach (DB::table('email_templates')->where('status', 'published')->orderBy('name')->get() as $row) {
                $plain = $this->plainBody((string) ($row->body_html ?? ''), (string) ($row->body_text ?? ''));
                $templates[] = [
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                    'subject' => (string) ($row->subject ?? ''),
                    'body_html' => $plain,
                    'body_text' => $plain,
                    'source' => 'email_templates',
                ];
            }
        }

        if (Schema::hasTable('communication_templates')) {
            foreach (DB::table('communication_templates')
                ->where('channel', 'email')
                ->where('status', 'published')
                ->orderBy('name')
                ->get() as $row) {
                $plain = $this->plainBody((string) ($row->body_html ?? ''), (string) ($row->body_text ?? ''));
                $templates[] = [
                    'id' => (int) ($row->email_template_id ?: $row->id),
                    'name' => (string) $row->name,
                    'subject' => (string) ($row->subject ?? ''),
                    'body_html' => $plain,
                    'body_text' => $plain,
                    'source' => 'communication_templates',
                ];
            }
        }

        return $templates;
    }

    private function plainBody(string $html, string $text = ''): string
    {
        $source = trim($text) !== '' ? $text : $html;

        return $this->bodies->toPlainText($source);
    }

    /** @return list<array{group_key: string, name: string}> */
    public function recipientGroups(): array
    {
        return [
            ['group_key' => 'individual', 'name' => 'Individual'],
            ['group_key' => 'members', 'name' => 'All members with email'],
            ['group_key' => 'visitors', 'name' => 'Registered visitors with email'],
        ];
    }
}
