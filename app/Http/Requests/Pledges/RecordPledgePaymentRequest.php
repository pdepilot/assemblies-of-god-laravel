<?php

namespace App\Http\Requests\Pledges;

use Illuminate\Foundation\Http\FormRequest;

class RecordPledgePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
