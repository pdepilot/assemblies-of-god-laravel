<?php

use App\Models\Admin;
use App\Models\CommitmentProgram;
use Illuminate\Support\Facades\DB;

test('admin can view commitment programs', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    CommitmentProgram::factory()->create(['name' => 'Building Fund 2026']);

    $response = $this->actingAs($admin, 'admin')->get(route('commitments.index'));

    $response->assertOk();
    $response->assertSee('Commitment Programs');
    $response->assertSee('Building Fund 2026');
});

test('admin can create commitment program and add giver with payment', function () {
    $admin = Admin::factory()->create(['role' => 'church_administrator']);

    $this->actingAs($admin, 'admin')->post(route('commitments.store'), [
        'name' => 'Mission Outreach',
        'target_amount' => 1000000,
        'status' => 'active',
    ])->assertRedirect();

    $programId = (int) DB::table('commitment_programs')->where('name', 'Mission Outreach')->value('id');

    $this->actingAs($admin, 'admin')->post(route('commitments.givers.store', $programId), [
        'program_id' => $programId,
        'donor_name' => 'Partner One',
        'committed_amount' => 50000,
        'frequency' => 'monthly',
    ])->assertRedirect(route('commitments.show', $programId));

    $giverId = (int) DB::table('commitment_givers')->where('donor_name', 'Partner One')->value('id');

    $this->actingAs($admin, 'admin')->post(route('commitments.givers.payment', $giverId), [
        'amount' => 10000,
    ])->assertRedirect(route('commitments.show', $programId));

    expect((float) DB::table('commitment_givers')->where('id', $giverId)->value('amount_paid'))->toBe(10000.0);
});

test('ss teacher cannot access commitments module', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);

    $this->actingAs($admin, 'admin')->get(route('commitments.index'))->assertForbidden();
});
