<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl">{{ $event['title'] }}</h2>
            <div class="flex gap-2">
                @if ($canManage)<a href="{{ route('events.edit', $event['id']) }}" class="px-4 py-2 text-sm font-semibold rounded-md bg-indigo-600 text-white">Edit</a>@endif
                <a href="{{ route('events.index') }}" class="px-4 py-2 text-sm font-semibold rounded-md border">Back</a>
            </div>
        </div>
    </x-slot>
    <div class="py-10"><div class="max-w-4xl mx-auto sm:px-6 space-y-6">
        @if (session('status'))<div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>@endif

        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
            <img src="{{ $event['image_url'] }}" alt="{{ $event['title'] }}" class="w-full max-h-80 object-cover bg-gray-100 dark:bg-gray-900">
            <div class="p-6">
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-sm text-gray-500">Code</dt><dd class="font-mono">{{ $event['event_code'] }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Date</dt><dd>{{ $event['event_date'] }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Category</dt><dd>{{ $event['category_label'] }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Status</dt><dd>{{ ucfirst($event['status']) }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Published</dt><dd>{{ $event['is_published'] ? 'Yes' : 'No (paused)' }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Recurring</dt><dd>{{ $event['is_recurring'] ? ($event['recurrence_label'] ?: 'Yes') : 'No' }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Public preview</dt><dd><a href="{{ $event['image_url'] }}" target="_blank" rel="noopener" class="text-indigo-600 hover:underline text-sm">Open image</a></dd></div>
                </dl>
                @if ($event['description'])<p class="mt-4 text-sm">{{ $event['description'] }}</p>@endif
            </div>
        </div>

        @if ($canManage)
            <div class="flex gap-3">
                <form method="POST" action="{{ route('events.set-published', $event['id']) }}">@csrf
                    <input type="hidden" name="published" value="{{ $event['is_published'] ? '0' : '1' }}" />
                    <button type="submit" class="px-4 py-2 rounded-md border text-sm">{{ $event['is_published'] ? 'Pause on website' : 'Publish on website' }}</button>
                </form>
                <form method="POST" action="{{ route('events.destroy', $event['id']) }}" data-confirm="Delete this event?" data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger">@csrf @method('DELETE')
                    <button type="submit" class="px-4 py-2 rounded-md bg-red-600 text-white text-sm">Delete</button>
                </form>
            </div>
        @endif
    </div></div>
</x-app-layout>
