@php
    $activities = is_array($activities ?? null) ? $activities : [];
    $icons = [
        'fa-church' => 'Church',
        'fa-donate' => 'Outreach',
        'fa-bible' => 'Bible',
        'fa-book' => 'Book',
        'fa-book-open' => 'Open book',
        'fa-hands' => 'Hands',
        'fa-praying-hands' => 'Prayer',
        'fa-users' => 'People',
        'fa-music' => 'Music',
        'fa-child' => 'Children',
        'fa-home' => 'Home',
        'fa-heart' => 'Heart',
        'fa-cross' => 'Cross',
        'fa-handshake' => 'Handshake',
        'fa-graduation-cap' => 'Teaching',
        'fa-globe' => 'Missions',
    ];
@endphp

<p class="text-sm text-gray-600 dark:text-gray-300">These cards appear under <strong>Activities</strong> on the homepage and on the Activities page. Fill the ones you want shown. Leave a card’s title and description blank to remove it. Uncheck <em>Show on site</em> to hide a card without deleting the text.</p>

@foreach ($activities as $index => $activity)
    @php $old = old('activities.'.$index, $activity); @endphp
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-4">
        <h4 class="font-semibold">Activity card {{ $index + 1 }}</h4>
        <input type="hidden" name="activities[{{ $index }}][id]" value="{{ (int) ($old['id'] ?? 0) }}">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium">Title</label>
                <input name="activities[{{ $index }}][title]" value="{{ $old['title'] ?? '' }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="Sunday Worship">
            </div>
            <div>
                <label class="block text-sm font-medium">Icon</label>
                <select name="activities[{{ $index }}][icon_class]" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    @foreach ($icons as $icon => $label)
                        <option value="{{ $icon }}" @selected(($old['icon_class'] ?? '') === $icon)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium">Meeting time (optional)</label>
                <input name="activities[{{ $index }}][meeting_schedule]" value="{{ $old['meeting_schedule'] ?? '' }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="Sunday 8:00 AM &amp; 10:30 AM">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium">Description</label>
                <textarea name="activities[{{ $index }}][description]" rows="3" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ $old['description'] ?? '' }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium">Read more link (optional)</label>
                <input name="activities[{{ $index }}][read_more_url]" value="{{ $old['read_more_url'] ?? '' }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="contact or /activity">
            </div>
            <div class="flex items-end">
                <label class="inline-flex items-center gap-2 text-sm font-medium">
                    <input type="hidden" name="activities[{{ $index }}][is_published]" value="0">
                    <input type="checkbox" name="activities[{{ $index }}][is_published]" value="1" class="rounded border-gray-300" @checked(filter_var($old['is_published'] ?? true, FILTER_VALIDATE_BOOLEAN))>
                    Show on site
                </label>
            </div>
        </div>
    </div>
@endforeach
