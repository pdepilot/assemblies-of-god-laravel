@php
    $section = $section ?? (($item['media_type'] ?? 'photo') === 'video' ? 'videos' : 'photos');
@endphp
<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Edit Gallery Item</h2></x-slot>
    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('sdtg._nav', ['canManage' => true])
            <a href="{{ route('sdtg.gallery.index', ['tab' => $section]) }}" class="text-sm text-indigo-600">&larr; Back to section</a>
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('sdtg.gallery.items.update', $item['id']) }}" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf
                @method('PUT')
                @include('sdtg.gallery.items._form')
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Update item</button>
            </form>
        </div>
    </div>
</x-app-layout>
