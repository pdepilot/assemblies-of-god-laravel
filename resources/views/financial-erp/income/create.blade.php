<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Record Income</h2>
            <a href="{{ route('financial-erp.income.categories.index') }}" class="px-4 py-2 text-sm font-semibold rounded-md border border-gray-300 dark:border-gray-600">Manage categories</a>
        </div>
    </x-slot>
    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('financial-erp.income.store') }}" class="space-y-4" id="erpIncomeForm">
                    @csrf
                    <div>
                        <label class="block text-sm">Date *</label>
                        <input type="date" name="income_date" value="{{ old('income_date', now()->toDateString()) }}" required
                               class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900">
                    </div>
                    <div>
                        <label for="category_id" class="block text-sm">Category *</label>
                        <select name="category_id" id="category_id"
                                class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900">
                            <option value="">Select a category…</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat['id'] }}" @selected((string) old('category_id') === (string) $cat['id'])>{{ $cat['name'] }}</option>
                            @endforeach
                            <option value="__new__" @selected(old('category_id') === '__new__' || (old('new_category_name') && ! old('category_id')))>
                                Not in list — create new category…
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">If the offering type is missing, choose “Not in list” and type the new name below.</p>
                        @error('category_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div id="newCategoryWrap" class="{{ old('new_category_name') || old('category_id') === '__new__' ? '' : 'hidden' }}">
                        <label for="new_category_name" class="block text-sm">New category name *</label>
                        <input id="new_category_name" name="new_category_name" type="text" maxlength="120"
                               value="{{ old('new_category_name') }}"
                               placeholder="e.g. Midweek Miracle Offering"
                               class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900">
                        <p class="mt-1 text-xs text-gray-500">This will be saved and available in the dropdown next time.</p>
                        @error('new_category_name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm">Amount *</label>
                        <input type="number" step="0.01" name="amount" required value="{{ old('amount') }}"
                               class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900">
                        @error('amount')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm">Member / payer name</label>
                        <input name="member_name" value="{{ old('member_name') }}" class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900">
                    </div>
                    <div>
                        <label class="block text-sm">Notes</label>
                        <textarea name="notes" rows="2" class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900">{{ old('notes') }}</textarea>
                    </div>
                    <button class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Save</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var select = document.getElementById('category_id');
            var wrap = document.getElementById('newCategoryWrap');
            var nameInput = document.getElementById('new_category_name');
            var form = document.getElementById('erpIncomeForm');
            if (!select || !wrap) return;

            function sync() {
                var isNew = select.value === '__new__';
                wrap.classList.toggle('hidden', !isNew);
                if (nameInput) {
                    nameInput.required = isNew;
                    if (!isNew) nameInput.value = '';
                }
            }

            select.addEventListener('change', sync);
            sync();

            if (form) {
                form.addEventListener('submit', function () {
                    if (select.value === '__new__') {
                        select.value = '';
                    }
                });
            }
        })();
    </script>
</x-app-layout>
