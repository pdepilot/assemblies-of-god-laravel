<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Church Events</h2>
            @if ($canManage)
                <a href="{{ route('events.create') }}" class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-indigo-600 text-white hover:bg-indigo-700">Create event</a>
            @endif
        </div>
    </x-slot>
    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Upcoming</div><div class="text-2xl font-semibold">{{ $stats['upcoming'] }}</div></div>
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Published</div><div class="text-2xl font-semibold">{{ $stats['published'] }}</div></div>
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">This week</div><div class="text-2xl font-semibold">{{ $stats['this_week'] }}</div></div>
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Total</div><div class="text-2xl font-semibold">{{ $stats['total'] }}</div></div>
            </div>

            @if (count($featuredEvents) > 0)
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 mb-3">Featured upcoming events</h3>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($featuredEvents as $featured)
                            <article class="rounded-lg border overflow-hidden bg-white dark:bg-gray-800">
                                <img src="{{ $featured['image_url'] }}" alt="{{ $featured['title'] }}" class="h-40 w-full object-cover bg-gray-100 dark:bg-gray-900">
                                <div class="p-4 space-y-2">
                                    <h4 class="font-semibold text-sm">{{ $featured['title'] }}</h4>
                                    <p class="text-xs text-gray-500">{{ $featured['event_date'] }} · {{ $featured['category_label'] }}</p>
                                    <div class="flex gap-3 text-sm">
                                        <a href="{{ route('events.show', $featured['id']) }}" class="text-indigo-600 hover:underline">View</a>
                                        @if ($canManage)
                                            <a href="{{ route('events.edit', $featured['id']) }}" class="text-indigo-600 hover:underline">Edit</a>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <form method="GET" class="flex flex-wrap gap-3 items-end mb-6">
                    <div><label class="block text-sm">Category</label>
                        <select name="category" class="mt-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            <option value="">All</option>@foreach($categories as $cat)<option value="{{ $cat }}" @selected($category===$cat)>{{ $categoryLabels[$cat] ?? $cat }}</option>@endforeach
                        </select></div>
                    <div><label class="block text-sm">Status</label>
                        <select name="status" class="mt-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            <option value="">All</option>@foreach($statuses as $st)<option value="{{ $st }}" @selected($status===$st)>{{ ucfirst($st) }}</option>@endforeach
                        </select></div>
                    <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Apply</button>
                </form>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700"><tr>
                        <th class="px-3 py-2 text-left text-xs">Image</th>
                        <th class="px-3 py-2 text-left text-xs">Code</th><th class="px-3 py-2 text-left text-xs">Title</th>
                        <th class="px-3 py-2 text-left text-xs">Date</th><th class="px-3 py-2 text-left text-xs">Category</th>
                        <th class="px-3 py-2 text-left text-xs">Status</th><th class="px-3 py-2 text-left text-xs">Actions</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($items as $item)
                            <tr>
                                <td class="px-3 py-2">
                                    <img src="{{ $item['image_url'] }}" alt="" class="h-12 w-16 rounded object-cover bg-gray-100 dark:bg-gray-900">
                                </td>
                                <td class="px-3 py-2 text-sm font-mono">{{ $item['event_code'] }}</td>
                                <td class="px-3 py-2 text-sm">{{ $item['title'] }}</td>
                                <td class="px-3 py-2 text-sm">{{ $item['event_date'] }}</td>
                                <td class="px-3 py-2 text-sm">{{ $item['category_label'] }}</td>
                                <td class="px-3 py-2 text-sm">{{ ucfirst($item['status']) }} @if(!$item['is_published'])<span class="text-xs text-amber-600">(paused)</span>@endif</td>
                                <td class="px-3 py-2 text-sm space-x-2">
                                    <a href="{{ route('events.show', $item['id']) }}" class="text-indigo-600 hover:underline">View</a>
                                    @if ($canManage)
                                        <a href="{{ route('events.edit', $item['id']) }}" class="text-indigo-600 hover:underline">Edit</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-3 py-6 text-center text-sm text-gray-500">No events found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
