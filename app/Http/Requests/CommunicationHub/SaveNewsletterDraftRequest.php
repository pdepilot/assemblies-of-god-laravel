<?php

namespace App\Http\Requests\CommunicationHub;

use App\Services\CommunicationHub\NewsletterDraftReadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveNewsletterDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(NewsletterDraftReadService::STATUSES)],
            'html_preview' => ['nullable', 'string'],
            'sections_json' => ['nullable', 'string'],
        ];
    }
}
