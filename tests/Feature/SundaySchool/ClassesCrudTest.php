<?php

use App\Models\Admin;
use App\Models\SundaySchoolClass;
use App\Models\SundaySchoolStudent;
use App\Models\SundaySchoolTeacher;

test('admin can list sunday school classes', function () {
    $admin = Admin::factory()->create(['role' => 'super_admin']);

    SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-LIST-01',
        'class_name' => 'Listed Class',
        'max_capacity' => 40,
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('ss.classes.index'));

    $response->assertOk();
    $response->assertSee('Listed Class');
    $response->assertSee('Total:');
    $response->assertDontSee('No classes found for this view.');
});

test('classes list shows teacher assigned via teacher class_id', function () {
    $admin = Admin::factory()->create(['role' => 'super_admin']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-LIST-02',
        'class_name' => 'Class With Teacher',
        'max_capacity' => 40,
        'status' => 'active',
    ]);

    SundaySchoolTeacher::query()->create([
        'teacher_code' => 'SS-TCH-LIST-01',
        'full_name' => 'Assigned Teacher',
        'class_id' => $class->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('ss.classes.index'));

    $response->assertOk();
    $response->assertSee('Class With Teacher');
    $response->assertSee('Assigned Teacher');
});

test('admin can create a sunday school class', function () {
    $admin = Admin::factory()->create(['role' => 'super_admin']);

    $response = $this->actingAs($admin, 'admin')->post(route('ss.classes.store'), [
        'class_name' => 'Beginner Class',
        'age_range' => '3-5',
        'max_capacity' => 40,
        'status' => 'active',
    ]);

    $response->assertRedirect(route('ss.classes.index'));
    $this->assertDatabaseHas('sunday_school_classes', [
        'class_name' => 'Beginner Class',
        'age_range' => '3-5',
        'max_capacity' => 40,
        'status' => 'active',
    ]);
});

test('admin can update a sunday school class', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00001',
        'class_name' => 'Old Name',
        'max_capacity' => 50,
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'admin')->put(route('ss.classes.update', $class), [
        'class_name' => 'Updated Name',
        'age_range' => '6-9',
        'max_capacity' => 45,
        'status' => 'active',
    ]);

    $response->assertRedirect(route('ss.classes.index'));
    $this->assertDatabaseHas('sunday_school_classes', [
        'id' => $class->id,
        'class_name' => 'Updated Name',
        'age_range' => '6-9',
        'max_capacity' => 45,
    ]);
});

test('admin can archive a sunday school class', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00002',
        'class_name' => 'Archive Me',
        'max_capacity' => 50,
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'admin')->post(route('ss.classes.archive', $class));

    $response->assertRedirect(route('ss.classes.index'));
    $this->assertDatabaseHas('sunday_school_classes', [
        'id' => $class->id,
        'status' => 'archived',
    ]);
});

test('admin cannot delete a class with enrolled students', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00003',
        'class_name' => 'Has Students',
        'max_capacity' => 50,
        'status' => 'active',
    ]);

    SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00001',
        'full_name' => 'Test Student',
        'class_id' => $class->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'admin')->delete(route('ss.classes.destroy', $class));

    $response->assertRedirect(route('ss.classes.index'));
    $response->assertSessionHasErrors('delete');
    $this->assertDatabaseHas('sunday_school_classes', ['id' => $class->id]);
});

test('ss teacher cannot create classes', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);

    $response = $this->actingAs($admin, 'admin')->get(route('ss.classes.create'));

    $response->assertForbidden();
});

test('seed defaults creates missing classes only', function () {
    $admin = Admin::factory()->create(['role' => 'super_admin']);

    SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00099',
        'class_name' => 'Beginner Class',
        'age_range' => '3-5',
        'max_capacity' => 50,
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'admin')->post(route('ss.classes.seed'));

    $response->assertRedirect(route('ss.classes.index'));
    expect(SundaySchoolClass::query()->where('class_name', 'Beginner Class')->count())->toBe(1);
    expect(SundaySchoolClass::query()->where('class_name', 'Youth Class')->exists())->toBeTrue();
});
