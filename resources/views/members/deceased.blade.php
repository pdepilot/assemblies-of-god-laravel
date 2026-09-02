<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Deceased Dashboard</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Memorial records for members marked as deceased.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('members.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                    Members directory
                </a>
                @if ($canManage)
                    <a href="{{ route('members.deceased.record') }}" class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                        Record from members
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">Total deceased</div>
                    <div class="text-2xl font-semibold">{{ number_format($stats['total'] ?? 0) }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">This year</div>
                    <div class="text-2xl font-semibold">{{ number_format($stats['this_year'] ?? 0) }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">This month</div>
                    <div class="text-2xl font-semibold">{{ number_format($stats['this_month'] ?? 0) }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">With memorial notes</div>
                    <div class="text-2xl font-semibold">{{ number_format($stats['with_memorial_notes'] ?? 0) }}</div>
                </div>
                <div class="rounded-lg border border-amber-200 dark:border-amber-700 p-4 bg-amber-50 dark:bg-amber-900/20">
                    <div class="text-sm text-amber-700 dark:text-amber-300">Missing date of death</div>
                    <div class="text-2xl font-semibold text-amber-800 dark:text-amber-200">{{ number_format($stats['missing_date_of_death'] ?? 0) }}</div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <form method="GET" class="flex flex-wrap gap-3 items-end mb-6">
                    <div class="flex-1 min-w-[220px]">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                        <input name="q" value="{{ $query }}" placeholder="Name, code, department, memorial notes…"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Search</button>
                    @if ($query !== '')
                        <a href="{{ route('members.deceased') }}" class="px-4 py-2 rounded-md border text-sm">Clear</a>
                    @endif
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Code</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Name</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Department</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Date of death</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Memorial notes</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($items as $row)
                                <tr>
                                    <td class="px-3 py-3 font-mono text-xs">{{ $row['member_code'] ?? '—' }}</td>
                                    <td class="px-3 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $row['full_name'] ?? '—' }}</td>
                                    <td class="px-3 py-3">{{ $row['department'] ?: '—' }}</td>
                                    <td class="px-3 py-3 whitespace-nowrap">{{ $row['date_of_death_display'] ?? '—' }}</td>
                                    <td class="px-3 py-3 max-w-md">
                                        @if (! empty($row['memorial_notes']))
                                            <span class="text-gray-700 dark:text-gray-200">{{ \Illuminate\Support\Str::limit($row['memorial_notes'], 120) }}</span>
                                        @else
                                            <span class="text-gray-400">No memorial notes</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        <div class="inline-flex flex-wrap gap-2">
                                            <a href="{{ route('members.show', $row['id']) }}" class="text-indigo-600 hover:underline">Open</a>
                                            <a href="{{ route('members.death-certificate', $row['id']) }}" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">Certificate</a>
                                            <a href="{{ route('members.death-certificate', [$row['id'], 'download' => 1]) }}" class="text-indigo-600 hover:underline">Download</a>
                                            @if ($canManage)
                                                <a href="{{ route('members.edit', $row['id']) }}" class="text-indigo-600 hover:underline">Edit</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-10 text-center text-gray-500">
                                        No deceased members found.
                                        @if ($canManage)
                                            Mark a member as deceased on their profile to include them here.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (($totalPages ?? 1) > 1)
                    <div class="mt-4 flex justify-between text-sm text-gray-500">
                        <span>Page {{ $page }} of {{ $totalPages }} ({{ $total }} total)</span>
                        <div class="flex gap-2">
                            @if ($page > 1)
                                <a class="px-3 py-1 rounded border" href="{{ route('members.deceased', array_filter(['q' => $query, 'page' => $page - 1])) }}">Previous</a>
                            @endif
                            @if ($page < $totalPages)
                                <a class="px-3 py-1 rounded border" href="{{ route('members.deceased', array_filter(['q' => $query, 'page' => $page + 1])) }}">Next</a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
