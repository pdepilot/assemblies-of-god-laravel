<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Our Worship</h2>
                <p class="text-sm text-gray-500 mt-1">Times, titles, buttons, and the map on the public homepage worship section.</p>
            </div>
            <a href="{{ url('/') }}#worship" target="_blank" rel="noopener" class="px-3 py-2 text-sm rounded-md border">View homepage section</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">
                    <ul class="list-disc pl-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            @include('website._nav', ['canManage' => $canManage])

            <form method="POST" action="{{ route('website.worship.update') }}" class="space-y-6 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                @csrf
                @method('PUT')

                @include('website.worship._program-fields', [
                    'programs' => $programs,
                    'location' => $location,
                ])

                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Save Our Worship</button>
            </form>
        </div>
    </div>
</x-app-layout>
