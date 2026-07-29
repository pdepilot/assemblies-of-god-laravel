<?php

use App\Models\Admin;
use App\Models\SundaySchoolClass;
use App\Models\SundaySchoolTeacher;

test('admin can add a sunday school teacher', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00100',
        'class_name' => 'Teacher Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'admin')->post(route('ss.teachers.store'), [
        'full_name' => 'Mary Teacher',
        'email' => 'mary@example.com',
        'class_id' => $class->id,
        'status' => 'active',
        'membership_status' => 'full_member',
    ]);

    $response->assertRedirect(route('ss.teachers.index'));
    $this->assertDatabaseHas('sunday_school_teachers', [
        'full_name' => 'Mary Teacher',
        'email' => 'mary@example.com',
        'class_id' => $class->id,
        'status' => 'active',
    ]);
    $this->assertDatabaseHas('sunday_school_classes', [
        'id' => $class->id,
        'teacher_id' => SundaySchoolTeacher::query()->where('full_name', 'Mary Teacher')->value('id'),
    ]);
});

test('admin can update a teacher', function () {
    $admin = Admin::factory()->create(['role' => 'super_admin']);
    $teacher = SundaySchoolTeacher::query()->create([
        'teacher_code' => 'SS-TCH-00100',
        'full_name' => 'Old Name',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'admin')->put(route('ss.teachers.update', $teacher), [
        'full_name' => 'New Name',
        'qualification' => 'B.Ed',
        'status' => 'active',
    ]);

    $response->assertRedirect(route('ss.teachers.index'));
    $this->assertDatabaseHas('sunday_school_teachers', [
        'id' => $teacher->id,
        'full_name' => 'New Name',
        'qualification' => 'B.Ed',
    ]);
});

test('admin can suspend and activate a teacher', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $teacher = SundaySchoolTeacher::query()->create([
        'teacher_code' => 'SS-TCH-00101',
        'full_name' => 'Suspend Me',
        'status' => 'active',
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('ss.teachers.suspend', $teacher))
        ->assertRedirect(route('ss.teachers.index'));

    $this->assertDatabaseHas('sunday_school_teachers', [
        'id' => $teacher->id,
        'status' => 'suspended',
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('ss.teachers.activate', $teacher))
        ->assertRedirect(route('ss.teachers.index'));

    $this->assertDatabaseHas('sunday_school_teachers', [
        'id' => $teacher->id,
        'status' => 'active',
    ]);
});

test('admin can delete a teacher and class references are cleared', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $teacher = SundaySchoolTeacher::query()->create([
        'teacher_code' => 'SS-TCH-00102',
        'full_name' => 'Delete Me',
        'status' => 'active',
    ]);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00102',
        'class_name' => 'Linked Class',
        'max_capacity' => 50,
        'status' => 'active',
        'teacher_id' => $teacher->id,
        'assistant_teacher_id' => $teacher->id,
    ]);

    $response = $this->actingAs($admin, 'admin')->delete(route('ss.teachers.destroy', $teacher));

    $response->assertRedirect(route('ss.teachers.index'));
    $this->assertDatabaseMissing('sunday_school_teachers', ['id' => $teacher->id]);
    $this->assertDatabaseHas('sunday_school_classes', [
        'id' => $class->id,
        'teacher_id' => null,
        'assistant_teacher_id' => null,
    ]);
});

test('ss teacher cannot access teachers list', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);

    $response = $this->actingAs($admin, 'admin')->get(route('ss.teachers.index'));

    $response->assertForbidden();
});
