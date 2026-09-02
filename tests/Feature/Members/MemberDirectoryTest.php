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

test('admin can view deceased dashboard with memorial details', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);

    Member::factory()->create([
        'full_name' => 'Living Member',
        'status' => 'active',
    ]);
    Member::factory()->create([
        'full_name' => 'Remembered Saint',
        'department' => "Men's Ministry",
        'status' => 'deceased',
        'date_of_death' => '2026-03-15',
        'death_notes' => 'Burial at Ikenegbu cemetery. Family thanksgiving held.',
        'joined_date' => '2010-01-01',
    ]);
    Member::factory()->create([
        'full_name' => 'No Date Record',
        'status' => 'deceased',
        'date_of_death' => null,
        'death_notes' => null,
        'joined_date' => '2012-05-01',
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('members.deceased'));

    $response->assertOk();
    $response->assertSee('Deceased Dashboard');
    $response->assertSee('Remembered Saint');
    $response->assertSee('15 Mar 2026');
    $response->assertSee('Burial at Ikenegbu cemetery');
    $response->assertSee('No Date Record');
    $response->assertDontSee('Living Member');
    $response->assertViewHas('stats', function (array $stats): bool {
        return (int) ($stats['total'] ?? 0) === 2
            && (int) ($stats['with_memorial_notes'] ?? 0) === 1
            && (int) ($stats['missing_date_of_death'] ?? 0) === 1;
    });
});

test('deceased dashboard search finds memorial notes', function () {
    $admin = Admin::factory()->create(['role' => 'church_administrator']);

    Member::factory()->create([
        'full_name' => 'Alpha Deceased',
        'status' => 'deceased',
        'date_of_death' => '2025-11-01',
        'death_notes' => 'Service at main sanctuary',
        'joined_date' => '2008-01-01',
    ]);
    Member::factory()->create([
        'full_name' => 'Beta Deceased',
        'status' => 'deceased',
        'date_of_death' => '2025-12-01',
        'death_notes' => 'Home going celebration',
        'joined_date' => '2009-01-01',
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('members.deceased', [
        'q' => 'sanctuary',
    ]));

    $response->assertOk();
    $response->assertSee('Alpha Deceased');
    $response->assertDontSee('Beta Deceased');
});

test('ss teacher cannot access deceased dashboard', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);

    $this->actingAs($admin, 'admin')->get(route('members.deceased'))->assertForbidden();
});

test('admin can record an existing member as deceased from the directory', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $member = Member::factory()->create([
        'full_name' => 'Living Elder',
        'status' => 'full_member',
        'joined_date' => '2015-01-01',
    ]);

    $create = $this->actingAs($admin, 'admin')->get(route('members.deceased.record'));
    $create->assertOk();
    $create->assertSee('Living Elder');
    $create->assertSee('Select someone already in the church members list');

    $response = $this->actingAs($admin, 'admin')->post(route('members.deceased.store'), [
        'member_id' => $member->id,
        'date_of_death' => '2026-04-10',
        'death_notes' => 'Funeral thanksgiving at AGCI.',
    ]);

    $response->assertRedirect(route('members.deceased'));
    $member->refresh();
    expect($member->status)->toBe('deceased')
        ->and($member->date_of_death?->toDateString())->toBe('2026-04-10')
        ->and($member->death_notes)->toBe('Funeral thanksgiving at AGCI.');
    $this->assertDatabaseHas('member_status_history', [
        'member_id' => $member->id,
        'previous_status' => 'full_member',
        'new_status' => 'deceased',
        'change_type' => 'manual',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('members.deceased'))
        ->assertSee('Living Elder')
        ->assertSee('Funeral thanksgiving at AGCI.');
});

test('cannot record an already deceased member again', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $member = Member::factory()->create([
        'full_name' => 'Already Gone',
        'status' => 'deceased',
        'date_of_death' => '2020-01-01',
        'joined_date' => '2010-01-01',
    ]);

    $response = $this->actingAs($admin, 'admin')->from(route('members.deceased.record'))->post(route('members.deceased.store'), [
        'member_id' => $member->id,
        'date_of_death' => '2026-04-10',
        'death_notes' => 'Should fail',
    ]);

    $response->assertSessionHasErrors('member_id');
    $member->refresh();
    expect($member->date_of_death?->toDateString())->toBe('2020-01-01')
        ->and($member->death_notes)->toBeNull();
});

test('ss teacher cannot record deceased members', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);
    $member = Member::factory()->create(['status' => 'active']);

    $this->actingAs($admin, 'admin')->get(route('members.deceased.record'))->assertForbidden();
    $this->actingAs($admin, 'admin')->post(route('members.deceased.store'), [
        'member_id' => $member->id,
        'date_of_death' => '2026-04-10',
    ])->assertForbidden();
});

test('admin can generate a death certificate pdf for a deceased member', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $member = Member::factory()->create([
        'full_name' => 'Certificate Elder',
        'member_code' => 'AGCI-12345',
        'status' => 'deceased',
        'date_of_death' => '2026-02-14',
        'death_notes' => 'Home going service held.',
        'joined_date' => '2005-06-01',
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('members.death-certificate', $member));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('inline');
    expect(strlen((string) $response->getContent()))->toBeGreaterThan(500);
    expect(substr((string) $response->getContent(), 0, 4))->toBe('%PDF');
});

test('admin can download a death certificate pdf', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $member = Member::factory()->create([
        'full_name' => 'Download Elder',
        'status' => 'deceased',
        'date_of_death' => '2026-01-01',
        'joined_date' => '2010-01-01',
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('members.death-certificate', [
        $member,
        'download' => 1,
    ]));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('attachment');
});

test('death certificate is not available for living members', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $member = Member::factory()->create([
        'full_name' => 'Still Living',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'admin')
        ->from(route('members.show', $member))
        ->get(route('members.death-certificate', $member));

    $response->assertRedirect(route('members.show', $member));
    $response->assertSessionHasErrors('certificate');
});

test('ss teacher cannot generate death certificates', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);
    $member = Member::factory()->create([
        'status' => 'deceased',
        'date_of_death' => '2026-01-01',
        'joined_date' => '2010-01-01',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('members.death-certificate', $member))
        ->assertForbidden();
});

test('member profile shows death details and certificate links when deceased', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $member = Member::factory()->create([
        'full_name' => 'Profile Deceased',
        'status' => 'deceased',
        'date_of_death' => '2024-08-20',
        'death_notes' => 'Laid to rest beside the church garden.',
        'joined_date' => '2001-03-01',
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('members.show', $member));

    $response->assertOk();
    $response->assertSee('Date of death');
    $response->assertSee('20 Aug 2024');
    $response->assertSee('Memorial notes');
    $response->assertSee('Laid to rest beside the church garden.');
    $response->assertSee('Death certificate');
});
