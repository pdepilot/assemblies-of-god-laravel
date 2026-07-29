<div class="flex flex-wrap gap-2 text-sm">
    <a href="{{ route('communication-hub.dashboard') }}" class="px-3 py-1 rounded border {{ request()->routeIs('communication-hub.dashboard') ? 'bg-indigo-600 text-white' : '' }}">Dashboard</a>
    <a href="{{ route('communication-hub.birthdays.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('communication-hub.birthdays.*') ? 'bg-indigo-600 text-white' : '' }}">Birthdays</a>
    <a href="{{ route('communication-hub.email-center.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('communication-hub.email-center.*') ? 'bg-indigo-600 text-white' : '' }}">Email Center</a>
    <a href="{{ route('communication-hub.sms-center.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('communication-hub.sms-center.*') ? 'bg-indigo-600 text-white' : '' }}">SMS Center</a>
    <a href="{{ route('communication-hub.settings.edit') }}" class="px-3 py-1 rounded border {{ request()->routeIs('communication-hub.settings.*') ? 'bg-indigo-600 text-white' : '' }}">Email / SMS</a>
    <a href="{{ route('communication-hub.templates.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('communication-hub.templates.*') ? 'bg-indigo-600 text-white' : '' }}">Templates</a>
    <a href="{{ route('communication-hub.automation.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('communication-hub.automation.*') ? 'bg-indigo-600 text-white' : '' }}">Automation</a>
    <a href="{{ route('communication-hub.campaigns.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('communication-hub.campaigns.*') ? 'bg-indigo-600 text-white' : '' }}">Campaigns</a>
    <a href="{{ route('communication-hub.scheduled.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('communication-hub.scheduled.*') ? 'bg-indigo-600 text-white' : '' }}">Scheduled</a>
    <a href="{{ route('communication-hub.queue.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('communication-hub.queue.*') ? 'bg-indigo-600 text-white' : '' }}">Queue</a>
    <a href="{{ route('communication-hub.recipients.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('communication-hub.recipients.*') ? 'bg-indigo-600 text-white' : '' }}">Recipients</a>
    <a href="{{ route('communication-hub.logs.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('communication-hub.logs.*') ? 'bg-indigo-600 text-white' : '' }}">Logs</a>
    <a href="{{ route('communication-hub.analytics.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('communication-hub.analytics.*') ? 'bg-indigo-600 text-white' : '' }}">Analytics</a>
    <a href="{{ route('communication-hub.ai-assistant.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('communication-hub.ai-assistant.*') ? 'bg-indigo-600 text-white' : '' }}">AI Assistant</a>
    <a href="{{ route('communication-hub.newsletter-drafts.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('communication-hub.newsletter-drafts.*') ? 'bg-indigo-600 text-white' : '' }}">Newsletter Drafts</a>
    <a href="{{ route('communication-hub.notifications.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('communication-hub.notifications.*') ? 'bg-indigo-600 text-white' : '' }}">Notifications</a>
    <a href="{{ route('contact.submissions.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('contact.*') ? 'bg-indigo-600 text-white' : '' }}">Contact Inbox</a>
    <a href="{{ route('newsletter-subscribers.index') }}" class="px-3 py-1 rounded border {{ request()->routeIs('newsletter-subscribers.*') ? 'bg-indigo-600 text-white' : '' }}">Subscribers</a>
</div>
