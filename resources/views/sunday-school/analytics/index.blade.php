<x-app-layout title="Sunday School">
    <x-slot name="header">
        <div>
            <h1 class="cms-page__title">Sunday School Department</h1>
            <p class="cms-page__subtitle" id="ssPortalSubtitle">Manage classes, teachers, students, attendance, offerings, memory verses, curriculum, promotions, and reports.</p>
        </div>
        <div class="cms-page__actions" id="ssAdminActions">
            <form method="POST" action="{{ route('ss.classes.seed') }}" class="inline">
                @csrf
                <button type="submit" class="cms-btn cms-btn--ghost"><i class="fas fa-seedling"></i> Seed Classes</button>
            </form>
            <a href="{{ route('ss.reports.export', ['type' => 'students']) }}" class="cms-btn cms-btn--ghost"><i class="fas fa-file-export"></i> Export</a>
            <a href="{{ route('ss.attendance.index') }}" class="cms-btn cms-btn--primary"><i class="fas fa-clipboard-check"></i> Mark Attendance</a>
        </div>
    </x-slot>

    @include('sunday-school._tabs', [
        'active' => ($tab ?? 'overview') === 'intelligence' ? 'intelligence' : 'overview',
        'canIntelligence' => $canIntelligence ?? false,
    ])

    @if (($tab ?? 'overview') === 'intelligence' && $superintendent)
        <div class="cms-tab-panel is-active" data-panel="superintendent">
            <div class="ss-super-dashboard">
                <header class="ss-super-dashboard__head">
                    <div>
                        <h2 class="ss-super-dashboard__title">Superintendent Intelligence Dashboard</h2>
                        <p class="ss-register-hint">Real-time command center for the entire Sunday School Department.</p>
                    </div>
                    <a href="{{ route('ss.analytics.index', ['tab' => 'intelligence']) }}" class="cms-btn cms-btn--ghost"><i class="fas fa-sync"></i> Refresh</a>
                </header>

                <div class="cms-stats ss-super-kpis">
                    <article class="cms-stat cms-card"><div class="cms-stat__value">{{ number_format($stats['attendance_today']) }}</div><div class="cms-stat__label">Attendance Today</div></article>
                    <article class="cms-stat cms-card"><div class="cms-stat__value">{{ number_format($superintendent['kpis']['attendance_week'] ?? 0) }}</div><div class="cms-stat__label">Weekly Attendance</div></article>
                    <article class="cms-stat cms-card"><div class="cms-stat__value">{{ number_format($stats['attendance_month']) }}</div><div class="cms-stat__label">Monthly Attendance</div></article>
                    <article class="cms-stat cms-card"><div class="cms-stat__value">{{ number_format($superintendent['kpis']['attendance_year'] ?? 0) }}</div><div class="cms-stat__label">Yearly Attendance</div></article>
                    <article class="cms-stat cms-card"><div class="cms-stat__value">₦{{ number_format($stats['offering_today'], 0) }}</div><div class="cms-stat__label">Offering Today</div></article>
                    <article class="cms-stat cms-card"><div class="cms-stat__value">₦{{ number_format((float) ($superintendent['kpis']['offering_week'] ?? 0), 0) }}</div><div class="cms-stat__label">Weekly Offering</div></article>
                    <article class="cms-stat cms-card"><div class="cms-stat__value">₦{{ number_format($stats['offering_month'], 0) }}</div><div class="cms-stat__label">Monthly Offering</div></article>
                    <article class="cms-stat cms-card"><div class="cms-stat__value">₦{{ number_format((float) ($superintendent['kpis']['offering_year'] ?? 0), 0) }}</div><div class="cms-stat__label">Yearly Offering</div></article>
                    <article class="cms-stat cms-card"><div class="cms-stat__value">₦{{ number_format($stats['offering_total'], 0) }}</div><div class="cms-stat__label">Total Offering Since Inception</div></article>
                    <article class="cms-stat cms-card"><div class="cms-stat__value">{{ number_format($stats['total_classes']) }}</div><div class="cms-stat__label">Active Classes</div></article>
                    <article class="cms-stat cms-card"><div class="cms-stat__value">{{ number_format($stats['total_teachers']) }}</div><div class="cms-stat__label">Active Teachers</div></article>
                    <article class="cms-stat cms-card"><div class="cms-stat__value">{{ number_format($stats['total_students']) }}</div><div class="cms-stat__label">Total Students</div></article>
                    <article class="cms-stat cms-card"><div class="cms-stat__value">{{ number_format($superintendent['kpis']['new_students_month'] ?? 0) }}</div><div class="cms-stat__label">New Students (Month)</div></article>
                    <article class="cms-stat cms-card"><div class="cms-stat__value">{{ ($superintendent['kpis']['memory_verse_rate'] ?? 0) }}%</div><div class="cms-stat__label">Memory Verse Rate</div></article>
                    <article class="cms-stat cms-card"><div class="cms-stat__value">{{ ($superintendent['kpis']['punctuality_early_pct'] ?? 0) }}%</div><div class="cms-stat__label">Early Arrivals %</div></article>
                    <article class="cms-stat cms-card"><div class="cms-stat__value">{{ ($superintendent['kpis']['retention_rate'] ?? 0) }}%</div><div class="cms-stat__label">Retention Rate</div></article>
                </div>

                <div class="cms-grid cms-grid--2col">
                    <section class="cms-card"><div class="cms-card__head"><h3 class="cms-card__title">Weekly Attendance Heatmap</h3></div><div class="cms-card__body"><div class="cms-chart-wrap"><canvas id="chartAttHeatmap"></canvas></div></div></section>
                    <section class="cms-card"><div class="cms-card__head"><h3 class="cms-card__title">Yearly Attendance Trend</h3></div><div class="cms-card__body"><div class="cms-chart-wrap"><canvas id="chartYearAtt"></canvas></div></div></section>
                    <section class="cms-card"><div class="cms-card__head"><h3 class="cms-card__title">Yearly Offering Trend</h3></div><div class="cms-card__body"><div class="cms-chart-wrap"><canvas id="chartYearOff"></canvas></div></div></section>
                    <section class="cms-card">
                        <div class="cms-card__head"><h3 class="cms-card__title">Top Students</h3></div>
                        <div class="cms-card__body">
                            <div class="cms-table-wrap">
                                <table class="cms-table cms-table--stack">
                                    <thead><tr><th>Student</th><th>Class</th><th>Score</th></tr></thead>
                                    <tbody>
                                        @forelse(($superintendent['top_performers']['students'] ?? []) as $row)
                                            <tr>
                                                <td>{{ $row['full_name'] }}</td>
                                                <td>{{ $row['class_name'] ?? '—' }}</td>
                                                <td>{{ number_format((float) $row['overall_score'], 1) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3">No student data.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                    <section class="cms-card cms-grid__full">
                        <div class="cms-card__head"><h3 class="cms-card__title">Teacher Performance Rankings</h3></div>
                        <div class="cms-card__body">
                            <div class="cms-table-wrap cms-table-wrap--scroll">
                                <table class="cms-table cms-table--responsive cms-table--stack">
                                    <thead><tr><th>Teacher</th><th>Class</th><th>Students</th><th>Score</th></tr></thead>
                                    <tbody>
                                        @forelse($teacherAnalytics['rankings'] as $row)
                                            <tr>
                                                <td>{{ $row['full_name'] }}</td>
                                                <td>{{ $row['class_name'] ?: '—' }}</td>
                                                <td>{{ $row['student_count'] }}</td>
                                                <td>{{ number_format((float) $row['composite_score'], 1) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4">No teacher data.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                    <section class="cms-card cms-grid__full">
                        <div class="cms-card__head"><h3 class="cms-card__title">Class Performance Rankings</h3></div>
                        <div class="cms-card__body">
                            <div class="cms-table-wrap cms-table-wrap--scroll">
                                <table class="cms-table cms-table--responsive cms-table--stack">
                                    <thead><tr><th>Class</th><th>Students</th><th>Attendance</th><th>Score</th></tr></thead>
                                    <tbody>
                                        @forelse($classAnalytics['rankings'] as $row)
                                            <tr>
                                                <td>{{ $row['class_name'] }}</td>
                                                <td>{{ $row['student_count'] }}</td>
                                                <td>{{ number_format((float) $row['attendance_rate'], 1) }}%</td>
                                                <td>{{ number_format((float) $row['composite_score'], 1) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4">No class data.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    @else
        <div class="cms-tab-panel is-active" data-panel="overview">
            <div class="cms-stats">
                <article class="cms-stat cms-card"><div class="cms-stat__top"><span class="cms-stat__icon cms-stat__icon--blue"><i class="fas fa-user-graduate"></i></span></div><div class="cms-stat__value">{{ number_format($stats['total_students']) }}</div><div class="cms-stat__label">Total Students</div></article>
                <article class="cms-stat cms-card"><div class="cms-stat__top"><span class="cms-stat__icon cms-stat__icon--purple"><i class="fas fa-chalkboard-user"></i></span></div><div class="cms-stat__value">{{ number_format($stats['total_teachers']) }}</div><div class="cms-stat__label">Total Teachers</div></article>
                <article class="cms-stat cms-card"><div class="cms-stat__top"><span class="cms-stat__icon cms-stat__icon--gold"><i class="fas fa-door-open"></i></span></div><div class="cms-stat__value">{{ number_format($stats['total_classes']) }}</div><div class="cms-stat__label">Total Classes</div></article>
                <article class="cms-stat cms-card"><div class="cms-stat__top"><span class="cms-stat__icon cms-stat__icon--green"><i class="fas fa-clipboard-check"></i></span></div><div class="cms-stat__value">{{ number_format($stats['attendance_today']) }}</div><div class="cms-stat__label">Attendance Today</div></article>
                <article class="cms-stat cms-card"><div class="cms-stat__top"><span class="cms-stat__icon cms-stat__icon--green"><i class="fas fa-calendar-check"></i></span></div><div class="cms-stat__value">{{ number_format($stats['attendance_month']) }}</div><div class="cms-stat__label">Attendance This Month</div></article>
                <article class="cms-stat cms-card"><div class="cms-stat__top"><span class="cms-stat__icon cms-stat__icon--gold"><i class="fas fa-coins"></i></span></div><div class="cms-stat__value">₦{{ number_format($stats['offering_today'], 0) }}</div><div class="cms-stat__label">Offering Today</div></article>
                <article class="cms-stat cms-card"><div class="cms-stat__top"><span class="cms-stat__icon cms-stat__icon--gold"><i class="fas fa-hand-holding-dollar"></i></span></div><div class="cms-stat__value">₦{{ number_format($stats['offering_month'], 0) }}</div><div class="cms-stat__label">Offering This Month</div></article>
                <article class="cms-stat cms-card"><div class="cms-stat__top"><span class="cms-stat__icon cms-stat__icon--gold"><i class="fas fa-sack-dollar"></i></span></div><div class="cms-stat__value">₦{{ number_format($stats['offering_total'], 0) }}</div><div class="cms-stat__label">Total Offering Since Inception</div></article>
                <article class="cms-stat cms-card"><div class="cms-stat__top"><span class="cms-stat__icon cms-stat__icon--green"><i class="fas fa-user-check"></i></span></div><div class="cms-stat__value">{{ number_format($stats['students_present_today']) }}</div><div class="cms-stat__label">Present Today</div></article>
                <article class="cms-stat cms-card"><div class="cms-stat__top"><span class="cms-stat__icon cms-stat__icon--blue"><i class="fas fa-user-xmark"></i></span></div><div class="cms-stat__value">{{ number_format($stats['students_absent_today']) }}</div><div class="cms-stat__label">Absent Today</div></article>
                <article class="cms-stat cms-card"><div class="cms-stat__top"><span class="cms-stat__icon cms-stat__icon--green"><i class="fas fa-clock"></i></span></div><div class="cms-stat__value">{{ number_format($stats['early_arrivals_today']) }}</div><div class="cms-stat__label">Early Arrivals</div></article>
                <article class="cms-stat cms-card"><div class="cms-stat__top"><span class="cms-stat__icon cms-stat__icon--purple"><i class="fas fa-hourglass-half"></i></span></div><div class="cms-stat__value">{{ number_format($stats['late_arrivals_today']) }}</div><div class="cms-stat__label">Late Arrivals</div></article>
                <article class="cms-stat cms-card"><div class="cms-stat__top"><span class="cms-stat__icon cms-stat__icon--gold"><i class="fas fa-book-bible"></i></span></div><div class="cms-stat__value">{{ number_format($stats['memory_verse_completions']) }}</div><div class="cms-stat__label">Memory Verse Completions</div></article>
            </div>

            <div class="cms-grid cms-grid--2col">
                <section class="cms-card"><div class="cms-card__head"><h2 class="cms-card__title">Weekly Attendance</h2></div><div class="cms-card__body"><div class="cms-chart-wrap cms-chart-wrap--sm"><canvas id="chartWeeklyAtt" aria-label="Weekly attendance chart"></canvas></div></div></section>
                <section class="cms-card"><div class="cms-card__head"><h2 class="cms-card__title">Monthly Attendance</h2></div><div class="cms-card__body"><div class="cms-chart-wrap cms-chart-wrap--sm"><canvas id="chartMonthlyAtt" aria-label="Monthly attendance chart"></canvas></div></div></section>
                <section class="cms-card"><div class="cms-card__head"><h2 class="cms-card__title">Offering Trends</h2></div><div class="cms-card__body"><div class="cms-chart-wrap cms-chart-wrap--sm"><canvas id="chartOfferings" aria-label="Offering trends chart"></canvas></div></div></section>
                <section class="cms-card"><div class="cms-card__head"><h2 class="cms-card__title">Class Performance</h2></div><div class="cms-card__body"><div class="cms-chart-wrap cms-chart-wrap--sm"><canvas id="chartClassPerf" aria-label="Class performance chart"></canvas></div></div></section>
                <section class="cms-card cms-grid__full"><div class="cms-card__head"><h2 class="cms-card__title">Teacher Performance</h2></div><div class="cms-card__body"><div class="cms-chart-wrap"><canvas id="chartTeacherPerf" aria-label="Teacher performance chart"></canvas></div></div></section>
            </div>
        </div>
    @endif

    <script type="application/json" id="ss-analytics-charts">@json($charts)</script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') return;
            var chartsEl = document.getElementById('ss-analytics-charts');
            var charts = chartsEl ? JSON.parse(chartsEl.textContent) : {};
            var opts = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            };

            function make(id, type, labels, data, color, extra) {
                var el = document.getElementById(id);
                if (!el || !labels) return;
                new Chart(el, {
                    type: type,
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: type === 'line' ? undefined : color,
                            borderColor: type === 'line' ? color : undefined,
                            tension: type === 'line' ? 0.3 : undefined,
                            fill: false
                        }]
                    },
                    options: extra || opts
                });
            }

            if (charts.weekly_attendance) {
                make('chartWeeklyAtt', 'bar',
                    charts.weekly_attendance.map(function (d) { return d.label; }),
                    charts.weekly_attendance.map(function (d) { return d.present; }),
                    'rgba(46, 125, 50, 0.7)');
            }
            if (charts.monthly_attendance) {
                make('chartMonthlyAtt', 'line',
                    charts.monthly_attendance.map(function (d) { return d.label; }),
                    charts.monthly_attendance.map(function (d) { return d.present; }),
                    '#1a2b5c');
            }
            if (charts.offering_trends) {
                make('chartOfferings', 'bar',
                    charts.offering_trends.map(function (d) { return d.label; }),
                    charts.offering_trends.map(function (d) { return d.amount; }),
                    'rgba(212, 175, 55, 0.75)');
            }
            if (charts.class_performance) {
                make('chartClassPerf', 'bar',
                    charts.class_performance.map(function (d) { return d.label; }),
                    charts.class_performance.map(function (d) { return d.rate; }),
                    'rgba(63, 81, 181, 0.7)',
                    Object.assign({}, opts, { scales: { y: { max: 100, beginAtZero: true } } }));
            }
            if (charts.teacher_performance) {
                make('chartTeacherPerf', 'bar',
                    charts.teacher_performance.map(function (d) { return d.label; }),
                    charts.teacher_performance.map(function (d) { return d.rate; }),
                    'rgba(123, 31, 162, 0.7)',
                    Object.assign({}, opts, { scales: { y: { max: 100, beginAtZero: true } } }));
            }

            var heat = charts.attendance_heatmap || [];
            if (heat.length) {
                make('chartAttHeatmap', 'bar',
                    heat.map(function (h) { return h.week; }),
                    heat.map(function (h) { return h.rate; }),
                    'rgba(251, 254, 6, 0.55)',
                    Object.assign({}, opts, { scales: { y: { max: 100, beginAtZero: true } } }));
            }
            var yearAtt = charts.yearly_attendance || [];
            if (yearAtt.length) {
                make('chartYearAtt', 'line',
                    yearAtt.map(function (x) { return x.label; }),
                    yearAtt.map(function (x) { return x.present; }),
                    '#4ade80');
            }
            var yearOff = charts.yearly_offerings || [];
            if (yearOff.length) {
                make('chartYearOff', 'bar',
                    yearOff.map(function (x) { return x.label; }),
                    yearOff.map(function (x) { return x.amount; }),
                    'rgba(96, 165, 250, 0.6)');
            }
        });
    </script>
</x-app-layout>
