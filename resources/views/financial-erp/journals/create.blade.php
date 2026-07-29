<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">New Journal Entry</h2></x-slot>
    <div class="py-10"><div class="max-w-5xl mx-auto sm:px-6 lg:px-8"><div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
        <form method="POST" action="{{ route('financial-erp.journals.store') }}" class="space-y-4">@csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="block text-sm">Date *</label><input type="date" name="header[journal_date]" value="{{ old('header.journal_date', now()->toDateString()) }}" required class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900"></div>
                <div><label class="block text-sm">Reference</label><input name="header[reference]" class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900"></div>
            </div>
            <div><label class="block text-sm">Memo</label><textarea name="header[memo]" rows="2" class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900">{{ old('header.memo') }}</textarea>@error('memo')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div class="space-y-3">
                <h3 class="font-semibold text-sm">Lines (minimum 2, debits = credits)</h3>
                @for($i = 0; $i < 2; $i++)
                    <div class="grid gap-3 sm:grid-cols-4 border rounded p-3">
                        <div class="sm:col-span-2"><label class="block text-xs">Account</label><select name="lines[{{ $i }}][account_id]" required class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900"><option value="">Select</option>@foreach($accounts as $acc)<option value="{{ $acc['id'] }}">{{ $acc['code'] }} — {{ $acc['name'] }}</option>@endforeach</select></div>
                        <div><label class="block text-xs">Debit</label><input type="number" step="0.01" name="lines[{{ $i }}][debit]" class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900"></div>
                        <div><label class="block text-xs">Credit</label><input type="number" step="0.01" name="lines[{{ $i }}][credit]" class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900"></div>
                    </div>
                @endfor
            </div>
            <label class="inline-flex gap-2 text-sm"><input type="checkbox" name="post" value="1"> Post immediately</label>
            <div class="flex gap-3"><button class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Save journal</button><a href="{{ route('financial-erp.journals.index') }}" class="px-4 py-2 rounded-md border text-sm">Cancel</a></div>
        </form>
    </div></div></div>
</x-app-layout>
