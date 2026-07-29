<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('site_testimonies')) {
            return;
        }

        Schema::create('site_testimonies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('full_name', 150);
            $table->string('email', 255);
            $table->string('role_title', 120)->nullable();
            $table->text('testimony_text');
            $table->string('photo_path', 500)->nullable();
            $table->string('source_page', 60)->default('index');
            $table->string('category_slug', 60)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('is_featured')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->dateTime('created_at')->useCurrent();

            $table->index(['status', 'created_at'], 'idx_site_testimony_status');
            $table->index(['source_page', 'status', 'created_at'], 'idx_site_testimony_source');
            $table->index(['is_featured', 'status'], 'idx_site_testimony_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_testimonies');
    }
};
