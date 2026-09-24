<?php

use App\Models\Admin;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

test('admin can view ministries hub with seeded settings', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin, 'admin')->get(route('ministries.settings.index'));

    $response->assertOk();
    $response->assertSee('Children Ministry');
    $response->assertSee('Music Department');
    $response->assertDontSee('Widowers Ministry');
});

test('admin can register a ministry roster person without touching church members', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'church_administrator']);
    $membersBefore = DB::table('members')->count();

    $response = $this->actingAs($admin, 'admin')->post(route('ministries.module.register', 'music'), [
        'full_name' => 'Praise Singer',
        'phone' => '08011112222',
        'address_line1' => '12 Worship Street',
        'city' => 'Owerri',
        'state' => 'Imo',
    ]);

    $response->assertRedirect(route('ministries.module.index', 'music'));
    $this->assertDatabaseHas('ministry_roster_people', [
        'ministry_key' => 'music',
        'full_name' => 'Praise Singer',
        'phone' => '08011112222',
        'status' => 'active',
    ]);
    expect(DB::table('members')->count())->toBe($membersBefore);
});

test('children ministry requires parent details', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')
        ->post(route('ministries.module.register', 'children'), [
            'full_name' => 'Little Child',
            'phone' => '08000000000',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('full_name');

    $this->actingAs($admin, 'admin')
        ->post(route('ministries.module.register', 'children'), [
            'full_name' => 'Little Child',
            'parent_name' => 'Parent One',
            'parent_phone' => '08099998888',
            'address_line1' => '1 Family Lane',
        ])
        ->assertRedirect(route('ministries.module.index', 'children'));

    $this->assertDatabaseHas('ministry_roster_people', [
        'ministry_key' => 'children',
        'full_name' => 'Little Child',
        'parent_name' => 'Parent One',
        'parent_phone' => '08099998888',
    ]);
});

test('ministry roster shows total members', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'admin']);

    DB::table('ministry_roster_people')->insert([
        'ministry_key' => 'youths',
        'person_code' => 'MR-YOU-00001',
        'full_name' => 'Youth One',
        'phone' => '0801',
        'status' => 'active',
        'joined_date' => now()->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('ministries.module.index', 'youths'))
        ->assertOk()
        ->assertSee('Total members')
        ->assertSee('Youth One');
});

test('admin can record ministry roster attendance', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'admin']);
    $personId = DB::table('ministry_roster_people')->insertGetId([
        'ministry_key' => 'choir',
        'person_code' => 'MR-CHO-00001',
        'full_name' => 'Choir Voice',
        'status' => 'active',
        'joined_date' => now()->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($admin, 'admin')->post(route('ministries.module.attendance', 'choir'), [
        'attendee' => 'roster:'.$personId,
        'service_date' => '2026-07-20',
        'present' => '1',
    ]);

    $response->assertRedirect(route('ministries.module.index', 'choir'));
    $this->assertDatabaseHas('ministry_roster_attendance', [
        'roster_person_id' => $personId,
        'ministry_key' => 'choir',
        'service_date' => '2026-07-20',
        'service_type' => 'weekly',
    ]);
});

test('ministry import form lists church members in dropdown', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'admin']);
    $member = Member::factory()->create([
        'full_name' => 'Available Church Member',
        'member_code' => 'AGCI-12345',
        'phone' => '08055556666',
    ]);
    Member::factory()->create(['full_name' => 'Another Member']);

    $this->actingAs($admin, 'admin')
        ->get(route('ministries.module.index', 'music'))
        ->assertOk()
        ->assertSee('Select church member')
        ->assertSee('Available Church Member')
        ->assertSee('AGCI-12345')
        ->assertSee('08055556666')
        ->assertSee('Another Member');
});

test('church member already on roster is excluded from import dropdown', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'admin']);
    $onRoster = Member::factory()->create(['full_name' => 'Already On Roster']);
    $available = Member::factory()->create(['full_name' => 'Still Available Member']);

    DB::table('ministry_members')->insert([
        'member_id' => $onRoster->id,
        'ministry_key' => 'music',
        'ministry_status' => 'active',
        'join_date' => now()->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $options = app(\App\Services\Ministries\MinistryModuleReadService::class)
        ->listChurchMembersForImport('music');
    $optionIds = collect($options)->pluck('id')->all();

    expect($optionIds)->toContain($available->id)
        ->and($optionIds)->not->toContain($onRoster->id);

    $this->actingAs($admin, 'admin')
        ->get(route('ministries.module.index', 'music'))
        ->assertOk()
        ->assertSee('Still Available Member');
});

test('legacy ministry members appear in roster and attendance dropdown', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'admin']);
    $member = Member::factory()->create([
        'full_name' => 'Legacy Men Member',
        'member_code' => 'AGCI-77777',
    ]);

    DB::table('ministry_members')->insert([
        'member_id' => $member->id,
        'ministry_key' => 'men',
        'ministry_status' => 'active',
        'join_date' => now()->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('ministries.module.index', 'men'))
        ->assertOk()
        ->assertSee('Legacy Men Member')
        ->assertSee('AGCI-77777')
        ->assertSee('member:'.$member->id, false);
});

test('importing a church member enrolls in ministry_members when available', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'admin']);
    $member = Member::factory()->create([
        'full_name' => 'Copied Member',
        'phone' => '08033334444',
        'address_line1' => '99 Church Rd',
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('ministries.module.import-member', 'ushers'), [
            'member_id' => $member->id,
            'notes' => 'Door team',
        ])
        ->assertRedirect(route('ministries.module.index', 'ushers'));

    $this->assertDatabaseHas('ministry_members', [
        'member_id' => $member->id,
        'ministry_key' => 'ushers',
        'ministry_status' => 'active',
    ]);
});

test('admin can update ministry setting', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'admin']);
    $settingId = DB::table('ministry_settings')->where('ministry_key', 'music')->value('id');

    $response = $this->actingAs($admin, 'admin')->put(route('ministries.settings.update', $settingId), [
        'name' => 'Music & Worship',
        'gender_filter' => 'any',
        'assignment_mode' => 'manual',
        'is_enabled' => '1',
        'sort_order' => 10,
    ]);

    $response->assertRedirect(route('ministries.settings.index'));
    $this->assertDatabaseHas('ministry_settings', [
        'ministry_key' => 'music',
        'name' => 'Music & Worship',
    ]);
});

test('finance role cannot access ministries module', function () {
    /** @var \Tests\TestCase $this */
    $admin = Admin::factory()->create(['role' => 'finance']);

    $this->actingAs($admin, 'admin')->get(route('ministries.settings.index'))->assertForbidden();
});
