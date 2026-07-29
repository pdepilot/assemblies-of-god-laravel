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

test('ss teacher cannot access financial erp', function () {
    $admin = Admin::factory()->create(['role' => 'ss_teacher']);

    $this->actingAs($admin, 'admin')->get(route('financial-erp.dashboard'))->assertForbidden();
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

test('laravel erp dashboard rewrites church management link to admin dashboard', function () {
    Illuminate\Support\Facades\Http::fake([
        '*/erp/dashboard*' => Illuminate\Support\Facades\Http::response(
            '<!DOCTYPE html><html><body class="erp-app"><a href="/AG_IKENEGBU_CHURCH_WEBSITE/portal/">Church Management</a></body></html>',
            200,
            ['Content-Type' => 'text/html; charset=UTF-8']
        ),
    ]);

    $response = $this->get('/erp/dashboard');

    $response->assertOk();
    $response->assertSee('Church Management');
    $response->assertSee('/admin/dashboard');
    $response->assertDontSee('/AG_IKENEGBU_CHURCH_WEBSITE/portal');
});
