<?php

namespace App\Services\CommunicationHub;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class NewsletterDraftWriteService
{
    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function save(array $data, int $adminId): array
    {
        $id = (int) ($data['id'] ?? 0);
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new InvalidArgumentException('Newsletter title is required.');
        }

        $status = (string) ($data['status'] ?? 'draft');
        if (! in_array($status, NewsletterDraftReadService::STATUSES, true)) {
            $status = 'draft';
        }

        $sections = $data['sections'] ?? $data['sections_json'] ?? NewsletterDraftReadService::defaultSections();
        if (is_string($sections)) {
            $decoded = json_decode($sections, true);
            $sections = is_array($decoded) ? $decoded : NewsletterDraftReadService::defaultSections();
        }

        $payload = [
            'title' => $title,
            'status' => $status,
            'sections_json' => json_encode($sections, JSON_UNESCAPED_UNICODE),
            'html_preview' => (string) ($data['html_preview'] ?? ''),
            'updated_at' => now(),
        ];

        if ($id > 0) {
            DB::table('newsletter_drafts')->where('id', $id)->update($payload);
        } else {
            DB::table('newsletter_drafts')->insert($payload + [
                'created_by' => $adminId > 0 ? $adminId : null,
                'created_at' => now(),
            ]);
            $id = (int) DB::getPdo()->lastInsertId();
        }

        $read = new NewsletterDraftReadService;

        return $read->getDraft($id) ?? ['id' => $id];
    }
}
