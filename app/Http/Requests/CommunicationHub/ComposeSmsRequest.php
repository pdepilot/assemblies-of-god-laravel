<?php

namespace App\Http\Requests\CommunicationHub;

use Illuminate\Foundation\Http\FormRequest;

final class ComposeSmsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:30'],
            'name' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:640'],
            'template_id' => ['nullable', 'integer', 'min:1'],
            'priority' => ['nullable', 'string', 'in:low,normal,high'],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }
}
