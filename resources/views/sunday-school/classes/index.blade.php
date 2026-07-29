<x-app-layout title="Sunday School">
    <x-slot name="header">
        <div>
            <h1 class="cms-page__title">Sunday School Department</h1>
            <p class="cms-page__subtitle">Manage classes, teachers, students, attendance, offerings, memory verses, curriculum, promotions, and reports.</p>
        </div>
        <div class="cms-page__actions">
            <a href="{{ route('ss.classes.create') }}" class="cms-btn cms-btn--primary"><i class="fas fa-plus"></i> Add Class</a>
        </div>
    </x-slot>

    @include('sunday-school._tabs', ['active' => 'classes', 'canIntelligence' => false])

    <div class="py-2">
        <div class="max-w-7xl mx-auto">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->has('delete'))
                <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">
                    {{ $errors->first('delete') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if ($canManage ?? false)
                        <div class="mb-6 flex flex-wrap gap-3">
                            <a href="{{ route('ss.classes.create') }}"
                               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-semibold rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                                Add class
                            </a>
                            <form method="POST" action="{{ route('ss.classes.seed') }}">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-semibold rounded-md bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200">
                                    Seed defaults
                                </button>
                            </form>
                        </div>
                    @endif

                    <form method="GET" action="{{ route('ss.classes.index') }}" class="mb-6">
                        <div class="flex flex-wrap gap-3 items-center">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Status
                                </label>
                                <select id="status" name="status"
                                        class="mt-1 block w-48 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                    <option value="" {{ $status === '' ? 'selected' : '' }}>Active default</option>
                                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>active</option>
                                    <option value="archived" {{ $status === 'archived' ? 'selected' : '' }}>archived</option>
                                </select>
                            </div>
                            <div>
                                <label for="per_page" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Per page
                                </label>
                                <input id="per_page" name="per_page" type="number" min="1" max="100"
                                       value="{{ $perPage }}"
                                       class="mt-1 block w-24 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900" />
                            </div>
                            <button type="submit"
                                    class="mt-6 inline-flex items-center px-4 py-2 border border-transparent text-sm font-semibold rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
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
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Class</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Age range</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Teacher</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Assistant</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Capacity</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Students (active)</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Status</th>
                                    @if ($canManage ?? false)
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                @forelse($items as $c)
                                    <tr>
                                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $c['class_code'] }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $c['class_name'] }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $c['age_range'] ?? '' }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $c['teacher_name'] ?? '' }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $c['assistant_name'] ?? '' }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $c['max_capacity'] }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $c['student_count'] }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">{{ $c['status'] }}</td>
                                        @if ($canManage ?? false)
                                            <td class="px-3 py-2 text-sm">
                                                <div class="flex flex-wrap gap-2">
                                                    <a href="{{ route('ss.classes.edit', $c['id']) }}"
                                                       class="text-indigo-600 dark:text-indigo-400 hover:underline">Edit</a>
                                                    @if ($c['status'] === 'active')
                                                        <form method="POST" action="{{ route('ss.classes.archive', $c['id']) }}" class="inline">
                                                            @csrf
                                                            <button type="submit" class="text-amber-600 dark:text-amber-400 hover:underline" data-confirm="Archive this class?" data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger">
                                                                Archive
                                                            </button>
                                                        </form>
                                                    @endif
                                                    <form method="POST" action="{{ route('ss.classes.destroy', $c['id']) }}" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 dark:text-red-400 hover:underline" data-confirm="Delete this class permanently?" data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ ($canManage ?? false) ? 9 : 8 }}" class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                            No classes found for this view.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 flex items-center justify-between">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Page <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $page }}</span> of
                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $totalPages }}</span>
                        </div>
                        <div class="flex gap-2">
                            @php
                                $qs = ['status' => $status, 'per_page' => $perPage];
                            @endphp
                            @if($page > 1)
                                <a href="{{ route('ss.classes.index', $qs + ['page' => $page - 1]) }}"
                                   class="px-3 py-2 rounded-md bg-gray-100 dark:bg-gray-700 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">
                                    Previous
                                </a>
                            @endif
                            @if($page < $totalPages)
                                <a href="{{ route('ss.classes.index', $qs + ['page' => $page + 1]) }}"
                                   class="px-3 py-2 rounded-md bg-gray-100 dark:bg-gray-700 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">
                                    Next
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
