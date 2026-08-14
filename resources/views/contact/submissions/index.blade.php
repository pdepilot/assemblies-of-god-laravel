<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Contact Inbox</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Enquiries, prayer requests, and visit notes from the public website.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif

            @include('communication-hub._nav', ['canManage' => $canManage])

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ([
                    ['label' => 'New', 'value' => $stats['new'] ?? 0],
                    ['label' => 'In progress', 'value' => $stats['in_progress'] ?? 0],
                    ['label' => 'Replied', 'value' => $stats['replied'] ?? 0],
                    ['label' => 'Prayer', 'value' => $stats['prayer'] ?? 0],
                    ['label' => 'Total', 'value' => $stats['total'] ?? 0],
                ] as $card)
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">{{ $card['label'] }}</p>
                        <p class="text-2xl font-semibold mt-1">{{ $card['value'] }}</p>
                    </div>
                @endforeach
            </div>

            <form method="GET" action="{{ route('contact.submissions.index') }}" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[180px]">
                    <label class="block text-sm font-medium">Search</label>
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name, email, code…" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                </div>
                <div>
                    <label class="block text-sm font-medium">Status</label>
                    <select name="status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        <option value="">All</option>
                        @foreach ($statuses as $s)
                            <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ str_replace('_', ' ', $s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium">Type</label>
                    <select name="type" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        <option value="">All</option>
                        @foreach ($inquiryTypes as $t)
                            <option value="{{ $t }}" @selected(($filters['type'] ?? '') === $t)>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm">Filter</button>
            </form>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left border-b bg-gray-50 dark:bg-gray-900/40">
                                <th class="px-4 py-3">Reference</th>
                                <th class="px-4 py-3">From</th>
                                <th class="px-4 py-3">Subject</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Received</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($result['items'] as $row)
                            @php
                                $mailto = 'mailto:'.rawurlencode((string) $row['email'])
                                    .'?subject='.rawurlencode('Re: '.(string) $row['subject'])
                                    .'&body='.rawurlencode('Dear '.(string) $row['full_name'].",\n\nThank you for contacting AGC Ikenegbu.\n\n");
                            @endphp
                            <tr class="border-b {{ ($row['status'] ?? '') === 'new' ? 'font-semibold bg-amber-50/60 dark:bg-amber-900/10' : '' }}">
                                <td class="px-4 py-3">
                                    <a href="{{ route('contact.submissions.show', $row['id']) }}" class="text-indigo-600 hover:underline">{{ $row['submission_code'] }}</a>
                                </td>
                                <td class="px-4 py-3">
                                    <div>{{ $row['full_name'] }}</div>
                                    <div class="text-xs text-gray-500 font-normal">{{ $row['email'] }}</div>
                                </td>
                                <td class="px-4 py-3 max-w-xs truncate">{{ $row['subject'] }}</td>
                                <td class="px-4 py-3 capitalize">{{ $row['inquiry_type'] }}</td>
                                <td class="px-4 py-3 capitalize">{{ str_replace('_', ' ', $row['status']) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($row['created_at'])->format('d M Y H:i') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap justify-end gap-2 font-normal">
                                        <a href="{{ route('contact.submissions.show', $row['id']) }}"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 dark:border-gray-600 text-xs hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <i class="fas fa-eye"></i> Open
                                        </a>
                                        @if ($canManage)
                                            <a href="{{ route('contact.submissions.show', $row['id']) }}#reply-composer"
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-indigo-600 text-white text-xs hover:bg-indigo-700">
                                                <i class="fas fa-reply"></i> Reply
                                            </a>
                                            <form method="POST" action="{{ route('contact.submissions.destroy', $row['id']) }}" class="inline"
                                                  onsubmit="return confirm('Delete this message permanently?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-red-300 text-red-700 text-xs hover:bg-red-50 dark:border-red-700 dark:text-red-300 dark:hover:bg-red-900/30">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ $mailto }}"
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-indigo-600 text-white text-xs hover:bg-indigo-700">
                                                <i class="fas fa-reply"></i> Reply
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-gray-500">No messages match your filters.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                @if (($result['pages'] ?? 1) > 1)
                    <div class="px-4 py-3 flex justify-between text-sm text-gray-500 border-t">
                        <span>Page {{ $result['page'] }} of {{ $result['pages'] }} ({{ $result['total'] }} total)</span>
                        <div class="flex gap-2">
                            @if ($result['page'] > 1)
                                <a class="px-3 py-1 rounded border" href="{{ route('contact.submissions.index', array_merge($filters, ['page' => $result['page'] - 1])) }}">Previous</a>
                            @endif
                            @if ($result['page'] < $result['pages'])
                                <a class="px-3 py-1 rounded border" href="{{ route('contact.submissions.index', array_merge($filters, ['page' => $result['page'] + 1])) }}">Next</a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
