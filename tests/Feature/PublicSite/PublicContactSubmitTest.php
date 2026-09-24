<?php

use Illuminate\Support\Facades\DB;

test('public contact form submits through laravel api', function () {
    /** @var \Tests\TestCase $this */
    $this->mock(\App\Services\CommunicationHub\HubMailConfigurator::class, function ($mock) {
        $mock->shouldReceive('sendHtml')->zeroOrMoreTimes();
    });

    $this->postJson(route('public.contact.submit'), [
        'action' => 'submit',
        'inquiry_type' => 'prayer',
        'name' => 'Ada Okoro',
        'email' => 'ada@example.com',
        'phone' => '08012345678',
        'subject' => 'Need prayer',
        'message' => 'Please pray for my family this week.',
        'ag_hp_trap' => '',
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['reference', 'inquiry_type']);

    $this->assertDatabaseHas('contact_submissions', [
        'full_name' => 'Ada Okoro',
        'email' => 'ada@example.com',
        'inquiry_type' => 'prayer',
        'status' => 'new',
    ]);

    $row = DB::table('contact_submissions')->where('email', 'ada@example.com')->first();
    expect($row->submission_code)->toStartWith('CNT-');
});

test('public contact page shows redesigned visit panel and form', function () {
    /** @var \Tests\TestCase $this */
    $this->get(route('public.contact'))
        ->assertOk()
        ->assertSee('Send a message', false)
        ->assertSee('contact-form', false)
        ->assertSee('Prayer request', false)
        ->assertSee('Plan a visit', false)
        ->assertSee('Get directions', false)
        ->assertSee('contactSuccessPopup', false)
        ->assertSee('Message sent', false)
        ->assertSee('contact-form.js', false);
});

test('public contact form rejects honeypot spam', function () {
    /** @var \Tests\TestCase $this */
    $this->postJson(route('public.contact.submit'), [
        'action' => 'submit',
        'name' => 'Bot',
        'email' => 'bot@example.com',
        'subject' => 'Hello there',
        'message' => 'This is a spam message body.',
        'ag_hp_trap' => 'filled',
    ])->assertStatus(422)->assertJsonPath('success', false);
});
