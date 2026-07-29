<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">New Commitment Program</h2></x-slot>
    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('commitments.store') }}" class="space-y-4">
                    @csrf
                    <div><label class="block text-sm">Name *</label><input type="text" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">@error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror</div>
                    <div><label class="block text-sm">Description</label><textarea name="description" rows="3" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('description') }}</textarea></div>
                    <div><label class="block text-sm">Target amount</label><input type="number" step="0.01" name="target_amount" value="{{ old('target_amount') }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"></div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="block text-sm">Start date</label><input type="date" name="start_date" value="{{ old('start_date') }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"></div>
                        <div><label class="block text-sm">End date</label><input type="date" name="end_date" value="{{ old('end_date') }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"></div>
                    </div>
                    <div><label class="block text-sm">Status</label>
                        <select name="status" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            @foreach($statuses as $st)<option value="{{ $st }}" @selected(old('status','draft')===$st)>{{ ucfirst($st) }}</option>@endforeach
                        </select></div>
                    <div class="flex gap-3">
                        <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Create program</button>
                        <a href="{{ route('commitments.index') }}" class="px-4 py-2 rounded-md border text-sm">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
