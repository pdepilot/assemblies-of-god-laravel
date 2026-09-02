<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Income Categories</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Offerings and other income types used when recording ERP income.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('financial-erp.income.index') }}" class="px-4 py-2 text-sm font-semibold rounded-md border border-gray-300 dark:border-gray-600">Back to income</a>
                @if ($canManage)
                    <a href="{{ route('financial-erp.income.categories.create') }}" class="px-4 py-2 text-sm font-semibold rounded-md bg-indigo-600 text-white">Add category</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead>
                        <tr class="text-left">
                            <th class="px-3 py-2 text-xs font-medium text-gray-500">Code</th>
                            <th class="px-3 py-2 text-xs font-medium text-gray-500">Name</th>
                            <th class="px-3 py-2 text-xs font-medium text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($items as $item)
                            <tr>
                                <td class="px-3 py-3 font-mono text-xs">{{ $item['code'] }}</td>
                                <td class="px-3 py-3 font-medium">{{ $item['name'] }}</td>
                                <td class="px-3 py-3">
                                    @if (! empty($item['is_active']))
                                        <span class="text-green-700 dark:text-green-300">Active</span>
                                    @else
                                        <span class="text-gray-400">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 py-8 text-center text-gray-500">No income categories yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
