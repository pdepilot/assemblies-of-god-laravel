<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ $project['name'] }}</h2></x-slot>
    <div class="py-10"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8"><div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
        <dl class="grid gap-3 sm:grid-cols-2 text-sm">
            <div><dt class="text-gray-500">Code</dt><dd>{{ $project['code'] }}</dd></div>
            <div><dt class="text-gray-500">Type</dt><dd>{{ ucfirst($project['project_type']) }}</dd></div>
            <div><dt class="text-gray-500">Budget</dt><dd>₦{{ number_format($project['budget_amount'], 2) }}</dd></div>
            <div><dt class="text-gray-500">Status</dt><dd>{{ ucfirst($project['status']) }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-gray-500">Description</dt><dd>{{ $project['description'] ?: '—' }}</dd></div>
        </dl>
    </div></div></div>
</x-app-layout>
