<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Activity Logs</h2>
                <p class="text-sm text-gray-500 mt-1">Audit trail of administrative actions across the platform.</p>
            </div>
            <a
                href="{{ route('security.activity-logs.export', request()->query()) }}"
                class="px-4 py-2 text-sm rounded-md border font-semibold"
            >
                Export CSV
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">Total entries</div>
                    <div class="text-2xl font-semibold">{{ number_format($stats['total']) }}</div>
                </div>
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">Today</div>
                    <div class="text-2xl font-semibold">{{ number_format($stats['today']) }}</div>
                </div>
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">Warnings / critical</div>
                    <div class="text-2xl font-semibold">{{ number_format($stats['warnings']) }}</div>
                </div>
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">Active admins</div>
                    <div class="text-2xl font-semibold">{{ number_format($stats['admins']) }}</div>
                </div>
            </div>

            <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 items-end bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                <div>
                    <label class="block text-sm">Date from</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                </div>
                <div>
                    <label class="block text-sm">Date to</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                </div>
                <div>
                    <label class="block text-sm">Module</label>
                    <select name="module" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        @foreach ($modules as $module)
                            <option value="{{ $module['id'] }}" @selected($filters['module'] === $module['id'])>{{ $module['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm">User</label>
                    <select name="admin_id" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        <option value="">All Users</option>
                        @foreach ($admins as $adminOption)
                            <option value="{{ $adminOption['id'] }}" @selected((int) $filters['admin_id'] === (int) $adminOption['id'])>
                                {{ $adminOption['full_name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm">Severity</label>
                    <select name="severity" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        <option value="" @selected($filters['severity'] === '')>All Levels</option>
                        <option value="info" @selected($filters['severity'] === 'info')>Info</option>
                        <option value="warning" @selected($filters['severity'] === 'warning')>Warning</option>
                        <option value="critical" @selected($filters['severity'] === 'critical')>Critical</option>
                    </select>
                </div>
                <div class="sm:col-span-2 xl:col-span-1">
                    <label class="block text-sm">Search</label>
                    <input type="search" name="q" value="{{ $filters['q'] }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="Message, action, IP…">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Filter</button>
                    <a href="{{ route('security.activity-logs.index') }}" class="px-4 py-2 rounded-md border text-sm font-semibold">Reset</a>
                </div>
            </form>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2 pr-3">Timestamp</th>
                            <th class="py-2 pr-3">User</th>
                            <th class="py-2 pr-3">Module</th>
                            <th class="py-2 pr-3">Action</th>
                            <th class="py-2 pr-3">Details</th>
                            <th class="py-2">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($result['items'] as $item)
                            <tr class="border-b align-top">
                                <td class="py-3 pr-3 whitespace-nowrap">{{ $item['timestamp_label'] }}</td>
                                <td class="py-3 pr-3">
                                    {{ $item['user'] }}
                                    @if ($item['user_email'] !== '')
                                        <div class="text-xs text-gray-500">{{ $item['user_email'] }}</div>
                                    @endif
                                </td>
                                <td class="py-3 pr-3">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                        {{ $item['module_label'] }}
                                    </span>
                                </td>
                                <td class="py-3 pr-3">
                                    {{ $item['action'] }}
                                    <div class="text-xs text-gray-500 capitalize">{{ $item['severity'] }}</div>
                                </td>
                                <td class="py-3 pr-3 max-w-md">{{ $item['details'] }}</td>
                                <td class="py-3 font-mono text-xs">{{ $item['ip'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-gray-500">No activity logs found for the selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-gray-500">
                    <span>
                        Showing page {{ $result['page'] }} of {{ $result['pages'] }}
                        ({{ number_format($result['total']) }} entries)
                    </span>
                    <div class="flex gap-2">
                        @if ($result['page'] > 1)
                            <a
                                href="{{ route('security.activity-logs.index', array_merge(request()->query(), ['page' => $result['page'] - 1])) }}"
                                class="px-3 py-1 rounded border"
                            >Previous</a>
                        @endif
                        @if ($result['page'] < $result['pages'])
                            <a
                                href="{{ route('security.activity-logs.index', array_merge(request()->query(), ['page' => $result['page'] + 1])) }}"
                                class="px-3 py-1 rounded border"
                            >Next</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
