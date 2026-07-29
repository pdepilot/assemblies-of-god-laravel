<?php

namespace App\Http\Requests\Newsletter;

use Illuminate\Foundation\Http\FormRequest;

class SendNewsletterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'audience' => ['required', 'in:selected,all_active'],
            'subscriber_ids' => ['nullable', 'array'],
            'subscriber_ids.*' => ['integer', 'min:1'],
            'template_id' => ['nullable', 'integer', 'min:1'],
            'subject' => ['nullable', 'string', 'max:200'],
            'body' => ['nullable', 'string', 'max:50000'],
        ];
    }
}
