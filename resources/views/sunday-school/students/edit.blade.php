<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Edit Student — {{ $student->full_name }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                        Student code: <span class="font-mono font-semibold text-gray-900 dark:text-gray-100">{{ $student->student_code }}</span>
                    </p>
                    @include('sunday-school.students._form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
