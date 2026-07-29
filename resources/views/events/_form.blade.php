@php
    $isEdit = isset($event);
    $action = $isEdit ? route('events.update', $event) : route('events.store');
    $eventMeta = is_array($eventData ?? null) ? $eventData : [];
    $previewUrl = $eventMeta['image_url'] ?? null;
@endphp
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6" id="eventForm">
    @csrf @if($isEdit) @method('PUT') @endif
    <div><x-input-label for="title" value="Title" /><x-text-input id="title" name="title" class="block mt-1 w-full" required :value="old('title', $eventMeta['title'] ?? '')" /><x-input-error :messages="$errors->get('title')" /></div>
    <div><x-input-label for="description" value="Description" /><textarea id="description" name="description" rows="3" class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('description', $eventMeta['description'] ?? '') }}</textarea></div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div><x-input-label for="event_date" value="Event date" /><x-text-input id="event_date" name="event_date" type="date" class="block mt-1 w-full" required :value="old('event_date', $eventMeta['event_date'] ?? now()->toDateString())" /></div>
        <div><x-input-label for="event_time" value="Time" /><x-text-input id="event_time" name="event_time" type="time" class="block mt-1 w-full" :value="old('event_time', $eventMeta['event_time'] ?? '')" /></div>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div><x-input-label for="category" value="Category" /><select id="category" name="category" class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">@foreach($categories as $cat)<option value="{{ $cat }}" @selected(old('category', $eventMeta['category'] ?? 'other')===$cat)>{{ $categoryLabels[$cat] ?? $cat }}</option>@endforeach</select></div>
        <div><x-input-label for="status" value="Status" /><select id="status" name="status" class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">@foreach($statuses as $st)<option value="{{ $st }}" @selected(old('status', $eventMeta['status'] ?? 'upcoming')===$st)>{{ ucfirst($st) }}</option>@endforeach</select></div>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div><x-input-label for="public_category_label" value="Public badge label" /><x-text-input id="public_category_label" name="public_category_label" class="block mt-1 w-full" :value="old('public_category_label', $eventMeta['public_category_label'] ?? '')" placeholder="Worship" /></div>
        <div><x-input-label for="icon_class" value="Icon" /><select id="icon_class" name="icon_class" class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">@foreach($icons as $icon)<option value="{{ $icon }}" @selected(old('icon_class', $eventMeta['icon_class'] ?? 'fa-church')===$icon)>{{ $icon }}</option>@endforeach</select></div>
    </div>
    <div><x-input-label for="location" value="Location" /><x-text-input id="location" name="location" class="block mt-1 w-full" :value="old('location', $eventMeta['location'] ?? '')" /></div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div><x-input-label for="recurrence_label" value="Recurrence label" /><x-text-input id="recurrence_label" name="recurrence_label" class="block mt-1 w-full" :value="old('recurrence_label', $eventMeta['recurrence_label'] ?? '')" placeholder="Every Sunday" /></div>
        <div><x-input-label for="schedule_display" value="Schedule display" /><x-text-input id="schedule_display" name="schedule_display" class="block mt-1 w-full" :value="old('schedule_display', $eventMeta['schedule_display'] ?? '')" /></div>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div><x-input-label for="cta_text" value="Button text" /><x-text-input id="cta_text" name="cta_text" class="block mt-1 w-full" :value="old('cta_text', $eventMeta['cta_text'] ?? 'Learn more')" /></div>
        <div><x-input-label for="cta_url" value="Button link" /><x-text-input id="cta_url" name="cta_url" class="block mt-1 w-full" :value="old('cta_url', $eventMeta['cta_url'] ?? 'contact')" /></div>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div><x-input-label for="sort_order" value="Sort order" /><x-text-input id="sort_order" name="sort_order" type="number" min="0" class="block mt-1 w-full" :value="old('sort_order', $eventMeta['sort_order'] ?? 0)" /></div>
        <div><x-input-label for="expected_attendance" value="Expected attendance" /><x-text-input id="expected_attendance" name="expected_attendance" type="number" min="0" class="block mt-1 w-full" :value="old('expected_attendance', $eventMeta['expected_attendance'] ?? 0)" /></div>
    </div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_recurring" value="1" @checked(old('is_recurring', $eventMeta['is_recurring'] ?? false)) /> Recurring event</label>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $eventMeta['is_published'] ?? true)) /> Published on website</label>

    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-4">
        <x-input-label value="Event image (homepage flier)" />
        <div id="eventImagePreview" class="rounded-md overflow-hidden bg-gray-100 dark:bg-gray-900 min-h-[160px] flex items-center justify-center">
            @if ($previewUrl)
                <img src="{{ $previewUrl }}" alt="Event image preview" class="max-h-56 w-full object-cover" id="eventImagePreviewImg">
            @else
                <p class="text-sm text-gray-500 px-4 text-center" id="eventImagePreviewPlaceholder">Upload an image for this event.</p>
            @endif
        </div>
        <div>
            <x-input-label for="image" value="Upload image" />
            <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="block mt-1 w-full text-sm" />
        </div>
        @if ($isEdit && ($eventMeta['is_custom_image'] ?? false))
            <label class="flex items-center gap-2 text-sm text-red-700 dark:text-red-300">
                <input type="checkbox" name="remove_image" value="1" @checked(old('remove_image')) /> Remove uploaded image
            </label>
        @endif
        <p class="text-xs text-gray-500">JPG, PNG, or WebP up to 5 MB. Upload only — no URL or path.</p>
    </div>

    <div class="flex gap-3"><button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Save</button><a href="{{ route('events.index') }}" class="px-4 py-2 rounded-md border text-sm">Cancel</a></div>
</form>
<script>
    (function () {
        var fileInput = document.getElementById('image');
        var preview = document.getElementById('eventImagePreview');
        if (!fileInput || !preview) return;
        fileInput.addEventListener('change', function () {
            var file = fileInput.files && fileInput.files[0];
            if (!file) return;
            preview.innerHTML = '<img src="' + URL.createObjectURL(file) + '" alt="Event image preview" class="max-h-56 w-full object-cover" id="eventImagePreviewImg">';
        });
    })();
</script>
