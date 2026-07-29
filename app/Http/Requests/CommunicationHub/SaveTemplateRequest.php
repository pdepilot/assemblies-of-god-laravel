<?php

namespace App\Http\Requests\CommunicationHub;

use App\Services\CommunicationHub\TemplateReadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:120'],
            'category_slug' => ['nullable', 'string', 'max:80'],
            'channel' => ['nullable', Rule::in(TemplateReadService::CHANNELS)],
            'subject' => ['nullable', 'string', 'max:500'],
            'body_html' => ['nullable', 'string'],
            'body_text' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(TemplateReadService::STATUSES)],
        ];
    }
}
