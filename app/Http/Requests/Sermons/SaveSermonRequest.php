<?php

namespace App\Http\Requests\Sermons;

use Illuminate\Foundation\Http\FormRequest;

class SaveSermonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'sermon_date' => ['required', 'date'],
            'minister_name' => ['nullable', 'string', 'max:255'],
            'sermon_type' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'max:20'],
            'category_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'content_html' => ['nullable', 'string'],
            'scripture_refs' => ['nullable', 'string', 'max:500'],
        ];
    }
}
