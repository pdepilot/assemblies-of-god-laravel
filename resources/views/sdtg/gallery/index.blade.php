@php
    $tab = $tab ?? 'photos';
    $pendingMemories = collect($memories)->where('status', 'pending')->count();
    $featuredMemories = collect($memories)->where('status', 'featured')->count();
    $publishedEditions = collect($editions)->filter(fn ($e) => ! empty($e['is_published']))->count();
    $sections = [
        'photos' => ['nav' => 'Photo Archive', 'eyebrow' => 'Photo Archive', 'title' => 'Captured In His Presence', 'desc' => 'Upload photos for the public masonry gallery and category filters.'],
        'videos' => ['nav' => 'Video Archive', 'eyebrow' => 'Video Archive', 'title' => 'Watch & Relive', 'desc' => 'Upload full sessions, messages, choir clips, testimonies, and crusade highlights.'],
        'featured' => ['nav' => 'Featured Moments', 'eyebrow' => 'Featured Moments', 'title' => 'Moments That Changed Everything', 'desc' => 'Items marked Featured appear in this spotlight section on the public page.'],
        'collections' => ['nav' => 'Crusade Collections', 'eyebrow' => 'Organized Archives', 'title' => 'Crusade Collections', 'desc' => 'Create albums that group photos/videos into crusade collections.'],
        'timeline' => ['nav' => 'Through The Years', 'eyebrow' => 'Our Legacy', 'title' => 'SDTG Through The Years', 'desc' => 'Manage crusade editions for the timeline and the share-form edition list.'],
        'community' => ['nav' => 'Community Stories', 'eyebrow' => 'From The Community', 'title' => 'Shared SDTG Stories', 'desc' => 'Feature public memory submissions so they appear on the community wall.'],
        'social' => ['nav' => 'Social Wall', 'eyebrow' => 'Stay Connected', 'title' => 'SDTG Social Wall', 'desc' => 'Social cards use church social links plus photo previews from the archive.'],
        'share' => ['nav' => 'Share Memories', 'eyebrow' => 'Your Story Matters', 'title' => 'Share Your SDTG Memories', 'desc' => 'Review pending visitor submissions from the public share form.'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">SDTG Gallery Page Manager</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Each tab matches a section on the public <code class="text-xs">/sdgt/gallery</code> page.</p>
            </div>
            <a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="px-4 py-2 border rounded-md text-sm font-semibold">Preview public page</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @include('sdtg._nav', ['canManage' => $canManage])

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['label' => 'Photos', 'value' => $stats['photos'], 'tab' => 'photos'],
                    ['label' => 'Videos', 'value' => $stats['videos'], 'tab' => 'videos'],
                    ['label' => 'Featured', 'value' => $stats['featured'] ?? 0, 'tab' => 'featured'],
                    ['label' => 'Albums', 'value' => $stats['albums'], 'tab' => 'collections'],
                ] as $stat)
                    <a href="{{ route('sdtg.gallery.index', ['tab' => $stat['tab']]) }}" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 hover:ring-2 hover:ring-indigo-500 transition">
                        <p class="text-sm text-gray-500">{{ $stat['label'] }}</p>
                        <p class="text-2xl font-semibold mt-1">{{ number_format($stat['value']) }}</p>
                    </a>
                @endforeach
            </div>

            <nav class="flex flex-wrap gap-2">
                @foreach ($sections as $key => $section)
                    <a href="{{ route('sdtg.gallery.index', ['tab' => $key]) }}"
                       class="px-3 py-2 rounded-md text-sm font-medium {{ $tab === $key ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border' }}">
                        {{ $section['nav'] }}
                        @if ($key === 'share' && $pendingMemories > 0)
                            <span class="ml-1">({{ $pendingMemories }})</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900 rounded-lg p-4">
                <p class="text-xs uppercase tracking-wide text-indigo-700 dark:text-indigo-300 font-semibold">{{ $sections[$tab]['eyebrow'] }}</p>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mt-1">{{ $sections[$tab]['title'] }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ $sections[$tab]['desc'] }}</p>
            </div>

            @if (in_array($tab, ['photos', 'videos', 'featured'], true))
                <div class="flex flex-wrap gap-2">
                    @if ($canManage)
                        @if ($tab === 'photos')
                            <a href="{{ route('sdtg.gallery.items.create', ['type' => 'photo']) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Upload photo</a>
                        @elseif ($tab === 'videos')
                            <a href="{{ route('sdtg.gallery.items.create', ['type' => 'video']) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Upload video</a>
                        @else
                            <a href="{{ route('sdtg.gallery.items.create', ['type' => 'photo', 'featured' => 1]) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Add featured photo</a>
                            <a href="{{ route('sdtg.gallery.items.create', ['type' => 'video', 'featured' => 1]) }}" class="px-4 py-2 border rounded-md text-sm font-semibold">Add featured video</a>
                        @endif
                    @endif
                </div>

                <form method="GET" action="{{ route('sdtg.gallery.index') }}" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-medium mb-1">Search</label>
                        <input type="text" name="q" value="{{ $filters['q'] }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm" placeholder="Title, caption, tags">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Year</label>
                        <input type="number" name="year" value="{{ $filters['year'] ?: '' }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1">Album</label>
                        <select name="album_id" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                            <option value="">All</option>
                            @foreach ($albums as $album)
                                <option value="{{ $album['id'] }}" @selected((int) $filters['album_id'] === (int) $album['id'])>{{ $album['title'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($tab === 'photos')
                        <div>
                            <label class="block text-xs font-medium mb-1">Category</label>
                            <select name="category" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                                <option value="">All</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}" @selected($filters['category'] === $category)>{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="lg:col-span-5">
                        <button class="px-3 py-2 rounded border text-sm">Apply filters</button>
                    </div>
                </form>

                @include('sdtg.gallery._items_table', [
                    'result' => $result,
                    'filters' => $filters,
                    'tab' => $tab,
                    'canManage' => $canManage,
                    'emptyMessage' => match ($tab) {
                        'videos' => 'No videos yet. Upload one to fill Watch & Relive.',
                        'featured' => 'No featured items yet. Mark an item as Featured, or add one here.',
                        default => 'No photos yet. Upload one to fill Captured In His Presence.',
                    },
                ])
            @endif

            @if ($tab === 'collections')
                <div class="flex flex-wrap gap-2">
                    @if ($canManage)
                        <a href="{{ route('sdtg.gallery.albums.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">New album / collection</a>
                    @endif
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @forelse ($albums as $album)
                            <article class="border rounded-lg overflow-hidden">
                                @if (! empty($album['cover_url']))
                                    <img src="{{ $album['cover_url'] }}" alt="{{ $album['title'] }}" class="w-full h-36 object-cover bg-gray-100">
                                @endif
                                <div class="p-4">
                                    <h4 class="font-medium">{{ $album['title'] }}</h4>
                                    <p class="text-xs text-gray-500 mt-1">{{ $album['crusade_year'] }} · {{ $album['item_count'] }} items · {{ ! empty($album['is_published']) ? 'Published' : 'Draft' }}</p>
                                    @if ($canManage)
                                        <div class="mt-3 flex gap-3 text-sm">
                                            <a href="{{ route('sdtg.gallery.albums.edit', $album['id']) }}" class="text-indigo-600">Edit</a>
                                            <form method="POST" action="{{ route('sdtg.gallery.albums.destroy', $album['id']) }}" data-confirm="Delete this album and all its items?" data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600">Delete</button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <p class="text-sm text-gray-500">No collections yet. Create an album to populate Crusade Collections.</p>
                        @endforelse
                    </div>
                </div>
            @endif

            @if ($tab === 'timeline')
                <div class="grid gap-6 lg:grid-cols-5">
                    @if ($canManage)
                        <div class="lg:col-span-2 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                            <h3 class="font-semibold mb-1">Add crusade edition</h3>
                            <p class="text-xs text-gray-500 mb-4">Appears on Through The Years and in the share-form edition dropdown.</p>
                            <form method="POST" action="{{ route('sdtg.gallery.editions.store') }}" class="space-y-3">
                                @csrf
                                <div>
                                    <label class="block text-xs font-medium mb-1">Year *</label>
                                    <input type="number" name="crusade_year" value="{{ old('crusade_year', date('Y')) }}" min="2000" max="2100" required class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1">Theme *</label>
                                    <input type="text" name="theme" value="{{ old('theme') }}" required maxlength="255" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1">Speakers summary *</label>
                                    <textarea name="speakers_summary" rows="3" required class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">{{ old('speakers_summary') }}</textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1">Timeline highlights</label>
                                    <textarea name="highlights" rows="3" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">{{ old('highlights') }}</textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1">Venue</label>
                                    <input type="text" name="venue" value="{{ old('venue', 'Owerri, Nigeria') }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                                </div>
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', true))>
                                    Published on public gallery
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm block">
                                    <input type="checkbox" name="is_next_crusade" value="1" @checked(old('is_next_crusade'))>
                                    Mark as next crusade
                                </label>
                                <button class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Save edition</button>
                            </form>
                        </div>
                    @endif

                    <div class="{{ $canManage ? 'lg:col-span-3' : 'lg:col-span-5' }} bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                        <p class="text-xs text-gray-500 mb-3">{{ $publishedEditions }} published · {{ count($editions) }} total</p>
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left border-b">
                                    <th class="py-2">Year</th>
                                    <th>Theme / highlights</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse ($editions as $edition)
                                <tr class="border-b align-top">
                                    <td class="py-3 font-medium">{{ $edition['crusade_year'] }}</td>
                                    <td class="py-3">
                                        @if ($canManage)
                                            <form method="POST" action="{{ route('sdtg.gallery.editions.update', $edition['id']) }}" class="space-y-2">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="crusade_year" value="{{ $edition['crusade_year'] }}">
                                                <input type="text" name="theme" value="{{ $edition['theme'] }}" required class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                                                <textarea name="speakers_summary" rows="2" required class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">{{ $edition['speakers_summary'] }}</textarea>
                                                <textarea name="highlights" rows="2" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">{{ $edition['highlights'] }}</textarea>
                                                <input type="text" name="venue" value="{{ $edition['venue'] }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                                                <div class="flex flex-wrap gap-3 items-center">
                                                    <label class="inline-flex items-center gap-1 text-xs">
                                                        <input type="checkbox" name="is_published" value="1" @checked(! empty($edition['is_published']))>
                                                        Published
                                                    </label>
                                                    <label class="inline-flex items-center gap-1 text-xs">
                                                        <input type="checkbox" name="is_next_crusade" value="1" @checked(! empty($edition['is_next_crusade']))>
                                                        Next
                                                    </label>
                                                    <button class="text-indigo-600 text-xs font-semibold">Update</button>
                                                </div>
                                            </form>
                                        @else
                                            <div class="font-medium">{{ $edition['theme'] }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3">{{ ! empty($edition['is_published']) ? 'Published' : 'Draft' }}</td>
                                    <td class="py-3">
                                        @if ($canManage)
                                            <form method="POST" action="{{ route('sdtg.gallery.editions.destroy', $edition['id']) }}" data-confirm="Delete this edition?" data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-red-600 text-sm">Delete</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-6 text-center text-gray-500">No editions yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if (in_array($tab, ['community', 'share'], true))
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                    @if ($tab === 'share')
                        <p class="text-sm text-gray-600 dark:text-gray-300">Visitors submit memories from the public Share form. Feature approved ones to publish them under <strong>Shared SDTG Stories</strong>.</p>
                    @else
                        <form method="GET" action="{{ route('sdtg.gallery.index') }}" class="flex gap-2 items-end">
                            <input type="hidden" name="tab" value="community">
                            <div>
                                <label class="block text-xs font-medium mb-1">Filter status</label>
                                <select name="memory_status" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                                    <option value="">All</option>
                                    @foreach (['pending', 'featured', 'rejected'] as $status)
                                        <option value="{{ $status }}" @selected(($filters['memory_status'] ?? '') === $status)>{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="px-3 py-2 rounded border text-sm">Filter</button>
                        </form>
                        <p class="text-sm text-gray-500">{{ $featuredMemories }} featured on the public wall.</p>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left border-b">
                                    <th class="py-2">Name</th>
                                    <th>Edition</th>
                                    <th>Testimony</th>
                                    <th>Media</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse ($memories as $row)
                                @php
                                    $photos = json_decode((string) ($row['photo_paths'] ?? '[]'), true);
                                    $videos = json_decode((string) ($row['video_paths'] ?? '[]'), true);
                                    $photoCount = is_array($photos) ? count(array_filter($photos)) : 0;
                                    $videoCount = is_array($videos) ? count(array_filter($videos)) : 0;
                                @endphp
                                <tr class="border-b align-top">
                                    <td class="py-3">
                                        <div class="font-medium">{{ $row['full_name'] ?? '—' }}</div>
                                        <div class="text-xs text-gray-500">{{ $row['email'] ?? '' }}</div>
                                    </td>
                                    <td class="py-3">{{ $row['edition'] ?: '—' }}</td>
                                    <td class="py-3 max-w-md">{{ \Illuminate\Support\Str::limit($row['testimony_text'] ?? '', 160) }}</td>
                                    <td class="py-3 whitespace-nowrap">{{ $photoCount }} photo(s) · {{ $videoCount }} video(s)</td>
                                    <td class="py-3 capitalize">{{ $row['status'] ?? 'pending' }}</td>
                                    <td class="py-3">
                                        @if ($canManage)
                                            <form method="POST" action="{{ route('sdtg.gallery.memories.update', $row['id']) }}" class="inline-flex gap-2 items-center">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="return_tab" value="{{ $tab }}">
                                                <select name="status" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                                                    @foreach (['pending', 'featured', 'rejected'] as $status)
                                                        <option value="{{ $status }}" @selected(($row['status'] ?? '') === $status)>{{ $status }}</option>
                                                    @endforeach
                                                </select>
                                                <button class="text-indigo-600 text-sm font-semibold">Update</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-gray-500">
                                        {{ $tab === 'share' ? 'No pending share-form submissions.' : 'No memory submissions yet.' }}
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if ($tab === 'social')
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        The <strong>SDTG Social Wall</strong> builds cards from your church social URLs and uses published gallery photos as preview thumbnails.
                    </p>
                    <ul class="text-sm list-disc list-inside text-gray-600 dark:text-gray-300 space-y-1">
                        <li>Facebook, Instagram, YouTube, and X links come from <a class="text-indigo-600" href="{{ route('settings.index') }}">Platform Settings → Church</a>.</li>
                        <li>Card preview images come from published photos in <a class="text-indigo-600" href="{{ route('sdtg.gallery.index', ['tab' => 'photos']) }}">Photo Archive</a>.</li>
                    </ul>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('settings.index') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Edit social links</a>
                        <a href="{{ route('sdtg.gallery.index', ['tab' => 'photos']) }}" class="px-4 py-2 border rounded-md text-sm font-semibold">Manage photo previews</a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
