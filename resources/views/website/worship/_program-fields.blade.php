@php
    $programs = is_array($programs ?? null) ? $programs : [];
    $location = is_array($location ?? null) ? $location : ['map_query' => ''];
@endphp

<p class="text-sm text-gray-600 dark:text-gray-300">These cards appear under <strong>Our Worship</strong> on the homepage. Fill the ones you want shown. Leave a card’s title and day blank to hide it. Button links can be a page name such as <code>blog</code>, <code>contact</code>, or <code>event</code>.</p>

@foreach ($programs as $index => $program)
    @php $old = old('programs.'.$index, $program); @endphp
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-4">
        <h4 class="font-semibold">Worship card {{ $index + 1 }}</h4>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium">Day / rhythm</label>
                <input name="programs[{{ $index }}][day]" value="{{ $old['day'] ?? '' }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="Every Sunday">
            </div>
            <div>
                <label class="block text-sm font-medium">Icon</label>
                <select name="programs[{{ $index }}][icon]" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    @foreach (['fa-calendar-day' => 'Calendar', 'fa-bible' => 'Bible', 'fa-praying-hands' => 'Prayer', 'fa-church' => 'Church', 'fa-book-bible' => 'Teaching', 'fa-music' => 'Music', 'fa-users' => 'People', 'fa-clock' => 'Clock'] as $icon => $label)
                        <option value="{{ $icon }}" @selected(($old['icon'] ?? '') === $icon)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium">Title</label>
                <input name="programs[{{ $index }}][title]" value="{{ $old['title'] ?? '' }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="Sunday Worship Services">
            </div>
            <div>
                <label class="block text-sm font-medium">First time</label>
                <input name="programs[{{ $index }}][time_primary]" value="{{ $old['time_primary'] ?? '' }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="8:00 AM">
            </div>
            <div>
                <label class="block text-sm font-medium">First time note</label>
                <input name="programs[{{ $index }}][note_primary]" value="{{ $old['note_primary'] ?? '' }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="First Service — Prayer & Praise">
            </div>
            <div>
                <label class="block text-sm font-medium">Second time (optional)</label>
                <input name="programs[{{ $index }}][time_secondary]" value="{{ $old['time_secondary'] ?? '' }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="10:30 AM">
            </div>
            <div>
                <label class="block text-sm font-medium">Second time note</label>
                <input name="programs[{{ $index }}][note_secondary]" value="{{ $old['note_secondary'] ?? '' }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium">Details</label>
                <textarea name="programs[{{ $index }}][body]" rows="3" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ $old['body'] ?? '' }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium">Button label</label>
                <input name="programs[{{ $index }}][cta_label]" value="{{ $old['cta_label'] ?? '' }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="Prayer Requests">
            </div>
            <div>
                <label class="block text-sm font-medium">Button link</label>
                <input name="programs[{{ $index }}][cta_url]" value="{{ $old['cta_url'] ?? '' }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="contact">
            </div>
        </div>
    </div>
@endforeach

<div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-3">
    <h4 class="font-semibold">Church location map</h4>
    <p class="text-sm text-gray-600 dark:text-gray-300">This map sits under the worship cards. Address, phone, and email on the card still come from church contact details.</p>
    <div>
        <label class="block text-sm font-medium">Map search</label>
        <input name="worship_map_query" value="{{ old('worship_map_query', $location['map_query'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" placeholder="11 Archdeacon Dennis Street, Ikenegbu, Owerri">
        <p class="mt-1 text-xs text-gray-500">Street, area, and city Google Maps should pin.</p>
    </div>
</div>
