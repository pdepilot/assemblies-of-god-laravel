<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ministry_age_transfers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('member_id')->nullable()->index();
            $table->unsignedBigInteger('roster_person_id')->nullable()->index();
            $table->string('from_key', 40);
            $table->string('to_key', 40);
            $table->unsignedSmallInteger('age_at_transfer');
            $table->string('full_name');
            $table->date('date_of_birth')->nullable();
            $table->string('source', 20); // schedule | on_save | manual
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->timestamp('transferred_at');
            $table->timestamps();

            $table->index(['from_key', 'to_key']);
            $table->index('transferred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ministry_age_transfers');
    }
};
