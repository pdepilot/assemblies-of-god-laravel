<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('donation_categories')) {
            Schema::create('donation_categories', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 50)->unique('uk_donation_cat_slug');
                $table->string('name', 100);
                $table->text('description')->nullable();
                $table->string('legacy_category', 50)->default('other');
                $table->string('icon_class', 50)->default('fa-hand-holding-heart');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_public')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'sort_order'], 'idx_donation_cat_active');
            });
        }

        if (! Schema::hasTable('donations')) {
            Schema::create('donations', function (Blueprint $table) {
                $table->id();
                $table->string('donation_code', 20)->unique('uk_donation_code');
                $table->string('donor_name')->default('Anonymous');
                $table->string('donor_email')->nullable();
                $table->string('donor_phone', 30)->nullable();
                $table->string('donor_location')->nullable();
                $table->decimal('amount', 12, 2)->default(0);
                $table->char('currency', 3)->default('NGN');
                $table->string('category', 32)->default('offering');
                $table->unsignedBigInteger('category_id')->nullable();
                $table->unsignedBigInteger('campaign_id')->nullable();
                $table->string('fund_scope', 16)->default('church');
                $table->string('payment_method', 50)->nullable();
                $table->string('payment_provider', 32)->nullable();
                $table->string('payment_status', 32)->nullable();
                $table->string('payment_reference', 128)->nullable();
                $table->boolean('is_anonymous')->default(false);
                $table->date('donation_date');
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('pledge_id')->nullable();
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();

                $table->index('donation_date', 'idx_donation_date');
                $table->index('fund_scope', 'idx_donation_scope');
            });
        }

        if (! Schema::hasTable('commitment_programs')) {
            Schema::create('commitment_programs', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('target_amount', 14, 2)->default(0);
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('status', 32)->default('draft');
                $table->timestamps();

                $table->index('status', 'idx_cp_status');
            });
        }

        if (! Schema::hasTable('commitment_givers')) {
            Schema::create('commitment_givers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('program_id');
                $table->string('donor_name');
                $table->string('email')->nullable();
                $table->string('phone', 30)->nullable();
                $table->decimal('committed_amount', 14, 2)->default(0);
                $table->decimal('amount_paid', 14, 2)->default(0);
                $table->string('frequency', 32)->default('monthly');
                $table->string('status', 32)->default('active');
                $table->timestamps();

                $table->index('program_id', 'idx_cg_program');
                $table->index('status', 'idx_cg_status');
            });
        }

        if (! Schema::hasTable('pledges')) {
            Schema::create('pledges', function (Blueprint $table) {
                $table->id();
                $table->string('donor_name');
                $table->string('donor_email')->nullable();
                $table->string('donor_phone', 30)->nullable();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->decimal('pledged_amount', 12, 2);
                $table->decimal('amount_paid', 12, 2)->default(0);
                $table->decimal('remaining_balance', 12, 2);
                $table->decimal('installment_amount', 12, 2)->nullable();
                $table->unsignedInteger('installment_count')->nullable();
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->date('next_due_date')->nullable();
                $table->string('status', 32)->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('status', 'idx_pledge_status');
                $table->index('next_due_date', 'idx_pledge_due');
            });
        }

        if (! Schema::hasTable('pledge_payments')) {
            Schema::create('pledge_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pledge_id');
                $table->unsignedBigInteger('donation_id')->nullable();
                $table->decimal('amount', 12, 2);
                $table->date('paid_at');
                $table->timestamp('created_at')->useCurrent();

                $table->index('pledge_id', 'idx_pledge_payments_pledge');
            });
        }

        $this->seedDonationCategories();
    }

    public function down(): void
    {
        Schema::dropIfExists('pledge_payments');
        Schema::dropIfExists('pledges');
        Schema::dropIfExists('commitment_givers');
        Schema::dropIfExists('commitment_programs');
        Schema::dropIfExists('donations');
        Schema::dropIfExists('donation_categories');
    }

    private function seedDonationCategories(): void
    {
        if (! Schema::hasTable('donation_categories') || DB::table('donation_categories')->count() > 0) {
            return;
        }

        $now = now();
        $rows = [
            ['offering', 'Offerings', 'offering', 1],
            ['tithe', 'Tithes', 'tithe', 2],
            ['charity', 'Charity & Welfare', 'welfare', 3],
            ['building_fund', 'Building Fund', 'building_fund', 4],
            ['project_fund', 'Project Fund', 'missions', 5],
            ['sdtg', 'SDTG Crusade', 'sdtg', 6],
        ];

        foreach ($rows as [$slug, $name, $legacy, $sort]) {
            DB::table('donation_categories')->insert([
                'slug' => $slug,
                'name' => $name,
                'legacy_category' => $legacy,
                'sort_order' => $sort,
                'is_active' => true,
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
