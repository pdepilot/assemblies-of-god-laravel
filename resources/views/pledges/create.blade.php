<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">New Pledge</h2></x-slot>
    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('pledges.store') }}" class="space-y-4">
                    @csrf
                    <div><label class="block text-sm">Donor name *</label><input type="text" name="donor_name" value="{{ old('donor_name') }}" required class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"></div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="block text-sm">Email</label><input type="email" name="donor_email" value="{{ old('donor_email') }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"></div>
                        <div><label class="block text-sm">Phone</label><input type="text" name="donor_phone" value="{{ old('donor_phone') }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"></div>
                    </div>
                    <div><label class="block text-sm">Pledged amount *</label><input type="number" step="0.01" name="pledged_amount" value="{{ old('pledged_amount') }}" required class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">@error('pledged_amount')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror</div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="block text-sm">Installments</label><input type="number" name="installment_count" value="{{ old('installment_count') }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"></div>
                        <div><label class="block text-sm">Category</label>
                            <select name="category_id" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                <option value="">—</option>@foreach($categories as $cat)<option value="{{ $cat['id'] }}">{{ $cat['name'] }}</option>@endforeach
                            </select></div>
                    </div>
                    <div><label class="block text-sm">Start date</label><input type="date" name="start_date" value="{{ old('start_date', now()->toDateString()) }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"></div>
                    <div><label class="block text-sm">Notes</label><textarea name="notes" rows="3" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('notes') }}</textarea></div>
                    <div class="flex gap-3">
                        <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Create pledge</button>
                        <a href="{{ route('pledges.index') }}" class="px-4 py-2 rounded-md border text-sm">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
