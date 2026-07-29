<?php

namespace App\Services\CommunicationHub;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class TemplateReadService
{
    public function __construct(
        private readonly EmailTemplateBodyService $bodies,
    ) {}

    public const CHANNELS = ['email', 'sms', 'whatsapp', 'push', 'in_app', 'multi'];

    public const STATUSES = ['draft', 'published', 'archived'];

    /** @return list<array<string, mixed>> */
    public function listTemplates(?string $channel = null, ?string $status = null): array
    {
        if (! Schema::hasTable('communication_templates')) {
            return [];
        }

        $query = DB::table('communication_templates')->orderByDesc('id');

        if ($channel !== null && $channel !== '') {
            $query->where('channel', $channel);
        }
        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        return $query->limit(200)->get()->map(function ($row) {
            $item = (array) $row;
            $item['display_body'] = $this->displayBody($item);

            return $item;
        })->all();
    }

    /** @return array<string, mixed>|null */
    public function getTemplate(int $id): ?array
    {
        $row = DB::table('communication_templates')->where('id', $id)->first();
        if (! $row) {
            return null;
        }

        $item = (array) $row;
        $item['display_body'] = $this->displayBody($item);

        return $item;
    }

    /** @param array<string, mixed> $row */
    private function displayBody(array $row): string
    {
        $channel = (string) ($row['channel'] ?? 'email');
        $text = trim((string) ($row['body_text'] ?? ''));
        $html = (string) ($row['body_html'] ?? '');

        if ($channel === 'email') {
            return $this->bodies->toPlainText($text !== '' ? $text : $html);
        }

        return $text !== '' ? $text : $this->bodies->toPlainText($html);
    }

    /** @return list<array<string, mixed>> */
    public function listCategories(): array
    {
        return DB::table('communication_template_categories')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }
}
