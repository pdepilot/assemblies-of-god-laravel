<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Ministry Age Transfers</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Children (age 13+) move to Teens; Teens (age 20+) move to Youth. Runs daily and on save.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('analytics.reports.index') }}" class="px-4 py-2 rounded-md border text-sm font-semibold">Reports Hub</a>
                <a href="{{ route('ministries.settings.index') }}" class="px-4 py-2 rounded-md border text-sm font-semibold">Ministry Settings</a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Children → Teens due</div>
                    <div class="text-3xl font-semibold mt-1">{{ $preview['counts']['children_to_teens'] ?? 0 }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Teens → Youth due</div>
                    <div class="text-3xl font-semibold mt-1">{{ $preview['counts']['teens_to_youths'] ?? 0 }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Missing date of birth</div>
                    <div class="text-3xl font-semibold mt-1">{{ $preview['counts']['missing_dob'] ?? 0 }}</div>
                </div>
            </div>

            @if ($canManage)
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('ministries.age-transfers.run') }}">
                        @csrf
                        <input type="hidden" name="dry_run" value="1">
                        <button type="submit" class="px-4 py-2 rounded-md border text-sm font-semibold">Preview only</button>
                    </form>
                    <form method="POST" action="{{ route('ministries.age-transfers.run') }}"
                          data-confirm="Run age transfers now? Eligible children and teens will be moved permanently."
                          data-confirm-title="Run age transfers"
                          data-confirm-ok="Transfer now"
                          data-confirm-tone="danger">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Run age transfers now</button>
                    </form>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <h3 class="text-base font-semibold mb-3">Due for transfer</h3>
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2 pr-3">Name</th>
                            <th class="pr-3">From</th>
                            <th class="pr-3">To</th>
                            <th class="pr-3">Age</th>
                            <th>DOB</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($preview['due'] as $row)
                        <tr class="border-b border-gray-100 dark:border-gray-700">
                            <td class="py-2 pr-3">{{ $row['full_name'] }}</td>
                            <td class="pr-3">{{ $row['from_key'] }}</td>
                            <td class="pr-3">{{ $row['to_key'] }}</td>
                            <td class="pr-3">{{ $row['age'] }}</td>
                            <td>{{ $row['date_of_birth'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-gray-500">No one is due for transfer right now.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if (!empty($preview['missing_dob']))
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                    <h3 class="text-base font-semibold mb-3">Missing date of birth (skipped)</h3>
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left border-b">
                                <th class="py-2 pr-3">Name</th>
                                <th>Ministry</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($preview['missing_dob'] as $row)
                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                <td class="py-2 pr-3">{{ $row['full_name'] }}</td>
                                <td>{{ $row['from_key'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <h3 class="text-base font-semibold mb-3">Recent transfers</h3>
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2 pr-3">When</th>
                            <th class="pr-3">Name</th>
                            <th class="pr-3">From → To</th>
                            <th class="pr-3">Age</th>
                            <th>Source</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($recent as $row)
                        <tr class="border-b border-gray-100 dark:border-gray-700">
                            <td class="py-2 pr-3 whitespace-nowrap">{{ $row['transferred_at'] ?? '' }}</td>
                            <td class="pr-3">{{ $row['full_name'] }}</td>
                            <td class="pr-3">{{ $row['from_key'] }} → {{ $row['to_key'] }}</td>
                            <td class="pr-3">{{ $row['age_at_transfer'] }}</td>
                            <td>{{ $row['source'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-gray-500">No transfers recorded yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
