<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">ERP Income</h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('financial-erp.income.categories.index') }}" class="px-4 py-2 text-sm font-semibold rounded-md border border-gray-300 dark:border-gray-600">Categories</a>
                @if ($canManage)
                    <a href="{{ route('financial-erp.income.create') }}" class="px-4 py-2 text-sm font-semibold rounded-md bg-indigo-600 text-white">Record income</a>
                @endif
            </div>
        </div>
    </x-slot>
    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <form method="GET" class="mb-4 flex gap-3">
                    <select name="category" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        <option value="">All categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat['code'] }}" @selected($category === $cat['code'])>{{ $cat['name'] }}</option>
                        @endforeach
                    </select>
                    <button class="px-3 py-2 rounded-md bg-indigo-600 text-white text-sm">Filter</button>
                </form>
                <p class="text-sm text-gray-500 mb-4">Total: ₦{{ number_format($totalAmount, 2) }}</p>
                <table class="min-w-full divide-y">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 text-left text-xs">Receipt</th>
                            <th class="px-3 py-2 text-left text-xs">Date</th>
                            <th class="px-3 py-2 text-left text-xs">Category</th>
                            <th class="px-3 py-2 text-left text-xs">Amount</th>
                            <th class="px-3 py-2 text-left text-xs">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td class="px-3 py-2 text-sm font-mono">{{ $item['receipt_no'] }}</td>
                                <td class="px-3 py-2 text-sm">{{ $item['income_date'] }}</td>
                                <td class="px-3 py-2 text-sm">{{ $item['category_name'] }}</td>
                                <td class="px-3 py-2 text-sm">₦{{ number_format($item['amount'], 2) }}</td>
                                <td class="px-3 py-2 text-sm">
                                    <a href="{{ route('financial-erp.income.show', $item['id']) }}" class="text-indigo-600">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-sm text-gray-500">No income records.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
