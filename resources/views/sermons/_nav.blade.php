<div class="flex flex-wrap gap-2 text-sm">
    <a href="{{ route('sermon.dashboard') }}" class="px-3 py-1 rounded border {{ request()->routeIs('sermon.dashboard') ? 'bg-indigo-600 text-white' : '' }}">Dashboard</a>
    <a href="{{ route('sermon.sermons.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('sermon.sermons.*') ? 'bg-indigo-600 text-white' : '' }}">Sermons</a>
    <a href="{{ route('sermon.broadcasts.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('sermon.broadcasts.*') ? 'bg-indigo-600 text-white' : '' }}">Broadcasts</a>
</div>
