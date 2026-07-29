<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Edit Media</h2>
    </x-slot>
    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
            @endif
            @include('sdtg._nav', ['canManage' => true])
            <form method="POST" action="{{ route('sdtg.media-library.assets.update', $asset['id']) }}" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf
                @method('PUT')
                @include('sdtg.media-library.assets._form')
                <div class="flex gap-3">
                    <button class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Update</button>
                    <a href="{{ route('sdtg.media-library.index') }}" class="px-4 py-2 border rounded-md text-sm">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
