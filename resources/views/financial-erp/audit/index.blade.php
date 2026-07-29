<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">ERP Audit Trail</h2></x-slot>
    <div class="py-10"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8"><div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
        <form method="GET" class="mb-4 flex gap-3"><input name="entity_type" value="{{ $entityType }}" placeholder="Entity type (account, journal...)" class="rounded-md border-gray-300"><button class="px-3 py-2 rounded-md bg-indigo-600 text-white text-sm">Filter</button></form>
        <table class="min-w-full divide-y"><thead><tr><th class="px-3 py-2 text-left text-xs">When</th><th class="px-3 py-2 text-left text-xs">Entity</th><th class="px-3 py-2 text-left text-xs">Action</th><th class="px-3 py-2 text-left text-xs">User</th></tr></thead>
        <tbody>@forelse($items as $item)<tr><td class="px-3 py-2 text-sm">{{ $item['created_at'] }}</td><td class="px-3 py-2 text-sm">{{ $item['entity_type'] }} #{{ $item['entity_id'] }}</td><td class="px-3 py-2 text-sm">{{ $item['action'] }}</td><td class="px-3 py-2 text-sm">{{ $item['user_name'] ?: '—' }}</td></tr>@empty<tr><td colspan="4" class="px-3 py-6 text-center text-sm text-gray-500">No audit entries.</td></tr>@endforelse</tbody></table>
    </div></div></div>
</x-app-layout>
