<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Record Expense</h2></x-slot>
    <div class="py-10"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8"><div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
        <form method="POST" action="{{ route('financial-erp.expenses.store') }}" class="space-y-4">@csrf
            <div><label class="block text-sm">Date *</label><input type="date" name="expense_date" value="{{ old('expense_date', now()->toDateString()) }}" required class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900"></div>
            <div><label class="block text-sm">Category *</label><select name="category_id" required class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900">@foreach($categories as $cat)<option value="{{ $cat['id'] }}">{{ $cat['name'] }}</option>@endforeach</select></div>
            <div><label class="block text-sm">Vendor</label><select name="vendor_id" class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900"><option value="">—</option>@foreach($vendors as $v)<option value="{{ $v['id'] }}">{{ $v['name'] }}</option>@endforeach</select></div>
            <div><label class="block text-sm">Amount *</label><input type="number" step="0.01" name="amount" required class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900">@error('amount')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="block text-sm">Notes</label><textarea name="notes" rows="2" class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900"></textarea></div>
            <button class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Save</button>
        </form>
    </div></div></div>
</x-app-layout>
