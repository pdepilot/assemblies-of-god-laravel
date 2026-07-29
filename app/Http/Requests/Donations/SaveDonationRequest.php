<?php

namespace App\Http\Requests\Donations;

use App\Services\Donations\DonationReadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveDonationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'donor_name' => ['nullable', 'string', 'max:255'],
            'donor_email' => ['nullable', 'email', 'max:255'],
            'donor_phone' => ['nullable', 'string', 'max:30'],
            'donor_location' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'size:3'],
            'category_id' => ['nullable', 'integer', 'exists:donation_categories,id'],
            'category_slug' => ['nullable', 'string', 'max:50'],
            'fund_scope' => ['nullable', Rule::in(DonationReadService::FUND_SCOPES)],
            'payment_method' => ['nullable', Rule::in(DonationReadService::PAYMENT_METHODS)],
            'is_anonymous' => ['nullable', 'boolean'],
            'donation_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
