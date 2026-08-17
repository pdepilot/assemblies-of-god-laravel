<?php

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

function fakeRiversIpLookup(): void
{
    Http::fake([
        'ip-api.com/*' => Http::response([
            'status' => 'success',
            'country' => 'Nigeria',
            'regionName' => 'Rivers',
            'city' => 'Port Harcourt',
            'lat' => 4.8156,
            'lon' => 7.0498,
            'query' => '102.89.1.1',
        ], 200),
    ]);
}

function postNewsletter(array $payload, string $ip = '102.89.1.1'): \Illuminate\Testing\TestResponse
{
    return test()->call(
        'POST',
        route('public.newsletter.subscribe'),
        [],
        [],
        [],
        [
            'REMOTE_ADDR' => $ip,
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_X_CSRF_TOKEN' => csrf_token(),
        ],
        json_encode($payload)
    );
}

test('newsletter subscription succeeds with invalid email rejected', function () {
    $this->postJson(route('public.newsletter.subscribe'), [
        'email' => 'not-an-email',
    ])->assertStatus(422);
});

test('newsletter duplicate active email returns friendly message', function () {
    DB::table('site_newsletter_subscribers')->insert([
        'email' => 'active@example.com',
        'source' => 'footer',
        'status' => 'active',
        'subscribed_at' => now(),
        'updated_at' => now(),
    ]);

    $this->postJson(route('public.newsletter.subscribe'), [
        'email' => 'active@example.com',
    ])
        ->assertOk()
        ->assertJsonPath('message', 'You are already subscribed. Thank you!');
});

test('newsletter ip geolocation failure does not block subscription', function () {
    Http::fake([
        'ip-api.com/*' => Http::response([], 500),
    ]);

    postNewsletter([
        'email' => 'noloc@example.com',
        'source' => 'footer',
    ])->assertOk()->assertJson(['success' => true]);

    $this->assertDatabaseHas('site_newsletter_subscribers', [
        'email' => 'noloc@example.com',
        'status' => 'active',
    ]);
});

test('posted country is ignored and ip location is stored automatically', function () {
    fakeRiversIpLookup();

    postNewsletter([
        'email' => 'auto-detect@example.com',
        'country' => 'Nigeria',
        'region' => 'Imo',
        'source' => 'footer',
    ])->assertOk();

    $this->assertDatabaseHas('site_newsletter_subscribers', [
        'email' => 'auto-detect@example.com',
        'region' => 'Rivers',
        'city' => 'Port Harcourt',
        'country' => 'Nigeria',
        'location_source' => 'ip',
        'location_accuracy' => 'estimated',
    ]);
});

test('ip derived location is stored as estimated', function () {
    fakeRiversIpLookup();

    postNewsletter([
        'email' => 'ip-estimate@example.com',
        'source' => 'footer',
    ])->assertOk();

    $this->assertDatabaseHas('site_newsletter_subscribers', [
        'email' => 'ip-estimate@example.com',
        'region' => 'Rivers',
        'location_source' => 'ip',
        'location_accuracy' => 'estimated',
    ]);
});

test('browser coordinates posted by the client are ignored in favour of automatic ip lookup', function () {
    fakeRiversIpLookup();

    postNewsletter([
        'email' => 'browser-ignored@example.com',
        'latitude' => 5.4833,
        'longitude' => 7.0333,
        'browser_location_consent' => true,
    ])->assertOk();

    $this->assertDatabaseHas('site_newsletter_subscribers', [
        'email' => 'browser-ignored@example.com',
        'region' => 'Rivers',
        'city' => 'Port Harcourt',
        'location_source' => 'ip',
        'location_accuracy' => 'estimated',
    ]);
});

test('automatic ip lookup stores country city and region for the subscriber', function () {
    fakeRiversIpLookup();

    postNewsletter([
        'email' => 'rivers-auto@example.com',
    ])->assertOk();

    $this->assertDatabaseHas('site_newsletter_subscribers', [
        'email' => 'rivers-auto@example.com',
        'country' => 'Nigeria',
        'region' => 'Rivers',
        'city' => 'Port Harcourt',
        'location_source' => 'ip',
        'location_accuracy' => 'estimated',
    ]);
});

test('admin subscriber detail distinguishes confirmed versus estimated location', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    $confirmedId = DB::table('site_newsletter_subscribers')->insertGetId([
        'email' => 'admin-confirmed@example.com',
        'source' => 'footer',
        'status' => 'active',
        'country' => 'Nigeria',
        'region' => 'Imo',
        'location_source' => 'user',
        'location_accuracy' => 'confirmed',
        'location_updated_at' => now(),
        'subscribed_at' => now(),
        'updated_at' => now(),
    ]);

    $estimatedId = DB::table('site_newsletter_subscribers')->insertGetId([
        'email' => 'admin-estimate@example.com',
        'source' => 'footer',
        'status' => 'active',
        'country' => 'Nigeria',
        'region' => 'Rivers',
        'location_source' => 'ip',
        'location_accuracy' => 'estimated',
        'location_updated_at' => now(),
        'subscribed_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('newsletter-subscribers.show', $confirmedId))
        ->assertOk()
        ->assertSee('Imo', false)
        ->assertSee('Subscriber confirmed', false);

    $this->actingAs($admin, 'admin')
        ->get(route('newsletter-subscribers.show', $estimatedId))
        ->assertOk()
        ->assertSee('Rivers', false)
        ->assertSee('IP estimate', false);
});

test('homepage loads ga4 bootstrap once when measurement id is configured', function () {
    config(['identity.public.google_analytics_id' => 'G-TESTMEASURE1']);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('ag-google-analytics-id', false);
    $response->assertSee('G-TESTMEASURE1', false);
    $response->assertSee('ga4.js', false);
    expect(substr_count($response->getContent(), 'ga4.js'))->toBe(1);
});

test('public 404 page renders without adsense bootstrap', function () {
    config([
        'identity.public.adsense_enabled' => true,
        'identity.public.adsense_client_id' => 'ca-pub-4828740366189357',
    ]);

    $response = $this->get('/this-page-does-not-exist-xyz');

    $response->assertNotFound();
    $response->assertSee('This page could not be found', false);
    $response->assertDontSee('google-adsense-account', false);
});

test('footer includes disclaimer about and contact trust links', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee(route('public.disclaimer'), false);
    $response->assertSee(route('public.about'), false);
    $response->assertSee(route('public.contact'), false);
});

test('privacy policy explains newsletter location and google analytics', function () {
    $response = $this->get(route('public.privacy'));

    $response->assertOk();
    $response->assertSee('Newsletter location', false);
    $response->assertSee('Google Analytics 4', false);
    $response->assertSee('automatically estimate', false);
});

test('newsletter form does not ask subscribers for country or city', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('id="agFooterNewsletter"', false);
    $response->assertDontSee('agNewsletterCountry', false);
    $response->assertDontSee('agNewsletterRegion', false);
    $response->assertDontSee('Country (optional)', false);
});
