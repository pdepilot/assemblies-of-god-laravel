<?php

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

test('admin can open analytics reports hub', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')
        ->get('/admin/analytics/reports')
        ->assertOk()
        ->assertSee('Reports Hub')
        ->assertSee('People & Membership')
        ->assertSee('Giving & Finance')
        ->assertSee('Ministry Rosters')
        ->assertSee('Donations & Stewardship')
        ->assertSee('Newsletter Subscribers')
        ->assertSee('Registration Portals');
});

test('admin can generate visitors and donations reports from cms catalog', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')->post(route('analytics.reports.generate'), [
        'type' => 'visitors',
        'period' => 'all_time',
        'format' => 'csv',
    ])->assertRedirect(route('analytics.reports.index'));

    $this->assertDatabaseHas('generated_reports', [
        'report_type' => 'visitors',
        'format' => 'csv',
        'generated_by' => $admin->id,
    ]);

    $this->actingAs($admin, 'admin')->post(route('analytics.reports.generate'), [
        'type' => 'donations',
        'period' => 'ytd',
        'format' => 'csv',
    ])->assertRedirect(route('analytics.reports.index'));

    $this->assertDatabaseHas('generated_reports', [
        'report_type' => 'donations',
        'format' => 'csv',
    ]);
});

test('legacy admin reports path redirects to analytics reports hub', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')
        ->get('/admin/reports')
        ->assertRedirect('/admin/analytics/reports');
});

test('admin can generate membership pdf and docx reports', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);

    DB::table('members')->insert([
        'member_code' => 'MEM-PDF-001',
        'full_name' => 'PDF Member',
        'phone' => '08011111111',
        'address_line1' => '1 Test Street',
        'city' => 'Owerri',
        'state' => 'Imo',
        'department' => 'youth',
        'status' => 'active',
        'joined_date' => now()->format('Y-m-d'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')->post(route('analytics.reports.generate'), [
        'type' => 'membership',
        'period' => 'all_time',
        'format' => 'pdf',
    ])->assertRedirect(route('analytics.reports.index'));

    $this->assertDatabaseHas('generated_reports', [
        'report_type' => 'membership',
        'format' => 'pdf',
        'generated_by' => $admin->id,
    ]);

    $this->actingAs($admin, 'admin')->post(route('analytics.reports.generate'), [
        'type' => 'membership',
        'period' => 'all_time',
        'format' => 'docx',
    ])->assertRedirect(route('analytics.reports.index'));

    $this->assertDatabaseHas('generated_reports', [
        'report_type' => 'membership',
        'format' => 'docx',
        'generated_by' => $admin->id,
    ]);
});

test('admin can delete a generated report', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);

    Storage::disk('local')->makeDirectory('reports');
    $relative = 'reports/ag-test-delete.csv';
    Storage::disk('local')->put($relative, "a,b\n1,2\n");
    $absolute = Storage::disk('local')->path($relative);

    $id = (int) DB::table('generated_reports')->insertGetId([
        'report_type' => 'membership',
        'title' => 'Delete Me',
        'period_key' => 'all_time',
        'period_label' => 'All Time',
        'format' => 'csv',
        'file_path' => $absolute,
        'file_name' => 'ag-test-delete.csv',
        'file_size' => 10,
        'row_count' => 1,
        'generated_by' => $admin->id,
        'generated_by_name' => 'Admin',
        'download_count' => 0,
        'meta_json' => json_encode([]),
        'created_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->delete(route('analytics.reports.destroy', $id))
        ->assertRedirect(route('analytics.reports.index'));

    $this->assertDatabaseMissing('generated_reports', ['id' => $id]);
    expect(Storage::disk('local')->exists($relative))->toBeFalse();
});
