<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Frontend Page Manager</h2></x-slot>
    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @include('website._nav', ['canManage' => $canManage])

            <article class="cms-card" id="agActivitiesEditor">
                <div class="cms-card__head">
                    <h2 class="cms-card__title">Homepage Activities</h2>
                </div>
                @if ($canManage)
                    <form method="POST" action="{{ route('website.activities.update') }}" class="cms-card__body" style="display:grid;gap:18px">
                        @csrf
                        @method('PUT')
                        @include('website.activities._activity-fields', [
                            'activities' => $homepageActivities ?? [],
                        ])
                        <div>
                            <button type="submit" class="cms-btn cms-btn--primary"><i class="fas fa-save"></i> Save Activities</button>
                        </div>
                    </form>
                @else
                    <div class="cms-card__body">
                        <p class="cms-help">You can view website pages, but you need website edit access to change Activities.</p>
                    </div>
                @endif
            </article>

            <article class="cms-card" id="agWorshipEditor">
                <div class="cms-card__head">
                    <h2 class="cms-card__title">Our Worship</h2>
                </div>
                @if ($canManage)
                    <form method="POST" action="{{ route('website.worship.update') }}" class="cms-card__body" style="display:grid;gap:18px">
                        @csrf
                        @method('PUT')
                        @include('website.worship._program-fields', [
                            'programs' => $worshipPrograms ?? [],
                            'location' => $worshipLocation ?? [],
                        ])
                        <div>
                            <button type="submit" class="cms-btn cms-btn--primary"><i class="fas fa-save"></i> Save Our Worship</button>
                        </div>
                    </form>
                @else
                    <div class="cms-card__body">
                        <p class="cms-help">You can view website pages, but you need website edit access to change Our Worship.</p>
                    </div>
                @endif
            </article>

            @php $hero = $bootstrap['ag']['hero'] ?? []; @endphp
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold">Homepage hero</h3>
                        <p class="text-sm text-gray-500">Primary welcome banner on the public homepage.</p>
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
                    <h3 class="text-lg font-semibold">All frontend pages</h3>
                    <p class="text-sm text-gray-500">Click Manage to edit everything for that page.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left border-b">
                                <th class="py-2 pr-4">Page</th>
                                <th class="py-2 pr-4">What you can edit</th>
                                <th class="py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach (($bootstrap['catalog'] ?? []) as $key => $meta)
                            <tr class="border-b align-top">
                                <td class="py-3 pr-4">
                                    <div class="font-medium">{{ $meta['label'] }}</div>
                                    <div class="text-xs text-gray-500 font-mono">/{{ $key === 'home' ? '' : $key }}</div>
                                </td>
                                <td class="py-3 pr-4 text-gray-600 dark:text-gray-300">{{ $meta['description'] }}</td>
                                <td class="py-3 whitespace-nowrap">
                                    @if ($canManage)
                                        @php
                                            $manageUrl = ! empty($meta['manage_route']) && \Illuminate\Support\Facades\Route::has($meta['manage_route'])
                                                ? route($meta['manage_route'])
                                                : route('website.pages.edit', $key);
                                        @endphp
                                        <a href="{{ $manageUrl }}" class="inline-flex px-3 py-1.5 bg-indigo-600 text-white rounded-md text-xs font-semibold">Manage</a>
                                    @endif
                                    @if ($key === 'home' && $canManage)
                                        <a href="{{ route('website.worship.edit') }}" class="ml-2 inline-flex px-3 py-1.5 border rounded-md text-xs font-semibold">Our Worship</a>
                                        <a href="{{ route('website.activities.edit') }}" class="ml-2 inline-flex px-3 py-1.5 border rounded-md text-xs font-semibold">Homepage Activities</a>
                                    @endif
                                    @if (! empty($meta['public_route']) && \Illuminate\Support\Facades\Route::has($meta['public_route']))
                                        <a href="{{ route($meta['public_route']) }}" target="_blank" rel="noopener" class="ml-2 text-gray-500 hover:underline text-xs">View live</a>
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
