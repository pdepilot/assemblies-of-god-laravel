<?php

namespace App\Http\Requests\SundaySchool;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTeacherRequest extends FormRequest
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
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'unspecified'])],
            'date_joined' => ['nullable', 'date'],
            'class_id' => ['nullable', 'integer', 'exists:sunday_school_classes,id'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'ministry_position' => ['nullable', 'string', 'max:120'],
            'admin_id' => ['nullable', 'integer', 'exists:admins,id'],
            'membership_status' => ['nullable', Rule::in(['visitor', 'member', 'regular', 'full_member', 'baptized', 'unbaptized'])],
            'status' => ['nullable', Rule::in(['active', 'suspended'])],
        ];
    }
}
