<?php

namespace App\Services\CommunicationHub;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class BirthdayReadService
{
    /**
     * @return array{
     *   today: list<array<string, mixed>>,
     *   upcoming: list<array<string, mixed>>,
     *   stats: array<string, int|bool>,
     *   history: list<array<string, mixed>>,
     *   days: int,
     *   auto_email: bool,
     *   auto_sms: bool
     * }
     */
    public function board(int $withinDays = 365, int $limit = 200): array
    {
        $withinDays = max(1, min(366, $withinDays));
        $limit = max(1, min(500, $limit));

        $today = [];
        $upcoming = [];
        $withEmail = 0;
        $withPhone = 0;
        $totalWithDob = 0;

        if (Schema::hasTable('members')) {
            $rows = DB::table('members')
                ->whereNotNull('date_of_birth')
                ->where('date_of_birth', '>', '1900-01-01')
                ->where(function ($q) {
                    $q->whereNull('status')
                        ->orWhereNotIn('status', ['deceased', 'inactive']);
                })
                ->orderBy('full_name')
                ->get([
                    'id', 'member_code', 'full_name', 'date_of_birth', 'email', 'phone',
                    'parent_email', 'parent_phone', 'department', 'gender', 'status',
                ]);

            foreach ($rows as $row) {
                $dob = (string) ($row->date_of_birth ?? '');
                $daysUntil = $this->daysUntilBirthday($dob);
                if ($daysUntil === null) {
                    continue;
                }

                $totalWithDob++;
                $email = $this->resolveEmail((array) $row);
                $phone = $this->resolvePhone((array) $row);
                if ($email !== '') {
                    $withEmail++;
                }
                if ($phone !== '') {
                    $withPhone++;
                }

                $nextDate = Carbon::today()->addDays($daysUntil);
                $item = [
                    'member_id' => (int) $row->id,
                    'member_code' => (string) ($row->member_code ?? ''),
                    'full_name' => (string) ($row->full_name ?? ''),
                    'date_of_birth' => $dob,
                    'birthday_display' => $nextDate->format('j M'),
                    'next_birthday' => $nextDate->toDateString(),
                    'age' => $this->ageAt($dob, Carbon::today()),
                    'turning_age' => $this->ageAt($dob, $nextDate),
                    'days_until' => $daysUntil,
                    'is_today' => $daysUntil === 0,
                    'email' => $email,
                    'phone' => $phone,
                    'department' => (string) ($row->department ?? ''),
                    'already_sent' => $daysUntil === 0 ? $this->alreadySent((int) $row->id, 'email') : false,
                    'already_sent_sms' => $daysUntil === 0 ? $this->alreadySent((int) $row->id, 'sms') : false,
                ];

                if ($daysUntil === 0) {
                    $today[] = $item;
                } elseif ($daysUntil <= $withinDays) {
                    $upcoming[] = $item;
                }
            }

            usort($upcoming, static function (array $a, array $b): int {
                $cmp = $a['days_until'] <=> $b['days_until'];

                return $cmp !== 0 ? $cmp : strcmp($a['full_name'], $b['full_name']);
            });
            $upcoming = array_slice($upcoming, 0, $limit);
        }

        $emailSentToday = 0;
        $smsSentToday = 0;
        if (Schema::hasTable('birthday_logs')) {
            $emailSentToday = (int) DB::table('birthday_logs')
                ->whereDate('birthday_date', Carbon::today()->toDateString())
                ->where('email_sent', true)
                ->count();
            $smsSentToday = (int) DB::table('birthday_logs')
                ->whereDate('birthday_date', Carbon::today()->toDateString())
                ->where('sms_sent', true)
                ->count();
        }

        return [
            'today' => $today,
            'upcoming' => $upcoming,
            'stats' => [
                'today' => count($today),
                'upcoming' => count($upcoming),
                'members_with_dob' => $totalWithDob,
                'with_email' => $withEmail,
                'with_phone' => $withPhone,
                'sent_today' => $emailSentToday + $smsSentToday,
                'email_sent_today' => $emailSentToday,
                'sms_sent_today' => $smsSentToday,
            ],
            'history' => $this->history(30),
            'days' => $withinDays,
            'auto_email' => $this->isAutoEmailEnabled(),
            'auto_sms' => $this->isAutoSmsEnabled(),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function history(int $limit = 30): array
    {
        if (! Schema::hasTable('birthday_logs')) {
            return [];
        }

        return DB::table('birthday_logs as bl')
            ->leftJoin('members as m', 'm.id', '=', 'bl.member_id')
            ->orderByDesc('bl.sent_at')
            ->orderByDesc('bl.id')
            ->limit(max(1, min(100, $limit)))
            ->get([
                'bl.id', 'bl.member_id', 'bl.birthday_date', 'bl.parent_email',
                'bl.email_sent', 'bl.sms_sent', 'bl.sms_phone', 'bl.message_subject', 'bl.sent_at',
                'm.full_name',
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'member_id' => (int) $row->member_id,
                'full_name' => (string) ($row->full_name ?? 'Member #'.$row->member_id),
                'birthday_date' => (string) ($row->birthday_date ?? ''),
                'email_sent' => (bool) $row->email_sent,
                'sms_sent' => (bool) $row->sms_sent,
                'sms_phone' => (string) ($row->sms_phone ?? ''),
                'message_subject' => (string) ($row->message_subject ?? ''),
                'sent_at' => (string) ($row->sent_at ?? ''),
                'parent_email' => (string) ($row->parent_email ?? ''),
            ])
            ->all();
    }

    public function isAutoEmailEnabled(): bool
    {
        if (! Schema::hasTable('communication_settings') || ! Schema::hasColumn('communication_settings', 'birthday_auto_email_enabled')) {
            return true;
        }

        $value = DB::table('communication_settings')->where('id', 1)->value('birthday_auto_email_enabled');

        return $value === null ? true : (bool) $value;
    }

    public function isAutoSmsEnabled(): bool
    {
        if (! Schema::hasTable('communication_settings') || ! Schema::hasColumn('communication_settings', 'birthday_auto_sms_enabled')) {
            return true;
        }

        $value = DB::table('communication_settings')->where('id', 1)->value('birthday_auto_sms_enabled');

        return $value === null ? true : (bool) $value;
    }

    public function setAutoEmailEnabled(bool $enabled, int $adminId): void
    {
        if (! Schema::hasTable('communication_settings')) {
            return;
        }

        DB::table('communication_settings')->updateOrInsert(
            ['id' => 1],
            [
                'birthday_auto_email_enabled' => $enabled,
                'updated_by' => $adminId,
                'updated_at' => now(),
            ]
        );
    }

    public function setAutoSmsEnabled(bool $enabled, int $adminId): void
    {
        if (! Schema::hasTable('communication_settings') || ! Schema::hasColumn('communication_settings', 'birthday_auto_sms_enabled')) {
            return;
        }

        DB::table('communication_settings')->updateOrInsert(
            ['id' => 1],
            [
                'birthday_auto_sms_enabled' => $enabled,
                'updated_by' => $adminId,
                'updated_at' => now(),
            ]
        );
    }

    private function alreadySent(int $memberId, string $channel): bool
    {
        if (! Schema::hasTable('birthday_logs')) {
            return false;
        }

        $query = DB::table('birthday_logs')
            ->where('member_id', $memberId)
            ->whereDate('birthday_date', Carbon::today()->toDateString());

        if ($channel === 'sms') {
            $query->where('sms_sent', true);
        } else {
            $query->where('email_sent', true);
        }

        return $query->exists();
    }

    /** @param array<string, mixed> $row */
    private function resolveEmail(array $row): string
    {
        foreach (['email', 'parent_email'] as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return $value;
            }
        }

        return '';
    }

    /** @param array<string, mixed> $row */
    private function resolvePhone(array $row): string
    {
        foreach (['phone', 'parent_phone'] as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function daysUntilBirthday(string $dob): ?int
    {
        try {
            $birth = Carbon::parse($dob)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ($birth->year < 1901) {
            return null;
        }

        $today = Carbon::today();
        $next = $birth->copy()->year($today->year)->startOfDay();
        if ($next->lt($today)) {
            $next->addYear();
        }

        return (int) $today->diffInDays($next, false);
    }

    private function ageAt(string $dob, Carbon $on): ?int
    {
        try {
            return Carbon::parse($dob)->diffInYears($on);
        } catch (\Throwable) {
            return null;
        }
    }
}
