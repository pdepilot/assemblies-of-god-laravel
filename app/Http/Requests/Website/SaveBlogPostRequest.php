<?php

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;

class SaveBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_published' => $this->boolean('is_published'),
            'remove_featured_image' => $this->boolean('remove_featured_image'),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:50'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body_html' => ['required', 'string'],
            'author' => ['nullable', 'string', 'max:120'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'seo_title' => ['nullable', 'string', 'max:200'],
            'tags' => ['nullable'],
            'featured_image_alt' => ['nullable', 'string', 'max:255'],
            'is_published' => ['nullable', 'boolean'],
            'featured_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'remove_featured_image' => ['nullable', 'boolean'],
        ];
    }
}
