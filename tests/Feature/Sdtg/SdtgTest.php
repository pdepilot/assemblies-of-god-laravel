<?php

use App\Models\Admin;
use App\Services\Sdtg\SdtgContentReadService;
use App\Services\Sdtg\SdtgLivestreamReadService;
use Illuminate\Support\Facades\DB;

test('sdtg administrator can view sdtg dashboard', function () {
    $admin = Admin::factory()->create(['role' => 'sdtg_administrator']);

    $response = $this->actingAs($admin, 'sdtg')->get(route('sdtg.dashboard'));

    $response->assertOk();
    $response->assertSee('SDTG Crusade Admin');
    $response->assertSee('Registrations');
});

test('sdtg administrator can update registration status', function () {
    $admin = Admin::factory()->create(['role' => 'sdtg_administrator']);

    $id = DB::table('sdtg_registrations')->insertGetId([
        'registration_code' => 'SDTG-TEST-001',
        'event_id' => 'sdtg-2026',
        'full_name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'registration_date' => '2026-07-20',
        'status' => 'pending',
        'created_at' => now(),
    ]);

    $this->actingAs($admin, 'sdtg')
        ->get(route('sdtg.registrations.show', $id))
        ->assertOk()
        ->assertSee('Jane Doe')
        ->assertSee('confirmed');

    $this->actingAs($admin, 'sdtg')->put(route('sdtg.registrations.update', $id), [
        'status' => 'confirmed',
        'notes' => 'Checked in at gate',
    ])->assertRedirect();

    $this->assertDatabaseHas('sdtg_registrations', [
        'id' => $id,
        'status' => 'confirmed',
        'notes' => 'Checked in at gate',
    ]);
});

test('ss teacher cannot access sdtg module', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);

    $this->actingAs($admin, 'sdtg')->get(route('sdtg.dashboard'))->assertForbidden();
});

test('sdtg administrator can view livestream control page', function () {
    $admin = Admin::factory()->create(['role' => 'sdtg_administrator']);

    $this->actingAs($admin, 'sdtg')
        ->get(route('sdtg.livestream.index'))
        ->assertOk()
        ->assertSee('SDTG Livestream')
        ->assertSee('Broadcast control')
        ->assertDontSee('AG_IKENEGBU_CHURCH_WEBSITE/portal/sdtg/livestream');
});

test('sdtg administrator can view speakers index', function () {
    $admin = Admin::factory()->create(['role' => 'sdtg_administrator']);
    $year = (int) date('Y');

    DB::table('sdtg_speakers')->insert([
        'slug' => 'test-speaker-'.$year,
        'full_name' => 'Pastor Test Speaker',
        'ministry' => 'AG Ikenebgu',
        'country' => 'Nigeria',
        'crusade_year' => $year,
        'speaker_type' => 'upcoming',
        'status' => 'confirmed',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin, 'sdtg')
        ->get(route('sdtg.speakers.index'))
        ->assertOk()
        ->assertSee('SDTG Speakers')
        ->assertSee('Pastor Test Speaker')
        ->assertDontSee('Communication Templates');
});

test('sdtg administrator can manage gallery albums and items', function () {
    $admin = Admin::factory()->create(['role' => 'sdtg_administrator']);
    $year = (int) date('Y');

    $this->actingAs($admin, 'sdtg')
        ->post(route('sdtg.gallery.albums.store'), [
            'title' => 'Opening Night',
            'crusade_year' => $year,
            'description' => 'Highlights',
            'is_published' => '1',
            'sort_order' => 1,
            'cover' => Illuminate\Http\UploadedFile::fake()->image('cover.jpg', 640, 480),
        ])
        ->assertRedirect(route('sdtg.gallery.index', ['tab' => 'collections']));

    $albumId = (int) DB::table('sdtg_gallery_albums')->where('title', 'Opening Night')->value('id');
    expect($albumId)->toBeGreaterThan(0);

    $coverPath = (string) DB::table('sdtg_gallery_albums')->where('id', $albumId)->value('cover_path');
    expect($coverPath)->toStartWith('uploads/sdtg-gallery/');
    expect(is_file(public_path('site/'.$coverPath)))->toBeTrue();

    $this->actingAs($admin, 'sdtg')
        ->post(route('sdtg.gallery.items.store'), [
            'title' => 'Worship Moment',
            'crusade_year' => $year,
            'album_id' => $albumId,
            'media_type' => 'photo',
            'category' => 'worship',
            'layout_size' => 'md',
            'is_published' => '1',
            'is_speakers_highlight' => '1',
            'media' => Illuminate\Http\UploadedFile::fake()->image('worship.jpg', 800, 600),
        ])
        ->assertRedirect(route('sdtg.gallery.index', ['tab' => 'photos']));

    $itemId = (int) DB::table('sdtg_gallery_items')->where('title', 'Worship Moment')->value('id');
    $filePath = (string) DB::table('sdtg_gallery_items')->where('id', $itemId)->value('file_path');
    expect($filePath)->toStartWith('uploads/sdtg-gallery/');
    expect(is_file(public_path('site/'.$filePath)))->toBeTrue();

    $this->actingAs($admin, 'sdtg')
        ->get(route('sdtg.gallery.index', ['tab' => 'photos']))
        ->assertOk()
        ->assertSee('Photo Archive')
        ->assertSee('Captured In His Presence')
        ->assertSee('Worship Moment')
        ->assertSee('site/uploads/sdtg-gallery/', false)
        ->assertDontSee('/portal/sdtg/gallery');

    $this->actingAs($admin, 'sdtg')
        ->get(route('sdtg.gallery.index', ['tab' => 'videos']))
        ->assertOk()
        ->assertSee('Video Archive')
        ->assertSee('Watch & Relive')
        ->assertSee('Upload video');

    $this->actingAs($admin, 'sdtg')
        ->get(route('sdtg.gallery.items.edit', $itemId))
        ->assertOk()
        ->assertSee('site/uploads/sdtg-gallery/', false);

    $this->getJson(route('public.sdtg.gallery.api'))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.photos.0.title', 'Worship Moment')
        ->assertJsonFragment(['title' => 'Worship Moment']);

    $this->assertDatabaseHas('sdtg_gallery_items', [
        'title' => 'Worship Moment',
        'category' => 'worship',
        'is_speakers_highlight' => 1,
    ]);
});

test('sdtg gallery admin manages editions timeline and features community memories', function () {
    $admin = Admin::factory()->create(['role' => 'sdtg_administrator']);
    $year = 2098;

    $this->actingAs($admin, 'sdtg')
        ->get(route('sdtg.gallery.index'))
        ->assertOk()
        ->assertSee('Photo Archive')
        ->assertSee('Captured In His Presence')
        ->assertSee('Through The Years');

    $this->actingAs($admin, 'sdtg')
        ->from(route('sdtg.gallery.index', ['tab' => 'timeline']))
        ->post(route('sdtg.gallery.editions.store'), [
            'crusade_year' => $year,
            'theme' => 'Glory Overflow',
            'speakers_summary' => 'Guest ministers from AG Ikenebgu',
            'highlights' => 'Worship nights and healing services',
            'venue' => 'Owerri, Nigeria',
            'is_published' => '1',
        ])
        ->assertRedirect(route('sdtg.gallery.index', ['tab' => 'timeline']));

    $this->assertDatabaseHas('sdtg_crusade_editions', [
        'crusade_year' => $year,
        'theme' => 'Glory Overflow',
        'is_published' => 1,
    ]);

    $this->getJson(route('public.sdtg.gallery.api'))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonFragment(['theme' => 'Glory Overflow'])
        ->assertJsonFragment(['value' => (string) $year]);

    $memoryId = (int) DB::table('sdtg_memory_submissions')->insertGetId([
        'full_name' => 'Ada Obi',
        'email' => 'ada@example.com',
        'edition' => (string) $year,
        'testimony_text' => 'God healed my family at SDTG.',
        'photo_paths' => json_encode([]),
        'video_paths' => json_encode([]),
        'status' => 'pending',
        'created_at' => now(),
    ]);

    $this->actingAs($admin, 'sdtg')
        ->put(route('sdtg.gallery.memories.update', $memoryId), [
            'status' => 'featured',
        ])
        ->assertRedirect(route('sdtg.gallery.index', ['tab' => 'community']));

    $this->assertDatabaseHas('sdtg_memory_submissions', [
        'id' => $memoryId,
        'status' => 'featured',
    ]);

    $this->getJson(route('public.sdtg.gallery.api'))
        ->assertOk()
        ->assertJsonFragment(['name' => 'Ada Obi'])
        ->assertJsonFragment(['testimony' => 'God healed my family at SDTG.']);

    $this->postJson(route('public.sdtg.memory.submit'), [
        'full_name' => 'Chidi Okoro',
        'email' => 'chidi@example.com',
        'edition' => (string) $year,
        'testimony_text' => 'A night of breakthrough.',
    ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('sdtg_memory_submissions', [
        'full_name' => 'Chidi Okoro',
        'status' => 'pending',
    ]);
});

test('sdtg administrator can manage media library folders and assets', function () {
    $admin = Admin::factory()->create(['role' => 'sdtg_administrator']);

    $this->actingAs($admin, 'sdtg')
        ->post(route('sdtg.media-library.folders.store'), [
            'name' => 'Promo Pack',
            'description' => 'Social assets',
        ])
        ->assertRedirect();

    $folderId = (int) DB::table('sdtg_media_folders')->where('name', 'Promo Pack')->value('id');
    expect($folderId)->toBeGreaterThan(0);

    $this->actingAs($admin, 'sdtg')
        ->post(route('sdtg.media-library.assets.store'), [
            'title' => 'Crusade Banner',
            'media_type' => 'image',
            'status' => 'published',
            'crusade_year' => (int) date('Y'),
            'folder_id' => $folderId,
            'media' => Illuminate\Http\UploadedFile::fake()->image('banner.jpg', 800, 450),
        ])
        ->assertRedirect(route('sdtg.media-library.index'));

    $path = (string) DB::table('sdtg_media_assets')->where('title', 'Crusade Banner')->value('file_path');
    expect($path)->toStartWith('uploads/sdtg-media/');
    expect(is_file(public_path('site/'.$path)))->toBeTrue();

    $this->actingAs($admin, 'sdtg')
        ->get(route('sdtg.media-library.index'))
        ->assertOk()
        ->assertSee('SDTG Media Library')
        ->assertSee('Promo Pack')
        ->assertSee('Crusade Banner')
        ->assertDontSee('/portal/sdtg/media-library');
});

test('sdtg administrator can go live and end stream', function () {
    $admin = Admin::factory()->create(['role' => 'sdtg_administrator']);

    $this->actingAs($admin, 'sdtg')
        ->post(route('sdtg.livestream.toggle'), [
            'is_live' => '1',
            'session_name' => 'Opening Night',
            'current_viewers' => 120,
        ])
        ->assertRedirect(route('sdtg.livestream.index'))
        ->assertSessionHas('status');

    $this->assertDatabaseHas('platform_setting_groups', [
        'group_key' => 'livestream',
    ]);

    $stored = DB::table('platform_setting_groups')->where('group_key', 'livestream')->value('settings');
    $settings = json_decode((string) $stored, true);
    expect($settings['is_live'] ?? false)->toBeTrue()
        ->and($settings['current_viewers'] ?? 0)->toBe(120);

    $this->actingAs($admin, 'sdtg')
        ->post(route('sdtg.livestream.toggle'), ['is_live' => '0'])
        ->assertRedirect(route('sdtg.livestream.index'));

    $stored = DB::table('platform_setting_groups')->where('group_key', 'livestream')->value('settings');
    $settings = json_decode((string) $stored, true);
    expect($settings['is_live'] ?? true)->toBeFalse();
});

test('sdtg administrator sees all catalog sections on content index', function () {
    $admin = Admin::factory()->create(['role' => 'sdtg_administrator']);

    $response = $this->actingAs($admin, 'sdtg')
        ->get(route('sdtg.content.index'))
        ->assertOk();

    $response->assertSee('Homepage Hero');
    $response->assertSee('Homepage Stats');
    $response->assertSee('Homepage About');
    $response->assertSee('Why Attend');
    $response->assertSee('Livestream Teaser');
    $response->assertSee('Homepage Donate Teaser');
    $response->assertSee('Donate Page');
    $response->assertSee('Livestream Page');
});

test('sdtg administrator can edit hero section and save it', function () {
    $admin = Admin::factory()->create(['role' => 'sdtg_administrator']);

    $this->actingAs($admin, 'sdtg')
        ->get(route('sdtg.content.edit', 'hero'))
        ->assertOk()
        ->assertSee('Homepage Hero')
        ->assertSee('SEND DOWN')
        ->assertSee('Upload only — no URL')
        ->assertSee('Background video')
        ->assertSee('Poster / background image')
        ->assertDontSee('Video URL')
        ->assertDontSee('name="content[video_url]"', false);

    $this->actingAs($admin, 'sdtg')
        ->put(route('sdtg.content.update', 'hero'), [
            'content' => [
                'eyebrow' => 'International Music Crusade',
                'title_line1' => 'ARISE AND',
                'title_line2' => 'SHINE',
                'subtitle' => 'Updated subtitle copy.',
                'primary_label' => 'Register Now',
                'primary_link' => 'registration',
                'secondary_label' => 'Watch Previous Crusades',
                'secondary_link' => '#gallery',
            ],
        ])
        ->assertRedirect(route('sdtg.content.edit', 'hero'))
        ->assertSessionHas('status');

    $stored = DB::table('sdtg_site_content')->where('section_key', 'hero')->value('content_json');
    $decoded = json_decode((string) $stored, true);
    expect($decoded['title_line1'] ?? null)->toBe('ARISE AND');

    $read = app(SdtgContentReadService::class);
    expect($read->getSection('hero')['title_line1'])->toBe('ARISE AND');
});

test('sdtg administrator can save livestream page hero subsection', function () {
    $admin = Admin::factory()->create(['role' => 'sdtg_administrator']);

    $this->actingAs($admin, 'sdtg')
        ->put(route('sdtg.content.update', 'livestream_page'), [
            'tab' => 'hero',
            'content' => [
                'badge' => 'Global Broadcast · Live From Owerri',
                'title_before' => 'Custom Livestream Title',
                'title_highlight' => 'Live',
                'subtitle' => 'Custom subtitle.',
                'primary_label' => 'Watch Live Now',
                'primary_link' => '#player',
                'secondary_label' => 'Register For Crusade',
                'secondary_link' => 'registration',
                'tertiary_label' => 'Submit Prayer Request',
                'tertiary_link' => '#prayer',
            ],
        ])
        ->assertRedirect(route('sdtg.content.edit', ['section' => 'livestream_page', 'tab' => 'hero']))
        ->assertSessionHas('status');

    $read = app(SdtgContentReadService::class);
    expect($read->getSection('livestream_page')['hero']['title_before'] ?? null)->toBe('Custom Livestream Title');

    $livestream = app(SdtgLivestreamReadService::class);
    expect($livestream->getPageContent()['hero']['title_before'] ?? null)->toBe('Custom Livestream Title');
});

test('ss teacher is forbidden from sdtg content index', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);

    $this->actingAs($admin, 'sdtg')
        ->get(route('sdtg.content.index'))
        ->assertForbidden();
});
