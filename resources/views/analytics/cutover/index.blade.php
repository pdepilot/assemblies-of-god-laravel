<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Migration Cutover</h2></x-slot>
    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('analytics._nav')
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h3 class="font-semibold">Staging validation</h3>
                    <span class="px-3 py-1 rounded text-sm {{ $status['validation']['passed'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                        {{ $status['validation']['passed'] ? 'All checks passed' : 'Action required' }}
                    </span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Run <code class="text-xs bg-gray-100 dark:bg-gray-900 px-1 rounded">php artisan analytics:validate-cutover</code> on staging after pointing the legacy beacon.</p>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead><tr class="text-left border-b"><th class="py-2">Status</th><th>Check</th><th>Detail</th></tr></thead>
                        <tbody>
                        @foreach ($status['validation']['checks'] as $check)
                            <tr class="border-b">
                                <td class="py-2">{{ $check['passed'] ? 'PASS' : 'FAIL' }}</td>
                                <td>{{ $check['label'] }}</td>
                                <td class="text-gray-600 dark:text-gray-400">{{ $check['detail'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold mb-2">Legacy beacon URL (staging)</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Copy <code class="text-xs">config/cutover.local.php.example</code> to <code class="text-xs">config/cutover.local.php</code> in the legacy site and set:</p>
                <pre class="text-xs bg-gray-50 dark:bg-gray-900 p-4 rounded overflow-x-auto">{{ $status['legacy_config_example'] }}</pre>
                <p class="text-sm mt-3">Target URL: <strong>{{ $status['beacon_url'] }}</strong></p>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold mb-4">Migration milestones</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead><tr class="text-left border-b"><th class="py-2">Milestone</th><th>Module</th><th>Status</th></tr></thead>
                        <tbody>
                        @foreach ($status['milestones'] as $row)
                            <tr class="border-b">
                                <td class="py-2 font-medium">{{ $row['key'] }}</td>
                                <td>{{ $row['label'] }}</td>
                                <td><span class="px-2 py-0.5 rounded text-xs {{ $row['status'] === 'done' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">{{ ucfirst($row['status']) }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold mb-4">Cutover checklist</h3>
                <ul class="space-y-2 text-sm">
                    @foreach ($status['checklist'] as $item)
                        <li class="flex items-start gap-2">
                            <span class="mt-0.5">{{ $item['done'] ? '✓' : '○' }}</span>
                            <span>{{ $item['label'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold mb-4">Public API bridge</h3>
                <div class="space-y-4 text-sm">
                    @foreach ($status['api_bridge'] as $note)
                        <div>
                            <div class="font-medium">{{ $note['title'] }}</div>
                            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $note['detail'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
