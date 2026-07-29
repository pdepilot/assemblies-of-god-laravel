<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Expense {{ $expense['voucher_no'] }}</h2></x-slot>
    <div class="py-10"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8"><div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
        <dl class="grid gap-3 sm:grid-cols-2 text-sm">
            <div><dt class="text-gray-500">Date</dt><dd>{{ $expense['expense_date'] }}</dd></div>
            <div><dt class="text-gray-500">Category</dt><dd>{{ $expense['category_name'] }}</dd></div>
            <div><dt class="text-gray-500">Vendor</dt><dd>{{ $expense['vendor_name'] ?: '—' }}</dd></div>
            <div><dt class="text-gray-500">Amount</dt><dd>₦{{ number_format($expense['amount'], 2) }}</dd></div>
            <div><dt class="text-gray-500">Approval</dt><dd>{{ ucfirst($expense['approval_status']) }}</dd></div>
        </dl>
    </div></div></div>
</x-app-layout>
