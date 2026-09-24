<?php

use Illuminate\Support\Facades\DB;

function seedPublicSermon(array $overrides = []): void
{
    $slug = $overrides['slug'] ?? 'grace-for-the-journey';
    DB::table('sermons')->insert(array_merge([
        'sermon_code' => 'SRM-'.strtoupper(substr(md5($slug), 0, 8)),
        'title' => 'Grace for the Journey',
        'slug' => $slug,
        'description' => 'A message on walking with Christ through every season.',
        'content_html' => '<p>God is faithful from the first step to the last.</p>',
        'scripture_refs' => 'Psalm 23:1',
        'sermon_date' => now()->toDateString(),
        'minister_name' => 'Rev. Bethel Nwanebu',
        'sermon_type' => 'video',
        'status' => 'published',
        'published_at' => now(),
        'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9wgGcQ',
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));
}

test('public sermons page uses the redesigned library layout', function () {
    /** @var \Tests\TestCase $this */
    $this->get(route('public.sermons'))
        ->assertOk()
        ->assertSee('sermon-library', false)
        ->assertSee('Latest Messages', false)
        ->assertSee('Search sermons', false)
        ->assertSee('Plan a visit', false);
});

test('published sermons appear on the public sermons library', function () {
    /** @var \Tests\TestCase $this */
    seedPublicSermon();

    $this->get(route('public.sermons'))
        ->assertOk()
        ->assertSee('Grace for the Journey', false)
        ->assertSee('Rev. Bethel Nwanebu', false)
        ->assertSee('sermon-library-featured', false);
});

test('sermon search and type filters work on the public library', function () {
    /** @var \Tests\TestCase $this */
    seedPublicSermon(['slug' => 'grace-search', 'title' => 'Grace Search Message']);
    seedPublicSermon([
        'slug' => 'audio-only-word',
        'title' => 'Audio Only Word',
        'sermon_code' => 'SRM-AUDIO01',
        'sermon_type' => 'audio',
        'youtube_url' => '',
        'audio_stream_url' => 'https://example.com/audio.mp3',
    ]);

    $this->get(route('public.sermons', ['q' => 'Grace Search']))
        ->assertOk()
        ->assertSee('Grace Search Message', false)
        ->assertDontSee('Audio Only Word', false);

    $this->get(route('public.sermons', ['type' => 'audio']))
        ->assertOk()
        ->assertSee('Audio Only Word', false)
        ->assertDontSee('Grace Search Message', false);
});

test('public sermon show accepts legacy underscore slugs', function () {
    /** @var \Tests\TestCase $this */
    seedPublicSermon([
        'slug' => 'walking_closer_with_jesus_every_day',
        'title' => 'Walking Closer With Jesus Every Day',
        'sermon_code' => 'SRM-WALK01',
    ]);

    $this->get('/sermons/walking_closer_with_jesus_every_day')
        ->assertOk()
        ->assertSee('Walking Closer With Jesus Every Day', false);

    $this->get('/sermons/walking-closer-with-jesus-every-day')
        ->assertOk()
        ->assertSee('Walking Closer With Jesus Every Day', false);
});

test('public sermon show plays an uploaded video file', function () {
    /** @var \Tests\TestCase $this */
    seedPublicSermon([
        'slug' => 'uploaded-video-word',
        'title' => 'Uploaded Video Word',
        'sermon_code' => 'SRM-UVID01',
        'youtube_url' => '',
        'video_file_path' => 'uploads/sermons/videos/sample.mp4',
    ]);

    $this->get(route('public.sermons.show', 'uploaded-video-word'))
        ->assertOk()
        ->assertSee('Uploaded Video Word', false)
        ->assertSee('uploads/sermons/videos/sample.mp4', false)
        ->assertDontSee('youtube.com/embed', false);
});

test('public sermon show uses the redesigned detail layout and embed', function () {
    /** @var \Tests\TestCase $this */
    seedPublicSermon(['slug' => 'public-detail-sermon', 'title' => 'Public Detail Sermon']);

    $this->get(route('public.sermons.show', 'public-detail-sermon'))
        ->assertOk()
        ->assertSee('sermon-detail', false)
        ->assertSee('Public Detail Sermon', false)
        ->assertSee('youtube.com/embed/dQw4w9wgGcQ', false)
        ->assertSee('Back to sermons', false);
});
