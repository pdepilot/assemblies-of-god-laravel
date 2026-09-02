<?php

namespace App\Services\CommunicationHub;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

final class EmailCenterWriteService
{
    public function __construct(
        private readonly EmailTemplateBodyService $bodies,
        private readonly HubMailConfigurator $mail,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{sent: int, failed: int, scheduled: int, total: int}
     */
    public function compose(array $data, int $adminId): array
    {
        if (! Schema::hasTable('email_history')) {
            throw new InvalidArgumentException('Email history is not available.');
        }

        $group = trim((string) ($data['recipient_group'] ?? 'individual'));
        $recipients = $this->resolveRecipients(
            $group,
            trim((string) ($data['individual_email'] ?? '')),
            $data,
        );
        if ($recipients === []) {
            throw new InvalidArgumentException('No recipients found for the selected group.');
        }

        $templateId = ! empty($data['template_id']) ? (int) $data['template_id'] : 0;
        $subject = trim((string) ($data['subject'] ?? ''));
        $bodyPlain = $this->bodies->toPlainText((string) ($data['body_html'] ?? ''));
        $templateSlug = null;

        if ($templateId > 0 && ($subject === '' || $bodyPlain === '')) {
            $tpl = $this->findTemplate($templateId);
            if ($tpl !== null) {
                if ($subject === '') {
                    $subject = (string) ($tpl['subject'] ?? '');
                }
                if ($bodyPlain === '') {
                    $bodyPlain = $this->bodies->toPlainText((string) ($tpl['body_text'] ?? $tpl['body_html'] ?? ''));
                }
                $templateSlug = (string) ($tpl['slug'] ?? null);
            }
        }

        if ($subject === '') {
            throw new InvalidArgumentException('Subject is required.');
        }
        if ($bodyPlain === '') {
            throw new InvalidArgumentException('Message body is required.');
        }

        $bodyHtml = $this->bodies->toHtml($bodyPlain);

        $scheduledAt = trim((string) ($data['scheduled_at'] ?? ''));
        $scheduleValue = $scheduledAt !== '' ? date('Y-m-d H:i:s', strtotime($scheduledAt) ?: time()) : null;
        $priority = in_array(($data['priority'] ?? 'normal'), ['low', 'normal', 'high'], true)
            ? (string) $data['priority']
            : 'normal';

        $batchId = bin2hex(random_bytes(8));
        $sent = 0;
        $failed = 0;
        $scheduled = 0;

        foreach ($recipients as $recipient) {
            $token = Str::random(64);
            $now = now()->format('Y-m-d H:i:s');

            if ($scheduleValue !== null && strtotime($scheduleValue) > time()) {
                DB::table('email_history')->insert([
                    'tracking_token' => $token,
                    'subject' => $subject,
                    'recipient' => $recipient['email'],
                    'recipient_name' => $recipient['name'],
                    'template_id' => $templateId > 0 ? $templateId : null,
                    'template_slug' => $templateSlug,
                    'recipient_group' => $group,
                    'batch_id' => $batchId,
                    'sent_by' => $adminId,
                    'provider' => 'laravel',
                    'status' => 'scheduled',
                    'scheduled_at' => $scheduleValue,
                    'created_at' => $now,
                ]);
                $this->writeLog($adminId, $subject, $recipient, $bodyHtml, 'scheduled', $priority, null);
                $scheduled++;
                continue;
            }

            $status = 'failed';
            $error = null;
            $sentAt = null;

            try {
                $this->mail->sendHtml($recipient, $subject, $bodyHtml);
                $status = 'sent';
                $sentAt = $now;
                $sent++;
            } catch (Throwable $e) {
                $error = $e->getMessage();
                $failed++;
            }

            DB::table('email_history')->insert([
                'tracking_token' => $token,
                'subject' => $subject,
                'recipient' => $recipient['email'],
                'recipient_name' => $recipient['name'],
                'template_id' => $templateId > 0 ? $templateId : null,
                'template_slug' => $templateSlug,
                'recipient_group' => $group,
                'batch_id' => $batchId,
                'sent_by' => $adminId,
                'provider' => 'smtp',
                'status' => $status,
                'error_message' => $error,
                'sent_at' => $sentAt,
                'created_at' => $now,
            ]);

            $this->writeLog($adminId, $subject, $recipient, $bodyHtml, $status, $priority, $error);
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'scheduled' => $scheduled,
            'total' => count($recipients),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{email: string, name: string}>
     */
    private function resolveRecipients(string $group, string $individualEmail, array $data = []): array
    {
        if ($group === 'individual' || $group === '') {
            if ($individualEmail === '' || ! filter_var($individualEmail, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('A valid individual email is required.');
            }

            return [['email' => $individualEmail, 'name' => '']];
        }

        if ($group === 'selected') {
            return $this->resolveSelectedEmails((string) ($data['selected_emails'] ?? ''));
        }

        if (in_array($group, ['website_subscribers', 'newsletter_subscribers'], true)
            && Schema::hasTable('site_newsletter_subscribers')) {
            return DB::table('site_newsletter_subscribers')
                ->where('status', 'active')
                ->orderBy('email')
                ->limit(2000)
                ->get(['email'])
                ->map(fn ($row) => [
                    'email' => strtolower(trim((string) $row->email)),
                    'name' => '',
                ])
                ->filter(fn (array $r) => filter_var($r['email'], FILTER_VALIDATE_EMAIL))
                ->unique('email')
                ->values()
                ->all();
        }

        if ($group === 'members' && Schema::hasTable('members')) {
            return DB::table('members')
                ->whereNotNull('email')
                ->where('email', '<>', '')
                ->whereNotIn('status', ['deceased'])
                ->orderBy('full_name')
                ->limit(500)
                ->get(['email', 'full_name'])
                ->map(fn ($row) => [
                    'email' => (string) $row->email,
                    'name' => (string) ($row->full_name ?? ''),
                ])
                ->filter(fn (array $r) => filter_var($r['email'], FILTER_VALIDATE_EMAIL))
                ->values()
                ->all();
        }

        if ($group === 'visitors' && Schema::hasTable('visitors')) {
            return DB::table('visitors')
                ->whereNotNull('email')
                ->where('email', '<>', '')
                ->orderByDesc('id')
                ->limit(500)
                ->get(['email', 'full_name'])
                ->map(fn ($row) => [
                    'email' => (string) $row->email,
                    'name' => (string) ($row->full_name ?? ''),
                ])
                ->filter(fn (array $r) => filter_var($r['email'], FILTER_VALIDATE_EMAIL))
                ->values()
                ->all();
        }

        throw new InvalidArgumentException('Unknown recipient group.');
    }

    /**
     * @return list<array{email: string, name: string}>
     */
    private function resolveSelectedEmails(string $raw): array
    {
        $parts = preg_split('/[\s,;]+/', $raw) ?: [];
        $emails = [];

        foreach ($parts as $part) {
            $email = strtolower(trim((string) $part));
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $emails[$email] = ['email' => $email, 'name' => ''];
        }

        if ($emails === []) {
            throw new InvalidArgumentException('Select at least one valid email address.');
        }

        return array_values($emails);
    }

    /** @return array<string, mixed>|null */
    private function findTemplate(int $id): ?array
    {
        if (Schema::hasTable('email_templates')) {
            $row = DB::table('email_templates')->where('id', $id)->first();
            if ($row) {
                return (array) $row;
            }
        }

        if (Schema::hasTable('communication_templates')) {
            $row = DB::table('communication_templates')
                ->where('channel', 'email')
                ->where(function ($q) use ($id) {
                    $q->where('id', $id)->orWhere('email_template_id', $id);
                })
                ->first();
            if ($row) {
                return (array) $row;
            }
        }

        return null;
    }

    public function deleteHistory(int $id): void
    {
        if (! Schema::hasTable('email_history')) {
            throw new InvalidArgumentException('Email history is not available.');
        }

        $deleted = DB::table('email_history')->where('id', $id)->delete();
        if ($deleted === 0) {
            throw new InvalidArgumentException('Email record not found.');
        }
    }

    /**
     * @param  list<int>  $ids
     */
    public function deleteHistoryMany(array $ids): int
    {
        if (! Schema::hasTable('email_history')) {
            throw new InvalidArgumentException('Email history is not available.');
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            throw new InvalidArgumentException('Select at least one email to delete.');
        }

        return (int) DB::table('email_history')->whereIn('id', $ids)->delete();
    }

    /**
     * @param  array{email: string, name: string}  $recipient
     */
    private function writeLog(
        int $adminId,
        string $subject,
        array $recipient,
        string $bodyHtml,
        string $status,
        string $priority,
        ?string $error,
    ): void {
        if (! Schema::hasTable('communication_logs')) {
            return;
        }

        DB::table('communication_logs')->insert([
            'channel' => 'email',
            'direction' => 'outbound',
            'subject' => $subject,
            'sender' => (string) ($this->mail->emailSettings()['from_email'] ?? config('mail.from.address', '')),
            'recipient' => $recipient['email'],
            'recipient_name' => $recipient['name'] !== '' ? $recipient['name'] : null,
            'status' => $status,
            'priority' => $priority,
            'error_message' => $error,
            'sent_by' => $adminId,
            'created_at' => now(),
        ]);
    }
}
