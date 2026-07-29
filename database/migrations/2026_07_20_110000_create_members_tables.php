<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('members')) {
            Schema::create('members', function (Blueprint $table) {
                $table->id();
                $table->string('member_code', 20)->unique('uk_member_code');
                $table->string('first_name', 100)->nullable();
                $table->string('last_name', 100)->nullable();
                $table->string('full_name', 255);
                $table->string('email', 255)->nullable();
                $table->string('phone', 30);
                $table->string('phone_alt', 30)->nullable();
                $table->enum('gender', ['male', 'female', 'other', 'unspecified'])->default('unspecified');
                $table->date('date_of_birth')->nullable();
                $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed', 'widow', 'widower', 'separated', 'unspecified'])->default('unspecified');
                $table->date('wedding_date')->nullable();
                $table->string('parent_name', 255)->nullable();
                $table->string('parent_email', 255)->nullable();
                $table->string('parent_phone', 30)->nullable();
                $table->string('address_line1', 255);
                $table->string('address_line2', 255)->nullable();
                $table->string('city', 100);
                $table->string('state', 100);
                $table->string('postal_code', 20)->nullable();
                $table->string('country', 100)->default('Nigeria');
                $table->string('department', 100);
                $table->string('status', 50)->default('active');
                $table->string('photo_path', 500)->nullable();
                $table->date('joined_date');
                $table->date('date_of_death')->nullable();
                $table->text('death_notes')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index('status', 'idx_member_status');
                $table->index('department', 'idx_member_dept');
                $table->index('full_name', 'idx_member_name');
            });
        }

        if (! Schema::hasTable('member_status_history')) {
            Schema::create('member_status_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('member_id');
                $table->string('previous_status', 50)->nullable();
                $table->string('new_status', 50);
                $table->string('status_reason', 255);
                $table->enum('change_type', ['auto', 'manual', 'system'])->default('manual');
                $table->unsignedBigInteger('changed_by')->nullable();
                $table->date('event_date');
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index('member_id', 'idx_member_history_member');
                $table->index('event_date', 'idx_member_history_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('member_status_history');
        Schema::dropIfExists('members');
    }
};
