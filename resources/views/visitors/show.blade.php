<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ $visitor['full_name'] }}</h2>
            <div class="flex gap-2">
                @if ($canManage && ! $visitor['is_promoted'])
                    <a href="{{ route('visitors.edit', $visitor['id']) }}"
                       class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md bg-indigo-600 text-white hover:bg-indigo-700">Edit</a>
                @endif
                <a href="{{ route('visitors.index') }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md border border-gray-300 dark:border-gray-600">Back</a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-sm text-gray-500">Visitor code</dt><dd class="font-medium font-mono">{{ $visitor['visitor_code'] }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Follow-up</dt><dd class="font-medium">{{ $followUpLabels[$visitor['follow_up_status']] ?? $visitor['follow_up_status'] }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Phone</dt><dd class="font-medium">{{ $visitor['phone'] }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Email</dt><dd class="font-medium">{{ $visitor['email'] ?: '—' }}</dd></div>
                    <div><dt class="text-sm text-gray-500">First visit</dt><dd class="font-medium">{{ $visitor['first_visit_date'] }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Visit count</dt><dd class="font-medium">{{ $visitor['visit_count'] }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Service</dt><dd class="font-medium">{{ $visitor['service_attended'] ?: '—' }}</dd></div>
                    <div><dt class="text-sm text-gray-500">How heard</dt><dd class="font-medium">{{ $visitor['how_heard'] ?: '—' }}</dd></div>
                </dl>
                @if ($visitor['notes'])
                    <div><dt class="text-sm text-gray-500">Notes</dt><dd class="mt-1">{{ $visitor['notes'] }}</dd></div>
                @endif
            </div>

            @if ($canManage && ! $visitor['is_promoted'])
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold mb-4">Record return visit</h3>
                    <form method="POST" action="{{ route('visitors.record-return', $visitor['id']) }}" class="flex flex-wrap gap-3 items-end">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Visit date</label>
                            <input type="date" name="visit_date" value="{{ now()->toDateString() }}"
                                   class="mt-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Record visit</button>
                    </form>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold mb-4">Promote to membership</h3>
                    <form method="POST" action="{{ route('visitors.promote', $visitor['id']) }}" class="space-y-4">
                        @csrf
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="confirm_membership" value="1" required />
                            Confirm full membership promotion
                        </label>
                        <x-input-error :messages="$errors->get('confirm_membership')" class="mt-2" />
                        <button type="submit" class="px-4 py-2 rounded-md bg-green-600 text-white text-sm font-semibold">Promote to member</button>
                    </form>
                </div>
            @endif

            @if ($visitor['is_promoted'] && $visitor['promoted_member_id'])
                <div class="rounded-md bg-blue-50 dark:bg-blue-900/30 p-4 text-sm">
                    Promoted to member —
                    <a href="{{ route('members.show', $visitor['promoted_member_id']) }}" class="text-indigo-600 hover:underline">View member record</a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
