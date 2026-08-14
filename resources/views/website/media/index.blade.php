<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Media Library</h2></x-slot>
    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('website._nav', ['canManage' => $canManage])
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">Sermon media</div>
                    <div class="text-2xl font-semibold">{{ $counts['sermon_media'] ?? 0 }}</div>
                </div>
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">Total</div>
                    <div class="text-2xl font-semibold">{{ $counts['total'] ?? 0 }}</div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-500">Media assets are managed from the Sermons module. This page shows an index count.</p>
            </div>
        </div>
    </div>
</x-app-layout>
