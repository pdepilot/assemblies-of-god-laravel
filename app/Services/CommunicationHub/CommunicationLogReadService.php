<?php

namespace App\Services\CommunicationHub;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class CommunicationLogReadService
{
    public const PER_PAGE = 10;

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function listLogs(string $channel = '', string $status = '', int $page = 1, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        if (Schema::hasTable('communication_logs')) {
            $query = DB::table('communication_logs');
            if ($channel !== '') {
                $query->where('channel', $channel);
            }
            if ($status !== '') {
                $query->where('status', $status);
            }

            $total = (clone $query)->count();

            if ($total > 0 || ($channel !== '' && $channel !== 'email')) {
                $items = $query->orderByDesc('id')
                    ->forPage($page, $perPage)
                    ->get()
                    ->map(fn ($row) => (array) $row + ['source' => 'communication_logs'])
                    ->all();

                return $this->paginator($items, $total, $perPage, $page, $channel, $status);
            }
        }

        if ($channel === '' || $channel === 'email') {
            return $this->listEmailHistory($status, $page, $perPage, $channel);
        }

        return $this->paginator([], 0, $perPage, $page, $channel, $status);
    }

    /** @return array<string, mixed>|null */
    public function getLog(int $id, ?string $source = null): ?array
    {
        if ($source === 'email_history') {
            $row = DB::table('email_history')->where('id', $id)->first();
            if (! $row) {
                return null;
            }

            return [
                'id' => $row->id,
                'channel' => 'email',
                'subject' => $row->subject,
                'recipient' => $row->recipient,
                'recipient_name' => $row->recipient_name,
                'template_slug' => $row->template_slug,
                'status' => $row->status,
                'opened' => $row->opened,
                'clicked' => $row->clicked,
                'error_message' => $row->error_message,
                'created_at' => $row->created_at,
                'source' => 'email_history',
            ];
        }

        $row = DB::table('communication_logs')->where('id', $id)->first();

        return $row ? (array) $row + ['source' => 'communication_logs'] : null;
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function listEmailHistory(string $status, int $page, int $perPage, string $channel): LengthAwarePaginator
    {
        if (! Schema::hasTable('email_history')) {
            return $this->paginator([], 0, $perPage, $page, $channel, $status);
        }

        $query = DB::table('email_history');
        if ($status !== '') {
            $query->where('status', $status);
        }

        $total = (clone $query)->count();
        $items = $query->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'channel' => 'email',
                'subject' => $row->subject,
                'recipient' => $row->recipient,
                'recipient_name' => $row->recipient_name,
                'template_slug' => $row->template_slug,
                'status' => $row->status,
                'opened' => $row->opened,
                'clicked' => $row->clicked,
                'created_at' => $row->created_at,
                'source' => 'email_history',
            ])
            ->all();

        return $this->paginator($items, $total, $perPage, $page, $channel, $status);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginator(array $items, int $total, int $perPage, int $page, string $channel, string $status): LengthAwarePaginator
    {
        return (new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        ))->appends(array_filter([
            'channel' => $channel !== '' ? $channel : null,
            'status' => $status !== '' ? $status : null,
        ]));
    }
}
