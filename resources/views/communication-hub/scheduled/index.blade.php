<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Scheduled Messages</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Immediate, scheduled, and recurring outbound messages across channels.</p>
            </div>
            @if ($canManage)
                <a href="{{ route('communication-hub.scheduled.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Schedule message</a>
            @endif
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">{{ $errors->first() }}</div>
            @endif

            @include('communication-hub._nav', ['canManage' => $canManage])

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2">Channel</th>
                            <th>Subject / Preview</th>
                            <th>When</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b">
                            <td class="py-3 capitalize">{{ $item['channel'] }}</td>
                            <td>
                                <p class="font-medium">{{ $item['preview'] }}</p>
                                @if (!empty($item['message_code']))
                                    <p class="text-xs text-gray-500 font-mono">{{ $item['message_code'] }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap">{{ $item['scheduled_at'] ?: '—' }}</td>
                            <td class="capitalize">{{ $item['status'] }}</td>
                            <td class="whitespace-nowrap space-x-3">
                                @if (($item['source'] ?? '') === 'scheduled_messages' && $canManage)
                                    <a href="{{ route('communication-hub.scheduled.edit', $item['id']) }}" class="text-indigo-600">Edit</a>
                                    @if (in_array($item['status'], ['scheduled', 'processing'], true))
                                        <form method="POST" action="{{ route('communication-hub.scheduled.cancel', $item['id']) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-amber-600">Cancel</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('communication-hub.scheduled.destroy', $item['id']) }}" class="inline" data-confirm="Delete this scheduled message?" data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600">Delete</button>
                                    </form>
                                @elseif (($item['source'] ?? '') === 'email_history')
                                    <a href="{{ route('communication-hub.email-center.index', ['folder' => 'scheduled']) }}" class="text-indigo-600">View in Email Center</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-gray-500">No scheduled messages.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
