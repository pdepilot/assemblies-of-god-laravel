<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Birthday Calendar</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Member birthdays from church records, with auto email/SMS toggles.</p>
            </div>
            <form method="GET" action="{{ route('communication-hub.birthdays.index') }}" class="flex items-center gap-2">
                <label for="birthdayDays" class="text-sm text-gray-500">Show</label>
                <select id="birthdayDays" name="days" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm" onchange="this.form.submit()">
                    @foreach ([7, 14, 30, 60, 90, 366] as $option)
                        <option value="{{ $option }}" @selected((int) ($board['days'] ?? 366) === $option)>
                            {{ $option === 366 ? 'Full year' : 'Next '.$option.' days' }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif

            @include('communication-hub._nav', ['canManage' => $canManage])

            <div class="flex flex-wrap gap-2">
                @if ($canManage)
                    <form method="POST" action="{{ route('communication-hub.birthdays.auto-email') }}">
                        @csrf
                        <input type="hidden" name="days" value="{{ $board['days'] }}">
                        <input type="hidden" name="enabled" value="{{ $board['auto_email'] ? '0' : '1' }}">
                        <button type="submit" class="px-3 py-1.5 rounded border text-sm {{ $board['auto_email'] ? 'bg-emerald-600 text-white border-emerald-600' : '' }}">
                            Auto Email: {{ $board['auto_email'] ? 'ON' : 'OFF' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('communication-hub.birthdays.auto-sms') }}">
                        @csrf
                        <input type="hidden" name="days" value="{{ $board['days'] }}">
                        <input type="hidden" name="enabled" value="{{ $board['auto_sms'] ? '0' : '1' }}">
                        <button type="submit" class="px-3 py-1.5 rounded border text-sm {{ $board['auto_sms'] ? 'bg-emerald-600 text-white border-emerald-600' : '' }}">
                            Auto SMS: {{ $board['auto_sms'] ? 'ON' : 'OFF' }}
                        </button>
                    </form>
                @endif
                <a href="{{ route('communication-hub.settings.edit') }}" class="px-3 py-1.5 rounded border text-sm">Email / SMS settings</a>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm text-gray-500">Today</div>
                    <div class="text-2xl font-semibold">{{ $board['stats']['today'] }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm text-gray-500">Upcoming</div>
                    <div class="text-2xl font-semibold">{{ $board['stats']['upcoming'] }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm text-gray-500">With date of birth</div>
                    <div class="text-2xl font-semibold">{{ $board['stats']['members_with_dob'] }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm text-gray-500">Greetings sent today</div>
                    <div class="text-2xl font-semibold">{{ $board['stats']['sent_today'] }}</div>
                    <div class="text-xs text-gray-400 mt-1">Email {{ $board['stats']['email_sent_today'] }} · SMS {{ $board['stats']['sms_sent_today'] }}</div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="font-semibold">Birthdays today</h3>
                    <span class="text-sm text-gray-500">{{ count($board['today']) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left border-b dark:border-gray-700">
                                <th class="px-6 py-2">Member</th>
                                <th class="py-2">Turning</th>
                                <th class="py-2">Department</th>
                                <th class="py-2">Contact</th>
                                <th class="py-2 pr-6">Greeting</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($board['today'] as $row)
                            <tr class="border-b dark:border-gray-700">
                                <td class="px-6 py-3">
                                    <div class="font-medium">{{ $row['full_name'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $row['member_code'] }}</div>
                                </td>
                                <td>{{ $row['turning_age'] ?? '—' }}</td>
                                <td>{{ $row['department'] !== '' ? $row['department'] : '—' }}</td>
                                <td>
                                    <div>{{ $row['email'] !== '' ? $row['email'] : '—' }}</div>
                                    <div class="text-xs text-gray-500">{{ $row['phone'] !== '' ? $row['phone'] : '' }}</div>
                                </td>
                                <td class="pr-6">
                                    <span class="text-xs {{ $row['already_sent'] ? 'text-emerald-600' : 'text-gray-500' }}">Email {{ $row['already_sent'] ? 'sent' : 'pending' }}</span>
                                    <span class="text-xs block {{ $row['already_sent_sms'] ? 'text-emerald-600' : 'text-gray-500' }}">SMS {{ $row['already_sent_sms'] ? 'sent' : 'pending' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No birthdays today.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="font-semibold">Upcoming birthdays</h3>
                    <span class="text-sm text-gray-500">{{ count($board['upcoming']) }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left border-b dark:border-gray-700">
                                <th class="px-6 py-2">Next date</th>
                                <th class="py-2">Member</th>
                                <th class="py-2">Days</th>
                                <th class="py-2">Turning</th>
                                <th class="py-2">Department</th>
                                <th class="py-2 pr-6">Email</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($board['upcoming'] as $row)
                            <tr class="border-b dark:border-gray-700">
                                <td class="px-6 py-3 whitespace-nowrap">{{ $row['birthday_display'] }}</td>
                                <td>
                                    <div class="font-medium">{{ $row['full_name'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $row['member_code'] }}</div>
                                </td>
                                <td>{{ $row['days_until'] }}</td>
                                <td>{{ $row['turning_age'] ?? '—' }}</td>
                                <td>{{ $row['department'] !== '' ? $row['department'] : '—' }}</td>
                                <td class="pr-6">{{ $row['email'] !== '' ? $row['email'] : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No upcoming birthdays in this range.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-semibold">Recent birthday messages</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left border-b dark:border-gray-700">
                                <th class="px-6 py-2">Member</th>
                                <th class="py-2">Birthday</th>
                                <th class="py-2">Email</th>
                                <th class="py-2">SMS</th>
                                <th class="py-2">Subject</th>
                                <th class="py-2 pr-6">When</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($board['history'] as $row)
                            <tr class="border-b dark:border-gray-700">
                                <td class="px-6 py-3">{{ $row['full_name'] }}</td>
                                <td>{{ $row['birthday_date'] }}</td>
                                <td>{{ $row['email_sent'] ? 'Yes' : 'No' }}</td>
                                <td>{{ $row['sms_sent'] ? 'Yes' : 'No' }}</td>
                                <td>{{ $row['message_subject'] !== '' ? $row['message_subject'] : '—' }}</td>
                                <td class="pr-6 whitespace-nowrap">{{ $row['sent_at'] !== '' ? $row['sent_at'] : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No birthday messages logged yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-3 text-xs text-gray-500 border-t border-gray-100 dark:border-gray-700">
                    Configure SMTP and SMS under Communication Hub → Settings. Automatic greetings use the birthday email/SMS templates once per day when enabled.
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
