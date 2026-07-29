@php $content = $content ?? []; @endphp

<div class="grid gap-4 sm:grid-cols-3">
    <div>
        <label class="block text-sm font-medium" for="us_title">Session title</label>
        <input id="us_title" name="content[title]" value="{{ old('content.title', $content['title'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="us_minister">Minister</label>
        <input id="us_minister" name="content[minister]" value="{{ old('content.minister', $content['minister'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="us_time">Time</label>
        <input id="us_time" name="content[time]" value="{{ old('content.time', $content['time'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900" placeholder="TBA">
    </div>
</div>
