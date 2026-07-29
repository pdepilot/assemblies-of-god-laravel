<?php

namespace App\Services\Contact;

use App\Services\CommunicationHub\HubMailConfigurator;
use App\Services\Settings\PlatformSettingsReadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class ContactMailService
{
    public function __construct(
        private readonly HubMailConfigurator $mail,
        private readonly PlatformSettingsReadService $platformSettings,
    ) {}

    /**
     * Send acknowledgement after a public form submit. Failures are logged, never thrown.
     *
     * @param  array<string, mixed>  $submission
     */
    public function sendAcknowledgement(array $submission): bool
    {
        $email = trim((string) ($submission['email'] ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $type = (string) ($submission['inquiry_type'] ?? 'general');
        $name = (string) ($submission['full_name'] ?? 'Friend');
        $code = (string) ($submission['submission_code'] ?? '');
        $subjectLine = (string) ($submission['subject'] ?? '');
        $settings = $this->publicSettings();

        $church = (string) ($settings['church_name'] ?? 'AGC Ikenebgu');
        $phone = (string) (($settings['phone_display'] ?? '') ?: ($settings['phone'] ?? ''));
        $officeEmail = (string) ($settings['email'] ?? '');
        $sunday = (string) ($settings['sunday_worship'] ?? '');
        $midweek = (string) ($settings['midweek_service'] ?? '');
        $prayer = (string) ($settings['prayer_meeting'] ?? '');

        [$mailSubject, $bodyHtml] = $this->buildAcknowledgementContent(
            $type,
            $name,
            $code,
            $subjectLine,
            $church,
            $phone,
            $officeEmail,
            $sunday,
            $midweek,
            $prayer,
        );

        try {
            $this->mail->sendHtml([
                'email' => $email,
                'name' => $name,
            ], $mailSubject, $bodyHtml);

            if (! empty($submission['id'])) {
                DB::table('contact_submissions')->where('id', (int) $submission['id'])->update([
                    'ack_sent_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return true;
        } catch (Throwable $e) {
            Log::warning('[AG Contact Ack] '.$e->getMessage(), [
                'submission_id' => $submission['id'] ?? null,
                'email' => $email,
            ]);

            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $submission
     */
    public function sendManualReply(array $submission, string $replySubject, string $replyBody, int $adminId): void
    {
        $to = trim((string) ($submission['email'] ?? ''));
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('This message does not have a valid email address.');
        }

        $name = (string) ($submission['full_name'] ?? '');
        $code = (string) ($submission['submission_code'] ?? '');
        $settings = $this->publicSettings();
        $church = (string) ($settings['church_name'] ?? 'AGC Ikenebgu');
        $phone = (string) (($settings['phone_display'] ?? '') ?: ($settings['phone'] ?? ''));
        $officeEmail = (string) ($settings['email'] ?? '');

        $safeBody = nl2br(e($replyBody), false);
        $bodyHtml = '<p style="margin:0 0 16px;font-size:16px;line-height:1.6;">Dear '
            .e($name).',</p>'
            .'<div style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#1a2b5c;">'.$safeBody.'</div>'
            .'<p style="margin:0 0 8px;font-size:14px;line-height:1.6;color:#475569;">Reference: <strong>'
            .e($code).'</strong></p>'
            .'<p style="margin:24px 0 0;font-size:15px;line-height:1.6;">With care,<br><strong>'
            .e($church).'</strong>';

        if ($phone !== '') {
            $bodyHtml .= '<br>'.e($phone);
        }
        if ($officeEmail !== '') {
            $bodyHtml .= '<br>'.e($officeEmail);
        }
        $bodyHtml .= '</p>';

        try {
            $this->mail->sendHtml([
                'email' => $to,
                'name' => $name,
            ], $replySubject, $bodyHtml);
        } catch (Throwable $e) {
            Log::error('[AG Contact Reply] '.$e->getMessage(), [
                'submission_id' => $submission['id'] ?? null,
                'admin_id' => $adminId,
            ]);

            throw new RuntimeException(
                'The reply could not be sent. Please check Communication Hub → Email / SMS settings and try again.',
                0,
                $e,
            );
        }
    }

    /** @return array<string, string> */
    public function publicSettings(): array
    {
        $defaults = [
            'church_name' => 'AGC Ikenebgu',
            'phone' => '',
            'phone_display' => '',
            'email' => '',
            'sunday_worship' => '',
            'midweek_service' => '',
            'prayer_meeting' => '',
        ];

        if (Schema::hasTable('contact_settings')) {
            $row = DB::table('contact_settings')->orderBy('id')->first();
            if ($row) {
                $data = (array) $row;

                return [
                    'church_name' => (string) ($data['church_name'] ?? $defaults['church_name']),
                    'phone' => (string) ($data['phone'] ?? ''),
                    'phone_display' => (string) ($data['phone_display'] ?? ''),
                    'email' => (string) ($data['email'] ?? ''),
                    'sunday_worship' => (string) ($data['sunday_worship'] ?? ''),
                    'midweek_service' => (string) ($data['midweek_service'] ?? ''),
                    'prayer_meeting' => (string) ($data['prayer_meeting'] ?? ''),
                ];
            }
        }

        $church = $this->platformSettings->getGroup('church');

        return [
            'church_name' => (string) ($church['name'] ?? $defaults['church_name']),
            'phone' => (string) ($church['phone'] ?? ''),
            'phone_display' => (string) ($church['phone'] ?? ''),
            'email' => (string) ($church['email'] ?? ''),
            'sunday_worship' => (string) ($church['service_sunday'] ?? ''),
            'midweek_service' => (string) ($church['service_midweek'] ?? ''),
            'prayer_meeting' => '',
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function buildAcknowledgementContent(
        string $type,
        string $name,
        string $code,
        string $subjectLine,
        string $church,
        string $phone,
        string $officeEmail,
        string $sunday,
        string $midweek,
        string $prayer,
    ): array {
        $safeName = e($name);
        $safeCode = e($code);
        $safeSubject = e($subjectLine);
        $safeChurch = e($church);

        $contactBits = '';
        if ($phone !== '') {
            $contactBits .= '<br>'.e($phone);
        }
        if ($officeEmail !== '') {
            $contactBits .= '<br>'.e($officeEmail);
        }

        if ($type === 'prayer') {
            $mailSubject = 'We received your prayer request — '.$church;
            $body = '<p style="margin:0 0 16px;font-size:16px;line-height:1.6;">Dear '.$safeName.',</p>'
                .'<p style="margin:0 0 16px;font-size:15px;line-height:1.7;">Thank you for trusting us with your prayer request'
                .($subjectLine !== '' ? ' about <strong>'.$safeSubject.'</strong>' : '')
                .'. Our intercessors at <strong>'.$safeChurch.'</strong> will stand with you in faith.</p>'
                .'<p style="margin:0 0 16px;font-size:15px;line-height:1.7;">“The prayer of a righteous person is powerful and effective.” — James 5:16</p>'
                .'<p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#475569;">Your reference number is <strong>'
                .$safeCode.'</strong>. Please keep it if you need to follow up with the church office.</p>'
                .'<p style="margin:24px 0 0;font-size:15px;line-height:1.6;">Standing with you in prayer,<br><strong>'
                .$safeChurch.'</strong>'.$contactBits.'</p>';

            return [$mailSubject, $body];
        }

        if ($type === 'visit') {
            $schedule = '';
            if ($sunday !== '') {
                $schedule .= '<li style="margin:0 0 6px;"><strong>Sunday Worship:</strong> '.e($sunday).'</li>';
            }
            if ($midweek !== '') {
                $schedule .= '<li style="margin:0 0 6px;"><strong>Midweek:</strong> '.e($midweek).'</li>';
            }
            if ($prayer !== '') {
                $schedule .= '<li style="margin:0 0 6px;"><strong>Prayer Meeting:</strong> '.e($prayer).'</li>';
            }

            $mailSubject = 'We look forward to welcoming you — '.$church;
            $body = '<p style="margin:0 0 16px;font-size:16px;line-height:1.6;">Dear '.$safeName.',</p>'
                .'<p style="margin:0 0 16px;font-size:15px;line-height:1.7;">Thank you for planning a visit to <strong>'
                .$safeChurch.'</strong>. We are excited to welcome you and your family.</p>'
                .($schedule !== ''
                    ? '<p style="margin:0 0 8px;font-size:15px;line-height:1.7;">Here are our regular service times:</p>'
                      .'<ul style="margin:0 0 16px;padding-left:20px;font-size:15px;line-height:1.7;">'.$schedule.'</ul>'
                    : '')
                .'<p style="margin:0 0 16px;font-size:15px;line-height:1.7;">Our team will review your note'
                .($subjectLine !== '' ? ' (<em>'.$safeSubject.'</em>)' : '')
                .' and follow up if needed. Your reference is <strong>'.$safeCode.'</strong>.</p>'
                .'<p style="margin:24px 0 0;font-size:15px;line-height:1.6;">See you soon,<br><strong>'
                .$safeChurch.'</strong>'.$contactBits.'</p>';

            return [$mailSubject, $body];
        }

        $mailSubject = 'We received your message — '.$church;
        $body = '<p style="margin:0 0 16px;font-size:16px;line-height:1.6;">Dear '.$safeName.',</p>'
            .'<p style="margin:0 0 16px;font-size:15px;line-height:1.7;">Thank you for contacting <strong>'
            .$safeChurch.'</strong>. We have received your enquiry'
            .($subjectLine !== '' ? ' about <strong>'.$safeSubject.'</strong>' : '')
            .' and will get back to you as soon as possible.</p>'
            .'<p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#475569;">Your reference number is <strong>'
            .$safeCode.'</strong>.</p>'
            .'<p style="margin:24px 0 0;font-size:15px;line-height:1.6;">Blessings,<br><strong>'
            .$safeChurch.'</strong>'.$contactBits.'</p>';

        return [$mailSubject, $body];
    }
}
