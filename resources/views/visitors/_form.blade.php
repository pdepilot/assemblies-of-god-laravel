@php
    $isEdit = isset($visitor);
    $action = $isEdit ? route('visitors.update', $visitor) : route('visitors.store');
    $v = $visitor ?? null;
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div>
        <x-input-label for="full_name" value="Full name" />
        <x-text-input id="full_name" name="full_name" type="text" class="block mt-1 w-full" required
                      :value="old('full_name', $v?->full_name ?? '')" />
        <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="phone" value="Phone" />
            <x-text-input id="phone" name="phone" type="text" class="block mt-1 w-full" required
                          :value="old('phone', $v?->phone ?? '')" />
        </div>
        <div>
            <x-input-label for="phone_alt" value="Alt phone" />
            <x-text-input id="phone_alt" name="phone_alt" type="text" class="block mt-1 w-full"
                          :value="old('phone_alt', $v?->phone_alt ?? '')" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" type="email" class="block mt-1 w-full"
                          :value="old('email', $v?->email ?? '')" />
        </div>
        <div>
            <x-input-label for="gender" value="Gender" />
            <select id="gender" name="gender" class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                @foreach (['unspecified', 'male', 'female', 'other'] as $gender)
                    <option value="{{ $gender }}" @selected(old('gender', $v?->gender ?? 'unspecified') === $gender)>{{ $gender }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="first_visit_date" value="First visit date" />
            <x-text-input id="first_visit_date" name="first_visit_date" type="date" class="block mt-1 w-full"
                          :value="old('first_visit_date', $v?->first_visit_date?->format('Y-m-d') ?? now()->toDateString())" />
        </div>
        <div>
            <x-input-label for="service_attended" value="Service attended" />
            <select id="service_attended" name="service_attended" class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                <option value="">Select</option>
                @foreach ($services as $service)
                    <option value="{{ $service }}" @selected(old('service_attended', $v?->service_attended ?? '') === $service)>{{ $service }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="how_heard" value="How they heard" />
            <select id="how_heard" name="how_heard" class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                <option value="">Select</option>
                @foreach ($howHeard as $option)
                    <option value="{{ $option }}" @selected(old('how_heard', $v?->how_heard ?? '') === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="interested_department" value="Interested department" />
            <select id="interested_department" name="interested_department" class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                <option value="">Select</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept }}" @selected(old('interested_department', $v?->interested_department ?? '') === $dept)>{{ $dept }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if ($isEdit)
        <div>
            <x-input-label for="follow_up_status" value="Follow-up status" />
            <select id="follow_up_status" name="follow_up_status" class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                @foreach ($followUpStatuses as $st)
                    <option value="{{ $st }}" @selected(old('follow_up_status', $v?->follow_up_status ?? 'new') === $st)>{{ $followUpLabels[$st] ?? $st }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div>
        <x-input-label for="prayer_request" value="Prayer request" />
        <textarea id="prayer_request" name="prayer_request" rows="2"
                  class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">{{ old('prayer_request', $v?->prayer_request ?? '') }}</textarea>
    </div>

    <div>
        <x-input-label for="notes" value="Notes" />
        <textarea id="notes" name="notes" rows="2"
                  class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">{{ old('notes', $v?->notes ?? '') }}</textarea>
    </div>

    <div>
        <x-input-label for="photo" value="Photo" />
        <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"
               class="block mt-1 w-full text-sm text-gray-700 dark:text-gray-300" />
        @if ($isEdit && $v?->photo_path)
            <label class="mt-2 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                <input type="checkbox" name="remove_photo" value="1" /> Remove current photo
            </label>
        @endif
    </div>

    <div class="flex gap-3">
        <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Save</button>
        <a href="{{ route('visitors.index') }}" class="px-4 py-2 rounded-md border border-gray-300 dark:border-gray-600 text-sm">Cancel</a>
    </div>
</form>
