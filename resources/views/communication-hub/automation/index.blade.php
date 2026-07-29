<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Automation Rules</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Event-driven messaging. Enable rules without changing core code.</p>
            </div>
            @if ($canManage)
                <a href="{{ route('communication-hub.automation.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">New Rule</a>
            @endif
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">{{ $errors->first() }}</div>
            @endif

            @include('communication-hub._nav', ['canManage' => $canManage])

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2">Rule</th>
                            <th>Trigger</th>
                            <th>Channel</th>
                            <th>Priority</th>
                            <th>Runs</th>
                            <th>Enabled</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($rules as $rule)
                        <tr class="border-b">
                            <td class="py-3">
                                <p class="font-medium">{{ $rule['name'] }}</p>
                                @if ($rule['description'])
                                    <p class="text-gray-500 text-xs">{{ $rule['description'] }}</p>
                                @endif
                            </td>
                            <td><code class="text-xs">{{ $rule['trigger_event'] }}</code></td>
                            <td>{{ $rule['channel'] }}</td>
                            <td>{{ $rule['priority'] }}</td>
                            <td>{{ $rule['run_count'] }}</td>
                            <td>
                                @if ($canManage)
                                    <form method="POST" action="{{ route('communication-hub.automation.toggle', $rule['id']) }}">
                                        @csrf
                                        <input type="hidden" name="is_enabled" value="{{ $rule['is_enabled'] ? '0' : '1' }}">
                                        <button type="submit" class="px-2 py-1 rounded border text-xs {{ $rule['is_enabled'] ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-600' }}">
                                            {{ $rule['is_enabled'] ? 'On' : 'Off' }}
                                        </button>
                                    </form>
                                @else
                                    {{ $rule['is_enabled'] ? 'On' : 'Off' }}
                                @endif
                            </td>
                            <td>
                                @if ($canManage)
                                    <a href="{{ route('communication-hub.automation.edit', $rule['id']) }}" class="text-indigo-600">Edit</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-gray-500">No automation rules found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
