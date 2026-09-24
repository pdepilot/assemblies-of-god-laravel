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
            <x-input-label for="first_name" value="First name" :required="true" />
            <x-text-input id="first_name" name="first_name" type="text" class="block mt-1 w-full" required
                          :value="old('first_name', $m?->first_name ?? '')" />
            <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="last_name" value="Last name" :required="true" />
            <x-text-input id="last_name" name="last_name" type="text" class="block mt-1 w-full" required
                          :value="old('last_name', $m?->last_name ?? '')" />
            <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="phone" value="Phone" :required="true" />
            <x-text-input id="phone" name="phone" type="text" class="block mt-1 w-full" required
                          :value="old('phone', $m?->phone ?? '')" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="phone_alt" value="Alt phone" />
            <x-text-input id="phone_alt" name="phone_alt" type="text" class="block mt-1 w-full"
                          :value="old('phone_alt', $m?->phone_alt ?? '')" />
            <x-input-error :messages="$errors->get('phone_alt')" class="mt-2" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="email" value="Email" :required="true" />
            <x-text-input id="email" name="email" type="email" class="block mt-1 w-full" required
                          :value="old('email', $m?->email ?? '')" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="gender" value="Gender" :required="true" />
            <select id="gender" name="gender" required class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                <option value="">Select gender</option>
                @foreach (['male', 'female', 'other'] as $gender)
                    <option value="{{ $gender }}" @selected(old('gender', $m?->gender ?? '') === $gender)>{{ $gender }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('gender')" class="mt-2" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <x-input-label for="date_of_birth" value="Date of birth" :required="true" />
            <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="block mt-1 w-full" required
                          :value="old('date_of_birth', $m?->date_of_birth?->format('Y-m-d') ?? '')" />
            <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="marital_status" value="Marital status" :required="true" />
            <select id="marital_status" name="marital_status" required class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                <option value="">Select status</option>
                @foreach (['single', 'married', 'divorced', 'widowed', 'widow', 'widower', 'separated'] as $ms)
                    <option value="{{ $ms }}" @selected(old('marital_status', $m?->marital_status ?? '') === $ms)>{{ $ms }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('marital_status')" class="mt-2" />
        </div>
        <div id="weddingDateWrap">
            <x-input-label for="wedding_date" value="Wedding date" :required="true" />
            <x-text-input id="wedding_date" name="wedding_date" type="date" class="block mt-1 w-full"
                          :value="old('wedding_date', $m?->wedding_date?->format('Y-m-d') ?? '')" />
            <x-input-error :messages="$errors->get('wedding_date')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="address_line1" value="Street address" :required="true" />
        <x-text-input id="address_line1" name="address_line1" type="text" class="block mt-1 w-full" required
                      :value="old('address_line1', $m?->address_line1 ?? '')" />
        <x-input-error :messages="$errors->get('address_line1')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="address_line2" value="Address line 2" :required="true" />
        <x-text-input id="address_line2" name="address_line2" type="text" class="block mt-1 w-full" required
                      :value="old('address_line2', $m?->address_line2 ?? '')" />
        <x-input-error :messages="$errors->get('address_line2')" class="mt-2" />
    </div>
    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <x-input-label for="city" value="City" :required="true" />
            <x-text-input id="city" name="city" type="text" class="block mt-1 w-full" required
                          :value="old('city', $m?->city ?? '')" />
            <x-input-error :messages="$errors->get('city')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="state" value="State" :required="true" />
            <x-text-input id="state" name="state" type="text" class="block mt-1 w-full" required
                          :value="old('state', $m?->state ?? '')" />
            <x-input-error :messages="$errors->get('state')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="postal_code" value="Postal code" :required="true" />
            <x-text-input id="postal_code" name="postal_code" type="text" class="block mt-1 w-full" required
                          :value="old('postal_code', $m?->postal_code ?? '')" />
            <x-input-error :messages="$errors->get('postal_code')" class="mt-2" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="occupation" value="Occupation" :required="true" />
            <x-text-input id="occupation" name="occupation" type="text" class="block mt-1 w-full" required
                          :value="old('occupation', $m?->occupation ?? '')" placeholder="e.g. Teacher, Trader, Civil servant" />
            <x-input-error :messages="$errors->get('occupation')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="country" value="Country" :required="true" />
            <x-text-input id="country" name="country" type="text" class="block mt-1 w-full" required
                          :value="old('country', $m?->country ?? 'Nigeria')" />
            <x-input-error :messages="$errors->get('country')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="department" value="Department" :required="true" />
            <select id="department" name="department" required class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                <option value="">Select department</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept }}" @selected(old('department', $m?->department ?? '') === $dept)>{{ $dept }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('department')" class="mt-2" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="status" value="Status" :required="true" />
            <select id="status" name="status" required class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                <option value="">Select status</option>
                @foreach ($statuses as $st)
                    <option value="{{ $st }}" @selected(old('status', $m?->status ?? 'active') === $st)>
                        {{ $statusLabels[$st] ?? $st }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('status')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="joined_date" value="Joined date" :required="true" />
            <x-text-input id="joined_date" name="joined_date" type="date" class="block mt-1 w-full" required
                          :value="old('joined_date', $m?->joined_date?->format('Y-m-d') ?? now()->toDateString())" />
            <x-input-error :messages="$errors->get('joined_date')" class="mt-2" />
        </div>
    </div>

    <div id="death-fields">
        <x-input-label for="death_notes" value="Memorial notes" />
        <textarea id="death_notes" name="death_notes" rows="3" class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" placeholder="Service details, burial place, family notes…">{{ old('death_notes', $m?->death_notes ?? '') }}</textarea>
    </div>

    @php
        $dobForParent = old('date_of_birth', $m?->date_of_birth?->format('Y-m-d') ?? '');
        $ageForParent = null;
        if (is_string($dobForParent) && $dobForParent !== '') {
            try {
                $ageForParent = \Carbon\Carbon::parse($dobForParent)->age;
            } catch (\Throwable) {
                $ageForParent = null;
            }
        }
        $showParentGuardian = $ageForParent !== null && $ageForParent <= 19;
    @endphp
    <div id="parentGuardianFields" class="border-t border-gray-200 dark:border-gray-700 pt-4" @if (! $showParentGuardian) hidden @endif>
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Parent / guardian (children and teens only)</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Use this only when the new member is a child or teenager. If you are adding an adult who is a parent, skip this and fill that adult’s own name, phone, and address.</p>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <x-input-label for="parent_name" value="Name" :required="true" />
                <x-text-input id="parent_name" name="parent_name" type="text" class="block mt-1 w-full"
                              :value="old('parent_name', $m?->parent_name ?? '')" />
                <x-input-error :messages="$errors->get('parent_name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="parent_phone" value="Phone" :required="true" />
                <x-text-input id="parent_phone" name="parent_phone" type="text" class="block mt-1 w-full"
                              :value="old('parent_phone', $m?->parent_phone ?? '')" />
                <x-input-error :messages="$errors->get('parent_phone')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="parent_email" value="Email" :required="true" />
                <x-text-input id="parent_email" name="parent_email" type="email" class="block mt-1 w-full"
                              :value="old('parent_email', $m?->parent_email ?? '')" />
                <x-input-error :messages="$errors->get('parent_email')" class="mt-2" />
            </div>
        </div>
    </div>

    <div>
        <x-input-label for="notes" value="Notes" />
        <textarea id="notes" name="notes" rows="3"
                  class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">{{ old('notes', $m?->notes ?? '') }}</textarea>
    </div>

    @php
        $portalEnabled = (bool) old('portal_enabled', (int) ($m?->portal_enabled ?? 0) === 1);
    @endphp
    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Member portal access</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Check this to let the member sign in from the member login page. Uncheck it to remove access.</p>
        <label class="inline-flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" id="portal_enabled" name="portal_enabled" value="1" class="rounded border-gray-300 mt-0.5"
                   @checked($portalEnabled)>
            <span>Grant access to the member portal</span>
        </label>
        <x-input-error :messages="$errors->get('portal_enabled')" class="mt-2" />
        <div class="mt-3">
            <x-input-label for="portal_password" value="Portal password" />
            <x-text-input id="portal_password" name="portal_password" type="password" class="block mt-1 w-full"
                          autocomplete="new-password" />
            <p class="mt-1 text-xs text-gray-500">Optional. Leave blank so the member sets a password on first login. Use email, member ID, or phone to sign in.</p>
            <x-input-error :messages="$errors->get('portal_password')" class="mt-2" />
        </div>
    </div>

    <div class="member-photo-capture">
        <x-input-label for="photo" value="Photo" :required="true" />
        <div class="member-photo-capture__row">
            <div class="member-photo-capture__preview" id="memberPhotoPreview">
                @if ($isEdit && $m?->photo_path)
                    @php $editPhotoUrl = app(\App\Services\Members\MemberReadService::class)->photoUrl($m->photo_path); @endphp
                    @if ($editPhotoUrl)
                        <img src="{{ $editPhotoUrl }}" alt="Current member photo">
                    @else
                        <span class="member-photo-capture__placeholder">No photo</span>
                    @endif
                @else
                    <span class="member-photo-capture__placeholder">No photo</span>
                @endif
            </div>
            <div class="member-photo-capture__controls">
                @if ($isEdit && $m?->photo_path)
                    <p class="text-sm text-gray-500 mb-2">Current photo on file.</p>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <input type="checkbox" name="remove_photo" value="1" class="rounded border-gray-300"> Remove current photo
                    </label>
                @endif
                <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"
                       class="block w-full text-sm text-gray-600 dark:text-gray-400"
                       @if (! $isEdit || ! $m?->photo_path) required @endif />
                <div class="member-photo-capture__actions">
                    <button type="button" class="member-photo-capture__camera-btn" id="memberTakePhotoBtn">
                        <i class="fas fa-camera" aria-hidden="true"></i> Take Photo
                    </button>
                </div>
                <p class="mt-1 text-xs text-gray-500">JPG, PNG, or WebP up to 2 MB. Upload a file or take a photo with the camera.</p>
                <p class="member-photo-capture__status text-sm mt-1" id="memberPhotoStatus" role="status"></p>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3 pt-2">
        <x-primary-button>{{ $isEdit ? 'Update member' : 'Register member' }}</x-primary-button>
        <a href="{{ $isEdit ? route('members.show', $member) : route('members.index') }}"
           class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">Cancel</a>
    </div>
</form>
<script>
(function () {
    var dob = document.getElementById('date_of_birth');
    var section = document.getElementById('parentGuardianFields');
    var marital = document.getElementById('marital_status');
    var wedding = document.getElementById('wedding_date');
    var status = document.getElementById('status');
    var deathFields = document.getElementById('death-fields');
    var parentIds = ['parent_name', 'parent_phone', 'parent_email'];

    function ageFrom(value) {
        if (!value) return null;
        var parts = value.split('-');
        if (parts.length !== 3) return null;
        var birth = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
        if (isNaN(birth.getTime())) return null;
        var today = new Date();
        var age = today.getFullYear() - birth.getFullYear();
        var m = today.getMonth() - birth.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
        return age >= 0 ? age : null;
    }

    function setRequired(el, on) {
        if (!el) return;
        if (on) el.setAttribute('required', 'required');
        else el.removeAttribute('required');
    }

    function syncParent() {
        if (!dob || !section) return;
        var age = ageFrom(dob.value);
        var show = age !== null && age <= 19;
        section.hidden = !show;
        parentIds.forEach(function (id) {
            setRequired(document.getElementById(id), show);
        });
    }

    function syncWedding() {
        setRequired(wedding, !!(marital && marital.value === 'married'));
    }

    function syncDeath() {
        var deceased = status && status.value === 'deceased';
        if (deathFields) deathFields.hidden = !deceased;
    }

    if (dob) {
        dob.addEventListener('change', syncParent);
        dob.addEventListener('input', syncParent);
    }
    if (marital) marital.addEventListener('change', syncWedding);
    if (status) status.addEventListener('change', syncDeath);

    syncParent();
    syncWedding();
    syncDeath();
})();
</script>
@push('scripts')
<script src="{{ asset('portal/js/member-photo-capture.js') }}?v={{ is_file(public_path('portal/js/member-photo-capture.js')) ? filemtime(public_path('portal/js/member-photo-capture.js')) : 1 }}"></script>
@endpush
