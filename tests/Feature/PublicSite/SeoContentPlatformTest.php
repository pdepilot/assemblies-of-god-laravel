<?php

use Illuminate\Support\Facades\DB;

test('robots.txt disallows private areas and includes sitemap', function () {
    $response = $this->get('/robots.txt');

    $response->assertOk();
    $response->assertSee('User-agent: Mediapartners-Google', false);
    $response->assertSee('User-agent: Google-Display-Ads-Bot', false);
    $response->assertSee('Disallow: /admin', false);
    $response->assertSee('Disallow: /member-portal', false);
    $response->assertSee('Disallow: /api/', false);
    $response->assertSee('Sitemap: '.url('/sitemap.xml'), false);
});

test('ads.txt declares the google publisher account', function () {
    config(['identity.public.adsense_client_id' => 'ca-pub-4828740366189357']);

    $response = $this->get('/ads.txt');

    $response->assertOk();
    $response->assertSee('google.com, pub-4828740366189357, DIRECT, f08c47fec0942fa0', false);
});

test('sitemap index and child sitemaps respond', function () {
    $this->get('/sitemap.xml')->assertOk()->assertSee('sitemap-pages.xml', false);
    $this->get('/sitemap-pages.xml')->assertOk()->assertSee(url('/'), false);
    $this->get('/sitemap-blog.xml')->assertOk();
    $this->get('/sitemap-sermons.xml')->assertOk();
    $this->get('/sitemap-images.xml')->assertOk();
    $this->get('/sitemap')->assertOk()->assertSee('Sitemap', false);
});

test('rss feed lists published blog posts', function () {
    DB::table('ag_blog_posts')->insert([
        'title' => 'RSS Sample Post',
        'slug' => 'rss-sample-post',
        'category' => 'church-news',
        'excerpt' => 'Excerpt for RSS',
        'body_html' => '<p>Body for RSS.</p>',
        'is_published' => 1,
        'published_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->get('/feed');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
    $response->assertSee('RSS Sample Post', false);
    $response->assertSee(route('public.blog.show', 'rss-sample-post'), false);
});

test('blog show includes meta description and json-ld article schema', function () {
    DB::table('ag_blog_posts')->insert([
        'title' => 'SEO Blog Post',
        'slug' => 'seo-blog-post',
        'category' => 'devotionals',
        'excerpt' => 'A short excerpt',
        'body_html' => '<h2>First heading</h2><p>Content here.</p>',
        'meta_description' => 'Custom blog meta description for SEO.',
        'seo_title' => 'Custom SEO Title',
        'tags' => json_encode(['faith', 'prayer']),
        'reading_time_minutes' => 3,
        'is_published' => 1,
        'published_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->get(route('public.blog.show', 'seo-blog-post'));

    $response->assertOk();
    $response->assertSee('Custom blog meta description for SEO.', false);
    $response->assertSee('application/ld+json', false);
    $response->assertSee('"@type":"Article"', false);
    $response->assertSee(route('public.blog.tag', 'faith'), false);
});

test('blog category and tag routes respond', function () {
    DB::table('ag_blog_posts')->insert([
        'title' => 'Tagged Post',
        'slug' => 'tagged-post',
        'category' => 'youth',
        'excerpt' => 'Youth excerpt',
        'body_html' => '<p>Youth body</p>',
        'tags' => json_encode(['youth-ministry']),
        'is_published' => 1,
        'published_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->get(route('public.blog.category', 'youth'))->assertOk()->assertSee('Tagged Post');
    $this->get(route('public.blog.tag', 'youth-ministry'))->assertOk()->assertSee('Tagged Post');
});

test('public sermon show uses seo fields and schema', function () {
    DB::table('sermons')->insert([
        'sermon_code' => 'SRM-TESTSEO1',
        'title' => 'Public SEO Sermon',
        'slug' => 'public-seo-sermon',
        'description' => 'Sermon description',
        'content_html' => '<p>Sermon body</p>',
        'sermon_date' => now()->toDateString(),
        'minister_name' => 'Rev. Test',
        'status' => 'published',
        'published_at' => now(),
        'seo_title' => 'SEO Sermon Title',
        'seo_description' => 'SEO sermon meta description.',
        'tags' => json_encode(['hope']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->get(route('public.sermons.show', 'public-seo-sermon'));

    $response->assertOk();
    $response->assertSee('Public SEO Sermon');
    $response->assertSee('SEO sermon meta description.', false);
    $response->assertSee('application/ld+json', false);
});

test('cms legal and leadership pages respond', function () {
    foreach ([
        'public.privacy',
        'public.terms',
        'public.cookie-policy',
        'public.statement-of-faith',
        'public.mission-vision',
        'public.editorial-policy',
        'public.accessibility',
        'public.disclaimer',
        'public.faq',
        'public.leadership',
    ] as $route) {
        $this->get(route($route))->assertOk();
    }
});

test('privacy and cookie policy disclose google adsense cookies and opt-out', function () {
    $privacy = $this->get(route('public.privacy'));
    $privacy->assertOk();
    $privacy->assertSee('Google Ads Settings', false);
    $privacy->assertSee('www.aboutads.info', false);
    $privacy->assertSee('policies.google.com/technologies/partner-sites', false);
    $privacy->assertSee('not directed at children under 13', false);

    $cookies = $this->get(route('public.cookie-policy'));
    $cookies->assertOk();
    $cookies->assertSee('Google AdSense', false);
    $cookies->assertSee('Google Ads Settings', false);
    $cookies->assertSee('Cookie settings', false);
});

test('newsletter subscribe stores subscriber', function () {
    $response = $this->postJson(route('public.newsletter.subscribe'), [
        'email' => 'subscriber@example.com',
        'source' => 'footer',
        'website' => '',
    ]);

    $response->assertOk()->assertJson(['success' => true]);
    $this->assertDatabaseHas('site_newsletter_subscribers', [
        'email' => 'subscriber@example.com',
    ]);
});

test('newsletter honeypot is ignored quietly', function () {
    $response = $this->postJson(route('public.newsletter.subscribe'), [
        'email' => 'bot@example.com',
        'website' => 'http://spam.test',
    ]);

    $response->assertOk()->assertJson(['success' => true]);
    $this->assertDatabaseMissing('site_newsletter_subscribers', [
        'email' => 'bot@example.com',
    ]);
});

test('donate and member-portal responses have no ad placeholders', function () {
    config([
        'identity.public.adsense_enabled' => true,
        'identity.public.adsense_client_id' => 'ca-pub-4828740366189357',
    ]);

    foreach ([
        route('public.donate'),
        route('public.member-portal.login'),
        route('public.privacy'),
        route('public.terms'),
        route('public.cookie-policy'),
        route('public.disclaimer'),
        route('public.accessibility'),
        route('public.sitemap'),
    ] as $url) {
        $response = $this->get($url);
        $response->assertOk();
        $response->assertDontSee('adsbygoogle', false);
        $response->assertDontSee('data-ad-client', false);
        $response->assertDontSee('ad-slot', false);
        $response->assertDontSee('google-adsense-account', false);
    }
});

test('homepage includes organization schema', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('application/ld+json', false);
    $response->assertSee('"@type":"Organization"', false);
});

test('homepage can load adsense bootstrap when enabled', function () {
    config([
        'identity.public.adsense_enabled' => true,
        'identity.public.adsense_client_id' => 'ca-pub-4828740366189357',
    ]);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('google-adsense-account', false);
    $response->assertSee('ca-pub-4828740366189357', false);
    $response->assertSee('adsense.js', false);
});
