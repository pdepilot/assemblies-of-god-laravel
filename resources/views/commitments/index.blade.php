<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Commitment Programs</h2>
            @if ($canManage)
                <a href="{{ route('commitments.create') }}" class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-indigo-600 text-white hover:bg-indigo-700">New program</a>
            @endif
        </div>
    </x-slot>
    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <form method="GET" class="flex flex-wrap gap-3 items-end mb-6">
                    <div><label class="block text-sm">Status</label>
                        <select name="status" class="mt-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            <option value="">All</option>@foreach($statuses as $st)<option value="{{ $st }}" @selected($status===$st)>{{ ucfirst($st) }}</option>@endforeach
                        </select></div>
                    <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Apply</button>
                </form>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700"><tr>
                        <th class="px-3 py-2 text-left text-xs">Program</th><th class="px-3 py-2 text-left text-xs">Target</th>
                        <th class="px-3 py-2 text-left text-xs">Paid</th><th class="px-3 py-2 text-left text-xs">Givers</th>
                        <th class="px-3 py-2 text-left text-xs">Status</th><th class="px-3 py-2 text-left text-xs">Actions</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($programs as $program)
                            <tr>
                                <td class="px-3 py-2 text-sm">{{ $program['name'] }}</td>
                                <td class="px-3 py-2 text-sm">₦{{ number_format($program['target_amount'], 2) }}</td>
                                <td class="px-3 py-2 text-sm">₦{{ number_format($program['paid_total'], 2) }} ({{ $program['progress_pct'] }}%)</td>
                                <td class="px-3 py-2 text-sm">{{ $program['giver_count'] }}</td>
                                <td class="px-3 py-2 text-sm">{{ ucfirst($program['status']) }}</td>
                                <td class="px-3 py-2 text-sm"><a href="{{ route('commitments.show', $program['id']) }}" class="text-indigo-600 hover:underline">View</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-6 text-center text-sm text-gray-500">No commitment programs found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
