<?php

namespace App\Services\CommunicationHub;

use Illuminate\Support\Facades\DB;

final class HubDashboardReadService
{
    /** @return array<string, mixed> */
    public function getStats(int $adminId): array
    {
        $today = now()->toDateString();

        return [
            'templates' => (int) DB::table('communication_templates')->count(),
            'logs_today' => (int) DB::table('communication_logs')
                ->whereDate('created_at', $today)
                ->count(),
            'emails_today' => (int) DB::table('email_history')
                ->whereDate('created_at', $today)
                ->whereIn('status', ['sent', 'delivered', 'opened', 'clicked'])
                ->count(),
            'new_contacts' => (int) DB::table('contact_submissions')->where('status', 'new')->count(),
            'active_subscribers' => (int) DB::table('site_newsletter_subscribers')->where('status', 'active')->count(),
            'unread_notifications' => (int) DB::table('notification_center')
                ->where('is_archived', false)
                ->where('is_read', false)
                ->where(function ($q) use ($adminId) {
                    $q->whereNull('admin_id')->orWhere('admin_id', $adminId);
                })
                ->count(),
            'newsletter_drafts' => (int) DB::table('newsletter_drafts')->where('status', 'draft')->count(),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function recentLogs(int $limit = 10): array
    {
        $items = DB::table('communication_logs')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        if ($items !== []) {
            return $items;
        }

        return DB::table('email_history')
            ->orderByDesc('id')
            ->limit($limit)
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
    }
}
