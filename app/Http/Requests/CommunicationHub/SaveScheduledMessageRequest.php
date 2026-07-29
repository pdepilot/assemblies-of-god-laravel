<?php

namespace App\Http\Requests\CommunicationHub;

use App\Services\CommunicationHub\ScheduledMessageReadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveScheduledMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'channel' => ['required', Rule::in(ScheduledMessageReadService::CHANNELS)],
            'subject' => ['nullable', 'string', 'max:500'],
            'body_html' => ['nullable', 'string'],
            'body_text' => ['nullable', 'string'],
            'recipient_group_key' => ['nullable', 'string', 'max:80'],
            'priority' => ['required', Rule::in(ScheduledMessageReadService::PRIORITIES)],
            'recurrence' => ['required', Rule::in(ScheduledMessageReadService::RECURRENCES)],
            'scheduled_at' => ['required', 'date'],
            'status' => ['nullable', Rule::in(['scheduled', 'cancelled'])],
            'template_slug' => ['nullable', 'string', 'max:120'],
        ];
    }
}
