<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Template</h2>
            @if ($canManage)
                <a href="{{ route('communication-hub.templates.edit', $template['id']) }}" class="px-3 py-1.5 rounded border text-sm">Edit</a>
            @endif
        </div>
    </x-slot>
    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('communication-hub._nav', ['canManage' => $canManage])
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                <div>
                    <h3 class="text-lg font-semibold">{{ $template['name'] }}</h3>
                    <p class="text-sm text-gray-500">{{ $template['slug'] }} · {{ $template['channel'] }} · {{ $template['status'] }}</p>
                </div>
                @if (! empty($template['subject']))
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500">Subject</div>
                        <p class="mt-1">{{ $template['subject'] }}</p>
                    </div>
                @endif
                @if (! empty($template['display_body']))
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500 mb-2">Message</div>
                        <div class="whitespace-pre-wrap border rounded-md p-4 bg-gray-50 dark:bg-gray-900/40 dark:border-gray-700">{{ $template['display_body'] }}</div>
                    </div>
                @else
                    <p class="text-gray-500">This template has no body content yet.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
