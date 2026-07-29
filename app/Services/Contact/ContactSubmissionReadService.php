<?php

namespace App\Services\Contact;

use Illuminate\Support\Facades\DB;

final class ContactSubmissionReadService
{
    public const STATUSES = ['new', 'read', 'in_progress', 'replied', 'archived'];

    public const INQUIRY_TYPES = ['general', 'prayer', 'visit'];

    /** @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int} */
    public function listSubmissions(
        string $status = '',
        string $query = '',
        int $page = 1,
        int $perPage = 25,
        string $type = '',
    ): array {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $builder = DB::table('contact_submissions');
        if ($status !== '' && in_array($status, self::STATUSES, true)) {
            $builder->where('status', $status);
        }
        if ($type !== '' && in_array($type, self::INQUIRY_TYPES, true)) {
            $builder->where('inquiry_type', $type);
        }
        if ($query !== '') {
            $builder->where(function ($q) use ($query) {
                $q->where('full_name', 'like', '%'.$query.'%')
                    ->orWhere('email', 'like', '%'.$query.'%')
                    ->orWhere('subject', 'like', '%'.$query.'%')
                    ->orWhere('submission_code', 'like', '%'.$query.'%')
                    ->orWhere('message', 'like', '%'.$query.'%');
            });
        }

        $total = (clone $builder)->count();
        $items = $builder->orderByDesc('id')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => (int) max(1, ceil($total / $perPage)),
        ];
    }

    /** @return array<string, mixed>|null */
    public function getSubmission(int $id): ?array
    {
        $row = DB::table('contact_submissions')->where('id', $id)->first();

        return $row ? (array) $row : null;
    }

    /** @return array<string, int> */
    public function getStats(): array
    {
        $rows = DB::table('contact_submissions')
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->all();

        $byType = DB::table('contact_submissions')
            ->selectRaw('inquiry_type, COUNT(*) as cnt')
            ->groupBy('inquiry_type')
            ->pluck('cnt', 'inquiry_type')
            ->all();

        return [
            'new' => (int) ($rows['new'] ?? 0),
            'read' => (int) ($rows['read'] ?? 0),
            'in_progress' => (int) ($rows['in_progress'] ?? 0),
            'replied' => (int) ($rows['replied'] ?? 0),
            'archived' => (int) ($rows['archived'] ?? 0),
            'prayer' => (int) ($byType['prayer'] ?? 0),
            'visit' => (int) ($byType['visit'] ?? 0),
            'general' => (int) ($byType['general'] ?? 0),
            'total' => (int) array_sum($rows),
        ];
    }
}
