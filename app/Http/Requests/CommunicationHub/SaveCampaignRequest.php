<?php

namespace App\Http\Requests\CommunicationHub;

use App\Services\CommunicationHub\CampaignReadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveCampaignRequest extends FormRequest
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
            'campaign_type' => ['required', Rule::in(CampaignReadService::TYPES)],
            'status' => ['required', Rule::in(['draft', 'scheduled', 'archived', 'cancelled'])],
            'audience_group_key' => ['nullable', 'string', 'max:80'],
            'subject' => ['nullable', 'string', 'max:500'],
            'body_html' => ['nullable', 'string'],
            'body_text' => ['nullable', 'string'],
            'scheduled_at' => ['nullable', 'date'],
            'template_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
