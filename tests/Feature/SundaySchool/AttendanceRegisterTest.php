<?php

use App\Models\Admin;
use App\Models\SundaySchoolClass;
use App\Models\SundaySchoolStudent;
use App\Models\SundaySchoolTeacher;

test('admin can save attendance for a class register', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00200',
        'class_name' => 'Attendance Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    $student = SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00200',
        'full_name' => 'Attendee One',
        'class_id' => $class->id,
        'status' => 'active',
    ]);
    $date = '2026-07-20';

    $response = $this->actingAs($admin, 'admin')->post(route('ss.attendance.store'), [
        'records' => [[
            'student_id' => $student->id,
            'class_id' => $class->id,
            'attendance_date' => $date,
            'status' => 'present',
            'arrival_status' => 'on_time',
        ]],
    ]);

    $response->assertRedirect(route('ss.attendance.index', [
        'class_id' => $class->id,
        'date' => $date,
    ]));
    $this->assertDatabaseHas('sunday_school_attendance', [
        'student_id' => $student->id,
        'class_id' => $class->id,
        'attendance_date' => $date,
        'status' => 'present',
        'arrival_status' => 'on_time',
        'recorded_by' => $admin->id,
    ]);
});

test('admin can update existing attendance via upsert', function () {
    $admin = Admin::factory()->create(['role' => 'super_admin']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00201',
        'class_name' => 'Upsert Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    $student = SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00201',
        'full_name' => 'Upsert Student',
        'class_id' => $class->id,
        'status' => 'active',
    ]);
    $date = '2026-07-21';

    $this->actingAs($admin, 'admin')->post(route('ss.attendance.store'), [
        'records' => [[
            'student_id' => $student->id,
            'class_id' => $class->id,
            'attendance_date' => $date,
            'status' => 'present',
            'arrival_status' => 'early',
        ]],
    ])->assertRedirect();

    $this->actingAs($admin, 'admin')->post(route('ss.attendance.store'), [
        'records' => [[
            'student_id' => $student->id,
            'class_id' => $class->id,
            'attendance_date' => $date,
            'status' => 'absent',
            'arrival_status' => 'unknown',
        ]],
    ])->assertRedirect();

    $this->assertDatabaseCount('sunday_school_attendance', 1);
    $this->assertDatabaseHas('sunday_school_attendance', [
        'student_id' => $student->id,
        'attendance_date' => $date,
        'status' => 'absent',
        'arrival_status' => 'unknown',
    ]);
});

test('ss teacher can load attendance sheet for assigned class only', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);
    $mine = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00210',
        'class_name' => 'My Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    $other = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00211',
        'class_name' => 'Other Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);

    SundaySchoolTeacher::query()->create([
        'teacher_code' => 'SS-TCH-00210',
        'full_name' => 'Scoped Teacher',
        'admin_id' => $admin->id,
        'class_id' => $mine->id,
        'status' => 'active',
    ]);

    SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00210',
        'full_name' => 'Visible Student',
        'class_id' => $mine->id,
        'status' => 'active',
    ]);

    $ok = $this->actingAs($admin, 'admin')->get(route('ss.attendance.index', [
        'class_id' => $mine->id,
        'date' => '2026-07-20',
    ]));
    $ok->assertOk();
    $ok->assertSee('Visible Student');

    $denied = $this->actingAs($admin, 'admin')->get(route('ss.attendance.index', [
        'class_id' => $other->id,
        'date' => '2026-07-20',
    ]));
    $denied->assertOk();
    $denied->assertSee('You do not have access to this class.');
});

test('finance role cannot access attendance register', function () {
    $admin = Admin::factory()->create(['role' => 'finance']);

    $response = $this->actingAs($admin, 'admin')->get(route('ss.attendance.index'));

    $response->assertForbidden();
});

test('admin can save offering and memory verse on register', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00220',
        'class_name' => 'Register Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    $student = SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00220',
        'full_name' => 'Offering Student',
        'class_id' => $class->id,
        'status' => 'active',
    ]);
    $date = '2026-07-22';

    $this->actingAs($admin, 'admin')->post(route('ss.attendance.store'), [
        'records' => [[
            'student_id' => $student->id,
            'class_id' => $class->id,
            'attendance_date' => $date,
            'status' => 'present',
            'arrival_status' => 'on_time',
            'offering_amount' => 150.50,
            'memory_verse' => true,
        ]],
    ])->assertRedirect();

    $this->assertDatabaseHas('sunday_school_offerings', [
        'student_id' => $student->id,
        'class_id' => $class->id,
        'offering_date' => $date,
        'amount' => 150.50,
    ]);
    $this->assertDatabaseHas('sunday_school_memory_verses', [
        'student_id' => $student->id,
        'class_id' => $class->id,
        'recitation_date' => $date,
        'score' => 'passed',
    ]);
});

test('admin can void a register entry with audit trail', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00230',
        'class_name' => 'Void Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    $student = SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00230',
        'full_name' => 'Void Student',
        'class_id' => $class->id,
        'status' => 'active',
    ]);
    $date = '2026-07-23';

    $this->actingAs($admin, 'admin')->post(route('ss.attendance.store'), [
        'records' => [[
            'student_id' => $student->id,
            'class_id' => $class->id,
            'attendance_date' => $date,
            'status' => 'present',
            'offering_amount' => 20,
            'memory_verse' => true,
        ]],
    ]);

    $response = $this->actingAs($admin, 'admin')->post(route('ss.attendance.void'), [
        'student_id' => $student->id,
        'class_id' => $class->id,
        'attendance_date' => $date,
        'reason' => 'Entered on wrong date by mistake',
    ]);

    $response->assertRedirect(route('ss.attendance.index', [
        'class_id' => $class->id,
        'date' => $date,
    ]));
    $this->assertDatabaseMissing('sunday_school_attendance', [
        'student_id' => $student->id,
        'attendance_date' => $date,
    ]);
    $this->assertDatabaseMissing('sunday_school_offerings', [
        'student_id' => $student->id,
        'offering_date' => $date,
    ]);
    $this->assertDatabaseHas('sunday_school_attendance_removals', [
        'student_id' => $student->id,
        'class_id' => $class->id,
        'attendance_date' => $date,
        'reason' => 'Entered on wrong date by mistake',
        'removed_by' => $admin->id,
    ]);
});

test('superintendent can view correction audit but teacher cannot', function () {
    $superintendent = Admin::factory()->create(['role' => 'ss_superintendent']);
    $teacher = Admin::factory()->create(['role' => 'ss_teacher']);

    $this->actingAs($superintendent, 'admin')
        ->get(route('ss.attendance.removals'))
        ->assertOk();

    $this->actingAs($teacher, 'admin')
        ->get(route('ss.attendance.removals'))
        ->assertForbidden();
});
