<?php

use App\Models\Admin;
use App\Models\Pledge;
use Illuminate\Support\Facades\DB;

test('admin can view pledges directory', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    Pledge::factory()->create(['donor_name' => 'Pledge Donor']);

    $response = $this->actingAs($admin, 'admin')->get(route('pledges.index'));

    $response->assertOk();
    $response->assertSee('Pledges');
    $response->assertSee('Pledge Donor');
});

test('admin can create pledge and record payment', function () {
    $admin = Admin::factory()->create(['role' => 'finance']);

    $this->actingAs($admin, 'admin')->post(route('pledges.store'), [
        'donor_name' => 'Annual Giver',
        'pledged_amount' => 120000,
        'installment_count' => 12,
        'start_date' => '2026-07-01',
    ])->assertRedirect();

    $pledgeId = (int) DB::table('pledges')->where('donor_name', 'Annual Giver')->value('id');

    $this->actingAs($admin, 'admin')->post(route('pledges.payment', $pledgeId), [
        'amount' => 10000,
    ])->assertRedirect(route('pledges.show', $pledgeId));

    $pledge = DB::table('pledges')->where('id', $pledgeId)->first();
    expect((float) $pledge->amount_paid)->toBe(10000.0);
    expect((float) $pledge->remaining_balance)->toBe(110000.0);
    $this->assertDatabaseHas('pledge_payments', ['pledge_id' => $pledgeId, 'amount' => 10000]);
});

test('ss teacher cannot access pledges module', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);

    $this->actingAs($admin, 'admin')->get(route('pledges.index'))->assertForbidden();
});
