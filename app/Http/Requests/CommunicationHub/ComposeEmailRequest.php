<?php

namespace App\Http\Requests\CommunicationHub;

use Illuminate\Foundation\Http\FormRequest;

final class ComposeEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'recipient_group' => ['required', 'string', 'in:individual,members,visitors'],
            'individual_email' => ['nullable', 'email', 'max:255'],
            'template_id' => ['nullable', 'integer', 'min:1'],
            'subject' => ['required', 'string', 'max:500'],
            'body_html' => ['required', 'string'],
            'priority' => ['nullable', 'string', 'in:low,normal,high'],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }
}
