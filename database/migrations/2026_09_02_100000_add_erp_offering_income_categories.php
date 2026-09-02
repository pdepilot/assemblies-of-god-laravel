<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('erp_income_categories')) {
            return;
        }

        $offeringsAccountId = Schema::hasTable('erp_accounts')
            ? DB::table('erp_accounts')->where('code', '4010')->value('id')
            : null;

        $now = now();

        $categories = [
            ['COVN', 'Covenant Offering'],
            ['SPOF', 'Special Offering'],
            ['XOSU', 'Cross Over Support'],
            ['FACL', 'Faith Clinic'],
            ['XOSD', 'Cross Over Seed'],
            ['AGCR', 'AG Care'],
            ['GCSP', 'General Council Support'],
            ['DSOF', 'District Support Offering'],
            ['FRFR', 'First Fruit'],
            ['HARV', 'Harvest Proceed'],
            ['WLFR', 'Welfare Offering'],
            ['ALTR', 'Altar Seed'],
            ['THGV', 'Thanksgiving Offering'],
            ['TEST', 'Testimony Offering'],
        ];

        foreach ($categories as [$code, $name]) {
            if (DB::table('erp_income_categories')->where('code', $code)->exists()) {
                $update = [
                    'name' => $name,
                    'is_active' => true,
                ];
                if ($offeringsAccountId) {
                    $update['account_id'] = $offeringsAccountId;
                }

                DB::table('erp_income_categories')
                    ->where('code', $code)
                    ->update($update);

                continue;
            }

            DB::table('erp_income_categories')->insert([
                'code' => $code,
                'name' => $name,
                'account_id' => $offeringsAccountId,
                'is_active' => true,
                'created_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('erp_income_categories')) {
            return;
        }

        DB::table('erp_income_categories')->whereIn('code', [
            'COVN',
            'SPOF',
            'XOSU',
            'FACL',
            'XOSD',
            'AGCR',
            'GCSP',
            'DSOF',
            'FRFR',
            'HARV',
            'WLFR',
            'ALTR',
            'THGV',
            'TEST',
        ])->delete();
    }
};
