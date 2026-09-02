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
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int, from: int, to: int}
     */
    public function listHistory(string $folder = '', int $page = 1, int $perPage = 15): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        if (! Schema::hasTable('email_history')) {
            return ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'per_page' => $perPage, 'from' => 0, 'to' => 0];
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

        $total = (int) (clone $query)->count();
        $pages = (int) max(1, (int) ceil($total / $perPage));
        if ($page > $pages) {
            $page = $pages;
            $offset = ($page - 1) * $perPage;
        }

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

        $from = $total === 0 ? 0 : $offset + 1;
        $to = $total === 0 ? 0 : min($offset + count($items), $total);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
            'from' => $from,
            'to' => $to,
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
