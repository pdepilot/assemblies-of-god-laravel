@php $content = $content ?? []; $mediaUrls = $mediaUrls ?? []; @endphp

<div>
    <label class="block text-sm font-medium" for="hero_eyebrow">Eyebrow</label>
    <input id="hero_eyebrow" name="content[eyebrow]" value="{{ old('content.eyebrow', $content['eyebrow'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="hero_title_line1">Title line 1</label>
        <input id="hero_title_line1" name="content[title_line1]" value="{{ old('content.title_line1', $content['title_line1'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="hero_title_line2">Title line 2</label>
        <input id="hero_title_line2" name="content[title_line2]" value="{{ old('content.title_line2', $content['title_line2'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
</div>
<div>
    <label class="block text-sm font-medium" for="hero_subtitle">Subtitle</label>
    <textarea id="hero_subtitle" name="content[subtitle]" rows="3" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">{{ old('content.subtitle', $content['subtitle'] ?? '') }}</textarea>
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="hero_primary_label">Primary button label</label>
        <input id="hero_primary_label" name="content[primary_label]" value="{{ old('content.primary_label', $content['primary_label'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="hero_primary_link">Primary button link</label>
        <input id="hero_primary_link" name="content[primary_link]" value="{{ old('content.primary_link', $content['primary_link'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="hero_secondary_label">Secondary button label</label>
        <input id="hero_secondary_label" name="content[secondary_label]" value="{{ old('content.secondary_label', $content['secondary_label'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="hero_secondary_link">Secondary button link</label>
        <input id="hero_secondary_link" name="content[secondary_link]" value="{{ old('content.secondary_link', $content['secondary_link'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
</div>
<div class="grid gap-6 sm:grid-cols-2 pt-2">
    @include('sdtg.content.partials._media_upload', [
        'field' => 'image',
        'label' => 'Poster / background image',
        'kind' => 'image',
        'mediaUrls' => $mediaUrls,
    ])
    @include('sdtg.content.partials._media_upload', [
        'field' => 'video_url',
        'label' => 'Background video',
        'kind' => 'video',
        'mediaUrls' => $mediaUrls,
    ])
</div>
