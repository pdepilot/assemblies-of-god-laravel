<?php

namespace App\Http\Requests\SundaySchool;

use Illuminate\Foundation\Http\FormRequest;

class BulkPromoteRequest extends FormRequest
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
            'from_class_id' => ['required', 'integer', 'exists:sunday_school_classes,id'],
            'to_class_id' => ['nullable', 'integer', 'exists:sunday_school_classes,id'],
        ];
    }
}
