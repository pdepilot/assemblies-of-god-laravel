<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Testimony Review</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $testimony['source_label'] }} · {{ $testimony['created_at'] }}</p>
            </div>
            <a href="{{ route('testimonies.index') }}" class="text-sm text-indigo-600">Back to queue</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">{{ $errors->first() }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="flex flex-wrap items-start gap-4">
                    @if ($testimony['photo_url'])
                        <img src="{{ $testimony['photo_url'] }}" alt="" class="h-20 w-20 rounded-full object-cover">
                    @endif
                    <div>
                        <p class="text-lg font-semibold">{{ $testimony['full_name'] }}</p>
                        @if ($testimony['role_title'])
                            <p class="text-gray-500">{{ $testimony['role_title'] }}</p>
                        @endif
                        <p class="text-sm text-gray-500">{{ $testimony['email'] }}</p>
                    </div>
                </div>
                <p class="whitespace-pre-wrap text-gray-800 dark:text-gray-200">{{ $testimony['testimony_text'] }}</p>
                <div class="text-sm text-gray-500 space-y-1">
                    <p>Status: <span class="capitalize">{{ $testimony['status'] }}</span></p>
                    @if ($testimony['is_featured'])
                        <p>Featured on public site</p>
                    @endif
                    @if ($testimony['amount'])
                        <p>Amount mentioned: {{ number_format($testimony['amount'], 2) }}</p>
                    @endif
                </div>
            </div>

            @if ($canManage)
                <form method="POST" action="{{ route('testimonies.update', $testimony['id']) }}" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="status" class="block text-sm font-medium mb-1">Status</label>
                        <select id="status" name="status" class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" @selected($testimony['status'] === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_featured" value="1" @checked($testimony['is_featured'])>
                        Feature on public page
                    </label>
                    <button class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Save changes</button>
                </form>

                <form method="POST" action="{{ route('testimonies.destroy', $testimony['id']) }}" data-confirm="Delete this testimony permanently?" data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    @csrf
                    @method('DELETE')
                    <button class="px-4 py-2 border border-red-300 text-red-700 rounded-md text-sm">Delete testimony</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
