<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-wrap items-center justify-between gap-3">

            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">

                {{ $lesson['lesson_title'] }}

            </h2>

            <div class="flex gap-3">

                <a href="{{ route('ss.lessons.edit', $lesson['id']) }}"

                   class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Edit</a>

                <a href="{{ route('ss.lessons.index') }}"

                   class="text-sm text-gray-600 dark:text-gray-400 hover:underline">Back to list</a>

            </div>

        </div>

    </x-slot>



    <div class="py-10">

        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))

                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">

                    {{ session('status') }}

                </div>

            @endif



            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-6">

                <dl class="grid gap-4 sm:grid-cols-2">

                    <div>

                        <dt class="text-sm text-gray-500 dark:text-gray-400">Date</dt>

                        <dd class="mt-1 font-medium">{{ $lesson['lesson_date'] }}</dd>

                    </div>

                    <div>

                        <dt class="text-sm text-gray-500 dark:text-gray-400">Class</dt>

                        <dd class="mt-1 font-medium">{{ $lesson['class_name'] ?? 'All classes' }}</dd>

                    </div>

                    <div>

                        <dt class="text-sm text-gray-500 dark:text-gray-400">Bible text</dt>

                        <dd class="mt-1 font-medium">{{ $lesson['bible_text'] ?? '—' }}</dd>

                    </div>

                    <div>

                        <dt class="text-sm text-gray-500 dark:text-gray-400">Golden text</dt>

                        <dd class="mt-1 font-medium">{{ $lesson['golden_text'] ?? '—' }}</dd>

                    </div>

                </dl>



                @if (! empty($lesson['objectives']))

                    <div>

                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Objectives</h3>

                        <p class="mt-2 text-sm whitespace-pre-line text-gray-900 dark:text-gray-100">{{ $lesson['objectives'] }}</p>

                    </div>

                @endif



                @if (! empty($lesson['teaching_notes']))

                    <div>

                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Teaching notes</h3>

                        <p class="mt-2 text-sm whitespace-pre-line text-gray-900 dark:text-gray-100">{{ $lesson['teaching_notes'] }}</p>

                    </div>

                @endif



                @if (! empty($lesson['activities']))

                    <div>

                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Activities</h3>

                        <p class="mt-2 text-sm whitespace-pre-line text-gray-900 dark:text-gray-100">{{ $lesson['activities'] }}</p>

                    </div>

                @endif



                @if ($canDelete)

                    <form method="POST" action="{{ route('ss.lessons.destroy', $lesson['id']) }}" data-confirm="Delete this lesson plan?" data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger" class="pt-4 border-t border-gray-200 dark:border-gray-700">

                        @csrf

                        @method('DELETE')

                        <button type="submit" class="text-sm text-red-600 dark:text-red-400 hover:underline">Delete lesson</button>

                    </form>

                @endif

            </div>

        </div>

    </div>

</x-app-layout>


