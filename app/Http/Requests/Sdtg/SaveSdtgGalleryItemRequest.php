<?php

namespace App\Http\Requests\Sdtg;

use App\Services\Sdtg\SdtgGalleryReadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveSdtgGalleryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $isVideo = $this->input('media_type') === 'video';

        return [
            'title' => ['required', 'string', 'max:255'],
            'crusade_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'album_id' => ['nullable', 'integer'],
            'media_type' => ['required', Rule::in(SdtgGalleryReadService::MEDIA_TYPES)],
            'category' => ['required', Rule::in(SdtgGalleryReadService::CATEGORIES)],
            'layout_size' => ['nullable', Rule::in(SdtgGalleryReadService::LAYOUTS)],
            'caption' => ['nullable', 'string'],
            'tags' => ['nullable', 'string', 'max:255'],
            'external_url' => ['nullable', 'string', 'max:500'],
            'video_type' => ['nullable', Rule::in(['local', 'youtube'])],
            'video_src' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
            'is_speakers_highlight' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'remove_media' => ['nullable', 'boolean'],
            'media' => $isVideo
                ? ['nullable', 'file', 'mimes:mp4,webm,mov,jpg,jpeg,png,webp', 'max:81920']
                : ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }
}
