<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl">{{ $portal['event_name'] }}</h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ $portal['public_url'] }}" target="_blank" rel="noopener" class="px-4 py-2 text-sm font-semibold rounded-md border">{{ $portal['status'] === 'draft' ? 'Preview form' : 'Open form' }}</a>
                <a href="{{ route('registration-portals.qr', $portal['id']) }}" target="_blank" rel="noopener" class="px-4 py-2 text-sm font-semibold rounded-md border">QR code</a>
                <a href="{{ route('registration-portals.registrants.index', $portal['id']) }}" class="px-4 py-2 text-sm font-semibold rounded-md bg-indigo-600 text-white">Registrants</a>
                @if ($canManage)
                    <a href="{{ route('registration-portals.edit', $portal['id']) }}" class="px-4 py-2 text-sm font-semibold rounded-md border">Edit</a>
                    <form method="POST" action="{{ route('registration-portals.destroy', $portal['id']) }}" data-confirm="Delete this registration form and all its registrants? This cannot be undone." data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 text-sm font-semibold rounded-md bg-red-600 text-white">Delete form</button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>
    <div class="py-10"><div class="max-w-4xl mx-auto sm:px-6 space-y-6">
        @if (session('status'))<div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>@endif
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div><dt class="text-sm text-gray-500">Slug</dt><dd class="font-mono">{{ $portal['slug'] }}</dd></div>
                <div><dt class="text-sm text-gray-500">Status</dt><dd>{{ ucfirst($portal['status']) }}</dd></div>
                <div><dt class="text-sm text-gray-500">Venue</dt><dd>{{ $portal['venue'] ?: '—' }}</dd></div>
                <div><dt class="text-sm text-gray-500">Registrants</dt><dd>{{ $portal['registrant_count'] }}</dd></div>
            </dl>
        </div>
        @if ($canManage)
            <form method="POST" action="{{ route('registration-portals.update-status', $portal['id']) }}" class="flex gap-3 items-end">@csrf
                <div><label class="block text-sm">Change status</label>
                    <select name="status" class="mt-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">@foreach($statuses as $st)<option value="{{ $st }}" @selected($portal['status']===$st)>{{ ucfirst($st) }}</option>@endforeach</select>
                </div>
                <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm">Update status</button>
            </form>
        @endif
    </div></div>
</x-app-layout>
