<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Team Members</h2></x-slot>
    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @include('website._nav', ['canManage' => $canManage])
            @if ($canManage)
                <a href="{{ route('website.team.create') }}" class="inline-block px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Add member</a>
            @endif
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2">Name</th>
                            <th>Role</th>
                            <th>Type</th>
                            <th>Active</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($members as $member)
                            <tr class="border-b">
                                <td class="py-2">{{ $member['full_name'] }}</td>
                                <td>{{ $member['role_title'] ?: '—' }}</td>
                                <td>{{ $member['member_type'] ?? 'member' }}</td>
                                <td>{{ !empty($member['is_active']) ? 'Yes' : 'No' }}</td>
                                <td>
                                    @if ($canManage)
                                        <a href="{{ route('website.team.edit', $member['id']) }}" class="text-indigo-600">Edit</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-gray-500">No team members yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
