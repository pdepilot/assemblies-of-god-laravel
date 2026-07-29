<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Campaign Manager</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Create email, SMS, or combined campaigns with audience targeting and scheduling.</p>
            </div>
            @if ($canManage)
                <a href="{{ route('communication-hub.campaigns.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">New Campaign</a>
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
                            <th class="py-2">Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Audience</th>
                            <th>Status</th>
                            <th>Scheduled</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($campaigns as $campaign)
                        <tr class="border-b">
                            <td class="py-3 font-mono text-xs">{{ $campaign['campaign_code'] }}</td>
                            <td>{{ $campaign['name'] }}</td>
                            <td>{{ $campaign['campaign_type'] }}</td>
                            <td>{{ $campaign['audience_group_key'] ?: '—' }}</td>
                            <td class="capitalize">{{ $campaign['status'] }}</td>
                            <td class="whitespace-nowrap">{{ $campaign['scheduled_at'] ?: '—' }}</td>
                            <td class="whitespace-nowrap space-x-3">
                                @if ($canManage)
                                    <a href="{{ route('communication-hub.campaigns.edit', $campaign['id']) }}" class="text-indigo-600">Edit</a>
                                    <form method="POST" action="{{ route('communication-hub.campaigns.destroy', $campaign['id']) }}" class="inline" data-confirm="Delete this campaign?" data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-gray-500">No campaigns yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
