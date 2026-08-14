<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Manage page — {{ $catalog['label'] }}</h2>
    </x-slot>
    <div class="py-10" x-data="{ tab: 'content' }">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">
                    <ul class="list-disc pl-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-gray-500">{{ $catalog['description'] }}</p>
                <div class="flex flex-wrap gap-2 text-sm">
                    <a href="{{ route('website.pages.index') }}" class="px-3 py-1 rounded border">All pages</a>
                    @if (! empty($catalog['public_route']) && \Illuminate\Support\Facades\Route::has($catalog['public_route']))
                        <a href="{{ route($catalog['public_route']) }}" target="_blank" rel="noopener" class="px-3 py-1 rounded border">Preview live</a>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap gap-2 text-sm">
                <button type="button" @click="tab = 'content'" class="px-3 py-1 rounded border" :class="tab === 'content' ? 'bg-indigo-600 text-white' : ''">Page content</button>
                <button type="button" @click="tab = 'seo'" class="px-3 py-1 rounded border" :class="tab === 'seo' ? 'bg-indigo-600 text-white' : ''">SEO</button>
                @if (count($related) > 0)
                    <button type="button" @click="tab = 'related'" class="px-3 py-1 rounded border" :class="tab === 'related' ? 'bg-indigo-600 text-white' : ''">Related editors</button>
                @endif
            </div>

            <form method="POST" action="{{ route('website.pages.update', $pageKey) }}" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-5">
                @csrf
                @method('PUT')

                <div x-show="tab === 'content'" x-cloak class="space-y-5">
                    @if (($catalog['type'] ?? '') === 'home')
                        @include('website.pages._home-fields', ['page' => $page])
                    @elseif (($catalog['type'] ?? '') === 'donate')
                        @include('website.pages._donate-fields', ['page' => $page])
                    @else
                        @include('website.pages._chrome-fields', [
                            'page' => $page,
                            'catalog' => $catalog,
                            'heroImageUrl' => $heroImageUrl,
                        ])
                    @endif
                </div>

                <div x-show="tab === 'seo'" x-cloak class="space-y-4">
                    <p class="text-sm text-gray-500">Search / social metadata for this page.</p>
                    <div>
                        <label class="block text-sm font-medium">SEO title</label>
                        <input name="seo_title" value="{{ old('seo_title', $seo['title'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Meta description</label>
                        <textarea name="seo_meta_description" rows="4" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('seo_meta_description', $seo['meta_description'] ?? '') }}</textarea>
                    </div>
                    <div class="space-y-3">
                        <label class="block text-sm font-medium" for="seo_og_image">Open Graph image</label>
                        @if (! empty($seoOgImageUrl))
                            <div class="rounded-md overflow-hidden border border-gray-200 dark:border-gray-700 max-w-md">
                                <img src="{{ $seoOgImageUrl }}" alt="Current Open Graph image" class="w-full max-h-48 object-cover">
                            </div>
                            <label class="inline-flex items-center gap-2 text-sm text-red-700 dark:text-red-300">
                                <input type="checkbox" name="remove_seo_og_image" value="1" @checked(old('remove_seo_og_image')) class="rounded border-gray-300">
                                Remove current image
                            </label>
                        @endif
                        <input id="seo_og_image" name="seo_og_image" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm">
                        <p class="text-xs text-gray-500">JPG, PNG, or WebP up to 5 MB. Upload only — no URL or path.</p>
                    </div>
                </div>

                @if (count($related) > 0)
                    <div x-show="tab === 'related'" x-cloak class="space-y-3">
                        <p class="text-sm text-gray-500">These modules also feed this page. Open them to manage that content.</p>
                        <ul class="divide-y border rounded-md">
                            @foreach ($related as $item)
                                @php
                                    $routeName = $item['route'] ?? '';
                                    $params = $item['params'] ?? [];
                                @endphp
                                @if ($routeName !== '' && \Illuminate\Support\Facades\Route::has($routeName))
                                    <li class="flex items-center justify-between gap-3 px-4 py-3">
                                        <span class="text-sm font-medium">{{ $item['label'] }}</span>
                                        <a href="{{ route($routeName, $params) }}" class="text-indigo-600 text-sm font-semibold hover:underline">Open</a>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="flex flex-wrap gap-3 pt-2 border-t">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Save page</button>
                    <a href="{{ route('website.pages.index') }}" class="px-4 py-2 border rounded-md text-sm">Back</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
