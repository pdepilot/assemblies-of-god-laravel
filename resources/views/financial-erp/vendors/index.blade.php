<x-app-layout>
    <x-slot name="header"><div class="flex justify-between"><h2 class="font-semibold text-xl">Vendors</h2>@if($canManage)<a href="{{ route('financial-erp.vendors.create') }}" class="px-4 py-2 text-sm font-semibold rounded-md bg-indigo-600 text-white">New vendor</a>@endif</div></x-slot>
    <div class="py-10"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8"><div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
        @if(session('status'))<div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>@endif
        <table class="min-w-full divide-y"><thead><tr><th class="px-3 py-2 text-left text-xs">Code</th><th class="px-3 py-2 text-left text-xs">Name</th><th class="px-3 py-2 text-left text-xs">Phone</th><th class="px-3 py-2 text-left text-xs">Actions</th></tr></thead>
        <tbody>@forelse($items as $item)<tr><td class="px-3 py-2 text-sm font-mono">{{ $item['code'] }}</td><td class="px-3 py-2 text-sm">{{ $item['name'] }}</td><td class="px-3 py-2 text-sm">{{ $item['phone'] ?: '—' }}</td><td class="px-3 py-2 text-sm">@if($canManage)<a href="{{ route('financial-erp.vendors.edit', $item['id']) }}" class="text-indigo-600">Edit</a>@endif</td></tr>@empty<tr><td colspan="4" class="px-3 py-6 text-center text-sm text-gray-500">No vendors.</td></tr>@endforelse</tbody></table>
    </div></div></div>
</x-app-layout>
