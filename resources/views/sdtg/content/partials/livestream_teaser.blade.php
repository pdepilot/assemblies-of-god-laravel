@php $content = $content ?? []; @endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="lt_eyebrow">Eyebrow</label>
        <input id="lt_eyebrow" name="content[eyebrow]" value="{{ old('content.eyebrow', $content['eyebrow'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="lt_heading">Heading</label>
        <input id="lt_heading" name="content[heading]" value="{{ old('content.heading', $content['heading'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
</div>
<div>
    <label class="block text-sm font-medium" for="lt_description">Description</label>
    <textarea id="lt_description" name="content[description]" rows="3" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">{{ old('content.description', $content['description'] ?? '') }}</textarea>
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="lt_youtube_url">YouTube URL</label>
        <input id="lt_youtube_url" name="content[youtube_url]" value="{{ old('content.youtube_url', $content['youtube_url'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="lt_facebook_url">Facebook URL</label>
        <input id="lt_facebook_url" name="content[facebook_url]" value="{{ old('content.facebook_url', $content['facebook_url'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="lt_cta_label">CTA label</label>
        <input id="lt_cta_label" name="content[cta_label]" value="{{ old('content.cta_label', $content['cta_label'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="lt_cta_link">CTA link</label>
        <input id="lt_cta_link" name="content[cta_link]" value="{{ old('content.cta_link', $content['cta_link'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
</div>
@include('sdtg.content.partials._media_upload', [
    'field' => 'preview_image',
    'label' => 'Preview image',
    'kind' => 'image',
    'mediaUrls' => $mediaUrls ?? [],
])
