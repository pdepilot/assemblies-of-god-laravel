<?php

namespace App\Http\Requests\Pledges;

use Illuminate\Foundation\Http\FormRequest;

class SavePledgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'donor_name' => ['required', 'string', 'max:255'],
            'donor_email' => ['nullable', 'email', 'max:255'],
            'donor_phone' => ['nullable', 'string', 'max:30'],
            'category_id' => ['nullable', 'integer', 'exists:donation_categories,id'],
            'pledged_amount' => ['required', 'numeric', 'min:0.01'],
            'installment_amount' => ['nullable', 'numeric', 'min:0.01'],
            'installment_count' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
