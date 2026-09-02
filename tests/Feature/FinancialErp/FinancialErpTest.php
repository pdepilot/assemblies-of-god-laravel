<?php

use App\Models\Admin;
use Illuminate\Support\Facades\DB;

test('finance admin can view financial erp dashboard', function () {
    $admin = Admin::factory()->create(['role' => 'finance']);

    $response = $this->actingAs($admin, 'admin')->get(route('financial-erp.dashboard'));

    $response->assertOk();
    $response->assertSee('Financial ERP');
    $response->assertSee('Chart of Accounts');
});

test('finance admin can create and post a balanced journal', function () {
    $admin = Admin::factory()->create(['role' => 'finance']);
    $cashId = DB::table('erp_accounts')->where('code', '1000')->value('id');
    $titheId = DB::table('erp_accounts')->where('code', '4000')->value('id');

    $response = $this->actingAs($admin, 'admin')->post(route('financial-erp.journals.store'), [
        'header' => [
            'journal_date' => '2026-07-20',
            'memo' => 'Test tithe journal',
        ],
        'lines' => [
            ['account_id' => $cashId, 'debit' => 5000, 'credit' => 0],
            ['account_id' => $titheId, 'debit' => 0, 'credit' => 5000],
        ],
        'post' => '1',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('erp_journals', [
        'memo' => 'Test tithe journal',
        'status' => 'posted',
        'total_debit' => 5000,
        'total_credit' => 5000,
    ]);
});

test('finance admin can record erp income with receipt sync', function () {
    $admin = Admin::factory()->create(['role' => 'finance']);
    $categoryId = DB::table('erp_income_categories')->where('code', 'TITH')->value('id');

    $this->actingAs($admin, 'admin')->post(route('financial-erp.income.store'), [
        'income_date' => '2026-07-20',
        'category_id' => $categoryId,
        'amount' => 25000,
        'member_name' => 'Brother John',
    ])->assertRedirect();

    $this->assertDatabaseHas('erp_income', ['member_name' => 'Brother John', 'amount' => 25000]);
    $this->assertDatabaseHas('erp_receipts', ['payer_name' => 'Brother John', 'amount' => 25000]);
});

test('erp income includes dedicated offering categories', function () {
    $admin = Admin::factory()->create(['role' => 'finance']);

    $expected = [
        'Covenant Offering',
        'Special Offering',
        'Cross Over Support',
        'Faith Clinic',
        'Cross Over Seed',
        'AG Care',
        'General Council Support',
        'District Support Offering',
        'First Fruit',
        'Harvest Proceed',
        'Welfare Offering',
        'Altar Seed',
        'Thanksgiving Offering',
        'Testimony Offering',
    ];

    foreach ($expected as $name) {
        expect(DB::table('erp_income_categories')->where('name', $name)->where('is_active', true)->exists())->toBeTrue();
    }

    $response = $this->actingAs($admin, 'admin')->get(route('financial-erp.income.create'));
    $response->assertOk();
    foreach ($expected as $name) {
        $response->assertSee($name);
    }

    $categoryId = DB::table('erp_income_categories')->where('code', 'THGV')->value('id');
    $this->actingAs($admin, 'admin')->post(route('financial-erp.income.store'), [
        'income_date' => '2026-09-02',
        'category_id' => $categoryId,
        'amount' => 10000,
        'member_name' => 'Sister Grace',
    ])->assertRedirect();

    $this->assertDatabaseHas('erp_income', [
        'category_id' => $categoryId,
        'member_name' => 'Sister Grace',
        'amount' => 10000,
    ]);
});

test('finance admin can record expense with approval queue', function () {
    $admin = Admin::factory()->create(['role' => 'finance']);
    $categoryId = DB::table('erp_expense_categories')->where('code', 'UTL')->value('id');

    $this->actingAs($admin, 'admin')->post(route('financial-erp.expenses.store'), [
        'expense_date' => '2026-07-20',
        'category_id' => $categoryId,
        'amount' => 15000,
        'notes' => 'Electricity bill',
    ])->assertRedirect();

    $expenseId = DB::table('erp_expenses')->where('notes', 'Electricity bill')->value('id');
    $this->assertDatabaseHas('erp_approvals', [
        'entity_type' => 'expense',
        'entity_id' => $expenseId,
        'status' => 'pending',
    ]);
});

test('finance admin can manually add an income category', function () {
    $admin = Admin::factory()->create(['role' => 'finance']);

    $create = $this->actingAs($admin, 'admin')->get(route('financial-erp.income.categories.create'));
    $create->assertOk();
    $create->assertSee('Add Income Category');

    $response = $this->actingAs($admin, 'admin')->post(route('financial-erp.income.categories.store'), [
        'name' => 'Youth Rally Offering',
        'code' => 'YRAL',
    ]);

    $response->assertRedirect(route('financial-erp.income.categories.index'));
    $this->assertDatabaseHas('erp_income_categories', [
        'name' => 'Youth Rally Offering',
        'code' => 'YRAL',
        'is_active' => 1,
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('financial-erp.income.create'))
        ->assertOk()
        ->assertSee('Youth Rally Offering');
});

test('finance admin can record income with a new category not in the dropdown', function () {
    $admin = Admin::factory()->create(['role' => 'finance']);

    $response = $this->actingAs($admin, 'admin')->post(route('financial-erp.income.store'), [
        'income_date' => '2026-09-02',
        'new_category_name' => 'Midweek Miracle Offering',
        'amount' => 7500,
        'member_name' => 'Brother Paul',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('erp_income_categories', [
        'name' => 'Midweek Miracle Offering',
        'is_active' => 1,
    ]);
    $categoryId = DB::table('erp_income_categories')->where('name', 'Midweek Miracle Offering')->value('id');
    $this->assertDatabaseHas('erp_income', [
        'category_id' => $categoryId,
        'member_name' => 'Brother Paul',
        'amount' => 7500,
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('financial-erp.income.create'))
        ->assertOk()
        ->assertSee('Midweek Miracle Offering')
        ->assertSee('Not in list — create new category');
});

test('income category code is auto generated when omitted', function () {
    $admin = Admin::factory()->create(['role' => 'finance']);

    $this->actingAs($admin, 'admin')->post(route('financial-erp.income.categories.store'), [
        'name' => 'Pastor Appreciation',
    ])->assertRedirect(route('financial-erp.income.categories.index'));

    $row = DB::table('erp_income_categories')->where('name', 'Pastor Appreciation')->first();
    expect($row)->not->toBeNull()
        ->and((string) $row->code)->not->toBe('')
        ->and((bool) $row->is_active)->toBeTrue();
});

test('ss teacher cannot add income categories', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);

    $this->actingAs($admin, 'admin')
        ->get(route('financial-erp.income.categories.create'))
        ->assertForbidden();

    $this->actingAs($admin, 'admin')
        ->post(route('financial-erp.income.categories.store'), [
            'name' => 'Blocked Category',
        ])
        ->assertForbidden();
});

test('finance admin launch opens laravel erp login', function () {
    $admin = Admin::factory()->create(['role' => 'finance']);

    $response = $this->actingAs($admin, 'admin')->get(route('financial-erp.launch'));

    $response->assertRedirect();
    $location = (string) $response->headers->get('Location');
    expect($location)->toContain('/erp/login?from=portal');
    expect($location)->toContain('127.0.0.1:8000');
    expect($location)->not->toContain('/erp/sso');
});

test('laravel erp login route proxies legacy login html', function () {
    Illuminate\Support\Facades\Http::fake([
        '*/erp/login*' => Illuminate\Support\Facades\Http::response(
            '<!DOCTYPE html><html><head><link rel="stylesheet" href="/AG_IKENEGBU_CHURCH_WEBSITE/erp/assets/css/erp-login.css"></head><body class="erp-login"><h1>Financial ERP</h1></body></html>',
            200,
            ['Content-Type' => 'text/html; charset=UTF-8']
        ),
    ]);

    $response = $this->get('/erp/login?from=portal');

    $response->assertOk();
    $response->assertSee('Financial ERP');
    $response->assertSee('/erp/assets/css/erp-login.css');
    $response->assertDontSee('/AG_IKENEGBU_CHURCH_WEBSITE/erp/assets');
});

test('laravel erp login rewrites legacy redirect query to /erp path', function () {
    Illuminate\Support\Facades\Http::fake([
        '*/erp/settings*' => Illuminate\Support\Facades\Http::response(
            '',
            302,
            ['Location' => '/AG_IKENEGBU_CHURCH_WEBSITE/erp/login?redirect=%2FAG_IKENEGBU_CHURCH_WEBSITE%2Ferp%2Fsettings']
        ),
    ]);

    $response = $this->get('/erp/settings');

    $response->assertRedirect();
    $location = (string) $response->headers->get('Location');
    expect($location)->toContain('/erp/login');
    expect($location)->toContain('redirect=');
    expect($location)->toContain(urlencode('/erp/settings'));
    expect($location)->not->toContain('AG_IKENEGBU_CHURCH_WEBSITE');
});

test('laravel erp login json redirect is rewritten off the xampp tree', function () {
    Illuminate\Support\Facades\Http::fake([
        '*/erp/handlers/auth-handler*' => Illuminate\Support\Facades\Http::response(
            json_encode([
                'success' => true,
                'message' => 'Welcome',
                'redirect' => '/AG_IKENEGBU_CHURCH_WEBSITE/erp/settings',
            ]),
            200,
            ['Content-Type' => 'application/json']
        ),
    ]);

    $response = $this->post('/erp/handlers/auth-handler', [
        'action' => 'login',
        'email' => 'finance@example.com',
        'password' => 'secret',
    ]);

    $response->assertOk();
    $payload = $response->json();
    expect($payload['redirect'] ?? '')->toContain('/erp/settings');
    expect($payload['redirect'] ?? '')->not->toContain('AG_IKENEGBU_CHURCH_WEBSITE');
});
