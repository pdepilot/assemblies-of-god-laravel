<?php

namespace App\Services\Sermons;

use Illuminate\Support\Facades\DB;

final class BroadcastReadService
{
    public const STATUSES = ['draft', 'scheduled', 'live', 'ended', 'cancelled', 'upcoming'];

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public function listStreams(string $status, int $page, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $builder = DB::table('live_streams');
        if ($status !== '' && in_array($status, self::STATUSES, true)) {
            $builder->where('status', $status);
        }

        $total = (int) $builder->count();
        $items = (clone $builder)
            ->orderByDesc('stream_date')
            ->orderByDesc('id')
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
    public function getStream(int $id): ?array
    {
        $row = DB::table('live_streams')->where('id', $id)->first();

        return $row ? (array) $row : null;
    }
}
