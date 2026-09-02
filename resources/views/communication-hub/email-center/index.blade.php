<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Email Center</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Compose, schedule, and review outbound email.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                @php
                    $composeTone = (string) session('compose_tone', 'success');
                    $bannerClass = match ($composeTone) {
                        'error' => 'bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-200',
                        'warning' => 'bg-amber-50 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200',
                        default => 'bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-200',
                    };
                @endphp
                <div class="rounded-md p-4 text-sm {{ $bannerClass }}" role="status" aria-live="polite" id="emailComposeResultBanner">
                    @if (session('compose_sent'))
                        <span class="font-semibold">Sent</span> — {{ session('status') }}
                    @else
                        {{ session('status') }}
                    @endif
                </div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">{{ $errors->first() }}</div>
            @endif

            @include('communication-hub._nav', ['canManage' => $canManage])

            <div class="flex flex-wrap gap-2">
                @foreach (['' => 'All', 'sent' => 'Sent', 'failed' => 'Failed', 'draft' => 'Drafts', 'scheduled' => 'Scheduled'] as $key => $label)
                    <a href="{{ route('communication-hub.email-center.index', ['folder' => $key]) }}"
                       class="px-3 py-1 rounded border text-sm {{ ($folder ?? '') === $key ? 'bg-indigo-600 text-white' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                        <h3 class="font-semibold">Message history</h3>
                        <p class="text-xs text-gray-500">
                            Showing {{ $result['from'] ?? 0 }}–{{ $result['to'] ?? 0 }} of {{ $result['total'] ?? 0 }}
                            @if (($result['pages'] ?? 1) > 1)
                                · Page {{ $result['page'] }} of {{ $result['pages'] }}
                            @endif
                        </p>
                    </div>

                    @if ($canManage && ($result['items'] ?? []) !== [])
                        <form method="POST" action="{{ route('communication-hub.email-center.destroy-many') }}" id="emailHistoryBulkForm"
                              data-confirm="Delete the selected email records? This cannot be undone."
                              data-confirm-title="Please confirm"
                              data-confirm-ok="Delete"
                              data-confirm-tone="danger"
                              class="mb-3">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="folder" value="{{ $folder }}">
                            <input type="hidden" name="page" value="{{ $result['page'] }}">
                            <button type="submit" class="px-3 py-1.5 rounded-md border border-red-300 text-red-700 text-xs font-semibold hover:bg-red-50 dark:border-red-700 dark:text-red-300 dark:hover:bg-red-900/20">
                                Delete selected
                            </button>
                        </form>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left border-b">
                                    @if ($canManage)
                                        <th class="py-2 pr-2 w-8">
                                            <input type="checkbox" id="emailHistorySelectAll" title="Select all on this page" class="rounded border-gray-300">
                                        </th>
                                    @endif
                                    <th class="py-2">Subject</th>
                                    <th>Recipient</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    @if ($canManage)
                                        <th></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                            @forelse ($result['items'] as $row)
                                <tr class="border-b">
                                    @if ($canManage)
                                        <td class="py-2 pr-2">
                                            <input type="checkbox" name="ids[]" value="{{ $row['id'] }}" form="emailHistoryBulkForm" class="email-history-row-check rounded border-gray-300">
                                        </td>
                                    @endif
                                    <td class="py-2">{{ $row['subject'] !== '' ? $row['subject'] : '—' }}</td>
                                    <td>{{ $row['recipient_name'] !== '' ? $row['recipient_name'] : $row['recipient'] }}</td>
                                    <td>{{ $row['status'] }}</td>
                                    <td class="whitespace-nowrap">{{ $row['sent_at'] !== '' ? $row['sent_at'] : $row['created_at'] }}</td>
                                    @if ($canManage)
                                        <td class="whitespace-nowrap text-right">
                                            <form method="POST"
                                                  action="{{ route('communication-hub.email-center.destroy', $row['id']) }}?{{ http_build_query(array_filter(['folder' => $folder ?: null, 'page' => $result['page']])) }}"
                                                  class="inline"
                                                  data-confirm="Delete this email record?"
                                                  data-confirm-title="Please confirm"
                                                  data-confirm-ok="Delete"
                                                  data-confirm-tone="danger">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline text-xs">Delete</button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $canManage ? 6 : 4 }}" class="py-6 text-center text-gray-500">No messages in this folder.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if (($result['total'] ?? 0) > 0)
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-gray-500">
                            <span>{{ $result['per_page'] }} per page</span>
                            <div class="flex flex-wrap items-center gap-2">
                                @if (($result['page'] ?? 1) > 1)
                                    <a class="px-3 py-1 rounded border hover:bg-gray-50 dark:hover:bg-gray-700"
                                       href="{{ route('communication-hub.email-center.index', array_filter(['folder' => $folder ?: null, 'page' => $result['page'] - 1])) }}">Previous</a>
                                @endif

                                @php
                                    $current = (int) ($result['page'] ?? 1);
                                    $pages = (int) ($result['pages'] ?? 1);
                                    $start = max(1, $current - 2);
                                    $end = min($pages, $current + 2);
                                @endphp
                                @if ($pages > 1)
                                    @for ($p = $start; $p <= $end; $p++)
                                        @if ($p === $current)
                                            <span class="px-3 py-1 rounded bg-indigo-600 text-white">{{ $p }}</span>
                                        @else
                                            <a class="px-3 py-1 rounded border hover:bg-gray-50 dark:hover:bg-gray-700"
                                               href="{{ route('communication-hub.email-center.index', array_filter(['folder' => $folder ?: null, 'page' => $p])) }}">{{ $p }}</a>
                                        @endif
                                    @endfor
                                @endif

                                @if (($result['page'] ?? 1) < ($result['pages'] ?? 1))
                                    <a class="px-3 py-1 rounded border hover:bg-gray-50 dark:hover:bg-gray-700"
                                       href="{{ route('communication-hub.email-center.index', array_filter(['folder' => $folder ?: null, 'page' => $result['page'] + 1])) }}">Next</a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold mb-4">Compose email</h3>
                    @if ($canManage)
                        <form method="POST" action="{{ route('communication-hub.email-center.compose') }}" class="space-y-4" id="emailCenterComposeForm">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium">Recipient group</label>
                                <select name="recipient_group" id="emailRecipientGroup" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                    @foreach ($recipientGroups as $group)
                                        <option value="{{ $group['group_key'] }}" @selected(old('recipient_group', 'individual') === $group['group_key'])>{{ $group['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div id="individualEmailWrap">
                                <label class="block text-sm font-medium">Individual email</label>
                                <input type="email" name="individual_email" value="{{ old('individual_email') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
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
                                <label class="block text-sm font-medium">Mail template</label>
                                <select name="template_id" id="emailTemplateSelect" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                    <option value="">No template (write your own)</option>
                                    @foreach ($templates as $template)
                                        <option value="{{ $template['id'] }}"
                                                @selected((string) old('template_id') === (string) $template['id'])>
                                            {{ $template['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Subject</label>
                                <input type="text" name="subject" id="emailSubject" value="{{ old('subject') }}" required maxlength="500"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Message</label>
                                <textarea name="body_html" id="emailBody" rows="12" required
                                          class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('body_html') }}</textarea>
                                <p class="mt-1 text-xs text-gray-500">Write in plain text. Use <code>@{{member_name}}</code> for the recipient's name.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Schedule (optional)</label>
                                <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <button type="submit" id="emailCenterSendBtn"
                                        class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold disabled:opacity-70 disabled:cursor-wait">
                                    <span id="emailCenterSendBtnLabel">Send</span>
                                    <span id="emailCenterSendSpinner" class="hidden" aria-hidden="true">
                                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                        </svg>
                                    </span>
                                </button>
                                <p id="emailCenterSendStatus" class="text-sm text-gray-500 dark:text-gray-400" aria-live="polite"></p>
                            </div>
                        </form>
                    @else
                        <p class="text-sm text-gray-500">You can view email history, but composing requires communication manage access.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @php
        $emailTemplatesById = collect($templates)->mapWithKeys(function (array $t) {
            return [
                (string) $t['id'] => [
                    'subject' => $t['subject'],
                    'body_html' => $t['body_html'],
                ],
            ];
        });
    @endphp
    <script type="application/json" id="emailCenterTemplatesJson">{!! json_encode($emailTemplatesById) !!}</script>
    <script type="application/json" id="emailCenterComposeFlags">{!! json_encode(['compose_sent' => (bool) session('compose_sent')]) !!}</script>
    <script>
        (function () {
            var group = document.getElementById('emailRecipientGroup');
            var wrap = document.getElementById('individualEmailWrap');
            function syncGroup() {
                if (!group || !wrap) return;
                wrap.style.display = group.value === 'individual' ? '' : 'none';
            }
            if (group) {
                group.addEventListener('change', syncGroup);
                syncGroup();
            }

            var sel = document.getElementById('emailTemplateSelect');
            var templatesJson = document.getElementById('emailCenterTemplatesJson');
            var templatesById = templatesJson ? JSON.parse(templatesJson.textContent || '{}') : {};
            if (sel) {
                sel.addEventListener('change', function () {
                    var opt = sel.options[sel.selectedIndex];
                    if (!opt || !opt.value) return;
                    var tpl = templatesById[String(opt.value)] || {};
                    var subject = tpl.subject || '';
                    var body = tpl.body_html || '';
                    var subjectInput = document.getElementById('emailSubject');
                    var bodyInput = document.getElementById('emailBody');
                    if (subjectInput) subjectInput.value = subject;
                    if (bodyInput) bodyInput.value = body;
                });
            }

            var selectAll = document.getElementById('emailHistorySelectAll');
            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    document.querySelectorAll('.email-history-row-check').forEach(function (box) {
                        box.checked = selectAll.checked;
                    });
                });
            }

            var composeForm = document.getElementById('emailCenterComposeForm');
            var sendBtn = document.getElementById('emailCenterSendBtn');
            var sendLabel = document.getElementById('emailCenterSendBtnLabel');
            var sendSpinner = document.getElementById('emailCenterSendSpinner');
            var sendStatus = document.getElementById('emailCenterSendStatus');
            if (composeForm && sendBtn && sendLabel) {
                composeForm.addEventListener('submit', function () {
                    if (sendBtn.disabled) {
                        return false;
                    }
                    sendBtn.disabled = true;
                    sendBtn.setAttribute('aria-busy', 'true');
                    sendLabel.textContent = 'Sending…';
                    if (sendSpinner) sendSpinner.classList.remove('hidden');
                    if (sendStatus) {
                        sendStatus.textContent = 'Sending your email…';
                        sendStatus.className = 'text-sm text-indigo-600 dark:text-indigo-300';
                    }
                });
            }

            var composeFlagsEl = document.getElementById('emailCenterComposeFlags');
            var composeFlags = composeFlagsEl ? JSON.parse(composeFlagsEl.textContent || '{}') : {};
            if (composeFlags.compose_sent) {
                if (sendStatus) {
                    sendStatus.textContent = 'Sent';
                    sendStatus.className = 'text-sm font-semibold text-green-700 dark:text-green-300';
                }
                if (sendLabel) sendLabel.textContent = 'Send another';
            }
        })();
    </script>
</x-app-layout>
