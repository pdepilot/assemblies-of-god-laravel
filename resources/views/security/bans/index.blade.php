<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Banned IPs &amp; Devices</h2>
                <p class="text-sm text-gray-500 mt-1">Search a banned member or admin by registered email or phone, then lift the ban.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
            @endif

            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">Active bans</div>
                    <div class="text-2xl font-semibold">{{ $result['active'] }}</div>
                </div>
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">Lifted / expired</div>
                    <div class="text-2xl font-semibold">{{ $result['expired'] }}</div>
                </div>
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">Showing</div>
                    <div class="text-2xl font-semibold">{{ $result['total'] }}</div>
                </div>
            </div>

            <form method="GET" class="flex flex-wrap gap-3 items-end bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                <div>
                    <label class="block text-sm">Status</label>
                    <select name="status" class="mt-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        <option value="active" @selected($status === 'active')>Active</option>
                        <option value="lifted" @selected($status === 'lifted')>Lifted / expired</option>
                        <option value="all" @selected($status === 'all')>All</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm">Source</label>
                    <select name="source" class="mt-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        <option value="all" @selected($source === 'all')>All sources</option>
                        @foreach ($sources as $sourceKey)
                            <option value="{{ $sourceKey }}" @selected($source === $sourceKey)>
                                {{ \App\Services\Security\DeviceBanService::sourceLabel($sourceKey) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm">Search email / phone</label>
                    <input type="search" name="q" value="{{ $query }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="Registered email, phone, IP, or device">
                </div>
                <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Filter</button>
            </form>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2 pr-3">IP address</th>
                            <th class="py-2 pr-3">Account</th>
                            <th class="py-2 pr-3">Source</th>
                            <th class="py-2 pr-3">Location</th>
                            <th class="py-2 pr-3">Device</th>
                            <th class="py-2 pr-3">Ban</th>
                            <th class="py-2 pr-3">Expires</th>
                            <th class="py-2 pr-3">Status</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($result['items'] as $item)
                            <tr class="border-b align-top">
                                <td class="py-3 pr-3 font-mono">{{ $item['ip_address'] }}</td>
                                <td class="py-3 pr-3">
                                    @if (! empty($item['account_name']))
                                        <div>{{ $item['account_name'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $item['account_kind'] === 'admin' ? 'Admin' : 'Member' }} · {{ $item['account_contact'] }}</div>
                                    @elseif (! empty($item['identifier_tried']))
                                        <div>{{ $item['identifier_tried'] }}</div>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="py-3 pr-3">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs {{ $item['source'] === 'member-portal/login' ? 'bg-sky-100 text-sky-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ $item['source_label'] }}
                                    </span>
                                </td>
                                <td class="py-3 pr-3">
                                    {{ $item['location']['label'] ?? 'Unknown location' }}
                                    @if (! empty($item['location']['isp']))
                                        <div class="text-xs text-gray-500">{{ $item['location']['isp'] }}</div>
                                    @endif
                                </td>
                                <td class="py-3 pr-3">
                                    {{ $item['browser_info'] }}
                                    <div class="text-xs text-gray-500 font-mono">{{ $item['fingerprint_short'] }}</div>
                                </td>
                                <td class="py-3 pr-3">{{ $item['ban_level_label'] }}</td>
                                <td class="py-3 pr-3">{{ \Illuminate\Support\Carbon::parse($item['ban_expires'])->format('M j, Y g:i A') }}</td>
                                <td class="py-3 pr-3">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs {{ $item['is_active'] ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-700' }}">
                                        {{ $item['status_label'] }}
                                    </span>
                                </td>
                                <td class="py-3 whitespace-nowrap space-x-2">
                                    <a href="{{ route('security.bans.show', $item['id']) }}" class="text-indigo-600 hover:underline">Details</a>
                                    @if ($canManage && $item['is_active'])
                                        <form method="POST" action="{{ route('security.bans.destroy', $item['id']) }}" class="inline" data-confirm="Unban this member or admin so they can sign in again?" data-confirm-title="Lift ban" data-confirm-ok="Unban" data-confirm-tone="danger">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline font-semibold">Unban</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-8 text-center text-gray-500">No banned accounts found for this filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
