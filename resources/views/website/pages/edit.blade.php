<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Edit page — {{ $catalog['label'] }}</h2>
    </x-slot>
    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">
                    <ul class="list-disc pl-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <p class="text-sm text-gray-500">{{ $catalog['description'] }}</p>

            <form method="POST" action="{{ route('website.pages.update', $pageKey) }}" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf
                @method('PUT')

                @if (($catalog['type'] ?? '') === 'home')
                    <h3 class="font-semibold">Ministries section</h3>
                    <div>
                        <label class="block text-sm font-medium">Eyebrow</label>
                        <input name="ministries_eyebrow" value="{{ old('ministries_eyebrow', $page['ministries_eyebrow'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Title</label>
                        <input name="ministries_title" value="{{ old('ministries_title', $page['ministries_title'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    </div>

                    <h3 class="font-semibold pt-2">Events section</h3>
                    <div>
                        <label class="block text-sm font-medium">Eyebrow</label>
                        <input name="events_eyebrow" value="{{ old('events_eyebrow', $page['events_eyebrow'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Title</label>
                        <input name="events_title" value="{{ old('events_title', $page['events_title'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Intro</label>
                        <textarea name="events_intro" rows="3" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('events_intro', $page['events_intro'] ?? '') }}</textarea>
                    </div>

                    <h3 class="font-semibold pt-2">Worship section</h3>
                    <div>
                        <label class="block text-sm font-medium">Eyebrow</label>
                        <input name="worship_eyebrow" value="{{ old('worship_eyebrow', $page['worship_eyebrow'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Title</label>
                        <input name="worship_title" value="{{ old('worship_title', $page['worship_title'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Intro</label>
                        <textarea name="worship_intro" rows="3" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('worship_intro', $page['worship_intro'] ?? '') }}</textarea>
                    </div>
                @else
                    <div>
                        <label class="block text-sm font-medium">Heading</label>
                        <input name="heading" value="{{ old('heading', $page['heading'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Eyebrow</label>
                        <input name="eyebrow" value="{{ old('eyebrow', $page['eyebrow'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Intro</label>
                        <textarea name="intro" rows="3" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('intro', $page['intro'] ?? '') }}</textarea>
                    </div>

                    @if (($catalog['type'] ?? '') === 'content')
                        <div>
                            <label class="block text-sm font-medium">Page body (HTML allowed)</label>
                            <textarea name="body_html" rows="10" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('body_html', $page['body_html'] ?? '') }}</textarea>
                        </div>
                    @endif

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium">CTA label</label>
                            <input name="cta_label" value="{{ old('cta_label', $page['cta_label'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        </div>
                        <div>
                            <label class="block text-sm font-medium">CTA URL</label>
                            <input name="cta_url" value="{{ old('cta_url', $page['cta_url'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="/contact">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-sm font-medium" for="hero_image">Header image</label>
                        @if (! empty($heroImageUrl))
                            <div class="rounded-md overflow-hidden border border-gray-200 dark:border-gray-700 max-w-md">
                                <img src="{{ $heroImageUrl }}" alt="Current header image" class="w-full max-h-48 object-cover">
                            </div>
                            <label class="inline-flex items-center gap-2 text-sm text-red-700 dark:text-red-300">
                                <input type="checkbox" name="remove_hero_image" value="1" @checked(old('remove_hero_image')) class="rounded border-gray-300">
                                Remove current image
                            </label>
                        @endif
                        <input id="hero_image" name="hero_image" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm">
                        <p class="text-xs text-gray-500">JPG, PNG, or WebP up to 5 MB. Upload only — no path or URL.</p>
                    </div>
                @endif

                <div class="flex flex-wrap gap-3 pt-2">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Save page</button>
                    <a href="{{ route('website.pages.index') }}" class="px-4 py-2 border rounded-md text-sm">Cancel</a>
                    @if (! empty($catalog['public_route']))
                        <a href="{{ route($catalog['public_route']) }}" target="_blank" rel="noopener" class="px-4 py-2 border rounded-md text-sm">Preview</a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
