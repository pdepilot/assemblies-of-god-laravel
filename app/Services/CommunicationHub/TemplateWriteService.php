<?php

namespace App\Services\CommunicationHub;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class TemplateWriteService
{
    public function __construct(
        private readonly EmailTemplateBodyService $bodies,
    ) {}

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function save(array $data, int $adminId): array
    {
        $id = (int) ($data['id'] ?? 0);
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Template name is required.');
        }

        $channel = (string) ($data['channel'] ?? 'email');
        if (! in_array($channel, TemplateReadService::CHANNELS, true)) {
            $channel = 'email';
        }

        $status = (string) ($data['status'] ?? 'published');
        if (! in_array($status, TemplateReadService::STATUSES, true)) {
            $status = 'published';
        }

        $bodySource = trim((string) ($data['body_text'] ?? $data['body_html'] ?? ''));
        if ($channel === 'email') {
            $bodyPlain = $this->bodies->toPlainText($bodySource);
            $bodyHtml = '';
            $bodyText = $bodyPlain;
        } else {
            $bodyHtml = (string) ($data['body_html'] ?? '');
            $bodyText = $bodySource !== '' ? $bodySource : (string) ($data['body_text'] ?? '');
        }

        $payload = [
            'name' => $name,
            'category_slug' => trim((string) ($data['category_slug'] ?? 'general')) ?: 'general',
            'channel' => $channel,
            'subject' => trim((string) ($data['subject'] ?? '')) ?: null,
            'body_html' => $bodyHtml !== '' ? $bodyHtml : null,
            'body_text' => $bodyText,
            'status' => $status,
            'updated_by' => $adminId > 0 ? $adminId : null,
            'updated_at' => now(),
        ];

        if ($id > 0) {
            $existing = DB::table('communication_templates')->where('id', $id)->first();
            if (! $existing) {
                throw new InvalidArgumentException('Template not found.');
            }
            DB::table('communication_templates')
                ->where('id', $id)
                ->update($payload + ['version' => (int) $existing->version + 1]);
        } else {
            $slug = $this->slugify((string) ($data['slug'] ?? $name), $channel);
            DB::table('communication_templates')->insert($payload + [
                'slug' => $slug,
                'version' => 1,
                'is_system' => false,
                'created_by' => $adminId > 0 ? $adminId : null,
                'created_at' => now(),
            ]);
            $id = (int) DB::getPdo()->lastInsertId();
        }

        $row = DB::table('communication_templates')->where('id', $id)->first();

        return $row ? (array) $row : ['id' => $id];
    }

    private function slugify(string $value, string $channel): string
    {
        $slug = Str::slug($value, '_');
        if ($slug === '') {
            $slug = 'template';
        }

        $base = $slug;
        $suffix = 1;
        while (DB::table('communication_templates')->where('slug', $slug)->where('channel', $channel)->exists()) {
            $slug = $base . '_' . $suffix;
            $suffix++;
        }

        return $slug;
    }
}
