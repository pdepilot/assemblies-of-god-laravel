<?php

namespace App\Http\Requests\CommunicationHub;

use App\Services\CommunicationHub\AutomationReadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveAutomationRuleRequest extends FormRequest
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
            'rule_key' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'trigger_event' => ['required', 'string', 'max:100'],
            'channel' => ['required', Rule::in(AutomationReadService::CHANNELS)],
            'template_slug' => ['nullable', 'string', 'max:120'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'is_enabled' => ['nullable', 'boolean'],
        ];
    }
}
