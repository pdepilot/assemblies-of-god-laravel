@php
    $speaker = $speaker ?? [];
@endphp

<div>
    <label class="block text-sm font-medium" for="full_name">Full name</label>
    <input id="full_name" name="full_name" value="{{ old('full_name', $speaker['full_name'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900" required>
</div>
<div>
    <label class="block text-sm font-medium" for="crusade_year">Crusade year</label>
    <input id="crusade_year" type="number" name="crusade_year" value="{{ old('crusade_year', $speaker['crusade_year'] ?? date('Y')) }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900" required>
</div>
<div>
    <label class="block text-sm font-medium" for="ministry">Ministry</label>
    <input id="ministry" name="ministry" value="{{ old('ministry', $speaker['ministry'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
</div>
<div>
    <label class="block text-sm font-medium" for="country">Country</label>
    <input id="country" name="country" value="{{ old('country', $speaker['country'] ?? 'Nigeria') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
</div>
<div>
    <label class="block text-sm font-medium" for="speaker_type">Speaker type</label>
    <select id="speaker_type" name="speaker_type" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
        @foreach (['upcoming', 'past', 'host', 'worship'] as $type)
            <option value="{{ $type }}" @selected(old('speaker_type', $speaker['speaker_type'] ?? 'upcoming') === $type)>{{ ucfirst($type) }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="block text-sm font-medium" for="status">Status</label>
    <select id="status" name="status" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
        @foreach (['pending', 'confirmed', 'cancelled'] as $status)
            <option value="{{ $status }}" @selected(old('status', $speaker['status'] ?? 'pending') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="block text-sm font-medium" for="topic">Topic</label>
    <input id="topic" name="topic" value="{{ old('topic', $speaker['topic'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">
</div>
<div>
    <label class="block text-sm font-medium" for="bio">Bio</label>
    <textarea id="bio" name="bio" rows="5" class="mt-1 w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900">{{ old('bio', $speaker['bio'] ?? '') }}</textarea>
</div>
