<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ $sermon['title'] ?? 'Sermon' }}</h2></x-slot>
    <div class="py-10"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @include('sermons._nav', ['canManage' => $canManage])
        <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-2">
            <p><strong>Code:</strong> {{ $sermon['sermon_code'] }}</p>
            <p><strong>Status:</strong> {{ $sermon['status'] }}</p>
            <p><strong>Date:</strong> {{ $sermon['sermon_date'] }}</p>
            <p><strong>Minister:</strong> {{ $sermon['minister_name'] }}</p>
            @php
                $resolver = app(\App\Services\PublicSite\PublicAssetResolver::class);
                $cover = trim((string) ($sermon['featured_image'] ?? ''));
                $video = trim((string) ($sermon['video_file_path'] ?? ''));
            @endphp
            @if ($cover !== '')
                <p><strong>Cover:</strong></p>
                <img src="{{ $resolver->url($cover) }}" alt="" class="max-h-48 rounded object-cover">
            @endif
            @if ($video !== '')
                <p class="mt-3"><strong>Video:</strong></p>
                <video class="w-full max-w-xl rounded" controls preload="metadata" src="{{ $resolver->url($video) }}"></video>
            @endif
            @if ($canManage)
                <a href="{{ route('sermon.sermons.edit', $sermon['id']) }}" class="text-indigo-600">Edit</a>
            @endif
        </div>
    </div></div>
</x-app-layout>
