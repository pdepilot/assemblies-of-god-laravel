<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Recipient Groups</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Dynamic audiences: members, workers, ministries, visitors, registrants, and custom smart groups.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('communication-hub._nav', ['canManage' => $canManage])

            @if (count($groups) === 0)
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-8 text-center text-gray-500">
                    No groups.
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($groups as $group)
                        <article class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $group['name'] }}</h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                <span class="font-mono text-xs">{{ $group['group_key'] }}</span>
                                · {{ $group['group_type'] }}
                                @if ($group['is_system'])
                                    · System
                                @endif
                            </p>
                            @if ($group['description'] !== '')
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $group['description'] }}</p>
                            @endif
                            @if ($group['member_count_cache'] !== null)
                                <p class="mt-3 text-xs text-gray-500">Cached count: {{ number_format($group['member_count_cache']) }}</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
