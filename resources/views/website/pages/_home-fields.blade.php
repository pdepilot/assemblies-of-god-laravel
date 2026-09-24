            <div class="rounded-md border border-indigo-100 bg-indigo-50/60 dark:bg-indigo-950/30 p-3 text-sm">
    Also edit the <a href="{{ route('website.pages.hero.edit') }}" class="text-indigo-700 font-semibold underline">homepage hero banner</a>
    and the <a href="{{ route('website.about.edit') }}" class="text-indigo-700 font-semibold underline">About section body</a>.
</div>

<div id="worship-section" class="space-y-3 scroll-mt-24">
    <h3 class="font-semibold">Our Worship</h3>
    <p class="text-sm text-gray-600 dark:text-gray-300">The homepage heading stays “Our Worship”. Edit the cards and map below.</p>
    @include('website.worship._program-fields', [
        'programs' => $worshipPrograms ?? [],
        'location' => $worshipLocation ?? [],
    ])
</div>

<h3 class="font-semibold pt-2">Activities section</h3>
<p class="text-sm text-gray-600 dark:text-gray-300">The homepage heading stays “Activities”. Edit the cards below.</p>
@include('website.activities._activity-fields', [
    'activities' => $homepageActivities ?? [],
])

<h3 class="font-semibold pt-2">Ministries section</h3>
<div>
    <label class="block text-sm font-medium">Eyebrow</label>
    <input name="ministries_eyebrow" value="{{ old('ministries_eyebrow', $page['ministries_eyebrow'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
</div>
<div>
    <label class="block text-sm font-medium">Title</label>
    <input name="ministries_title" value="{{ old('ministries_title', $page['ministries_title'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
</div>

<h3 class="font-semibold pt-2">Events section</h3>
<div>
    <label class="block text-sm font-medium">Eyebrow</label>
    <input name="events_eyebrow" value="{{ old('events_eyebrow', $page['events_eyebrow'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
</div>
<div>
    <label class="block text-sm font-medium">Title</label>
    <input name="events_title" value="{{ old('events_title', $page['events_title'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
</div>
<div>
    <label class="block text-sm font-medium">Intro</label>
    <textarea name="events_intro" rows="3" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('events_intro', $page['events_intro'] ?? '') }}</textarea>
</div>

<h3 class="font-semibold pt-2">Sermons section</h3>
<div>
    <label class="block text-sm font-medium">Eyebrow</label>
    <input name="sermons_eyebrow" value="{{ old('sermons_eyebrow', $page['sermons_eyebrow'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
</div>
<div>
    <label class="block text-sm font-medium">Title</label>
    <input name="sermons_title" value="{{ old('sermons_title', $page['sermons_title'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
</div>

<h3 class="font-semibold pt-2">Team section</h3>
<div>
    <label class="block text-sm font-medium">Eyebrow</label>
    <input name="team_eyebrow" value="{{ old('team_eyebrow', $page['team_eyebrow'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
</div>
<div>
    <label class="block text-sm font-medium">Title</label>
    <input name="team_title" value="{{ old('team_title', $page['team_title'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
</div>

<h3 class="font-semibold pt-2">Testimonials section</h3>
<div>
    <label class="block text-sm font-medium">Eyebrow</label>
    <input name="testimonials_eyebrow" value="{{ old('testimonials_eyebrow', $page['testimonials_eyebrow'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
</div>
<div>
    <label class="block text-sm font-medium">Title</label>
    <input name="testimonials_title" value="{{ old('testimonials_title', $page['testimonials_title'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
</div>
<div>
    <label class="block text-sm font-medium">Intro</label>
    <textarea name="testimonials_intro" rows="2" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('testimonials_intro', $page['testimonials_intro'] ?? '') }}</textarea>
</div>
