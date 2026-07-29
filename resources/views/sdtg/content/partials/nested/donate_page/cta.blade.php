@php $content = $content ?? []; @endphp

<div>
    <label class="block text-sm font-medium" for="dp_cta_title">Title</label>
    <input id="dp_cta_title" name="content[title]" value="{{ old('content.title', $content['title'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
</div>
@include('sdtg.content.partials._media_upload', [
    'field' => 'image',
    'label' => 'CTA image',
    'kind' => 'image',
    'mediaUrls' => $mediaUrls ?? [],
])
<div>
    <label class="block text-sm font-medium" for="dp_cta_subtitle">Subtitle</label>
    <textarea id="dp_cta_subtitle" name="content[subtitle]" rows="2" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">{{ old('content.subtitle', $content['subtitle'] ?? '') }}</textarea>
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="dp_cta_primary_label">Primary button label</label>
        <input id="dp_cta_primary_label" name="content[primary_label]" value="{{ old('content.primary_label', $content['primary_label'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="dp_cta_primary_link">Primary button link</label>
        <input id="dp_cta_primary_link" name="content[primary_link]" value="{{ old('content.primary_link', $content['primary_link'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="dp_cta_secondary_label">Secondary button label</label>
        <input id="dp_cta_secondary_label" name="content[secondary_label]" value="{{ old('content.secondary_label', $content['secondary_label'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="dp_cta_secondary_link">Secondary button link</label>
        <input id="dp_cta_secondary_link" name="content[secondary_link]" value="{{ old('content.secondary_link', $content['secondary_link'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
</div>
