<?php

namespace App\Http\Requests\Contact;

use App\Services\Contact\ContactSubmissionReadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContactSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(ContactSubmissionReadService::STATUSES)],
            'admin_notes' => ['nullable', 'string'],
        ];
    }
}
