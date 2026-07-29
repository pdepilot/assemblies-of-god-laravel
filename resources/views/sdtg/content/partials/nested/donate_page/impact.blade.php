@php $content = $content ?? []; @endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="dp_impact_eyebrow">Eyebrow</label>
        <input id="dp_impact_eyebrow" name="content[eyebrow]" value="{{ old('content.eyebrow', $content['eyebrow'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="dp_impact_heading">Heading</label>
        <input id="dp_impact_heading" name="content[heading]" value="{{ old('content.heading', $content['heading'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
</div>

@include('sdtg.content.partials._icon_cards', [
    'items' => $content['cards'] ?? [],
    'name' => 'cards',
    'label' => 'Impact Cards (with progress bar)',
    'withProgress' => true,
    'spares' => 2,
])
