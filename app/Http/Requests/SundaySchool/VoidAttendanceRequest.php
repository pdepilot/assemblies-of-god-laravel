<?php

namespace App\Http\Requests\SundaySchool;

use Illuminate\Foundation\Http\FormRequest;

class VoidAttendanceRequest extends FormRequest
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
            'attendance_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }
}
