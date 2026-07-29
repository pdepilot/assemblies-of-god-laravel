@php
    $content = $content ?? [];
    $youtube = $content['youtube'] ?? [];
    $facebook = $content['facebook'] ?? [];
    $vimeo = $content['vimeo'] ?? [];
    $custom = $content['custom'] ?? [];
    $defaultPlatform = old('content.default_platform', $content['default_platform'] ?? 'youtube');
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div class="border border-gray-200 dark:border-gray-700 rounded-md p-3 space-y-2">
        <p class="text-sm font-semibold">YouTube</p>
        <label class="block text-xs font-medium text-gray-500">Video / Channel ID</label>
        <input name="content[youtube][id]" value="{{ old('content.youtube.id', $youtube['id'] ?? '') }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
        <label class="block text-xs font-medium text-gray-500">Label</label>
        <input name="content[youtube][label]" value="{{ old('content.youtube.label', $youtube['label'] ?? '') }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
    </div>
    <div class="border border-gray-200 dark:border-gray-700 rounded-md p-3 space-y-2">
        <p class="text-sm font-semibold">Facebook</p>
        <label class="block text-xs font-medium text-gray-500">Video ID</label>
        <input name="content[facebook][id]" value="{{ old('content.facebook.id', $facebook['id'] ?? '') }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
        <label class="block text-xs font-medium text-gray-500">Label</label>
        <input name="content[facebook][label]" value="{{ old('content.facebook.label', $facebook['label'] ?? '') }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
        <label class="block text-xs font-medium text-gray-500">Embed URL</label>
        <input name="content[facebook][embed]" value="{{ old('content.facebook.embed', $facebook['embed'] ?? '') }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
    </div>
    <div class="border border-gray-200 dark:border-gray-700 rounded-md p-3 space-y-2">
        <p class="text-sm font-semibold">Vimeo</p>
        <label class="block text-xs font-medium text-gray-500">Video ID</label>
        <input name="content[vimeo][id]" value="{{ old('content.vimeo.id', $vimeo['id'] ?? '') }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
        <label class="block text-xs font-medium text-gray-500">Label</label>
        <input name="content[vimeo][label]" value="{{ old('content.vimeo.label', $vimeo['label'] ?? '') }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
    </div>
    <div class="border border-gray-200 dark:border-gray-700 rounded-md p-3 space-y-2">
        <p class="text-sm font-semibold">Custom / Direct (SDTG)</p>
        <label class="block text-xs font-medium text-gray-500">Video URL / path</label>
        <input name="content[custom][url]" value="{{ old('content.custom.url', $custom['url'] ?? '') }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm" placeholder="videos/worship-crowd.mp4">
        <label class="block text-xs font-medium text-gray-500">Label</label>
        <input name="content[custom][label]" value="{{ old('content.custom.label', $custom['label'] ?? '') }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
    </div>
</div>

<div>
    <label class="block text-sm font-medium" for="lp_default_platform">Default platform</label>
    <select id="lp_default_platform" name="content[default_platform]" class="mt-1 w-full sm:w-64 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
        @foreach (['youtube', 'facebook', 'vimeo', 'custom'] as $platform)
            <option value="{{ $platform }}" @selected($defaultPlatform === $platform)>{{ ucfirst($platform) }}</option>
        @endforeach
    </select>
</div>
