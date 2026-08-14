<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Edit SEO — {{ $pageKey ?? ($page['key'] ?? '') }}
        </h2>
    </x-slot>
    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800 mb-4">
                    <ul class="list-disc pl-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 text-sm flex flex-wrap gap-3 items-center justify-between">
                <div>
                    <p class="font-medium text-gray-800 dark:text-gray-100">Public page</p>
                    <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="text-indigo-600 break-all">{{ $publicUrl }}</a>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="px-3 py-2 border rounded-md text-sm">Preview live</a>
                    @if (! empty($contentEditRoute))
                        <a href="{{ $contentEditRoute }}" class="px-3 py-2 border rounded-md text-sm">Full page editor</a>
                    @endif
                </div>
            </div>

            <form method="POST" action="{{ route('website.seo.update', $page['key']) }}" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-6">
                @csrf
                @method('PUT')

                @if (! empty($canEditChrome) && is_array($contentPage ?? null))
                    <div class="space-y-4 pb-6 border-b border-gray-200 dark:border-gray-700">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Hero banner (on-page)</h3>
                            <p class="text-xs text-gray-500 mt-1">This is the large title visitors see on the page card — not the browser tab title.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="heading">Hero title</label>
                            <input id="heading" name="heading" value="{{ old('heading', $contentPage['heading'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" required maxlength="255">
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="eyebrow">Eyebrow</label>
                            <input id="eyebrow" name="eyebrow" value="{{ old('eyebrow', $contentPage['eyebrow'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" maxlength="120">
                        </div>
                        <div>
                            <label class="block text-sm font-medium" for="intro">Intro</label>
                            <textarea id="intro" name="intro" rows="3" maxlength="2000" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('intro', $contentPage['intro'] ?? '') }}</textarea>
                        </div>
                    </div>
                @endif

                <div class="space-y-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Search &amp; social (SEO)</h3>
                        <p class="text-xs text-gray-500 mt-1">Browser tab, Google snippet, and social share cards.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Title tag</label>
                        <input name="title" value="{{ old('title', $page['title'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" required maxlength="255">
                        <p class="text-xs text-gray-500 mt-1">Shown in the browser tab and search results.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Meta description</label>
                        <textarea name="meta_description" rows="4" maxlength="500" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('meta_description', $page['meta_description'] ?? '') }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Aim for about 150–160 characters.</p>
                    </div>
                    <div>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="include_in_sitemap" value="1" @checked(old('include_in_sitemap', $page['include_in_sitemap'] ?? true)) class="rounded border-gray-300">
                            Include this page in the XML sitemap
                        </label>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Robots notes (internal)</label>
                        <textarea name="robots_notes" rows="2" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="e.g. Allow indexing; noindex only if temporarily unpublished">{{ old('robots_notes', $page['robots_notes'] ?? '') }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Optional notes for editors — not written to robots.txt yet.</p>
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
                </div>

                <div class="flex gap-3 pt-2 border-t border-gray-200 dark:border-gray-700">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Save</button>
                    <a href="{{ route('website.seo.index') }}" class="px-4 py-2 border rounded-md text-sm">Back to SEO list</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
