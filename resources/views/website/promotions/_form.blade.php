@php
    $toLocal = static function (mixed $value): string {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }
        try {
            return \Illuminate\Support\Carbon::parse($raw)->format('Y-m-d\TH:i');
        } catch (\Throwable) {
            return '';
        }
    };
@endphp
<div>
    <label class="block text-sm font-medium">Title</label>
    <input name="title" value="{{ old('title', $promotion['title'] ?? '') }}" maxlength="255" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" required>
    @error('title')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
</div>
<div>
    <label class="block text-sm font-medium">Eyebrow (optional)</label>
    <input name="eyebrow" value="{{ old('eyebrow', $promotion['eyebrow'] ?? '') }}" maxlength="120" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="This Sunday · Special Programme">
</div>
<div>
    <label class="block text-sm font-medium">Message</label>
    <textarea name="body" rows="4" maxlength="4000" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('body', $promotion['body'] ?? '') }}</textarea>
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium">Button label</label>
        <input name="cta_label" value="{{ old('cta_label', $promotion['cta_label'] ?? '') }}" maxlength="80" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="View event">
    </div>
    <div>
        <label class="block text-sm font-medium">Button link</label>
        <input name="cta_url" value="{{ old('cta_url', $promotion['cta_url'] ?? '') }}" maxlength="500" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="event or /donate">
        <p class="text-xs text-gray-500 mt-1">Use a site path like <code>event</code>, <code>donate</code>, or a full URL.</p>
    </div>
</div>
<div class="space-y-3">
    <label class="block text-sm font-medium" for="image">Poster image</label>
    @if (! empty($promotion['image_url']))
        <img src="{{ $promotion['image_url'] }}" alt="" class="h-32 w-auto rounded object-cover border">
        <label class="inline-flex items-center gap-2 text-sm text-red-700 dark:text-red-300">
            <input type="checkbox" name="remove_image" value="1" @checked(old('remove_image')) class="rounded border-gray-300">
            Remove current image
        </label>
    @endif
    <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 w-full text-sm">
    <p class="text-xs text-gray-500">JPG, PNG, or WebP up to 5 MB. Shown at the top of the load banner.</p>
    @error('image')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium">Programme starts</label>
        <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $toLocal($promotion['starts_at'] ?? null)) }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
        <p class="text-xs text-gray-500 mt-1">Shown on the banner. The banner itself appears as soon as it is Active.</p>
    </div>
    <div>
        <label class="block text-sm font-medium">Programme ends</label>
        <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $toLocal($promotion['ends_at'] ?? null)) }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
        <p class="text-xs text-gray-500 mt-1">After this time the banner stops. Leave blank to keep showing until you turn it off.</p>
        @error('ends_at')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium">Priority</label>
        <input type="number" name="sort_order" min="0" max="9999" value="{{ old('sort_order', $promotion['sort_order'] ?? 0) }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
        <p class="text-xs text-gray-500 mt-1">Higher number wins if more than one banner is active.</p>
    </div>
    <div class="space-y-3 pt-6">
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', ! empty($promotion['is_active']))) class="rounded border-gray-300">
            Active (show on the public site)
        </label>
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="show_every_visit" value="1" @checked(old('show_every_visit', ! empty($promotion['show_every_visit']))) class="rounded border-gray-300">
            Show on every visit
        </label>
        <p class="text-xs text-gray-500">If unchecked, visitors who close the banner will not see it again until you update it.</p>
    </div>
</div>
