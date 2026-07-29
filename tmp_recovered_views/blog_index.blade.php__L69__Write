<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Blog Posts</h2></x-slot>
    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))<div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>@endif
            @include('website._nav', ['canManage' => $canManage])
            @if ($canManage)<a href="{{ route('website.blog.create') }}" class="inline-block px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">New Post</a>@endif
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead><tr class="text-left border-b"><th class="py-2">Title</th><th>Category</th><th>Published</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($result['items'] as $item)
                        <tr class="border-b">
                            <td class="py-2">{{ $item['title'] }}</td>
                            <td>{{ $item['category'] }}</td>
                            <td>{{ $item['is_published'] ? 'Yes' : 'No' }}</td>
                            <td><a href="{{ route('website.blog.show', $item['id']) }}" class="text-indigo-600">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-gray-500">No blog posts found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
