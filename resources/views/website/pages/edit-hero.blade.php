<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Edit Homepage Hero</h2></x-slot>
    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">
                    <ul class="list-disc pl-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <form method="POST" action="{{ route('website.pages.hero.update') }}" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium">Headline</label>
                    <input name="headline" value="{{ old('headline', $hero['headline'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" required>
                </div>
                <div>
                    <label class="block text-sm font-medium">Subheadline</label>
                    <textarea name="subheadline" rows="3" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('subheadline', $hero['subheadline'] ?? '') }}</textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium">CTA label</label>
                        <input name="cta_label" value="{{ old('cta_label', $hero['cta_label'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">CTA URL</label>
                        <input name="cta_url" value="{{ old('cta_url', $hero['cta_url'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    </div>
                </div>
                <div class="space-y-3">
                    <label class="block text-sm font-medium" for="background_image">Background image</label>
                    @if (! empty($backgroundImageUrl))
                        <div class="rounded-md overflow-hidden border border-gray-200 dark:border-gray-700 max-w-md">
                            <img src="{{ $backgroundImageUrl }}" alt="Current hero background" class="w-full max-h-48 object-cover">
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm text-red-700 dark:text-red-300">
                            <input type="checkbox" name="remove_background_image" value="1" @checked(old('remove_background_image')) class="rounded border-gray-300">
                            Remove current image
                        </label>
                    @endif
                    <input id="background_image" name="background_image" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm">
                    <p class="text-xs text-gray-500">JPG, PNG, or WebP up to 5 MB. Upload only — no URL or path.</p>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Save hero</button>
                    <a href="{{ route('website.pages.index') }}" class="px-4 py-2 border rounded-md text-sm">Cancel</a>
                    <a href="{{ url('/') }}" target="_blank" rel="noopener" class="px-4 py-2 border rounded-md text-sm">Preview homepage</a>
                </div>
                <p class="text-xs text-gray-500">Saves the first homepage hero slide shown on the public site. Additional slides keep their existing content.</p>
            </form>
        </div>
    </div>
</x-app-layout>
