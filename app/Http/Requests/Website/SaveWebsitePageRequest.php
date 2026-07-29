<?php

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;

class SaveWebsitePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:255'],
            'eyebrow' => ['nullable', 'string', 'max:120'],
            'intro' => ['nullable', 'string', 'max:2000'],
            'body_html' => ['nullable', 'string'],
            'cta_label' => ['nullable', 'string', 'max:120'],
            'cta_url' => ['nullable', 'string', 'max:500'],
            'hero_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'remove_hero_image' => ['nullable', 'boolean'],
            'ministries_eyebrow' => ['nullable', 'string', 'max:120'],
            'ministries_title' => ['nullable', 'string', 'max:255'],
            'events_eyebrow' => ['nullable', 'string', 'max:120'],
            'events_title' => ['nullable', 'string', 'max:255'],
            'events_intro' => ['nullable', 'string', 'max:2000'],
            'worship_eyebrow' => ['nullable', 'string', 'max:120'],
            'worship_title' => ['nullable', 'string', 'max:255'],
            'worship_intro' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
