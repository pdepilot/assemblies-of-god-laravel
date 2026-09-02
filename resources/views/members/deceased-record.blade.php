<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Record deceased member</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Select someone already in the church members list. This does not create a new registration.
                </p>
            </div>
            <a href="{{ route('members.deceased') }}" class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                Back to Deceased
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-6">
                <form method="GET" action="{{ route('members.deceased.record') }}" class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <label for="member_q" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Filter members list</label>
                        <input id="member_q" name="member_q" value="{{ $pickerQuery }}"
                               placeholder="Search by name, code, phone, department…"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-md border border-gray-300 dark:border-gray-600 text-sm font-semibold">Filter</button>
                    @if ($pickerQuery !== '')
                        <a href="{{ route('members.deceased.record') }}" class="px-4 py-2 rounded-md border text-sm">Clear</a>
                    @endif
                </form>

                <form method="POST" action="{{ route('members.deceased.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="member_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Church member</label>
                        <select id="member_id" name="member_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            <option value="">Choose a member…</option>
                            @foreach ($livingMembers as $member)
                                <option value="{{ $member['id'] }}" @selected((int) old('member_id') === (int) $member['id'])>
                                    {{ $member['full_name'] }}
                                    @if ($member['member_code'])
                                        ({{ $member['member_code'] }})
                                    @endif
                                    @if ($member['department'])
                                        · {{ $member['department'] }}
                                    @endif
                                    · {{ $statusLabels[$member['status']] ?? $member['status'] }}
                                </option>
                            @endforeach
                        </select>
                        @if ($livingMembers === [])
                            <p class="mt-2 text-sm text-amber-600 dark:text-amber-400">
                                No living members match this filter.
                                <a href="{{ route('members.index') }}" class="underline">Open the Members directory</a>
                                if you need to add someone first.
                            </p>
                        @else
                            <p class="mt-1 text-xs text-gray-500">Showing up to 500 living members. Use the filter if the list is long.</p>
                        @endif
                        <x-input-error :messages="$errors->get('member_id')" class="mt-2" />
                    </div>

                    <div>
                        <label for="date_of_death" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date of death</label>
                        <input id="date_of_death" name="date_of_death" type="date" required
                               value="{{ old('date_of_death') }}"
                               max="{{ now()->toDateString() }}"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                        <x-input-error :messages="$errors->get('date_of_death')" class="mt-2" />
                    </div>

                    <div>
                        <label for="death_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Memorial notes</label>
                        <textarea id="death_notes" name="death_notes" rows="4"
                                  placeholder="Service details, burial place, family notes…"
                                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">{{ old('death_notes') }}</textarea>
                        <x-input-error :messages="$errors->get('death_notes')" class="mt-2" />
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700"
                                @disabled($livingMembers === [])>
                            Record as deceased
                        </button>
                        <a href="{{ route('members.deceased') }}" class="px-4 py-2 rounded-md border text-sm font-semibold">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
