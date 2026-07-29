@php $album = $album ?? []; @endphp

<div>
    <label class="block text-sm font-medium" for="title">Title</label>
    <input id="title" name="title" value="{{ old('title', $album['title'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900" required>
</div>
<div>
    <label class="block text-sm font-medium" for="crusade_year">Crusade year</label>
    <input id="crusade_year" type="number" name="crusade_year" value="{{ old('crusade_year', $album['crusade_year'] ?? date('Y')) }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900" required>
</div>
<div>
    <label class="block text-sm font-medium" for="description">Description</label>
    <textarea id="description" name="description" rows="3" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">{{ old('description', $album['description'] ?? '') }}</textarea>
</div>
<div>
    <label class="block text-sm font-medium" for="sort_order">Sort order</label>
    <input id="sort_order" type="number" name="sort_order" value="{{ old('sort_order', $album['sort_order'] ?? 0) }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
</div>
<div>
    <label class="block text-sm font-medium" for="cover">Cover image</label>
    <input id="cover" type="file" name="cover" accept=".jpg,.jpeg,.png,.webp" class="mt-1 w-full text-sm">
    @if (! empty($album['cover_url']))
        <div class="mt-3 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 max-w-md">
            <img src="{{ $album['cover_url'] }}" alt="{{ $album['title'] ?? 'Album cover' }}" class="w-full max-h-56 object-cover">
        </div>
    @endif
</div>
@if (! empty($album['cover_path']))
    <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="remove_cover" value="1">
        Remove current cover
    </label>
@endif
<label class="inline-flex items-center gap-2 text-sm">
    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $album['is_published'] ?? true))>
    Published
</label>
