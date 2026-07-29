<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Edit SEO — {{ $page['key'] ?? '' }}</h2></x-slot>
    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800 mb-4">
                    <ul class="list-disc pl-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <form method="POST" action="{{ route('website.seo.update', $page['key']) }}" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium">Title</label>
                    <input name="title" value="{{ old('title', $page['title'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" required>
                </div>
                <div>
                    <label class="block text-sm font-medium">Meta description</label>
                    <textarea name="meta_description" rows="4" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('meta_description', $page['meta_description'] ?? '') }}</textarea>
                </div>
                <div class="space-y-3">
                    <label class="block text-sm font-medium" for="og_image">Open Graph image (optional)</label>
                    @if (! empty($ogImageUrl))
                        <div class="rounded-md overflow-hidden border border-gray-200 dark:border-gray-700 max-w-md">
                            <img src="{{ $ogImageUrl }}" alt="Current Open Graph image" class="w-full max-h-48 object-cover">
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm text-red-700 dark:text-red-300">
                            <input type="checkbox" name="remove_og_image" value="1" @checked(old('remove_og_image')) class="rounded border-gray-300">
                            Remove current image
                        </label>
                    @endif
                    <input id="og_image" name="og_image" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm">
                    <p class="text-xs text-gray-500">JPG, PNG, or WebP up to 5 MB. Upload only — no path or URL.</p>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Save SEO</button>
                    <a href="{{ route('website.seo.index') }}" class="px-4 py-2 border rounded-md text-sm">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
