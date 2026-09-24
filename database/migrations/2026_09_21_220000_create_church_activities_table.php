<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('church_activities')) {
            return;
        }

        Schema::create('church_activities', function (Blueprint $table) {
            $table->id();
            $table->string('activity_code', 24)->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon_class', 50)->default('fa-church');
            $table->string('leader_name')->default('');
            $table->string('meeting_schedule')->default('');
            $table->string('member_department')->nullable();
            $table->string('read_more_url')->nullable();
            $table->string('card_gradient', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('church_activities');
    }
};
