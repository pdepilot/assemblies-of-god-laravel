<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl">Registration Portals</h2>
            @if ($canManage)<a href="{{ route('registration-portals.create') }}" class="px-4 py-2 text-sm font-semibold rounded-md bg-indigo-600 text-white">Create portal</a>@endif
        </div>
    </x-slot>
    <div class="py-10"><div class="max-w-7xl mx-auto sm:px-6 space-y-6">
        @if (session('status'))<div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>@endif
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Portals</div><div class="text-2xl font-semibold">{{ $stats['portals']['total'] }}</div></div>
            <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Open</div><div class="text-2xl font-semibold">{{ $stats['portals']['active'] }}</div></div>
            <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Registrations</div><div class="text-2xl font-semibold">{{ $stats['registrations']['total'] }}</div></div>
            <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">This month</div><div class="text-2xl font-semibold">{{ $stats['registrations']['monthly'] }}</div></div>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Male</div><div class="text-2xl font-semibold">{{ $stats['demographics']['male'] }}</div></div>
            <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Female</div><div class="text-2xl font-semibold">{{ $stats['demographics']['female'] }}</div></div>
            <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Children</div><div class="text-2xl font-semibold">{{ $stats['demographics']['children'] }}</div></div>
            <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Teens</div><div class="text-2xl font-semibold">{{ $stats['demographics']['teens'] }}</div></div>
            <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Adults</div><div class="text-2xl font-semibold">{{ $stats['demographics']['adults'] }}</div></div>
        </div>
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700"><tr>
                    <th class="px-3 py-2 text-left text-xs">Event</th><th class="px-3 py-2 text-left text-xs">Slug</th>
                    <th class="px-3 py-2 text-left text-xs">Status</th><th class="px-3 py-2 text-left text-xs">Registrants</th><th class="px-3 py-2 text-left text-xs">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($items as $item)
                        <tr>
                            <td class="px-3 py-2 text-sm">{{ $item['event_name'] }}</td>
                            <td class="px-3 py-2 text-sm font-mono">{{ $item['slug'] }}</td>
                            <td class="px-3 py-2 text-sm">{{ ucfirst($item['status']) }}</td>
                            <td class="px-3 py-2 text-sm">{{ $item['registrant_count'] }}</td>
                            <td class="px-3 py-2 text-sm space-x-2">
                                <a href="{{ route('registration-portals.show', $item['id']) }}" class="text-indigo-600 hover:underline">Details</a>
                                <a href="{{ route('registration-portals.qr', $item['id']) }}" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">QR</a>
                                <a href="{{ $item['public_url'] }}" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">{{ $item['status'] === 'draft' ? 'Preview' : 'Open form' }}</a>
                                <a href="{{ route('registration-portals.registrants.index', $item['id']) }}" class="text-indigo-600 hover:underline">Registrants</a>
                                @if ($canManage)
                                    <form method="POST" action="{{ route('registration-portals.destroy', $item['id']) }}" class="inline" data-confirm="Delete this registration form and all its registrants? This cannot be undone." data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-6 text-center text-sm text-gray-500">No registration portals yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div></div>
</x-app-layout>
