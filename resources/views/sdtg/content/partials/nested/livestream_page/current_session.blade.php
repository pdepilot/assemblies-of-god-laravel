@php $content = $content ?? []; @endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="cs_title">Session title</label>
        <input id="cs_title" name="content[title]" value="{{ old('content.title', $content['title'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="cs_topic">Topic</label>
        <input id="cs_topic" name="content[topic]" value="{{ old('content.topic', $content['topic'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
</div>
<div class="grid gap-4 sm:grid-cols-3">
    <div>
        <label class="block text-sm font-medium" for="cs_minister">Minister</label>
        <input id="cs_minister" name="content[minister]" value="{{ old('content.minister', $content['minister'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="cs_ministry">Ministry</label>
        <input id="cs_ministry" name="content[ministry]" value="{{ old('content.ministry', $content['ministry'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="cs_country">Country</label>
        <input id="cs_country" name="content[country]" value="{{ old('content.country', $content['country'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
</div>
<div>
    <label class="block text-sm font-medium" for="cs_worship_team">Worship team</label>
    <input id="cs_worship_team" name="content[worship_team]" value="{{ old('content.worship_team', $content['worship_team'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
</div>
@include('sdtg.content.partials._media_upload', [
    'field' => 'photo',
    'label' => 'Session photo',
    'kind' => 'image',
    'mediaUrls' => $mediaUrls ?? [],
])
