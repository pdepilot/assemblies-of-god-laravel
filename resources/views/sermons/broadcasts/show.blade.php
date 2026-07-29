<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ $broadcast['title'] ?? 'Broadcast' }}</h2></x-slot>
    <div class="py-10"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @include('sermons._nav', ['canManage' => $canManage])
        <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-2">
            <p><strong>Status:</strong> {{ $broadcast['status'] }}</p>
            <p><strong>Date:</strong> {{ $broadcast['stream_date'] }}</p>
            @if ($canManage)
                <form method="POST" action="{{ route('sermon.broadcasts.start', $broadcast['id']) }}" class="inline">@csrf<button class="text-green-600">Start</button></form>
                <form method="POST" action="{{ route('sermon.broadcasts.stop', $broadcast['id']) }}" class="inline">@csrf<button class="text-red-600">Stop</button></form>
            @endif
        </div>
    </div></div>
</x-app-layout>
