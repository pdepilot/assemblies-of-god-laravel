<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Donations</h2>
            @if ($canManage)
                <a href="{{ route('donations.create') }}" class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-indigo-600 text-white hover:bg-indigo-700">Record donation</a>
            @endif
        </div>
    </x-slot>
    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">This month</div><div class="text-2xl font-semibold">₦{{ number_format($stats['total_month'], 2) }}</div></div>
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Transactions</div><div class="text-2xl font-semibold">{{ $stats['transactions'] }}</div></div>
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Manual</div><div class="text-2xl font-semibold">₦{{ number_format($stats['manual_month'], 2) }}</div></div>
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Trend</div><div class="text-2xl font-semibold">{{ $stats['trend'] }}%</div></div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <form method="GET" class="flex flex-wrap gap-3 items-end mb-6">
                    <div><label class="block text-sm">Scope</label>
                        <select name="scope" class="mt-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            @foreach($scopes as $sc)<option value="{{ $sc }}" @selected($scope===$sc)>{{ ucfirst($sc) }}</option>@endforeach
                        </select></div>
                    <div><label class="block text-sm">Category</label>
                        <select name="category" class="mt-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            <option value="">All</option>@foreach($categories as $cat)<option value="{{ $cat['slug'] }}" @selected($category===$cat['slug'])>{{ $cat['name'] }}</option>@endforeach
                        </select></div>
                    <div><label class="block text-sm">Search</label><input type="text" name="q" value="{{ $query }}" class="mt-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="Donor or code"></div>
                    <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Apply</button>
                </form>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700"><tr>
                        <th class="px-3 py-2 text-left text-xs">Code</th><th class="px-3 py-2 text-left text-xs">Donor</th>
                        <th class="px-3 py-2 text-left text-xs">Amount</th><th class="px-3 py-2 text-left text-xs">Category</th>
                        <th class="px-3 py-2 text-left text-xs">Date</th><th class="px-3 py-2 text-left text-xs">Method</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($items as $item)
                            <tr>
                                <td class="px-3 py-2 text-sm font-mono">{{ $item['donation_code'] }}</td>
                                <td class="px-3 py-2 text-sm">{{ $item['is_anonymous'] ? 'Anonymous' : $item['donor_name'] }}</td>
                                <td class="px-3 py-2 text-sm">₦{{ number_format($item['amount'], 2) }}</td>
                                <td class="px-3 py-2 text-sm">{{ $item['category_label'] }}</td>
                                <td class="px-3 py-2 text-sm">{{ $item['donation_date'] }}</td>
                                <td class="px-3 py-2 text-sm">{{ ucfirst((string) $item['payment_method']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-6 text-center text-sm text-gray-500">No donations found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
