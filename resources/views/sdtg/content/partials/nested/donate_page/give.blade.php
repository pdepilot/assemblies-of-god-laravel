@php
    $content = $content ?? [];
    $categories = array_values($content['categories'] ?? []);
    for ($i = 0; $i < 1; $i++) {
        $categories[] = [];
    }
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="dp_give_eyebrow">Eyebrow</label>
        <input id="dp_give_eyebrow" name="content[eyebrow]" value="{{ old('content.eyebrow', $content['eyebrow'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="dp_give_heading">Heading</label>
        <input id="dp_give_heading" name="content[heading]" value="{{ old('content.heading', $content['heading'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
</div>
<div>
    <label class="block text-sm font-medium" for="dp_give_description">Description</label>
    <textarea id="dp_give_description" name="content[description]" rows="2" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">{{ old('content.description', $content['description'] ?? '') }}</textarea>
</div>

<div class="space-y-4">
    <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-200">Giving Categories &amp; Bank Accounts</h4>
    @foreach ($categories as $i => $cat)
        @php $prefix = "content[categories][$i]"; @endphp
        <div class="border border-gray-200 dark:border-gray-700 rounded-md p-4 space-y-3">
            <div class="grid gap-3 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500">Key (slug)</label>
                    <input name="{{ $prefix }}[key]" value="{{ old("content.categories.$i.key", $cat['key'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm" placeholder="tithes">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500">Icon</label>
                    <input name="{{ $prefix }}[icon]" value="{{ old("content.categories.$i.icon", $cat['icon'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm" placeholder="fa-church">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500">Title</label>
                    <input name="{{ $prefix }}[title]" value="{{ old("content.categories.$i.title", $cat['title'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="border border-gray-100 dark:border-gray-800 rounded p-3 space-y-2">
                    <p class="text-xs font-semibold uppercase text-gray-400">Nigeria account</p>
                    <input name="{{ $prefix }}[nigeria][bank]" value="{{ old("content.categories.$i.nigeria.bank", $cat['nigeria']['bank'] ?? '') }}" placeholder="Bank" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                    <input name="{{ $prefix }}[nigeria][account_name]" value="{{ old("content.categories.$i.nigeria.account_name", $cat['nigeria']['account_name'] ?? '') }}" placeholder="Account name" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                    <input name="{{ $prefix }}[nigeria][account_number]" value="{{ old("content.categories.$i.nigeria.account_number", $cat['nigeria']['account_number'] ?? '') }}" placeholder="Account number" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                </div>
                <div class="border border-gray-100 dark:border-gray-800 rounded p-3 space-y-2">
                    <p class="text-xs font-semibold uppercase text-gray-400">International account</p>
                    <input name="{{ $prefix }}[international][bank]" value="{{ old("content.categories.$i.international.bank", $cat['international']['bank'] ?? '') }}" placeholder="Bank" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                    <input name="{{ $prefix }}[international][account_name]" value="{{ old("content.categories.$i.international.account_name", $cat['international']['account_name'] ?? '') }}" placeholder="Account name" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                    <input name="{{ $prefix }}[international][account_number]" value="{{ old("content.categories.$i.international.account_number", $cat['international']['account_number'] ?? '') }}" placeholder="Account number" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                    <div class="grid grid-cols-2 gap-2">
                        <input name="{{ $prefix }}[international][swift]" value="{{ old("content.categories.$i.international.swift", $cat['international']['swift'] ?? '') }}" placeholder="SWIFT" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                        <input name="{{ $prefix }}[international][iban]" value="{{ old("content.categories.$i.international.iban", $cat['international']['iban'] ?? '') }}" placeholder="IBAN" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    <p class="text-xs text-gray-400">Rows without a key and title are discarded automatically when you save.</p>
</div>
