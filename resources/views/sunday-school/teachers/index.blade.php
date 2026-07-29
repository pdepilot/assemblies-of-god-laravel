<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Sunday School — Teachers
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

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <a href="{{ route('ss.teachers.create') }}"
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-semibold rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                            Add teacher
                        </a>
                    </div>

                    <form method="GET" action="{{ route('ss.teachers.index') }}" class="mb-6">
                        <div class="flex flex-wrap gap-3 items-end">
                            <div>
                                <label for="q" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                                <input id="q" name="q" type="text" value="{{ $query }}"
                                       placeholder="Name, code, email..."
                                       class="mt-1 block w-48 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                            </div>
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                <select id="status" name="status"
                                        class="mt-1 block w-40 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                    <option value="" @selected($status === '')>All</option>
                                    <option value="active" @selected($status === 'active')>active</option>
                                    <option value="suspended" @selected($status === 'suspended')>suspended</option>
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
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Students</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Admin account</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Status</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                @forelse($items as $t)
                                    <tr>
                                        <td class="px-3 py-2 text-sm font-mono text-gray-900 dark:text-gray-100">{{ $t['teacher_code'] }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $t['full_name'] }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $t['class_name'] ?? '—' }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $t['student_count'] ?? 0 }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $t['admin_name'] ?? '—' }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $t['status'] }}</td>
                                        <td class="px-3 py-2 text-sm">
                                            <div class="flex flex-wrap gap-2">
                                                <a href="{{ route('ss.teachers.edit', $t['id']) }}"
                                                   class="text-indigo-600 dark:text-indigo-400 hover:underline">Edit</a>
                                                @if ($t['status'] === 'active')
                                                    <form method="POST" action="{{ route('ss.teachers.suspend', $t['id']) }}" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-amber-600 dark:text-amber-400 hover:underline" data-confirm="Suspend this teacher?" data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger">Suspend</button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('ss.teachers.activate', $t['id']) }}" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-green-600 dark:text-green-400 hover:underline">Activate</button>
                                                    </form>
                                                @endif
                                                <form method="POST" action="{{ route('ss.teachers.destroy', $t['id']) }}" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:underline" data-confirm="Delete this teacher permanently?" data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                            No teachers found for this view.
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
                            @php $qs = array_filter(['q' => $query, 'status' => $status, 'per_page' => $perPage]); @endphp
                            @if ($page > 1)
                                <a href="{{ route('ss.teachers.index', $qs + ['page' => $page - 1]) }}"
                                   class="px-3 py-2 rounded-md bg-gray-100 dark:bg-gray-700 text-sm hover:bg-gray-200 dark:hover:bg-gray-600">Previous</a>
                            @endif
                            @if ($page < $totalPages)
                                <a href="{{ route('ss.teachers.index', $qs + ['page' => $page + 1]) }}"
                                   class="px-3 py-2 rounded-md bg-gray-100 dark:bg-gray-700 text-sm hover:bg-gray-200 dark:hover:bg-gray-600">Next</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
