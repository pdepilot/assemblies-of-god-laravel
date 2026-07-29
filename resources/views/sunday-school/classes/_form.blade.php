@php
    $isEdit = isset($class);
    $action = $isEdit ? route('ss.classes.update', $class) : route('ss.classes.store');
    $method = $isEdit ? 'PUT' : 'POST';
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div>
        <x-input-label for="class_name" value="Class name" />
        <x-text-input id="class_name" name="class_name" type="text" class="block mt-1 w-full"
                      :value="old('class_name', $class->class_name ?? '')" required />
        <x-input-error :messages="$errors->get('class_name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="class_code" value="Class code (optional on create)" />
        <x-text-input id="class_code" name="class_code" type="text" class="block mt-1 w-full"
                      :value="old('class_code', $class->class_code ?? '')" />
        <x-input-error :messages="$errors->get('class_code')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="age_range" value="Age range" />
        <x-text-input id="age_range" name="age_range" type="text" class="block mt-1 w-full"
                      :value="old('age_range', $class->age_range ?? '')" />
        <x-input-error :messages="$errors->get('age_range')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="description" value="Description" />
        <textarea id="description" name="description" rows="3"
                  class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">{{ old('description', $class->description ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="teacher_id" value="Teacher" />
            <select id="teacher_id" name="teacher_id"
                    class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                <option value="">— None —</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected((string) old('teacher_id', $class->teacher_id ?? '') === (string) $teacher->id)>
                        {{ $teacher->full_name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('teacher_id')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="assistant_teacher_id" value="Assistant teacher" />
            <select id="assistant_teacher_id" name="assistant_teacher_id"
                    class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                <option value="">— None —</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected((string) old('assistant_teacher_id', $class->assistant_teacher_id ?? '') === (string) $teacher->id)>
                        {{ $teacher->full_name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('assistant_teacher_id')" class="mt-2" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="max_capacity" value="Max capacity" />
            <x-text-input id="max_capacity" name="max_capacity" type="number" min="1" class="block mt-1 w-full"
                          :value="old('max_capacity', $class->max_capacity ?? 50)" />
            <x-input-error :messages="$errors->get('max_capacity')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="status" value="Status" />
            <select id="status" name="status"
                    class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                <option value="active" @selected(old('status', $class->status ?? 'active') === 'active')>active</option>
                <option value="archived" @selected(old('status', $class->status ?? 'active') === 'archived')>archived</option>
            </select>
            <x-input-error :messages="$errors->get('status')" class="mt-2" />
        </div>
    </div>

    <div class="flex items-center gap-3 pt-2">
        <x-primary-button>{{ $isEdit ? 'Update class' : 'Create class' }}</x-primary-button>
        <a href="{{ route('ss.classes.index') }}"
           class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
            Cancel
        </a>
    </div>
</form>
