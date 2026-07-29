<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Edit Registration Portal</h2></x-slot>
    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                @include('registration-portals._form')
            </div>
        </div>
    </div>
</x-app-layout>
