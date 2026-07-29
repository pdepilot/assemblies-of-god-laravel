<?php

use App\Models\Admin;
use App\Models\SundaySchoolClass;
use App\Models\SundaySchoolStudent;
use Illuminate\Support\Facades\DB;

test('admin can register and list visitors', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00400',
        'class_name' => 'Visitor Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'admin')->post(route('ss.visitors.store'), [
        'visitor_name' => 'Jane Guest',
        'phone' => '08012345678',
        'class_id' => $class->id,
        'visit_date' => '2026-07-20',
        'follow_up_status' => 'pending',
        'filter_from' => '2026-07-01',
        'filter_to' => '2026-07-31',
    ]);

    $response->assertRedirect(route('ss.visitors.index', [
        'class_id' => $class->id,
        'from' => '2026-07-01',
        'to' => '2026-07-31',
    ]));

    $this->assertDatabaseHas('sunday_school_visitors', [
        'visitor_name' => 'Jane Guest',
        'class_id' => $class->id,
    ]);

    $list = $this->actingAs($admin, 'admin')->get(route('ss.visitors.index', [
        'from' => '2026-07-01',
        'to' => '2026-07-31',
    ]));

    $list->assertOk();
    $list->assertSee('Jane Guest');
});

test('admin can bulk promote a class', function () {
    $admin = Admin::factory()->create(['role' => 'super_admin']);
    $from = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00410',
        'class_name' => 'Primary A',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    $to = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00411',
        'class_name' => 'Primary B',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    $student = SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00410',
        'full_name' => 'Promote Me',
        'class_id' => $from->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'admin')->post(route('ss.promotions.bulk'), [
        'from_class_id' => $from->id,
        'to_class_id' => $to->id,
    ]);

    $response->assertRedirect(route('ss.promotions.index'));
    $response->assertSessionHas('status');

    expect(SundaySchoolStudent::query()->find($student->id)?->class_id)->toBe($to->id);
    $this->assertDatabaseHas('sunday_school_promotions', [
        'student_id' => $student->id,
        'from_class_id' => $from->id,
        'to_class_id' => $to->id,
        'promotion_type' => 'promote',
    ]);
});

test('admin can evaluate promotion year', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00420',
        'class_name' => 'Eval Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00420',
        'full_name' => 'Eval Student',
        'class_id' => $class->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin, 'admin')->post(route('ss.promotions.evaluate'), [
        'year' => 2026,
    ]);

    $response->assertRedirect(route('ss.promotions.index', ['year' => 2026]));
    $this->assertDatabaseHas('sunday_school_promotion_evaluations', [
        'promotion_year' => 2026,
    ]);
});

test('admin can generate and approve awards', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00430',
        'class_name' => 'Award Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00430',
        'full_name' => 'Award Student',
        'class_id' => $class->id,
        'status' => 'active',
    ]);

    $generate = $this->actingAs($admin, 'admin')->post(route('ss.awards.generate'), [
        'period_type' => 'annual',
    ]);

    $generate->assertRedirect(route('ss.awards.index'));
    expect(DB::table('sunday_school_awards')->count())->toBeGreaterThan(0);

    $awardId = (int) DB::table('sunday_school_awards')->value('id');
    $approve = $this->actingAs($admin, 'admin')->post(route('ss.awards.approve', $awardId));
    $approve->assertRedirect();

    $this->assertDatabaseHas('sunday_school_awards', [
        'id' => $awardId,
        'status' => 'approved',
    ]);
});

test('admin can queue and process notifications', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);

    $queue = $this->actingAs($admin, 'admin')->post(route('ss.notifications.store'), [
        'subject' => 'Test Announcement',
        'body' => 'Hello parents',
        'recipient_type' => 'all',
        'channel' => 'email',
    ]);

    $queue->assertRedirect(route('ss.notifications.index'));
    $this->assertDatabaseHas('sunday_school_notifications', [
        'subject' => 'Test Announcement',
        'status' => 'pending',
    ]);

    $send = $this->actingAs($admin, 'admin')->post(route('ss.notifications.send'));
    $send->assertRedirect(route('ss.notifications.index'));

    $this->assertDatabaseHas('sunday_school_notifications', [
        'subject' => 'Test Announcement',
        'status' => 'sent',
    ]);
});

test('superintendent can search class offerings', function () {
    $admin = Admin::factory()->create(['role' => 'ss_superintendent']);
    $class = SundaySchoolClass::query()->create([
        'class_code' => 'SS-CLS-00440',
        'class_name' => 'Search Offering Class',
        'max_capacity' => 50,
        'status' => 'active',
    ]);
    $student = SundaySchoolStudent::query()->create([
        'student_code' => 'SS-STU-00440',
        'full_name' => 'Search Giver',
        'class_id' => $class->id,
        'status' => 'active',
    ]);

    DB::table('sunday_school_offerings')->insert([
        'student_id' => $student->id,
        'class_id' => $class->id,
        'offering_date' => '2026-07-10',
        'amount' => 300,
        'recorded_by' => $admin->id,
        'created_at' => now(),
    ]);

    $response = $this->actingAs($admin, 'admin')->get(route('ss.superintendent.offerings', [
        'q' => 'Search Offering',
        'from' => '2026-07-01',
        'to' => '2026-07-31',
    ]));

    $response->assertOk();
    $response->assertSee('Search Offering Class');
    $response->assertSee('300');
});

test('finance role cannot access promotions or superintendent offering search', function () {
    $admin = Admin::factory()->create(['role' => 'finance']);

    $this->actingAs($admin, 'admin')->get(route('ss.promotions.index'))->assertForbidden();
    $this->actingAs($admin, 'admin')->get(route('ss.superintendent.offerings'))->assertForbidden();
});

test('ss teacher can access visitors but not awards', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);

    $this->actingAs($admin, 'admin')->get(route('ss.visitors.index'))->assertOk();
    $this->actingAs($admin, 'admin')->get(route('ss.awards.index'))->assertForbidden();
    $this->actingAs($admin, 'admin')->get(route('ss.certificates.index'))->assertForbidden();
});
