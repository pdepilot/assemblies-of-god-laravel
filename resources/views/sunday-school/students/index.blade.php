<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Sunday School — Students
            </h2>
            <a href="{{ route('ss.classes.index') }}"
               class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                View classes
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->has('archive') || $errors->has('delete'))
                <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">
                    {{ $errors->first('archive') ?: $errors->first('delete') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($canManage)
                        <div class="mb-6">
                            <a href="{{ route('ss.students.create') }}"
                               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-semibold rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                                Register student
                            </a>
                        </div>
                    @endif

                    <form method="GET" action="{{ route('ss.students.index') }}" class="mb-6">
                        <div class="flex flex-wrap gap-3 items-end">
                            <div>
                                <label for="q" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                                <input id="q" name="q" type="text" value="{{ $query }}"
                                       placeholder="Name, code, parent..."
                                       class="mt-1 block w-48 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                            </div>
                            <div>
                                <label for="class_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Class</label>
                                <select id="class_id" name="class_id"
                                        class="mt-1 block w-48 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                    <option value="">All classes</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class['id'] }}" @selected((string) $classId === (string) $class['id'])>
                                            {{ $class['class_name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                <select id="status" name="status"
                                        class="mt-1 block w-40 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                    <option value="" @selected($status === '')>Active default</option>
                                    <option value="active" @selected($status === 'active')>active</option>
                                    <option value="archived" @selected($status === 'archived')>archived</option>
                                    <option value="graduated" @selected($status === 'graduated')>graduated</option>
                                </select>
                            </div>
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-semibold rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                                Apply
                            </button>
                        </div>
                    </form>

                    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                        Total: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $total }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Code</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Name</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Class</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Age</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Parent</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Status</th>
                                    @if ($canManage || $canDelete)
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                @forelse($items as $s)
                                    <tr>
                                        <td class="px-3 py-2 text-sm font-mono text-gray-900 dark:text-gray-100">{{ $s['student_code'] }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $s['full_name'] }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $s['class_name'] ?? '—' }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $s['age'] ?? '—' }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $s['parent_name'] ?? '—' }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $s['status'] }}</td>
                                        @if ($canManage || $canDelete)
                                            <td class="px-3 py-2 text-sm">
                                                <div class="flex flex-wrap gap-2">
                                                    @if ($canManage)
                                                        <a href="{{ route('ss.students.edit', $s['id']) }}"
                                                           class="text-indigo-600 dark:text-indigo-400 hover:underline">Edit</a>
                                                        @if ($s['status'] === 'active')
                                                            <form method="POST" action="{{ route('ss.students.archive', $s['id']) }}" class="inline">
                                                                @csrf
                                                                <button type="submit" class="text-amber-600 dark:text-amber-400 hover:underline" data-confirm="Archive this student?" data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger">Archive</button>
                                                            </form>
                                                        @endif
                                                    @endif
                                                    @if ($canDelete)
                                                        <form method="POST" action="{{ route('ss.students.destroy', $s['id']) }}" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-red-600 dark:text-red-400 hover:underline" data-confirm="Delete this student permanently?" data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger">Delete</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ ($canManage || $canDelete) ? 7 : 6 }}" class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                            No students found for this view.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 flex items-center justify-between">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Page {{ $page }} of {{ $totalPages }}
                        </div>
                        <div class="flex gap-2">
                            @php $qs = array_filter(['q' => $query, 'class_id' => $classId ?: null, 'status' => $status, 'per_page' => $perPage]); @endphp
                            @if ($page > 1)
                                <a href="{{ route('ss.students.index', $qs + ['page' => $page - 1]) }}"
                                   class="px-3 py-2 rounded-md bg-gray-100 dark:bg-gray-700 text-sm hover:bg-gray-200 dark:hover:bg-gray-600">Previous</a>
                            @endif
                            @if ($page < $totalPages)
                                <a href="{{ route('ss.students.index', $qs + ['page' => $page + 1]) }}"
                                   class="px-3 py-2 rounded-md bg-gray-100 dark:bg-gray-700 text-sm hover:bg-gray-200 dark:hover:bg-gray-600">Next</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
