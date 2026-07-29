@php
    $ssActive = $active ?? 'overview';
    $canIntelligence = $canIntelligence ?? false;
    $ssTabs = [
        'overview' => ['label' => 'Overview', 'route' => 'ss.analytics.index'],
    ];
    if ($canIntelligence) {
        $ssTabs['intelligence'] = ['label' => 'Intelligence', 'route' => 'ss.analytics.index', 'params' => ['tab' => 'intelligence']];
    }
    $ssTabs += [
        'classes' => ['label' => 'Classes', 'route' => 'ss.classes.index'],
        'teachers' => ['label' => 'Teachers', 'route' => 'ss.teachers.index'],
        'students' => ['label' => 'Students', 'route' => 'ss.students.index'],
        'attendance' => ['label' => 'Attendance', 'route' => 'ss.attendance.index'],
        'offerings' => ['label' => 'Offerings', 'route' => 'ss.offerings.index'],
        'lessons' => ['label' => 'Curriculum', 'route' => 'ss.lessons.index'],
        'visitors' => ['label' => 'Visitors', 'route' => 'ss.visitors.index'],
        'promotions' => ['label' => 'Promotions', 'route' => 'ss.promotions.index'],
        'reports' => ['label' => 'Reports', 'route' => 'ss.reports.index'],
        'awards' => ['label' => 'Awards', 'route' => 'ss.awards.index'],
        'certificates' => ['label' => 'Certificates', 'route' => 'ss.certificates.index'],
        'notifications' => ['label' => 'Notifications', 'route' => 'ss.notifications.index'],
        'correction-audit' => ['label' => 'Correction Audit', 'route' => 'ss.attendance.removals'],
    ];
@endphp

<div class="ss-tab-select-wrap">
    <label for="ssTabSelect" class="visually-hidden">Sunday School section</label>
    <select id="ssTabSelect" class="ss-tab-select" aria-label="Jump to section" onchange="if (this.value) window.location.href = this.value;">
        @foreach ($ssTabs as $key => $tab)
            <option value="{{ route($tab['route'], $tab['params'] ?? []) }}" @selected($ssActive === $key)>
                {{ $tab['label'] }}
            </option>
        @endforeach
    </select>
</div>

<nav class="cms-tabs" data-tabs role="tablist" aria-label="Sunday School sections">
    @foreach ($ssTabs as $key => $tab)
        <a
            href="{{ route($tab['route'], $tab['params'] ?? []) }}"
            class="cms-tab{{ $ssActive === $key ? ' is-active' : '' }}"
            role="tab"
            aria-selected="{{ $ssActive === $key ? 'true' : 'false' }}"
        >{{ $tab['label'] }}</a>
    @endforeach
</nav>
