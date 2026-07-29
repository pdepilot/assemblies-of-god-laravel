@php $content = $content ?? []; $mediaUrls = $mediaUrls ?? []; @endphp

<div>
    <label class="block text-sm font-medium" for="lp_hero_badge">Badge</label>
    <input id="lp_hero_badge" name="content[badge]" value="{{ old('content.badge', $content['badge'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="lp_hero_title_before">Title (before highlight)</label>
        <input id="lp_hero_title_before" name="content[title_before]" value="{{ old('content.title_before', $content['title_before'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="lp_hero_title_highlight">Title highlight</label>
        <input id="lp_hero_title_highlight" name="content[title_highlight]" value="{{ old('content.title_highlight', $content['title_highlight'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
</div>
<div>
    <label class="block text-sm font-medium" for="lp_hero_subtitle">Subtitle</label>
    <textarea id="lp_hero_subtitle" name="content[subtitle]" rows="3" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">{{ old('content.subtitle', $content['subtitle'] ?? '') }}</textarea>
</div>
<div class="grid gap-6 sm:grid-cols-2">
    @include('sdtg.content.partials._media_upload', [
        'field' => 'poster',
        'label' => 'Poster image',
        'kind' => 'image',
        'mediaUrls' => $mediaUrls,
    ])
    @include('sdtg.content.partials._media_upload', [
        'field' => 'video',
        'label' => 'Background video',
        'kind' => 'video',
        'mediaUrls' => $mediaUrls,
    ])
</div>
<div class="grid gap-4 sm:grid-cols-3">
    <div>
        <label class="block text-sm font-medium" for="lp_hero_primary_label">Primary button label</label>
        <input id="lp_hero_primary_label" name="content[primary_label]" value="{{ old('content.primary_label', $content['primary_label'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="lp_hero_primary_link">Primary button link</label>
        <input id="lp_hero_primary_link" name="content[primary_link]" value="{{ old('content.primary_link', $content['primary_link'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div></div>
</div>
<div class="grid gap-4 sm:grid-cols-3">
    <div>
        <label class="block text-sm font-medium" for="lp_hero_secondary_label">Secondary button label</label>
        <input id="lp_hero_secondary_label" name="content[secondary_label]" value="{{ old('content.secondary_label', $content['secondary_label'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="lp_hero_secondary_link">Secondary button link</label>
        <input id="lp_hero_secondary_link" name="content[secondary_link]" value="{{ old('content.secondary_link', $content['secondary_link'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
</div>
<div class="grid gap-4 sm:grid-cols-3">
    <div>
        <label class="block text-sm font-medium" for="lp_hero_tertiary_label">Tertiary button label</label>
        <input id="lp_hero_tertiary_label" name="content[tertiary_label]" value="{{ old('content.tertiary_label', $content['tertiary_label'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="lp_hero_tertiary_link">Tertiary button link</label>
        <input id="lp_hero_tertiary_link" name="content[tertiary_link]" value="{{ old('content.tertiary_link', $content['tertiary_link'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
</div>
