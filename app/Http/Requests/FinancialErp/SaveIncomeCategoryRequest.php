<?php

namespace App\Http\Requests\FinancialErp;

use Illuminate\Foundation\Http\FormRequest;

class SaveIncomeCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]+$/'],
            'account_id' => ['nullable', 'integer', 'exists:erp_accounts,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => 'Category code may only contain letters, numbers, hyphens, and underscores.',
        ];
    }
}
