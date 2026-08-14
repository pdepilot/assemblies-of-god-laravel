<?php

use App\Support\RbacPlatform;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        Schema::table('roles', function (Blueprint $table) {
            if (! Schema::hasColumn('roles', 'platform')) {
                $table->string('platform', 16)
                    ->default(RbacPlatform::BOTH)
                    ->after('dashboard_type');
            }
        });

        // Backfill: super_admin → both; SDTG roles → sdtg; remaining → ag.
        DB::table('roles')->where('slug', 'super_admin')->update([
            'platform' => RbacPlatform::BOTH,
            'updated_at' => now(),
        ]);

        DB::table('roles')
            ->where('slug', '!=', 'super_admin')
            ->where(function ($query): void {
                $query->where('dashboard_type', 'sdtg')
                    ->orWhere('slug', 'like', '%sdtg%')
                    ->orWhere('name', 'like', '%SDTG%')
                    ->orWhere('name', 'like', '%Send Down Thy Glory%');
            })
            ->update([
                'platform' => RbacPlatform::SDTG,
                'updated_at' => now(),
            ]);

        DB::table('roles')
            ->where('slug', '!=', 'super_admin')
            ->where('platform', RbacPlatform::BOTH)
            ->where(function ($query): void {
                $query->whereNull('dashboard_type')
                    ->orWhere('dashboard_type', '!=', 'sdtg');
            })
            ->where('slug', 'not like', '%sdtg%')
            ->where('name', 'not like', '%SDTG%')
            ->update([
                'platform' => RbacPlatform::AG,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasColumn('roles', 'platform')) {
            return;
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('platform');
        });
    }
};
