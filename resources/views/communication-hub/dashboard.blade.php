<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Communication Hub</h2></x-slot>
    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @include('communication-hub._nav', ['canManage' => $canManage])
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    'Templates' => $stats['templates'],
                    'Logs today' => $stats['logs_today'],
                    'Emails today' => $stats['emails_today'],
                    'New contacts' => $stats['new_contacts'],
                    'Active subscribers' => $stats['active_subscribers'],
                    'Unread notifications' => $stats['unread_notifications'],
                    'Newsletter drafts' => $stats['newsletter_drafts'],
                ] as $label => $value)
                    <div class="rounded-lg border p-4 bg-white dark:bg-gray-800">
                        <div class="text-sm text-gray-500">{{ $label }}</div>
                        <div class="text-2xl font-semibold">{{ $value }}</div>
                    </div>
                @endforeach
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold mb-4">Recent communications</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead><tr class="text-left border-b"><th class="py-2">Channel</th><th>Subject</th><th>Recipient</th><th>Status</th><th>When</th></tr></thead>
                        <tbody>
                        @forelse ($recent as $row)
                            <tr class="border-b">
                                <td class="py-2">{{ $row['channel'] ?? '—' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($row['subject'] ?? '—', 50) }}</td>
                                <td>{{ $row['recipient'] ?? '—' }}</td>
                                <td>{{ $row['status'] ?? '—' }}</td>
                                <td>{{ $row['created_at'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-gray-500">No communication logs yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
