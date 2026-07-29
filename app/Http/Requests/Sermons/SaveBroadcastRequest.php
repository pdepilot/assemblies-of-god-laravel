<?php

namespace App\Http\Requests\Sermons;

use Illuminate\Foundation\Http\FormRequest;

class SaveBroadcastRequest extends FormRequest
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
            'stream_date' => ['required', 'date'],
            'start_time' => ['required', 'string'],
            'broadcast_type' => ['nullable', 'string', 'max:20'],
            'platform' => ['nullable', 'string', 'max:40'],
            'status' => ['nullable', 'string', 'max:20'],
            'minister_name' => ['nullable', 'string', 'max:255'],
            'embed_url' => ['nullable', 'string', 'max:1000'],
            'stream_url' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
