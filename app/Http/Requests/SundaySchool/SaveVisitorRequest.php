<?php

namespace App\Http\Requests\SundaySchool;

use Illuminate\Foundation\Http\FormRequest;

class SaveVisitorRequest extends FormRequest
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
            'id' => ['nullable', 'integer'],
            'visitor_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'invited_by' => ['nullable', 'string', 'max:255'],
            'class_id' => ['nullable', 'integer', 'exists:sunday_school_classes,id'],
            'visit_date' => ['required', 'date'],
            'follow_up_status' => ['nullable', 'in:pending,contacted,converted,closed'],
            'follow_up_notes' => ['nullable', 'string'],
        ];
    }
}
