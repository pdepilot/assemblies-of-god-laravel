@php
    $content = $content ?? [];
    $items = array_values($content['items'] ?? []);
    for ($i = 0; $i < 2; $i++) {
        $items[] = [];
    }
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="dp_testimonials_eyebrow">Eyebrow</label>
        <input id="dp_testimonials_eyebrow" name="content[eyebrow]" value="{{ old('content.eyebrow', $content['eyebrow'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="dp_testimonials_heading">Heading</label>
        <input id="dp_testimonials_heading" name="content[heading]" value="{{ old('content.heading', $content['heading'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
</div>

<div class="space-y-3">
    <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-200">Testimonies</h4>
    @foreach ($items as $i => $item)
        @php $prefix = "content[items][$i]"; @endphp
        <div class="grid grid-cols-12 gap-2 items-start border border-gray-200 dark:border-gray-700 rounded-md p-3">
            <div class="col-span-12 sm:col-span-3">
                <label class="block text-xs font-medium text-gray-500">Image path/URL</label>
                <input name="{{ $prefix }}[image]" value="{{ old("content.items.$i.image", $item['image'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
            </div>
            <div class="col-span-12 sm:col-span-6">
                <label class="block text-xs font-medium text-gray-500">Quote</label>
                <textarea name="{{ $prefix }}[quote]" rows="2" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">{{ old("content.items.$i.quote", $item['quote'] ?? '') }}</textarea>
            </div>
            <div class="col-span-6 sm:col-span-2">
                <label class="block text-xs font-medium text-gray-500">Author</label>
                <input name="{{ $prefix }}[author]" value="{{ old("content.items.$i.author", $item['author'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
            </div>
            <div class="col-span-6 sm:col-span-1">
                <label class="block text-xs font-medium text-gray-500">Location</label>
                <input name="{{ $prefix }}[location]" value="{{ old("content.items.$i.location", $item['location'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
            </div>
        </div>
    @endforeach
    <p class="text-xs text-gray-400">Rows without a quote are discarded automatically when you save.</p>
</div>
