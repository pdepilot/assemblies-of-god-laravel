<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">New sermon</h2>
            <p class="text-sm text-gray-500 mt-1">Add a cover image and optionally upload a video up to 15 MB.</p>
        </div>
    </x-slot>
    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('sermons._nav', ['canManage' => true])
            <form method="POST" action="{{ route('sermon.sermons.store') }}" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-6">
                @csrf
                @include('sermons.sermons._form')
                <div class="flex items-center gap-3 pt-2">
                    <x-primary-button>Save sermon</x-primary-button>
                    <a href="{{ route('sermon.sermons.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
