<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Sunday School — Attendance Register
            </h2>
            <div class="flex flex-wrap gap-3 text-sm">
                @if ($canViewRemovals ?? false)
                    <a href="{{ route('ss.attendance.removals') }}"
                       class="text-indigo-600 dark:text-indigo-400 hover:underline">
                        Correction audit
                    </a>
                @endif
                <a href="{{ route('ss.classes.index') }}"
                   class="text-indigo-600 dark:text-indigo-400 hover:underline">
                    View classes
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->has('void'))
                <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">
                    {{ $errors->first('void') }}
                </div>
            @endif

            @if ($sheetError)
                <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">
                    {{ $sheetError }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                        Mark attendance, enter offering amounts, and tick memory verse recitation for each student.
                    </p>
                    <form method="GET" action="{{ route('ss.attendance.index') }}" class="flex flex-wrap gap-3 items-end">
                        <div>
                            <label for="class_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Class</label>
                            <select id="class_id" name="class_id" required
                                    class="mt-1 block w-56 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                <option value="">Select class</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class['id'] }}" @selected((string) $classId === (string) $class['id'])>
                                        {{ $class['class_name'] }} ({{ $class['class_code'] }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                            <input id="date" name="date" type="date" value="{{ $date }}"
                                   class="mt-1 block w-44 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                        </div>
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-semibold rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                            Load register
                        </button>
                    </form>
                </div>
            </div>

            @if ($classId > 0 && $sheetError === null)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        @if ($sheet === [])
                            <p class="text-sm text-gray-500 dark:text-gray-400">No active students in this class.</p>
                        @else
                            <form id="register-form" method="POST" action="{{ route('ss.attendance.store') }}">
                                @csrf
                            </form>

                            <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                                Register for <span class="font-semibold text-gray-900 dark:text-gray-100">{{ count($sheet) }}</span> student(s) on {{ $date }}.
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Code</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Name</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Status</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Arrival</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Offering</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Memory verse</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                        @foreach ($sheet as $index => $row)
                                            @php
                                                $status = $row['has_register_data'] && $row['attendance_id']
                                                    ? ($row['status'] ?? 'present')
                                                    : 'present';
                                                $arrival = $row['has_register_data'] && $row['attendance_id']
                                                    ? ($row['arrival_status'] ?? 'unknown')
                                                    : 'unknown';
                                            @endphp
                                            <tr>
                                                <td class="px-3 py-2 text-sm font-mono text-gray-900 dark:text-gray-100">{{ $row['student_code'] }}</td>
                                                <td class="px-3 py-2 text-sm text-gray-900 dark:text-gray-100">
                                                    {{ $row['full_name'] }}
                                                    @if ($row['has_register_data'])
                                                        <span class="ml-1 text-xs text-green-600 dark:text-green-400">(saved)</span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 text-sm">
                                                    <input type="hidden" form="register-form" name="records[{{ $index }}][student_id]" value="{{ $row['student_id'] }}">
                                                    <input type="hidden" form="register-form" name="records[{{ $index }}][class_id]" value="{{ $classId }}">
                                                    <input type="hidden" form="register-form" name="records[{{ $index }}][attendance_date]" value="{{ $date }}">
                                                    <select form="register-form" name="records[{{ $index }}][status]"
                                                            class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm">
                                                        @foreach (['present', 'absent', 'excused'] as $option)
                                                            <option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="px-3 py-2 text-sm">
                                                    <select form="register-form" name="records[{{ $index }}][arrival_status]"
                                                            class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm">
                                                        @foreach (['unknown', 'early', 'on_time', 'late'] as $option)
                                                            <option value="{{ $option }}" @selected($arrival === $option)>{{ $option }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="px-3 py-2 text-sm">
                                                    <input type="number" form="register-form" name="records[{{ $index }}][offering_amount]"
                                                           value="{{ number_format($row['offering_amount'], 2, '.', '') }}"
                                                           min="0" step="0.01"
                                                           class="w-24 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 text-sm" />
                                                </td>
                                                <td class="px-3 py-2 text-sm text-center">
                                                    <input type="checkbox" form="register-form" name="records[{{ $index }}][memory_verse]" value="1"
                                                           @checked($row['memory_verse_passed'])
                                                           class="rounded border-gray-300 dark:border-gray-700 text-indigo-600" />
                                                </td>
                                                <td class="px-3 py-2 text-sm">
                                                    @if ($row['has_register_data'])
                                                        <details class="text-xs">
                                                            <summary class="cursor-pointer text-red-600 dark:text-red-400 hover:underline">Remove</summary>
                                                            <div class="mt-2 p-2 border border-gray-200 dark:border-gray-700 rounded-md bg-gray-50 dark:bg-gray-900">
                                                                <form method="POST" action="{{ route('ss.attendance.void') }}" class="space-y-2">
                                                                    @csrf
                                                                    <input type="hidden" name="student_id" value="{{ $row['student_id'] }}">
                                                                    <input type="hidden" name="class_id" value="{{ $classId }}">
                                                                    <input type="hidden" name="attendance_date" value="{{ $date }}">
                                                                    <textarea name="reason" rows="2" required minlength="5" maxlength="2000"
                                                                              placeholder="Reason for removal (min 5 chars)"
                                                                              class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 text-xs"></textarea>
                                                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:underline" data-confirm="Remove this register entry?" data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger">
                                                                        Confirm remove
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </details>
                                                    @else
                                                        <span class="text-gray-400">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-6">
                                <button type="submit" form="register-form"
                                        class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                    Save register
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @elseif ($classId === 0 && $classes !== [])
                <p class="text-sm text-gray-500 dark:text-gray-400">Select a class and date to load the attendance register.</p>
            @elseif ($classes === [])
                <p class="text-sm text-gray-500 dark:text-gray-400">No classes are available for your account.</p>
            @endif
        </div>
    </div>
</x-app-layout>
