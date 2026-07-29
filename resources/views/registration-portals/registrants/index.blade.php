<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl">Registrants — {{ $portal['event_name'] }}</h2>
            <a href="{{ route('registration-portals.show', $portal['id']) }}" class="px-4 py-2 text-sm font-semibold rounded-md border">Back to portal</a>
        </div>
    </x-slot>
    <div class="py-10"><div class="max-w-7xl mx-auto sm:px-6 space-y-6">
        @if (session('status'))<div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>@endif
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700"><tr>
                    <th class="px-3 py-2 text-left text-xs">Number</th><th class="px-3 py-2 text-left text-xs">Name</th>
                    <th class="px-3 py-2 text-left text-xs">Email</th><th class="px-3 py-2 text-left text-xs">Status</th>
                    <th class="px-3 py-2 text-left text-xs">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($items as $item)
                        <tr>
                            <td class="px-3 py-2 text-sm font-mono">{{ $item['registration_number'] }}</td>
                            <td class="px-3 py-2 text-sm">
                                <a href="{{ route('registration-portals.registrants.show', [$portal['id'], $item['id']]) }}" class="text-indigo-600 hover:underline font-medium">{{ $item['full_name'] }}</a>
                            </td>
                            <td class="px-3 py-2 text-sm">{{ $item['email'] ?: '—' }}</td>
                            <td class="px-3 py-2 text-sm">{{ ucfirst($item['status']) }}</td>
                            <td class="px-3 py-2 text-sm space-x-2">
                                <a href="{{ route('registration-portals.registrants.show', [$portal['id'], $item['id']]) }}" class="text-indigo-600 hover:underline">Full details</a>
                                @if($canManage)
                                    <form method="POST" action="{{ route('registration-portals.registrants.update-status', [$portal['id'], $item['id']]) }}" class="inline">@csrf
                                        <input type="hidden" name="status" value="approved" /><button type="submit" class="text-green-600 hover:underline">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('registration-portals.registrants.destroy', [$portal['id'], $item['id']]) }}" class="inline" data-confirm="Delete this registrant? This cannot be undone." data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-6 text-center text-sm text-gray-500">No registrants yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div></div>
</x-app-layout>
