<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">SDTG Media Library</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Crusade videos, promotional images, audio tracks, and social assets.</p>
            </div>
            @if ($canManage)
                <a href="{{ route('sdtg.media-library.assets.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">
                    <i class="fas fa-cloud-upload-alt"></i> Upload Media
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">{{ $errors->first() }}</div>
            @endif

            @include('sdtg._nav', ['canManage' => $canManage])

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['label' => 'Total assets', 'value' => $stats['total']],
                    ['label' => 'Images', 'value' => $stats['images']],
                    ['label' => 'Videos', 'value' => $stats['videos']],
                    ['label' => 'Published', 'value' => $stats['published']],
                ] as $card)
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">{{ $card['label'] }}</p>
                        <p class="text-2xl font-semibold mt-1">{{ number_format($card['value']) }}</p>
                    </div>
                @endforeach
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="font-semibold">Folders</h3>
                </div>
                <div class="flex flex-wrap gap-2">
                    @forelse ($folders as $folder)
                        <div class="inline-flex items-center gap-2 px-3 py-2 rounded border text-sm">
                            <a href="{{ route('sdtg.media-library.index', ['folder_id' => $folder['id']]) }}" class="hover:text-indigo-600">
                                <i class="fas fa-folder text-amber-500"></i> {{ $folder['name'] }}
                                <span class="text-xs text-gray-500">({{ $folder['asset_count'] }})</span>
                            </a>
                            @if ($canManage)
                                <form method="POST" action="{{ route('sdtg.media-library.folders.destroy', $folder['id']) }}" data-confirm="Delete this folder? Assets will be unassigned." data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 text-xs" title="Delete folder"><i class="fas fa-times"></i></button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No folders yet.</p>
                    @endforelse
                </div>
                @if ($canManage)
                    <form method="POST" action="{{ route('sdtg.media-library.folders.store') }}" class="flex flex-wrap gap-2 items-end border-t pt-4">
                        @csrf
                        <div class="flex-1 min-w-[160px]">
                            <label class="block text-xs font-medium">New folder</label>
                            <input type="text" name="name" required class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm" placeholder="Folder name">
                        </div>
                        <div class="min-w-[200px]">
                            <label class="block text-xs font-medium">Description</label>
                            <input type="text" name="description" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                        </div>
                        <button class="px-3 py-2 rounded border text-sm">Add folder</button>
                    </form>
                @endif
            </div>

            <form method="GET" action="{{ route('sdtg.media-library.index') }}" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
                <div class="lg:col-span-2">
                    <label class="block text-xs font-medium mb-1">Search</label>
                    <input type="search" name="q" value="{{ $filters['q'] }}" class="w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm" placeholder="Title, tags…">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Type</label>
                    <select name="media_type" class="w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                        <option value="">All</option>
                        @foreach ($mediaTypes as $type)
                            <option value="{{ $type }}" @selected($filters['media_type'] === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Folder</label>
                    <select name="folder_id" class="w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                        <option value="">All</option>
                        @foreach ($folders as $folder)
                            <option value="{{ $folder['id'] }}" @selected((int) $filters['folder_id'] === (int) $folder['id'])>{{ $folder['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Year</label>
                    <select name="year" class="w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                        <option value="">All</option>
                        @foreach ($years as $year)
                            <option value="{{ $year }}" @selected((int) $filters['year'] === (int) $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Status</label>
                    <select name="status" class="w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                        <option value="">All</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-6">
                    <button class="px-3 py-2 rounded border text-sm">Apply filters</button>
                </div>
            </form>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @forelse ($result['items'] as $asset)
                    <article class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden border border-gray-100 dark:border-gray-700">
                        <div class="h-36 bg-gray-100 dark:bg-gray-900 flex items-center justify-center overflow-hidden">
                            @if (! empty($asset['thumbnail_url']) && ($asset['media_type'] ?? '') === 'image')
                                <img src="{{ $asset['thumbnail_url'] }}" alt="{{ $asset['title'] }}" class="w-full h-full object-cover">
                            @elseif (! empty($asset['thumbnail_url']))
                                <img src="{{ $asset['thumbnail_url'] }}" alt="{{ $asset['title'] }}" class="w-full h-full object-cover">
                            @else
                                <i class="fas {{ ($asset['media_type'] ?? '') === 'video' ? 'fa-film' : (($asset['media_type'] ?? '') === 'audio' ? 'fa-music' : 'fa-image') }} text-3xl text-gray-400"></i>
                            @endif
                        </div>
                        <div class="p-4 space-y-2">
                            <h4 class="font-medium text-sm leading-snug">{{ $asset['title'] }}</h4>
                            <p class="text-xs text-gray-500 capitalize">{{ $asset['media_type'] }} · {{ $asset['status'] }} · {{ $asset['crusade_year'] }}</p>
                            <p class="text-xs text-gray-400">{{ $asset['folder_name'] ?? 'No folder' }} · {{ $asset['file_size_label'] }}</p>
                            @if ($canManage)
                                <div class="flex gap-3 text-sm pt-1">
                                    <a href="{{ route('sdtg.media-library.assets.edit', $asset['id']) }}" class="text-indigo-600">Edit</a>
                                    <form method="POST" action="{{ route('sdtg.media-library.assets.destroy', $asset['id']) }}" data-confirm="Delete this asset?" data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600">Delete</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-gray-500 sm:col-span-2 lg:col-span-3 xl:col-span-4">No media assets match your filters.</p>
                @endforelse
            </div>

            @if (($result['pages'] ?? 1) > 1)
                <div class="flex justify-between text-sm text-gray-500">
                    <span>Page {{ $result['page'] }} of {{ $result['pages'] }} ({{ $result['total'] }} total)</span>
                    <div class="flex gap-2">
                        @if ($result['page'] > 1)
                            <a class="px-3 py-1 rounded border" href="{{ route('sdtg.media-library.index', array_merge($filters, ['page' => $result['page'] - 1])) }}">Previous</a>
                        @endif
                        @if ($result['page'] < $result['pages'])
                            <a class="px-3 py-1 rounded border" href="{{ route('sdtg.media-library.index', array_merge($filters, ['page' => $result['page'] + 1])) }}">Next</a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
