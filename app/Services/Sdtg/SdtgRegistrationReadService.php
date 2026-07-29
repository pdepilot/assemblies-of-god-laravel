<?php

namespace App\Services\Sdtg;

use Illuminate\Support\Facades\DB;

final class SdtgRegistrationReadService
{
    public const STATUSES = ['confirmed', 'pending', 'cancelled'];

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public function list(string $query, string $status, int $page, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $builder = DB::table('sdtg_registrations');
        if ($query !== '') {
            $builder->where(function ($q) use ($query) {
                $q->where('full_name', 'like', '%'.$query.'%')
                    ->orWhere('email', 'like', '%'.$query.'%')
                    ->orWhere('registration_code', 'like', '%'.$query.'%');
            });
        }
        if ($status !== '' && in_array($status, self::STATUSES, true)) {
            $builder->where('status', $status);
        }

        $total = (int) $builder->count();
        $items = (clone $builder)
            ->orderByDesc('registration_date')
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
    public function get(int $id): ?array
    {
        $row = DB::table('sdtg_registrations')->where('id', $id)->first();

        return $row ? (array) $row : null;
    }

    /** @return array<string, int> */
    public function getStats(): array
    {
        return [
            'total' => (int) DB::table('sdtg_registrations')->where('status', '<>', 'cancelled')->count(),
            'confirmed' => (int) DB::table('sdtg_registrations')->where('status', 'confirmed')->count(),
            'pending' => (int) DB::table('sdtg_registrations')->where('status', 'pending')->count(),
            'volunteers' => (int) DB::table('sdtg_registrations')->where('is_volunteer', true)->count(),
        ];
    }
}
