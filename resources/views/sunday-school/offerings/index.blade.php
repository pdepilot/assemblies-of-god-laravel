<x-app-layout>
    @php
        /** @var list<array<string, mixed>> $classes */
        /** @var list<array<string, mixed>> $students */
        /** @var list<array<string, mixed>> $items */
        $classOptions = is_array($classes) ? $classes : [];
        $studentOptions = is_array($students ?? null) ? $students : [];
        $offeringRows = is_array($items ?? null) ? $items : [];
        $offeringsIndexUrl = route('ss.offerings.index');
    @endphp
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Sunday School — Offerings
            </h2>
            <a href="{{ route('ss.classes.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                View classes
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

            @if ($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('ss.offerings.index') }}" class="flex flex-wrap gap-3 items-end mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Class</label>
                        <select name="class_id" class="mt-1 block w-48 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            <option value="">All classes</option>
                            @foreach ($classOptions as $classOption)
                                <option value="{{ data_get($classOption, 'id') }}" @selected((string) $classId === (string) data_get($classOption, 'id'))>
                                    {{ data_get($classOption, 'class_name') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
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

                <p class="mb-4 text-sm font-semibold text-gray-900 dark:text-gray-100">
                    Total: ₦{{ number_format($total, 2) }}
                </p>

                <div class="overflow-x-auto mb-8">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Date</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Student</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Class</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($offeringRows as $row)
                                <tr>
                                    <td class="px-3 py-2 text-sm">{{ data_get($row, 'offering_date') }}</td>
                                    <td class="px-3 py-2 text-sm">{{ data_get($row, 'student_name') }}</td>
                                    <td class="px-3 py-2 text-sm">{{ data_get($row, 'class_name') }}</td>
                                    <td class="px-3 py-2 text-sm">₦{{ number_format((float) data_get($row, 'amount', 0), 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-6 text-center text-sm text-gray-500">No offering records for this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Record offering</h3>
                <form method="POST" action="{{ route('ss.offerings.store') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 items-end">
                    @csrf
                    <input type="hidden" name="filter_from" value="{{ $from }}">
                    <input type="hidden" name="filter_to" value="{{ $to }}">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Class</label>
                        <select name="class_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                data-offerings-index="{{ $offeringsIndexUrl }}"
                                data-from="{{ $from }}"
                                data-to="{{ $to }}"
                                onchange="window.location=this.dataset.offeringsIndex+'?class_id='+encodeURIComponent(this.value)+'&from='+encodeURIComponent(this.dataset.from)+'&to='+encodeURIComponent(this.dataset.to)">
                            <option value="">Select class</option>
                            @foreach ($classOptions as $classOption)
                                <option value="{{ data_get($classOption, 'id') }}" @selected((string) old('class_id', $classId) === (string) data_get($classOption, 'id'))>
                                    {{ data_get($classOption, 'class_name') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Student</label>
                        <select name="student_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            <option value="">Select student</option>
                            @foreach ($studentOptions as $student)
                                <option value="{{ data_get($student, 'id') }}" @selected((string) old('student_id') === (string) data_get($student, 'id'))>
                                    {{ data_get($student, 'full_name') }} ({{ data_get($student, 'student_code') }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount (₦)</label>
                        <input type="number" name="amount" min="0" step="0.01" required value="{{ old('amount') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                        <input type="date" name="offering_date" required value="{{ old('offering_date', now()->toDateString()) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                    </div>
                    <div class="sm:col-span-2 lg:col-span-4">
                        <x-primary-button>Record offering</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
