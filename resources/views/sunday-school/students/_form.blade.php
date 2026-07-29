@php
    $isEdit = isset($student);
    $action = $isEdit ? route('ss.students.update', $student) : route('ss.students.store');
    $s = $student ?? null;
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div>
        <x-input-label for="full_name" value="Full name" />
        <x-text-input id="full_name" name="full_name" type="text" class="block mt-1 w-full"
                      :value="old('full_name', $s?->full_name ?? '')" required />
        <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="date_of_birth" value="Date of birth" />
            <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="block mt-1 w-full"
                          :value="old('date_of_birth', $s?->date_of_birth?->format('Y-m-d') ?? '')" />
            <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="gender" value="Gender" />
            <select id="gender" name="gender"
                    class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                @foreach (['unspecified', 'male', 'female'] as $gender)
                    <option value="{{ $gender }}" @selected(old('gender', $s?->gender ?? 'unspecified') === $gender)>{{ $gender }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="marital_status" value="Marital status" />
            <select id="marital_status" name="marital_status"
                    class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                @foreach (['unspecified', 'single', 'married', 'divorced', 'widowed', 'separated'] as $ms)
                    <option value="{{ $ms }}" @selected(old('marital_status', $s?->marital_status ?? 'unspecified') === $ms)>{{ $ms }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="wedding_date" value="Wedding date (if married)" />
            <x-text-input id="wedding_date" name="wedding_date" type="date" class="block mt-1 w-full"
                          :value="old('wedding_date', $s?->wedding_date?->format('Y-m-d') ?? '')" />
            <x-input-error :messages="$errors->get('wedding_date')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="class_id" value="Class" />
        <select id="class_id" name="class_id" required
                class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
            <option value="">— Select class —</option>
            @foreach ($classes as $class)
                <option value="{{ $class['id'] }}" @selected((string) old('class_id', $s?->class_id ?? '') === (string) $class['id'])>
                    {{ $class['class_name'] }} ({{ $class['class_code'] }})
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('class_id')" class="mt-2" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="parent_name" value="Parent / guardian name" />
            <x-text-input id="parent_name" name="parent_name" type="text" class="block mt-1 w-full"
                          :value="old('parent_name', $s?->parent_name ?? '')" />
        </div>
        <div>
            <x-input-label for="parent_phone" value="Parent phone" />
            <x-text-input id="parent_phone" name="parent_phone" type="text" class="block mt-1 w-full"
                          :value="old('parent_phone', $s?->parent_phone ?? '')" />
        </div>
    </div>

    <div>
        <x-input-label for="parent_email" value="Parent email" />
        <x-text-input id="parent_email" name="parent_email" type="email" class="block mt-1 w-full"
                      :value="old('parent_email', $s?->parent_email ?? '')" />
        <x-input-error :messages="$errors->get('parent_email')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="address" value="Address" />
        <textarea id="address" name="address" rows="2"
                  class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">{{ old('address', $s?->address ?? '') }}</textarea>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <x-input-label for="date_joined" value="Date joined" />
            <x-text-input id="date_joined" name="date_joined" type="date" class="block mt-1 w-full"
                          :value="old('date_joined', $s?->date_joined?->format('Y-m-d') ?? date('Y-m-d'))" />
        </div>
        <div>
            <x-input-label for="membership_status" value="Membership status" />
            <select id="membership_status" name="membership_status"
                    class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                @foreach (['unbaptized', 'visitor', 'baptized', 'full_member'] as $ms)
                    <option value="{{ $ms }}" @selected(old('membership_status', $s?->membership_status ?? 'unbaptized') === $ms)>{{ $ms }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="status" value="Status" />
            <select id="status" name="status"
                    class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                @foreach (['active', 'archived', 'graduated'] as $st)
                    <option value="{{ $st }}" @selected(old('status', $s?->status ?? 'active') === $st)>{{ $st }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <input type="hidden" name="department" value="{{ old('department', $s?->department ?? 'Sunday School') }}" />

    <div class="flex items-center gap-3 pt-2">
        <x-primary-button>{{ $isEdit ? 'Update student' : 'Register student' }}</x-primary-button>
        <a href="{{ route('ss.students.index') }}"
           class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
            Cancel
        </a>
    </div>
</form>

@if ($isEdit)
    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Transfer to another class</h3>
        <form method="POST" action="{{ route('ss.students.transfer', $student) }}" class="flex flex-wrap gap-3 items-end">
            @csrf
            <div>
                <x-input-label for="to_class_id" value="New class" />
                <select id="to_class_id" name="to_class_id" required
                        class="block mt-1 w-64 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                    @foreach ($classes as $class)
                        @if ((int) $class['id'] !== (int) $student->class_id)
                            <option value="{{ $class['id'] }}">{{ $class['class_name'] }}</option>
                        @endif
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('to_class_id')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="notes" value="Notes (optional)" />
                <x-text-input id="notes" name="notes" type="text" class="block mt-1 w-64" />
            </div>
            <x-primary-button type="submit">Transfer</x-primary-button>
        </form>
    </div>
@endif
