<?php

use App\Models\Admin;
use Illuminate\Support\Facades\DB;

test('admin can view laravel settings center', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);

    DB::table('platform_setting_groups')->updateOrInsert(
        ['group_key' => 'church'],
        [
            'settings' => json_encode([
                'name' => 'Assemblies of God — Ikenebgu',
                'short_name' => 'AGC IKENEGBU',
            ]),
            'updated_by' => $admin->id,
            'updated_at' => now(),
        ]
    );

    $response = $this->actingAs($admin, 'admin')->get(route('settings.index'));

    $response->assertOk();
    $response->assertSee('Settings & Administration Center');
    $response->assertSee('Assemblies of God — Ikenebgu');
    $response->assertSee('Church Profile');
    $response->assertSee('Website Design');
    $response->assertDontSee('AG_IKENEGBU_CHURCH_WEBSITE/portal/settings');
});

test('admin can update church settings group', function () {
    $admin = Admin::factory()->create(['role' => 'church_administrator']);

    $this->actingAs($admin, 'admin')->post(route('settings.group.update'), [
        'group' => 'church',
        'name' => 'AGC Ikenegbu Updated',
        'short_name' => 'AGI',
        'city' => 'Owerri',
        'state' => 'Imo',
    ])->assertRedirect();

    $raw = DB::table('platform_setting_groups')->where('group_key', 'church')->value('settings');
    $decoded = json_decode((string) $raw, true);

    expect($decoded['name'] ?? null)->toBe('AGC Ikenegbu Updated');
    expect($decoded['short_name'] ?? null)->toBe('AGI');
});

test('admin can upload church logo instead of typing a path', function () {
    $admin = Admin::factory()->create(['role' => 'admin']);
    $file = Illuminate\Http\UploadedFile::fake()->image('church-logo.png', 200, 120);

    $this->actingAs($admin, 'admin')
        ->post(route('settings.group.update'), [
            'group' => 'church',
            'name' => 'AGC Ikenegbu',
            'short_name' => 'AGI',
            'logo' => $file,
        ])
        ->assertRedirect(route('settings.index', ['tab' => 'church']));

    $raw = DB::table('platform_setting_groups')->where('group_key', 'church')->value('settings');
    $decoded = json_decode((string) $raw, true);
    $logoPath = (string) ($decoded['logo_path'] ?? '');

    expect($logoPath)->toStartWith('uploads/settings/church/');
    expect(is_file(public_path('site/'.$logoPath)))->toBeTrue();

    $this->actingAs($admin, 'admin')
        ->get(route('settings.index', ['tab' => 'church']))
        ->assertOk()
        ->assertSee('Church logo')
        ->assertDontSee('Logo Path');
});

test('content editor can update personal preferences but not platform groups', function () {
    $admin = Admin::factory()->create([
        'role' => 'content_editor',
        'full_name' => 'Editor One',
        'ui_theme' => 'gold',
        'ui_mode' => 'dark',
    ]);

    $this->actingAs($admin, 'admin')->post(route('settings.preferences.update'), [
        'full_name' => 'Editor Updated',
        'phone' => '08011112222',
        'ui_theme' => 'blue',
        'ui_mode' => 'light',
    ])->assertRedirect(route('settings.index', ['tab' => 'preferences']));

    $this->assertDatabaseHas('admins', [
        'id' => $admin->id,
        'full_name' => 'Editor Updated',
        'ui_theme' => 'blue',
        'ui_mode' => 'light',
    ]);

    $this->actingAs($admin, 'admin')->post(route('settings.group.update'), [
        'group' => 'church',
        'name' => 'Should Fail',
    ])->assertForbidden();
});
