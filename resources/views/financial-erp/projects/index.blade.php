<x-app-layout>
    <x-slot name="header"><div class="flex justify-between"><h2 class="font-semibold text-xl">Projects</h2>@if($canManage)<a href="{{ route('financial-erp.projects.create') }}" class="px-4 py-2 text-sm font-semibold rounded-md bg-indigo-600 text-white">New project</a>@endif</div></x-slot>
    <div class="py-10"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8"><div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
        <table class="min-w-full divide-y"><thead><tr><th class="px-3 py-2 text-left text-xs">Code</th><th class="px-3 py-2 text-left text-xs">Name</th><th class="px-3 py-2 text-left text-xs">Budget</th><th class="px-3 py-2 text-left text-xs">Status</th><th class="px-3 py-2 text-left text-xs">Actions</th></tr></thead>
        <tbody>@forelse($items as $item)<tr><td class="px-3 py-2 text-sm font-mono">{{ $item['code'] }}</td><td class="px-3 py-2 text-sm">{{ $item['name'] }}</td><td class="px-3 py-2 text-sm">₦{{ number_format($item['budget_amount'], 2) }}</td><td class="px-3 py-2 text-sm">{{ ucfirst($item['status']) }}</td><td class="px-3 py-2 text-sm"><a href="{{ route('financial-erp.projects.show', $item['id']) }}" class="text-indigo-600">View</a></td></tr>@empty<tr><td colspan="5" class="px-3 py-6 text-center text-sm text-gray-500">No projects.</td></tr>@endforelse</tbody></table>
    </div></div></div>
</x-app-layout>
