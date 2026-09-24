<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Website</h2></x-slot>
    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @include('website._nav', ['canManage' => $canManage])
            <div class="rounded-lg border border-indigo-200 bg-indigo-50 dark:bg-indigo-950/30 dark:border-indigo-800 p-4 text-sm text-indigo-900 dark:text-indigo-100">
                <p class="font-medium mb-1">Public site coverage</p>
                <p>Edits here now drive the live frontend: homepage hero (first slide), About content, Homepage Activities cards, Our Worship programmes, Team, SEO titles/descriptions (including remaining legacy pages), published Blog posts at <code>/blog</code>, and the promotion banner that appears as soon as the public site loads. Events, Sermons, Testimonials, and Contact details still come from their own admin modules.</p>
            </div>
            @if ($canManage)
                <a href="{{ route('website.activities.edit') }}" class="block rounded-lg border border-indigo-200 bg-white dark:bg-gray-800 p-4 hover:border-indigo-400">
                    <div class="font-semibold">Homepage Activities</div>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Edit Sunday Worship, outreach, Bible study, and the other cards on the public homepage.</p>
                </a>
                <a href="{{ route('website.worship.edit') }}" class="block rounded-lg border border-indigo-200 bg-white dark:bg-gray-800 p-4 hover:border-indigo-400">
                    <div class="font-semibold">Our Worship</div>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Edit Sunday, midweek, and prayer cards plus the church location map on the homepage.</p>
                </a>
            @endif
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($blogStats as $label => $value)
                    <div class="rounded-lg border p-4 bg-white dark:bg-gray-800">
                        <div class="text-sm text-gray-500">{{ ucfirst(str_replace('_', ' ', $label)) }}</div>
                        <div class="text-2xl font-semibold">{{ $value }}</div>
                    </div>
                @endforeach
                @foreach ($mediaCounts as $label => $value)
                    <div class="rounded-lg border p-4 bg-white dark:bg-gray-800">
                        <div class="text-sm text-gray-500">{{ ucfirst(str_replace('_', ' ', $label)) }}</div>
                        <div class="text-2xl font-semibold">{{ $value }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
