<?php

namespace App\Services\Sdtg;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SdtgEditionsReadService
{
    /** @return list<array<string, mixed>> */
    public function listEditions(): array
    {
        if (! Schema::hasTable('sdtg_crusade_editions')) {
            return [];
        }

        return DB::table('sdtg_crusade_editions')
            ->orderByDesc('crusade_year')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function getEdition(int $id): ?array
    {
        $row = DB::table('sdtg_crusade_editions')->where('id', $id)->first();

        return $row ? (array) $row : null;
    }
}
