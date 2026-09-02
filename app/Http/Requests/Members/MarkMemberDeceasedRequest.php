<?php

namespace App\Http\Requests\Members;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarkMemberDeceasedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'member_id' => [
                'required',
                'integer',
                Rule::exists('members', 'id')->where(fn ($q) => $q->where('status', '<>', 'deceased')),
            ],
            'date_of_death' => ['required', 'date', 'before_or_equal:today'],
            'death_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'member_id.exists' => 'Select a living member from the church directory.',
            'date_of_death.required' => 'Date of death is required.',
        ];
    }
}
