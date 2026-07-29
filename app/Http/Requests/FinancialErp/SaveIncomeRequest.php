<?php

namespace App\Http\Requests\FinancialErp;

use Illuminate\Foundation\Http\FormRequest;

class SaveIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'income_date' => ['required', 'date'],
            'category_id' => ['required', 'integer', 'exists:erp_income_categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'member_name' => ['nullable', 'string', 'max:160'],
            'reference_no' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
