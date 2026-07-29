<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Journal {{ $journal['journal_no'] }}</h2></x-slot>
    <div class="py-10"><div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if(session('status'))<div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>@endif
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <dl class="grid gap-3 sm:grid-cols-2 text-sm"><div><dt class="text-gray-500">Date</dt><dd>{{ $journal['journal_date'] }}</dd></div><div><dt class="text-gray-500">Status</dt><dd>{{ ucfirst($journal['status']) }}</dd></div><div class="sm:col-span-2"><dt class="text-gray-500">Memo</dt><dd>{{ $journal['memo'] ?: '—' }}</dd></div></dl>
            @if($canManage && $journal['status']==='draft')<form method="POST" action="{{ route('financial-erp.journals.post', $journal['id']) }}" class="mt-4">@csrf<button class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Post journal</button></form>@endif
        </div>
        <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
            <table class="min-w-full divide-y"><thead><tr><th class="px-3 py-2 text-left text-xs">Account</th><th class="px-3 py-2 text-left text-xs">Debit</th><th class="px-3 py-2 text-left text-xs">Credit</th></tr></thead>
            <tbody>@foreach($journal['lines'] as $line)<tr><td class="px-3 py-2 text-sm">{{ $line['account_code'] }} — {{ $line['account_name'] }}</td><td class="px-3 py-2 text-sm">@if($line['debit']>0)₦{{ number_format($line['debit'], 2) }}@endif</td><td class="px-3 py-2 text-sm">@if($line['credit']>0)₦{{ number_format($line['credit'], 2) }}@endif</td></tr>@endforeach</tbody></table>
        </div>
    </div></div>
</x-app-layout>
