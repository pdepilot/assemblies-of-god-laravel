<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">SDTG Page Content</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage the editable copy shown on the public SDTG homepage, donate page and livestream page.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">{{ session('error') }}</div>
            @endif
            @include('sdtg._nav', ['canManage' => $canManage])

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2">Section</th>
                            <th>Description</th>
                            <th>Type</th>
                            <th>Updated</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($sections as $section)
                        <tr class="border-b align-top">
                            <td class="py-3 font-medium whitespace-nowrap">{{ $section['label'] }}</td>
                            <td class="text-gray-500 dark:text-gray-400 max-w-md">{{ $section['description'] }}</td>
                            <td class="capitalize whitespace-nowrap">{{ $section['type'] }}</td>
                            <td class="whitespace-nowrap">{{ $section['updated_at'] ?? '—' }}</td>
                            <td class="whitespace-nowrap space-x-3">
                                @if ($canManage)
                                    <a href="{{ route('sdtg.content.edit', $section['key']) }}" class="text-indigo-600 font-semibold">Edit</a>
                                @endif
                                <a href="{{ route($section['public_route'], $section['public_param'] ? ['path' => $section['public_param']] : []) }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-indigo-600">View public →</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-gray-500">No content sections found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
