<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Edit Sermon</h2></x-slot>
    <div class="py-10"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        @php
            $tagValue = old('tags');
            if ($tagValue === null) {
                $rawTags = $sermon['tags'] ?? [];
                if (is_string($rawTags)) {
                    $decoded = json_decode($rawTags, true);
                    $rawTags = is_array($decoded) ? $decoded : [];
                }
                $tagValue = is_array($rawTags) ? implode(', ', $rawTags) : '';
            }
        @endphp
        <form method="POST" action="{{ route('sermon.sermons.update', $sermon['id']) }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
            @csrf @method('PUT')
            <div><label class="block text-sm font-medium">Title</label><input name="title" value="{{ old('title', $sermon['title']) }}" class="mt-1 w-full rounded border-gray-300" required></div>
            <div><label class="block text-sm font-medium">Sermon Date</label><input type="date" name="sermon_date" value="{{ old('sermon_date', $sermon['sermon_date']) }}" class="mt-1 w-full rounded border-gray-300" required></div>
            <div><label class="block text-sm font-medium">Minister</label><input name="minister_name" value="{{ old('minister_name', $sermon['minister_name']) }}" class="mt-1 w-full rounded border-gray-300"></div>
            <div><label class="block text-sm font-medium">SEO Title</label><input name="seo_title" value="{{ old('seo_title', $sermon['seo_title'] ?? '') }}" class="mt-1 w-full rounded border-gray-300" maxlength="255"></div>
            <div><label class="block text-sm font-medium">SEO Description</label><textarea name="seo_description" rows="2" class="mt-1 w-full rounded border-gray-300" maxlength="500">{{ old('seo_description', $sermon['seo_description'] ?? '') }}</textarea></div>
            <div><label class="block text-sm font-medium">Tags (comma-separated)</label><input name="tags" value="{{ $tagValue }}" class="mt-1 w-full rounded border-gray-300" placeholder="faith, prayer, holy-spirit"></div>
            <div><label class="block text-sm font-medium">YouTube URL</label><input name="youtube_url" value="{{ old('youtube_url', $sermon['youtube_url'] ?? '') }}" class="mt-1 w-full rounded border-gray-300"></div>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Update Sermon</button>
        </form>
    </div></div>
</x-app-layout>
