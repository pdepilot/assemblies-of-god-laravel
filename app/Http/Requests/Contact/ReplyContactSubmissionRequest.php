<?php

namespace App\Http\Requests\Contact;

use Illuminate\Foundation\Http\FormRequest;

class ReplyContactSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reply_subject' => ['nullable', 'string', 'max:255'],
            'reply_body' => ['required', 'string', 'min:5'],
        ];
    }
}
