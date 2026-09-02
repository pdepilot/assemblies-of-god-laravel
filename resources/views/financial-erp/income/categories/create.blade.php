<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Add Income Category</h2>
            <a href="{{ route('financial-erp.income.categories.index') }}" class="px-4 py-2 text-sm font-semibold rounded-md border border-gray-300 dark:border-gray-600">Back</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('financial-erp.income.categories.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="name" class="block text-sm font-medium">Category name *</label>
                        <input id="name" name="name" type="text" required maxlength="120"
                               value="{{ old('name') }}"
                               placeholder="e.g. Youth Thanksgiving Offering"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <label for="code" class="block text-sm font-medium">Code (optional)</label>
                        <input id="code" name="code" type="text" maxlength="32"
                               value="{{ old('code') }}"
                               placeholder="Auto-generated if left blank"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 font-mono">
                        <p class="mt-1 text-xs text-gray-500">Letters, numbers, hyphens, and underscores only.</p>
                        <x-input-error :messages="$errors->get('code')" class="mt-2" />
                    </div>
                    <div>
                        <label for="account_id" class="block text-sm font-medium">Post to income account</label>
                        <select id="account_id" name="account_id"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            <option value="">Offerings Income (default)</option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account['id'] }}" @selected((string) old('account_id') === (string) $account['id'])>
                                    {{ $account['code'] }} — {{ $account['name'] }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('account_id')" class="mt-2" />
                    </div>
                    <div class="flex flex-wrap gap-3 pt-2">
                        <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Save category</button>
                        <a href="{{ route('financial-erp.income.categories.index') }}" class="px-4 py-2 rounded-md border text-sm font-semibold">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
