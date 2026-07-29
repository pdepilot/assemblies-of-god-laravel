<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Edit Album</h2></x-slot>
    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('sdtg._nav', ['canManage' => true])
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('sdtg.gallery.albums.update', $album['id']) }}" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf
                @method('PUT')
                @include('sdtg.gallery.albums._form', ['album' => $album])
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Update Album</button>
            </form>
        </div>
    </div>
</x-app-layout>
