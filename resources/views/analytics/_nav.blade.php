<div class="flex flex-wrap gap-2 text-sm">
    <a href="{{ route('analytics.site-traffic.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('analytics.site-traffic.*') ? 'bg-indigo-600 text-white' : '' }}">Site Traffic</a>
    <a href="{{ route('analytics.reports.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('analytics.reports.*') ? 'bg-indigo-600 text-white' : '' }}">Reports Hub</a>
    <a href="{{ route('analytics.cutover.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('analytics.cutover.*') ? 'bg-indigo-600 text-white' : '' }}">Migration Cutover</a>
</div>
