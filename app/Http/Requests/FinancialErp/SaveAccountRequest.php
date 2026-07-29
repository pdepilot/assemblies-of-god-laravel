<?php

namespace App\Http\Requests\FinancialErp;

use App\Services\FinancialErp\AccountReadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:160'],
            'account_type' => ['required', Rule::in(AccountReadService::ACCOUNT_TYPES)],
            'subtype' => ['nullable', 'string', 'max:60'],
            'parent_id' => ['nullable', 'integer'],
            'opening_balance' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string'],
            'is_postable' => ['nullable', 'boolean'],
            'is_bank' => ['nullable', 'boolean'],
            'is_cash' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
