@php
    $isEdit = isset($teacher);
    $action = $isEdit ? route('ss.teachers.update', $teacher) : route('ss.teachers.store');
    $t = $teacher ?? null;
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div>
        <x-input-label for="full_name" value="Full name" />
        <x-text-input id="full_name" name="full_name" type="text" class="block mt-1 w-full"
                      :value="old('full_name', $t?->full_name ?? '')" required />
        <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" type="email" class="block mt-1 w-full"
                          :value="old('email', $t?->email ?? '')" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="phone" value="Phone" />
            <x-text-input id="phone" name="phone" type="text" class="block mt-1 w-full"
                          :value="old('phone', $t?->phone ?? '')" />
        </div>
    </div>

    <div>
        <x-input-label for="address" value="Address" />
        <textarea id="address" name="address" rows="2"
                  class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">{{ old('address', $t?->address ?? '') }}</textarea>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="gender" value="Gender" />
            <select id="gender" name="gender"
                    class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                @foreach (['unspecified', 'male', 'female'] as $gender)
                    <option value="{{ $gender }}" @selected(old('gender', $t?->gender ?? 'unspecified') === $gender)>{{ $gender }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="date_joined" value="Date joined" />
            <x-text-input id="date_joined" name="date_joined" type="date" class="block mt-1 w-full"
                          :value="old('date_joined', $t?->date_joined?->format('Y-m-d') ?? '')" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="class_id" value="Primary class" />
            <select id="class_id" name="class_id"
                    class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                <option value="">— None —</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->id }}" @selected((string) old('class_id', $t?->class_id ?? '') === (string) $class->id)>
                        {{ $class->class_name }} ({{ $class->class_code }})
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="admin_id" value="Linked admin account (portal login)" />
            <select id="admin_id" name="admin_id"
                    class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                <option value="">— None —</option>
                @foreach ($admins as $admin)
                    <option value="{{ $admin->id }}" @selected((string) old('admin_id', $t?->admin_id ?? '') === (string) $admin->id)>
                        {{ $admin->full_name }} ({{ $admin->email }}) — {{ $admin->role }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Links this teacher to an admin user for ss_teacher portal access.</p>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="qualification" value="Qualification" />
            <x-text-input id="qualification" name="qualification" type="text" class="block mt-1 w-full"
                          :value="old('qualification', $t?->qualification ?? '')" />
        </div>
        <div>
            <x-input-label for="ministry_position" value="Ministry position" />
            <x-text-input id="ministry_position" name="ministry_position" type="text" class="block mt-1 w-full"
                          :value="old('ministry_position', $t?->ministry_position ?? '')" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="membership_status" value="Membership status" />
            <select id="membership_status" name="membership_status"
                    class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                @foreach (['unbaptized', 'visitor', 'baptized', 'full_member'] as $ms)
                    <option value="{{ $ms }}" @selected(old('membership_status', $t?->membership_status ?? 'unbaptized') === $ms)>{{ $ms }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="status" value="Status" />
            <select id="status" name="status"
                    class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                <option value="active" @selected(old('status', $t?->status ?? 'active') === 'active')>active</option>
                <option value="suspended" @selected(old('status', $t?->status ?? 'active') === 'suspended')>suspended</option>
            </select>
        </div>
    </div>

    <div class="flex items-center gap-3 pt-2">
        <x-primary-button>{{ $isEdit ? 'Update teacher' : 'Add teacher' }}</x-primary-button>
        <a href="{{ route('ss.teachers.index') }}"
           class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
            Cancel
        </a>
    </div>
</form>
