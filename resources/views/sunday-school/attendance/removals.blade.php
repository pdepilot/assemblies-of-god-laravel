<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Sunday School — Correction Audit
            </h2>
            <a href="{{ route('ss.attendance.index') }}"
               class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                Back to register
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                Records removed from the attendance register by teachers or admins, including the reason given at the time of removal.
            </p>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                        Total removals: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $total }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Date</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Student</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Class</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Status</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Offering</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">MV</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Reason</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Removed by</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Removed at</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                @forelse($items as $item)
                                    <tr>
                                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $item['attendance_date'] }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-gray-100">
                                            {{ $item['student_name'] }}
                                            @if (! empty($item['student_code']))
                                                <span class="block text-xs font-mono text-gray-500">{{ $item['student_code'] }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $item['class_name'] }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $item['status'] ?? '—' }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ number_format((float) $item['offering_amount'], 2) }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ (int) $item['memory_verse_passed'] === 1 ? 'Yes' : 'No' }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300 max-w-xs">{{ $item['reason'] }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $item['removed_by_name'] ?? '—' }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $item['removed_at'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                            No correction records yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 flex items-center justify-between">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Page {{ $page }} of {{ $totalPages }}
                        </div>
                        <div class="flex gap-2">
                            @if ($page > 1)
                                <a href="{{ route('ss.attendance.removals', ['page' => $page - 1, 'per_page' => $perPage]) }}"
                                   class="px-3 py-2 rounded-md bg-gray-100 dark:bg-gray-700 text-sm hover:bg-gray-200 dark:hover:bg-gray-600">Previous</a>
                            @endif
                            @if ($page < $totalPages)
                                <a href="{{ route('ss.attendance.removals', ['page' => $page + 1, 'per_page' => $perPage]) }}"
                                   class="px-3 py-2 rounded-md bg-gray-100 dark:bg-gray-700 text-sm hover:bg-gray-200 dark:hover:bg-gray-600">Next</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
