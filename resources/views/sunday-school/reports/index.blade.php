<x-app-layout>

    <x-slot name="header">

        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">

            Sunday School — Reports

        </h2>

    </x-slot>



    <div class="py-10">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">

                    <dt class="text-sm text-gray-500 dark:text-gray-400">Students</dt>

                    <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['total_students'] }}</dd>

                </div>

                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">

                    <dt class="text-sm text-gray-500 dark:text-gray-400">Present today</dt>

                    <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['students_present_today'] }}</dd>

                </div>

                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">

                    <dt class="text-sm text-gray-500 dark:text-gray-400">Offerings (month)</dt>

                    <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">₦{{ number_format($stats['offering_month'], 2) }}</dd>

                </div>

                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">

                    <dt class="text-sm text-gray-500 dark:text-gray-400">Memory verses (month)</dt>

                    <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['memory_verse_completions'] }}</dd>

                </div>

                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">

                    <dt class="text-sm text-gray-500 dark:text-gray-400">Classes</dt>

                    <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['total_classes'] }}</dd>

                </div>

                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">

                    <dt class="text-sm text-gray-500 dark:text-gray-400">Early arrivals today</dt>

                    <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['early_arrivals_today'] }}</dd>

                </div>

                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">

                    <dt class="text-sm text-gray-500 dark:text-gray-400">Late arrivals today</dt>

                    <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['late_arrivals_today'] }}</dd>

                </div>

                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">

                    <dt class="text-sm text-gray-500 dark:text-gray-400">Offerings (all time)</dt>

                    <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">₦{{ number_format($stats['offering_total'], 2) }}</dd>

                </div>

            </dl>



            <div class="grid gap-6 lg:grid-cols-2">

                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">

                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Class daily report</h3>

                    <form method="GET" action="{{ route('ss.reports.index') }}" class="grid gap-4 sm:grid-cols-3 items-end">

                        <input type="hidden" name="generate" value="1">

                        <input type="hidden" name="from" value="{{ $from }}">

                        <input type="hidden" name="to" value="{{ $to }}">

                        <div>

                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Class</label>

                            <select name="class_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">

                                <option value="">Select class</option>

                                @foreach ($classes as $class)

                                    <option value="{{ $class['id'] }}" @selected((string) $classId === (string) $class['id'])>

                                        {{ $class['class_name'] }}

                                    </option>

                                @endforeach

                            </select>

                        </div>

                        <div>

                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>

                            <input type="date" name="report_date" value="{{ $reportDate }}" required

                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />

                        </div>

                        <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Generate</button>

                    </form>



                    @if ($classReportError)

                        <p class="mt-4 text-sm text-red-600 dark:text-red-400">{{ $classReportError }}</p>

                    @endif



                    @if ($classReport)

                        <ul class="mt-4 space-y-2 text-sm text-gray-900 dark:text-gray-100">

                            <li><strong>Students:</strong> {{ $classReport['total_students'] }}</li>

                            <li><strong>Present:</strong> {{ $classReport['present_students'] }}</li>

                            <li><strong>Absent:</strong> {{ $classReport['absent_students'] }}</li>

                            <li><strong>Offerings:</strong> ₦{{ number_format($classReport['offerings'], 2) }}</li>

                            <li><strong>Memory verse pass:</strong> {{ $classReport['memory_verse_pass'] }}</li>

                            <li><strong>Attendance rate:</strong> {{ $classReport['attendance_rate'] }}%</li>

                        </ul>

                    @endif

                </div>



                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">

                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">CSV exports</h3>

                    <div class="flex flex-wrap gap-3">

                        <a href="{{ route('ss.reports.export', ['type' => 'students', 'class_id' => $classId ?: null]) }}"

                           class="inline-flex px-4 py-2 rounded-md border border-gray-300 dark:border-gray-600 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">

                            Students CSV

                        </a>

                        <a href="{{ route('ss.reports.export', ['type' => 'attendance', 'class_id' => $classId ?: null, 'to' => $reportDate]) }}"

                           class="inline-flex px-4 py-2 rounded-md border border-gray-300 dark:border-gray-600 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">

                            Attendance CSV

                        </a>

                        <a href="{{ route('ss.reports.export', ['type' => 'offerings', 'class_id' => $classId ?: null, 'from' => $from, 'to' => $to]) }}"

                           class="inline-flex px-4 py-2 rounded-md border border-gray-300 dark:border-gray-600 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">

                            Offerings CSV

                        </a>

                        @if ($canExportTeachers)

                            <a href="{{ route('ss.reports.export', ['type' => 'teachers']) }}"

                               class="inline-flex px-4 py-2 rounded-md border border-gray-300 dark:border-gray-600 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">

                                Teachers CSV

                            </a>

                        @endif

                    </div>

                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Attendance export uses the selected class and report date above.</p>

                </div>

            </div>



            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">

                <div class="flex flex-wrap items-end justify-between gap-3 mb-4">

                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Punctuality report</h3>

                    <form method="GET" action="{{ route('ss.reports.index') }}" class="flex flex-wrap gap-3 items-end">

                        @if ($classId)

                            <input type="hidden" name="class_id" value="{{ $classId }}">

                        @endif

                        @if ($reportDate)

                            <input type="hidden" name="report_date" value="{{ $reportDate }}">

                        @endif

                        <div>

                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">From</label>

                            <input type="date" name="from" value="{{ $from }}"

                                   class="mt-1 block w-40 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />

                        </div>

                        <div>

                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">To</label>

                            <input type="date" name="to" value="{{ $to }}"

                                   class="mt-1 block w-40 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />

                        </div>

                        <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Refresh</button>

                    </form>

                </div>

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">

                        <thead class="bg-gray-50 dark:bg-gray-700">

                            <tr>

                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Student</th>

                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Class</th>

                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Early</th>

                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Late</th>

                            </tr>

                        </thead>

                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">

                            @forelse($punctuality as $row)

                                <tr>

                                    <td class="px-3 py-2 text-sm">{{ $row['full_name'] }}</td>

                                    <td class="px-3 py-2 text-sm">{{ $row['class_name'] }}</td>

                                    <td class="px-3 py-2 text-sm">{{ $row['early_count'] }}</td>

                                    <td class="px-3 py-2 text-sm">{{ $row['late_count'] }}</td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="4" class="px-3 py-6 text-center text-sm text-gray-500">No punctuality data for this period.</td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>



            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">

                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Student performance rankings</h3>

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">

                        <thead class="bg-gray-50 dark:bg-gray-700">

                            <tr>

                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Rank</th>

                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Student</th>

                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Class</th>

                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Overall score</th>

                            </tr>

                        </thead>

                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">

                            @forelse($rankings as $index => $row)

                                <tr>

                                    <td class="px-3 py-2 text-sm">{{ $index + 1 }}</td>

                                    <td class="px-3 py-2 text-sm">{{ $row['full_name'] }}</td>

                                    <td class="px-3 py-2 text-sm">{{ $row['class_name'] ?? '—' }}</td>

                                    <td class="px-3 py-2 text-sm font-medium">{{ number_format((float) $row['overall_score'], 1) }}</td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="4" class="px-3 py-6 text-center text-sm text-gray-500">No students to rank.</td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>


