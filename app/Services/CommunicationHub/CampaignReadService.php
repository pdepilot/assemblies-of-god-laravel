<?php

namespace App\Services\CommunicationHub;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class CampaignReadService
{
    public const TYPES = ['email', 'sms', 'combined', 'whatsapp', 'push'];

    public const STATUSES = ['draft', 'scheduled', 'running', 'completed', 'archived', 'cancelled'];

    /** @return list<array<string, mixed>> */
    public function listCampaigns(int $limit = 200): array
    {
        if (! Schema::hasTable('communication_campaigns')) {
            return [];
        }

        return DB::table('communication_campaigns')
            ->orderByDesc('id')
            ->limit(max(1, min(500, $limit)))
            ->get()
            ->map(fn ($row) => $this->formatCampaign((array) $row))
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function getCampaign(int $id): ?array
    {
        if (! Schema::hasTable('communication_campaigns')) {
            return null;
        }

        $row = DB::table('communication_campaigns')->where('id', $id)->first();

        return $row ? $this->formatCampaign((array) $row) : null;
    }

    /** @return list<array{group_key: string, name: string}> */
    public function audienceOptions(): array
    {
        $groups = [];
        $keys = [];

        if (Schema::hasTable('recipient_groups')) {
            foreach (DB::table('recipient_groups')->orderBy('name')->get(['group_key', 'name']) as $row) {
                $key = (string) $row->group_key;
                $groups[] = ['group_key' => $key, 'name' => (string) $row->name];
                $keys[] = $key;
            }
        }

        foreach ($this->builtinRecipientGroups() as $group) {
            if (! in_array($group['group_key'], $keys, true)) {
                $groups[] = $group;
            }
        }

        usort($groups, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $groups;
    }

    /** @return list<array{group_key: string, name: string}> */
    public function builtinRecipientGroups(): array
    {
        return [
            ['group_key' => 'website_subscribers', 'name' => 'Website Newsletter Subscribers'],
            ['group_key' => 'all_members', 'name' => 'All Members'],
            ['group_key' => 'workers', 'name' => 'Workers'],
            ['group_key' => 'pastors', 'name' => 'Pastors'],
            ['group_key' => 'department_leaders', 'name' => 'Department Leaders'],
            ['group_key' => 'youth', 'name' => 'Youth'],
            ['group_key' => 'women', 'name' => 'Women'],
            ['group_key' => 'men', 'name' => 'Men'],
            ['group_key' => 'children', 'name' => 'Children'],
            ['group_key' => 'choir', 'name' => 'Choir'],
            ['group_key' => 'media_team', 'name' => 'Media Team'],
            ['group_key' => 'first_timers', 'name' => 'First Timers'],
            ['group_key' => 'new_converts', 'name' => 'New Converts'],
            ['group_key' => 'volunteers', 'name' => 'Volunteers'],
            ['group_key' => 'finance_team', 'name' => 'Finance Team'],
            ['group_key' => 'event_registrants', 'name' => 'Event Registrants'],
            ['group_key' => 'visitors', 'name' => 'Visitors'],
        ];
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function formatCampaign(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'campaign_code' => (string) $row['campaign_code'],
            'name' => (string) $row['name'],
            'campaign_type' => (string) $row['campaign_type'],
            'status' => (string) $row['status'],
            'template_id' => $row['template_id'] ?? null,
            'audience_group_key' => $row['audience_group_key'] ?? null,
            'subject' => $row['subject'] ?? null,
            'body_html' => (string) ($row['body_html'] ?? ''),
            'body_text' => (string) ($row['body_text'] ?? ''),
            'scheduled_at' => $row['scheduled_at'] ?? null,
            'started_at' => $row['started_at'] ?? null,
            'completed_at' => $row['completed_at'] ?? null,
            'created_by' => $row['created_by'] ?? null,
            'created_at' => (string) ($row['created_at'] ?? ''),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
        ];
    }
}
