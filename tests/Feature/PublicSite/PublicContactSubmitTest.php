<?php

use Illuminate\Support\Facades\DB;

test('public contact form submits through laravel api', function () {
    $this->mock(\App\Services\CommunicationHub\HubMailConfigurator::class, function ($mock) {
        $mock->shouldReceive('sendHtml')->once();
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
    expect($row->ack_sent_at)->not->toBeNull();
});

test('public contact form rejects honeypot spam', function () {
    $this->postJson(route('public.contact.submit'), [
        'action' => 'submit',
        'name' => 'Bot',
        'email' => 'bot@example.com',
        'subject' => 'Hello there',
        'message' => 'This is a spam message body.',
        'ag_hp_trap' => 'filled',
    ])->assertStatus(422)->assertJsonPath('success', false);
});
