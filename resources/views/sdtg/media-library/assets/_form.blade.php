@php $asset = $asset ?? []; @endphp

<div>
    <label class="block text-sm font-medium" for="title">Title</label>
    <input id="title" name="title" value="{{ old('title', $asset['title'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" required>
</div>
<div class="grid gap-4 sm:grid-cols-3">
    <div>
        <label class="block text-sm font-medium" for="media_type">Type</label>
        <select id="media_type" name="media_type" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            @foreach ($mediaTypes as $type)
                <option value="{{ $type }}" @selected(old('media_type', $asset['media_type'] ?? 'image') === $type)>{{ $type }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium" for="status">Status</label>
        <select id="status" name="status" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(old('status', $asset['status'] ?? 'draft') === $status)>{{ $status }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium" for="crusade_year">Crusade year</label>
        <input id="crusade_year" type="number" name="crusade_year" value="{{ old('crusade_year', $asset['crusade_year'] ?? date('Y')) }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" required>
    </div>
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="folder_id">Folder</label>
        <select id="folder_id" name="folder_id" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            <option value="">None</option>
            @foreach ($folders as $folder)
                <option value="{{ $folder['id'] }}" @selected((int) old('folder_id', $asset['folder_id'] ?? 0) === (int) $folder['id'])>{{ $folder['name'] }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium" for="sort_order">Sort order</label>
        <input id="sort_order" type="number" name="sort_order" value="{{ old('sort_order', $asset['sort_order'] ?? 0) }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
    </div>
</div>
<div>
    <label class="block text-sm font-medium" for="description">Description</label>
    <textarea id="description" name="description" rows="3" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('description', $asset['description'] ?? '') }}</textarea>
</div>
<div>
    <label class="block text-sm font-medium" for="tags">Tags</label>
    <input id="tags" name="tags" value="{{ old('tags', $asset['tags'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="video_type">Video type</label>
        <select id="video_type" name="video_type" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            @foreach (['local', 'youtube', 'vimeo'] as $type)
                <option value="{{ $type }}" @selected(old('video_type', $asset['video_type'] ?? 'local') === $type)>{{ $type }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium" for="video_src">Video source / ID</label>
        <input id="video_src" name="video_src" value="{{ old('video_src', $asset['video_src'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
    </div>
</div>
<div>
    <label class="block text-sm font-medium" for="duration_label">Duration label</label>
    <input id="duration_label" name="duration_label" value="{{ old('duration_label', $asset['duration_label'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="e.g. 3:45">
</div>
<div>
    <label class="block text-sm font-medium" for="media">Media file</label>
    <input id="media" type="file" name="media" class="mt-1 w-full text-sm">
    <p class="text-xs text-gray-500 mt-1">Upload only — no URL for images or files.</p>
    @if (! empty($asset['file_url']))
        <div class="mt-3">
            @if (($asset['media_type'] ?? '') === 'image')
                <img src="{{ $asset['thumbnail_url'] ?? $asset['file_url'] }}" alt="" class="max-h-48 rounded border object-cover">
            @else
                <a href="{{ $asset['file_url'] }}" target="_blank" rel="noopener" class="text-indigo-600 text-sm">Open current file</a>
            @endif
        </div>
    @endif
</div>
@if (! empty($asset['file_path']))
    <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="remove_media" value="1">
        Remove current media file
    </label>
@endif
<div>
    <label class="block text-sm font-medium" for="thumbnail">Thumbnail image</label>
    <input id="thumbnail" type="file" name="thumbnail" accept=".jpg,.jpeg,.png,.webp,.gif" class="mt-1 w-full text-sm">
</div>
@if (! empty($asset['thumbnail_path']))
    <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="remove_thumb" value="1">
        Remove current thumbnail
    </label>
@endif
