@php
    $field = $field ?? 'image';
    $label = $label ?? 'Image';
    $kind = $kind ?? 'image';
    $currentUrl = $mediaUrls[$field] ?? null;
    $inputId = 'media_'.$field;
    $accept = $kind === 'video'
        ? 'video/mp4,video/webm,video/quicktime'
        : 'image/jpeg,image/png,image/webp';
    $hint = $kind === 'video'
        ? 'MP4, WebM, or MOV up to 50 MB. Upload only — no URL.'
        : 'JPG, PNG, or WebP up to 5 MB. Upload only — no URL.';
@endphp
<div class="space-y-3">
    <label class="block text-sm font-medium" for="{{ $inputId }}">{{ $label }}</label>
    @if (! empty($currentUrl))
        @if ($kind === 'video')
            <video src="{{ $currentUrl }}" controls class="w-full max-w-md max-h-48 rounded border border-gray-200 dark:border-gray-700 bg-black"></video>
        @else
            <div class="rounded-md overflow-hidden border border-gray-200 dark:border-gray-700 max-w-md">
                <img src="{{ $currentUrl }}" alt="Current {{ strtolower($label) }}" class="w-full max-h-48 object-cover">
            </div>
        @endif
        <label class="inline-flex items-center gap-2 text-sm text-red-700 dark:text-red-300">
            <input type="checkbox" name="remove_media[{{ $field }}]" value="1" @checked(old('remove_media.'.$field)) class="rounded border-gray-300">
            Remove current {{ strtolower($kind) }}
        </label>
    @endif
    <input id="{{ $inputId }}" name="media[{{ $field }}]" type="file" accept="{{ $accept }}" class="block w-full text-sm">
    <p class="text-xs text-gray-500">{{ $hint }}</p>
</div>
