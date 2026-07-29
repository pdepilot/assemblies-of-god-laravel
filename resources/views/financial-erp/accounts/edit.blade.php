<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Edit Account — {{ $account->code }}</h2></x-slot>
    <div class="py-10"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8"><div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
        <form method="POST" action="{{ route('financial-erp.accounts.update', $account) }}" class="space-y-4">@csrf @method('PUT')
            <div><label class="block text-sm">Code *</label><input name="code" value="{{ old('code', $account->code) }}" required class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900"></div>
            <div><label class="block text-sm">Name *</label><input name="name" value="{{ old('name', $account->name) }}" required class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900"></div>
            <div><label class="block text-sm">Type *</label><select name="account_type" class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900">@foreach($types as $t)<option value="{{ $t }}" @selected(old('account_type', $account->account_type)===$t)>{{ ucfirst($t) }}</option>@endforeach</select></div>
            <div class="flex gap-3"><button class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Update</button><a href="{{ route('financial-erp.accounts.index') }}" class="px-4 py-2 rounded-md border text-sm">Cancel</a></div>
        </form>
        <form method="POST" action="{{ route('financial-erp.accounts.destroy', $account) }}" class="mt-6" data-confirm="Archive this account?" data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger">@csrf @method('DELETE')<button class="text-sm text-red-600">Archive account</button></form>
    </div></div></div>
</x-app-layout>
