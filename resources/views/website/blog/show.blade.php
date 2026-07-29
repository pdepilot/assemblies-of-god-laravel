<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ $post['title'] }}</h2></x-slot>
    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('website.blog.index') }}" class="px-4 py-2 border rounded-md text-sm">Back</a>
                @if ($canManage)
                    <a href="{{ route('website.blog.edit', $post['id']) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Edit</a>
                    @if (empty($post['is_published']))
                        <form method="POST" action="{{ route('website.blog.publish', $post['id']) }}">
                            @csrf
                            <button type="submit" class="px-4 py-2 border rounded-md text-sm">Publish</button>
                        </form>
                    @endif
                @endif
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                <p class="text-sm text-gray-500">
                    {{ $post['category'] ?? '—' }} · {{ !empty($post['is_published']) ? 'Published' : 'Draft' }}
                    @if (!empty($post['author'])) · {{ $post['author'] }} @endif
                </p>
                @if (!empty($post['excerpt']))
                    <p class="text-gray-700 dark:text-gray-300">{{ $post['excerpt'] }}</p>
                @endif
                <div class="prose dark:prose-invert max-w-none">{!! $post['body_html'] ?? '' !!}</div>
            </div>
        </div>
    </div>
</x-app-layout>
