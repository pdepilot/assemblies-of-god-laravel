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
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
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
                    <h3 class="font-semibold mb-4">Message history</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left border-b">
                                    <th class="py-2">Subject</th>
                                    <th>Recipient</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse ($result['items'] as $row)
                                <tr class="border-b">
                                    <td class="py-2">{{ $row['subject'] !== '' ? $row['subject'] : '—' }}</td>
                                    <td>{{ $row['recipient_name'] !== '' ? $row['recipient_name'] : $row['recipient'] }}</td>
                                    <td>{{ $row['status'] }}</td>
                                    <td class="whitespace-nowrap">{{ $row['sent_at'] !== '' ? $row['sent_at'] : $row['created_at'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-6 text-center text-gray-500">No messages in this folder.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if (($result['pages'] ?? 1) > 1)
                        <div class="mt-4 flex justify-between text-sm text-gray-500">
                            <span>Page {{ $result['page'] }} of {{ $result['pages'] }}</span>
                            <div class="flex gap-2">
                                @if ($result['page'] > 1)
                                    <a class="px-3 py-1 rounded border" href="{{ route('communication-hub.email-center.index', ['folder' => $folder, 'page' => $result['page'] - 1]) }}">Previous</a>
                                @endif
                                @if ($result['page'] < $result['pages'])
                                    <a class="px-3 py-1 rounded border" href="{{ route('communication-hub.email-center.index', ['folder' => $folder, 'page' => $result['page'] + 1]) }}">Next</a>
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
                            <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Send</button>
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
        })();
    </script>
</x-app-layout>
