@php

    $isEdit = isset($lesson);

    $action = $isEdit ? route('ss.lessons.update', $lesson['id']) : route('ss.lessons.store');

    $l = $lesson ?? null;

@endphp



<form method="POST" action="{{ $action }}" class="space-y-4">

    @csrf

    @if ($isEdit)

        @method('PUT')

    @endif



    <div>

        <x-input-label for="lesson_title" value="Lesson title" />

        <x-text-input id="lesson_title" name="lesson_title" type="text" class="block mt-1 w-full"

                      :value="old('lesson_title', $l['lesson_title'] ?? '')" required />

        <x-input-error :messages="$errors->get('lesson_title')" class="mt-2" />

    </div>



    <div class="grid gap-4 sm:grid-cols-2">

        <div>

            <x-input-label for="lesson_date" value="Lesson date" />

            <x-text-input id="lesson_date" name="lesson_date" type="date" class="block mt-1 w-full"

                          :value="old('lesson_date', isset($l['lesson_date']) ? \Illuminate\Support\Str::before((string) $l['lesson_date'], ' ') : now()->toDateString())" required />

            <x-input-error :messages="$errors->get('lesson_date')" class="mt-2" />

        </div>

        <div>

            <x-input-label for="class_id" value="Class (optional)" />

            <select id="class_id" name="class_id"

                    class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">

                <option value="">All classes</option>

                @foreach ($classes as $class)

                    <option value="{{ $class['id'] }}" @selected((string) old('class_id', $l['class_id'] ?? '') === (string) $class['id'])>

                        {{ $class['class_name'] }} ({{ $class['class_code'] }})

                    </option>

                @endforeach

            </select>

            <x-input-error :messages="$errors->get('class_id')" class="mt-2" />

        </div>

    </div>



    <div class="grid gap-4 sm:grid-cols-2">

        <div>

            <x-input-label for="bible_text" value="Bible text" />

            <x-text-input id="bible_text" name="bible_text" type="text" class="block mt-1 w-full"

                          :value="old('bible_text', $l['bible_text'] ?? '')" />

        </div>

        <div>

            <x-input-label for="golden_text" value="Golden text" />

            <x-text-input id="golden_text" name="golden_text" type="text" class="block mt-1 w-full"

                          :value="old('golden_text', $l['golden_text'] ?? '')" />

        </div>

    </div>



    <div>

        <x-input-label for="objectives" value="Objectives" />

        <textarea id="objectives" name="objectives" rows="3"

                  class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">{{ old('objectives', $l['objectives'] ?? '') }}</textarea>

    </div>



    <div>

        <x-input-label for="teaching_notes" value="Teaching notes" />

        <textarea id="teaching_notes" name="teaching_notes" rows="4"

                  class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">{{ old('teaching_notes', $l['teaching_notes'] ?? '') }}</textarea>

    </div>



    <div>

        <x-input-label for="activities" value="Activities" />

        <textarea id="activities" name="activities" rows="3"

                  class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">{{ old('activities', $l['activities'] ?? '') }}</textarea>

    </div>



    <div class="flex items-center gap-3 pt-2">

        <x-primary-button>{{ $isEdit ? 'Update lesson' : 'Save lesson' }}</x-primary-button>

        <a href="{{ $isEdit ? route('ss.lessons.show', $l['id']) : route('ss.lessons.index') }}"

           class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">

            Cancel

        </a>

    </div>

</form>


