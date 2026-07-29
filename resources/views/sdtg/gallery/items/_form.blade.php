@php
    $item = $item ?? [];
    $mediaType = old('media_type', $item['media_type'] ?? 'photo');
    $isVideo = $mediaType === 'video';
@endphp

<div class="rounded-md bg-slate-50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-700 p-3 text-sm text-slate-700 dark:text-slate-300">
    @if ($isVideo)
        This upload feeds the public <strong>Video Archive — Watch &amp; Relive</strong> section.
    @else
        This upload feeds the public <strong>Photo Archive — Captured In His Presence</strong> section.
        @if (! empty(old('is_featured', $item['is_featured'] ?? false)))
            Marked Featured items also appear under <strong>Moments That Changed Everything</strong>.
        @endif
    @endif
</div>

<div>
    <label class="block text-sm font-medium" for="title">Title</label>
    <input id="title" name="title" value="{{ old('title', $item['title'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900" required>
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="crusade_year">Crusade year</label>
        <input id="crusade_year" type="number" name="crusade_year" value="{{ old('crusade_year', $item['crusade_year'] ?? date('Y')) }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900" required>
    </div>
    <div>
        <label class="block text-sm font-medium" for="album_id">Album / collection</label>
        <select id="album_id" name="album_id" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
            <option value="">None</option>
            @foreach ($albums as $album)
                <option value="{{ $album['id'] }}" @selected((int) old('album_id', $item['album_id'] ?? 0) === (int) $album['id'])>{{ $album['title'] }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="grid gap-4 sm:grid-cols-3">
    <div>
        <label class="block text-sm font-medium" for="media_type">Media type</label>
        <select id="media_type" name="media_type" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
            @foreach (['photo', 'video'] as $type)
                <option value="{{ $type }}" @selected($mediaType === $type)>{{ $type }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium" for="category">Category filter</label>
        <select id="category" name="category" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
            @foreach ($categories as $category)
                <option value="{{ $category }}" @selected(old('category', $item['category'] ?? ($isVideo ? 'videos' : 'highlights')) === $category)>{{ $category }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium" for="layout_size">Layout size</label>
        <select id="layout_size" name="layout_size" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
            @foreach ($layouts as $layout)
                <option value="{{ $layout }}" @selected(old('layout_size', $item['layout_size'] ?? 'md') === $layout)>{{ $layout }}</option>
            @endforeach
        </select>
    </div>
</div>
<div>
    <label class="block text-sm font-medium" for="caption">Caption</label>
    <textarea id="caption" name="caption" rows="3" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">{{ old('caption', $item['caption'] ?? '') }}</textarea>
</div>
<div>
    <label class="block text-sm font-medium" for="tags">Tags</label>
    <input id="tags" name="tags" value="{{ old('tags', $item['tags'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
</div>

@if (! $isVideo)
    <div>
        <label class="block text-sm font-medium" for="media">Photo upload</label>
        <input id="media" type="file" name="media" accept=".jpg,.jpeg,.png,.webp,image/*" class="mt-1 w-full text-sm">
        <p class="text-xs text-gray-500 mt-1">JPG, PNG, or WebP. Upload only — no URL.</p>
        @if (! empty($item['image_url']))
            <div class="mt-3 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 max-w-md">
                <img src="{{ $item['image_url'] }}" alt="{{ $item['title'] ?? 'Gallery photo' }}" class="w-full max-h-64 object-cover">
            </div>
        @endif
    </div>
@else
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium" for="video_type">Video type</label>
            <select id="video_type" name="video_type" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
                @foreach (['youtube', 'local'] as $type)
                    <option value="{{ $type }}" @selected(old('video_type', $item['video_type'] ?? 'youtube') === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium" for="video_src">YouTube ID / URL (if youtube)</label>
            <input id="video_src" name="video_src" value="{{ old('video_src', $item['video_src'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900" placeholder="dQw4w9WgXcQ or full URL">
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium" for="media">Video file upload (local)</label>
        <input id="media" type="file" name="media" accept=".mp4,.webm,.mov,video/*" class="mt-1 w-full text-sm">
        <p class="text-xs text-gray-500 mt-1">MP4 / WebM / MOV up to 80MB. Leave blank if using YouTube.</p>
        @if (! empty($item['image_url']) && ($item['media_type'] ?? '') === 'video')
            <p class="text-xs text-gray-500 mt-2">Current file: {{ $item['file_path'] ?? 'stored media' }}</p>
        @endif
    </div>
@endif

@if (! empty($item['file_path']))
    <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="remove_media" value="1">
        Remove current media file
    </label>
@endif
<div>
    <label class="block text-sm font-medium" for="thumbnail">Thumbnail / poster image</label>
    <input id="thumbnail" type="file" name="thumbnail" accept=".jpg,.jpeg,.png,.webp,image/*" class="mt-1 w-full text-sm">
    @if (! empty($item['thumbnail_url']) && ($item['thumbnail_url'] ?? '') !== ($item['image_url'] ?? ''))
        <div class="mt-3 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 w-40">
            <img src="{{ $item['thumbnail_url'] }}" alt="Thumbnail" class="w-full h-28 object-cover">
        </div>
    @endif
</div>
<div>
    <label class="block text-sm font-medium" for="sort_order">Sort order</label>
    <input id="sort_order" type="number" name="sort_order" value="{{ old('sort_order', $item['sort_order'] ?? 0) }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
</div>
<div class="flex flex-wrap gap-4 text-sm">
    <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $item['is_published'] ?? true))> Published on public gallery</label>
    <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $item['is_featured'] ?? false))> Featured Moments section</label>
    <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_speakers_highlight" value="1" @checked(old('is_speakers_highlight', $item['is_speakers_highlight'] ?? false))> Speakers highlight</label>
</div>
