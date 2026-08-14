<div>
    <label class="block text-sm font-medium">Hero title</label>
    <input name="heading" value="{{ old('heading', $page['heading'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" required>
    <p class="text-xs text-gray-500 mt-1">Large title on the public page banner.</p>
</div>
<div>
    <label class="block text-sm font-medium">Eyebrow</label>
    <input name="eyebrow" value="{{ old('eyebrow', $page['eyebrow'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
</div>
<div>
    <label class="block text-sm font-medium">Intro</label>
    <textarea name="intro" rows="3" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('intro', $page['intro'] ?? '') }}</textarea>
</div>

@if (($catalog['type'] ?? '') === 'content')
    <div>
        <label class="block text-sm font-medium">Page body (HTML allowed)</label>
        <textarea name="body_html" rows="10" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('body_html', $page['body_html'] ?? '') }}</textarea>
    </div>
@endif

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium">CTA label</label>
        <input name="cta_label" value="{{ old('cta_label', $page['cta_label'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium">CTA URL</label>
        <input name="cta_url" value="{{ old('cta_url', $page['cta_url'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="/contact">
    </div>
</div>

<div class="space-y-3">
    <label class="block text-sm font-medium" for="hero_image">Header image</label>
    @if (! empty($heroImageUrl))
        <div class="rounded-md overflow-hidden border border-gray-200 dark:border-gray-700 max-w-md">
            <img src="{{ $heroImageUrl }}" alt="Current header image" class="w-full max-h-48 object-cover">
        </div>
        <label class="inline-flex items-center gap-2 text-sm text-red-700 dark:text-red-300">
            <input type="checkbox" name="remove_hero_image" value="1" @checked(old('remove_hero_image')) class="rounded border-gray-300">
            Remove current image
        </label>
    @endif
    <input id="hero_image" name="hero_image" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm">
    <p class="text-xs text-gray-500">JPG, PNG, or WebP up to 5 MB. Upload only — no URL or path.</p>
</div>
