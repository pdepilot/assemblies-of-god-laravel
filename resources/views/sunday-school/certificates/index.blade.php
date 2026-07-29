<x-app-layout title="Sunday School">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="cms-page__title">Sunday School — Certificates</h1>
                <p class="cms-page__subtitle">Issue, preview, and download Sunday School award certificates.</p>
            </div>
            <a href="{{ route('ss.awards.index') }}" class="cms-btn cms-btn--ghost">View awards</a>
        </div>
    </x-slot>

    @include('sunday-school._tabs', ['active' => 'certificates', 'canIntelligence' => false])

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @if ($errors->has('certificate'))
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">{{ $errors->first('certificate') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                    Generate certificates from approved or published awards on the
                    <a href="{{ route('ss.awards.index') }}" class="text-indigo-600 hover:underline">Awards</a> page.
                </p>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Certificate #</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Recipient</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Award</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Issued</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Status</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($items as $item)
                                <tr>
                                    <td class="px-3 py-2 text-sm font-mono">{{ $item['certificate_number'] }}</td>
                                    <td class="px-3 py-2 text-sm">{{ $item['recipient_name'] }} <span class="text-gray-500">({{ $item['recipient_type'] }})</span></td>
                                    <td class="px-3 py-2 text-sm">{{ $item['award_title'] }}</td>
                                    <td class="px-3 py-2 text-sm">{{ $item['issued_date'] }}</td>
                                    <td class="px-3 py-2 text-sm">
                                        {{ ucfirst($item['status']) }}
                                        @if (!$item['has_file'])
                                            <span class="text-xs text-amber-600">(file missing)</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-sm">
                                        <div class="flex flex-wrap gap-2">
                                            @if ($item['has_file'])
                                                <a href="{{ $item['preview_url'] }}" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">Preview</a>
                                                <a href="{{ $item['download_url'] }}" class="text-indigo-600 hover:underline">Download</a>
                                            @endif
                                            <form method="POST" action="{{ route('ss.certificates.regenerate', $item['id']) }}">
                                                @csrf
                                                <button type="submit" class="text-indigo-600 hover:underline">Regenerate</button>
                                            </form>
                                            <form method="POST" action="{{ route('ss.certificates.destroy', $item['id']) }}" data-confirm="Delete this certificate?" data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-6 text-center text-sm text-gray-500">No certificates yet. Approve an award and generate its certificate from the Awards tab.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
