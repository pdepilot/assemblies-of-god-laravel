@php $announcement = $announcement ?? []; @endphp

<div>
    <label class="block text-sm font-medium" for="title">Title</label>
    <input id="title" name="title" value="{{ old('title', $announcement['title'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900" required>
</div>
<div>
    <label class="block text-sm font-medium" for="category">Category</label>
    <input id="category" name="category" value="{{ old('category', $announcement['category'] ?? 'Announcement') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
</div>
<div>
    <label class="block text-sm font-medium" for="excerpt">Excerpt</label>
    <textarea id="excerpt" name="excerpt" rows="2" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">{{ old('excerpt', $announcement['excerpt'] ?? '') }}</textarea>
</div>
<div>
    <label class="block text-sm font-medium" for="body">Body</label>
    <textarea id="body" name="body" rows="6" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">{{ old('body', $announcement['body'] ?? '') }}</textarea>
</div>
<div>
    <label class="block text-sm font-medium" for="link_url">Link URL</label>
    <input id="link_url" name="link_url" value="{{ old('link_url', $announcement['link_url'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
</div>
<label class="inline-flex items-center gap-2 text-sm">
    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $announcement['is_published'] ?? true))>
    Published
</label>
