<?php

use App\Models\Admin;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

test('admin can view members directory', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    Member::factory()->create(['full_name' => 'Directory Member']);

    $response = $this->actingAs($admin, 'admin')->get(route('members.index'));

    $response->assertOk();
    $response->assertSee('Directory Member');
    $response->assertSee('Members Directory');
    $response->assertSee('By ministry');
    $response->assertSee('Children Ministry');
    $response->assertSee('Grand total');
});

test('members directory shows ministry totals from member departments', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);

    Member::factory()->create([
        'full_name' => 'Child One',
        'department' => 'Children Ministry',
        'status' => 'active',
    ]);
    Member::factory()->create([
        'full_name' => 'Child Two',
        'department' => 'Children',
        'status' => 'full_member',
    ]);
    Member::factory()->create([
        'full_name' => 'Man One',
        'department' => "Men's Ministry",
        'status' => 'active',
    ]);
    Member::factory()->create([
        'full_name' => 'Generic Member',
        'department' => 'Member',
        'status' => 'active',
    ]);
    Member::factory()->create([
        'full_name' => 'Deceased Man',
        'department' => "Men's Ministry",
        'status' => 'deceased',
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('members.index'));

    $response->assertOk();
    $response->assertViewHas('ministryGrandTotal', 3);
    $response->assertViewHas('ministryTotals', function (array $ministries): bool {
        $byKey = collect($ministries)->keyBy('key');

        return (int) ($byKey['children']['total'] ?? 0) === 2
            && (int) ($byKey['men']['total'] ?? 0) === 1;
    });
});

test('members directory falls back to roster totals when department counts are empty', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);

    DB::table('ministry_roster_people')->insert([
        [
            'ministry_key' => 'children',
            'person_code' => 'MR-CHI-00001',
            'full_name' => 'Child One',
            'status' => 'active',
            'joined_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'ministry_key' => 'men',
            'person_code' => 'MR-MEN-00001',
            'full_name' => 'Man One',
            'status' => 'active',
            'joined_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('members.index'));

    $response->assertOk();
    $response->assertViewHas('ministryGrandTotal', 2);
    $response->assertViewHas('ministryTotals', function (array $ministries): bool {
        $byKey = collect($ministries)->keyBy('key');

        return (int) ($byKey['children']['total'] ?? 0) === 1
            && (int) ($byKey['men']['total'] ?? 0) === 1;
    });
});


test('admin can register a member with status history', function () {
    $admin = Admin::factory()->create(['role' => 'super_admin']);

    $response = $this->actingAs($admin, 'admin')->post(route('members.store'), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'phone' => '08012345678',
        'address_line1' => '12 Church Road',
        'city' => 'Owerri',
        'state' => 'Imo',
        'department' => 'Member',
        'status' => 'active',
        'joined_date' => '2026-07-20',
    ]);

    $response->assertRedirect(route('members.index'));
    $this->assertDatabaseHas('members', [
        'full_name' => 'Jane Doe',
        'member_code' => 'AGCI-00001',
        'phone' => '08012345678',
        'department' => 'Member',
    ]);
    $this->assertDatabaseHas('member_status_history', [
        'new_status' => 'active',
        'status_reason' => 'Initial member registration',
        'change_type' => 'system',
        'changed_by' => $admin->id,
    ]);
});

test('admin can update member and log status change', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $member = Member::factory()->create([
        'member_code' => 'AGCI-00010',
        'status' => 'active',
        'phone' => '08099998888',
        'address_line1' => '1 Old Street',
        'city' => 'Owerri',
        'state' => 'Imo',
        'department' => 'Member',
    ]);

    $response = $this->actingAs($admin, 'admin')->put(route('members.update', $member), [
        'full_name' => 'Updated Name',
        'phone' => '08099998888',
        'address_line1' => '1 Old Street',
        'city' => 'Owerri',
        'state' => 'Imo',
        'department' => 'Youth Ministry',
        'status' => 'full_member',
        'joined_date' => $member->joined_date->format('Y-m-d'),
    ]);

    $response->assertRedirect(route('members.show', $member));
    $this->assertDatabaseHas('members', [
        'id' => $member->id,
        'full_name' => 'Updated Name',
        'status' => 'full_member',
        'department' => 'Youth Ministry',
    ]);
    $this->assertDatabaseHas('member_status_history', [
        'member_id' => $member->id,
        'previous_status' => 'active',
        'new_status' => 'full_member',
        'change_type' => 'manual',
    ]);
});

test('ss teacher cannot access members module', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);

    $this->actingAs($admin, 'admin')->get(route('members.index'))->assertForbidden();
});
