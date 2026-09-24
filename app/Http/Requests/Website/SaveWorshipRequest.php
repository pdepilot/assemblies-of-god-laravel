<?php

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;

class SaveWorshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'programs' => ['required', 'array', 'max:6'],
            'programs.*.day' => ['nullable', 'string', 'max:120'],
            'programs.*.icon' => ['nullable', 'string', 'max:40'],
            'programs.*.title' => ['nullable', 'string', 'max:255'],
            'programs.*.time_primary' => ['nullable', 'string', 'max:80'],
            'programs.*.note_primary' => ['nullable', 'string', 'max:255'],
            'programs.*.time_secondary' => ['nullable', 'string', 'max:80'],
            'programs.*.note_secondary' => ['nullable', 'string', 'max:255'],
            'programs.*.body' => ['nullable', 'string', 'max:2000'],
            'programs.*.cta_label' => ['nullable', 'string', 'max:80'],
            'programs.*.cta_url' => ['nullable', 'string', 'max:500'],
            'worship_map_query' => ['nullable', 'string', 'max:255'],
        ];
    }
}
