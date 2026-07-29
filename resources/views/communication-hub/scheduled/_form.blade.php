@php
    $message = $message ?? null;
    $scheduledValue = old('scheduled_at', $message['scheduled_at'] ?? '');
    if (is_string($scheduledValue) && $scheduledValue !== '') {
        $scheduledValue = str_replace(' ', 'T', substr($scheduledValue, 0, 16));
    }
@endphp
<div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
    <div class="grid gap-4 md:grid-cols-3">
        <div>
            <label for="channel" class="block text-sm font-medium mb-1">Channel</label>
            <select id="channel" name="channel" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                @foreach ($channels as $channel)
                    <option value="{{ $channel }}" @selected(old('channel', $message['channel'] ?? 'email') === $channel)>{{ $channel }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="priority" class="block text-sm font-medium mb-1">Priority</label>
            <select id="priority" name="priority" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                @foreach ($priorities as $priority)
                    <option value="{{ $priority }}" @selected(old('priority', $message['priority'] ?? 'normal') === $priority)>{{ $priority }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="recurrence" class="block text-sm font-medium mb-1">Recurrence</label>
            <select id="recurrence" name="recurrence" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                @foreach ($recurrences as $recurrence)
                    <option value="{{ $recurrence }}" @selected(old('recurrence', $message['recurrence'] ?? 'none') === $recurrence)>{{ $recurrence }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div>
        <label for="recipient_group_key" class="block text-sm font-medium mb-1">Audience group</label>
        <select id="recipient_group_key" name="recipient_group_key" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            <option value="">— Select audience —</option>
            @foreach ($audiences as $audience)
                <option value="{{ $audience['group_key'] }}" @selected(old('recipient_group_key', $message['recipient_group_key'] ?? '') === $audience['group_key'])>
                    {{ $audience['name'] }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="subject" class="block text-sm font-medium mb-1">Subject / title</label>
        <input id="subject" name="subject" value="{{ old('subject', $message['subject'] ?? '') }}" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
    </div>
    <div>
        <label for="body_html" class="block text-sm font-medium mb-1">Message body</label>
        <textarea id="body_html" name="body_html" rows="8" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('body_html', $message['body_html'] ?? $message['body_text'] ?? '') }}</textarea>
    </div>
    <div>
        <label for="scheduled_at" class="block text-sm font-medium mb-1">Send at</label>
        <input id="scheduled_at" type="datetime-local" name="scheduled_at" value="{{ $scheduledValue }}" required class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
    </div>
</div>
