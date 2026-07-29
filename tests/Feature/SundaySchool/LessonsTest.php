<?php

use App\Models\Admin;
use App\Models\SundaySchoolClass;
use App\Models\SundaySchoolLesson;
use App\Models\SundaySchoolTeacher;

test('admin can list curriculum lessons', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00400',
        'class_name' => 'Curriculum Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);

    SundaySchoolLesson::query()->create([
        'class_id' => $class->id,
        'lesson_title' => 'Faith of Abraham',
        'lesson_date' => '2026-07-15',
        'bible_text' => 'Genesis 12:1-4',
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('ss.lessons.index', [
        'class_id' => $class->id,
    ]));

    $response->assertOk();
    $response->assertSee('Faith of Abraham');
    $response->assertSee('Genesis 12:1-4');
});

test('admin can create and update a lesson', function () {
    $admin = Admin::factory()->create(['role' => 'super_admin']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00401',
        'class_name' => 'Lesson Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);

    $create = $this->actingAs($admin, 'admin')->post(route('ss.lessons.store'), [
        'lesson_title' => 'The Good Shepherd',
        'lesson_date' => '2026-07-20',
        'class_id' => $class->id,
        'bible_text' => 'John 10:11',
        'golden_text' => 'I am the good shepherd',
        'objectives' => 'Understand Christ as shepherd',
        'teaching_notes' => 'Use visual aids',
        'activities' => 'Draw sheep',
    ]);

    $create->assertRedirect(route('ss.lessons.index'));
    $this->assertDatabaseHas('sunday_school_lessons', [
        'lesson_title' => 'The Good Shepherd',
        'class_id' => $class->id,
        'bible_text' => 'John 10:11',
        'created_by' => $admin->id,
    ]);

    $lesson = SundaySchoolLesson::query()->where('lesson_title', 'The Good Shepherd')->firstOrFail();

    $update = $this->actingAs($admin, 'admin')->put(route('ss.lessons.update', $lesson), [
        'lesson_title' => 'The Good Shepherd (Updated)',
        'lesson_date' => '2026-07-21',
        'class_id' => $class->id,
        'bible_text' => 'John 10:11-15',
    ]);

    $update->assertRedirect(route('ss.lessons.show', $lesson));
    $this->assertDatabaseHas('sunday_school_lessons', [
        'id' => $lesson->id,
        'lesson_title' => 'The Good Shepherd (Updated)',
        'bible_text' => 'John 10:11-15',
    ]);
});

test('ss teacher only sees lessons for assigned classes and global lessons', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);
    $mine = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00410',
        'class_name' => 'My Lesson Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    $other = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00411',
        'class_name' => 'Other Lesson Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);

    SundaySchoolTeacher::query()->create([
        'teacher_code' => 'SS-TCH-00410',
        'full_name' => 'Curriculum Teacher',
        'admin_id' => $admin->id,
        'class_id' => $mine->id,
        'status' => 'active',
    ]);

    SundaySchoolLesson::query()->create([
        'class_id' => null,
        'lesson_title' => 'Church-wide Lesson',
        'lesson_date' => '2026-07-10',
        'created_by' => $admin->id,
    ]);
    SundaySchoolLesson::query()->create([
        'class_id' => $mine->id,
        'lesson_title' => 'Visible Class Lesson',
        'lesson_date' => '2026-07-11',
        'created_by' => $admin->id,
    ]);
    SundaySchoolLesson::query()->create([
        'class_id' => $other->id,
        'lesson_title' => 'Hidden Class Lesson',
        'lesson_date' => '2026-07-12',
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('ss.lessons.index'));

    $response->assertOk();
    $response->assertSee('Church-wide Lesson');
    $response->assertSee('Visible Class Lesson');
    $response->assertDontSee('Hidden Class Lesson');
});

test('only admins can delete lessons', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $teacherAdmin = Admin::factory()->create(['role' => 'ss_teacher']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00420',
        'class_name' => 'Delete Lesson Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);

    SundaySchoolTeacher::query()->create([
        'teacher_code' => 'SS-TCH-00420',
        'full_name' => 'Delete Teacher',
        'admin_id' => $teacherAdmin->id,
        'class_id' => $class->id,
        'status' => 'active',
    ]);

    $lesson = SundaySchoolLesson::query()->create([
        'class_id' => $class->id,
        'lesson_title' => 'Delete Me',
        'lesson_date' => '2026-07-20',
        'created_by' => $admin->id,
    ]);

    $this->actingAs($teacherAdmin, 'admin')
        ->delete(route('ss.lessons.destroy', $lesson))
        ->assertForbidden();

    $this->actingAs($admin, 'admin')
        ->delete(route('ss.lessons.destroy', $lesson))
        ->assertRedirect(route('ss.lessons.index'));

    $this->assertDatabaseMissing('sunday_school_lessons', ['id' => $lesson->id]);
});

test('finance role cannot access curriculum', function () {
    $admin = Admin::factory()->create(['role' => 'finance']);

    $this->actingAs($admin, 'admin')->get(route('ss.lessons.index'))->assertForbidden();
});
