@php
    /** @var array<string, mixed> $data */
    /** @var string $prefix */
    $gallery = is_array($data['gallery'] ?? null) ? $data['gallery'] : [];
    $highlight = is_array($data['highlight'] ?? null) ? $data['highlight'] : [];
    $features = is_array($data['features'] ?? null) ? $data['features'] : [];
    $asset = app(\App\Services\PublicSite\PublicAssetResolver::class);
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium">Eyebrow</label>
        <input type="text" name="{{ $prefix }}[eyebrow]" value="{{ old($prefix.'.eyebrow', $data['eyebrow'] ?? '') }}" maxlength="120" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium">Title</label>
        <input type="text" name="{{ $prefix }}[title]" value="{{ old($prefix.'.title', $data['title'] ?? '') }}" maxlength="255" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
    </div>
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium">Introduction</label>
        <textarea name="{{ $prefix }}[intro]" rows="3" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old($prefix.'.intro', $data['intro'] ?? '') }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium">Vision title</label>
        <input type="text" name="{{ $prefix }}[vision_title]" value="{{ old($prefix.'.vision_title', $data['vision_title'] ?? '') }}" maxlength="80" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
    </div>
    <div>
        <label class="block text-sm font-medium">Mission title</label>
        <input type="text" name="{{ $prefix }}[mission_title]" value="{{ old($prefix.'.mission_title', $data['mission_title'] ?? '') }}" maxlength="80" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
    </div>
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium">Vision text</label>
        <textarea name="{{ $prefix }}[vision_text]" rows="2" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old($prefix.'.vision_text', $data['vision_text'] ?? '') }}</textarea>
    </div>
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium">Mission text</label>
        <textarea name="{{ $prefix }}[mission_text]" rows="2" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old($prefix.'.mission_text', $data['mission_text'] ?? '') }}</textarea>
    </div>
</div>

<div class="mt-6 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-4">
    <h4 class="font-semibold">Gallery Images</h4>
    @for ($i = 0; $i < 3; $i++)
        @php
            $item = $gallery[$i] ?? ['image' => '', 'alt' => ''];
            $imagePath = old($prefix.'.gallery.'.$i.'.image', $item['image'] ?? '');
            $imageUrl = $imagePath !== '' ? $asset->url((string) $imagePath) : null;
        @endphp
        <div class="grid gap-4 sm:grid-cols-2 border-t border-gray-100 dark:border-gray-800 pt-4 first:border-0 first:pt-0">
            <div>
                <label class="block text-sm font-medium">Image {{ $i + 1 }}</label>
                @if ($imageUrl)
                    <img src="{{ $imageUrl }}" alt="" class="mt-2 h-24 w-36 rounded object-cover border">
                @endif
                <input type="file" name="{{ $prefix }}_gallery_{{ $i }}" accept="image/jpeg,image/png,image/webp,image/gif" class="mt-2 block w-full text-sm">
                <p class="text-xs text-gray-500 mt-1">JPG, PNG, WebP, or GIF up to 5 MB. Upload only — no URL or path.</p>
            </div>
            <div>
                <label class="block text-sm font-medium">Alt text</label>
                <input type="text" name="{{ $prefix }}[gallery][{{ $i }}][alt]" value="{{ old($prefix.'.gallery.'.$i.'.alt', $item['alt'] ?? '') }}" maxlength="255" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            </div>
        </div>
    @endfor
</div>

<div class="mt-6 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-4">
    <h4 class="font-semibold">Highlight Box</h4>
    @php
        $highlightPath = old($prefix.'.highlight.image', $highlight['image'] ?? '');
        $highlightUrl = $highlightPath !== '' ? $asset->url((string) $highlightPath) : null;
    @endphp
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium">Image</label>
            @if ($highlightUrl)
                <img src="{{ $highlightUrl }}" alt="" class="mt-2 h-24 w-36 rounded object-cover border">
            @endif
            <input type="file" name="{{ $prefix }}_highlight" accept="image/jpeg,image/png,image/webp,image/gif" class="mt-2 block w-full text-sm">
            <p class="text-xs text-gray-500 mt-1">JPG, PNG, WebP, or GIF up to 5 MB. Upload only — no URL or path.</p>
        </div>
        <div>
            <label class="block text-sm font-medium">Image alt</label>
            <input type="text" name="{{ $prefix }}[highlight][image_alt]" value="{{ old($prefix.'.highlight.image_alt', $highlight['image_alt'] ?? '') }}" maxlength="255" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium">Quote</label>
            <textarea name="{{ $prefix }}[highlight][quote]" rows="2" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old($prefix.'.highlight.quote', $highlight['quote'] ?? '') }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium">Stat value</label>
            <input type="text" name="{{ $prefix }}[highlight][stat_value]" value="{{ old($prefix.'.highlight.stat_value', $highlight['stat_value'] ?? '') }}" maxlength="40" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
        </div>
        <div>
            <label class="block text-sm font-medium">Stat label</label>
            <input type="text" name="{{ $prefix }}[highlight][stat_label]" value="{{ old($prefix.'.highlight.stat_label', $highlight['stat_label'] ?? '') }}" maxlength="80" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
        </div>
    </div>
</div>

<div class="mt-6">
    <label class="block text-sm font-medium">Features (one per line)</label>
    <textarea name="{{ $prefix }}[features_text]" rows="4" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="Worship &amp; Prayer">{{ old($prefix.'.features_text', implode("\n", $features)) }}</textarea>
</div>
