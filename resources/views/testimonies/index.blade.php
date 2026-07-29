<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Site Testimonies</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Review testimonies submitted from AGC Ikenebgu public pages.</p>
            </div>
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

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ([
                    ['label' => 'Total', 'value' => $stats['total']],
                    ['label' => 'Pending', 'value' => $stats['pending']],
                    ['label' => 'Approved', 'value' => $stats['approved']],
                    ['label' => 'Rejected', 'value' => $stats['rejected']],
                    ['label' => 'Featured', 'value' => $stats['featured']],
                ] as $stat)
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                        <p class="text-sm text-gray-500">{{ $stat['label'] }}</p>
                        <p class="text-2xl font-semibold mt-1">{{ $stat['value'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'featured' => 'Featured'] as $key => $label)
                    <a href="{{ route('testimonies.index', array_filter(['status' => $key, 'q' => $query, 'source' => $sourcePage])) }}"
                       class="px-3 py-1 rounded border text-sm {{ $status === $key ? 'bg-indigo-600 text-white border-indigo-600' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('testimonies.index') }}" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 grid gap-4 md:grid-cols-3">
                <input type="hidden" name="status" value="{{ $status }}">
                <div>
                    <label for="q" class="block text-sm font-medium mb-1">Search</label>
                    <input id="q" name="q" value="{{ $query }}" placeholder="Name, email, testimony text…" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                </div>
                <div>
                    <label for="source" class="block text-sm font-medium mb-1">Source Page</label>
                    <select id="source" name="source" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        <option value="">All Pages</option>
                        @foreach ($sourcePages as $key => $label)
                            <option value="{{ $key }}" @selected($sourcePage === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Filter</button>
                </div>
            </form>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2">Name</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($result['items'] as $row)
                        <tr class="border-b">
                            <td class="py-2">
                                <p class="font-medium">{{ $row['full_name'] }}</p>
                                <p class="text-gray-500">{{ $row['email'] }}</p>
                            </td>
                            <td>{{ $row['source_label'] }}</td>
                            <td>
                                <span class="capitalize">{{ $row['status'] }}</span>
                                @if ($row['is_featured'])
                                    <span class="text-xs text-amber-600">Featured</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap">{{ $row['created_at'] }}</td>
                            <td>
                                <a href="{{ route('testimonies.show', $row['id']) }}" class="text-indigo-600">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-gray-500">No testimonies found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if (($result['pages'] ?? 1) > 1)
                <div class="flex justify-between text-sm text-gray-500">
                    <span>Page {{ $result['page'] }} of {{ $result['pages'] }}</span>
                    <div class="flex gap-2">
                        @if ($result['page'] > 1)
                            <a href="{{ route('testimonies.index', array_filter(['status' => $status, 'q' => $query, 'source' => $sourcePage, 'page' => $result['page'] - 1])) }}" class="text-indigo-600">Previous</a>
                        @endif
                        @if ($result['page'] < $result['pages'])
                            <a href="{{ route('testimonies.index', array_filter(['status' => $status, 'q' => $query, 'source' => $sourcePage, 'page' => $result['page'] + 1])) }}" class="text-indigo-600">Next</a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
