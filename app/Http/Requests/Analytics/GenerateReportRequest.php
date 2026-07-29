<?php

namespace App\Http\Requests\Analytics;

use App\Services\Analytics\ReportWriteService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(ReportWriteService::REPORT_TYPES)],
            'period' => ['required', 'string', 'max:50'],
            'format' => ['required', 'string', Rule::in(['csv', 'excel', 'pdf', 'docx', 'word', 'doc'])],
        ];
    }
}
