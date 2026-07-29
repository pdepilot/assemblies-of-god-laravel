<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">New GL Account</h2></x-slot>
    <div class="py-10"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8"><div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
        <form method="POST" action="{{ route('financial-erp.accounts.store') }}" class="space-y-4">@csrf
            <div><label class="block text-sm">Code *</label><input name="code" required class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900">@error('code')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="block text-sm">Name *</label><input name="name" required class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900"></div>
            <div><label class="block text-sm">Type *</label><select name="account_type" class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900">@foreach($types as $t)<option value="{{ $t }}">{{ ucfirst($t) }}</option>@endforeach</select></div>
            <div><label class="block text-sm">Opening balance</label><input type="number" step="0.01" name="opening_balance" class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900"></div>
            <div><label class="block text-sm">Description</label><textarea name="description" rows="2" class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900"></textarea></div>
            <label class="inline-flex gap-2 text-sm"><input type="checkbox" name="is_postable" value="1" checked> Postable</label>
            <div class="flex gap-3"><button class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Save</button><a href="{{ route('financial-erp.accounts.index') }}" class="px-4 py-2 rounded-md border text-sm">Cancel</a></div>
        </form>
    </div></div></div>
</x-app-layout>
