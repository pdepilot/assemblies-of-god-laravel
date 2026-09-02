<?php

use App\Models\Admin;

test('content editor can view and save about content editor', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $this->actingAs($admin, 'admin')
        ->get(route('website.about.edit'))
        ->assertOk()
        ->assertSee('About Page Content')
        ->assertSee('About Page (/about)')
        ->assertSee('Homepage About Section')
        ->assertSee('Growing Together in Faith, Hope, and Love')
        ->assertDontSee('Content JSON');

    $this->actingAs($admin, 'admin')
        ->put(route('website.about.update'), [
            'section' => 'homepage_about',
            'content' => [
                'eyebrow' => 'About AGC Ikenegbu',
                'title' => 'Custom About Title',
                'intro' => 'Custom intro text for homepage about.',
                'vision_title' => 'Our Vision',
                'vision_text' => 'Vision text',
                'mission_title' => 'Our Mission',
                'mission_text' => 'Mission text',
                'features_text' => "Worship\nPrayer",
                'gallery' => [
                    ['image' => 'images/main1.jpg', 'alt' => 'Main'],
                ],
                'highlight' => [
                    'image' => 'images/rev1.jpg',
                    'image_alt' => 'Highlight',
                    'quote' => 'A quote',
                    'stat_value' => '100+',
                    'stat_label' => 'Members',
                ],
                'scripture_banner' => [
                    'cta_label' => 'Learn More',
                    'cta_url' => 'about',
                    'verses' => [
                        ['quote' => 'For God so loved the world.', 'reference' => 'John 3:16'],
                    ],
                ],
            ],
        ])
        ->assertRedirect(route('website.about.edit', ['tab' => 'homepage_about']));

    $this->assertDatabaseHas('ag_site_content', ['section_key' => 'homepage_about']);
    $row = \Illuminate\Support\Facades\DB::table('ag_site_content')->where('section_key', 'homepage_about')->first();
    expect($row)->not->toBeNull();
    expect((string) $row->content_json)->toContain('Custom About Title');
});

test('content editor can view website dashboard', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $response = $this->actingAs($admin, 'admin')->get(route('website.dashboard'));

    $response->assertOk();
    $response->assertSee('Website CMS');
    $response->assertSee('Blog');
});

test('content editor can view website pages and edit hero', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $this->actingAs($admin, 'admin')
        ->get(route('website.pages.index'))
        ->assertOk()
        ->assertSee('Frontend Page Manager')
        ->assertSee('Homepage hero');

    $this->actingAs($admin, 'admin')
        ->put(route('website.pages.hero.update'), [
            'headline' => 'Welcome Home',
            'subheadline' => 'Join us this Sunday',
            'cta_label' => 'Plan a visit',
            'cta_url' => '/contact',
        ])
        ->assertRedirect(route('website.pages.edit', 'home'));

    $this->actingAs($admin, 'admin')
        ->get(route('website.pages.index'))
        ->assertOk()
        ->assertSee('Welcome Home');

    $this->actingAs($admin, 'admin')
        ->get(route('website.pages.hero.edit'))
        ->assertOk()
        ->assertSee('Upload only')
        ->assertDontSee('Background image URL');
});

test('content editor can view seo team and media pages', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $this->actingAs($admin, 'admin')->get(route('website.seo.index'))->assertOk()->assertSee('SEO Settings');
    $this->actingAs($admin, 'admin')->get(route('website.team.index'))->assertOk()->assertSee('Team Members');
    $this->actingAs($admin, 'admin')->get(route('website.media.index'))->assertOk()->assertSee('Media Library');
});

test('content editor can create blog post', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $this->actingAs($admin, 'admin')->post(route('website.blog.store'), [
        'title' => 'Church Anniversary Update',
        'category' => 'church-news',
        'excerpt' => 'Celebrating 50 years of ministry.',
        'body_html' => '<p>Join us this Sunday.</p>',
    ])->assertRedirect();

    $this->assertDatabaseHas('ag_blog_posts', [
        'title' => 'Church Anniversary Update',
        'category' => 'church-news',
        'slug' => 'church-anniversary-update',
    ]);
});

test('ss teacher cannot access website cms', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);

    $this->actingAs($admin, 'admin')->get(route('website.dashboard'))->assertForbidden();
});

test('website hero save updates public homepage first slide', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $this->actingAs($admin, 'admin')
        ->put(route('website.pages.hero.update'), [
            'headline' => 'Public Hero Headline From CMS',
            'subheadline' => 'Public tagline from CMS',
            'cta_label' => 'Visit Us',
            'cta_url' => '/contact',
        ])
        ->assertRedirect(route('website.pages.edit', 'home'));

    $home = $this->get(route('public.home'));
    $home->assertOk();
    $home->assertSee('Public Hero Headline From CMS');
    $home->assertSee('Public tagline from CMS');

    $this->assertDatabaseHas('ag_site_content', [
        'section_key' => 'homepage_hero',
    ]);

    $row = \Illuminate\Support\Facades\DB::table('ag_site_content')
        ->where('section_key', 'homepage_hero')
        ->value('content_json');
    expect((string) $row)->toContain('Public Hero Headline From CMS');
    expect((string) $row)->toContain('Public tagline from CMS');
});

test('content editor can edit and save a website page override', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $this->actingAs($admin, 'admin')
        ->get(route('website.pages.edit', 'contact'))
        ->assertOk()
        ->assertSee('Manage page')
        ->assertSee('Contact');

    $this->actingAs($admin, 'admin')
        ->put(route('website.pages.update', 'contact'), [
            'heading' => 'Get In Touch CMS',
            'eyebrow' => 'We Are Here',
            'intro' => 'Reach the church office anytime.',
            'body_html' => '<p>Office hours and prayer line details.</p>',
            'cta_label' => '',
            'cta_url' => '',
        ])
        ->assertRedirect(route('website.pages.edit', 'contact'));

    $public = $this->get(route('public.contact'));
    $public->assertOk();
    $public->assertSee('Get In Touch CMS');
    $public->assertSee('We Are Here');
    $public->assertSee('Reach the church office anytime.');
    $public->assertSee('Office hours and prayer line details.', false);
});

test('homepage section titles come from pages home override', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $this->actingAs($admin, 'admin')
        ->put(route('website.pages.update', 'home'), [
            'ministries_eyebrow' => 'CMS Ministries',
            'ministries_title' => 'CMS Ministries Title',
            'events_eyebrow' => 'CMS Gather',
            'events_title' => 'CMS Events Title',
            'events_intro' => 'CMS events intro copy.',
            'worship_eyebrow' => 'CMS Visit',
            'worship_title' => 'CMS Worship Title',
            'worship_intro' => 'CMS worship intro copy.',
        ])
        ->assertRedirect(route('website.pages.edit', 'home'));

    $home = $this->get(route('public.home'));
    $home->assertOk();
    $home->assertSee('CMS Ministries Title');
    $home->assertSee('CMS Events Title');
    $home->assertSee('CMS Worship Title');
});

test('content editor can edit donate page copy from page manager', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $this->actingAs($admin, 'admin')
        ->get(route('website.pages.edit', 'donate'))
        ->assertOk()
        ->assertSee('Give / Donate')
        ->assertSee('Page content');

    $this->actingAs($admin, 'admin')
        ->put(route('website.pages.update', 'donate'), [
            'hero_badge' => 'CMS Give Badge',
            'hero_title' => "Give Freely.\nLove Deeply.",
            'hero_scripture' => 'CMS scripture line',
            'hero_ref' => '— CMS 1:1',
            'hero_cta_label' => 'CMS Give Now',
            'categories_title' => 'CMS Categories Title',
            'final_cta_title' => 'CMS Final CTA',
        ])
        ->assertRedirect(route('website.pages.edit', 'donate'));

    $donate = $this->get(route('public.donate'));
    $donate->assertOk();
    $donate->assertSee('CMS Give Badge');
    $donate->assertSee('Give Freely.');
    $donate->assertSee('Love Deeply.');
    $donate->assertSee('CMS Categories Title');
    $donate->assertSee('CMS Final CTA');
});

test('seo settings appear on public homepage meta tags', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $this->actingAs($admin, 'admin')
        ->put(route('website.seo.update', 'home'), [
            'title' => 'CMS SEO Home Title',
            'meta_description' => 'CMS SEO home description for AGC Ikenegbu.',
            'include_in_sitemap' => '1',
        ])
        ->assertRedirect(route('website.seo.edit', 'home'));

    $home = $this->get(route('public.home'));
    $home->assertOk();
    $home->assertSee('CMS SEO Home Title', false);
    $home->assertSee('CMS SEO home description for AGC Ikenegbu.', false);
    $home->assertSee('og:title', false);
});

test('contact seo edit saves end-to-end on public contact page', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $this->actingAs($admin, 'admin')
        ->get(route('website.seo.edit', 'contact'))
        ->assertOk()
        ->assertSee('Preview live')
        ->assertSee('Hero title')
        ->assertSee(route('public.contact'), false);

    $this->actingAs($admin, 'admin')
        ->put(route('website.seo.update', 'contact'), [
            'heading' => 'Visit Our Church',
            'eyebrow' => 'Welcome',
            'intro' => 'We would love to meet you this Sunday.',
            'title' => 'Plan Your Visit | AGC Contact SEO',
            'meta_description' => 'Custom contact meta for end-to-end SEO editing.',
            'include_in_sitemap' => '1',
            'robots_notes' => 'Index contact page',
        ])
        ->assertRedirect(route('website.seo.edit', 'contact'));

    $public = $this->get(route('public.contact'));
    $public->assertOk();
    $public->assertSee('Visit Our Church');
    $public->assertSee('We would love to meet you this Sunday.');
    $public->assertSee('Plan Your Visit | AGC Contact SEO', false);
    $public->assertSee('Custom contact meta for end-to-end SEO editing.', false);
});
test('about content ignores client-submitted image urls and paths', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $this->actingAs($admin, 'admin')
        ->put(route('website.about.update'), [
            'section' => 'homepage_about',
            'content' => [
                'eyebrow' => 'About AGC Ikenegbu',
                'title' => 'Title Without Image Hijack',
                'intro' => 'Intro',
                'vision_title' => 'Our Vision',
                'vision_text' => 'Vision text',
                'mission_title' => 'Our Mission',
                'mission_text' => 'Mission text',
                'features_text' => "Worship\nPrayer",
                'gallery' => [
                    ['image' => 'https://evil.example/hack.jpg', 'alt' => 'Hacked'],
                ],
                'highlight' => [
                    'image' => 'https://evil.example/hack2.jpg',
                    'image_alt' => 'Hacked',
                    'quote' => 'A quote',
                    'stat_value' => '100+',
                    'stat_label' => 'Members',
                ],
            ],
        ])
        ->assertRedirect(route('website.about.edit', ['tab' => 'homepage_about']));

    $row = \Illuminate\Support\Facades\DB::table('ag_site_content')->where('section_key', 'homepage_about')->first();
    expect($row)->not->toBeNull();
    expect((string) $row->content_json)->not->toContain('evil.example');
    expect((string) $row->content_json)->toContain('Title Without Image Hijack');
});

test('page manager chrome and seo image fields are upload-only', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $this->actingAs($admin, 'admin')
        ->get(route('website.pages.edit', 'contact'))
        ->assertOk()
        ->assertSee('Upload only — no URL or path')
        ->assertSee('name="hero_image"', false)
        ->assertSee('type="file"', false)
        ->assertDontSee('Background image URL')
        ->assertDontSee('Image path/URL')
        ->assertDontSee('placeholder="images/');
});

test('seo edit form is upload-only for open graph image', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $this->actingAs($admin, 'admin')
        ->get(route('website.seo.edit', 'home'))
        ->assertOk()
        ->assertSee('Upload only — no path or URL')
        ->assertSee('type="file"', false)
        ->assertSee('enctype="multipart/form-data"', false)
        ->assertDontSee('Relative to /site')
        ->assertDontSee('placeholder="images/');
});

test('about page inherits homepage_about body when about_page is not saved', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $this->actingAs($admin, 'admin')
        ->put(route('website.about.update'), [
            'section' => 'homepage_about',
            'content' => [
                'eyebrow' => 'Inherited Eyebrow',
                'title' => 'Inherited About Body Title',
                'intro' => 'Inherited intro for about page.',
                'vision_title' => 'Our Vision',
                'vision_text' => 'Vision text',
                'mission_title' => 'Our Mission',
                'mission_text' => 'Mission text',
                'features_text' => "Worship\nPrayer",
            ],
        ])
        ->assertRedirect();

    expect(\Illuminate\Support\Facades\DB::table('ag_site_content')->where('section_key', 'about_page')->exists())->toBeFalse();

    $about = $this->get(route('public.about'));
    $about->assertOk();
    $about->assertSee('Inherited About Body Title');
    $about->assertSee('Inherited intro for about page.');
    $about->assertDontSee('about-child.jpg', false);
    $about->assertSee('site/images/rev1.jpg', false);
});

test('about page renders cms about_page content', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $this->actingAs($admin, 'admin')
        ->put(route('website.about.update'), [
            'section' => 'about_page',
            'content' => [
                'hero' => [
                    'title' => 'About CMS Title',
                    'breadcrumb_home_label' => 'Home',
                    'breadcrumb_current' => 'About',
                ],
                'eyebrow' => 'About CMS',
                'title' => 'About Page Body Title From CMS',
                'intro' => 'About page intro from CMS editor.',
                'vision_title' => 'Our Vision',
                'vision_text' => 'Vision text',
                'mission_title' => 'Our Mission',
                'mission_text' => 'Mission text',
                'features_text' => "Worship\nPrayer",
            ],
        ])
        ->assertRedirect();

    $about = $this->get(route('public.about'));
    $about->assertOk();
    $about->assertSee('About CMS Title');
    $about->assertSee('About Page Body Title From CMS');
    $about->assertSee('About page intro from CMS editor.');
});

test('published blog posts appear on public blog routes', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $this->actingAs($admin, 'admin')->post(route('website.blog.store'), [
        'title' => 'Public Blog CMS Post',
        'category' => 'devotionals',
        'excerpt' => 'A public excerpt.',
        'body_html' => '<p>Public body content.</p>',
        'is_published' => 1,
    ])->assertRedirect();

    \Illuminate\Support\Facades\DB::table('ag_blog_posts')
        ->where('slug', 'public-blog-cms-post')
        ->update([
            'is_published' => 1,
            'published_at' => now(),
        ]);

    $index = $this->get(route('public.blog'));
    $index->assertOk();
    $index->assertSee('Public Blog CMS Post');

    $show = $this->get(route('public.blog.show', 'public-blog-cms-post'));
    $show->assertOk();
    $show->assertSee('Public Blog CMS Post');
    $show->assertSee('Public body content.', false);
});
