<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">New Broadcast</h2></x-slot>
    <div class="py-10"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('sermon.broadcasts.store') }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
            @csrf
            <div><label class="block text-sm font-medium">Title</label><input name="title" value="{{ old('title') }}" class="mt-1 w-full rounded border-gray-300" required></div>
            <div><label class="block text-sm font-medium">Stream Date</label><input type="date" name="stream_date" value="{{ old('stream_date', date('Y-m-d')) }}" class="mt-1 w-full rounded border-gray-300" required></div>
            <div><label class="block text-sm font-medium">Start Time</label><input type="time" name="start_time" value="{{ old('start_time', '09:00') }}" class="mt-1 w-full rounded border-gray-300" required></div>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Save Broadcast</button>
        </form>
    </div></div>
</x-app-layout>
