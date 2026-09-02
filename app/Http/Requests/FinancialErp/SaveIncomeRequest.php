<?php

namespace App\Http\Requests\FinancialErp;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'category_id' => ['nullable', 'integer', 'exists:erp_income_categories,id'],
            'new_category_name' => ['nullable', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'member_name' => ['nullable', 'string', 'max:160'],
            'reference_no' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $categoryId = (int) $this->input('category_id', 0);
            $newName = trim((string) $this->input('new_category_name', ''));

            if ($categoryId <= 0 && $newName === '') {
                $validator->errors()->add(
                    'category_id',
                    'Select a category from the list, or enter a new category name.'
                );
            }
        });
    }
}
