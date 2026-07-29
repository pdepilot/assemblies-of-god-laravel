<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Members Directory</h2>
            @if ($canManage)
                <a href="{{ route('members.create') }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                    Register member
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
                    <div class="text-sm text-gray-500">Active</div><div class="text-2xl font-semibold">{{ $stats['active'] }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">New this month</div><div class="text-2xl font-semibold">{{ $stats['new_this_month'] }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">Visitors</div><div class="text-2xl font-semibold">{{ $stats['visitors'] }}</div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">By ministry</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Church members grouped by their assigned ministry department.</p>
                    </div>
                    <div class="rounded-lg border border-indigo-200 dark:border-indigo-700 bg-indigo-50 dark:bg-indigo-900/30 px-4 py-2 text-right">
                        <div class="text-xs uppercase tracking-wide text-indigo-600 dark:text-indigo-300">Grand total</div>
                        <div class="text-2xl font-semibold text-indigo-700 dark:text-indigo-200">{{ number_format($ministryGrandTotal) }}</div>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                    @forelse ($ministryTotals as $ministry)
                        <a href="{{ route('members.index', ['department' => $ministry['department'] ?? $ministry['name']]) }}"
                           class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-900/40 hover:border-indigo-400 dark:hover:border-indigo-500 transition">
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $ministry['name'] }}</div>
                            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ number_format($ministry['total']) }}</div>
                        </a>
                    @empty
                        <p class="sm:col-span-2 md:col-span-3 lg:col-span-4 text-sm text-gray-500">No enabled ministries found.</p>
                    @endforelse
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
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Department</label>
                        <select name="department" class="mt-1 block w-48 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            <option value="">All</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept }}" @selected($department === $dept)>{{ $dept }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select name="status" class="mt-1 block w-40 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            <option value="">All</option>
                            @foreach ($statuses as $st)
                                <option value="{{ $st }}" @selected($status === $st)>{{ $statusLabels[$st] ?? $st }}</option>
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
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Department</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Status</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($items as $item)
                                <tr>
                                    <td class="px-3 py-2 text-sm font-mono">{{ $item['member_code'] }}</td>
                                    <td class="px-3 py-2 text-sm">
                                        <span class="member-profile-thumb" aria-hidden="true">
                                            @if (! empty($item['photo_url']))
                                                <img src="{{ $item['photo_url'] }}" alt="">
                                            @else
                                                {{ strtoupper(\Illuminate\Support\Str::substr($item['full_name'] ?? 'M', 0, 1)) }}
                                            @endif
                                        </span>
                                        {{ $item['full_name'] }}
                                    </td>
                                    <td class="px-3 py-2 text-sm">{{ $item['phone'] }}</td>
                                    <td class="px-3 py-2 text-sm">{{ $item['department'] }}</td>
                                    <td class="px-3 py-2 text-sm">{{ $statusLabels[$item['status']] ?? $item['status'] }}</td>
                                    <td class="px-3 py-2 text-sm flex gap-2">
                                        <a href="{{ route('members.show', $item['id']) }}" class="text-indigo-600 hover:underline">View</a>
                                        @if ($canManage)
                                            <a href="{{ route('members.edit', $item['id']) }}" class="text-indigo-600 hover:underline">Edit</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-3 py-6 text-center text-sm text-gray-500">No members found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex justify-between text-sm text-gray-600 dark:text-gray-400">
                    <span>Page {{ $page }} of {{ $totalPages }} ({{ $total }} total)</span>
                    <div class="flex gap-2">
                        @php $qs = array_filter(['q' => $query, 'department' => $department, 'status' => $status, 'per_page' => $perPage]); @endphp
                        @if ($page > 1)
                            <a href="{{ route('members.index', $qs + ['page' => $page - 1]) }}" class="px-3 py-2 rounded-md bg-gray-100 dark:bg-gray-700">Previous</a>
                        @endif
                        @if ($page < $totalPages)
                            <a href="{{ route('members.index', $qs + ['page' => $page + 1]) }}" class="px-3 py-2 rounded-md bg-gray-100 dark:bg-gray-700">Next</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
