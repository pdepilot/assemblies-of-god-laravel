@php
    $content = $content ?? [];
    $donors = array_values($content['donors'] ?? []);
    for ($i = 0; $i < 2; $i++) {
        $donors[] = [];
    }
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="dp_donorwall_eyebrow">Eyebrow</label>
        <input id="dp_donorwall_eyebrow" name="content[eyebrow]" value="{{ old('content.eyebrow', $content['eyebrow'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium" for="dp_donorwall_heading">Heading</label>
        <input id="dp_donorwall_heading" name="content[heading]" value="{{ old('content.heading', $content['heading'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
    </div>
</div>
<div>
    <label class="block text-sm font-medium" for="dp_donorwall_description">Description</label>
    <textarea id="dp_donorwall_description" name="content[description]" rows="2" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">{{ old('content.description', $content['description'] ?? '') }}</textarea>
</div>

<div class="space-y-3">
    <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-200">Recent Donors</h4>
    @foreach ($donors as $i => $donor)
        @php $prefix = "content[donors][$i]"; @endphp
        <div class="grid grid-cols-12 gap-2 items-start border border-gray-200 dark:border-gray-700 rounded-md p-3">
            <div class="col-span-12 sm:col-span-3">
                <label class="block text-xs font-medium text-gray-500">Name</label>
                <input name="{{ $prefix }}[name]" value="{{ old("content.donors.$i.name", $donor['name'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
            </div>
            <div class="col-span-12 sm:col-span-3">
                <label class="block text-xs font-medium text-gray-500">Meta (location · date)</label>
                <input name="{{ $prefix }}[meta]" value="{{ old("content.donors.$i.meta", $donor['meta'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
            </div>
            <div class="col-span-6 sm:col-span-2">
                <label class="block text-xs font-medium text-gray-500">Amount</label>
                <input name="{{ $prefix }}[amount]" value="{{ old("content.donors.$i.amount", $donor['amount'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
            </div>
            <div class="col-span-6 sm:col-span-2">
                <label class="block text-xs font-medium text-gray-500">Purpose</label>
                <input name="{{ $prefix }}[purpose]" value="{{ old("content.donors.$i.purpose", $donor['purpose'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
            </div>
            <div class="col-span-12 sm:col-span-2">
                <label class="block text-xs font-medium text-gray-500">Phone (masked)</label>
                <input name="{{ $prefix }}[phone]" value="{{ old("content.donors.$i.phone", $donor['phone'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
            </div>
        </div>
    @endforeach
    <p class="text-xs text-gray-400">Rows without a name are discarded automatically when you save.</p>
</div>
