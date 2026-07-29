<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Sunday School — Promotions
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

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-8">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Bulk class promotion</h3>
                    <form method="POST" action="{{ route('ss.promotions.bulk') }}" class="flex flex-wrap gap-3 items-end">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">From class</label>
                            <select name="from_class_id" required class="mt-1 block w-56 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                <option value="">Select class</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class['id'] }}">{{ $class['class_name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">To class (leave empty to graduate)</label>
                            <select name="to_class_id" class="mt-1 block w-56 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                <option value="">Graduate all</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class['id'] }}">{{ $class['class_name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-primary-button>Bulk promote</x-primary-button>
                    </form>
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Evaluate promotion year</h3>
                    <form method="POST" action="{{ route('ss.promotions.evaluate') }}" class="flex flex-wrap gap-3 items-end">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Year</label>
                            <input type="number" name="year" min="2000" max="2100" value="{{ $year }}"
                                   class="mt-1 block w-32 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                        </div>
                        <x-primary-button>Run evaluation</x-primary-button>
                    </form>
                </div>

                <div>
                    <form method="GET" action="{{ route('ss.promotions.index') }}" class="flex flex-wrap gap-3 items-end mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">View evaluations for year</label>
                            <input type="number" name="year" min="2000" max="2100" value="{{ $year }}"
                                   class="mt-1 block w-32 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Load</button>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Student</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Class</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Overall</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Meets req.</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Recommendation</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($evaluations as $row)
                                    <tr>
                                        <td class="px-3 py-2 text-sm">{{ $row['full_name'] }}</td>
                                        <td class="px-3 py-2 text-sm">{{ $row['class_name'] ?? '—' }}</td>
                                        <td class="px-3 py-2 text-sm">{{ number_format((float) $row['overall_score'], 1) }}</td>
                                        <td class="px-3 py-2 text-sm">{{ ! empty($row['meets_requirements']) ? 'Yes' : 'No' }}</td>
                                        <td class="px-3 py-2 text-sm">{{ $row['recommendation'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-6 text-center text-sm text-gray-500">No evaluations for {{ $year }} yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
