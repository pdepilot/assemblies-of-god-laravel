<?php

namespace App\Services\CommunicationHub;

use Illuminate\Support\Facades\DB;

final class NewsletterDraftReadService
{
    public const STATUSES = ['draft', 'template', 'scheduled', 'published', 'archived'];

    /** @return list<array<string, mixed>> */
    public function listDrafts(): array
    {
        return DB::table('newsletter_drafts')
            ->select('id', 'title', 'status', 'created_at', 'updated_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function getDraft(int $id): ?array
    {
        $row = DB::table('newsletter_drafts')->where('id', $id)->first();
        if (! $row) {
            return null;
        }
        $data = (array) $row;
        if (is_string($data['sections_json'] ?? null)) {
            $decoded = json_decode($data['sections_json'], true);
            $data['sections_json'] = is_array($decoded) ? $decoded : [];
        }

        return $data;
    }

    /** @return list<array<string, mixed>> */
    public static function defaultSections(): array
    {
        return [
            ['type' => 'hero', 'title' => 'Hero Banner', 'content' => ''],
            ['type' => 'pastor_message', 'title' => "Pastor's Message", 'content' => ''],
            ['type' => 'events', 'title' => 'Upcoming Events', 'content' => ''],
            ['type' => 'announcements', 'title' => 'Announcements', 'content' => ''],
            ['type' => 'footer', 'title' => 'Footer', 'content' => ''],
        ];
    }
}
