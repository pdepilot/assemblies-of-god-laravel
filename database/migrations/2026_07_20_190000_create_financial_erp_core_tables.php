<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('erp_fiscal_years')) {
            Schema::create('erp_fiscal_years', function (Blueprint $table) {
                $table->id();
                $table->string('name', 80);
                $table->date('start_date');
                $table->date('end_date');
                $table->boolean('is_current')->default(false);
                $table->boolean('is_locked')->default(false);
                $table->timestamps();
                $table->unique('name', 'uq_erp_fy_name');
            });
        }

        if (! Schema::hasTable('erp_fiscal_periods')) {
            Schema::create('erp_fiscal_periods', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fiscal_year_id');
                $table->unsignedTinyInteger('period_no');
                $table->string('name', 40);
                $table->date('start_date');
                $table->date('end_date');
                $table->boolean('is_locked')->default(false);
                $table->timestamp('created_at')->useCurrent();
                $table->unique(['fiscal_year_id', 'period_no'], 'uq_erp_period');
            });
        }

        if (! Schema::hasTable('erp_accounts')) {
            Schema::create('erp_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('code', 32)->unique('uq_erp_account_code');
                $table->string('name', 160);
                $table->string('account_type', 32);
                $table->string('subtype', 60)->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('normal_balance', 16)->default('debit');
                $table->boolean('is_postable')->default(true);
                $table->boolean('is_bank')->default(false);
                $table->boolean('is_cash')->default(false);
                $table->decimal('opening_balance', 18, 2)->default(0);
                $table->char('currency', 3)->default('NGN');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->dateTime('deleted_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->index('account_type', 'idx_erp_accounts_type');
            });
        }

        if (! Schema::hasTable('erp_vendors')) {
            Schema::create('erp_vendors', function (Blueprint $table) {
                $table->id();
                $table->string('code', 32)->unique('uq_erp_vendor_code');
                $table->string('name', 160);
                $table->string('contact_person', 120)->nullable();
                $table->string('email', 160)->nullable();
                $table->string('phone', 40)->nullable();
                $table->text('address')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->dateTime('deleted_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index('name', 'idx_erp_vendors_name');
            });
        }

        if (! Schema::hasTable('erp_projects')) {
            Schema::create('erp_projects', function (Blueprint $table) {
                $table->id();
                $table->string('code', 32)->unique('uq_erp_project_code');
                $table->string('name', 160);
                $table->string('project_type', 32)->default('general');
                $table->decimal('budget_amount', 18, 2)->default(0);
                $table->string('status', 32)->default('active');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->dateTime('deleted_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('erp_bank_accounts')) {
            Schema::create('erp_bank_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('account_id');
                $table->string('bank_name', 120);
                $table->string('account_number', 60);
                $table->string('account_name', 160);
                $table->char('currency', 3)->default('NGN');
                $table->decimal('opening_balance', 18, 2)->default(0);
                $table->decimal('current_balance', 18, 2)->default(0);
                $table->boolean('is_cash')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique('account_id', 'uq_erp_bank_gl');
            });
        }

        if (! Schema::hasTable('erp_journals')) {
            Schema::create('erp_journals', function (Blueprint $table) {
                $table->id();
                $table->string('journal_no', 40)->unique('uq_erp_journal_no');
                $table->date('journal_date');
                $table->string('source_type', 40)->default('manual');
                $table->string('reference', 120)->nullable();
                $table->text('memo')->nullable();
                $table->string('status', 32)->default('draft');
                $table->decimal('total_debit', 18, 2)->default(0);
                $table->decimal('total_credit', 18, 2)->default(0);
                $table->dateTime('posted_at')->nullable();
                $table->unsignedBigInteger('posted_by')->nullable();
                $table->unsignedBigInteger('reversed_journal_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->dateTime('deleted_at')->nullable();
                $table->timestamps();
                $table->index('journal_date', 'idx_erp_journals_date');
                $table->index('status', 'idx_erp_journals_status');
            });
        }

        if (! Schema::hasTable('erp_journal_lines')) {
            Schema::create('erp_journal_lines', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('journal_id');
                $table->unsignedSmallInteger('line_no')->default(1);
                $table->unsignedBigInteger('account_id');
                $table->decimal('debit', 18, 2)->default(0);
                $table->decimal('credit', 18, 2)->default(0);
                $table->string('description', 255)->nullable();
                $table->index('journal_id', 'idx_erp_jl_journal');
                $table->index('account_id', 'idx_erp_jl_account');
            });
        }

        if (! Schema::hasTable('erp_income_categories')) {
            Schema::create('erp_income_categories', function (Blueprint $table) {
                $table->id();
                $table->string('code', 32)->unique('uq_erp_income_cat');
                $table->string('name', 120);
                $table->unsignedBigInteger('account_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('erp_expense_categories')) {
            Schema::create('erp_expense_categories', function (Blueprint $table) {
                $table->id();
                $table->string('code', 32)->unique('uq_erp_expense_cat');
                $table->string('name', 120);
                $table->unsignedBigInteger('account_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('erp_income')) {
            Schema::create('erp_income', function (Blueprint $table) {
                $table->id();
                $table->string('receipt_no', 40)->unique('uq_erp_income_receipt');
                $table->date('income_date');
                $table->unsignedBigInteger('category_id');
                $table->unsignedBigInteger('account_id')->nullable();
                $table->decimal('amount', 18, 2);
                $table->string('payment_method', 40)->default('cash');
                $table->string('reference_no', 80)->nullable();
                $table->unsignedBigInteger('member_id')->nullable();
                $table->string('member_name', 160)->nullable();
                $table->unsignedBigInteger('project_id')->nullable();
                $table->unsignedBigInteger('bank_account_id')->nullable();
                $table->unsignedBigInteger('journal_id')->nullable();
                $table->text('notes')->nullable();
                $table->string('approval_status', 32)->default('draft');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->dateTime('deleted_at')->nullable();
                $table->timestamps();
                $table->index('income_date', 'idx_erp_income_date');
            });
        }

        if (! Schema::hasTable('erp_expenses')) {
            Schema::create('erp_expenses', function (Blueprint $table) {
                $table->id();
                $table->string('voucher_no', 40)->unique('uq_erp_expense_voucher');
                $table->date('expense_date');
                $table->unsignedBigInteger('category_id');
                $table->unsignedBigInteger('vendor_id')->nullable();
                $table->unsignedBigInteger('account_id')->nullable();
                $table->decimal('amount', 18, 2);
                $table->string('payment_method', 40)->default('cash');
                $table->string('reference_no', 80)->nullable();
                $table->unsignedBigInteger('project_id')->nullable();
                $table->unsignedBigInteger('bank_account_id')->nullable();
                $table->unsignedBigInteger('journal_id')->nullable();
                $table->text('notes')->nullable();
                $table->string('payment_status', 32)->default('unpaid');
                $table->string('approval_status', 32)->default('draft');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->dateTime('deleted_at')->nullable();
                $table->timestamps();
                $table->index('expense_date', 'idx_erp_expense_date');
            });
        }

        if (! Schema::hasTable('erp_receipts')) {
            Schema::create('erp_receipts', function (Blueprint $table) {
                $table->id();
                $table->string('receipt_no', 40)->unique('uq_erp_receipt_doc');
                $table->date('receipt_date');
                $table->string('payer_name', 160);
                $table->decimal('amount', 18, 2);
                $table->string('payment_method', 40)->default('cash');
                $table->unsignedBigInteger('income_id')->nullable();
                $table->string('reference_no', 80)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('erp_approvals')) {
            Schema::create('erp_approvals', function (Blueprint $table) {
                $table->id();
                $table->string('entity_type', 40);
                $table->unsignedBigInteger('entity_id');
                $table->unsignedTinyInteger('level_no')->default(1);
                $table->string('approver_role', 60)->nullable();
                $table->string('status', 32)->default('pending');
                $table->timestamp('created_at')->useCurrent();
                $table->index(['entity_type', 'entity_id'], 'idx_erp_appr_entity');
                $table->index('status', 'idx_erp_appr_status');
            });
        }

        if (! Schema::hasTable('erp_audit_log')) {
            Schema::create('erp_audit_log', function (Blueprint $table) {
                $table->id();
                $table->string('entity_type', 60);
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->string('action', 60);
                $table->longText('before_json')->nullable();
                $table->longText('after_json')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('user_role', 80)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['entity_type', 'entity_id'], 'idx_erp_audit_entity');
            });
        }

        if (! Schema::hasTable('erp_settings')) {
            Schema::create('erp_settings', function (Blueprint $table) {
                $table->id();
                $table->string('setting_key', 80)->unique('uq_erp_setting_key');
                $table->text('setting_value')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        $this->seedErpDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_audit_log');
        Schema::dropIfExists('erp_approvals');
        Schema::dropIfExists('erp_receipts');
        Schema::dropIfExists('erp_expenses');
        Schema::dropIfExists('erp_income');
        Schema::dropIfExists('erp_expense_categories');
        Schema::dropIfExists('erp_income_categories');
        Schema::dropIfExists('erp_journal_lines');
        Schema::dropIfExists('erp_journals');
        Schema::dropIfExists('erp_bank_accounts');
        Schema::dropIfExists('erp_projects');
        Schema::dropIfExists('erp_vendors');
        Schema::dropIfExists('erp_accounts');
        Schema::dropIfExists('erp_fiscal_periods');
        Schema::dropIfExists('erp_fiscal_years');
        Schema::dropIfExists('erp_settings');
    }

    private function seedErpDefaults(): void
    {
        if (! Schema::hasTable('erp_accounts') || DB::table('erp_accounts')->count() > 0) {
            return;
        }

        $year = (int) now()->format('Y');
        $now = now();

        $fyId = DB::table('erp_fiscal_years')->insertGetId([
            'name' => (string) $year,
            'start_date' => "{$year}-01-01",
            'end_date' => "{$year}-12-31",
            'is_current' => true,
            'is_locked' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        for ($m = 1; $m <= 12; $m++) {
            $start = sprintf('%d-%02d-01', $year, $m);
            $end = now()->parse($start)->endOfMonth()->toDateString();
            DB::table('erp_fiscal_periods')->insert([
                'fiscal_year_id' => $fyId,
                'period_no' => $m,
                'name' => now()->parse($start)->format('M Y'),
                'start_date' => $start,
                'end_date' => $end,
                'is_locked' => false,
                'created_at' => $now,
            ]);
        }

        $accounts = [
            ['1000', 'Cash on Hand', 'asset', 1, 1],
            ['1010', 'Main Bank Account', 'asset', 1, 0],
            ['1100', 'Accounts Receivable', 'asset', 0, 0],
            ['1500', 'Fixed Assets', 'asset', 0, 0],
            ['2000', 'Accounts Payable', 'liability', 0, 0],
            ['2100', 'Accrued Expenses', 'liability', 0, 0],
            ['3000', 'Church Equity / Net Assets', 'equity', 0, 0],
            ['4000', 'Tithes Income', 'income', 0, 0],
            ['4010', 'Offerings Income', 'income', 0, 0],
            ['4020', 'Building Fund Income', 'income', 0, 0],
            ['4030', 'Missions Income', 'income', 0, 0],
            ['4040', 'Partnership Income', 'income', 0, 0],
            ['4090', 'Other Income', 'income', 0, 0],
            ['5000', 'Ministry Expenses', 'expense', 0, 0],
            ['5010', 'Utilities', 'expense', 0, 0],
            ['5020', 'Salaries & Wages', 'expense', 0, 0],
            ['5030', 'Maintenance & Repairs', 'expense', 0, 0],
            ['5040', 'Missions Expenses', 'expense', 0, 0],
            ['5050', 'Transport & Travel', 'expense', 0, 0],
            ['5060', 'Office & Administration', 'expense', 0, 0],
            ['5070', 'Worship & Media', 'expense', 0, 0],
            ['5080', 'Welfare & Outreach', 'expense', 0, 0],
            ['5090', 'Other Expenses', 'expense', 0, 0],
        ];

        $ids = [];
        foreach ($accounts as [$code, $name, $type, $isCash, $isBank]) {
            $nb = in_array($type, ['asset', 'expense'], true) ? 'debit' : 'credit';
            $ids[$code] = DB::table('erp_accounts')->insertGetId([
                'code' => $code,
                'name' => $name,
                'account_type' => $type,
                'is_postable' => true,
                'is_bank' => (bool) $isBank,
                'is_cash' => (bool) $isCash,
                'normal_balance' => $nb,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('erp_bank_accounts')->insert([
            [
                'account_id' => $ids['1000'],
                'bank_name' => 'Cash',
                'account_number' => 'CASH-001',
                'account_name' => 'Petty / Cash on Hand',
                'is_cash' => true,
                'current_balance' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'account_id' => $ids['1010'],
                'bank_name' => 'Main Bank',
                'account_number' => 'BANK-001',
                'account_name' => 'Church Operating Account',
                'is_cash' => false,
                'current_balance' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        foreach ([
            ['TITH', 'Tithes', '4000'],
            ['OFFR', 'Offerings', '4010'],
            ['BLDG', 'Building Fund', '4020'],
            ['MISS', 'Missions', '4030'],
            ['PART', 'Partnership', '4040'],
            ['OTHR', 'Other Income', '4090'],
        ] as [$code, $name, $ac]) {
            DB::table('erp_income_categories')->insert([
                'code' => $code,
                'name' => $name,
                'account_id' => $ids[$ac],
                'is_active' => true,
                'created_at' => $now,
            ]);
        }

        foreach ([
            ['MIN', 'Ministry Programs', '5000'],
            ['UTL', 'Utilities', '5010'],
            ['PAY', 'Payroll / Salaries', '5020'],
            ['MNT', 'Maintenance & Repairs', '5030'],
            ['MSE', 'Missions Expense', '5040'],
            ['TRN', 'Transport & Travel', '5050'],
            ['ADM', 'Office & Administration', '5060'],
            ['OTH', 'Other Expenses', '5090'],
        ] as [$code, $name, $ac]) {
            DB::table('erp_expense_categories')->insert([
                'code' => $code,
                'name' => $name,
                'account_id' => $ids[$ac],
                'is_active' => true,
                'created_at' => $now,
            ]);
        }

        foreach ([
            'currency' => 'NGN',
            'church_name' => 'AG Ikenebgu',
            'fiscal_year_start_month' => '1',
            'require_expense_approval' => '1',
        ] as $key => $value) {
            DB::table('erp_settings')->insert([
                'setting_key' => $key,
                'setting_value' => $value,
            ]);
        }
    }
};
