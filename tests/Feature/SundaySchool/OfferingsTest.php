<?php

use App\Models\Admin;
use App\Models\SundaySchoolClass;
use App\Models\SundaySchoolStudent;
use App\Models\SundaySchoolTeacher;
use Illuminate\Support\Facades\DB;

test('admin can list offerings for a class and date range', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00300',
        'class_name' => 'Offering Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    $student = SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00300',
        'full_name' => 'Giver Student',
        'class_id' => $class->id,
        'status' => 'active',
    ]);

    DB::table('sunday_school_offerings')->insert([
        'student_id' => $student->id,
        'class_id' => $class->id,
        'offering_date' => '2026-07-15',
        'amount' => 250.00,
        'recorded_by' => $admin->id,
        'created_at' => now(),
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('ss.offerings.index', [
        'class_id' => $class->id,
        'from' => '2026-07-01',
        'to' => '2026-07-31',
    ]));

    $response->assertOk();
    $response->assertSee('Giver Student');
    $response->assertSee('250.00');
});

test('admin can record a standalone offering', function () {
    $admin = Admin::factory()->create(['role' => 'super_admin']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00301',
        'class_name' => 'Record Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    $student = SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00301',
        'full_name' => 'New Giver',
        'class_id' => $class->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'admin')->post(route('ss.offerings.store'), [
        'student_id' => $student->id,
        'class_id' => $class->id,
        'amount' => 500,
        'offering_date' => '2026-07-20',
        'filter_from' => '2026-07-01',
        'filter_to' => '2026-07-31',
    ]);

    $response->assertRedirect(route('ss.offerings.index', [
        'class_id' => $class->id,
        'from' => '2026-07-01',
        'to' => '2026-07-31',
    ]));
    $this->assertDatabaseHas('sunday_school_offerings', [
        'student_id' => $student->id,
        'class_id' => $class->id,
        'amount' => 500,
        'recorded_by' => $admin->id,
    ]);
    expect(DB::table('sunday_school_offerings')
        ->where('student_id', $student->id)
        ->whereDate('offering_date', '2026-07-20')
        ->exists())->toBeTrue();
});

test('ss teacher only sees offerings for assigned classes', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);
    $mine = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00310',
        'class_name' => 'My Offering Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    $other = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00311',
        'class_name' => 'Other Offering Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);

    SundaySchoolTeacher::query()->create([
        'teacher_code' => 'SS-TCH-00310',
        'full_name' => 'Offering Teacher',
        'admin_id' => $admin->id,
        'class_id' => $mine->id,
        'status' => 'active',
    ]);

    $visible = SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00310',
        'full_name' => 'Visible Giver',
        'class_id' => $mine->id,
        'status' => 'active',
    ]);
    $hidden = SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00311',
        'full_name' => 'Hidden Giver',
        'class_id' => $other->id,
        'status' => 'active',
    ]);

    DB::table('sunday_school_offerings')->insert([
        [
            'student_id' => $visible->id,
            'class_id' => $mine->id,
            'offering_date' => '2026-07-10',
            'amount' => 100,
            'recorded_by' => $admin->id,
            'created_at' => now(),
        ],
        [
            'student_id' => $hidden->id,
            'class_id' => $other->id,
            'offering_date' => '2026-07-10',
            'amount' => 200,
            'recorded_by' => $admin->id,
            'created_at' => now(),
        ],
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('ss.offerings.index', [
        'from' => '2026-07-01',
        'to' => '2026-07-31',
    ]));

    $response->assertOk();
    $response->assertSee('Visible Giver');
    $response->assertDontSee('Hidden Giver');
});

test('finance role cannot access offerings', function () {
    $admin = Admin::factory()->create(['role' => 'finance']);

    $this->actingAs($admin, 'admin')->get(route('ss.offerings.index'))->assertForbidden();
});
