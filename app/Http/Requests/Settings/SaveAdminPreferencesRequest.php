<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

final class SaveAdminPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'ui_theme' => ['required', 'string', 'in:gold,blue,emerald,rose'],
            'ui_mode' => ['required', 'string', 'in:dark,light'],
        ];
    }
}
