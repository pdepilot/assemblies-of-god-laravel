<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Newsletter Subscribers</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Emails collected from the website footer and subscribe forms.</p>
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

            <div class="grid gap-3 sm:grid-cols-3">
                @foreach ([
                    ['label' => 'Total', 'value' => $stats['total'] ?? 0],
                    ['label' => 'Active', 'value' => $stats['active'] ?? 0],
                    ['label' => 'Unsubscribed', 'value' => $stats['unsubscribed'] ?? 0],
                ] as $card)
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">{{ $card['label'] }}</p>
                        <p class="text-2xl font-semibold mt-1">{{ $card['value'] }}</p>
                    </div>
                @endforeach
            </div>

            <form method="GET" action="{{ route('newsletter-subscribers.index') }}" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[180px]">
                    <label class="block text-sm font-medium">Search</label>
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Email…" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                </div>
                <div>
                    <label class="block text-sm font-medium">Status</label>
                    <select name="status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        <option value="">All</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                        <option value="unsubscribed" @selected(($filters['status'] ?? '') === 'unsubscribed')>Unsubscribed</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium">Source</label>
                    <input type="text" name="source" value="{{ $filters['source'] ?? '' }}" placeholder="footer, sermons…" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                </div>
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm">Filter</button>
            </form>

            @if ($canManage)
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4" id="send-newsletter">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="font-semibold flex items-center gap-2"><i class="fas fa-paper-plane text-indigo-600"></i> Send Newsletter</h3>
                            <p class="text-sm text-gray-500 mt-1">Select active subscribers below, or send to every active subscriber.</p>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-300"><span id="nsSelectedCount">0</span> selected</p>
                    </div>

                    <form method="POST" action="{{ route('newsletter-subscribers.send') }}" id="nsSendForm" class="space-y-4" data-active-count="{{ (int) ($stats['active'] ?? 0) }}">
                        @csrf
                        <input type="hidden" name="audience" id="nsAudience" value="selected">
                        <div id="nsSelectedInputs"></div>

                        <div>
                            <label class="block text-sm font-medium">Template (optional)</label>
                            <select name="template_id" id="nsSendTemplate" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                <option value="">No template (write your own)</option>
                                @foreach ($templates as $tpl)
                                    <option
                                        value="{{ $tpl['id'] }}"
                                        data-subject="{{ e($tpl['subject'] ?? '') }}"
                                        data-body="{{ e($tpl['display_body'] ?? '') }}"
                                        @selected((string) old('template_id') === (string) $tpl['id'])
                                    >{{ $tpl['name'] ?? ('Template #'.$tpl['id']) }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Choosing a template fills the subject and message. You can still edit them before sending.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Subject</label>
                            <input type="text" name="subject" id="nsSendSubject" value="{{ old('subject') }}" maxlength="200" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="Newsletter subject">
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Message</label>
                            <textarea name="body" id="nsSendBody" rows="8" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="Write your newsletter message…">{{ old('body') }}</textarea>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button type="submit" id="btnSendSelected" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700 disabled:opacity-50" disabled>
                                <i class="fas fa-paper-plane"></i> Send to Selected
                            </button>
                            <button type="submit" id="btnSendAll" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                                <i class="fas fa-users"></i> Send to All Active ({{ $stats['active'] ?? 0 }})
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left border-b bg-gray-50 dark:bg-gray-900/40">
                                @if ($canManage)
                                    <th class="px-4 py-3 w-10">
                                        <input type="checkbox" id="nsSelectAll" class="rounded border-gray-300" title="Select all active on this page" aria-label="Select all active on this page">
                                    </th>
                                @endif
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Source</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Subscribed</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($result['items'] as $row)
                            <tr class="border-b">
                                @if ($canManage)
                                    <td class="px-4 py-3">
                                        @if (($row['status'] ?? '') === 'active')
                                            <input type="checkbox" class="ns-row-check rounded border-gray-300" value="{{ $row['id'] }}" data-email="{{ $row['email'] }}" aria-label="Select {{ $row['email'] }}">
                                        @endif
                                    </td>
                                @endif
                                <td class="px-4 py-3">
                                    <a href="{{ route('newsletter-subscribers.show', $row['id']) }}" class="text-indigo-600 hover:underline">{{ $row['email'] }}</a>
                                </td>
                                <td class="px-4 py-3">{{ $row['source'] ?? '—' }}</td>
                                <td class="px-4 py-3 capitalize">{{ $row['status'] }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ !empty($row['subscribed_at']) ? \Illuminate\Support\Carbon::parse($row['subscribed_at'])->format('d M Y H:i') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex flex-wrap justify-end gap-2">
                                        <a href="{{ route('newsletter-subscribers.show', $row['id']) }}" class="px-3 py-1.5 rounded-md border text-xs">Open</a>
                                        @if ($canManage)
                                            <form method="POST" action="{{ route('newsletter-subscribers.toggle-status', $row['id']) }}">
                                                @csrf
                                                <button class="px-3 py-1.5 rounded-md border text-xs">
                                                    {{ ($row['status'] ?? '') === 'active' ? 'Unsubscribe' : 'Reactivate' }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canManage ? 6 : 5 }}" class="px-4 py-10 text-center text-gray-500">No subscribers match your filters.</td>
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
                                <a class="px-3 py-1 rounded border" href="{{ route('newsletter-subscribers.index', array_merge($filters, ['page' => $result['page'] - 1])) }}">Previous</a>
                            @endif
                            @if ($result['page'] < $result['pages'])
                                <a class="px-3 py-1 rounded border" href="{{ route('newsletter-subscribers.index', array_merge($filters, ['page' => $result['page'] + 1])) }}">Next</a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($canManage)
        <script>
            (function () {
                var form = document.getElementById('nsSendForm');
                if (!form) return;

                var selectedInputs = document.getElementById('nsSelectedInputs');
                var countEl = document.getElementById('nsSelectedCount');
                var sendSelectedBtn = document.getElementById('btnSendSelected');
                var sendAllBtn = document.getElementById('btnSendAll');
                var audienceInput = document.getElementById('nsAudience');
                var selectAll = document.getElementById('nsSelectAll');
                var subjectInput = document.getElementById('nsSendSubject');
                var bodyInput = document.getElementById('nsSendBody');
                var templateSelect = document.getElementById('nsSendTemplate');

                function selectedChecks() {
                    return Array.prototype.slice.call(document.querySelectorAll('.ns-row-check:checked'));
                }

                function syncSelection() {
                    var checks = selectedChecks();
                    selectedInputs.innerHTML = '';
                    checks.forEach(function (cb) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'subscriber_ids[]';
                        input.value = cb.value;
                        selectedInputs.appendChild(input);
                    });
                    countEl.textContent = String(checks.length);
                    sendSelectedBtn.disabled = checks.length === 0;
                }

                document.addEventListener('change', function (e) {
                    if (e.target && e.target.classList && e.target.classList.contains('ns-row-check')) {
                        syncSelection();
                    }
                });

                if (selectAll) {
                    selectAll.addEventListener('change', function () {
                        document.querySelectorAll('.ns-row-check').forEach(function (cb) {
                            cb.checked = selectAll.checked;
                        });
                        syncSelection();
                    });
                }

                if (templateSelect) {
                    templateSelect.addEventListener('change', function () {
                        var opt = templateSelect.options[templateSelect.selectedIndex];
                        if (!opt || !opt.value) return;
                        var subject = opt.getAttribute('data-subject') || '';
                        var body = opt.getAttribute('data-body') || '';
                        if (subject && (!subjectInput.value || subjectInput.dataset.fromTemplate === '1')) {
                            subjectInput.value = subject;
                            subjectInput.dataset.fromTemplate = '1';
                        }
                        if (body && (!bodyInput.value || bodyInput.dataset.fromTemplate === '1')) {
                            bodyInput.value = body;
                            bodyInput.dataset.fromTemplate = '1';
                        }
                    });
                }

                [subjectInput, bodyInput].forEach(function (el) {
                    if (!el) return;
                    el.addEventListener('input', function () {
                        delete el.dataset.fromTemplate;
                    });
                });

                sendSelectedBtn.addEventListener('click', function () {
                    audienceInput.value = 'selected';
                });

                sendAllBtn.addEventListener('click', function (e) {
                    audienceInput.value = 'all_active';
                    var total = Number(form.getAttribute('data-active-count') || '0');
                    if (total < 1) {
                        e.preventDefault();
                        if (window.CMS && CMS.showToast) CMS.showToast('There are no active subscribers to email.', 'warning');
                        else alert('There are no active subscribers to email.');
                        return;
                    }
                    e.preventDefault();
                    if (!window.CMS || !CMS.confirm) {
                        if (window.CMS && CMS.showToast) CMS.showToast('Confirm dialog unavailable. Refresh the page.', 'error');
                        return;
                    }
                    CMS.confirm({
                        title: 'Send newsletter',
                        message: 'Send this newsletter to all ' + total + ' active subscribers?',
                        confirmLabel: 'Send to all',
                        tone: 'primary'
                    }).then(function (ok) {
                        if (!ok) return;
                        form.setAttribute('data-confirm-accepted', '1');
                        form.requestSubmit(sendAllBtn);
                    });
                });

                form.addEventListener('submit', function (e) {
                    if (audienceInput.value === 'selected' && selectedChecks().length === 0) {
                        e.preventDefault();
                        if (window.CMS && CMS.showToast) CMS.showToast('Select at least one active subscriber.', 'warning');
                        else alert('Select at least one active subscriber.');
                        return;
                    }
                    var subject = (subjectInput.value || '').trim();
                    var body = (bodyInput.value || '').trim();
                    var hasTemplate = !!(templateSelect && templateSelect.value);
                    if (!subject && !hasTemplate) {
                        e.preventDefault();
                        if (window.CMS && CMS.showToast) CMS.showToast('Please enter a subject or choose a template.', 'warning');
                        else alert('Please enter a subject or choose a template.');
                        return;
                    }
                    if (body.replace(/<[^>]+>/g, '').trim().length < 5 && !hasTemplate) {
                        e.preventDefault();
                        if (window.CMS && CMS.showToast) CMS.showToast('Please write a message of at least 5 characters or choose a template.', 'warning');
                        else alert('Please write a message of at least 5 characters or choose a template.');
                    }
                });

                syncSelection();
            })();
        </script>
    @endif
</x-app-layout>
