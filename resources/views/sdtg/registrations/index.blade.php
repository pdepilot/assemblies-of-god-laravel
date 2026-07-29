<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">SDTG Registrations</h2></x-slot>
    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))<div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>@endif
            @include('sdtg._nav', ['canManage' => $canManage])
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead><tr class="text-left border-b"><th class="py-2">Name</th><th>Code</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($result['items'] as $item)
                        <tr class="border-b">
                            <td class="py-2">{{ $item['full_name'] }}</td>
                            <td>{{ $item['registration_code'] }}</td>
                            <td>{{ $item['status'] }}</td>
                            <td><a href="{{ route('sdtg.registrations.show', $item['id']) }}" class="text-indigo-600">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-gray-500">No registrations found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
