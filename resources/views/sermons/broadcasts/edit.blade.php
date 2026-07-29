<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Edit Broadcast</h2></x-slot>
    <div class="py-10"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('sermon.broadcasts.update', $broadcast['id']) }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
            @csrf @method('PUT')
            <div><label class="block text-sm font-medium">Title</label><input name="title" value="{{ old('title', $broadcast['title']) }}" class="mt-1 w-full rounded border-gray-300" required></div>
            <div><label class="block text-sm font-medium">Stream Date</label><input type="date" name="stream_date" value="{{ old('stream_date', $broadcast['stream_date']) }}" class="mt-1 w-full rounded border-gray-300" required></div>
            <div><label class="block text-sm font-medium">Start Time</label><input type="time" name="start_time" value="{{ old('start_time', $broadcast['start_time']) }}" class="mt-1 w-full rounded border-gray-300" required></div>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Update Broadcast</button>
        </form>
    </div></div>
</x-app-layout>
