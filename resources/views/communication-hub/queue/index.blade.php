<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Communication Queue</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pending and failed jobs across email and SMS queues.</p>
            </div>
            @if ($canManage)
                <div class="flex flex-wrap items-center gap-2">
                    @if ($hasFailed)
                        <form method="POST" action="{{ route('communication-hub.queue.destroy-failed') }}" data-confirm="Delete all failed queue jobs? This cannot be undone." data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-semibold">Delete Failed</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('communication-hub.queue.process') }}">
                        @csrf
                        <button class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Process Queues</button>
                    </form>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif

            @include('communication-hub._nav', ['canManage' => $canManage])

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ([
                    ['label' => 'Email Pending', 'value' => $overview['email_pending']],
                    ['label' => 'SMS Pending', 'value' => $overview['sms_pending']],
                    ['label' => 'Email Failed', 'value' => $overview['email_failed']],
                    ['label' => 'SMS Failed', 'value' => $overview['sms_failed']],
                    ['label' => 'Scheduled', 'value' => $overview['scheduled']],
                ] as $stat)
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                        <p class="text-sm text-gray-500">{{ $stat['label'] }}</p>
                        <p class="text-2xl font-semibold mt-1">{{ $stat['value'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <h3 class="font-semibold">Recent queue jobs</h3>
                    <p class="text-xs text-gray-500">
                        Showing {{ $jobs->firstItem() ?? 0 }}–{{ $jobs->lastItem() ?? 0 }} of {{ $jobs->total() }}
                    </p>
                </div>
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2">Channel</th>
                            <th>Recipient / Preview</th>
                            <th>Detail</th>
                            <th>Status</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($jobs as $job)
                        <tr class="border-b">
                            <td class="py-3 capitalize">{{ $job['channel'] }}</td>
                            <td>{{ $job['preview'] }}</td>
                            <td>{{ $job['detail'] }}</td>
                            <td class="capitalize">
                                {{ $job['status'] }}
                                @if ($job['error'] !== '')
                                    <p class="text-xs text-red-600">{{ $job['error'] }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap">{{ $job['scheduled_at'] ?: $job['created_at'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-gray-500">Queue is empty.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                @if ($jobs->hasPages())
                    <div class="mt-4">{{ $jobs->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
