<?php

use App\Models\Admin;
use App\Models\SundaySchoolClass;
use App\Models\SundaySchoolStudent;
use App\Models\SundaySchoolTeacher;

test('admin can register a sunday school student', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00001',
        'class_name' => 'Beginner Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'admin')->post(route('ss.students.store'), [
        'full_name' => 'Jane Doe',
        'class_id' => $class->id,
        'parent_name' => 'John Doe',
        'parent_phone' => '08012345678',
        'status' => 'active',
    ]);

    $response->assertRedirect(route('ss.students.index'));
    $this->assertDatabaseHas('sunday_school_students', [
        'full_name' => 'Jane Doe',
        'class_id' => $class->id,
        'status' => 'active',
    ]);
});

test('admin can transfer student to another class', function () {
    $admin = Admin::factory()->create(['role' => 'super_admin']);
    $from = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00010',
        'class_name' => 'Class A',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    $to = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00011',
        'class_name' => 'Class B',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    $student = SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00010',
        'full_name' => 'Transfer Student',
        'class_id' => $from->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'admin')->post(route('ss.students.transfer', $student), [
        'to_class_id' => $to->id,
        'notes' => 'Moved up',
    ]);

    $response->assertRedirect(route('ss.students.index'));
    $this->assertDatabaseHas('sunday_school_students', [
        'id' => $student->id,
        'class_id' => $to->id,
    ]);
    $this->assertDatabaseHas('sunday_school_promotions', [
        'student_id' => $student->id,
        'from_class_id' => $from->id,
        'to_class_id' => $to->id,
        'promotion_type' => 'transfer',
    ]);
});

test('ss teacher only sees students in assigned classes', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);
    $mine = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00020',
        'class_name' => 'My Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    $other = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00021',
        'class_name' => 'Other Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);

    SundaySchoolTeacher::query()->create([
        'teacher_code' => 'SS-TCH-00001',
        'full_name' => 'Teacher One',
        'admin_id' => $admin->id,
        'class_id' => $mine->id,
        'status' => 'active',
    ]);

    SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00020',
        'full_name' => 'Visible Student',
        'class_id' => $mine->id,
        'status' => 'active',
    ]);
    SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00021',
        'full_name' => 'Hidden Student',
        'class_id' => $other->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('ss.students.index'));

    $response->assertOk();
    $response->assertSee('Visible Student');
    $response->assertDontSee('Hidden Student');
});

test('ss teacher cannot delete students', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00030',
        'class_name' => 'Teacher Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    SundaySchoolTeacher::query()->create([
        'teacher_code' => 'SS-TCH-00002',
        'full_name' => 'Teacher Two',
        'admin_id' => $admin->id,
        'class_id' => $class->id,
        'status' => 'active',
    ]);
    $student = SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00030',
        'full_name' => 'Protected Student',
        'class_id' => $class->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'admin')->delete(route('ss.students.destroy', $student));

    $response->assertForbidden();
    $this->assertDatabaseHas('sunday_school_students', ['id' => $student->id]);
});

test('admin can archive a student', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00040',
        'class_name' => 'Archive Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    $student = SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00040',
        'full_name' => 'Archive Me',
        'class_id' => $class->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'admin')->post(route('ss.students.archive', $student));

    $response->assertRedirect(route('ss.students.index'));
    $this->assertDatabaseHas('sunday_school_students', [
        'id' => $student->id,
        'status' => 'archived',
    ]);
});
