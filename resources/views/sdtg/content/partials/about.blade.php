@php $content = $content ?? []; @endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="about_eyebrow">Eyebrow</label>
        <input id="about_eyebrow" name="content[eyebrow]" value="{{ old('content.eyebrow', $content['eyebrow'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="about_heading">Heading</label>
        <input id="about_heading" name="content[heading]" value="{{ old('content.heading', $content['heading'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
</div>
<div>
    <label class="block text-sm font-medium" for="about_lead">Lead paragraph</label>
    <textarea id="about_lead" name="content[lead]" rows="3" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">{{ old('content.lead', $content['lead'] ?? '') }}</textarea>
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="about_badge_number">Badge number</label>
        <input id="about_badge_number" name="content[badge_number]" value="{{ old('content.badge_number', $content['badge_number'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="about_badge_text">Badge text</label>
        <input id="about_badge_text" name="content[badge_text]" value="{{ old('content.badge_text', $content['badge_text'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
</div>
@include('sdtg.content.partials._media_upload', [
    'field' => 'image',
    'label' => 'Section image',
    'kind' => 'image',
    'mediaUrls' => $mediaUrls ?? [],
])

@include('sdtg.content.partials._icon_cards', [
    'items' => $content['blocks'] ?? [],
    'name' => 'blocks',
    'label' => 'History / Mission / Vision / Impact Blocks',
    'withProgress' => false,
    'spares' => 2,
])
