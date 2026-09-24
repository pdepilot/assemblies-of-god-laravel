<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ministry_settings')) {
            Schema::create('ministry_settings', function (Blueprint $table) {
                $table->id();
                $table->string('ministry_key', 50)->unique('uk_ministry_settings_key');
                $table->string('name', 100);
                $table->unsignedInteger('min_age')->nullable();
                $table->unsignedInteger('max_age')->nullable();
                $table->string('gender_filter', 16)->default('any');
                $table->string('assignment_mode', 16)->default('manual');
                $table->boolean('is_enabled')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index('is_enabled', 'idx_ministry_settings_enabled');
            });
        }

        if (! Schema::hasTable('ministry_members')) {
            Schema::create('ministry_members', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('member_id');
                $table->string('ministry_key', 50);
                $table->string('ministry_status', 20)->default('active');
                $table->date('join_date');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['member_id', 'ministry_key'], 'uk_ministry_member');
                $table->index('ministry_key', 'idx_ministry_members_key');
                $table->index('ministry_status', 'idx_ministry_members_status');
            });
        }

        if (! Schema::hasTable('ministry_attendance')) {
            Schema::create('ministry_attendance', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('member_id');
                $table->string('ministry_type', 50);
                $table->date('service_date');
                $table->string('service_type', 50)->default('weekly');
                $table->boolean('present')->default(true);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->unique(
                    ['member_id', 'ministry_type', 'service_date', 'service_type'],
                    'uk_ministry_attendance'
                );
                $table->index('service_date', 'idx_ministry_attendance_date');
            });
        }

        if (! Schema::hasTable('visitors')) {
            Schema::create('visitors', function (Blueprint $table) {
                $table->id();
                $table->string('visitor_code', 20)->unique('uk_visitor_code');
                $table->string('full_name');
                $table->string('email')->nullable();
                $table->string('phone', 30);
                $table->string('phone_alt', 30)->nullable();
                $table->string('gender', 20)->default('unspecified');
                $table->string('address_line1')->default('');
                $table->string('address_line2')->nullable();
                $table->string('city')->default('');
                $table->string('state')->default('');
                $table->string('postal_code', 20)->nullable();
                $table->string('country', 100)->default('Nigeria');
                $table->date('first_visit_date');
                $table->date('last_visit_date')->nullable();
                $table->unsignedInteger('visit_count')->default(1);
                $table->string('service_attended', 100)->nullable();
                $table->string('how_heard')->nullable();
                $table->string('interested_department', 100)->nullable();
                $table->string('follow_up_status', 32)->default('new');
                $table->string('photo_path', 500)->nullable();
                $table->text('prayer_request')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('promoted_member_id')->nullable();
                $table->dateTime('promoted_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index('follow_up_status', 'idx_visitor_follow_up');
                $table->index('full_name', 'idx_visitor_name');
                $table->index('phone', 'idx_visitor_phone');
                $table->index('first_visit_date', 'idx_visitor_first_visit');
            });
        }

        $this->seedMinistrySettings();
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors');
        Schema::dropIfExists('ministry_attendance');
        Schema::dropIfExists('ministry_members');
        Schema::dropIfExists('ministry_settings');
    }

    private function seedMinistrySettings(): void
    {
        if (! Schema::hasTable('ministry_settings')) {
            return;
        }

        if (DB::table('ministry_settings')->count() > 0) {
            return;
        }

        $now = now();
        $rows = [
            ['children', 'Children Ministry', 0, 12, 'any', 'auto', 1, 1],
            ['teens', 'Teen Ministry', 13, 19, 'any', 'auto', 1, 2],
            ['youths', 'Youth Ministry', 20, 120, 'any', 'auto', 1, 3],
            ['men', "Men's Ministry", 20, 120, 'male', 'auto', 1, 4],
            ['women', "Women's Ministry", 20, 120, 'female', 'auto', 1, 5],
            ['widows', 'Widows', 20, 120, 'female', 'auto', 1, 7],
            ['music', 'Music Department', null, null, 'any', 'manual', 1, 10],
            ['choir', 'Choir', null, null, 'any', 'manual', 1, 11],
            ['ushers', 'Ushering', null, null, 'any', 'manual', 1, 12],
            ['media', 'Media Team', null, null, 'any', 'manual', 1, 13],
        ];

        foreach ($rows as $row) {
            DB::table('ministry_settings')->insert([
                'ministry_key' => $row[0],
                'name' => $row[1],
                'min_age' => $row[2],
                'max_age' => $row[3],
                'gender_filter' => $row[4],
                'assignment_mode' => $row[5],
                'is_enabled' => $row[6],
                'sort_order' => $row[7],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
