<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Promotion Banner</h2></x-slot>
    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @include('website._nav', ['canManage' => $canManage])
            <p class="text-sm text-gray-600 dark:text-gray-300">The active banner appears immediately when the public website loads, above the intro video, so programmes and events are seen first.</p>
            @if ($canManage)
                <a href="{{ route('website.promotions.create') }}" class="inline-block px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">New promotion</a>
            @endif
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2">Title</th>
                            <th>Schedule</th>
                            <th>Active</th>
                            <th>Every visit</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($promotions as $promotion)
                            <tr class="border-b">
                                <td class="py-2">{{ $promotion['title'] }}</td>
                                <td>
                                    @if (! empty($promotion['starts_at']) || ! empty($promotion['ends_at']))
                                        {{ $promotion['starts_at'] ?: 'Now' }}
                                        –
                                        {{ $promotion['ends_at'] ?: 'Open' }}
                                    @else
                                        Always
                                    @endif
                                </td>
                                <td>{{ ! empty($promotion['is_active']) ? 'Yes' : 'No' }}</td>
                                <td>{{ ! empty($promotion['show_every_visit']) ? 'Yes' : 'No' }}</td>
                                <td class="whitespace-nowrap">
                                    @if ($canManage)
                                        <a href="{{ route('website.promotions.edit', $promotion['id']) }}" class="text-indigo-600">Edit</a>
                                        <form method="POST" action="{{ route('website.promotions.destroy', $promotion['id']) }}" class="inline ms-3" onsubmit="return confirm('Remove this promotion banner?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600">Remove</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-gray-500">No promotion banners yet. Create one to promote a programme or event on load.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
