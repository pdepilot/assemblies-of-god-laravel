<div class="flex flex-wrap gap-2 text-sm">
    <a href="{{ route('website.dashboard') }}" class="px-3 py-1 rounded border {{ request()->routeIs('website.dashboard') ? 'bg-indigo-600 text-white' : '' }}">Dashboard</a>
    <a href="{{ route('website.blog.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('website.blog.*') ? 'bg-indigo-600 text-white' : '' }}">Blog</a>
    <a href="{{ route('website.about.edit') }}" class="px-3 py-1 rounded border {{ request()->routeIs('website.about.*') ? 'bg-indigo-600 text-white' : '' }}">About</a>
    <a href="{{ route('website.team.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('website.team.*') ? 'bg-indigo-600 text-white' : '' }}">Team</a>
    <a href="{{ route('website.pages.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('website.pages.*') ? 'bg-indigo-600 text-white' : '' }}">Page Manager</a>
    <a href="{{ route('website.seo.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('website.seo.*') ? 'bg-indigo-600 text-white' : '' }}">SEO</a>
    <a href="{{ route('website.media.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('website.media.*') ? 'bg-indigo-600 text-white' : '' }}">Media Library</a>
</div>
