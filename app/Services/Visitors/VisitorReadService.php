<?php

namespace App\Services\Visitors;

use Illuminate\Support\Facades\DB;

final class VisitorReadService
{
    public const FOLLOW_UP_STATUSES = [
        'new',
        'contacted',
        'in_discipleship',
        'ready',
        'promoted',
        'inactive',
    ];

    public const SERVICES = [
        'Sunday First Service',
        'Sunday Second Service',
        'Midweek Service',
        'Prayer Meeting',
        'Special Program',
    ];

    public const HOW_HEARD = [
        'Friend / Family',
        'Social Media',
        'Street Evangelism',
        'Website',
        'Conference / Outreach',
        'Other',
    ];

    /** @return array<string, string> */
    public static function followUpLabels(): array
    {
        return [
            'new' => 'New Visitor',
            'contacted' => 'Contacted',
            'in_discipleship' => 'In Discipleship',
            'ready' => 'Ready for Membership',
            'promoted' => 'Promoted to Member',
            'inactive' => 'Inactive',
        ];
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, pages: int}
     */
    public function listVisitors(string $query, string $followUp, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $base = DB::table('visitors');

        if ($query !== '') {
            $like = '%'.$query.'%';
            $base->where(function ($q) use ($like) {
                $q->where('full_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('visitor_code', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('phone_alt', 'like', $like);
            });
        }

        if ($followUp !== '' && in_array($followUp, self::FOLLOW_UP_STATUSES, true)) {
            $base->where('follow_up_status', $followUp);
        }

        $total = (int) (clone $base)->count();

        $items = (clone $base)
            ->orderByDesc('first_visit_date')
            ->orderBy('full_name')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(fn ($row) => $this->formatVisitor((array) $row))
            ->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => max(1, (int) ceil($total / max(1, $perPage))),
        ];
    }

    /** @return array<string, mixed>|null */
    public function getVisitor(int $id): ?array
    {
        $row = DB::table('visitors')->where('id', $id)->first();

        return $row ? $this->formatVisitor((array) $row) : null;
    }

    /** @return array<string, int> */
    public function getStats(): array
    {
        $monthStart = now()->startOfMonth()->toDateString();

        $contacted = (int) DB::table('visitors')->where('follow_up_status', 'contacted')->count();
        $discipleship = (int) DB::table('visitors')->where('follow_up_status', 'in_discipleship')->count();
        $ready = (int) DB::table('visitors')->where('follow_up_status', 'ready')->count();

        return [
            'total' => (int) DB::table('visitors')->count(),
            'new' => (int) DB::table('visitors')->where('follow_up_status', 'new')->count(),
            'in_pipeline' => $contacted + $discipleship + $ready,
            'ready' => $ready,
            'promoted' => (int) DB::table('visitors')->where('follow_up_status', 'promoted')->count(),
            'this_month' => (int) DB::table('visitors')->where('first_visit_date', '>=', $monthStart)->count(),
        ];
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function formatVisitor(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'visitor_code' => (string) $row['visitor_code'],
            'full_name' => (string) $row['full_name'],
            'email' => $row['email'],
            'phone' => (string) $row['phone'],
            'phone_alt' => $row['phone_alt'],
            'gender' => (string) $row['gender'],
            'address_line1' => (string) ($row['address_line1'] ?? ''),
            'address_line2' => $row['address_line2'],
            'city' => (string) ($row['city'] ?? ''),
            'state' => (string) ($row['state'] ?? ''),
            'postal_code' => $row['postal_code'],
            'country' => (string) ($row['country'] ?? 'Nigeria'),
            'first_visit_date' => (string) $row['first_visit_date'],
            'last_visit_date' => $row['last_visit_date'],
            'visit_count' => (int) $row['visit_count'],
            'service_attended' => $row['service_attended'],
            'how_heard' => $row['how_heard'],
            'interested_department' => $row['interested_department'],
            'follow_up_status' => (string) $row['follow_up_status'],
            'photo_path' => $row['photo_path'],
            'prayer_request' => $row['prayer_request'],
            'notes' => $row['notes'],
            'promoted_member_id' => $row['promoted_member_id'] ? (int) $row['promoted_member_id'] : null,
            'promoted_at' => $row['promoted_at'],
            'created_by' => $row['created_by'] ? (int) $row['created_by'] : null,
            'is_promoted' => (string) $row['follow_up_status'] === 'promoted',
        ];
    }
}
