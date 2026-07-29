<?php

namespace App\Http\Requests\CommunicationHub;

use Illuminate\Foundation\Http\FormRequest;

class SaveHubSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['sms_enabled', 'birthday_auto_email_enabled', 'birthday_auto_sms_enabled'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => $this->boolean($field)]);
            }
        }

        if ($this->input('smtp_pass') === '') {
            $this->request->remove('smtp_pass');
        }
        if ($this->input('sms_api_key') === '') {
            $this->request->remove('sms_api_key');
        }
        if ($this->input('sms_api_secret') === '') {
            $this->request->remove('sms_api_secret');
        }
        foreach (['church_website', 'sms_base_url', 'reply_to'] as $optional) {
            if ($this->input($optional) === '') {
                $this->merge([$optional => null]);
            }
        }

        if ($this->filled('church_website')) {
            $this->merge(['church_website' => $this->normalizeWebsiteUrl((string) $this->input('church_website'))]);
        }
        if ($this->filled('sms_base_url')) {
            $this->merge(['sms_base_url' => $this->normalizeWebsiteUrl((string) $this->input('sms_base_url'))]);
        }
    }

    private function normalizeWebsiteUrl(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (! preg_match('~^https?://~i', $value)) {
            $value = 'https://'.ltrim($value, '/');
        }

        return $value;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'default_channel' => ['nullable', 'string', 'max:40'],
            'sms_enabled' => ['nullable', 'boolean'],
            'sms_provider' => ['nullable', 'string', 'max:40'],
            'sms_sender_id' => ['nullable', 'string', 'max:32'],
            'sms_api_key' => ['nullable', 'string', 'max:255'],
            'sms_api_secret' => ['nullable', 'string', 'max:255'],
            'sms_base_url' => ['nullable', 'url', 'max:255'],
            'birthday_auto_email_enabled' => ['nullable', 'boolean'],
            'birthday_auto_sms_enabled' => ['nullable', 'boolean'],
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'retry_max_attempts' => ['nullable', 'integer', 'min:1', 'max:10'],
            'brand_motto' => ['nullable', 'string', 'max:255'],
            'brand_theme_primary' => ['nullable', 'string', 'max:20'],
            'brand_theme_accent' => ['nullable', 'string', 'max:20'],
            'include_pastor_signature' => ['nullable', 'boolean'],
            'include_qr_code' => ['nullable', 'boolean'],
            'newsletter_default_footer' => ['nullable', 'string'],
            'from_email' => ['nullable', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'reply_to' => ['nullable', 'email', 'max:255'],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_user' => ['nullable', 'string', 'max:255'],
            'smtp_pass' => ['nullable', 'string', 'max:255'],
            'smtp_encryption' => ['nullable', 'string', 'max:16'],
            'church_address' => ['nullable', 'string', 'max:500'],
            'church_phone' => ['nullable', 'string', 'max:50'],
            'church_email' => ['nullable', 'email', 'max:255'],
            'church_website' => ['nullable', 'url', 'max:255'],
            'pastor_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
