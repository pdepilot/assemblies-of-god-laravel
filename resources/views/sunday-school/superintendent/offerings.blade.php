<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Superintendent — Offering Search
            </h2>
            <a href="{{ route('ss.analytics.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                Back to analytics
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('ss.superintendent.offerings') }}" class="flex flex-wrap gap-3 items-end mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Class name</label>
                        <input type="text" name="q" value="{{ $query }}" placeholder="Search class name"
                               class="mt-1 block w-56 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">From</label>
                        <input type="date" name="from" value="{{ $from }}"
                               class="mt-1 block w-40 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">To</label>
                        <input type="date" name="to" value="{{ $to }}"
                               class="mt-1 block w-40 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Search</button>
                </form>

                @if ($search)
                    <p class="mb-4 text-sm font-semibold text-gray-900 dark:text-gray-100">
                        Grand total: ₦{{ number_format($search['grand_total'], 2) }}
                    </p>

                    <h3 class="text-md font-semibold text-gray-900 dark:text-gray-100 mb-2">By class</h3>
                    <div class="overflow-x-auto mb-8">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Class</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Total</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Offering days</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">First date</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Last date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($search['classes'] as $row)
                                    <tr>
                                        <td class="px-3 py-2 text-sm">{{ $row['class_name'] }}</td>
                                        <td class="px-3 py-2 text-sm">₦{{ number_format((float) $row['total'], 2) }}</td>
                                        <td class="px-3 py-2 text-sm">{{ $row['offering_days'] }}</td>
                                        <td class="px-3 py-2 text-sm">{{ $row['first_date'] ?? '—' }}</td>
                                        <td class="px-3 py-2 text-sm">{{ $row['last_date'] ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-6 text-center text-sm text-gray-500">No classes matched your search.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h3 class="text-md font-semibold text-gray-900 dark:text-gray-100 mb-2">By date (latest 200)</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Date</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Class</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($search['by_date'] as $row)
                                    <tr>
                                        <td class="px-3 py-2 text-sm">{{ $row['offering_date'] }}</td>
                                        <td class="px-3 py-2 text-sm">{{ $row['class_name'] }}</td>
                                        <td class="px-3 py-2 text-sm">₦{{ number_format((float) $row['amount'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-3 py-6 text-center text-sm text-gray-500">No offering records for this range.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500">Enter search criteria and click Search to view offering totals by class.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
