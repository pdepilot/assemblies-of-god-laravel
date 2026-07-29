<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('security_logs')) {
            return;
        }

        Schema::table('security_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('security_logs', 'device_fingerprint')) {
                $table->char('device_fingerprint', 64)->nullable()->after('admin_id');
            }

            if (! Schema::hasColumn('security_logs', 'metadata')) {
                $table->json('metadata')->nullable()->after('message');
            }

            if (! Schema::hasColumn('security_logs', 'severity')) {
                $table->string('severity', 20)->default('info')->after('event_type');
            }
        });
    }

    public function down(): void
    {
        // Keep columns; legacy production schema includes them.
    }
};
