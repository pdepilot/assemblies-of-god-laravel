<?php

namespace App\Http\Requests\SundaySchool;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAttendanceRequest extends FormRequest
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
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'integer', 'exists:sunday_school_students,id'],
            'records.*.class_id' => ['required', 'integer', 'exists:sunday_school_classes,id'],
            'records.*.attendance_date' => ['required', 'date'],
            'records.*.status' => ['nullable', Rule::in(['present', 'absent', 'excused'])],
            'records.*.arrival_status' => ['nullable', Rule::in(['early', 'on_time', 'late', 'unknown'])],
            'records.*.offering_amount' => ['nullable', 'numeric', 'min:0'],
            'records.*.memory_verse' => ['nullable', 'boolean'],
        ];
    }
}
