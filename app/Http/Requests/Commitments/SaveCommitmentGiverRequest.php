<?php

namespace App\Http\Requests\Commitments;

use App\Services\Commitments\CommitmentReadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCommitmentGiverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'program_id' => ['required', 'integer', 'exists:commitment_programs,id'],
            'donor_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'committed_amount' => ['required', 'numeric', 'min:0.01'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'frequency' => ['nullable', Rule::in(CommitmentReadService::FREQUENCIES)],
        ];
    }
}
