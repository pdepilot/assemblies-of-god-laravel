<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">SDTG Community</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @include('sdtg._nav', ['canManage' => $canManage])

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <h3 class="font-semibold mb-3">Testimonials</h3>
                <table class="min-w-full text-sm mb-8">
                    <thead><tr class="text-left border-b"><th class="py-2">Name</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($testimonials as $row)
                        <tr class="border-b">
                            <td class="py-2">{{ $row['full_name'] ?? $row['name'] ?? '—' }}</td>
                            <td class="capitalize">{{ $row['status'] ?? '—' }}</td>
                            <td>
                                @if ($canManage)
                                    <form method="POST" action="{{ route('sdtg.community.testimonials.update', $row['id']) }}" class="inline-flex gap-2 items-center">
                                        @csrf
                                        @method('PUT')
                                        <select name="status" class="rounded border-gray-300 text-sm">
                                            @foreach (['pending', 'approved', 'rejected'] as $status)
                                                <option value="{{ $status }}" @selected(($row['status'] ?? '') === $status)>{{ $status }}</option>
                                            @endforeach
                                        </select>
                                        <button class="text-indigo-600 text-sm">Update</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-4 text-gray-500">No testimonials.</td></tr>
                    @endforelse
                    </tbody>
                </table>

                <h3 class="font-semibold mb-3">Prayer Requests</h3>
                <table class="min-w-full text-sm mb-8">
                    <thead><tr class="text-left border-b"><th class="py-2">Name</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($prayerRequests as $row)
                        <tr class="border-b">
                            <td class="py-2">{{ $row['full_name'] ?? $row['name'] ?? '—' }}</td>
                            <td class="capitalize">{{ $row['status'] ?? '—' }}</td>
                            <td>
                                @if ($canManage)
                                    <form method="POST" action="{{ route('sdtg.community.prayer.update', $row['id']) }}" class="inline-flex gap-2 items-center">
                                        @csrf
                                        @method('PUT')
                                        <select name="status" class="rounded border-gray-300 text-sm">
                                            @foreach (['new', 'praying', 'answered', 'archived'] as $status)
                                                <option value="{{ $status }}" @selected(($row['status'] ?? '') === $status)>{{ $status }}</option>
                                            @endforeach
                                        </select>
                                        <button class="text-indigo-600 text-sm">Update</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-4 text-gray-500">No prayer requests.</td></tr>
                    @endforelse
                    </tbody>
                </table>

                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <h3 class="font-semibold">Memory Submissions</h3>
                    <a href="{{ route('sdtg.gallery.index', ['tab' => 'community']) }}" class="text-sm text-indigo-600">Open in Gallery page manager</a>
                </div>
                <p class="text-xs text-gray-500 mb-3">Featured memories appear on the public <code>/sdgt/gallery</code> community wall.</p>
                <table class="min-w-full text-sm">
                    <thead><tr class="text-left border-b"><th class="py-2">Name</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($memories as $row)
                        <tr class="border-b">
                            <td class="py-2">{{ $row['full_name'] ?? $row['name'] ?? '—' }}</td>
                            <td class="capitalize">{{ $row['status'] ?? '—' }}</td>
                            <td>
                                @if ($canManage)
                                    <form method="POST" action="{{ route('sdtg.community.memories.update', $row['id']) }}" class="inline-flex gap-2 items-center">
                                        @csrf
                                        @method('PUT')
                                        <select name="status" class="rounded border-gray-300 text-sm">
                                            @foreach (['pending', 'featured', 'rejected'] as $status)
                                                <option value="{{ $status }}" @selected(($row['status'] ?? '') === $status)>{{ $status }}</option>
                                            @endforeach
                                        </select>
                                        <button class="text-indigo-600 text-sm">Update</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-4 text-gray-500">No memory submissions.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
