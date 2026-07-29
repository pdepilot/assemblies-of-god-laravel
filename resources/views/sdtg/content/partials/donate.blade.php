@php $content = $content ?? []; @endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="donate_eyebrow">Eyebrow</label>
        <input id="donate_eyebrow" name="content[eyebrow]" value="{{ old('content.eyebrow', $content['eyebrow'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="donate_heading">Heading</label>
        <input id="donate_heading" name="content[heading]" value="{{ old('content.heading', $content['heading'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
</div>
<div>
    <label class="block text-sm font-medium" for="donate_description">Description</label>
    <textarea id="donate_description" name="content[description]" rows="2" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">{{ old('content.description', $content['description'] ?? '') }}</textarea>
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="donate_cta_label">CTA label</label>
        <input id="donate_cta_label" name="content[cta_label]" value="{{ old('content.cta_label', $content['cta_label'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="donate_cta_link">CTA link</label>
        <input id="donate_cta_link" name="content[cta_link]" value="{{ old('content.cta_link', $content['cta_link'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
</div>

@include('sdtg.content.partials._icon_cards', [
    'items' => $content['cards'] ?? [],
    'name' => 'cards',
    'label' => 'Giving Category Cards',
    'withProgress' => false,
    'spares' => 2,
])
