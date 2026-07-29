<?php

use App\Models\Admin;
use App\Models\SundaySchoolClass;
use App\Models\SundaySchoolStudent;
use App\Models\SundaySchoolTeacher;
use Illuminate\Support\Facades\DB;

test('admin can view analytics page with charts', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00600',
        'class_name' => 'Analytics Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00600',
        'full_name' => 'Chart Student',
        'class_id' => $class->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('ss.analytics.index'));

    $response->assertOk();
    $response->assertSee('Sunday School Department');
    $response->assertSee('Overview');
    $response->assertSee('Total Students');
    $response->assertSee('chartWeeklyAtt');
    $response->assertSee('Weekly Attendance');
    $response->assertSee('Teacher Performance');
});

test('analytics page includes chart data payload', function () {
    $admin = Admin::factory()->create(['role' => 'super_admin']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00601',
        'class_name' => 'Payload Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    $student = SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00601',
        'full_name' => 'Payload Student',
        'class_id' => $class->id,
        'status' => 'active',
    ]);

    DB::table('sunday_school_attendance')->insert([
        'student_id' => $student->id,
        'class_id' => $class->id,
        'attendance_date' => now()->toDateString(),
        'status' => 'present',
        'arrival_status' => 'early',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('ss.analytics.index', ['tab' => 'intelligence']));

    $response->assertOk();
    $response->assertSee('weekly_attendance');
    $response->assertSee('Superintendent Intelligence Dashboard');
    $response->assertSee('chartAttHeatmap');
});

test('superintendent sees top performers section', function () {
    $admin = Admin::factory()->create(['role' => 'ss_superintendent']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00610',
        'class_name' => 'Super Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00610',
        'full_name' => 'Top Performer',
        'class_id' => $class->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('ss.analytics.index', ['tab' => 'intelligence']));

    $response->assertOk();
    $response->assertSee('Top Students');
});
test('ss teacher can view scoped analytics', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00620',
        'class_name' => 'Teacher Analytics Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    SundaySchoolTeacher::query()->create([
        'teacher_code' => 'SS-TCH-00620',
        'full_name' => 'Analytics Teacher',
        'admin_id' => $admin->id,
        'class_id' => $class->id,
        'status' => 'active',
    ]);
    SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00620',
        'full_name' => 'Scoped Student',
        'class_id' => $class->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('ss.analytics.index'));

    $response->assertOk();
    $response->assertSee('Total Students');
    $response->assertSee('chartWeeklyAtt');
    $response->assertDontSee('Superintendent Intelligence Dashboard');
    $response->assertDontSee('>Intelligence<', false);
});

test('finance role cannot access analytics', function () {
    $admin = Admin::factory()->create(['role' => 'finance']);

    $this->actingAs($admin, 'admin')->get(route('ss.analytics.index'))->assertForbidden();
});
