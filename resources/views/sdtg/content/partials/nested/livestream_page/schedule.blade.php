@php
    $rows = array_values($content ?? []);
    for ($i = 0; $i < 2; $i++) {
        $rows[] = [];
    }
@endphp

<div class="space-y-3">
    <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-200">Programme Schedule</h4>
    @foreach ($rows as $i => $row)
        <div class="grid grid-cols-12 gap-2 items-start border border-gray-200 dark:border-gray-700 rounded-md p-3">
            <div class="col-span-6 sm:col-span-2">
                <label class="block text-xs font-medium text-gray-500">Time</label>
                <input name="content[{{ $i }}][time]" value="{{ old("content.$i.time", $row['time'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm" placeholder="7:00 PM">
            </div>
            <div class="col-span-6 sm:col-span-3">
                <label class="block text-xs font-medium text-gray-500">Session</label>
                <input name="content[{{ $i }}][session]" value="{{ old("content.$i.session", $row['session'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
            </div>
            <div class="col-span-6 sm:col-span-3">
                <label class="block text-xs font-medium text-gray-500">Speaker</label>
                <input name="content[{{ $i }}][speaker]" value="{{ old("content.$i.speaker", $row['speaker'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
            </div>
            <div class="col-span-6 sm:col-span-4">
                <label class="block text-xs font-medium text-gray-500">Worship</label>
                <input name="content[{{ $i }}][worship]" value="{{ old("content.$i.worship", $row['worship'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
            </div>
        </div>
    @endforeach
    <p class="text-xs text-gray-400">Rows without a session name are discarded automatically when you save.</p>
</div>
