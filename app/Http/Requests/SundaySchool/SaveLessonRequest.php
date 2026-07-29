<?php

namespace App\Http\Requests\SundaySchool;

use Illuminate\Foundation\Http\FormRequest;

class SaveLessonRequest extends FormRequest
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
            'lesson_title' => ['required', 'string', 'max:255'],
            'lesson_date' => ['required', 'date'],
            'class_id' => ['nullable', 'integer', 'exists:sunday_school_classes,id'],
            'bible_text' => ['nullable', 'string', 'max:255'],
            'golden_text' => ['nullable', 'string', 'max:255'],
            'objectives' => ['nullable', 'string'],
            'teaching_notes' => ['nullable', 'string'],
            'activities' => ['nullable', 'string'],
        ];
    }
}
