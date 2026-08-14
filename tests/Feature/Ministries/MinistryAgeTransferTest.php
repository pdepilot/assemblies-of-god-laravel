<?php

use App\Models\Admin;
use App\Models\Member;
use App\Services\Ministries\MinistryAgeTransferService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

test('child age 13 moves from children to teens across stores', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $dob = now()->subYears(13)->subDay()->toDateString();

    $member = Member::factory()->create([
        'full_name' => 'Growing Child',
        'date_of_birth' => $dob,
        'department' => 'Children Ministry',
        'status' => 'full_member',
    ]);

    DB::table('ministry_members')->insert([
        'member_id' => $member->id,
        'ministry_key' => 'children',
        'ministry_status' => 'active',
        'join_date' => now()->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $rosterId = DB::table('ministry_roster_people')->insertGetId([
        'ministry_key' => 'children',
        'person_code' => 'MR-CHI-AGE01',
        'full_name' => 'Growing Child',
        'date_of_birth' => $dob,
        'status' => 'active',
        'joined_date' => now()->toDateString(),
        'linked_member_id' => $member->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $result = app(MinistryAgeTransferService::class)->run('manual', (int) $admin->id);

    expect($result['transferred'])->toBeGreaterThanOrEqual(1);

    $this->assertDatabaseHas('members', [
        'id' => $member->id,
        'department' => 'Teen Ministry',
    ]);
    $this->assertDatabaseHas('ministry_members', [
        'member_id' => $member->id,
        'ministry_key' => 'children',
        'ministry_status' => 'inactive',
    ]);
    $this->assertDatabaseHas('ministry_members', [
        'member_id' => $member->id,
        'ministry_key' => 'teens',
        'ministry_status' => 'active',
    ]);
    $this->assertDatabaseHas('ministry_roster_people', [
        'id' => $rosterId,
        'ministry_key' => 'teens',
    ]);
    $this->assertDatabaseHas('ministry_age_transfers', [
        'member_id' => $member->id,
        'from_key' => 'children',
        'to_key' => 'teens',
    ]);
});

test('teen age 20 moves to youths', function () {
    $dob = now()->subYears(20)->subDay()->toDateString();

    $member = Member::factory()->create([
        'full_name' => 'Older Teen',
        'date_of_birth' => $dob,
        'department' => 'Teen Ministry',
        'status' => 'full_member',
    ]);

    DB::table('ministry_members')->insert([
        'member_id' => $member->id,
        'ministry_key' => 'teens',
        'ministry_status' => 'active',
        'join_date' => now()->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    app(MinistryAgeTransferService::class)->run('schedule');

    $this->assertDatabaseHas('members', [
        'id' => $member->id,
        'department' => 'Youth Ministry',
    ]);
    $this->assertDatabaseHas('ministry_members', [
        'member_id' => $member->id,
        'ministry_key' => 'youths',
        'ministry_status' => 'active',
    ]);
    $this->assertDatabaseHas('ministry_age_transfers', [
        'member_id' => $member->id,
        'from_key' => 'teens',
        'to_key' => 'youths',
        'source' => 'schedule',
    ]);
});

test('age 12 stays children and age 19 stays teens', function () {
    $child = Member::factory()->create([
        'full_name' => 'Still Child',
        'date_of_birth' => now()->subYears(12)->toDateString(),
        'department' => 'Children Ministry',
        'status' => 'full_member',
    ]);
    $teen = Member::factory()->create([
        'full_name' => 'Still Teen',
        'date_of_birth' => now()->subYears(19)->toDateString(),
        'department' => 'Teen Ministry',
        'status' => 'full_member',
    ]);

    DB::table('ministry_members')->insert([
        [
            'member_id' => $child->id,
            'ministry_key' => 'children',
            'ministry_status' => 'active',
            'join_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'member_id' => $teen->id,
            'ministry_key' => 'teens',
            'ministry_status' => 'active',
            'join_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $result = app(MinistryAgeTransferService::class)->run('manual');

    $movedIds = collect($result['items'])->pluck('member_id')->filter()->all();
    expect($movedIds)->not->toContain($child->id);
    expect($movedIds)->not->toContain($teen->id);

    $this->assertDatabaseHas('members', ['id' => $child->id, 'department' => 'Children Ministry']);
    $this->assertDatabaseHas('members', ['id' => $teen->id, 'department' => 'Teen Ministry']);
});

test('missing date of birth is skipped', function () {
    $member = Member::factory()->create([
        'full_name' => 'No Birthday',
        'date_of_birth' => null,
        'department' => 'Children Ministry',
        'status' => 'full_member',
    ]);

    $preview = app(MinistryAgeTransferService::class)->preview();
    $missingNames = collect($preview['missing_dob'])->pluck('full_name')->all();
    expect($missingNames)->toContain('No Birthday');

    $result = app(MinistryAgeTransferService::class)->run('manual');
    expect(collect($result['items'])->pluck('member_id'))->not->toContain($member->id);
    $this->assertDatabaseMissing('ministry_age_transfers', ['member_id' => $member->id]);
});

test('dry-run command does not write transfers', function () {
    $member = Member::factory()->create([
        'full_name' => 'Dry Run Child',
        'date_of_birth' => now()->subYears(14)->toDateString(),
        'department' => 'Children Ministry',
        'status' => 'full_member',
    ]);

    Artisan::call('ministries:age-transfer', ['--dry-run' => true]);

    $this->assertDatabaseHas('members', ['id' => $member->id, 'department' => 'Children Ministry']);
    $this->assertDatabaseMissing('ministry_age_transfers', ['member_id' => $member->id]);
});

test('admin can open age transfers page and generate eligibility report', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')
        ->get(route('ministries.age-transfers.index'))
        ->assertOk()
        ->assertSee('Ministry Age Transfers');

    $response = $this->actingAs($admin, 'admin')->post(route('analytics.reports.generate'), [
        'type' => 'ministry_age_eligibility',
        'period' => 'all_time',
        'format' => 'csv',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('generated_reports', [
        'report_type' => 'ministry_age_eligibility',
    ]);
});

test('member save triggers age transfer when due', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);

    $member = Member::factory()->create([
        'full_name' => 'Save Sync Child',
        'date_of_birth' => now()->subYears(10)->toDateString(),
        'department' => 'Children Ministry',
        'status' => 'full_member',
        'phone' => '08012345678',
        'address_line1' => '1 Family Lane',
        'city' => 'Owerri',
        'state' => 'Imo',
    ]);

    DB::table('ministry_members')->insert([
        'member_id' => $member->id,
        'ministry_key' => 'children',
        'ministry_status' => 'active',
        'join_date' => now()->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')->put(route('members.update', $member), [
        'full_name' => 'Save Sync Child',
        'phone' => '08012345678',
        'address_line1' => '1 Family Lane',
        'city' => 'Owerri',
        'state' => 'Imo',
        'date_of_birth' => now()->subYears(13)->subMonth()->toDateString(),
        'marital_status' => 'single',
        'department' => 'Children Ministry',
        'status' => 'full_member',
        'joined_date' => now()->toDateString(),
    ])->assertRedirect(route('members.show', $member));

    $this->assertDatabaseHas('members', [
        'id' => $member->id,
        'department' => 'Teen Ministry',
    ]);
    $this->assertDatabaseHas('ministry_age_transfers', [
        'member_id' => $member->id,
        'from_key' => 'children',
        'to_key' => 'teens',
        'source' => 'on_save',
    ]);
});
