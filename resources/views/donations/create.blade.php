<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Record Manual Donation</h2>
    </x-slot>
    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('donations.store') }}" class="space-y-4">
                    @csrf
                    <div><label class="block text-sm">Donor name</label><input type="text" name="donor_name" value="{{ old('donor_name') }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"></div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="block text-sm">Email</label><input type="email" name="donor_email" value="{{ old('donor_email') }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"></div>
                        <div><label class="block text-sm">Phone</label><input type="text" name="donor_phone" value="{{ old('donor_phone') }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"></div>
                    </div>
                    <div><label class="block text-sm">Amount (NGN) *</label><input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">@error('amount')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror</div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="block text-sm">Category</label>
                            <select name="category_id" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                @foreach($categories as $cat)<option value="{{ $cat['id'] }}" @selected(old('category_id')==$cat['id'])>{{ $cat['name'] }}</option>@endforeach
                            </select></div>
                        <div><label class="block text-sm">Fund scope</label>
                            <select name="fund_scope" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                @foreach($scopes as $sc)<option value="{{ $sc }}" @selected(old('fund_scope','church')===$sc)>{{ ucfirst($sc) }}</option>@endforeach
                            </select></div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="block text-sm">Payment method</label>
                            <select name="payment_method" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                @foreach($paymentMethods as $method)<option value="{{ $method }}" @selected(old('payment_method','cash')===$method)>{{ ucfirst(str_replace('_',' ',$method)) }}</option>@endforeach
                            </select></div>
                        <div><label class="block text-sm">Donation date</label><input type="date" name="donation_date" value="{{ old('donation_date', now()->toDateString()) }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"></div>
                    </div>
                    <div><label class="block text-sm">Notes</label><textarea name="notes" rows="3" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('notes') }}</textarea></div>
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_anonymous" value="1" @checked(old('is_anonymous'))> Anonymous</label>
                    <div class="flex gap-3">
                        <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Save donation</button>
                        <a href="{{ route('donations.index') }}" class="px-4 py-2 rounded-md border text-sm">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
