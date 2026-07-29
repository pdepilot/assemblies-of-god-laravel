<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Chart of Accounts</h2>
            @if($canManage)<a href="{{ route('financial-erp.accounts.create') }}" class="px-4 py-2 text-sm font-semibold rounded-md bg-indigo-600 text-white">New account</a>@endif
        </div>
    </x-slot>
    <div class="py-10"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        @if(session('status'))<div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>@endif
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <form method="GET" class="mb-4"><select name="type" class="rounded-md border-gray-300" onchange="this.form.submit()"><option value="">All types</option>@foreach($types as $t)<option value="{{ $t }}" @selected($type===$t)>{{ ucfirst($t) }}</option>@endforeach</select></form>
            <table class="min-w-full divide-y"><thead><tr><th class="px-3 py-2 text-left text-xs">Code</th><th class="px-3 py-2 text-left text-xs">Name</th><th class="px-3 py-2 text-left text-xs">Type</th><th class="px-3 py-2 text-left text-xs">Actions</th></tr></thead>
            <tbody>@forelse($items as $item)<tr><td class="px-3 py-2 text-sm font-mono">{{ $item['code'] }}</td><td class="px-3 py-2 text-sm">{{ $item['name'] }}</td><td class="px-3 py-2 text-sm">{{ ucfirst($item['account_type']) }}</td><td class="px-3 py-2 text-sm">@if($canManage)<a href="{{ route('financial-erp.accounts.edit', $item['id']) }}" class="text-indigo-600">Edit</a>@endif</td></tr>@empty<tr><td colspan="4" class="px-3 py-6 text-center text-sm text-gray-500">No accounts.</td></tr>@endforelse</tbody></table>
        </div>
    </div></div>
</x-app-layout>
