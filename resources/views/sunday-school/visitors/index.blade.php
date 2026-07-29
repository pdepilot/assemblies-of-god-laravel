<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Sunday School — Visitors
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
                <form method="GET" action="{{ route('ss.visitors.index') }}" class="flex flex-wrap gap-3 items-end mb-6">
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

                <p class="mb-4 text-sm font-semibold text-gray-900 dark:text-gray-100">Total visitors: {{ $total }}</p>

                <div class="overflow-x-auto mb-8">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Date</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Name</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Class</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Phone</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Follow-up</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($items as $row)
                                <tr>
                                    <td class="px-3 py-2 text-sm">{{ $row['visit_date'] }}</td>
                                    <td class="px-3 py-2 text-sm">{{ $row['visitor_name'] }}</td>
                                    <td class="px-3 py-2 text-sm">{{ $row['class_name'] ?? '—' }}</td>
                                    <td class="px-3 py-2 text-sm">{{ $row['phone'] ?? '—' }}</td>
                                    <td class="px-3 py-2 text-sm">{{ $row['follow_up_status'] }}</td>
                                    <td class="px-3 py-2 text-sm">
                                        <form method="POST" action="{{ route('ss.visitors.update', $row['id']) }}" class="inline-flex flex-wrap gap-2 items-center">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="filter_from" value="{{ $from }}">
                                            <input type="hidden" name="filter_to" value="{{ $to }}">
                                            <input type="hidden" name="visitor_name" value="{{ $row['visitor_name'] }}">
                                            <input type="hidden" name="visit_date" value="{{ $row['visit_date'] }}">
                                            <select name="follow_up_status" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-xs">
                                                @foreach (['pending', 'contacted', 'converted', 'closed'] as $status)
                                                    <option value="{{ $status }}" @selected($row['follow_up_status'] === $status)>{{ $status }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="text-indigo-600 dark:text-indigo-400 text-xs hover:underline">Update</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-6 text-center text-sm text-gray-500">No visitors for this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Register visitor</h3>
                <form method="POST" action="{{ route('ss.visitors.store') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @csrf
                    <input type="hidden" name="filter_from" value="{{ $from }}">
                    <input type="hidden" name="filter_to" value="{{ $to }}">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                        <input type="text" name="visitor_name" required value="{{ old('visitor_name') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Class</label>
                        <select name="class_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            <option value="">No class</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class['id'] }}" @selected((string) old('class_id') === (string) $class['id'])>
                                    {{ $class['class_name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Visit date</label>
                        <input type="date" name="visit_date" required value="{{ old('visit_date', now()->toDateString()) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Invited by</label>
                        <input type="text" name="invited_by" value="{{ old('invited_by') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Follow-up status</label>
                        <select name="follow_up_status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            @foreach (['pending', 'contacted', 'converted', 'closed'] as $status)
                                <option value="{{ $status }}" @selected(old('follow_up_status', 'pending') === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                        <textarea name="follow_up_notes" rows="2"
                                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">{{ old('follow_up_notes') }}</textarea>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <x-primary-button>Save visitor</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
