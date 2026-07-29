<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ $announcement['title'] }}</h2>
            @if ($canManage)
                <a href="{{ route('sdtg.announcements.edit', $announcement['id']) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Edit</a>
            @endif
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('sdtg._nav', ['canManage' => $canManage])
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-3 text-sm">
                <p><strong>Category:</strong> {{ $announcement['category'] ?? '—' }}</p>
                <p><strong>Published:</strong> {{ ! empty($announcement['is_published']) ? 'Yes' : 'No' }}</p>
                @if (! empty($announcement['excerpt']))
                    <p class="text-gray-600">{{ $announcement['excerpt'] }}</p>
                @endif
                <div class="whitespace-pre-wrap">{{ $announcement['body'] ?? '' }}</div>
                @if (! empty($announcement['link_url']))
                    <p><a href="{{ $announcement['link_url'] }}" class="text-indigo-600" target="_blank" rel="noopener">Open link</a></p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
