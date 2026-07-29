<div class="flex flex-wrap gap-2 text-sm">
    <a href="{{ route('sdtg.dashboard') }}" class="px-3 py-1 rounded border {{ request()->routeIs('sdtg.dashboard') ? 'bg-indigo-600 text-white' : '' }}">Dashboard</a>
    <a href="{{ route('sdtg.registrations.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('sdtg.registrations.*') ? 'bg-indigo-600 text-white' : '' }}">Registrations</a>
    <a href="{{ route('sdtg.speakers.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('sdtg.speakers.*') ? 'bg-indigo-600 text-white' : '' }}">Speakers</a>
    <a href="{{ route('sdtg.gallery.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('sdtg.gallery.*') ? 'bg-indigo-600 text-white' : '' }}">Gallery</a>
    <a href="{{ route('sdtg.media-library.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('sdtg.media-library.*') ? 'bg-indigo-600 text-white' : '' }}">Media Library</a>
    <a href="{{ route('sdtg.announcements.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('sdtg.announcements.*') ? 'bg-indigo-600 text-white' : '' }}">Announcements</a>
    <a href="{{ route('sdtg.content.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('sdtg.content.*') ? 'bg-indigo-600 text-white' : '' }}">Content</a>
    <a href="{{ route('sdtg.livestream.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('sdtg.livestream.*') ? 'bg-indigo-600 text-white' : '' }}">Livestream</a>
    <a href="{{ route('sdtg.community.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('sdtg.community.*') ? 'bg-indigo-600 text-white' : '' }}">Community</a>
</div>
