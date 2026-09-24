<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contact_settings')) {
            return;
        }

        DB::table('contact_settings')->update([
            'phone' => '+2348034095171',
            'phone_display' => '08034095171',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('contact_settings')) {
            return;
        }

        DB::table('contact_settings')->update([
            'phone' => '+2348034567890',
            'phone_display' => '+234 803 456 7890',
            'updated_at' => now(),
        ]);
    }
};
