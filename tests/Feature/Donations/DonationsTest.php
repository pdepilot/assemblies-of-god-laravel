<?php

use App\Models\Admin;
use App\Models\Donation;
use Illuminate\Support\Facades\DB;

test('finance admin can view donations directory', function () {
    $admin = Admin::factory()->create(['role' => 'finance']);
    Donation::factory()->create(['donor_name' => 'Jane Doe', 'amount' => 5000]);

    $response = $this->actingAs($admin, 'admin')->get(route('donations.index'));

    $response->assertOk();
    $response->assertSee('Donations');
    $response->assertSee('Jane Doe');
});

test('finance admin can record a manual donation', function () {
    $admin = Admin::factory()->create(['role' => 'finance']);
    $categoryId = DB::table('donation_categories')->where('slug', 'offering')->value('id');

    $response = $this->actingAs($admin, 'admin')->post(route('donations.store'), [
        'donor_name' => 'John Smith',
        'amount' => 10000,
        'category_id' => $categoryId,
        'payment_method' => 'cash',
        'donation_date' => '2026-07-20',
    ]);

    $response->assertRedirect(route('donations.index'));
    $this->assertDatabaseHas('donations', [
        'donor_name' => 'John Smith',
        'amount' => 10000,
        'payment_provider' => 'manual',
        'payment_status' => 'successful',
    ]);
});

test('ss teacher cannot access donations module', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);

    $this->actingAs($admin, 'admin')->get(route('donations.index'))->assertForbidden();
});
