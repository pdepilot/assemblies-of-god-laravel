<?php

namespace App\Services\CommunicationHub;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class AutomationWriteService
{
    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function save(array $input, int $adminId): array
    {
        if (! Schema::hasTable('automation_rules')) {
            throw new InvalidArgumentException('Automation rules table is not available.');
        }

        $id = (int) ($input['id'] ?? 0);
        $name = trim((string) ($input['name'] ?? ''));
        $trigger = trim((string) ($input['trigger_event'] ?? ''));
        if ($name === '' || $trigger === '') {
            throw new InvalidArgumentException('Rule name and trigger event are required.');
        }

        $channel = (string) ($input['channel'] ?? 'email');
        if (! in_array($channel, AutomationReadService::CHANNELS, true)) {
            $channel = 'email';
        }

        $ruleKey = trim((string) ($input['rule_key'] ?? ''));
        if ($ruleKey === '') {
            $ruleKey = Str::slug($name, '_');
        }

        $templateSlug = trim((string) ($input['template_slug'] ?? '')) ?: null;
        $actions = [
            ['type' => 'send', 'channel' => $channel, 'template_slug' => $templateSlug],
        ];

        $payload = [
            'name' => $name,
            'description' => trim((string) ($input['description'] ?? '')) ?: null,
            'trigger_event' => $trigger,
            'conditions_json' => null,
            'actions_json' => json_encode($actions, JSON_UNESCAPED_UNICODE),
            'channel' => $channel,
            'template_slug' => $templateSlug,
            'priority' => (int) ($input['priority'] ?? 100),
            'is_enabled' => filter_var($input['is_enabled'] ?? false, FILTER_VALIDATE_BOOL) ? 1 : 0,
            'updated_at' => now(),
        ];

        if ($id > 0) {
            $existing = DB::table('automation_rules')->where('id', $id)->first();
            if (! $existing) {
                throw new InvalidArgumentException('Automation rule not found.');
            }
            DB::table('automation_rules')->where('id', $id)->update($payload);
        } else {
            $id = (int) DB::table('automation_rules')->insertGetId($payload + [
                'rule_key' => $ruleKey,
                'is_system' => 0,
                'run_count' => 0,
                'created_by' => $adminId > 0 ? $adminId : null,
                'created_at' => now(),
            ]);
        }

        return (new AutomationReadService)->getRule($id) ?? [];
    }

    /** @return array<string, mixed> */
    public function setEnabled(int $id, bool $enabled): array
    {
        if (! Schema::hasTable('automation_rules')) {
            throw new InvalidArgumentException('Automation rules table is not available.');
        }

        $existing = DB::table('automation_rules')->where('id', $id)->first();
        if (! $existing) {
            throw new InvalidArgumentException('Automation rule not found.');
        }

        DB::table('automation_rules')->where('id', $id)->update([
            'is_enabled' => $enabled ? 1 : 0,
            'updated_at' => now(),
        ]);

        return (new AutomationReadService)->getRule($id) ?? [];
    }
}
