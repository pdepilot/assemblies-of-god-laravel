<?php

namespace App\Services\CommunicationHub;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class RecipientGroupReadService
{
    /**
     * @return list<array{
     *     group_key: string,
     *     name: string,
     *     description: string,
     *     group_type: string,
     *     is_system: bool,
     *     member_count_cache: int|null
     * }>
     */
    public function listGroups(): array
    {
        $groups = [];
        $keys = [];

        if (Schema::hasTable('recipient_groups')) {
            foreach (DB::table('recipient_groups')->orderBy('name')->get() as $row) {
                $key = (string) $row->group_key;
                $groups[] = [
                    'group_key' => $key,
                    'name' => (string) $row->name,
                    'description' => (string) ($row->description ?? ''),
                    'group_type' => (string) ($row->group_type ?? 'dynamic'),
                    'is_system' => (bool) ($row->is_system ?? false),
                    'member_count_cache' => isset($row->member_count_cache) ? (int) $row->member_count_cache : null,
                ];
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

    /**
     * @return list<array{
     *     group_key: string,
     *     name: string,
     *     description: string,
     *     group_type: string,
     *     is_system: bool,
     *     member_count_cache: int|null
     * }>
     */
    public function builtinRecipientGroups(): array
    {
        $builtins = [
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

        return array_map(static fn (array $g): array => [
            'group_key' => $g['group_key'],
            'name' => $g['name'],
            'description' => '',
            'group_type' => 'dynamic',
            'is_system' => true,
            'member_count_cache' => null,
        ], $builtins);
    }
}
