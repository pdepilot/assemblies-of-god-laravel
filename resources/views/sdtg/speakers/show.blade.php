<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ $speaker['full_name'] }}</h2>
            @if ($canManage)
                <a href="{{ route('sdtg.speakers.edit', $speaker['id']) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Edit</a>
            @endif
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @include('sdtg._nav', ['canManage' => $canManage])

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-2 text-sm">
                <p><strong>Ministry:</strong> {{ $speaker['ministry'] ?: '—' }}</p>
                <p><strong>Country:</strong> {{ $speaker['country'] ?: '—' }}</p>
                <p><strong>Year:</strong> {{ $speaker['crusade_year'] }}</p>
                <p><strong>Type:</strong> <span class="capitalize">{{ $speaker['speaker_type'] }}</span></p>
                <p><strong>Status:</strong> <span class="capitalize">{{ $speaker['status'] }}</span></p>
                <p><strong>Topic:</strong> {{ $speaker['topic'] ?: '—' }}</p>
                @if (! empty($speaker['bio']))
                    <div class="pt-3 border-t">
                        <p class="font-medium mb-1">Bio</p>
                        <p class="whitespace-pre-wrap text-gray-700 dark:text-gray-300">{{ $speaker['bio'] }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
