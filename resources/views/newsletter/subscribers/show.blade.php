<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Subscriber</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('communication-hub._nav', ['canManage' => $canManage])

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                <div>
                    <p class="text-sm text-gray-500">Email</p>
                    <p class="text-lg font-medium">{{ $subscriber['email'] ?? '' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <p class="capitalize">{{ $subscriber['status'] ?? '' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Form source</p>
                    <p>{{ $subscriber['source'] ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Location</p>
                    @if (! empty($subscriber['location_display']))
                        <p>{{ $subscriber['location_display'] }}</p>
                        @if (! empty($subscriber['location_source_label']))
                            <p class="text-sm text-gray-500">Source: {{ $subscriber['location_source_label'] }}</p>
                        @endif
                        @if (! empty($subscriber['location_accuracy_label']))
                            <p class="text-sm text-gray-500">Accuracy: {{ $subscriber['location_accuracy_label'] }}</p>
                        @endif
                    @else
                        <p class="text-gray-500">No location stored</p>
                    @endif
                </div>
                @if (! empty($subscriber['ip_address']))
                    <div>
                        <p class="text-sm text-gray-500">IP address (subscription time)</p>
                        <p class="font-mono text-sm">{{ $subscriber['ip_address'] }}</p>
                    </div>
                @endif
                @if ($canManage)
                    <div class="flex flex-wrap gap-2 pt-2">
                        <form method="POST" action="{{ route('newsletter-subscribers.toggle-status', $subscriber['id']) }}">
                            @csrf
                            <button class="px-4 py-2 border rounded-md">Toggle status</button>
                        </form>
                        <form method="POST" action="{{ route('newsletter-subscribers.destroy', $subscriber['id']) }}" data-confirm="Delete this subscriber permanently? This cannot be undone." data-confirm-title="Please confirm" data-confirm-ok="Delete" data-confirm-tone="danger">
                            @csrf
                            @method('DELETE')
                            <button class="px-4 py-2 border border-red-300 text-red-700 rounded-md">Delete subscriber</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
