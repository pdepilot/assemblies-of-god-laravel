<?php

namespace App\Services\CommunicationHub;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class HubSettingsWriteService
{
    /** @param array<string, mixed> $data */
    public function save(array $data, int $adminId): void
    {
        $hubFields = [
            'default_channel', 'sms_enabled', 'sms_provider', 'sms_sender_id',
            'sms_api_key', 'sms_api_secret', 'sms_base_url',
            'birthday_auto_email_enabled', 'birthday_auto_sms_enabled',
            'rate_limit_per_minute', 'retry_max_attempts',
            'brand_motto', 'brand_theme_primary', 'brand_theme_accent',
            'include_pastor_signature', 'include_qr_code', 'newsletter_default_footer',
        ];
        $hub = [];
        foreach ($hubFields as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }
            if (in_array($field, ['sms_api_key', 'sms_api_secret'], true) && ($data[$field] === null || $data[$field] === '')) {
                continue;
            }
            if ($field === 'birthday_auto_sms_enabled' && ! Schema::hasColumn('communication_settings', 'birthday_auto_sms_enabled')) {
                continue;
            }
            $hub[$field] = $data[$field];
        }
        if ($hub !== []) {
            $hub['updated_by'] = $adminId;
            $hub['updated_at'] = now();
            DB::table('communication_settings')->where('id', 1)->update($hub);
        }

        $emailFields = [
            'from_email', 'from_name', 'reply_to',
            'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_encryption',
            'church_address', 'church_phone', 'church_email', 'church_website', 'pastor_name',
        ];
        $email = [];
        foreach ($emailFields as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }
            if ($field === 'smtp_pass' && ($data[$field] === null || $data[$field] === '')) {
                continue;
            }
            if ($field === 'smtp_encryption' && ! Schema::hasColumn('email_settings', 'smtp_encryption')) {
                continue;
            }
            $email[$field] = $data[$field];
        }
        if ($email !== []) {
            $email['updated_at'] = now();
            DB::table('email_settings')->where('id', 1)->update($email);
        }
    }
}
