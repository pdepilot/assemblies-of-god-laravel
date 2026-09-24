<?php

use App\Models\Admin;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

test('media administrator can view sermons dashboard', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'media_administrator']);

    $response = $this->actingAs($admin, 'admin')->get(route('sermon.dashboard'));

    $response->assertOk();
    $response->assertSee('Sermons');
    $response->assertSee('Broadcasts');
});

test('media administrator can create sermon', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'media_administrator']);

    $this->actingAs($admin, 'admin')
        ->get(route('sermon.sermons.create'))
        ->assertOk()
        ->assertSee('Cover image')
        ->assertSee('Sermon video')
        ->assertSee('15 MB');

    $this->actingAs($admin, 'admin')->post(route('sermon.sermons.store'), [
        'title' => 'Faith That Moves Mountains',
        'sermon_date' => '2026-07-20',
        'minister_name' => 'Rev. Bethel Nwanebu',
        'sermon_type' => 'audio',
        'status' => 'draft',
        'featured_image' => UploadedFile::fake()->image('cover.jpg', 400, 225),
        'video_file' => UploadedFile::fake()->create('message.mp4', 512, 'video/mp4'),
    ])->assertRedirect();

    $this->assertDatabaseHas('sermons', [
        'title' => 'Faith That Moves Mountains',
        'minister_name' => 'Rev. Bethel Nwanebu',
        'status' => 'draft',
        'sermon_type' => 'video',
    ]);

    $sermon = DB::table('sermons')->where('title', 'Faith That Moves Mountains')->first();
    expect($sermon)->not->toBeNull();
    expect((string) $sermon->featured_image)->toStartWith('uploads/sermons/images/');
    expect((string) $sermon->video_file_path)->toStartWith('uploads/sermons/videos/');
    expect(is_file(public_path('site/'.$sermon->featured_image)))->toBeTrue();
    expect(is_file(public_path('site/'.$sermon->video_file_path)))->toBeTrue();
});

test('sermon video larger than 15mb is rejected', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'media_administrator']);

    $this->actingAs($admin, 'admin')->post(route('sermon.sermons.store'), [
        'title' => 'Too Large Video',
        'sermon_date' => '2026-07-20',
        'sermon_type' => 'video',
        'status' => 'draft',
        'video_file' => UploadedFile::fake()->create('huge.mp4', 16000, 'video/mp4'),
    ])->assertSessionHasErrors('video_file');
});

test('ss teacher cannot access sermons module', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);

    $this->actingAs($admin, 'admin')->get(route('sermon.dashboard'))->assertForbidden();
});
