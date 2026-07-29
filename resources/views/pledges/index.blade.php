<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Pledges</h2>
            @if ($canManage)
                <a href="{{ route('pledges.create') }}" class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-indigo-600 text-white hover:bg-indigo-700">New pledge</a>
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
                    <div><label class="block text-sm">Search</label><input type="text" name="q" value="{{ $query }}" class="mt-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"></div>
                    <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Apply</button>
                </form>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700"><tr>
                        <th class="px-3 py-2 text-left text-xs">Donor</th><th class="px-3 py-2 text-left text-xs">Pledged</th>
                        <th class="px-3 py-2 text-left text-xs">Paid</th><th class="px-3 py-2 text-left text-xs">Remaining</th>
                        <th class="px-3 py-2 text-left text-xs">Status</th><th class="px-3 py-2 text-left text-xs">Actions</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($items as $item)
                            <tr>
                                <td class="px-3 py-2 text-sm">{{ $item['donor_name'] }}</td>
                                <td class="px-3 py-2 text-sm">₦{{ number_format($item['pledged_amount'], 2) }}</td>
                                <td class="px-3 py-2 text-sm">₦{{ number_format($item['amount_paid'], 2) }}</td>
                                <td class="px-3 py-2 text-sm">₦{{ number_format($item['remaining_balance'], 2) }}</td>
                                <td class="px-3 py-2 text-sm">{{ ucfirst($item['status']) }}</td>
                                <td class="px-3 py-2 text-sm"><a href="{{ route('pledges.show', $item['id']) }}" class="text-indigo-600 hover:underline">View</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-6 text-center text-sm text-gray-500">No pledges found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
