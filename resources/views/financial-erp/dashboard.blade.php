<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Financial ERP</h2>
    </x-slot>
    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Today income</div><div class="text-2xl font-semibold">₦{{ number_format($stats['today_income'], 2) }}</div></div>
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Today expenses</div><div class="text-2xl font-semibold">₦{{ number_format($stats['today_expenses'], 2) }}</div></div>
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Month income</div><div class="text-2xl font-semibold">₦{{ number_format($stats['month_income'], 2) }}</div></div>
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Month expenses</div><div class="text-2xl font-semibold">₦{{ number_format($stats['month_expenses'], 2) }}</div></div>
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Cash balance</div><div class="text-2xl font-semibold">₦{{ number_format($stats['cash_balance'], 2) }}</div></div>
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Bank balance</div><div class="text-2xl font-semibold">₦{{ number_format($stats['bank_balance'], 2) }}</div></div>
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Pending approvals</div><div class="text-2xl font-semibold">{{ $stats['pending_approvals'] }}</div></div>
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">GL accounts</div><div class="text-2xl font-semibold">{{ $stats['account_count'] }}</div></div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold mb-4">Modules</h3>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('financial-erp.accounts.index') }}" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Chart of Accounts</a>
                    <a href="{{ route('financial-erp.journals.index') }}" class="px-4 py-2 rounded-md border text-sm">Journal Entries</a>
                    <a href="{{ route('financial-erp.income.index') }}" class="px-4 py-2 rounded-md border text-sm">Income</a>
                    <a href="{{ route('financial-erp.expenses.index') }}" class="px-4 py-2 rounded-md border text-sm">Expenses</a>
                    <a href="{{ route('financial-erp.vendors.index') }}" class="px-4 py-2 rounded-md border text-sm">Vendors</a>
                    <a href="{{ route('financial-erp.projects.index') }}" class="px-4 py-2 rounded-md border text-sm">Projects</a>
                    <a href="{{ route('financial-erp.audit.index') }}" class="px-4 py-2 rounded-md border text-sm">Audit Trail</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
