<?php

use App\Models\Admin;
use Illuminate\Support\Facades\DB;

test('authenticated admin can view site testimonies index', function () {
    $admin = Admin::factory()->create(['role' => 'content_editor']);

    DB::table('site_testimonies')->insert([
        'full_name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'testimony_text' => 'God has been faithful in my life and family.',
        'source_page' => 'index',
        'status' => 'pending',
        'created_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('testimonies.index'))
        ->assertOk()
        ->assertSee('Site Testimonies')
        ->assertSee('Jane Doe');
});

test('authenticated admin can approve a site testimony', function () {
    $admin = Admin::factory()->create(['role' => 'church_administrator']);

    $id = (int) DB::table('site_testimonies')->insertGetId([
        'full_name' => 'John Smith',
        'email' => 'john@example.com',
        'testimony_text' => 'The Lord answered my prayers in a mighty way.',
        'source_page' => 'about',
        'status' => 'pending',
        'created_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->put(route('testimonies.update', $id), [
            'status' => 'approved',
            'is_featured' => '1',
        ])
        ->assertRedirect();

    $row = DB::table('site_testimonies')->where('id', $id)->first();
    expect($row->status)->toBe('approved');
    expect((int) $row->is_featured)->toBe(1);
});
