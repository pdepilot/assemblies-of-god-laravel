<?php

namespace App\Http\Requests\RegistrationPortals;

use App\Services\RegistrationPortals\RegistrationPortalReadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveRegistrationPortalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'event_name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:120'],
            'event_subtitle' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string'],
            'theme' => ['nullable', 'string', 'max:120'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'registration_opens' => ['nullable', 'date'],
            'registration_closes' => ['nullable', 'date'],
            'venue' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'max_registrants' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(RegistrationPortalReadService::PORTAL_STATUSES)],
            'template' => ['nullable', Rule::in(['conference', 'youth', 'quick'])],
            'fields' => ['nullable'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('status') && ! $this->route('registrationPortal')) {
            $this->merge(['status' => 'open']);
        }

        $fields = $this->input('fields');
        if (is_string($fields)) {
            $decoded = json_decode($fields, true);
            if (is_array($decoded)) {
                $this->merge(['fields' => $decoded]);
            }
        }
    }
}
