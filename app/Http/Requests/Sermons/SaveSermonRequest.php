<?php

namespace App\Http\Requests\Sermons;

use Illuminate\Foundation\Http\FormRequest;

class SaveSermonRequest extends FormRequest
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
            'sermon_date' => ['required', 'date'],
            'minister_name' => ['nullable', 'string', 'max:255'],
            'sermon_type' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'max:20'],
            'category_id' => ['nullable', 'integer'],
            'series_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'content_html' => ['nullable', 'string'],
            'scripture_refs' => ['nullable', 'string', 'max:500'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:500'],
            'tags' => ['nullable'],
            'youtube_url' => ['nullable', 'string', 'max:1000'],
            'audio_stream_url' => ['nullable', 'string', 'max:1000'],
            'featured_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'video_file' => ['nullable', 'file', 'mimes:mp4,webm,mov,m4v', 'max:15360'],
            'remove_featured_image' => ['nullable', 'boolean'],
            'remove_video' => ['nullable', 'boolean'],
        ];
    }
}
