<?php

namespace App\Services\CommunicationHub;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AutomationReadService
{
    public const CHANNELS = ['email', 'sms', 'multi', 'in_app'];

    /** @return list<array<string, mixed>> */
    public function listRules(): array
    {
        if (! Schema::hasTable('automation_rules')) {
            return [];
        }

        return DB::table('automation_rules')
            ->orderBy('priority')
            ->orderBy('name')
            ->get()
            ->map(fn ($row) => $this->formatRow((array) $row))
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function getRule(int $id): ?array
    {
        if (! Schema::hasTable('automation_rules')) {
            return null;
        }

        $row = DB::table('automation_rules')->where('id', $id)->first();

        return $row ? $this->formatRow((array) $row) : null;
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function formatRow(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'rule_key' => (string) $row['rule_key'],
            'name' => (string) $row['name'],
            'description' => $row['description'] ?? null,
            'trigger_event' => (string) $row['trigger_event'],
            'conditions_json' => $row['conditions_json'] ?? null,
            'actions_json' => $row['actions_json'] ?? null,
            'channel' => (string) $row['channel'],
            'template_slug' => $row['template_slug'] ?? null,
            'priority' => (int) $row['priority'],
            'is_enabled' => ! empty($row['is_enabled']),
            'is_system' => ! empty($row['is_system']),
            'last_run_at' => $row['last_run_at'] ?? null,
            'run_count' => (int) ($row['run_count'] ?? 0),
            'created_by' => $row['created_by'] ?? null,
            'created_at' => (string) ($row['created_at'] ?? ''),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
        ];
    }
}
