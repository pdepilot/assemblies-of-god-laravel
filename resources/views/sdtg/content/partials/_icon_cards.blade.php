@php
    // Expects: $items (array), $name (e.g. 'blocks', 'cards'), $label (heading), $withProgress (bool), $spares (int)
    $withProgress = $withProgress ?? false;
    $spares = $spares ?? 2;
    $rows = array_values($items ?? []);
    for ($i = 0; $i < $spares; $i++) {
        $rows[] = [];
    }
@endphp
<div class="space-y-3">
    <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-200">{{ $label ?? 'Cards' }}</h4>
    <div class="space-y-3">
        @foreach ($rows as $i => $row)
            <div class="grid grid-cols-12 gap-3 items-start border border-gray-200 dark:border-gray-700 rounded-md p-3">
                <div class="col-span-12 sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-500">Icon</label>
                    <input name="content[{{ $name }}][{{ $i }}][icon]" value="{{ old('content.'.$name.'.'.$i.'.icon', $row['icon'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm" placeholder="fa-star">
                </div>
                <div class="col-span-12 sm:col-span-3">
                    <label class="block text-xs font-medium text-gray-500">Title</label>
                    <input name="content[{{ $name }}][{{ $i }}][title]" value="{{ old('content.'.$name.'.'.$i.'.title', $row['title'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                </div>
                <div class="col-span-12 {{ $withProgress ? 'sm:col-span-5' : 'sm:col-span-7' }}">
                    <label class="block text-xs font-medium text-gray-500">Text</label>
                    <textarea name="content[{{ $name }}][{{ $i }}][text]" rows="2" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">{{ old('content.'.$name.'.'.$i.'.text', $row['text'] ?? '') }}</textarea>
                </div>
                @if ($withProgress)
                    <div class="col-span-12 sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-500">Progress %</label>
                        <input type="number" min="0" max="100" name="content[{{ $name }}][{{ $i }}][progress]" value="{{ old('content.'.$name.'.'.$i.'.progress', $row['progress'] ?? 0) }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                    </div>
                @endif
            </div>
        @endforeach
    </div>
    <p class="text-xs text-gray-400">Blank rows (no title/text) are discarded automatically when you save. Spare rows above let you add new cards.</p>
</div>
