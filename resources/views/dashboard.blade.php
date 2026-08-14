<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="cms-page__title">Dashboard</h1>
            <p class="cms-page__subtitle">Welcome back, {{ $admin->display_name }}. Live overview of AGC Ikenegbu.</p>
        </div>
        <div class="cms-page__actions">
            <span class="cms-badge cms-badge--muted">{{ now()->format('D, M j · g:i A') }}</span>
            <a href="{{ route('analytics.reports.index') }}" class="cms-btn cms-btn--ghost"><i class="fas fa-download" aria-hidden="true"></i> Reports</a>
            <a href="{{ route('events.create') }}" class="cms-btn cms-btn--primary"><i class="fas fa-plus" aria-hidden="true"></i> New Event</a>
        </div>
    </x-slot>

    <p class="cms-section-label">AGC IKENEGBU — Church Metrics</p>
    <div class="cms-stats">
        <article class="cms-stat cms-card">
            <div class="cms-stat__top">
                <div class="cms-stat__icon cms-stat__icon--gold"><i class="fas fa-users" aria-hidden="true"></i></div>
            </div>
            <div class="cms-stat__value">{{ number_format($stats['members']) }}</div>
            <div class="cms-stat__label">Total Members</div>
            <span class="cms-stat__brand cms-stat__brand--ag">AGC Ikenegbu</span>
        </article>
        <article class="cms-stat cms-card">
            <div class="cms-stat__top">
                <div class="cms-stat__icon cms-stat__icon--blue"><i class="fas fa-handshake" aria-hidden="true"></i></div>
            </div>
            <div class="cms-stat__value">{{ number_format($stats['visitors']) }}</div>
            <div class="cms-stat__label">Registered Visitors</div>
            <span class="cms-stat__brand cms-stat__brand--ag">AGC Ikenegbu</span>
        </article>
        <article class="cms-stat cms-card">
            <div class="cms-stat__top">
                <div class="cms-stat__icon cms-stat__icon--green"><i class="fas fa-calendar-days" aria-hidden="true"></i></div>
            </div>
            <div class="cms-stat__value">{{ number_format($stats['events']) }}</div>
            <div class="cms-stat__label">Published Events</div>
            <span class="cms-stat__brand cms-stat__brand--ag">AGC Ikenegbu</span>
        </article>
        <article class="cms-stat cms-card">
            <div class="cms-stat__top">
                <div class="cms-stat__icon cms-stat__icon--purple"><i class="fas fa-book-open" aria-hidden="true"></i></div>
            </div>
            <div class="cms-stat__value">{{ number_format($stats['ss_students']) }}</div>
            <div class="cms-stat__label">Sunday School Students</div>
            <span class="cms-stat__brand cms-stat__brand--ag">AGC Ikenegbu</span>
        </article>
    </div>

    <div class="cms-grid cms-grid--2" style="margin-top: 1.5rem;">
        <article class="cms-card">
            <div class="cms-card__head">
                <h3 class="cms-card__title">Quick Actions</h3>
            </div>
            <div class="cms-card__body" style="display:grid;gap:10px">
                <a href="{{ route('visitors.create') }}" class="cms-btn cms-btn--ghost" style="justify-content:flex-start"><i class="fas fa-handshake" aria-hidden="true"></i> Register Visitor</a>
                <a href="{{ route('members.create') }}" class="cms-btn cms-btn--ghost" style="justify-content:flex-start"><i class="fas fa-user-plus" aria-hidden="true"></i> Add Member</a>
                <a href="{{ route('ss.attendance.index') }}" class="cms-btn cms-btn--ghost" style="justify-content:flex-start"><i class="fas fa-clipboard-check" aria-hidden="true"></i> Sunday School Attendance</a>
                <a href="{{ route('donations.create') }}" class="cms-btn cms-btn--ghost" style="justify-content:flex-start"><i class="fas fa-hand-holding-heart" aria-hidden="true"></i> Record Donation</a>
                <a href="{{ route('analytics.site-traffic.index') }}" class="cms-btn cms-btn--ghost" style="justify-content:flex-start"><i class="fas fa-chart-area" aria-hidden="true"></i> Site Traffic</a>
            </div>
        </article>
        <article class="cms-card">
            <div class="cms-card__head">
                <h3 class="cms-card__title">Your Session</h3>
            </div>
            <div class="cms-card__body">
                <dl class="cms-meta-list">
                    <div><dt>Email</dt><dd>{{ $admin->email }}</dd></div>
                    <div><dt>Role</dt><dd>{{ ucfirst(str_replace('_', ' ', (string) $admin->role)) }}</dd></div>
                    <div><dt>Department</dt><dd>{{ $admin->department ?: 'Not set' }}</dd></div>
                    <div><dt>Theme</dt><dd>{{ $admin->ui_theme }}/{{ $admin->ui_mode }}</dd></div>
                </dl>
            </div>
        </article>
    </div>
</x-app-layout>
