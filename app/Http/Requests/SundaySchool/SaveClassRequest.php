<?php

namespace App\Http\Requests\SundaySchool;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveClassRequest extends FormRequest
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
            'class_name' => ['required', 'string', 'max:120'],
            'class_code' => ['nullable', 'string', 'max:20'],
            'age_range' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string'],
            'teacher_id' => ['nullable', 'integer', 'exists:sunday_school_teachers,id'],
            'assistant_teacher_id' => ['nullable', 'integer', 'exists:sunday_school_teachers,id'],
            'max_capacity' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'status' => ['nullable', Rule::in(['active', 'archived'])],
        ];
    }
}
