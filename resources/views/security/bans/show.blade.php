<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Ban details</h2>
                <p class="text-sm text-gray-500 mt-1 font-mono">{{ $ban['ip_address'] }} · {{ $ban['fingerprint_short'] }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('security.bans.index') }}" class="px-4 py-2 text-sm rounded-md border">Back</a>
                @if ($canManage && $ban['is_active'])
                    <form method="POST" action="{{ route('security.bans.destroy', $ban['id']) }}" data-confirm="Unban this member or admin so they can sign in again?" data-confirm-title="Lift ban" data-confirm-ok="Unban" data-confirm-tone="danger">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 text-sm rounded-md bg-red-600 text-white font-semibold">Unban</button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                    <div>
                        <dt class="text-gray-500">IP address</dt>
                        <dd class="font-mono font-medium">{{ $ban['ip_address'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Account</dt>
                        <dd class="font-medium">
                            @if (! empty($ban['account_name']))
                                {{ $ban['account_name'] }}
                                <div class="text-xs text-gray-500 font-normal">{{ $ban['account_kind'] === 'admin' ? 'Admin' : 'Member' }} · {{ $ban['account_contact'] }}</div>
                            @elseif (! empty($ban['identifier_tried']))
                                {{ $ban['identifier_tried'] }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Login used</dt>
                        <dd class="font-medium">{{ $ban['identifier_tried'] ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Status</dt>
                        <dd class="font-medium">{{ $ban['status_label'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Source</dt>
                        <dd class="font-medium">{{ $ban['source_label'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Location</dt>
                        <dd class="font-medium">{{ $ban['location']['label'] ?? 'Unknown location' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">ISP / network</dt>
                        <dd class="font-medium">{{ $ban['location']['isp'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Device / browser</dt>
                        <dd class="font-medium">{{ $ban['browser_info'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Ban type</dt>
                        <dd class="font-medium">{{ $ban['ban_level_label'] }} (#{{ $ban['ban_count'] }})</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Started</dt>
                        <dd class="font-medium">{{ \Illuminate\Support\Carbon::parse($ban['ban_start'])->format('M j, Y g:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Expires</dt>
                        <dd class="font-medium">{{ \Illuminate\Support\Carbon::parse($ban['ban_expires'])->format('M j, Y g:i A') }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500">Device fingerprint</dt>
                        <dd class="font-mono text-xs break-all">{{ $ban['device_fingerprint'] }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500">User agent</dt>
                        <dd class="text-xs break-all text-gray-600 dark:text-gray-300">{{ $ban['user_agent'] !== '' ? $ban['user_agent'] : '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <h3 class="font-semibold mb-3">Recent login attempts from this device</h3>
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2">When</th>
                            <th>Email tried</th>
                            <th>Result</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ban['recent_attempts'] as $attempt)
                            <tr class="border-b">
                                <td class="py-2">{{ \Illuminate\Support\Carbon::parse($attempt['created_at'])->format('M j, Y g:i A') }}</td>
                                <td>{{ $attempt['email_attempted'] ?? '—' }}</td>
                                <td>{{ ! empty($attempt['success']) ? 'Success' : 'Failed' }}</td>
                                <td>{{ $attempt['failure_reason'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-gray-500">No login attempts found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
