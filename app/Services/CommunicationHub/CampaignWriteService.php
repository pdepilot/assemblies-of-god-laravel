<?php

namespace App\Services\CommunicationHub;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class CampaignWriteService
{
    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function save(array $input, int $adminId): array
    {
        if (! Schema::hasTable('communication_campaigns')) {
            throw new InvalidArgumentException('Campaigns table is not available.');
        }

        $id = (int) ($input['id'] ?? 0);
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Campaign name is required.');
        }

        $type = (string) ($input['campaign_type'] ?? 'email');
        if (! in_array($type, CampaignReadService::TYPES, true)) {
            $type = 'email';
        }

        $status = (string) ($input['status'] ?? 'draft');
        if (! in_array($status, CampaignReadService::STATUSES, true)) {
            $status = 'draft';
        }

        $scheduledAt = trim((string) ($input['scheduled_at'] ?? ''));
        if ($scheduledAt !== '') {
            $scheduledAt = str_replace('T', ' ', $scheduledAt);
            if (strlen($scheduledAt) === 16) {
                $scheduledAt .= ':00';
            }
        } else {
            $scheduledAt = null;
        }

        if ($status === 'scheduled' && $scheduledAt === null) {
            throw new InvalidArgumentException('Scheduled campaigns require a schedule date/time.');
        }

        $payload = [
            'name' => $name,
            'campaign_type' => $type,
            'status' => $status,
            'template_id' => ! empty($input['template_id']) ? (int) $input['template_id'] : null,
            'audience_group_key' => trim((string) ($input['audience_group_key'] ?? '')) ?: null,
            'subject' => trim((string) ($input['subject'] ?? '')) ?: null,
            'body_html' => (string) ($input['body_html'] ?? ''),
            'body_text' => trim((string) ($input['body_text'] ?? '')) ?: strip_tags((string) ($input['body_html'] ?? '')),
            'scheduled_at' => $scheduledAt,
            'updated_at' => now(),
        ];

        if ($id > 0) {
            $existing = DB::table('communication_campaigns')->where('id', $id)->first();
            if (! $existing) {
                throw new InvalidArgumentException('Campaign not found.');
            }
            DB::table('communication_campaigns')->where('id', $id)->update($payload);
        } else {
            $id = (int) DB::table('communication_campaigns')->insertGetId($payload + [
                'campaign_code' => 'CMP'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
                'created_by' => $adminId > 0 ? $adminId : null,
                'created_at' => now(),
            ]);
        }

        return (new CampaignReadService)->getCampaign($id) ?? [];
    }

    public function delete(int $id): void
    {
        if (! Schema::hasTable('communication_campaigns')) {
            throw new InvalidArgumentException('Campaigns table is not available.');
        }

        $existing = DB::table('communication_campaigns')->where('id', $id)->first();
        if (! $existing) {
            throw new InvalidArgumentException('Campaign not found.');
        }

        DB::table('communication_campaigns')->where('id', $id)->delete();
    }
}
