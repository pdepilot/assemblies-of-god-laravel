<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Edit: {{ $meta['label'] }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $meta['description'] }}</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">{{ session('error') }}</div>
            @endif
            @include('sdtg._nav', ['canManage' => true])

            <div class="flex flex-wrap items-center justify-between gap-3">
                <a href="{{ route('sdtg.content.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; Back to all sections</a>
                <div class="flex items-center gap-3">
                    <a href="{{ route($meta['public_route'], $meta['public_param'] ? ['path' => $meta['public_param']] : []) }}" target="_blank" rel="noopener" class="text-sm text-gray-500 hover:text-indigo-600">View public page →</a>
                    <form method="POST" action="{{ route('sdtg.content.reset', $sectionKey) }}" onsubmit="return confirm('Reset this section back to the default content? This cannot be undone.');">
                        @csrf
                        @if ($activeTab)
                            <input type="hidden" name="tab" value="{{ $activeTab }}">
                        @endif
                        <button type="submit" class="px-3 py-2 rounded border border-red-300 text-red-600 text-sm font-semibold hover:bg-red-50 dark:hover:bg-red-900/20">Reset to defaults</button>
                    </form>
                </div>
            </div>

            @if ($meta['type'] === 'nested')
                <div class="flex flex-wrap gap-2 text-sm border-b border-gray-200 dark:border-gray-700 pb-3">
                    @foreach ($tabs as $tab)
                        <a href="{{ route('sdtg.content.edit', ['section' => $sectionKey, 'tab' => $tab]) }}"
                           class="px-3 py-1 rounded-full border {{ $tab === $activeTab ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300' }}">
                            {{ ucwords(str_replace('_', ' ', $tab)) }}
                        </a>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('sdtg.content.update', $sectionKey) }}" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-6">
                @csrf
                @method('PUT')
                @if ($activeTab)
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                @endif

                @if ($meta['type'] === 'flat')
                    @include('sdtg.content.partials.'.$sectionKey, ['content' => $content, 'mediaUrls' => $mediaUrls ?? []])
                @else
                    @include('sdtg.content.partials.nested.'.$sectionKey.'.'.$activeTab, ['content' => $content[$activeTab] ?? [], 'mediaUrls' => $mediaUrls ?? []])
                @endif

                <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Save Section</button>
                </div>
            </form>

            <details class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                <summary class="cursor-pointer text-sm font-semibold text-gray-600 dark:text-gray-300">Advanced: raw JSON (read-only)</summary>
                <pre class="mt-3 text-xs overflow-x-auto bg-gray-50 dark:bg-gray-900 rounded p-3">{{ json_encode($meta['type'] === 'flat' ? $content : ($content[$activeTab] ?? []), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </details>
        </div>
    </div>
</x-app-layout>
