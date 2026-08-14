<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Reports Hub</h2></x-slot>
    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">{{ $errors->first() }}</div>
            @endif
            @include('analytics._nav')
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    'Generated YTD' => $stats['generated_ytd'],
                    'Generated this month' => $stats['generated_month'],
                    'Total downloads' => $stats['total_downloads'],
                    'Scheduled' => $stats['scheduled_reports'],
                ] as $label => $value)
                    <div class="rounded-lg border p-4 bg-white dark:bg-gray-800">
                        <div class="text-sm text-gray-500">{{ $label }}</div>
                        <div class="text-2xl font-semibold">{{ $value }}</div>
                    </div>
                @endforeach
            </div>
            @if ($canExport ?? false)
                <form method="POST" action="{{ route('analytics.reports.generate') }}" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 flex flex-wrap gap-3 items-end">
                    @csrf
                    <div class="min-w-[16rem] flex-1">
                        <label class="block text-sm text-gray-600 dark:text-gray-400">Report type (Church Management System)</label>
                        <select name="type" class="w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            @foreach (($catalog ?? []) as $group)
                                <optgroup label="{{ $group['group'] }}">
                                    @foreach ($group['options'] as $option)
                                        <option value="{{ $option['value'] }}" @selected(old('type') === $option['value'])>{{ $option['label'] }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 dark:text-gray-400">Period</label>
                        <select name="period" class="rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            <option value="ytd" @selected(old('period', 'ytd') === 'ytd')>Year to date</option>
                            <option value="current_month" @selected(old('period') === 'current_month')>Current month</option>
                            <option value="current_quarter" @selected(old('period') === 'current_quarter')>Current quarter</option>
                            <option value="last_quarter" @selected(old('period') === 'last_quarter')>Last quarter</option>
                            <option value="next_30_days" @selected(old('period') === 'next_30_days')>Next 30 days</option>
                            <option value="all_time" @selected(old('period') === 'all_time')>All time</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 dark:text-gray-400">Format</label>
                        <select name="format" class="rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            <option value="csv" @selected(old('format', 'csv') === 'csv')>CSV</option>
                            <option value="pdf" @selected(old('format') === 'pdf')>PDF</option>
                            <option value="docx" @selected(old('format') === 'docx')>Microsoft Word (.docx)</option>
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm">Generate report</button>
                </form>
                <p class="text-sm text-gray-500 dark:text-gray-400 -mt-2">
                    Choose any church module report above (members, ministries, giving, communications, ERP, and more), then generate CSV, PDF, or Word.
                </p>
            @endif
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold mb-4">Recent reports</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left border-b">
                                <th class="py-2">Title</th>
                                <th>Type</th>
                                <th>Format</th>
                                <th>Period</th>
                                <th>Rows</th>
                                <th>Generated</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($recent as $row)
                            <tr class="border-b">
                                <td class="py-2">{{ $row['title'] }}</td>
                                <td>{{ $row['report_type'] }}</td>
                                <td class="uppercase">{{ $row['format'] }}</td>
                                <td>{{ $row['period_label'] }}</td>
                                <td>{{ $row['row_count'] }}</td>
                                <td>{{ $row['created_at'] }}</td>
                                <td class="whitespace-nowrap space-x-3">
                                    <a href="{{ route('analytics.reports.download', $row['id']) }}" class="text-indigo-600">Download</a>
                                    @if ($canExport ?? false)
                                        <form method="POST" action="{{ route('analytics.reports.destroy', $row['id']) }}" class="inline" data-confirm="Delete this report permanently?" data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-4 text-gray-500">No reports generated yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
