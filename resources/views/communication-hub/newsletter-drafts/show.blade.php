<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Draft</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-3">
                <h3 class="font-semibold text-lg">{{ $draft['title'] }}</h3>
                @if ($canManage)
                    <a href="{{ route('communication-hub.newsletter-drafts.edit', $draft['id']) }}" class="text-indigo-600">Edit</a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
