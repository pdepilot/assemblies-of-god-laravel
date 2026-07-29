<?php

namespace App\Services\Sdtg;

use Illuminate\Support\Facades\DB;

final class SdtgSpeakerReadService
{
    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public function list(int $year, int $page, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $builder = DB::table('sdtg_speakers');
        if ($year > 0) {
            $builder->where('crusade_year', $year);
        }

        $total = (int) $builder->count();
        $items = (clone $builder)
            ->orderBy('sort_order')
            ->orderBy('full_name')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'per_page' => $perPage,
        ];
    }

    /** @return array<string, mixed>|null */
    public function get(int $id): ?array
    {
        $row = DB::table('sdtg_speakers')->where('id', $id)->first();

        return $row ? (array) $row : null;
    }
}
