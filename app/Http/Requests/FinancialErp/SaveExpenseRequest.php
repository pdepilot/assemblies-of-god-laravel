<?php

namespace App\Http\Requests\FinancialErp;

use Illuminate\Foundation\Http\FormRequest;

class SaveExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'expense_date' => ['required', 'date'],
            'category_id' => ['required', 'integer', 'exists:erp_expense_categories,id'],
            'vendor_id' => ['nullable', 'integer', 'exists:erp_vendors,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'reference_no' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
