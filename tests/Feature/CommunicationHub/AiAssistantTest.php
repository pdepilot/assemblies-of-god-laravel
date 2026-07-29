<?php

use App\Models\Admin;
use Illuminate\Support\Facades\DB;

test('authenticated admin can view ai message assistant', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    $this->actingAs($admin, 'admin')
        ->get(route('communication-hub.ai-assistant.index'))
        ->assertOk()
        ->assertSee('AI Message Assistant')
        ->assertSee('Queue AI Job')
        ->assertSee('Generate Birthday Message');
});

test('authenticated admin can queue an ai job', function () {
    $admin = Admin::factory()->create(['role' => 'communications_officer']);

    $this->actingAs($admin, 'admin')
        ->post(route('communication-hub.ai-assistant.store'), [
            'job_type' => 'birthday',
            'prompt' => 'Write a warm birthday greeting for Sister Ada.',
        ])
        ->assertRedirect(route('communication-hub.ai-assistant.index'))
        ->assertSessionHas('status')
        ->assertSessionHas('ai_job');

    $this->assertDatabaseHas('ai_message_jobs', [
        'job_type' => 'birthday',
        'prompt' => 'Write a warm birthday greeting for Sister Ada.',
        'status' => 'pending',
        'provider' => 'future',
        'created_by' => $admin->id,
    ]);

    expect(DB::table('ai_message_jobs')->count())->toBe(1);
});
