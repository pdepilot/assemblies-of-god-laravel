<?php

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;

class SaveActivitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'activities' => ['required', 'array', 'max:8'],
            'activities.*.id' => ['nullable', 'integer', 'min:0'],
            'activities.*.title' => ['nullable', 'string', 'max:255'],
            'activities.*.description' => ['nullable', 'string', 'max:2000'],
            'activities.*.icon_class' => ['nullable', 'string', 'max:50'],
            'activities.*.meeting_schedule' => ['nullable', 'string', 'max:255'],
            'activities.*.read_more_url' => ['nullable', 'string', 'max:500'],
            'activities.*.is_published' => ['nullable'],
        ];
    }
}
