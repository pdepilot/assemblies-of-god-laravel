<?php

namespace App\Http\Requests\Events;

use App\Services\Events\EventReadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'event_date' => ['required', 'date'],
            'event_time' => ['nullable', 'string', 'max:20'],
            'location' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', Rule::in(EventReadService::CATEGORIES)],
            'recurrence_label' => ['nullable', 'string', 'max:120'],
            'schedule_display' => ['nullable', 'string', 'max:120'],
            'public_category_label' => ['nullable', 'string', 'max:80'],
            'icon_class' => ['nullable', 'string', 'max:50'],
            'cta_text' => ['nullable', 'string', 'max:100'],
            'cta_url' => ['nullable', 'string', 'max:255'],
            'is_recurring' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'expected_attendance' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(EventReadService::STATUSES)],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }
}
