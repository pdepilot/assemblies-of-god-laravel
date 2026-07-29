<?php

use App\Models\Admin;
use App\Models\Member;
use App\Models\Visitor;

test('admin can view church visitors directory', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    Visitor::factory()->create(['full_name' => 'Pipeline Visitor']);

    $response = $this->actingAs($admin, 'admin')->get(route('visitors.index'));

    $response->assertOk();
    $response->assertSee('Pipeline Visitor');
    $response->assertSee('Church Visitors');
});

test('admin can register a church visitor', function () {
    $admin = Admin::factory()->create(['role' => 'church_administrator']);

    $response = $this->actingAs($admin, 'admin')->post(route('visitors.store'), [
        'full_name' => 'New Guest',
        'phone' => '08012345678',
        'first_visit_date' => '2026-07-20',
        'service_attended' => 'Sunday First Service',
    ]);

    $response->assertRedirect(route('visitors.index'));
    $this->assertDatabaseHas('visitors', [
        'full_name' => 'New Guest',
        'visitor_code' => 'V001',
        'follow_up_status' => 'new',
    ]);
});

test('admin can record visitor return visit and bump status to contacted', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $visitor = Visitor::factory()->create([
        'follow_up_status' => 'new',
        'visit_count' => 1,
    ]);

    $response = $this->actingAs($admin, 'admin')->post(route('visitors.record-return', $visitor), [
        'visit_date' => '2026-07-21',
    ]);

    $response->assertRedirect(route('visitors.show', $visitor));
    $this->assertDatabaseHas('visitors', [
        'id' => $visitor->id,
        'visit_count' => 2,
        'follow_up_status' => 'contacted',
    ]);
});

test('admin can promote visitor to full member', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $visitor = Visitor::factory()->create([
        'visitor_code' => 'V010',
        'full_name' => 'Promote Me',
        'phone' => '08011112222',
        'follow_up_status' => 'ready',
        'address_line1' => '1 Visitor Street',
        'city' => 'Owerri',
        'state' => 'Imo',
    ]);

    $response = $this->actingAs($admin, 'admin')->post(route('visitors.promote', $visitor), [
        'confirm_membership' => '1',
        'joined_date' => '2026-07-20',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('visitors', [
        'id' => $visitor->id,
        'follow_up_status' => 'promoted',
    ]);
    $this->assertDatabaseHas('members', [
        'full_name' => 'Promote Me',
        'status' => 'full_member',
    ]);
});

test('follow up officer can view visitors but not delete', function () {
    $admin = Admin::factory()->create(['role' => 'follow_up_officer']);
    $visitor = Visitor::factory()->create();

    $this->actingAs($admin, 'admin')->get(route('visitors.index'))->assertOk();
    $this->actingAs($admin, 'admin')->delete(route('visitors.destroy', $visitor))->assertForbidden();
});

test('ss teacher cannot access church visitors module', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);

    $this->actingAs($admin, 'admin')->get(route('visitors.index'))->assertForbidden();
});
