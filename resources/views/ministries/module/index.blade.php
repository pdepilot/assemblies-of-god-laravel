<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ $setting['name'] }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Ministry roster only — separate from the church Members directory.
                </p>
            </div>
            <a href="{{ route('ministries.settings.index') }}"
               class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md border border-gray-300 dark:border-gray-600">
                All ministries
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">Total members</div>
                    <div class="text-2xl font-semibold">{{ $stats['total'] }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">New this month</div>
                    <div class="text-2xl font-semibold">{{ $stats['new_this_month'] }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">Birthdays (30 days)</div>
                    <div class="text-2xl font-semibold">{{ $stats['upcoming_birthdays'] }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">Attendance this month</div>
                    <div class="text-2xl font-semibold">{{ $stats['attendance_month'] }}</div>
                </div>
            </div>

            @if ($canManage)
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-8">
                    <form method="POST" action="{{ route('ministries.module.register', $setting['ministry_key']) }}" class="space-y-4">
                        @csrf
                        <h3 class="font-semibold text-lg">Register ministry member</h3>
                        <p class="text-sm text-gray-500">Saves only to this ministry. Does not add or change church Members.</p>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium">Full name *</label>
                                <input name="full_name" value="{{ old('full_name') }}" required
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Phone</label>
                                <input name="phone" value="{{ old('phone') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Email</label>
                                <input name="email" type="email" value="{{ old('email') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Date of birth</label>
                                <input name="date_of_birth" type="date" value="{{ old('date_of_birth') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Gender</label>
                                <select name="gender" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                    <option value="">—</option>
                                    <option value="male" @selected(old('gender') === 'male')>Male</option>
                                    <option value="female" @selected(old('gender') === 'female')>Female</option>
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium">Address</label>
                                <input name="address_line1" value="{{ old('address_line1') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium">City</label>
                                <input name="city" value="{{ old('city') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium">State</label>
                                <input name="state" value="{{ old('state') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                            </div>

                            @if ($requiresParents)
                                <div class="sm:col-span-2 border-t border-gray-200 dark:border-gray-700 pt-4">
                                    <h4 class="font-medium mb-2">Parent / guardian details</h4>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">Parent name *</label>
                                    <input name="parent_name" value="{{ old('parent_name') }}" required
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">Parent phone *</label>
                                    <input name="parent_phone" value="{{ old('parent_phone') }}" required
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium">Parent email</label>
                                    <input name="parent_email" type="email" value="{{ old('parent_email') }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">Class / group</label>
                                    <input name="group_name" value="{{ old('group_name') }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                                </div>
                            @else
                                <div>
                                    <label class="block text-sm font-medium">Role / group</label>
                                    <input name="role_note" value="{{ old('role_note') }}" placeholder="e.g. soprano, usher post"
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                                </div>
                            @endif

                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium">Notes</label>
                                <input name="notes" value="{{ old('notes') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                            </div>
                        </div>

                        <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">
                            Add to {{ $setting['name'] }}
                        </button>
                    </form>

                    <div class="grid gap-6 lg:grid-cols-2 border-t border-gray-200 dark:border-gray-700 pt-6">
                        <form method="POST" action="{{ route('ministries.module.import-member', $setting['ministry_key']) }}" class="space-y-3">
                            @csrf
                            <h3 class="font-semibold">Optional: copy from church Members</h3>
                            <p class="text-xs text-gray-500">Creates a roster snapshot. Does not enroll or edit the church member record.</p>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Select church member</label>
                                <select name="member_id" required
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                    <option value="">Choose a member…</option>
                                    @foreach ($churchMembers as $member)
                                        <option value="{{ $member['id'] }}" @selected((int) old('member_id') === $member['id'])>
                                            {{ $member['full_name'] }}
                                            @if ($member['member_code'])
                                                ({{ $member['member_code'] }})
                                            @endif
                                            @if ($member['phone'])
                                                · {{ $member['phone'] }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @if ($churchMembers === [])
                                    <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                        No church members available — add members in the Members directory first, or everyone is already on this roster.
                                    </p>
                                @endif
                            </div>
                            <input name="notes" type="text" placeholder="Notes (optional)"
                                   class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                            <button type="submit" class="px-4 py-2 rounded-md border border-gray-300 dark:border-gray-600 text-sm font-semibold">Copy into roster</button>
                        </form>

                        <form method="POST" action="{{ route('ministries.module.attendance', $setting['ministry_key']) }}" class="space-y-3">
                            @csrf
                            <h3 class="font-semibold">Record attendance</h3>
                            <select name="attendee" required
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                <option value="">Select roster member…</option>
                                @foreach ($attendanceOptions as $option)
                                    <option value="{{ ($option['source'] ?? 'roster') }}:{{ $option['id'] }}" @selected(old('attendee') === (($option['source'] ?? 'roster').':'.$option['id']))>
                                        {{ $option['full_name'] }}
                                        @if ($option['person_code'])
                                            ({{ $option['person_code'] }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @if ($attendanceOptions === [])
                                <p class="text-xs text-amber-600 dark:text-amber-400">
                                    No members on this ministry roster yet — register someone or copy from church Members.
                                </p>
                            @endif
                            <input name="service_date" type="date" value="{{ now()->toDateString() }}"
                                   class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="present" value="1" checked /> Present
                            </label>
                            <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Save attendance</button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h3 class="font-semibold text-lg">Roster ({{ $total }} total)</h3>
                    <form method="GET" class="flex gap-3 items-end">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                            <input name="q" value="{{ $query }}" placeholder="Name, phone, parent, address…"
                                   class="mt-1 block w-56 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Apply</button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Code</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Name</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Phone</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Address</th>
                                @if ($requiresParents)
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Parent</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Parent phone</th>
                                @endif
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Joined</th>
                                @if ($canManage)
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500"></th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($items as $item)
                                <tr>
                                    <td class="px-3 py-2 text-sm font-mono">{{ $item['person_code'] }}</td>
                                    <td class="px-3 py-2 text-sm">{{ $item['full_name'] }}</td>
                                    <td class="px-3 py-2 text-sm">{{ $item['phone'] ?: '—' }}</td>
                                    <td class="px-3 py-2 text-sm">{{ $item['address'] ?: '—' }}</td>
                                    @if ($requiresParents)
                                        <td class="px-3 py-2 text-sm">{{ $item['parent_name'] ?: '—' }}</td>
                                        <td class="px-3 py-2 text-sm">{{ $item['parent_phone'] ?: '—' }}</td>
                                    @endif
                                    <td class="px-3 py-2 text-sm">{{ $item['joined_date'] ?: '—' }}</td>
                                    @if ($canManage)
                                        <td class="px-3 py-2 text-sm">
                                            <form method="POST" action="{{ route('ministries.module.archive', [$setting['ministry_key'], $item['id']]) }}" data-confirm="Remove this person from the active roster?" data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger">
                                                @csrf
                                                <button type="submit" class="text-red-600 hover:underline">Archive</button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $requiresParents ? ($canManage ? 8 : 7) : ($canManage ? 6 : 5) }}"
                                        class="px-3 py-6 text-center text-sm text-gray-500">
                                        No people on this ministry roster yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($totalPages > 1)
                    <div class="mt-4 flex justify-between text-sm text-gray-600 dark:text-gray-400">
                        <span>Page {{ $page }} of {{ $totalPages }}</span>
                        <div class="flex gap-2">
                            @if ($page > 1)
                                <a href="{{ route('ministries.module.index', [$setting['ministry_key'], 'q' => $query, 'page' => $page - 1]) }}"
                                   class="px-3 py-1 rounded bg-gray-100 dark:bg-gray-700">Previous</a>
                            @endif
                            @if ($page < $totalPages)
                                <a href="{{ route('ministries.module.index', [$setting['ministry_key'], 'q' => $query, 'page' => $page + 1]) }}"
                                   class="px-3 py-1 rounded bg-gray-100 dark:bg-gray-700">Next</a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
