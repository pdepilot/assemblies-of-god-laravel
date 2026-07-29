<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">New Sermon</h2></x-slot>
    <div class="py-10"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('sermon.sermons.store') }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
            @csrf
            <div><label class="block text-sm font-medium">Title</label><input name="title" value="{{ old('title') }}" class="mt-1 w-full rounded border-gray-300" required></div>
            <div><label class="block text-sm font-medium">Sermon Date</label><input type="date" name="sermon_date" value="{{ old('sermon_date', date('Y-m-d')) }}" class="mt-1 w-full rounded border-gray-300" required></div>
            <div><label class="block text-sm font-medium">Minister</label><input name="minister_name" value="{{ old('minister_name') }}" class="mt-1 w-full rounded border-gray-300"></div>
            <div><label class="block text-sm font-medium">Type</label><select name="sermon_type" class="mt-1 w-full rounded border-gray-300">@foreach($types as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach</select></div>
            <div><label class="block text-sm font-medium">Status</label><select name="status" class="mt-1 w-full rounded border-gray-300">@foreach($statuses as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach</select></div>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Save Sermon</button>
        </form>
    </div></div>
</x-app-layout>
