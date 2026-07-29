<x-app-layout title="Sunday School">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Sunday School — Awards
            </h2>
            <a href="{{ route('ss.certificates.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                View certificates
            </a>
        </div>
    </x-slot>

    @include('sunday-school._tabs', ['active' => 'awards', 'canIntelligence' => false])

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">
                    {{ session('status') }}
                </div>
            @endif
            @if ($errors->has('certificate'))
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">{{ $errors->first('certificate') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-wrap gap-3 items-end mb-6">
                    <form method="POST" action="{{ route('ss.awards.generate') }}" class="flex flex-wrap gap-3 items-end">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Period</label>
                            <select name="period_type" class="mt-1 block w-40 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                <option value="annual">Annual</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <x-primary-button>Generate recommendations</x-primary-button>
                    </form>

                    <form method="GET" action="{{ route('ss.awards.index') }}" class="flex flex-wrap gap-3 items-end">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status filter</label>
                            <select name="status" class="mt-1 block w-40 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                <option value="">All</option>
                                @foreach (['recommended', 'approved', 'published', 'rejected'] as $st)
                                    <option value="{{ $st }}" @selected($status === $st)>{{ $st }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Filter</button>
                    </form>
                </div>

                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Total: {{ $total }}</p>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Award</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Recipient</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Period</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Score</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Status</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($items as $row)
                                @php $cert = $certificateMap[$row['id']] ?? null; @endphp
                                <tr>
                                    <td class="px-3 py-2 text-sm">{{ $row['award_name'] }}</td>
                                    <td class="px-3 py-2 text-sm">{{ $row['recipient_name'] }} <span class="text-gray-500">({{ $row['recipient_type'] }})</span></td>
                                    <td class="px-3 py-2 text-sm">{{ $row['period_label'] }}</td>
                                    <td class="px-3 py-2 text-sm">{{ number_format((float) $row['score'], 1) }}</td>
                                    <td class="px-3 py-2 text-sm">{{ $row['status'] }}</td>
                                    <td class="px-3 py-2 text-sm">
                                        <div class="flex flex-wrap gap-2">
                                            @if ($row['status'] === 'recommended')
                                                <form method="POST" action="{{ route('ss.awards.approve', $row['id']) }}">
                                                    @csrf
                                                    <button type="submit" class="text-green-600 text-xs hover:underline">Approve</button>
                                                </form>
                                                <form method="POST" action="{{ route('ss.awards.reject', $row['id']) }}">
                                                    @csrf
                                                    <button type="submit" class="text-red-600 text-xs hover:underline">Reject</button>
                                                </form>
                                            @endif
                                            @if (in_array($row['status'], ['recommended', 'approved'], true))
                                                <form method="POST" action="{{ route('ss.awards.publish', $row['id']) }}">
                                                    @csrf
                                                    <button type="submit" class="text-indigo-600 text-xs hover:underline">Publish</button>
                                                </form>
                                            @endif
                                            @if (in_array($row['status'], ['approved', 'published'], true))
                                                @if ($cert)
                                                    <a href="{{ $cert['preview_url'] }}" target="_blank" rel="noopener" class="text-indigo-600 text-xs hover:underline">View certificate</a>
                                                @else
                                                    <form method="POST" action="{{ route('ss.certificates.generate-from-award', $row['id']) }}">
                                                        @csrf
                                                        <button type="submit" class="text-indigo-600 text-xs hover:underline">Generate certificate</button>
                                                    </form>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-6 text-center text-sm text-gray-500">No awards yet. Generate recommendations to begin.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
