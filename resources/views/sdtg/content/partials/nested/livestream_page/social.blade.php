@php
    $rows = array_values($content ?? []);
    for ($i = 0; $i < 1; $i++) {
        $rows[] = [];
    }
@endphp

<div class="space-y-3">
    <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-200">Social Links</h4>
    @foreach ($rows as $i => $row)
        <div class="grid grid-cols-12 gap-2 items-start border border-gray-200 dark:border-gray-700 rounded-md p-3">
            <div class="col-span-6 sm:col-span-2">
                <label class="block text-xs font-medium text-gray-500">Platform</label>
                <input name="content[{{ $i }}][platform]" value="{{ old("content.$i.platform", $row['platform'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm" placeholder="facebook">
            </div>
            <div class="col-span-6 sm:col-span-2">
                <label class="block text-xs font-medium text-gray-500">Icon class</label>
                <input name="content[{{ $i }}][icon]" value="{{ old("content.$i.icon", $row['icon'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm" placeholder="fab fa-facebook-f">
            </div>
            <div class="col-span-6 sm:col-span-2">
                <label class="block text-xs font-medium text-gray-500">Label</label>
                <input name="content[{{ $i }}][label]" value="{{ old("content.$i.label", $row['label'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
            </div>
            <div class="col-span-6 sm:col-span-2">
                <label class="block text-xs font-medium text-gray-500">Subtitle</label>
                <input name="content[{{ $i }}][subtitle]" value="{{ old("content.$i.subtitle", $row['subtitle'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
            </div>
            <div class="col-span-12 sm:col-span-4">
                <label class="block text-xs font-medium text-gray-500">URL</label>
                <input name="content[{{ $i }}][url]" value="{{ old("content.$i.url", $row['url'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
            </div>
        </div>
    @endforeach
    <p class="text-xs text-gray-400">Rows without a label are discarded automatically when you save.</p>
</div>
