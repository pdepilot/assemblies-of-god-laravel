<?php

namespace App\Services\Newsletter;

use App\Services\CommunicationHub\EmailCenterWriteService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class SubscriberWriteService
{
    public function __construct(
        private readonly EmailCenterWriteService $emailCenter,
        private readonly SubscriberReadService $read,
    ) {}

    /** @return array<string, mixed> */
    public function setStatus(int $id, string $status): array
    {
        if (! in_array($status, SubscriberReadService::STATUSES, true)) {
            throw new InvalidArgumentException('Invalid status.');
        }

        $existing = DB::table('site_newsletter_subscribers')->where('id', $id)->first();
        if (! $existing) {
            throw new InvalidArgumentException('Subscriber not found.');
        }

        if ($status === 'unsubscribed') {
            DB::table('site_newsletter_subscribers')->where('id', $id)->update([
                'status' => 'unsubscribed',
                'unsubscribed_at' => $existing->unsubscribed_at ?? now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('site_newsletter_subscribers')->where('id', $id)->update([
                'status' => 'active',
                'unsubscribed_at' => null,
                'updated_at' => now(),
            ]);
        }

        return $this->read->getSubscriber($id) ?? (array) $existing;
    }

    public function delete(int $id): void
    {
        $existing = DB::table('site_newsletter_subscribers')->where('id', $id)->first();
        if (! $existing) {
            throw new InvalidArgumentException('Subscriber not found.');
        }

        DB::table('site_newsletter_subscribers')->where('id', $id)->delete();
    }

    /**
     * @param  list<int|string>  $ids
     * @return array{sent: int, failed: int, scheduled: int, total: int}
     */
    public function sendToSelected(array $ids, string $subject, string $body, int $adminId, int $templateId = 0): array
    {
        $subscribers = $this->getActiveByIds($ids);
        if ($subscribers === []) {
            throw new InvalidArgumentException('Select at least one active subscriber.');
        }

        return $this->emailCenter->compose([
            'recipient_group' => 'selected',
            'selected_emails' => implode(',', array_column($subscribers, 'email')),
            'subject' => $subject,
            'body_html' => $body,
            'template_id' => $templateId > 0 ? $templateId : null,
            'priority' => 'normal',
        ], $adminId);
    }

    /**
     * @return array{sent: int, failed: int, scheduled: int, total: int}
     */
    public function sendToAllActive(string $subject, string $body, int $adminId, int $templateId = 0): array
    {
        return $this->emailCenter->compose([
            'recipient_group' => 'website_subscribers',
            'subject' => $subject,
            'body_html' => $body,
            'template_id' => $templateId > 0 ? $templateId : null,
            'priority' => 'normal',
        ], $adminId);
    }

    /**
     * @param  list<int|string>  $ids
     * @return list<array{id: int, email: string}>
     */
    public function getActiveByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn (int $id): bool => $id > 0,
        )));

        if ($ids === []) {
            return [];
        }

        return DB::table('site_newsletter_subscribers')
            ->where('status', 'active')
            ->whereIn('id', $ids)
            ->orderBy('email')
            ->get(['id', 'email'])
            ->map(function ($row) {
                $email = strtolower(trim((string) $row->email));
                if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return null;
                }

                return ['id' => (int) $row->id, 'email' => $email];
            })
            ->filter()
            ->values()
            ->all();
    }
}
