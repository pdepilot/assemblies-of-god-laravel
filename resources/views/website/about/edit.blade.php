<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">About Page Content</h2>
                <p class="text-sm text-gray-500 mt-1">Manage the About section on the homepage and the full About Us page.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ url('/') }}" target="_blank" rel="noopener" class="px-3 py-2 text-sm rounded-md border">View Homepage</a>
                <a href="{{ route('public.about') }}" target="_blank" rel="noopener" class="px-3 py-2 text-sm rounded-md border">View About Page</a>
            </div>
        </div>
    </x-slot>

    <div class="py-10" x-data="{ tab: '{{ old('section', $activeTab ?? 'homepage_about') }}' }">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">
                    <ul class="list-disc pl-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            @include('website._nav', ['canManage' => true])

            <div class="flex flex-wrap gap-2">
                <button type="button" @click="tab = 'homepage_about'" :class="tab === 'homepage_about' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800'" class="px-4 py-2 rounded-md border text-sm font-medium">Homepage About Section</button>
                <button type="button" @click="tab = 'about_page'" :class="tab === 'about_page' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800'" class="px-4 py-2 rounded-md border text-sm font-medium">About Page</button>
            </div>

            {{-- Homepage About --}}
            <div x-show="tab === 'homepage_about'" x-cloak>
                <form method="POST" action="{{ route('website.about.update') }}" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="section" value="homepage_about">
                    <h3 class="text-lg font-semibold">Homepage — About Section</h3>

                    @include('website.about._shared-fields', ['prefix' => 'content', 'data' => $homepage])

                    @php $banner = is_array($homepage['scripture_banner'] ?? null) ? $homepage['scripture_banner'] : []; @endphp
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-4" x-data="{ verses: {{ Js::from(old('content.scripture_banner.verses', $banner['verses'] ?? [['quote' => '', 'reference' => '']])) }} }">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h4 class="font-semibold">Scripture Banner</h4>
                            <button type="button" class="px-3 py-1.5 text-sm rounded-md border" @click="verses.push({ quote: '', reference: '' })">Add verse</button>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium">CTA label</label>
                                <input type="text" name="content[scripture_banner][cta_label]" value="{{ old('content.scripture_banner.cta_label', $banner['cta_label'] ?? '') }}" maxlength="80" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            </div>
                            <div>
                                <label class="block text-sm font-medium">CTA URL</label>
                                <input type="text" name="content[scripture_banner][cta_url]" value="{{ old('content.scripture_banner.cta_url', $banner['cta_url'] ?? '') }}" maxlength="255" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="about">
                            </div>
                        </div>
                        <template x-for="(verse, index) in verses" :key="index">
                            <div class="rounded-md border border-gray-200 dark:border-gray-700 p-3 space-y-3">
                                <div>
                                    <label class="block text-sm font-medium">Quote</label>
                                    <textarea :name="'content[scripture_banner][verses][' + index + '][quote]'" rows="2" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" x-model="verse.quote"></textarea>
                                </div>
                                <div class="flex gap-2 items-end">
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium">Reference</label>
                                        <input type="text" :name="'content[scripture_banner][verses][' + index + '][reference]'" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" x-model="verse.reference" maxlength="120">
                                    </div>
                                    <button type="button" class="px-3 py-2 text-sm text-red-600" @click="verses.splice(index, 1)">Remove</button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="flex flex-wrap justify-end gap-2 pt-2">
                        <button type="submit" formaction="{{ route('website.about.reset') }}" formmethod="POST" data-confirm="Reset this section to the default content? This cannot be undone." data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger" class="px-4 py-2 rounded-md border text-sm">Reset to Default</button>
                        <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Save Homepage Section</button>
                    </div>
                </form>
            </div>

            {{-- About Page --}}
            <div x-show="tab === 'about_page'" x-cloak>
                <form method="POST" action="{{ route('website.about.update') }}" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="section" value="about_page">
                    <h3 class="text-lg font-semibold">About Page</h3>

                    @php $hero = is_array($aboutPage['hero'] ?? null) ? $aboutPage['hero'] : []; @endphp
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-4">
                        <h4 class="font-semibold">Page Hero</h4>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium">Hero title</label>
                                <input type="text" name="content[hero][title]" value="{{ old('content.hero.title', $hero['title'] ?? '') }}" maxlength="120" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Breadcrumb: Home label</label>
                                <input type="text" name="content[hero][breadcrumb_home_label]" value="{{ old('content.hero.breadcrumb_home_label', $hero['breadcrumb_home_label'] ?? '') }}" maxlength="40" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Breadcrumb: Home URL</label>
                                <input type="text" name="content[hero][breadcrumb_home_url]" value="{{ old('content.hero.breadcrumb_home_url', $hero['breadcrumb_home_url'] ?? '') }}" maxlength="255" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="./">
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Breadcrumb: Parent label</label>
                                <input type="text" name="content[hero][breadcrumb_parent_label]" value="{{ old('content.hero.breadcrumb_parent_label', $hero['breadcrumb_parent_label'] ?? '') }}" maxlength="40" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Breadcrumb: Parent URL</label>
                                <input type="text" name="content[hero][breadcrumb_parent_url]" value="{{ old('content.hero.breadcrumb_parent_url', $hero['breadcrumb_parent_url'] ?? '') }}" maxlength="255" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Breadcrumb: Current page</label>
                                <input type="text" name="content[hero][breadcrumb_current]" value="{{ old('content.hero.breadcrumb_current', $hero['breadcrumb_current'] ?? '') }}" maxlength="40" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            </div>
                        </div>
                    </div>

                    @include('website.about._shared-fields', ['prefix' => 'content', 'data' => $aboutPage])

                    @php $cta = is_array($aboutPage['cta_banner'] ?? null) ? $aboutPage['cta_banner'] : []; @endphp
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-4">
                        <h4 class="font-semibold">Bottom CTA Banner</h4>
                        <div>
                            <label class="block text-sm font-medium">Banner title</label>
                            <textarea name="content[cta_banner][title]" rows="2" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('content.cta_banner.title', $cta['title'] ?? '') }}</textarea>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium">CTA label</label>
                                <input type="text" name="content[cta_banner][cta_label]" value="{{ old('content.cta_banner.cta_label', $cta['cta_label'] ?? '') }}" maxlength="80" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            </div>
                            <div>
                                <label class="block text-sm font-medium">CTA URL</label>
                                <input type="text" name="content[cta_banner][cta_url]" value="{{ old('content.cta_banner.cta_url', $cta['cta_url'] ?? '') }}" maxlength="255" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-2 pt-2">
                        <button type="submit" formaction="{{ route('website.about.reset') }}" formmethod="POST" data-confirm="Reset this section to the default content? This cannot be undone." data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger" class="px-4 py-2 rounded-md border text-sm">Reset to Default</button>
                        <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Save About Page</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
