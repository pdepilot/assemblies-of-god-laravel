<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('registrants')) {
            return;
        }

        Schema::table('registrants', function (Blueprint $table) {
            if (! Schema::hasColumn('registrants', 'age_group')) {
                $table->string('age_group', 20)->nullable()->after('gender');
                $table->index(['portal_id', 'age_group'], 'idx_registrants_age_group');
            }
            if (! Schema::hasColumn('registrants', 'age')) {
                $table->unsignedTinyInteger('age')->nullable()->after('age_group');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('registrants')) {
            return;
        }

        Schema::table('registrants', function (Blueprint $table) {
            if (Schema::hasColumn('registrants', 'age')) {
                $table->dropColumn('age');
            }
            if (Schema::hasColumn('registrants', 'age_group')) {
                $table->dropIndex('idx_registrants_age_group');
                $table->dropColumn('age_group');
            }
        });
    }
};
