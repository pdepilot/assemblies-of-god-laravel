<?php

namespace App\Http\Requests\CommunicationHub;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class QueueAiJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'job_type' => [
                'required',
                'string',
                Rule::in(['birthday', 'sermon', 'easter', 'christmas', 'event', 'rewrite', 'translate']),
            ],
            'prompt' => ['required', 'string', 'max:10000'],
        ];
    }
}
