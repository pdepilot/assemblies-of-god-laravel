<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">SDTG Speakers</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Guest ministers and keynote speakers for the crusade year.</p>
            </div>
            @if ($canManage)
                <a href="{{ route('sdtg.speakers.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Add Speaker</a>
            @endif
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif

            @include('sdtg._nav', ['canManage' => $canManage])

            <form method="GET" action="{{ route('sdtg.speakers.index') }}" class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="year" class="block text-sm font-medium">Crusade year</label>
                    <input id="year" type="number" name="year" value="{{ request('year', date('Y')) }}" class="mt-1 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 w-28">
                </div>
                <button type="submit" class="px-3 py-2 rounded border text-sm">Filter</button>
            </form>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <div class="flex justify-between text-xs text-gray-500 mb-3">
                    <span>Showing {{ count($result['items']) }} of {{ $result['total'] }}</span>
                    <span>Page {{ $result['page'] }} / {{ $result['pages'] }}</span>
                </div>
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2">Name</th>
                            <th>Ministry</th>
                            <th>Country</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($result['items'] as $item)
                        <tr class="border-b">
                            <td class="py-2 font-medium">{{ $item['full_name'] }}</td>
                            <td>{{ $item['ministry'] ?: '—' }}</td>
                            <td>{{ $item['country'] ?: '—' }}</td>
                            <td class="capitalize">{{ $item['speaker_type'] }}</td>
                            <td class="capitalize">{{ $item['status'] }}</td>
                            <td class="whitespace-nowrap space-x-3">
                                <a href="{{ route('sdtg.speakers.show', $item['id']) }}" class="text-indigo-600">View</a>
                                @if ($canManage)
                                    <a href="{{ route('sdtg.speakers.edit', $item['id']) }}" class="text-indigo-600">Edit</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-gray-500">No speakers found.</td></tr>
                    @endforelse
                    </tbody>
                </table>

                @if ($result['pages'] > 1)
                    <div class="mt-4 flex gap-2 text-sm">
                        @if ($result['page'] > 1)
                            <a class="px-3 py-1 rounded border" href="{{ route('sdtg.speakers.index', ['year' => request('year', date('Y')), 'page' => $result['page'] - 1]) }}">Previous</a>
                        @endif
                        @if ($result['page'] < $result['pages'])
                            <a class="px-3 py-1 rounded border" href="{{ route('sdtg.speakers.index', ['year' => request('year', date('Y')), 'page' => $result['page'] + 1]) }}">Next</a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
