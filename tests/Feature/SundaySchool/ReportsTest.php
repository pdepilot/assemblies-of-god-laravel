<?php

use App\Models\Admin;
use App\Models\SundaySchoolClass;
use App\Models\SundaySchoolStudent;
use App\Models\SundaySchoolTeacher;
use Illuminate\Support\Facades\DB;

test('admin can view reports page with stats', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00500',
        'class_name' => 'Reports Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00500',
        'full_name' => 'Report Student',
        'class_id' => $class->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('ss.reports.index'));

    $response->assertOk();
    $response->assertSee('Sunday School — Reports');
    $response->assertSee('Report Student');
});

test('admin can generate a class daily report', function () {
    $admin = Admin::factory()->create(['role' => 'super_admin']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00501',
        'class_name' => 'Daily Report Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    $student = SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00501',
        'full_name' => 'Present Student',
        'class_id' => $class->id,
        'status' => 'active',
    ]);

    DB::table('sunday_school_attendance')->insert([
        'student_id' => $student->id,
        'class_id' => $class->id,
        'attendance_date' => '2026-07-20',
        'status' => 'present',
        'arrival_status' => 'early',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('sunday_school_offerings')->insert([
        'student_id' => $student->id,
        'class_id' => $class->id,
        'offering_date' => '2026-07-20',
        'amount' => 150,
        'recorded_by' => $admin->id,
        'created_at' => now(),
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('ss.reports.index', [
        'generate' => 1,
        'class_id' => $class->id,
        'report_date' => '2026-07-20',
    ]));

    $response->assertOk();
    $response->assertSee('Attendance rate:');
    $response->assertSee('100%');
    $response->assertSee('150.00');

    $this->assertDatabaseHas('sunday_school_reports', [
        'report_type' => 'class_daily',
        'class_id' => $class->id,
    ]);
});

test('punctuality report lists early and late counts', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00510',
        'class_name' => 'Punctual Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    $student = SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00510',
        'full_name' => 'Early Bird',
        'class_id' => $class->id,
        'status' => 'active',
    ]);

    DB::table('sunday_school_attendance')->insert([
        'student_id' => $student->id,
        'class_id' => $class->id,
        'attendance_date' => '2026-07-15',
        'status' => 'present',
        'arrival_status' => 'early',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('ss.reports.index', [
        'from' => '2026-07-01',
        'to' => '2026-07-31',
    ]));

    $response->assertOk();
    $response->assertSee('Early Bird');
    $response->assertSee('Punctual Class');
});

test('admin can export students csv', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00520',
        'class_name' => 'Export Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00520',
        'full_name' => 'CSV Student',
        'class_id' => $class->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('ss.reports.export', [
        'type' => 'students',
        'class_id' => $class->id,
    ]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=utf-8');
    expect($response->streamedContent())->toContain('CSV Student');
});

test('ss teacher cannot export teachers csv', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);
    SundaySchoolTeacher::query()->create([
        'teacher_code' => 'SS-TCH-00520',
        'full_name' => 'Export Teacher',
        'admin_id' => $admin->id,
        'status' => 'active',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('ss.reports.export', ['type' => 'teachers']))
        ->assertForbidden();
});

test('finance role cannot access reports', function () {
    $admin = Admin::factory()->create(['role' => 'finance']);

    $this->actingAs($admin, 'admin')->get(route('ss.reports.index'))->assertForbidden();
});
