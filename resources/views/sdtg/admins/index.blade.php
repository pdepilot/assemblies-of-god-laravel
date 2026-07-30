<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">SDTG Admin Access</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Set which platforms each administrator can sign into. This is not full RBAC.</p>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
    @endif

    <div class="overflow-x-auto rounded border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold">Name</th>
                    <th class="px-4 py-3 text-left font-semibold">Email</th>
                    <th class="px-4 py-3 text-left font-semibold">Role</th>
                    <th class="px-4 py-3 text-left font-semibold">Platform access</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($admins as $row)
                    <tr>
                        <td class="px-4 py-3">{{ $row->full_name ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $row->email }}</td>
                        <td class="px-4 py-3">{{ $row->role }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('sdtg.admins.platform-access', $row->id) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PUT')
                                <select name="platform_access" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                                    @foreach ($platformOptions as $option)
                                        <option value="{{ $option }}" @selected(($row->platform_access ?? 'both') === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="rounded bg-indigo-600 px-3 py-1 text-white text-xs">Save</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
