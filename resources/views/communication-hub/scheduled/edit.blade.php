<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Edit Scheduled Message</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('communication-hub.scheduled.update', $message['id']) }}" class="space-y-4">
                @csrf
                @method('PUT')
                @include('communication-hub.scheduled._form')
                <div class="flex gap-3">
                    <button class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Update schedule</button>
                    <a href="{{ route('communication-hub.scheduled.index') }}" class="px-4 py-2 border rounded-md text-sm">Back</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
