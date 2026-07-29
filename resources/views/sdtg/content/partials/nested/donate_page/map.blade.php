@php $content = $content ?? []; @endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="dp_map_eyebrow">Eyebrow</label>
        <input id="dp_map_eyebrow" name="content[eyebrow]" value="{{ old('content.eyebrow', $content['eyebrow'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="dp_map_heading">Heading</label>
        <input id="dp_map_heading" name="content[heading]" value="{{ old('content.heading', $content['heading'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
</div>

@include('sdtg.content.partials._stat_items', [
    'items' => $content['stats'] ?? [],
    'name' => 'stats',
    'label' => 'Global Family Stats',
    'withIcon' => false,
    'spares' => 1,
])
