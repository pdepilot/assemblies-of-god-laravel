<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Ministries</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold">Age-based transfers</h3>
                        <p class="text-sm text-gray-500">Children 13+ → Teens; Teens 20+ → Youth. Daily job + on save.</p>
                    </div>
                    <a href="{{ route('ministries.age-transfers.index') }}"
                       class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                        Open age transfers
                    </a>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-6">
                @foreach ($settings as $setting)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 space-y-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h3 class="text-lg font-semibold">{{ $setting['name'] }}</h3>
                                <p class="text-sm text-gray-500">
                                    Key: {{ $setting['ministry_key'] }} ·
                                    {{ $setting['assignment_mode'] === 'auto' ? 'Auto' : 'Manual' }} ·
                                    {{ $setting['is_enabled'] ? 'Enabled' : 'Disabled' }}
                                </p>
                                @php $stats = $statsByKey[$setting['ministry_key']] ?? [] @endphp
                                <p class="text-sm mt-1">
                                    Active: {{ $stats['total'] ?? 0 }} ·
                                    New this month: {{ $stats['new_this_month'] ?? 0 }} ·
                                    Attendance this month: {{ $stats['attendance_month'] ?? 0 }}
                                </p>
                            </div>
                            <a href="{{ route('ministries.module.index', $setting['ministry_key']) }}"
                               class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                                Open roster
                            </a>
                        </div>

                        @if ($canManage)
                            <form method="POST" action="{{ route('ministries.settings.update', $setting['id']) }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 items-end">
                                @csrf
                                @method('PUT')
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Display name</label>
                                    <input name="name" value="{{ old('name', $setting['name']) }}" required
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Gender filter</label>
                                    <select name="gender_filter" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                        @foreach (['any', 'male', 'female'] as $gf)
                                            <option value="{{ $gf }}" @selected($setting['gender_filter'] === $gf)>{{ $gf }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sort order</label>
                                    <input name="sort_order" type="number" min="0" value="{{ $setting['sort_order'] }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                                </div>
                                <div class="flex items-center gap-4">
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="is_enabled" value="1" @checked($setting['is_enabled']) /> Enabled
                                    </label>
                                    <input type="hidden" name="assignment_mode" value="{{ $setting['assignment_mode'] }}" />
                                    <button type="submit" class="px-4 py-2 rounded-md bg-gray-800 dark:bg-gray-200 dark:text-gray-900 text-white dark:text-gray-900 text-sm font-semibold">Save</button>
                                </div>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
