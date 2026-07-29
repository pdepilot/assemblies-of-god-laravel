<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">New Draft</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('communication-hub.newsletter-drafts.store') }}" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf
                <div>
                    <label for="title" class="block text-sm font-medium">Title</label>
                    <input id="title" name="title" value="{{ old('title') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" required>
                </div>
                <div>
                    <label for="sections_json" class="block text-sm font-medium">Sections JSON</label>
                    <textarea id="sections_json" name="sections_json" rows="6" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 font-mono text-xs">{{ old('sections_json', json_encode($defaultSections, JSON_PRETTY_PRINT)) }}</textarea>
                </div>
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-md">Save</button>
            </form>
        </div>
    </div>
</x-app-layout>
