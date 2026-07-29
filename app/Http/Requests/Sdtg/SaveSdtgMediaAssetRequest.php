<?php

namespace App\Http\Requests\Sdtg;

use App\Services\Sdtg\SdtgMediaReadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSdtgMediaAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'tags' => ['nullable', 'string', 'max:255'],
            'media_type' => ['required', Rule::in(SdtgMediaReadService::MEDIA_TYPES)],
            'status' => ['required', Rule::in(SdtgMediaReadService::STATUSES)],
            'folder_id' => ['nullable', 'integer', 'min:1'],
            'crusade_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'external_url' => ['nullable', 'string', 'max:500'],
            'thumbnail_url' => ['nullable', 'string', 'max:500'],
            'video_type' => ['nullable', Rule::in(['local', 'youtube', 'vimeo'])],
            'video_src' => ['nullable', 'string', 'max:500'],
            'duration_label' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'media' => ['nullable', 'file', 'max:204800'],
            'thumbnail' => ['nullable', 'image', 'max:10240'],
            'remove_media' => ['nullable', 'boolean'],
            'remove_thumb' => ['nullable', 'boolean'],
        ];
    }
}
