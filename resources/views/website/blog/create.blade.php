<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">New Blog Post</h2></x-slot>
    <div class="py-10"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        @if ($errors->any())
            <div class="rounded-md bg-red-50 p-4 text-sm text-red-800 mb-4">
                <ul class="list-disc pl-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        <form method="POST" action="{{ route('website.blog.store') }}" enctype="multipart/form-data" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
            @csrf
            <div><label class="block text-sm font-medium">Title</label><input name="title" value="{{ old('title') }}" class="mt-1 w-full rounded border-gray-300" required></div>
            <div><label class="block text-sm font-medium">Category</label><select name="category" class="mt-1 w-full rounded border-gray-300">@foreach($categories as $slug => $label)<option value="{{ $slug }}">{{ $label }}</option>@endforeach</select></div>
            <div><label class="block text-sm font-medium">Excerpt</label><textarea name="excerpt" rows="3" class="mt-1 w-full rounded border-gray-300">{{ old('excerpt') }}</textarea></div>
            <div><label class="block text-sm font-medium">Body HTML</label><textarea name="body_html" rows="8" class="mt-1 w-full rounded border-gray-300">{{ old('body_html') }}</textarea></div>
            <div class="space-y-2">
                <label class="block text-sm font-medium" for="featured_image">Featured image</label>
                <input id="featured_image" name="featured_image" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm">
                <p class="text-xs text-gray-500">JPG, PNG, or WebP up to 5 MB. Upload only — no path or URL.</p>
            </div>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Save Post</button>
        </form>
    </div></div>
</x-app-layout>
