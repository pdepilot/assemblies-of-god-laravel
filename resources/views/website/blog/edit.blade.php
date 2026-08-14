<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Edit Blog Post</h2></x-slot>
    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800 mb-4">
                    <ul class="list-disc pl-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <form method="POST" action="{{ route('website.blog.update', $post['id']) }}" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium">Title</label>
                    <input name="title" value="{{ old('title', $post['title'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" required>
                </div>
                <div>
                    <label class="block text-sm font-medium">Category</label>
                    <select name="category" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        @foreach ($categories as $slug => $label)
                            <option value="{{ $slug }}" @selected(old('category', $post['category'] ?? '') === $slug)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium">Excerpt</label>
                    <textarea name="excerpt" rows="3" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('excerpt', $post['excerpt'] ?? '') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium">Body HTML</label>
                    <textarea name="body_html" rows="8" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('body_html', $post['body_html'] ?? '') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium">SEO title (optional)</label>
                    <input name="seo_title" value="{{ old('seo_title', $post['seo_title'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                </div>
                <div>
                    <label class="block text-sm font-medium">Meta description</label>
                    <textarea name="meta_description" rows="2" maxlength="255" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('meta_description', $post['meta_description'] ?? '') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium">Tags (comma-separated)</label>
                    <input name="tags" value="{{ old('tags', is_array($post['tags'] ?? null) ? implode(', ', $post['tags']) : '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="prayer, faith, family">
                </div>
                <div>
                    <label class="block text-sm font-medium">Author</label>
                    <input name="author" value="{{ old('author', $post['author'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                </div>
                <div class="space-y-3">
                    <label class="block text-sm font-medium" for="featured_image">Featured image</label>
                    @if (! empty($featuredImageUrl))
                        <div class="rounded-md overflow-hidden border border-gray-200 dark:border-gray-700 max-w-md">
                            <img src="{{ $featuredImageUrl }}" alt="Current featured image" class="w-full max-h-48 object-cover">
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm text-red-700 dark:text-red-300">
                            <input type="checkbox" name="remove_featured_image" value="1" @checked(old('remove_featured_image')) class="rounded border-gray-300">
                            Remove current image
                        </label>
                    @endif
                    <input id="featured_image" name="featured_image" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm">
                    <label class="block text-sm font-medium mt-2">Featured image ALT text</label>
                    <input name="featured_image_alt" value="{{ old('featured_image_alt', $post['featured_image_alt'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-xs text-gray-500">JPG, PNG, or WebP up to 5 MB. Upload only — no path or URL.</p>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Update post</button>
                    <a href="{{ route('website.blog.show', $post['id']) }}" class="px-4 py-2 border rounded-md text-sm">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
