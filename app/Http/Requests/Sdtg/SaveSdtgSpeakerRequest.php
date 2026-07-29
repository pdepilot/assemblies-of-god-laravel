<?php

namespace App\Http\Requests\Sdtg;

use Illuminate\Foundation\Http\FormRequest;

class SaveSdtgSpeakerRequest extends FormRequest
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
            'crusade_year' => ['required', 'integer'],
            'ministry' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'speaker_type' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'max:20'],
            'bio' => ['nullable', 'string'],
            'topic' => ['nullable', 'string', 'max:255'],
        ];
    }
}
