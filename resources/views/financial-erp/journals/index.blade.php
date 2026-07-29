<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between gap-3"><h2 class="font-semibold text-xl">Journal Entries</h2>@if($canManage)<a href="{{ route('financial-erp.journals.create') }}" class="px-4 py-2 text-sm font-semibold rounded-md bg-indigo-600 text-white">New journal</a>@endif</div>
    </x-slot>
    <div class="py-10"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8"><div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
        <table class="min-w-full divide-y"><thead><tr><th class="px-3 py-2 text-left text-xs">No</th><th class="px-3 py-2 text-left text-xs">Date</th><th class="px-3 py-2 text-left text-xs">Debit</th><th class="px-3 py-2 text-left text-xs">Credit</th><th class="px-3 py-2 text-left text-xs">Status</th><th class="px-3 py-2 text-left text-xs">Actions</th></tr></thead>
        <tbody>@forelse($items as $item)<tr><td class="px-3 py-2 text-sm font-mono">{{ $item['journal_no'] }}</td><td class="px-3 py-2 text-sm">{{ $item['journal_date'] }}</td><td class="px-3 py-2 text-sm">₦{{ number_format($item['total_debit'], 2) }}</td><td class="px-3 py-2 text-sm">₦{{ number_format($item['total_credit'], 2) }}</td><td class="px-3 py-2 text-sm">{{ ucfirst($item['status']) }}</td><td class="px-3 py-2 text-sm"><a href="{{ route('financial-erp.journals.show', $item['id']) }}" class="text-indigo-600">View</a></td></tr>@empty<tr><td colspan="6" class="px-3 py-6 text-center text-sm text-gray-500">No journals.</td></tr>@endforelse</tbody></table>
    </div></div></div>
</x-app-layout>
