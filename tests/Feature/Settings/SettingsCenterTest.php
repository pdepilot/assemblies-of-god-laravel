<?php

use App\Models\Admin;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

test('admin church profile email phone name and address appear on public pages', function () {
    $admin = Admin::factory()->create(['role' => 'church_administrator']);

    if (! Schema::hasTable('contact_settings')) {
        Schema::create('contact_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('church_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone_display')->nullable();
            $table->string('address_line1')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('facebook_url')->nullable();
            $table->timestamps();
        });
    }

    DB::table('contact_settings')->insert([
        'church_name' => 'Stale Church Name',
        'email' => 'old@email.com',
        'phone' => '+2348000000000',
        'phone_display' => '08000000000',
        'address_line1' => 'Old Address',
        'city' => 'Owerri',
        'state' => 'Imo',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')->post(route('settings.group.update'), [
        'group' => 'church',
        'name' => 'AGC Ikenegbu Live Name',
        'short_name' => 'AGI Live',
        'email' => 'new@email.com',
        'phone' => '08011112222',
        'address' => '12 New Church Road',
        'city' => 'Owerri',
        'state' => 'Imo',
        'social_facebook' => 'https://facebook.com/agcikenegbu',
    ])->assertRedirect(route('settings.index', ['tab' => 'church']));

    $raw = DB::table('platform_setting_groups')->where('group_key', 'church')->value('settings');
    $decoded = json_decode((string) $raw, true);
    expect($decoded['email'] ?? null)->toBe('new@email.com');
    expect($decoded['phone'] ?? null)->toBe('08011112222');
    expect($decoded['name'] ?? null)->toBe('AGC Ikenegbu Live Name');
    expect($decoded['address'] ?? null)->toBe('12 New Church Road');

    expect(DB::table('contact_settings')->orderBy('id')->value('email'))->toBe('new@email.com');

    foreach (['/', '/about', '/contact', '/event'] as $path) {
        $page = $this->get($path);
        $page->assertOk();
        $page->assertSee('new@email.com', false);
        $page->assertDontSee('old@email.com', false);
        $page->assertSee('08011112222', false);
        $page->assertSee('AGC Ikenegbu Live Name', false);
        $page->assertSee('12 New Church Road', false);
    }

    $this->get('/about')->assertSee('new@email.com', false);
});

test('church profile save does not run an unbounded query burst', function () {
    $admin = Admin::factory()->create(['role' => 'church_administrator']);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $started = hrtime(true);
    $this->actingAs($admin, 'admin')->post(route('settings.group.update'), [
        'group' => 'church',
        'name' => 'AGC Query Budget',
        'short_name' => 'AGI',
        'email' => 'budget@email.com',
    ])->assertRedirect();
    $elapsedMs = (hrtime(true) - $started) / 1e6;

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect(count($queries))->toBeLessThan(80);
    expect($elapsedMs)->toBeLessThan(5000);
});

test('church profile save succeeds when contact_settings table is missing', function () {
    $admin = Admin::factory()->create(['role' => 'church_administrator']);

    Schema::dropIfExists('contact_settings');

    $this->actingAs($admin, 'admin')->post(route('settings.group.update'), [
        'group' => 'church',
        'name' => 'No Contact Table Church',
        'email' => 'notable@email.com',
        'phone' => '08033334444',
    ])->assertRedirect(route('settings.index', ['tab' => 'church']));

    $raw = DB::table('platform_setting_groups')->where('group_key', 'church')->value('settings');
    $decoded = json_decode((string) $raw, true);
    expect($decoded['email'] ?? null)->toBe('notable@email.com');

    $this->get('/')->assertOk()->assertSee('notable@email.com', false);
});

test('church profile save inserts one contact_settings row and does not duplicate on update', function () {
    $admin = Admin::factory()->create(['role' => 'church_administrator']);

    Schema::dropIfExists('contact_settings');
    Schema::create('contact_settings', function (Blueprint $table) {
        $table->increments('id');
        $table->string('church_name')->nullable();
        $table->string('email')->nullable();
        $table->string('phone')->nullable();
        $table->string('phone_display')->nullable();
        $table->string('whatsapp_url')->nullable();
        $table->timestamps();
    });

    DB::table('contact_settings')->insert([
        'church_name' => 'Keep Me',
        'email' => 'old@email.com',
        'whatsapp_url' => 'https://wa.me/keep',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')->post(route('settings.group.update'), [
        'group' => 'church',
        'name' => 'Synced Once',
        'email' => 'first@email.com',
    ])->assertRedirect();

    $this->actingAs($admin, 'admin')->post(route('settings.group.update'), [
        'group' => 'church',
        'name' => 'Synced Twice',
        'email' => 'second@email.com',
    ])->assertRedirect();

    expect((int) DB::table('contact_settings')->count())->toBe(1);
    $row = DB::table('contact_settings')->orderBy('id')->first();
    expect($row->email)->toBe('second@email.com');
    expect($row->whatsapp_url)->toBe('https://wa.me/keep');

    $mail = app(\App\Services\Contact\ContactMailService::class)->publicSettings();
    expect($mail['email'])->toBe('second@email.com');
});

test('public homepage reads church overlay with a single platform church query', function () {
    $admin = Admin::factory()->create(['role' => 'church_administrator']);

    $this->actingAs($admin, 'admin')->post(route('settings.group.update'), [
        'group' => 'church',
        'email' => 'once@email.com',
        'name' => 'Once Church',
    ])->assertRedirect();

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->get('/')->assertOk()->assertSee('once@email.com', false);
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $churchJsonQueries = collect($queries)->filter(
        fn (array $query): bool => str_contains((string) $query['query'], 'platform_setting_groups')
            && str_contains(strtolower(json_encode($query['bindings'] ?? [])), 'church')
    );

    expect($churchJsonQueries->count())->toBe(1);
});
