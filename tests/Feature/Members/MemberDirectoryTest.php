<?php

use App\Models\Admin;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

test('admin can view members directory', function () {
    /** @var \Tests\TestCase $this */
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

test('members department dropdown does not list duplicate ministry labels', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'admin']);
    Member::factory()->create(['department' => 'Children']);
    Member::factory()->create(['department' => 'Children Ministry']);
    Member::factory()->create(['department' => 'Widows Ministry']);
    Member::factory()->create(['department' => 'Widowers Ministry']);
    Member::factory()->create(['department' => 'Prayer']);
    Member::factory()->create(['department' => 'Prayer Chain']);
    Member::factory()->create(['department' => 'Youth']);

    $departments = app(\App\Services\Members\MemberReadService::class)->listDepartmentOptions();
    $labels = array_map(static fn ($dept) => mb_strtolower((string) $dept), $departments);

    expect($labels)->toHaveCount(count(array_unique($labels)));
    expect($departments)->toContain('Children Ministry');
    expect($departments)->toContain('Widows');
    expect($departments)->toContain('Youth Ministry');
    expect($departments)->not->toContain('Children');
    expect($departments)->not->toContain('Youth');
    expect($departments)->not->toContain('Widows Ministry');
    expect($departments)->not->toContain('Widowers Ministry');
    expect($departments)->not->toContain('Prayer Chain');

    $html = $this->actingAs($admin, 'admin')->get(route('members.create'))->assertOk()->getContent();
    expect(substr_count($html, '>Children Ministry<'))->toBe(1);
    expect(substr_count($html, '>Children<'))->toBe(0);
});

test('join form membership statuses match living add-member options', function () {
    /** @var \Tests\TestCase $this */
    expect(\App\Services\Members\MemberReadService::PUBLIC_JOIN_STATUSES)->toBe([
        'full_member',
        'baptized',
        'visitor',
        'unbaptized',
        'active',
        'worker',
        'inactive',
    ]);

    $admin = Admin::factory()->create(['role' => 'admin']);
    $create = $this->actingAs($admin, 'admin')->get(route('members.create'));
    $create->assertOk();
    $create->assertSee('Status');
    $create->assertSee('Full Member');
    $create->assertSee('Baptized');
    $create->assertSee('Unbaptized');
});

test('parent guardian fields stay hidden on add member until the member is a child or teen', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'admin']);

    $create = $this->actingAs($admin, 'admin')->get(route('members.create'));
    $create->assertOk();
    $create->assertSee('children and teens only');
    expect($create->getContent())->toMatch('/id="parentGuardianFields"[^>]*\bhidden\b/');

    $child = Member::factory()->create([
        'date_of_birth' => now()->subYears(8)->toDateString(),
    ]);
    $editChild = $this->actingAs($admin, 'admin')->get(route('members.edit', $child));
    $editChild->assertOk();
    expect($editChild->getContent())->not->toMatch('/id="parentGuardianFields"[^>]*\bhidden\b/');
});

test('members directory shows ministry totals from member departments', function () {
    /** @var \Tests\TestCase $this */
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
    /** @var \Tests\TestCase $this */
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
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'super_admin']);

    $this->actingAs($admin, 'admin')
        ->get(route('members.create'))
        ->assertOk()
        ->assertSee('Occupation')
        ->assertSee('Take Photo')
        ->assertSee('name="photo"', false)
        ->assertSee('First name', false)
        ->assertSee('Notes')
        ->assertSee('Grant access to the member portal')
        ->assertSee('name="portal_enabled"', false);

    $response = $this->actingAs($admin, 'admin')->post(route('members.store'), memberAdminFormPayload());

    $response->assertRedirect(route('members.index'));
    $this->assertDatabaseHas('members', [
        'full_name' => 'Jane Doe',
        'member_code' => 'AGCI-00001',
        'phone' => '08012345678',
        'department' => 'Member',
        'occupation' => 'Teacher',
        'portal_enabled' => 0,
    ]);
    $this->assertDatabaseHas('member_status_history', [
        'new_status' => 'active',
        'status_reason' => 'Initial member registration',
        'change_type' => 'system',
        'changed_by' => $admin->id,
    ]);
});

test('admin can update member and log status change', function () {
    /** @var \Tests\TestCase $this */
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

    $response = $this->actingAs($admin, 'admin')->put(route('members.update', $member), memberAdminFormPayload([
        'first_name' => 'Updated',
        'last_name' => 'Name',
        'full_name' => 'Updated Name',
        'phone' => '08099998888',
        'email' => 'updated.name@example.com',
        'department' => 'Youth Ministry',
        'status' => 'full_member',
        'joined_date' => $member->joined_date->format('Y-m-d'),
        'occupation' => 'Trader',
    ]));

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
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);

    $this->actingAs($admin, 'admin')->get(route('members.index'))->assertForbidden();
});

test('admin can view deceased dashboard with memorial details', function () {
    /** @var \Tests\TestCase $this */
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
    /** @var \Tests\TestCase $this */
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
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);

    $this->actingAs($admin, 'admin')->get(route('members.deceased'))->assertForbidden();
});

test('admin can record an existing member as deceased from the directory', function () {
    /** @var \Tests\TestCase $this */
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
    /** @var \Tests\TestCase $this */
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
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);
    $member = Member::factory()->create(['status' => 'active']);

    $this->actingAs($admin, 'admin')->get(route('members.deceased.record'))->assertForbidden();
    $this->actingAs($admin, 'admin')->post(route('members.deceased.store'), [
        'member_id' => $member->id,
        'date_of_death' => '2026-04-10',
    ])->assertForbidden();
});

test('admin can generate a death certificate pdf for a deceased member', function () {
    /** @var \Tests\TestCase $this */
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
    /** @var \Tests\TestCase $this */
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
    /** @var \Tests\TestCase $this */
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
    /** @var \Tests\TestCase $this */
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
    /** @var \Tests\TestCase $this */
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

test('member profile shows occupation or a placeholder when it is empty', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'admin']);
    $member = Member::factory()->create([
        'full_name' => 'Clifford Profile',
        'status' => 'full_member',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('members.show', $member))
        ->assertOk()
        ->assertSee('Occupation');
});

test('searching a parent member shows the names and photos of their children', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'admin']);
    $parent = Member::factory()->create([
        'first_name' => 'Ada',
        'last_name' => 'Okafor',
        'full_name' => 'Ada Okafor',
        'phone' => '08034095171',
        'email' => 'ada.okafor@example.com',
        'date_of_birth' => now()->subYears(38)->toDateString(),
        'department' => 'Women\'s Ministry',
    ]);
    Member::factory()->create([
        'first_name' => 'Chidi',
        'last_name' => 'Okafor',
        'full_name' => 'Chidi Okafor',
        'parent_name' => 'Ada Okafor',
        'parent_phone' => '08034095171',
        'parent_email' => 'ada.okafor@example.com',
        'date_of_birth' => now()->subYears(9)->toDateString(),
        'photo_path' => 'uploads/members/chidi.jpg',
        'department' => 'Children Ministry',
    ]);
    Member::factory()->create([
        'first_name' => 'Amaka',
        'last_name' => 'Okafor',
        'full_name' => 'Amaka Okafor',
        'parent_name' => 'Ada Okafor',
        'parent_phone' => '08034095171',
        'date_of_birth' => now()->subYears(6)->toDateString(),
        'department' => 'Children Ministry',
    ]);

    $search = $this->actingAs($admin, 'admin')->get(route('members.index', ['q' => 'Ada Okafor']));
    $search->assertOk();
    $search->assertSee('Ada Okafor');
    $search->assertSee('Children');
    $search->assertSee('Chidi Okafor');
    $search->assertSee('Amaka Okafor');
    $search->assertSee('uploads/members/chidi.jpg', false);

    $profile = $this->actingAs($admin, 'admin')->get(route('members.show', $parent));
    $profile->assertOk();
    $profile->assertSee('Children');
    $profile->assertSee('Chidi Okafor');
    $profile->assertSee('Amaka Okafor');
});

test('admin can grant and revoke member portal login access', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')->post(route('members.store'), memberAdminFormPayload([
        'email' => 'portal.member@example.com',
        'portal_enabled' => '1',
        'portal_password' => 'secret12',
    ]))->assertRedirect(route('members.index'));

    $member = Member::query()->where('email', 'portal.member@example.com')->first();
    expect($member)->not->toBeNull();
    expect((int) $member->portal_enabled)->toBe(1);
    expect(\Illuminate\Support\Facades\Hash::check('secret12', (string) $member->portal_password_hash))->toBeTrue();

    $this->actingAs($admin, 'admin')
        ->get(route('members.index', ['q' => 'Jane Doe']))
        ->assertOk()
        ->assertSee('Portal access');

    $this->actingAs($admin, 'admin')
        ->get(route('members.show', $member))
        ->assertOk()
        ->assertSee('Access granted');

    $this->actingAs($admin, 'admin')->put(route('members.update', $member), memberAdminFormPayload([
        'email' => 'portal.member@example.com',
        'joined_date' => $member->joined_date->format('Y-m-d'),
    ]))->assertRedirect(route('members.show', $member));

    $this->assertDatabaseHas('members', [
        'id' => $member->id,
        'portal_enabled' => 0,
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('members.show', $member))
        ->assertOk()
        ->assertSee('No access');
});
