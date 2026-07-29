<?php

use App\Models\Admin;
use App\Models\RegistrationPortal;
use App\Models\Registrant;

test('admin can view registration portals dashboard', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    RegistrationPortal::factory()->create(['event_name' => 'Leadership Summit']);
    Registrant::factory()->create(['gender' => 'Male', 'age_group' => 'Adult']);
    Registrant::factory()->create(['gender' => 'Female', 'age_group' => 'Teen']);
    Registrant::factory()->create(['gender' => 'Male', 'age_group' => 'Child']);

    $response = $this->actingAs($admin, 'admin')->get(route('registration-portals.index'));

    $response->assertOk();
    $response->assertSee('Leadership Summit');
    $response->assertSee('Registration Portals');
    $response->assertSee('Male');
    $response->assertSee('Female');
    $response->assertSee('Children');
    $response->assertSee('Teens');
});

test('admin can create a registration portal', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin, 'admin')->post(route('registration-portals.store'), [
        'event_name' => 'Annual Convention 2026',
        'slug' => 'annual-convention-2026',
        'status' => 'draft',
        'template' => 'quick',
        'venue' => 'Main Auditorium',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('registration_portals', [
        'event_name' => 'Annual Convention 2026',
        'slug' => 'annual-convention-2026',
        'status' => 'draft',
    ]);

    $portalId = RegistrationPortal::query()->where('slug', 'annual-convention-2026')->value('id');
    expect(\Illuminate\Support\Facades\DB::table('registration_fields')->where('portal_id', $portalId)->where('field_key', 'age_group')->exists())->toBeTrue();
});

test('admin can approve a registrant', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $portal = RegistrationPortal::factory()->create(['status' => 'open']);
    $registrant = Registrant::factory()->create([
        'portal_id' => $portal->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin, 'admin')->post(
        route('registration-portals.registrants.update-status', [$portal, $registrant]),
        ['status' => 'approved'],
    );

    $response->assertRedirect();
    $this->assertDatabaseHas('registrants', [
        'id' => $registrant->id,
        'status' => 'approved',
        'approved_by' => $admin->id,
    ]);
});

test('admin can open a registration portal', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $portal = RegistrationPortal::factory()->create(['status' => 'draft']);

    $this->actingAs($admin, 'admin')
        ->post(route('registration-portals.update-status', $portal), ['status' => 'open'])
        ->assertRedirect();

    expect(RegistrationPortal::query()->find($portal->id)?->status)->toBe('open');
});

test('finance role cannot access registration portals', function () {
    $admin = Admin::factory()->create(['role' => 'finance']);

    $this->actingAs($admin, 'admin')->get(route('registration-portals.index'))->assertForbidden();
});

test('admin can preview draft registration form at public url', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $portal = RegistrationPortal::factory()->create([
        'event_name' => 'Draft Retreat',
        'slug' => 'draft-retreat',
        'status' => 'draft',
    ]);

    \Illuminate\Support\Facades\DB::table('registration_fields')->insert([
        'portal_id' => $portal->id,
        'field_key' => 'full_name',
        'field_type' => 'text',
        'label' => 'Full Name',
        'is_required' => 1,
        'field_width' => 'full',
        'options_json' => json_encode([]),
        'validation_rules' => json_encode([]),
        'sort_order' => 0,
        'created_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('public.registration-portal', 'draft-retreat'))
        ->assertOk()
        ->assertSee('Draft Retreat')
        ->assertSee('Draft preview')
        ->assertSee('Full Name');
});

test('guest cannot view draft registration form', function () {
    RegistrationPortal::factory()->create([
        'slug' => 'secret-draft',
        'status' => 'draft',
    ]);

    $this->get(route('public.registration-portal', 'secret-draft'))->assertNotFound();
});

test('guest can view open registration form', function () {
    $portal = RegistrationPortal::factory()->create([
        'event_name' => 'Open Summit',
        'slug' => 'open-summit',
        'status' => 'open',
    ]);

    \Illuminate\Support\Facades\DB::table('registration_fields')->insert([
        'portal_id' => $portal->id,
        'field_key' => 'email',
        'field_type' => 'email',
        'label' => 'Email Address',
        'is_required' => 1,
        'field_width' => 'full',
        'options_json' => json_encode([]),
        'validation_rules' => json_encode([]),
        'sort_order' => 0,
        'created_at' => now(),
    ]);

    $this->get(route('public.registration-portal', 'open-summit'))
        ->assertOk()
        ->assertSee('Open Summit')
        ->assertSee('Email Address')
        ->assertDontSee('Draft preview');
});

test('admin can create portal with custom fields from builder', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')->post(route('registration-portals.store'), [
        'event_name' => 'Custom Fields Event',
        'slug' => 'custom-fields-event',
        'status' => 'open',
        'template' => 'quick',
        'fields' => [
            ['field_key' => 'full_name', 'field_type' => 'text', 'label' => 'Full Name', 'is_required' => true],
            ['field_key' => 'favorite_song', 'field_type' => 'text', 'label' => 'Favorite Song', 'is_required' => false],
            ['field_key' => 'gender', 'field_type' => 'radio', 'label' => 'Gender', 'is_required' => true, 'options' => ['Male', 'Female']],
        ],
    ])->assertRedirect();

    $portalId = RegistrationPortal::query()->where('slug', 'custom-fields-event')->value('id');
    expect(\Illuminate\Support\Facades\DB::table('registration_fields')->where('portal_id', $portalId)->where('field_key', 'favorite_song')->exists())->toBeTrue();
});

test('guest can submit registration on an open portal', function () {
    $portal = RegistrationPortal::factory()->create([
        'event_name' => 'Submit Summit',
        'slug' => 'submit-summit',
        'status' => 'open',
        'registration_settings' => [
            'registration_type' => 'free',
            'confirmation_method' => 'automatic',
            'duplicate_email' => true,
            'duplicate_phone' => true,
        ],
    ]);

    \Illuminate\Support\Facades\DB::table('registration_fields')->insert([
        [
            'portal_id' => $portal->id,
            'field_key' => 'full_name',
            'field_type' => 'text',
            'label' => 'Full Name',
            'is_required' => 1,
            'field_width' => 'full',
            'options_json' => json_encode([]),
            'validation_rules' => json_encode([]),
            'sort_order' => 0,
            'created_at' => now(),
        ],
        [
            'portal_id' => $portal->id,
            'field_key' => 'email',
            'field_type' => 'email',
            'label' => 'Email Address',
            'is_required' => 1,
            'field_width' => 'full',
            'options_json' => json_encode([]),
            'validation_rules' => json_encode([]),
            'sort_order' => 1,
            'created_at' => now(),
        ],
    ]);

    $response = $this->postJson(route('public.registration-portal.submit'), [
        'slug' => 'submit-summit',
        'full_name' => 'Ada Okoye',
        'email' => 'ada@example.com',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('registrant.full_name', 'Ada Okoye');

    $this->assertDatabaseHas('registrants', [
        'portal_id' => $portal->id,
        'full_name' => 'Ada Okoye',
        'email' => 'ada@example.com',
    ]);
});

test('guest cannot submit registration on a draft portal', function () {
    RegistrationPortal::factory()->create([
        'slug' => 'closed-submit',
        'status' => 'draft',
    ]);

    $this->postJson(route('public.registration-portal.submit'), [
        'slug' => 'closed-submit',
        'full_name' => 'Someone',
    ])->assertStatus(422);
});

test('admin can open full registrant details', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $portal = RegistrationPortal::factory()->create(['status' => 'open']);
    $registrant = Registrant::factory()->create([
        'portal_id' => $portal->id,
        'full_name' => 'Chioma Ade',
        'email' => 'chioma@example.com',
        'status' => 'pending',
    ]);

    \Illuminate\Support\Facades\DB::table('registrant_answers')->insert([
        'registrant_id' => $registrant->id,
        'field_id' => null,
        'field_key' => 'church',
        'answer_text' => 'AG Ikenegbu',
        'answer_file' => null,
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('registration-portals.registrants.show', [$portal, $registrant->id]))
        ->assertOk()
        ->assertSee('Chioma Ade')
        ->assertSee('chioma@example.com')
        ->assertSee('AG Ikenegbu')
        ->assertSee('Form answers');
});

test('admin can delete a registrant', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $portal = RegistrationPortal::factory()->create(['status' => 'open']);
    $registrant = Registrant::factory()->create([
        'portal_id' => $portal->id,
        'full_name' => 'To Delete',
    ]);

    $this->actingAs($admin, 'admin')
        ->delete(route('registration-portals.registrants.destroy', [$portal, $registrant->id]))
        ->assertRedirect(route('registration-portals.registrants.index', $portal));

    $this->assertDatabaseMissing('registrants', ['id' => $registrant->id]);
});

test('admin can delete a registration portal and related data', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $portal = RegistrationPortal::factory()->create([
        'event_name' => 'Form To Remove',
        'status' => 'open',
    ]);
    $fieldId = \Illuminate\Support\Facades\DB::table('registration_fields')->insertGetId([
        'portal_id' => $portal->id,
        'field_key' => 'full_name',
        'field_type' => 'text',
        'label' => 'Full Name',
        'is_required' => 1,
        'field_width' => 'full',
        'options_json' => json_encode([]),
        'validation_rules' => json_encode([]),
        'sort_order' => 0,
        'created_at' => now(),
    ]);
    $registrant = Registrant::factory()->create([
        'portal_id' => $portal->id,
        'full_name' => 'Will Be Removed',
    ]);
    \Illuminate\Support\Facades\DB::table('registrant_answers')->insert([
        'registrant_id' => $registrant->id,
        'field_id' => $fieldId,
        'field_key' => 'full_name',
        'answer_text' => 'Will Be Removed',
        'answer_file' => null,
    ]);

    $this->actingAs($admin, 'admin')
        ->delete(route('registration-portals.destroy', $portal))
        ->assertRedirect(route('registration-portals.index'));

    $this->assertDatabaseMissing('registration_portals', ['id' => $portal->id]);
    $this->assertDatabaseMissing('registration_fields', ['id' => $fieldId]);
    $this->assertDatabaseMissing('registrants', ['id' => $registrant->id]);
    $this->assertDatabaseMissing('registrant_answers', ['registrant_id' => $registrant->id]);
});

test('admin can open printable registration qr page', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $portal = RegistrationPortal::factory()->create([
        'event_name' => 'QR Summit',
        'slug' => 'qr-summit',
        'status' => 'open',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('registration-portals.qr', $portal))
        ->assertOk()
        ->assertSee('QR Summit')
        ->assertSee('Scan to Register')
        ->assertSee('/register/qr-summit');
});

test('public qr endpoint returns svg for portal slug', function () {
    RegistrationPortal::factory()->create([
        'slug' => 'qr-image-event',
        'status' => 'open',
    ]);

    $this->get(route('public.registration-portal.qr', ['slug' => 'qr-image-event']))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml; charset=utf-8');
});
