<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">SMS Center</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Compose, schedule, and track SMS via Termii or Twilio.</p>
            </div>
            <div class="text-sm text-gray-500">
                Provider: {{ strtoupper($board['balance']['provider'] ?? 'termii') }}
                · Balance:
                @if ($board['balance']['balance'] !== null)
                    {{ number_format((float) $board['balance']['balance'], 2) }} {{ $board['balance']['currency'] }}
                @else
                    —
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">{{ $errors->first() }}</div>
            @endif

            @include('communication-hub._nav', ['canManage' => $canManage])

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm text-gray-500">Pending queue</div>
                    <div class="text-2xl font-semibold">{{ $board['stats']['pending'] }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm text-gray-500">Sent today</div>
                    <div class="text-2xl font-semibold">{{ $board['stats']['sent_today'] }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm text-gray-500">Failed today</div>
                    <div class="text-2xl font-semibold">{{ $board['stats']['failed_today'] }}</div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                        <h3 class="font-semibold">Delivery reports</h3>
                        <div class="flex flex-wrap gap-2 text-sm">
                            @foreach (['' => 'All', 'sent' => 'Sent', 'failed' => 'Failed', 'queued' => 'Queued'] as $key => $label)
                                <a href="{{ route('communication-hub.sms-center.index', ['status' => $key]) }}"
                                   class="px-3 py-1 rounded border {{ ($status ?? '') === $key ? 'bg-indigo-600 text-white' : '' }}">{{ $label }}</a>
                            @endforeach
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left border-b dark:border-gray-700">
                                    <th class="py-2">Phone</th>
                                    <th>Message</th>
                                    <th>Provider</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse ($board['items'] as $row)
                                <tr class="border-b dark:border-gray-700 align-top">
                                    <td class="py-3 whitespace-nowrap">
                                        <div>{{ $row['recipient_phone'] }}</div>
                                        @if ($row['recipient_name'] !== '')
                                            <div class="text-xs text-gray-500">{{ $row['recipient_name'] }}</div>
                                        @endif
                                    </td>
                                    <td class="max-w-xs">
                                        <div class="truncate" title="{{ $row['message_body'] }}">{{ \Illuminate\Support\Str::limit($row['message_body'], 80) }}</div>
                                        @if ($row['error_message'] !== '')
                                            <div class="text-xs text-red-600 mt-1">{{ \Illuminate\Support\Str::limit($row['error_message'], 100) }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $row['provider'] !== '' ? $row['provider'] : '—' }}</td>
                                    <td>{{ $row['status'] }}</td>
                                    <td class="whitespace-nowrap">{{ $row['sent_at'] !== '' ? $row['sent_at'] : $row['created_at'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-8 text-center text-gray-500">No SMS delivery reports yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if (($board['pages'] ?? 1) > 1)
                        <div class="mt-4 flex justify-between text-sm text-gray-500">
                            <span>Page {{ $board['page'] }} of {{ $board['pages'] }}</span>
                            <div class="flex gap-2">
                                @if ($board['page'] > 1)
                                    <a class="px-3 py-1 rounded border" href="{{ route('communication-hub.sms-center.index', ['status' => $status, 'page' => $board['page'] - 1]) }}">Previous</a>
                                @endif
                                @if ($board['page'] < $board['pages'])
                                    <a class="px-3 py-1 rounded border" href="{{ route('communication-hub.sms-center.index', ['status' => $status, 'page' => $board['page'] + 1]) }}">Next</a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold mb-4">Compose SMS</h3>
                    @if (! ($board['settings']['enabled'] ?? false))
                        <div class="mb-4 rounded-md bg-amber-50 dark:bg-amber-900/30 p-3 text-sm text-amber-800 dark:text-amber-200">
                            SMS sending is currently disabled.
                            <a class="underline" href="{{ route('communication-hub.settings.edit') }}">Enable it in Email / SMS settings</a>.
                        </div>
                    @endif
                    @if ($canManage)
                        <form method="POST" action="{{ route('communication-hub.sms-center.compose') }}" class="space-y-4" id="smsCenterComposeForm">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium">Phone</label>
                                <input type="text" name="phone" value="{{ old('phone') }}" required maxlength="30"
                                       placeholder="08012345678 or 2348012345678"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Name (optional)</label>
                                <input type="text" name="name" value="{{ old('name') }}" maxlength="255"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            </div>
                            @if ($templates !== [])
                                <div>
                                    <label class="block text-sm font-medium">SMS template</label>
                                    <select id="smsTemplateSelect" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                        <option value="">No template (write your own)</option>
                                        @foreach ($templates as $template)
                                            <option value="{{ $template['id'] }}">{{ $template['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div>
                                <label class="block text-sm font-medium">Message</label>
                                <textarea name="message" id="smsMessage" rows="5" required maxlength="640"
                                          class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('message') }}</textarea>
                                <p class="mt-1 text-xs text-gray-500"><span id="smsCharCount">0</span>/640</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Priority</label>
                                <select name="priority" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                    @foreach (['normal', 'high', 'low'] as $priority)
                                        <option value="{{ $priority }}" @selected(old('priority', 'normal') === $priority)>{{ ucfirst($priority) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Schedule (optional)</label>
                                <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            </div>
                            <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Send / Queue SMS</button>
                        </form>
                    @else
                        <p class="text-sm text-gray-500">You can view delivery reports, but composing requires communication manage access.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var msg = document.getElementById('smsMessage');
            var count = document.getElementById('smsCharCount');
            function syncCount() {
                if (msg && count) count.textContent = String(msg.value.length);
            }
            if (msg) {
                msg.addEventListener('input', syncCount);
                syncCount();
            }

            @php
                $templatesById = collect($templates)->mapWithKeys(function (array $t) {
                    return [(string) $t['id'] => $t['body_text']];
                });
            @endphp
            var templatesById = @json($templatesById);
            var sel = document.getElementById('smsTemplateSelect');
            if (sel && msg) {
                sel.addEventListener('change', function () {
                    if (!sel.value) return;
                    msg.value = templatesById[String(sel.value)] || '';
                    syncCount();
                });
            }
        })();
    </script>
</x-app-layout>
