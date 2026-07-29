@php
    $rows = array_values($content ?? []);
    for ($i = 0; $i < 1; $i++) {
        $rows[] = [];
    }
@endphp

<div class="space-y-3">
    <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-200">Past Broadcasts</h4>
    @foreach ($rows as $i => $row)
        <div class="grid grid-cols-12 gap-2 items-start border border-gray-200 dark:border-gray-700 rounded-md p-3">
            <div class="col-span-12 sm:col-span-4">
                <label class="block text-xs font-medium text-gray-500">Title</label>
                <input name="content[{{ $i }}][title]" value="{{ old("content.$i.title", $row['title'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
            </div>
            <div class="col-span-4 sm:col-span-1">
                <label class="block text-xs font-medium text-gray-500">Year</label>
                <input name="content[{{ $i }}][year]" value="{{ old("content.$i.year", $row['year'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
            </div>
            <div class="col-span-4 sm:col-span-2">
                <label class="block text-xs font-medium text-gray-500">Duration</label>
                <input name="content[{{ $i }}][duration]" value="{{ old("content.$i.duration", $row['duration'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm" placeholder="3h 42m">
            </div>
            <div class="col-span-4 sm:col-span-2">
                <label class="block text-xs font-medium text-gray-500">Type</label>
                <select name="content[{{ $i }}][type]" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                    @foreach (['local', 'youtube', 'vimeo'] as $type)
                        <option value="{{ $type }}" @selected(old("content.$i.type", $row['type'] ?? 'youtube') === $type)>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-12 sm:col-span-3">
                <label class="block text-xs font-medium text-gray-500">Thumb path/URL</label>
                <input name="content[{{ $i }}][thumb]" value="{{ old("content.$i.thumb", $row['thumb'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
            </div>
            <div class="col-span-12">
                <label class="block text-xs font-medium text-gray-500">Video (path, YouTube ID, or URL)</label>
                <input name="content[{{ $i }}][video]" value="{{ old("content.$i.video", $row['video'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
            </div>
        </div>
    @endforeach
    <p class="text-xs text-gray-400">Rows without a title are discarded automatically when you save.</p>
</div>
