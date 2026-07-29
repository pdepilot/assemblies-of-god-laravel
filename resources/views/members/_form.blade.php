@php
    $isEdit = isset($member);
    $action = $isEdit ? route('members.update', $member) : route('members.store');
    $m = $member ?? null;
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="first_name" value="First name" />
            <x-text-input id="first_name" name="first_name" type="text" class="block mt-1 w-full"
                          :value="old('first_name', $m?->first_name ?? '')" />
        </div>
        <div>
            <x-input-label for="last_name" value="Last name" />
            <x-text-input id="last_name" name="last_name" type="text" class="block mt-1 w-full"
                          :value="old('last_name', $m?->last_name ?? '')" />
        </div>
    </div>

    <div>
        <x-input-label for="full_name" value="Full name (optional if first/last provided)" />
        <x-text-input id="full_name" name="full_name" type="text" class="block mt-1 w-full"
                      :value="old('full_name', $m?->full_name ?? '')" />
        <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="phone" value="Phone" />
            <x-text-input id="phone" name="phone" type="text" class="block mt-1 w-full" required
                          :value="old('phone', $m?->phone ?? '')" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="phone_alt" value="Alt phone" />
            <x-text-input id="phone_alt" name="phone_alt" type="text" class="block mt-1 w-full"
                          :value="old('phone_alt', $m?->phone_alt ?? '')" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" type="email" class="block mt-1 w-full"
                          :value="old('email', $m?->email ?? '')" />
        </div>
        <div>
            <x-input-label for="gender" value="Gender" />
            <select id="gender" name="gender" class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                @foreach (['unspecified', 'male', 'female', 'other'] as $gender)
                    <option value="{{ $gender }}" @selected(old('gender', $m?->gender ?? 'unspecified') === $gender)>{{ $gender }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <x-input-label for="date_of_birth" value="Date of birth" />
            <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="block mt-1 w-full"
                          :value="old('date_of_birth', $m?->date_of_birth?->format('Y-m-d') ?? '')" />
        </div>
        <div>
            <x-input-label for="marital_status" value="Marital status" />
            <select id="marital_status" name="marital_status" class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                @foreach (['unspecified', 'single', 'married', 'divorced', 'widowed', 'widow', 'widower', 'separated'] as $ms)
                    <option value="{{ $ms }}" @selected(old('marital_status', $m?->marital_status ?? 'unspecified') === $ms)>{{ $ms }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="wedding_date" value="Wedding date" />
            <x-text-input id="wedding_date" name="wedding_date" type="date" class="block mt-1 w-full"
                          :value="old('wedding_date', $m?->wedding_date?->format('Y-m-d') ?? '')" />
        </div>
    </div>

    <div>
        <x-input-label for="address_line1" value="Street address" />
        <x-text-input id="address_line1" name="address_line1" type="text" class="block mt-1 w-full" required
                      :value="old('address_line1', $m?->address_line1 ?? '')" />
    </div>
    <div>
        <x-input-label for="address_line2" value="Address line 2" />
        <x-text-input id="address_line2" name="address_line2" type="text" class="block mt-1 w-full"
                      :value="old('address_line2', $m?->address_line2 ?? '')" />
    </div>
    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <x-input-label for="city" value="City" />
            <x-text-input id="city" name="city" type="text" class="block mt-1 w-full" required
                          :value="old('city', $m?->city ?? '')" />
        </div>
        <div>
            <x-input-label for="state" value="State" />
            <x-text-input id="state" name="state" type="text" class="block mt-1 w-full" required
                          :value="old('state', $m?->state ?? '')" />
        </div>
        <div>
            <x-input-label for="postal_code" value="Postal code" />
            <x-text-input id="postal_code" name="postal_code" type="text" class="block mt-1 w-full"
                          :value="old('postal_code', $m?->postal_code ?? '')" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="country" value="Country" />
            <x-text-input id="country" name="country" type="text" class="block mt-1 w-full"
                          :value="old('country', $m?->country ?? 'Nigeria')" />
        </div>
        <div>
            <x-input-label for="department" value="Department" />
            <select id="department" name="department" required class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                <option value="">Select department</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept }}" @selected(old('department', $m?->department ?? '') === $dept)>{{ $dept }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="status" value="Status" />
            <select id="status" name="status" class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                @foreach ($statuses as $st)
                    <option value="{{ $st }}" @selected(old('status', $m?->status ?? 'active') === $st)>
                        {{ $statusLabels[$st] ?? $st }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="joined_date" value="Joined date" />
            <x-text-input id="joined_date" name="joined_date" type="date" class="block mt-1 w-full"
                          :value="old('joined_date', $m?->joined_date?->format('Y-m-d') ?? now()->toDateString())" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2" id="death-fields">
        <div>
            <x-input-label for="date_of_death" value="Date of death (if deceased)" />
            <x-text-input id="date_of_death" name="date_of_death" type="date" class="block mt-1 w-full"
                          :value="old('date_of_death', $m?->date_of_death?->format('Y-m-d') ?? '')" />
        </div>
        <div>
            <x-input-label for="death_notes" value="Death notes" />
            <x-text-input id="death_notes" name="death_notes" type="text" class="block mt-1 w-full"
                          :value="old('death_notes', $m?->death_notes ?? '')" />
        </div>
    </div>

    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Parent / guardian (optional)</h3>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <x-input-label for="parent_name" value="Name" />
                <x-text-input id="parent_name" name="parent_name" type="text" class="block mt-1 w-full"
                              :value="old('parent_name', $m?->parent_name ?? '')" />
            </div>
            <div>
                <x-input-label for="parent_phone" value="Phone" />
                <x-text-input id="parent_phone" name="parent_phone" type="text" class="block mt-1 w-full"
                              :value="old('parent_phone', $m?->parent_phone ?? '')" />
            </div>
            <div>
                <x-input-label for="parent_email" value="Email" />
                <x-text-input id="parent_email" name="parent_email" type="email" class="block mt-1 w-full"
                              :value="old('parent_email', $m?->parent_email ?? '')" />
            </div>
        </div>
    </div>

    <div>
        <x-input-label for="notes" value="Notes" />
        <textarea id="notes" name="notes" rows="3"
                  class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">{{ old('notes', $m?->notes ?? '') }}</textarea>
    </div>

    <div>
        <x-input-label for="photo" value="Photo" />
        @if ($isEdit && $m?->photo_path)
            @php $editPhotoUrl = app(\App\Services\Members\MemberReadService::class)->photoUrl($m->photo_path); @endphp
            @if ($editPhotoUrl)
                <div class="member-profile-thumb mb-2" style="width:72px;height:72px">
                    <img src="{{ $editPhotoUrl }}" alt="Current member photo">
                </div>
            @endif
            <p class="text-sm text-gray-500 mb-2">Current photo on file.</p>
            <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-2">
                <input type="checkbox" name="remove_photo" value="1" class="rounded border-gray-300"> Remove current photo
            </label>
        @endif
        <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"
               class="block mt-1 w-full text-sm text-gray-600 dark:text-gray-400" />
    </div>

    <div class="flex items-center gap-3 pt-2">
        <x-primary-button>{{ $isEdit ? 'Update member' : 'Register member' }}</x-primary-button>
        <a href="{{ $isEdit ? route('members.show', $member) : route('members.index') }}"
           class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">Cancel</a>
    </div>
</form>
