<?php

namespace App\Http\Requests\SundaySchool;

use Illuminate\Foundation\Http\FormRequest;

class TransferStudentRequest extends FormRequest
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
            'to_class_id' => ['required', 'integer', 'exists:sunday_school_classes,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
