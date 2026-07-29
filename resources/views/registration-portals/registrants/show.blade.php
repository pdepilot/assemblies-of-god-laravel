<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl">Registrant details</h2>
            <a href="{{ route('registration-portals.registrants.index', $portal['id']) }}" class="px-4 py-2 text-sm font-semibold rounded-md border">Back to registrants</a>
        </div>
    </x-slot>
    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-gray-500">{{ $portal['event_name'] }}</p>
                        <h3 class="text-2xl font-semibold">{{ $registrant['full_name'] }}</h3>
                        <p class="font-mono text-sm text-indigo-600">{{ $registrant['registration_number'] }}</p>
                    </div>
                    <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium bg-gray-100 dark:bg-gray-700">{{ ucfirst($registrant['status']) }}</span>
                </div>

                <dl class="grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-sm text-gray-500">Email</dt><dd>{{ $registrant['email'] ?: '—' }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Phone</dt><dd>{{ $registrant['phone'] ?: '—' }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Church</dt><dd>{{ $registrant['church'] ?: '—' }}</dd></div>
                    <div><dt class="text-sm text-gray-500">State</dt><dd>{{ $registrant['state_name'] ?: '—' }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Gender</dt><dd>{{ $registrant['gender'] ?: '—' }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Age group</dt><dd>{{ $registrant['age_group'] ?: '—' }}@if(!empty($registrant['age'])) ({{ $registrant['age'] }})@endif</dd></div>
                    <div><dt class="text-sm text-gray-500">Payment</dt><dd>{{ ucfirst($registrant['payment_status']) }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Attendance</dt><dd>{{ str_replace('_', ' ', ucfirst($registrant['attendance_status'])) }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Registered</dt><dd>{{ $registrant['created_at'] }}</dd></div>
                    @if (!empty($registrant['approved_at']))
                        <div><dt class="text-sm text-gray-500">Approved at</dt><dd>{{ $registrant['approved_at'] }}</dd></div>
                    @endif
                    @if (!empty($registrant['ip_address']))
                        <div><dt class="text-sm text-gray-500">IP address</dt><dd class="font-mono text-sm">{{ $registrant['ip_address'] }}</dd></div>
                    @endif
                </dl>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold mb-4">Form answers</h3>
                @if (empty($registrant['answers']))
                    <p class="text-sm text-gray-500">No field answers were recorded for this registrant.</p>
                @else
                    <dl class="space-y-4">
                        @foreach ($registrant['answers'] as $answer)
                            <div class="border-b border-gray-100 dark:border-gray-700 pb-3">
                                <dt class="text-sm text-gray-500">{{ $answer['label'] }}</dt>
                                <dd class="mt-1">
                                    @if (!empty($answer['file_url']))
                                        <a href="{{ $answer['file_url'] }}" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">View uploaded file</a>
                                    @else
                                        {{ $answer['answer_text'] ?: '—' }}
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </div>

            @if ($canManage)
                <form method="POST" action="{{ route('registration-portals.registrants.update-status', [$portal['id'], $registrant['id']]) }}" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 flex flex-wrap gap-3 items-end">
                    @csrf
                    <div>
                        <label class="block text-sm mb-1">Update status</label>
                        <select name="status" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            @foreach ($statuses as $st)
                                <option value="{{ $st }}" @selected($registrant['status'] === $st)>{{ ucfirst($st) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Save status</button>
                </form>

                <form method="POST" action="{{ route('registration-portals.registrants.destroy', [$portal['id'], $registrant['id']]) }}" data-confirm="Delete this registrant? This cannot be undone." data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger">
                    @csrf @method('DELETE')
                    <button type="submit" class="px-4 py-2 rounded-md border border-red-300 text-red-700 text-sm font-semibold">Delete registrant</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
