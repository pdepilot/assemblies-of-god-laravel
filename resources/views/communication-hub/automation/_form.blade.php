@php
    $rule = $rule ?? null;
@endphp
<div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
    <div>
        <label for="name" class="block text-sm font-medium mb-1">Name</label>
        <input id="name" name="name" value="{{ old('name', $rule['name'] ?? '') }}" required class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
    </div>
    @if (! $rule)
        <div>
            <label for="rule_key" class="block text-sm font-medium mb-1">Rule key (optional)</label>
            <input id="rule_key" name="rule_key" value="{{ old('rule_key') }}" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="auto-generated from name">
        </div>
    @endif
    <div>
        <label for="description" class="block text-sm font-medium mb-1">Description</label>
        <textarea id="description" name="description" rows="2" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('description', $rule['description'] ?? '') }}</textarea>
    </div>
    <div>
        <label for="trigger_event" class="block text-sm font-medium mb-1">Trigger event</label>
        <input id="trigger_event" name="trigger_event" value="{{ old('trigger_event', $rule['trigger_event'] ?? '') }}" required class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="e.g. member.birthday">
    </div>
    <div class="grid gap-4 md:grid-cols-3">
        <div>
            <label for="channel" class="block text-sm font-medium mb-1">Channel</label>
            <select id="channel" name="channel" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                @foreach ($channels as $channel)
                    <option value="{{ $channel }}" @selected(old('channel', $rule['channel'] ?? 'email') === $channel)>{{ $channel }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="template_slug" class="block text-sm font-medium mb-1">Template slug</label>
            <input id="template_slug" name="template_slug" value="{{ old('template_slug', $rule['template_slug'] ?? '') }}" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
        </div>
        <div>
            <label for="priority" class="block text-sm font-medium mb-1">Priority</label>
            <input id="priority" type="number" name="priority" value="{{ old('priority', $rule['priority'] ?? 100) }}" min="1" max="9999" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
        </div>
    </div>
    <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_enabled" value="1" @checked(old('is_enabled', $rule['is_enabled'] ?? false))>
        Enabled
    </label>
</div>
