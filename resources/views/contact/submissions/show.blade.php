<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Contact Message</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $submission['submission_code'] }} · {{ ucfirst($submission['inquiry_type']) }}</p>
            </div>
            <a href="{{ route('contact.submissions.index') }}" class="text-sm text-indigo-600 hover:underline">← Back to inbox</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">{{ $errors->first() }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 capitalize">{{ str_replace('_', ' ', $submission['status']) }}</span>
                    <span class="px-2 py-1 rounded bg-indigo-50 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-200 capitalize">{{ $submission['inquiry_type'] }}</span>
                    @if (!empty($submission['ack_sent_at']))
                        <span class="px-2 py-1 rounded bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-200">Auto-reply sent</span>
                    @endif
                </div>

                <div>
                    <p class="text-lg font-semibold">{{ $submission['full_name'] }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        <a href="mailto:{{ $submission['email'] }}" class="text-indigo-600 hover:underline">{{ $submission['email'] }}</a>
                        @if (!empty($submission['phone']))
                            · {{ $submission['phone'] }}
                        @endif
                    </p>
                    <p class="text-xs text-gray-500 mt-1">Received {{ \Illuminate\Support\Carbon::parse($submission['created_at'])->format('d M Y H:i') }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500">Subject</h3>
                    <p class="mt-1 font-medium">{{ $submission['subject'] }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500">Message</h3>
                    <p class="mt-2 whitespace-pre-wrap leading-relaxed">{{ $submission['message'] }}</p>
                </div>

                @if (!empty($submission['reply_body']))
                    <div class="border-t pt-4">
                        <h3 class="text-sm font-medium text-gray-500">Previous reply</h3>
                        @if (!empty($submission['reply_subject']))
                            <p class="mt-1 font-medium">{{ $submission['reply_subject'] }}</p>
                        @endif
                        <p class="mt-2 whitespace-pre-wrap text-sm">{{ $submission['reply_body'] }}</p>
                        @if (!empty($submission['replied_at']))
                            <p class="text-xs text-gray-500 mt-2">Sent {{ \Illuminate\Support\Carbon::parse($submission['replied_at'])->format('d M Y H:i') }}</p>
                        @endif
                    </div>
                @endif
            </div>

            @php
                $mailto = 'mailto:'.rawurlencode((string) $submission['email'])
                    .'?subject='.rawurlencode('Re: '.(string) $submission['subject'])
                    .'&body='.rawurlencode('Dear '.(string) $submission['full_name'].",\n\nThank you for contacting AGC Ikenebgu.\n\n");
            @endphp

            @if ($canManage)
                <form method="POST" action="{{ route('contact.submissions.reply', $submission['id']) }}" id="reply-composer" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4 scroll-mt-24">
                    @csrf
                    <div>
                        <h3 class="font-semibold flex items-center gap-2"><i class="fas fa-paper-plane text-indigo-600"></i> Reply by Email</h3>
                        <p class="text-sm text-gray-500 mt-1">Sends a branded church email to {{ $submission['email'] }} and marks this message as Replied.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Subject</label>
                        <input type="text" name="reply_subject" value="{{ old('reply_subject', 'Re: '.$submission['subject']) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900" maxlength="255">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Message</label>
                        <textarea name="reply_body" rows="8" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="Write your reply…">{{ old('reply_body') }}</textarea>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ $mailto }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 dark:border-gray-600 text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                            <i class="fas fa-envelope-open-text"></i> Open in Mail App
                        </a>
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">
                            <i class="fas fa-paper-plane"></i> Send Reply
                        </button>
                    </div>
                </form>

                <form method="POST" action="{{ route('contact.submissions.update', $submission['id']) }}" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                    @csrf
                    @method('PUT')
                    <h3 class="font-semibold">Follow-up status</h3>
                    <div>
                        <label class="block text-sm font-medium">Status</label>
                        <select name="status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            @foreach ($statuses as $s)
                                <option value="{{ $s }}" @selected(old('status', $submission['status']) === $s)>{{ str_replace('_', ' ', $s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Internal notes</label>
                        <textarea name="admin_notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="Pastoral notes, call-back details, prayer team assignment…">{{ old('admin_notes', $submission['admin_notes']) }}</textarea>
                    </div>
                    <button class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700">
                        <i class="fas fa-save"></i> Save Update
                    </button>
                </form>
            @else
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <a href="{{ $mailto }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 dark:border-gray-600 text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                        <i class="fas fa-envelope-open-text"></i> Open in Mail App
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
