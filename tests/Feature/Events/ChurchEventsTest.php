<?php

use App\Models\Admin;
use App\Models\ChurchEvent;

test('admin can view church events directory', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    ChurchEvent::factory()->create(['title' => 'Youth Convention']);

    $response = $this->actingAs($admin, 'admin')->get(route('events.index'));

    $response->assertOk();
    $response->assertSee('Youth Convention');
    $response->assertSee('Church Events');
});

test('admin can create a church event', function () {
    $admin = Admin::factory()->create(['role' => 'church_administrator']);

    $response = $this->actingAs($admin, 'admin')->post(route('events.store'), [
        'title' => 'Midweek Service',
        'event_date' => '2026-08-01',
        'category' => 'service',
        'status' => 'upcoming',
        'is_published' => '1',
        'recurrence_label' => 'Every Wednesday',
    ]);

    $response->assertRedirect(route('events.index'));
    $this->assertDatabaseHas('church_events', [
        'title' => 'Midweek Service',
        'category' => 'service',
        'is_recurring' => 1,
    ]);
});

test('admin can pause and resume event publication', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $event = ChurchEvent::factory()->create(['is_published' => true]);

    $this->actingAs($admin, 'admin')
        ->post(route('events.set-published', $event), ['published' => '0'])
        ->assertRedirect();

    expect(ChurchEvent::query()->find($event->id)?->is_published)->toBeFalse();
});

test('asset resolver routes event uploads through legacy admin base when local file is missing', function () {
    $resolver = app(\App\Services\PublicSite\PublicAssetResolver::class);
    $url = $resolver->uploadUrl('uploads/events/sample-flier.jpg');

    expect($url)->toContain('uploads/events/sample-flier.jpg');
    expect($url)->toContain('/portal/');
});

test('event read service exposes image url for homepage and admin previews', function () {
    ChurchEvent::factory()->create([
        'title' => 'Flier Event',
        'image_path' => 'uploads/events/test-flier.jpg',
    ]);

    $read = app(\App\Services\Events\EventReadService::class);
    $event = $read->getEvent((int) ChurchEvent::query()->where('title', 'Flier Event')->value('id'));

    expect($event)->not->toBeNull();
    expect($event['image_url'])->toContain('uploads/events/test-flier.jpg');
    expect($event['is_custom_image'])->toBeTrue();
});

test('admin can view event create and edit forms', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $event = ChurchEvent::factory()->create([
        'title' => 'Form Event',
        'image_path' => 'uploads/events/test-flier.jpg',
    ]);

    $this->actingAs($admin, 'admin')->get(route('events.create'))
        ->assertOk()
        ->assertSee('Upload image')
        ->assertDontSee('Or use default image');
    $this->actingAs($admin, 'admin')->get(route('events.edit', $event))
        ->assertOk()
        ->assertSee('Form Event')
        ->assertSee('Upload image');
});

test('admin can edit a church event', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $event = ChurchEvent::factory()->create(['title' => 'Original Title']);

    $response = $this->actingAs($admin, 'admin')->put(route('events.update', $event), [
        'title' => 'Updated Title',
        'event_date' => '2026-09-01',
        'category' => 'program',
        'status' => 'upcoming',
        'is_published' => '1',
    ]);

    $response->assertRedirect(route('events.show', $event));
    $this->assertDatabaseHas('church_events', [
        'id' => $event->id,
        'title' => 'Updated Title',
    ]);
});
