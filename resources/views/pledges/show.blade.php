<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Pledge — {{ $pledge['donor_name'] }}</h2></x-slot>
    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Pledged</div><div class="text-xl font-semibold">₦{{ number_format($pledge['pledged_amount'], 2) }}</div></div>
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Paid</div><div class="text-xl font-semibold">₦{{ number_format($pledge['amount_paid'], 2) }}</div></div>
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Remaining</div><div class="text-xl font-semibold">₦{{ number_format($pledge['remaining_balance'], 2) }}</div></div>
            </div>
            @if ($canManage && $pledge['status'] !== 'completed')
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <form method="POST" action="{{ route('pledges.payment', $pledge['id']) }}" class="flex flex-wrap gap-3 items-end">
                        @csrf
                        <div><label class="block text-sm">Payment amount</label><input type="number" step="0.01" name="amount" required class="mt-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"></div>
                        <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Record payment</button>
                    </form>
                    @error('amount')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                </div>
            @endif
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold mb-4">Payment history</h3>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700"><tr>
                        <th class="px-3 py-2 text-left text-xs">Date</th><th class="px-3 py-2 text-left text-xs">Amount</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($payments as $payment)
                            <tr>
                                <td class="px-3 py-2 text-sm">{{ $payment['paid_at'] }}</td>
                                <td class="px-3 py-2 text-sm">₦{{ number_format($payment['amount'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="px-3 py-6 text-center text-sm text-gray-500">No payments recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
