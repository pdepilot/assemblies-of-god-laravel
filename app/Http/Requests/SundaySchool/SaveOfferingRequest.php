<?php

namespace App\Http\Requests\SundaySchool;

use Illuminate\Foundation\Http\FormRequest;

class SaveOfferingRequest extends FormRequest
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
            'student_id' => ['required', 'integer', 'exists:sunday_school_students,id'],
            'class_id' => ['required', 'integer', 'exists:sunday_school_classes,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'offering_date' => ['required', 'date'],
        ];
    }
}
