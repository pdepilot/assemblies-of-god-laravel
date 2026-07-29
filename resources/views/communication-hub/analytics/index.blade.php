<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Communication Analytics</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Open, click, bounce, delivery rates, campaign and automation performance.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('communication-hub._nav', ['canManage' => $canManage])

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ([
                    ['label' => 'Open Rate', 'value' => $kpis['open_rate'].'%'],
                    ['label' => 'Click Rate', 'value' => $kpis['click_rate'].'%'],
                    ['label' => 'Bounce Rate', 'value' => $kpis['bounce_rate'].'%'],
                    ['label' => 'Delivery Success', 'value' => $kpis['delivery_success_rate'].'%'],
                    ['label' => 'Failed', 'value' => number_format($kpis['failed_messages'])],
                ] as $stat)
                    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4">
                        <p class="text-sm text-gray-500">{{ $stat['label'] }}</p>
                        <p class="text-2xl font-semibold mt-1">{{ $stat['value'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold mb-4">Monthly Trends</h3>
                    <canvas id="chMonthlyChart" height="160"></canvas>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold mb-4">Most Used Templates</h3>
                    @forelse ($topTemplates as $template)
                        <div class="flex items-center justify-between py-2 border-b text-sm">
                            <span class="font-mono text-xs">{{ $template['slug'] }}</span>
                            <strong>{{ number_format($template['uses']) }}</strong>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No template usage yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            var monthly = @json($monthlyTrends);
            var canvas = document.getElementById('chMonthlyChart');
            if (!canvas || !window.Chart) return;

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: monthly.map(function (m) { return m.month; }),
                    datasets: [
                        { label: 'Email', data: monthly.map(function (m) { return m.email; }), backgroundColor: '#60a5fa' },
                        { label: 'SMS', data: monthly.map(function (m) { return m.sms; }), backgroundColor: '#34d399' }
                    ]
                },
                options: {
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        })();
    </script>
</x-app-layout>
