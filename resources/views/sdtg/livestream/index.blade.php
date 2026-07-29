<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">SDTG Livestream</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage broadcast state, viewer counts, and stream platform links.</p>
            </div>
            <a href="{{ $previewUrl }}" target="_blank" rel="noopener"
               class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md border border-gray-300 dark:border-gray-600">
                Preview public page
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

            @include('sdtg._nav', ['canManage' => $canManage])

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">Status</div>
                    <div class="text-2xl font-semibold {{ $stats['is_live'] ? 'text-red-600' : '' }}">
                        {{ $stats['is_live'] ? 'LIVE' : 'Offline' }}
                    </div>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">Current viewers</div>
                    <div class="text-2xl font-semibold">{{ number_format($stats['current_viewers']) }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">Peak viewers ({{ $stats['peak_year_label'] }})</div>
                    <div class="text-2xl font-semibold">{{ number_format($stats['peak_year']) }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
                    <div class="text-sm text-gray-500">Peak all time</div>
                    <div class="text-2xl font-semibold">{{ number_format($stats['peak_all_time']) }}</div>
                </div>
            </div>

            @if ($canManage)
                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                        <h3 class="font-semibold text-lg">Broadcast control</h3>

                        @if ($stats['is_live'])
                            <p class="text-sm text-gray-500">
                                Session: <strong>{{ $stats['session_name'] ?: 'SDTG Livestream' }}</strong>
                                @if ($stats['session_started_at'])
                                    · started {{ \Carbon\Carbon::parse($stats['session_started_at'])->diffForHumans() }}
                                @endif
                            </p>

                            <form method="POST" action="{{ route('sdtg.livestream.viewers') }}" class="flex gap-3 items-end">
                                @csrf
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Update viewer count</label>
                                    <input name="current_viewers" type="number" min="0" value="{{ $stats['current_viewers'] }}" required
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                                </div>
                                <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Update</button>
                            </form>

                            <form method="POST" action="{{ route('sdtg.livestream.toggle') }}">
                                @csrf
                                <input type="hidden" name="is_live" value="0" />
                                <button type="submit" class="px-4 py-2 rounded-md bg-red-600 text-white text-sm font-semibold">End stream</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('sdtg.livestream.toggle') }}" class="space-y-3">
                                @csrf
                                <input type="hidden" name="is_live" value="1" />
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Session name</label>
                                    <input name="session_name" type="text" value="{{ old('session_name', $stats['session_name'] ?: 'SDTG Livestream') }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Starting viewer count</label>
                                    <input name="current_viewers" type="number" min="0" value="{{ old('current_viewers', 0) }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                                </div>
                                <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Go live</button>
                            </form>
                        @endif
                    </div>

                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                        <h3 class="font-semibold text-lg">Stream platforms</h3>
                        <form method="POST" action="{{ route('sdtg.livestream.settings') }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default platform</label>
                                <select name="default_platform" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                    @foreach (['youtube' => 'YouTube', 'facebook' => 'Facebook', 'vimeo' => 'Vimeo', 'custom' => 'SDTG Direct'] as $value => $label)
                                        <option value="{{ $value }}" @selected(($settings['default_platform'] ?? 'youtube') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="youtube_enabled" value="1" @checked($settings['youtube_enabled'] ?? true) /> YouTube enabled
                            </label>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">YouTube channel / video ID</label>
                                <input name="youtube_channel" value="{{ old('youtube_channel', $settings['youtube_channel'] ?? '') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                            </div>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="facebook_enabled" value="1" @checked($settings['facebook_enabled'] ?? true) /> Facebook enabled
                            </label>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Facebook page / embed URL</label>
                                <input name="facebook_page" value="{{ old('facebook_page', $settings['facebook_page'] ?? '') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                            </div>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="vimeo_enabled" value="1" @checked($settings['vimeo_enabled'] ?? false) /> Vimeo enabled
                            </label>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Vimeo URL / ID</label>
                                <input name="vimeo_url" value="{{ old('vimeo_url', $settings['vimeo_url'] ?? '') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Direct stream URL</label>
                                <input name="audio_stream_url" value="{{ old('audio_stream_url', $settings['audio_stream_url'] ?? '') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                            </div>
                            <button type="submit" class="px-4 py-2 rounded-md bg-gray-800 dark:bg-gray-200 dark:text-gray-900 text-white text-sm font-semibold">Save platforms</button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="font-semibold text-lg">Recent sessions</h3>
                    <a href="{{ route('sdtg.content.index') }}" class="text-sm text-indigo-600 hover:underline">
                        Edit livestream page content →
                    </a>
                </div>

                @if ($hasPageContent)
                    <p class="text-sm text-gray-500">Public page content is configured in SDTG Page Content (livestream_page section).</p>
                @else
                    <p class="text-sm text-amber-600 dark:text-amber-400">No saved livestream page content yet — use Page Content to configure hero, schedule, and past broadcasts.</p>
                @endif

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Date</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Session</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Peak viewers</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($stats['sessions'] as $session)
                                <tr>
                                    <td class="px-3 py-2 text-sm">{{ $session['stat_date'] }}</td>
                                    <td class="px-3 py-2 text-sm">{{ $session['session_name'] }}</td>
                                    <td class="px-3 py-2 text-sm">{{ number_format($session['peak_viewers']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-3 py-6 text-center text-sm text-gray-500">No session stats recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
