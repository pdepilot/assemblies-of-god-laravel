<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Website Pages</h2></x-slot>
    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @include('website._nav', ['canManage' => $canManage])

            @php $hero = $bootstrap['ag']['hero'] ?? []; @endphp
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold">Homepage hero</h3>
                        <p class="text-sm text-gray-500">Primary welcome message shown on the public homepage.</p>
                    </div>
                    @if ($canManage)
                        <a href="{{ route('website.pages.hero.edit') }}" class="inline-block px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Edit hero</a>
                    @endif
                </div>
                <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                    <div>
                        <dt class="text-gray-500">Headline</dt>
                        <dd class="font-medium">{{ $hero['headline'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">CTA</dt>
                        <dd class="font-medium">{{ ($hero['cta_label'] ?? '') !== '' ? $hero['cta_label'] : '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500">Subheadline</dt>
                        <dd class="font-medium">{{ ($hero['subheadline'] ?? '') !== '' ? $hero['subheadline'] : '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold">Site pages</h3>
                    <p class="text-sm text-gray-500">Edit page headers and content shown on the public website.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left border-b">
                                <th class="py-2 pr-4">Page</th>
                                <th class="py-2 pr-4">Heading / title</th>
                                <th class="py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach (($bootstrap['catalog'] ?? []) as $key => $meta)
                            @php
                                $page = $bootstrap['ag']['pages'][$key] ?? [];
                                $preview = $page['heading'] ?? $page['ministries_title'] ?? $page['events_title'] ?? '—';
                            @endphp
                            <tr class="border-b align-top">
                                <td class="py-3 pr-4">
                                    <div class="font-medium">{{ $meta['label'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $meta['description'] }}</div>
                                </td>
                                <td class="py-3 pr-4">{{ \Illuminate\Support\Str::limit((string) $preview, 60) }}</td>
                                <td class="py-3 whitespace-nowrap">
                                    @if ($canManage)
                                        <a href="{{ route('website.pages.edit', $key) }}" class="text-indigo-600 hover:underline">Edit</a>
                                    @endif
                                    @if (! empty($meta['public_route']))
                                        <a href="{{ route($meta['public_route']) }}" target="_blank" rel="noopener" class="ml-3 text-gray-500 hover:underline">View</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
