<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-wrap items-center justify-between gap-3">

            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">

                Sunday School — Curriculum

            </h2>

            <a href="{{ route('ss.lessons.create') }}"

               class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">

                Add lesson

            </a>

        </div>

    </x-slot>



    <div class="py-10">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))

                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">

                    {{ session('status') }}

                </div>

            @endif



            @if ($errors->has('delete'))

                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">

                    {{ $errors->first('delete') }}

                </div>

            @endif



            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">

                <form method="GET" action="{{ route('ss.lessons.index') }}" class="flex flex-wrap gap-3 items-end mb-6">

                    <div>

                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Class</label>

                        <select name="class_id" class="mt-1 block w-48 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">

                            <option value="">All classes</option>

                            @foreach ($classes as $class)

                                <option value="{{ $class['id'] }}" @selected((string) $classId === (string) $class['id'])>

                                    {{ $class['class_name'] }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                    <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Filter</button>

                </form>



                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">

                        <thead class="bg-gray-50 dark:bg-gray-700">

                            <tr>

                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Date</th>

                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Title</th>

                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Class</th>

                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Bible text</th>

                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300"></th>

                            </tr>

                        </thead>

                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">

                            @forelse($items as $row)

                                <tr>

                                    <td class="px-3 py-2 text-sm">{{ $row['lesson_date'] }}</td>

                                    <td class="px-3 py-2 text-sm font-medium">{{ $row['lesson_title'] }}</td>

                                    <td class="px-3 py-2 text-sm">{{ $row['class_name'] ?? 'All classes' }}</td>

                                    <td class="px-3 py-2 text-sm">{{ $row['bible_text'] ?? '—' }}</td>

                                    <td class="px-3 py-2 text-sm text-right space-x-2">

                                        <a href="{{ route('ss.lessons.show', $row['id']) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">View</a>

                                        <a href="{{ route('ss.lessons.edit', $row['id']) }}" class="text-gray-600 dark:text-gray-400 hover:underline">Edit</a>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="5" class="px-3 py-6 text-center text-sm text-gray-500">No lessons yet.</td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>



                @if ($totalPages > 1)

                    <div class="mt-6 flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">

                        <span>{{ $total }} lesson(s)</span>

                        <div class="flex gap-2">

                            @if ($page > 1)

                                <a href="{{ route('ss.lessons.index', array_filter(['class_id' => $classId ?: null, 'page' => $page - 1])) }}"

                                   class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Previous</a>

                            @endif

                            @if ($page < $totalPages)

                                <a href="{{ route('ss.lessons.index', array_filter(['class_id' => $classId ?: null, 'page' => $page + 1])) }}"

                                   class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Next</a>

                            @endif

                        </div>

                    </div>

                @endif

            </div>

        </div>

    </div>

</x-app-layout>


