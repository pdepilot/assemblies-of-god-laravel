<?php

namespace App\Http\Requests\FinancialErp;

use App\Services\FinancialErp\ProjectReadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'code' => ['nullable', 'string', 'max:32'],
            'project_type' => ['nullable', Rule::in(ProjectReadService::PROJECT_TYPES)],
            'budget_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(ProjectReadService::STATUSES)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ];
    }
}
