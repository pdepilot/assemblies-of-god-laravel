<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Newsletter Drafts</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @include('communication-hub._nav', ['canManage' => $canManage])

            @if ($canManage)
                <a href="{{ route('communication-hub.newsletter-drafts.create') }}" class="inline-block text-indigo-600">New Draft</a>
            @endif

            @forelse ($items as $item)
                <p>
                    <a href="{{ route('communication-hub.newsletter-drafts.show', $item['id']) }}" class="text-indigo-600">
                        {{ $item['title'] }}
                    </a>
                </p>
            @empty
                <p class="text-gray-500">No drafts.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
