<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

final class SavePlatformSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'group' => ['required', 'string', 'in:general,church,website_design,rbac'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'language' => ['nullable', 'string', 'max:16'],
            'currency' => ['nullable', 'string', 'max:8'],
            'date_format' => ['nullable', 'string', 'max:16'],
            'records_per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
            'name' => ['nullable', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:120'],
            'pastor' => ['nullable', 'string', 'max:255'],
            'founded_year' => ['nullable', 'integer', 'min:1800', 'max:2100'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'service_sunday' => ['nullable', 'string', 'max:255'],
            'service_midweek' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'remove_logo' => ['nullable', 'boolean'],
            'social_facebook' => ['nullable', 'string', 'max:255'],
            'social_instagram' => ['nullable', 'string', 'max:255'],
            'social_youtube' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'string', 'max:7'],
            'secondary_color' => ['nullable', 'string', 'max:7'],
            'dark_color' => ['nullable', 'string', 'max:7'],
            'text_color' => ['nullable', 'string', 'max:7'],
            'background_color' => ['nullable', 'string', 'max:7'],
            'accent_color' => ['nullable', 'string', 'max:7'],
            'enforcement_enabled' => ['nullable', 'boolean'],
            'debug_enabled' => ['nullable', 'boolean'],
        ];
    }
}
