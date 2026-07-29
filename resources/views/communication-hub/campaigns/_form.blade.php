@php
    $campaign = $campaign ?? null;
    $scheduledValue = old('scheduled_at', $campaign['scheduled_at'] ?? '');
    if (is_string($scheduledValue) && $scheduledValue !== '') {
        $scheduledValue = str_replace(' ', 'T', substr($scheduledValue, 0, 16));
    }
@endphp
<div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
    <div>
        <label for="name" class="block text-sm font-medium mb-1">Name</label>
        <input id="name" name="name" value="{{ old('name', $campaign['name'] ?? '') }}" required class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
    </div>
    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label for="campaign_type" class="block text-sm font-medium mb-1">Type</label>
            <select id="campaign_type" name="campaign_type" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected(old('campaign_type', $campaign['campaign_type'] ?? 'email') === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="status" class="block text-sm font-medium mb-1">Status</label>
            <select id="status" name="status" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(old('status', $campaign['status'] ?? 'draft') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div>
        <label for="audience_group_key" class="block text-sm font-medium mb-1">Audience group</label>
        <select id="audience_group_key" name="audience_group_key" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            <option value="">— Select audience —</option>
            @foreach ($audiences as $audience)
                <option value="{{ $audience['group_key'] }}" @selected(old('audience_group_key', $campaign['audience_group_key'] ?? '') === $audience['group_key'])>
                    {{ $audience['name'] }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="subject" class="block text-sm font-medium mb-1">Subject</label>
        <input id="subject" name="subject" value="{{ old('subject', $campaign['subject'] ?? '') }}" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
    </div>
    <div>
        <label for="body_html" class="block text-sm font-medium mb-1">Body</label>
        <textarea id="body_html" name="body_html" rows="8" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('body_html', $campaign['body_html'] ?? '') }}</textarea>
    </div>
    <div>
        <label for="scheduled_at" class="block text-sm font-medium mb-1">Schedule</label>
        <input id="scheduled_at" type="datetime-local" name="scheduled_at" value="{{ $scheduledValue }}" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
    </div>
</div>
