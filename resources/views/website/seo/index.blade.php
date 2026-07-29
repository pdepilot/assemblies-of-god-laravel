<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">SEO Settings</h2></x-slot>
    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @include('website._nav', ['canManage' => $canManage])
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2">Page</th>
                            <th>Title</th>
                            <th>Meta description</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pages as $page)
                            <tr class="border-b">
                                <td class="py-2 font-mono">{{ $page['key'] }}</td>
                                <td>{{ $page['title'] ?? '—' }}</td>
                                <td class="max-w-md truncate">{{ $page['meta_description'] ?? '—' }}</td>
                                <td>
                                    @if ($canManage)
                                        <a href="{{ route('website.seo.edit', $page['key']) }}" class="text-indigo-600">Edit</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-gray-500">No SEO pages configured.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
