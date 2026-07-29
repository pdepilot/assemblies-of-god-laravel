<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl">Create registration form</h2>
            <p class="text-sm text-gray-500 mt-1">Pick a template, customize fields, then create.</p>
        </div>
    </x-slot>
    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                @include('registration-portals._form')
            </div>
        </div>
    </div>
</x-app-layout>
