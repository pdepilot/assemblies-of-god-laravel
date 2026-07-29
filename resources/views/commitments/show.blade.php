<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ $program['name'] }}</h2>
    </x-slot>
    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Target</div><div class="text-xl font-semibold">₦{{ number_format($program['target_amount'], 2) }}</div></div>
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Paid</div><div class="text-xl font-semibold">₦{{ number_format($program['paid_total'], 2) }}</div></div>
                <div class="rounded-lg border p-4 bg-white dark:bg-gray-800"><div class="text-sm text-gray-500">Givers</div><div class="text-xl font-semibold">{{ $program['giver_count'] }}</div></div>
            </div>
            @if ($canManage)
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold mb-4">Add commitment giver</h3>
                    <form method="POST" action="{{ route('commitments.givers.store', $program['id']) }}" class="grid gap-4 sm:grid-cols-2">
                        @csrf
                        <div><label class="block text-sm">Donor name *</label><input type="text" name="donor_name" value="{{ old('donor_name') }}" required class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"></div>
                        <div><label class="block text-sm">Committed amount *</label><input type="number" step="0.01" name="committed_amount" value="{{ old('committed_amount') }}" required class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"></div>
                        <div><label class="block text-sm">Email</label><input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"></div>
                        <div><label class="block text-sm">Frequency</label>
                            <select name="frequency" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                                @foreach($frequencies as $freq)<option value="{{ $freq }}">{{ ucfirst(str_replace('_',' ',$freq)) }}</option>@endforeach
                            </select></div>
                        <div class="sm:col-span-2"><button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Add giver</button></div>
                    </form>
                </div>
            @endif
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold mb-4">Givers</h3>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700"><tr>
                        <th class="px-3 py-2 text-left text-xs">Donor</th><th class="px-3 py-2 text-left text-xs">Committed</th>
                        <th class="px-3 py-2 text-left text-xs">Paid</th><th class="px-3 py-2 text-left text-xs">Status</th>
                        @if($canManage)<th class="px-3 py-2 text-left text-xs">Payment</th>@endif
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($givers as $giver)
                            <tr>
                                <td class="px-3 py-2 text-sm">{{ $giver['donor_name'] }}</td>
                                <td class="px-3 py-2 text-sm">₦{{ number_format($giver['committed_amount'], 2) }}</td>
                                <td class="px-3 py-2 text-sm">₦{{ number_format($giver['amount_paid'], 2) }}</td>
                                <td class="px-3 py-2 text-sm">{{ ucfirst($giver['status']) }}</td>
                                @if($canManage)
                                    <td class="px-3 py-2 text-sm">
                                        @if($giver['status'] !== 'completed')
                                            <form method="POST" action="{{ route('commitments.givers.payment', $giver['id']) }}" class="flex gap-2 items-center">
                                                @csrf
                                                <input type="number" step="0.01" name="amount" placeholder="Amount" class="w-28 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm">
                                                <button type="submit" class="text-indigo-600 hover:underline text-sm">Record</button>
                                            </form>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="{{ $canManage ? 5 : 4 }}" class="px-3 py-6 text-center text-sm text-gray-500">No givers yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
