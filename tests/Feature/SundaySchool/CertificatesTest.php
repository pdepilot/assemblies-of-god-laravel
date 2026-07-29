<?php

use App\Models\Admin;
use App\Models\SundaySchoolClass;
use App\Models\SundaySchoolStudent;
use Illuminate\Support\Facades\DB;

test('admin can view sunday school certificates page', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);

    DB::table('sunday_school_certificates')->insert([
        'certificate_number' => 'SS-CERT-2026-000001',
        'verification_code' => 'abc123verifycode',
        'award_id' => null,
        'recipient_type' => 'student',
        'recipient_name' => 'Test Student',
        'award_title' => 'Best Attendance',
        'achievement_description' => 'Excellent attendance record.',
        'issued_date' => '2026-06-22',
        'file_path' => 'uploads/sunday-school/certificates/SS-CERT-2026-000001.pdf',
        'issued_by' => $admin->id,
        'status' => 'issued',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('ss.certificates.index'));

    $response->assertOk();
    $response->assertSee('Certificates');
    $response->assertSee('SS-CERT-2026-000001');
    $response->assertSee('Test Student');
});

test('admin can delete a sunday school certificate record', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);

    $id = DB::table('sunday_school_certificates')->insertGetId([
        'certificate_number' => 'SS-CERT-2026-000002',
        'verification_code' => 'delete-me-code',
        'recipient_type' => 'student',
        'recipient_name' => 'Delete Me',
        'award_title' => 'Award',
        'issued_date' => '2026-06-22',
        'file_path' => 'uploads/sunday-school/certificates/SS-CERT-2026-000002.pdf',
        'status' => 'issued',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($admin, 'admin')->delete(route('ss.certificates.destroy', $id));

    $response->assertRedirect(route('ss.certificates.index'));
    $this->assertDatabaseMissing('sunday_school_certificates', ['id' => $id]);
});

test('awards page shows generate certificate action for approved awards', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00500',
        'class_name' => 'Cert Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00500',
        'full_name' => 'Cert Student',
        'class_id' => $class->id,
        'status' => 'active',
    ]);

    $this->actingAs($admin, 'admin')->post(route('ss.awards.generate'), [
        'period_type' => 'annual',
    ])->assertRedirect(route('ss.awards.index'));

    $awardId = (int) DB::table('sunday_school_awards')->value('id');
    $this->actingAs($admin, 'admin')->post(route('ss.awards.approve', $awardId))->assertRedirect();

    $response = $this->actingAs($admin, 'admin')->get(route('ss.awards.index'));

    $response->assertOk();
    $response->assertSee('Generate certificate');
});

test('admin can preview a certificate pdf when file exists', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $certNumber = 'SS-CERT-2026-PREVIEW1';
    $directory = public_path('site/uploads/sunday-school/certificates');
    if (! is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    $fullPath = $directory.DIRECTORY_SEPARATOR.$certNumber.'.pdf';
    file_put_contents($fullPath, '%PDF-1.4 test certificate');

    $id = DB::table('sunday_school_certificates')->insertGetId([
        'certificate_number' => $certNumber,
        'verification_code' => 'preview-code',
        'recipient_type' => 'student',
        'recipient_name' => 'Preview Student',
        'award_title' => 'Award',
        'issued_date' => '2026-06-22',
        'file_path' => 'uploads/sunday-school/certificates/'.$certNumber.'.pdf',
        'status' => 'issued',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('ss.certificates.preview', $id));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');

    @unlink($fullPath);
});

test('ss teacher cannot access certificates page', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);

    $this->actingAs($admin, 'admin')->get(route('ss.certificates.index'))->assertForbidden();
});
