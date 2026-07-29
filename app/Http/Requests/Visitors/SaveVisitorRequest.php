<?php

namespace App\Http\Requests\Visitors;

use App\Services\Visitors\VisitorReadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveVisitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'phone_alt' => ['nullable', 'string', 'max:30'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other', 'unspecified'])],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'first_visit_date' => ['nullable', 'date'],
            'last_visit_date' => ['nullable', 'date'],
            'visit_count' => ['nullable', 'integer', 'min:1'],
            'service_attended' => ['nullable', Rule::in(VisitorReadService::SERVICES)],
            'how_heard' => ['nullable', Rule::in(VisitorReadService::HOW_HEARD)],
            'interested_department' => ['nullable', 'string', 'max:100'],
            'follow_up_status' => ['nullable', Rule::in(VisitorReadService::FOLLOW_UP_STATUSES)],
            'prayer_request' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ];
    }
}
