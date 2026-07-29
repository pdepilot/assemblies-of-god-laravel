<?php

namespace App\Services\CommunicationHub;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class QueueReadService
{
    public const JOBS_PER_PAGE = 15;

    /** @return array<string, int> */
    public function overview(): array
    {
        return [
            'email_pending' => $this->countEmailPending(),
            'sms_pending' => $this->safeCount('sms_queue', 'pending'),
            'email_failed' => $this->countEmailFailed(),
            'sms_failed' => $this->safeCount('sms_queue', 'failed'),
            'scheduled' => $this->countScheduled(),
        ];
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function recentJobs(int $perPage = self::JOBS_PER_PAGE): LengthAwarePaginator
    {
        $perPage = max(1, min(100, $perPage));
        $unions = $this->jobUnionQueries();

        if ($unions === []) {
            return new Paginator([], 0, $perPage, 1, [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]);
        }

        /** @var Builder $base */
        $base = array_shift($unions);
        foreach ($unions as $union) {
            $base->unionAll($union);
        }

        $paginator = DB::query()
            ->fromSub($base, 'queue_jobs')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $paginator->setCollection(
            $paginator->getCollection()->map(static fn (object $row): array => [
                'id' => (int) $row->id,
                'channel' => (string) $row->channel,
                'source' => (string) $row->source,
                'preview' => (string) ($row->preview ?? ''),
                'detail' => (string) ($row->detail ?? ''),
                'status' => (string) ($row->status ?? ''),
                'scheduled_at' => (string) ($row->scheduled_at ?? ''),
                'error' => (string) ($row->error ?? ''),
                'created_at' => (string) ($row->created_at ?? ''),
            ])
        );

        return $paginator;
    }

    /** @return list<Builder> */
    private function jobUnionQueries(): array
    {
        $unions = [];

        if (Schema::hasTable('sms_queue')) {
            $unions[] = DB::table('sms_queue')
                ->selectRaw("
                    id,
                    'sms' as channel,
                    'sms_queue' as source,
                    recipient_phone as preview,
                    SUBSTR(message_body, 1, 80) as detail,
                    status,
                    CAST(scheduled_at AS CHAR) as scheduled_at,
                    COALESCE(error_message, '') as error,
                    CAST(created_at AS CHAR) as created_at
                ")
                ->whereIn('status', ['pending', 'failed', 'processing']);
        }

        if (Schema::hasTable('email_queue')) {
            $unions[] = DB::table('email_queue')
                ->selectRaw("
                    id,
                    'email' as channel,
                    'email_queue' as source,
                    recipient as preview,
                    subject as detail,
                    status,
                    CAST(scheduled_at AS CHAR) as scheduled_at,
                    COALESCE(error_message, '') as error,
                    CAST(created_at AS CHAR) as created_at
                ")
                ->whereIn('status', ['pending', 'failed', 'processing']);
        }

        if (Schema::hasTable('email_history')) {
            $unions[] = DB::table('email_history')
                ->selectRaw("
                    id,
                    'email' as channel,
                    'email_history' as source,
                    recipient as preview,
                    subject as detail,
                    status,
                    CAST(COALESCE(scheduled_at, created_at) AS CHAR) as scheduled_at,
                    COALESCE(error_message, '') as error,
                    CAST(created_at AS CHAR) as created_at
                ")
                ->whereIn('status', ['scheduled', 'queued', 'pending', 'failed']);
        }

        return $unions;
    }

    private function countEmailPending(): int
    {
        $count = $this->safeCount('email_queue', 'pending');

        if (Schema::hasTable('email_history')) {
            $count += (int) DB::table('email_history')
                ->where(function ($q) {
                    $q->whereIn('status', ['queued', 'pending'])
                        ->orWhere(function ($q2) {
                            $q2->where('status', 'scheduled')
                                ->whereNotNull('scheduled_at')
                                ->where('scheduled_at', '<=', now());
                        });
                })
                ->count();
        }

        return $count;
    }

    private function countEmailFailed(): int
    {
        $count = $this->safeCount('email_queue', 'failed');

        if (Schema::hasTable('email_history')) {
            $count += (int) DB::table('email_history')->where('status', 'failed')->count();
        }

        return $count;
    }

    private function countScheduled(): int
    {
        $count = 0;

        if (Schema::hasTable('scheduled_messages')) {
            $count += (int) DB::table('scheduled_messages')->where('status', 'scheduled')->count();
        }

        if (Schema::hasTable('email_history')) {
            $count += (int) DB::table('email_history')
                ->where('status', 'scheduled')
                ->where(function ($q) {
                    $q->whereNull('scheduled_at')->orWhere('scheduled_at', '>', now());
                })
                ->count();
        }

        if (Schema::hasTable('sms_queue')) {
            $count += (int) DB::table('sms_queue')
                ->where('status', 'pending')
                ->where('scheduled_at', '>', now())
                ->count();
        }

        return $count;
    }

    private function safeCount(string $table, string $status): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)->where('status', $status)->count();
    }
}
