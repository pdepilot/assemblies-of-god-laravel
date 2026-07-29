@php
    // Expects: $items (array), $name (e.g. 'items', 'stats'), $label (heading), $withIcon (bool), $spares (int)
    $withIcon = $withIcon ?? false;
    $spares = $spares ?? 2;
    $rows = array_values($items ?? []);
    for ($i = 0; $i < $spares; $i++) {
        $rows[] = [];
    }
@endphp
<div class="space-y-3">
    <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-200">{{ $label ?? 'Stats' }}</h4>
    <div class="space-y-3">
        @foreach ($rows as $i => $row)
            <div class="grid grid-cols-12 gap-3 items-start border border-gray-200 dark:border-gray-700 rounded-md p-3">
                @if ($withIcon)
                    <div class="col-span-12 sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-500">Icon</label>
                        <input name="content[{{ $name }}][{{ $i }}][icon]" value="{{ old('content.'.$name.'.'.$i.'.icon', $row['icon'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm" placeholder="fa-star">
                    </div>
                @endif
                <div class="col-span-6 sm:col-span-3">
                    <label class="block text-xs font-medium text-gray-500">Target number</label>
                    <input type="number" name="content[{{ $name }}][{{ $i }}][target]" value="{{ old('content.'.$name.'.'.$i.'.target', $row['target'] ?? 0) }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                </div>
                <div class="col-span-6 sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-500">Suffix</label>
                    <input name="content[{{ $name }}][{{ $i }}][suffix]" value="{{ old('content.'.$name.'.'.$i.'.suffix', $row['suffix'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm" placeholder="+">
                </div>
                <div class="col-span-12 {{ $withIcon ? 'sm:col-span-5' : 'sm:col-span-7' }}">
                    <label class="block text-xs font-medium text-gray-500">Label</label>
                    <input name="content[{{ $name }}][{{ $i }}][label]" value="{{ old('content.'.$name.'.'.$i.'.label', $row['label'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                </div>
            </div>
        @endforeach
    </div>
    <p class="text-xs text-gray-400">Blank rows (no label) are discarded automatically when you save.</p>
</div>
