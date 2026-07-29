<?php

namespace App\Http\Requests\Testimonies;

use App\Services\Testimonies\SiteTestimonyReadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateSiteTestimonyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(SiteTestimonyReadService::STATUSES)],
            'is_featured' => ['nullable', 'boolean'],
        ];
    }
}
