<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Church Visitors</h2>
            @if ($canManage)
                <a href="{{ route('visitors.create') }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                    Register visitor
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">Total</div><div class="text-2xl font-semibold">{{ $stats['total'] }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">New</div><div class="text-2xl font-semibold">{{ $stats['new'] }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">In pipeline</div><div class="text-2xl font-semibold">{{ $stats['in_pipeline'] }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">This month</div><div class="text-2xl font-semibold">{{ $stats['this_month'] }}</div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <form method="GET" class="flex flex-wrap gap-3 items-end mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                        <input name="q" value="{{ $query }}" placeholder="Name, code, phone..."
                               class="mt-1 block w-48 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Follow-up</label>
                        <select name="follow_up" class="mt-1 block w-48 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            <option value="">All</option>
                            @foreach ($followUpStatuses as $st)
                                <option value="{{ $st }}" @selected($followUp === $st)>{{ $followUpLabels[$st] ?? $st }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Apply</button>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Code</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Name</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Phone</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">First visit</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Status</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($items as $item)
                                <tr>
                                    <td class="px-3 py-2 text-sm font-mono">{{ $item['visitor_code'] }}</td>
                                    <td class="px-3 py-2 text-sm">{{ $item['full_name'] }}</td>
                                    <td class="px-3 py-2 text-sm">{{ $item['phone'] }}</td>
                                    <td class="px-3 py-2 text-sm">{{ $item['first_visit_date'] }}</td>
                                    <td class="px-3 py-2 text-sm">{{ $followUpLabels[$item['follow_up_status']] ?? $item['follow_up_status'] }}</td>
                                    <td class="px-3 py-2 text-sm">
                                        <a href="{{ route('visitors.show', $item['id']) }}" class="text-indigo-600 hover:underline">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-3 py-6 text-center text-sm text-gray-500">No visitors found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
