<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Communication Logs</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('communication-hub._nav', ['canManage' => $canManage])

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <h3 class="font-semibold">Recent logs</h3>
                    <p class="text-xs text-gray-500">
                        Showing {{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }}
                    </p>
                </div>

                @forelse ($logs as $row)
                    <p class="py-2 border-b">
                        {{ $row['channel'] ?? 'email' }} — {{ $row['subject'] ?? '—' }} —
                        <a href="{{ route('communication-hub.logs.show', ['log' => $row['id'], 'source' => $row['source'] ?? 'communication_logs']) }}" class="text-indigo-600">View</a>
                    </p>
                @empty
                    <p class="text-gray-500">No logs.</p>
                @endforelse

                @if ($logs->hasPages())
                    <div class="mt-4">{{ $logs->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
