<?php

namespace App\Http\Requests\SundaySchool;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveStudentRequest extends FormRequest
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
            'full_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'unspecified'])],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed', 'separated', 'unspecified'])],
            'wedding_date' => ['nullable', 'date', 'required_if:marital_status,married'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'parent_phone' => ['nullable', 'string', 'max:30'],
            'parent_email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'class_id' => ['nullable', 'integer', 'exists:sunday_school_classes,id'],
            'department' => ['nullable', 'string', 'max:100'],
            'date_joined' => ['nullable', 'date'],
            'membership_status' => ['nullable', Rule::in(['visitor', 'member', 'regular', 'full_member', 'baptized', 'unbaptized'])],
            'baptism_status' => ['nullable', Rule::in(['not_baptized', 'baptized', 'unknown'])],
            'status' => ['nullable', Rule::in(['active', 'archived', 'graduated'])],
        ];
    }
}
