<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Site Traffic</h2></x-slot>
    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @include('analytics._nav')
            <form method="GET" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-400">From</label>
                    <input type="date" name="from" value="{{ $dashboard['filters']['from'] }}" class="rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                </div>
                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-400">To</label>
                    <input type="date" name="to" value="{{ $dashboard['filters']['to'] }}" class="rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                </div>
                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-400">Site area</label>
                    <select name="site_area" class="rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        @foreach (['all' => 'All', 'ag' => 'Main site', 'sermon' => 'Sermons', 'register' => 'Registration'] as $value => $label)
                            <option value="{{ $value }}" @selected($dashboard['filters']['site_area'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm">Apply filters</button>
            </form>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ([
                    'Visitors' => $dashboard['kpis']['visitors'],
                    'Sessions' => $dashboard['kpis']['sessions'],
                    'Pageviews' => $dashboard['kpis']['pageviews'],
                    'Avg duration (s)' => $dashboard['kpis']['avg_duration_seconds'],
                    'Bounce rate (%)' => $dashboard['kpis']['bounce_rate'],
                ] as $label => $value)
                    <div class="rounded-lg border p-4 bg-white dark:bg-gray-800">
                        <div class="text-sm text-gray-500">{{ $label }}</div>
                        <div class="text-2xl font-semibold">{{ $value }}</div>
                    </div>
                @endforeach
            </div>

            @php
                $deviceTotal = max(1, collect($dashboard['devices'] ?? [])->sum('sessions'));
            @endphp
            <div class="grid gap-6 lg:grid-cols-2">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold mb-4">Devices</h3>
                    <div class="space-y-3">
                        @forelse ($dashboard['devices'] as $device)
                            @php
                                $label = ucfirst((string) ($device['device_type'] ?: 'unknown'));
                                $sessions = (int) $device['sessions'];
                                $pct = round(($sessions / $deviceTotal) * 100, 1);
                                $barWidth = min(100, $pct).'%';
                            @endphp
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="font-medium">{{ $label }}</span>
                                    <span class="text-gray-500">{{ number_format($sessions) }} ({{ $pct }}%)</span>
                                </div>
                                <div class="h-2 rounded bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                    <div class="h-full bg-indigo-500" {!! 'style="width: '.e($barWidth).'"' !!}></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">No device data yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold mb-4">By site area</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left border-b">
                                    <th class="py-2">Area</th>
                                    <th>Sessions</th>
                                    <th>Visitors</th>
                                    <th>Pageviews</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse ($dashboard['by_site_area'] as $row)
                                <tr class="border-b">
                                    <td class="py-2">{{ $row['site_area'] }}</td>
                                    <td>{{ number_format($row['sessions']) }}</td>
                                    <td>{{ number_format($row['visitors']) }}</td>
                                    <td>{{ number_format($row['pageviews']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-4 text-gray-500">No area data yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                        <h3 class="font-semibold">Top pages</h3>
                        <span class="text-xs text-gray-500">{{ $topPages->total() }} total · 5 per page</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead><tr class="text-left border-b"><th class="py-2">Path</th><th>Area</th><th>Views</th><th>Avg duration</th></tr></thead>
                            <tbody>
                            @forelse ($topPages as $row)
                                <tr class="border-b">
                                    <td class="py-2">{{ $row['path'] }}</td>
                                    <td>{{ $row['site_area'] }}</td>
                                    <td>{{ $row['views'] }}</td>
                                    <td>{{ $row['avg_duration_seconds'] }}s</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-4 text-gray-500">No pageviews in this range.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($topPages->hasPages())
                        <div class="mt-4">{{ $topPages->links() }}</div>
                    @endif
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold mb-4">Top locations</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left border-b">
                                    <th class="py-2">Country</th>
                                    <th>City</th>
                                    <th>Sessions</th>
                                    <th>Visitors</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse ($dashboard['locations'] as $row)
                                <tr class="border-b">
                                    <td class="py-2">{{ $row['country'] !== '' ? $row['country'] : 'Unknown' }}</td>
                                    <td>{{ $row['city'] !== '' ? $row['city'] : '—' }}</td>
                                    <td>{{ number_format($row['sessions']) }}</td>
                                    <td>{{ number_format($row['visitors']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-4 text-gray-500">No location data yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6"
                x-data="siteTrafficSessionDetail({
                    sessionUrl: @js(url('/admin/analytics/site-traffic/sessions')),
                    visitorUrl: @js(url('/admin/analytics/site-traffic/visitors')),
                    from: @js($dashboard['filters']['from']),
                    to: @js($dashboard['filters']['to']),
                })"
            >
                <style>[x-cloak]{display:none!important}</style>
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <div>
                        <h3 class="font-semibold">Recent sessions</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Click a row to see pages visited and time spent</p>
                    </div>
                    <span class="text-xs text-gray-500">{{ $recentSessions->total() }} total · 5 per page</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left border-b">
                                <th class="py-2">When</th>
                                <th>Country</th>
                                <th>City</th>
                                <th>Device</th>
                                <th>Browser / OS</th>
                                <th>Area</th>
                                <th>Pages</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($recentSessions as $session)
                            <tr
                                class="border-b align-top cursor-pointer hover:bg-indigo-50 dark:hover:bg-gray-700/60 transition-colors"
                                role="button"
                                tabindex="0"
                                @click="openSession(@js($session['session_key']))"
                                @keydown.enter.prevent="openSession(@js($session['session_key']))"
                                @keydown.space.prevent="openSession(@js($session['session_key']))"
                            >
                                <td class="py-2 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($session['last_seen_at'])->format('M j, g:i A') }}</td>
                                <td>{{ $session['country'] !== '' ? $session['country'] : 'Unknown' }}</td>
                                <td>{{ $session['city'] !== '' ? $session['city'] : '—' }}</td>
                                <td>{{ ucfirst((string) ($session['device_type'] ?: 'unknown')) }}</td>
                                <td>{{ trim(($session['browser'] ?? '').' / '.($session['os'] ?? ''), ' /') }}</td>
                                <td>{{ $session['site_area'] }}</td>
                                <td>{{ number_format($session['pageview_count']) }}</td>
                                <td class="whitespace-nowrap" x-text="formatDuration({{ (int) $session['duration_seconds'] }})"></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="py-4 text-gray-500">No sessions in this range.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($recentSessions->hasPages())
                    <div class="mt-4">{{ $recentSessions->links() }}</div>
                @endif

                <div
                    x-show="open"
                    x-cloak
                    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
                    style="display: none;"
                    @keydown.escape.window="close()"
                >
                    <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80" @click="close()"></div>
                    <div class="relative mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-xl sm:w-full sm:max-w-3xl sm:mx-auto overflow-hidden">
                        <div class="flex items-start justify-between gap-3 px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100" x-text="title"></h3>
                                <p class="text-sm text-gray-500 mt-0.5" x-text="subtitle"></p>
                            </div>
                            <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" @click="close()" aria-label="Close">
                                <span class="text-2xl leading-none">&times;</span>
                            </button>
                        </div>

                        <div class="px-5 py-4 space-y-4 max-h-[75vh] overflow-y-auto">
                            <template x-if="loading">
                                <p class="text-sm text-gray-500">Loading visit details…</p>
                            </template>
                            <template x-if="!loading && error">
                                <p class="text-sm text-red-600" x-text="error"></p>
                            </template>

                            <template x-if="!loading && !error && mode === 'session' && session">
                                <div class="space-y-4">
                                    <div class="grid gap-3 sm:grid-cols-2 text-sm">
                                        <div><span class="text-gray-500">Device</span><div class="font-medium" x-text="deviceLabel(session)"></div></div>
                                        <div><span class="text-gray-500">Location</span><div class="font-medium" x-text="locationLabel(session)"></div></div>
                                        <div><span class="text-gray-500">Time spent</span><div class="font-medium" x-text="formatDuration(session.duration_seconds)"></div></div>
                                        <div><span class="text-gray-500">Pages</span><div class="font-medium" x-text="session.pageview_count"></div></div>
                                        <div class="sm:col-span-2"><span class="text-gray-500">Referrer</span><div class="font-medium break-all" x-text="session.referrer || '—'"></div></div>
                                    </div>

                                    <div>
                                        <h4 class="font-medium mb-2">Pages visited</h4>
                                        <div class="overflow-x-auto border rounded-md dark:border-gray-700">
                                            <table class="min-w-full text-sm">
                                                <thead>
                                                    <tr class="text-left border-b bg-gray-50 dark:bg-gray-900/40">
                                                        <th class="py-2 px-3">When</th>
                                                        <th class="px-3">Page</th>
                                                        <th class="px-3">Area</th>
                                                        <th class="px-3">Time on page</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="(page, idx) in (session.pages || [])" :key="idx">
                                                        <tr class="border-b dark:border-gray-700">
                                                            <td class="py-2 px-3 whitespace-nowrap" x-text="formatWhen(page.entered_at)"></td>
                                                            <td class="px-3">
                                                                <div class="font-medium" x-text="page.title || page.path"></div>
                                                                <div class="text-xs text-gray-500" x-text="page.path"></div>
                                                            </td>
                                                            <td class="px-3" x-text="page.site_area"></td>
                                                            <td class="px-3 whitespace-nowrap" x-text="formatDuration(page.duration_seconds)"></td>
                                                        </tr>
                                                    </template>
                                                    <tr x-show="!(session.pages || []).length">
                                                        <td colspan="4" class="py-4 px-3 text-gray-500">No pageviews recorded for this visit.</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <button
                                        type="button"
                                        class="px-3 py-2 text-sm rounded-md border border-indigo-300 text-indigo-700 dark:text-indigo-300 dark:border-indigo-700 hover:bg-indigo-50 dark:hover:bg-indigo-950/40"
                                        @click="openVisitor(session.visitor_key)"
                                    >
                                        View all visits from this device
                                    </button>
                                </div>
                            </template>

                            <template x-if="!loading && !error && mode === 'visitor' && visitor">
                                <div class="space-y-4">
                                    <div class="grid gap-3 sm:grid-cols-3 text-sm">
                                        <div><span class="text-gray-500">Visits</span><div class="font-medium" x-text="visitor.session_count"></div></div>
                                        <div><span class="text-gray-500">Total time</span><div class="font-medium" x-text="formatDuration(visitor.total_duration_seconds)"></div></div>
                                        <div><span class="text-gray-500">Range</span><div class="font-medium" x-text="(visitor.filters?.from || '') + ' → ' + (visitor.filters?.to || '')"></div></div>
                                    </div>

                                    <template x-for="(visit, vIdx) in (visitor.sessions || [])" :key="visit.session_key">
                                        <div class="border rounded-md dark:border-gray-700 p-3 space-y-2">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <div class="text-sm font-medium" x-text="formatWhen(visit.last_seen_at) + ' · ' + deviceLabel(visit)"></div>
                                                <div class="text-sm text-gray-500" x-text="formatDuration(visit.duration_seconds) + ' · ' + visit.pageview_count + ' pages'"></div>
                                            </div>
                                            <ul class="text-sm space-y-1">
                                                <template x-for="(page, pIdx) in (visit.pages || [])" :key="pIdx">
                                                    <li class="flex flex-wrap justify-between gap-2">
                                                        <span>
                                                            <span x-text="page.title || page.path"></span>
                                                            <span class="text-xs text-gray-500" x-text="' (' + page.path + ')'"></span>
                                                        </span>
                                                        <span class="text-gray-500 whitespace-nowrap" x-text="formatDuration(page.duration_seconds)"></span>
                                                    </li>
                                                </template>
                                            </ul>
                                            <button type="button" class="text-xs text-indigo-600 hover:underline" @click="openSession(visit.session_key)">Open visit detail</button>
                                        </div>
                                    </template>

                                    <p class="text-sm text-gray-500" x-show="!(visitor.sessions || []).length">No visits for this device in the selected date range.</p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            @if ($canManage ?? false)
                <form method="POST" action="{{ route('analytics.site-traffic.purge') }}" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 flex flex-wrap gap-3 items-end">
                    @csrf
                    <div>
                        <label class="block text-sm text-gray-600 dark:text-gray-400">Purge older than (days)</label>
                        <input type="number" name="days" min="7" max="365" value="90" class="rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md text-sm" data-confirm="Delete old traffic data?" data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger">Purge traffic</button>
                </form>
            @endif
        </div>
    </div>

    <script>
        function siteTrafficSessionDetail(config) {
            return {
                open: false,
                loading: false,
                error: '',
                mode: 'session',
                session: null,
                visitor: null,
                title: '',
                subtitle: '',
                sessionUrl: config.sessionUrl,
                visitorUrl: config.visitorUrl,
                from: config.from,
                to: config.to,

                formatDuration(seconds) {
                    const s = Math.max(0, parseInt(seconds || 0, 10));
                    if (s < 60) return s + 's';
                    if (s < 3600) {
                        const m = Math.floor(s / 60);
                        const rem = s % 60;
                        return rem ? (m + 'm ' + rem + 's') : (m + 'm');
                    }
                    const h = Math.floor(s / 3600);
                    const m = Math.floor((s % 3600) / 60);
                    return m ? (h + 'h ' + m + 'm') : (h + 'h');
                },

                formatWhen(value) {
                    if (!value) return '—';
                    const d = new Date(String(value).replace(' ', 'T'));
                    if (Number.isNaN(d.getTime())) return String(value);
                    return d.toLocaleString(undefined, {
                        month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit',
                    });
                },

                deviceLabel(row) {
                    if (!row) return '—';
                    const device = (row.device_type || 'unknown');
                    const label = device.charAt(0).toUpperCase() + device.slice(1);
                    const browser = [row.browser, row.os].filter(Boolean).join(' / ');
                    return browser ? (label + ' · ' + browser) : label;
                },

                locationLabel(row) {
                    if (!row) return '—';
                    const city = row.city || '';
                    const country = row.country || 'Unknown';
                    return city ? (city + ', ' + country) : country;
                },

                close() {
                    this.open = false;
                    this.loading = false;
                    this.error = '';
                },

                async openSession(sessionKey) {
                    if (!sessionKey) return;
                    this.open = true;
                    this.loading = true;
                    this.error = '';
                    this.mode = 'session';
                    this.session = null;
                    this.visitor = null;
                    this.title = 'Visit details';
                    this.subtitle = 'Pages viewed and time spent on this visit';
                    try {
                        const res = await fetch(this.sessionUrl + '/' + encodeURIComponent(sessionKey), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        if (!res.ok) throw new Error(res.status === 404 ? 'Visit not found.' : 'Could not load visit details.');
                        this.session = await res.json();
                        this.title = this.deviceLabel(this.session);
                        this.subtitle = this.locationLabel(this.session) + ' · ' + this.formatDuration(this.session.duration_seconds);
                    } catch (e) {
                        this.error = e.message || 'Could not load visit details.';
                    } finally {
                        this.loading = false;
                    }
                },

                async openVisitor(visitorKey) {
                    if (!visitorKey) return;
                    this.open = true;
                    this.loading = true;
                    this.error = '';
                    this.mode = 'visitor';
                    this.session = null;
                    this.visitor = null;
                    this.title = 'Device visit history';
                    this.subtitle = 'All visits from this device in the selected date range';
                    try {
                        const qs = new URLSearchParams({ from: this.from || '', to: this.to || '' });
                        const res = await fetch(this.visitorUrl + '/' + encodeURIComponent(visitorKey) + '?' + qs.toString(), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        if (!res.ok) throw new Error('Could not load device history.');
                        this.visitor = await res.json();
                        this.subtitle = (this.visitor.session_count || 0) + ' visits · ' + this.formatDuration(this.visitor.total_duration_seconds || 0) + ' total';
                    } catch (e) {
                        this.error = e.message || 'Could not load device history.';
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
</x-app-layout>
