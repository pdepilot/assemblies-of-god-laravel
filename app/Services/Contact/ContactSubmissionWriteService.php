<?php

namespace App\Services\Contact;

use App\Services\Security\SecurityAuditService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ContactSubmissionWriteService
{
    public function __construct(
        private readonly ContactMailService $mail,
        private readonly SecurityAuditService $audit,
    ) {}

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function update(int $id, array $input, int $adminId): array
    {
        $existing = DB::table('contact_submissions')->where('id', $id)->first();
        if (! $existing) {
            throw new InvalidArgumentException('Message not found.');
        }

        $status = strtolower(trim((string) ($input['status'] ?? $existing->status)));
        if (! in_array($status, ContactSubmissionReadService::STATUSES, true)) {
            throw new InvalidArgumentException('Invalid status.');
        }

        $notes = trim((string) ($input['admin_notes'] ?? ($existing->admin_notes ?? '')));
        $readAt = $existing->read_at;
        if ($status !== 'new' && $readAt === null) {
            $readAt = now();
        }

        $repliedAt = $existing->replied_at;
        if ($status === 'replied') {
            $repliedAt = now();
        }

        DB::table('contact_submissions')->where('id', $id)->update([
            'status' => $status,
            'admin_notes' => $notes !== '' ? $notes : null,
            'read_at' => $readAt,
            'replied_at' => $repliedAt,
            'handled_by' => $adminId,
            'updated_at' => now(),
        ]);

        $read = new ContactSubmissionReadService;

        return $read->getSubmission($id) ?? (array) $existing;
    }

    public function delete(int $id): void
    {
        $existing = DB::table('contact_submissions')->where('id', $id)->first();
        if (! $existing) {
            throw new InvalidArgumentException('Message not found.');
        }

        DB::table('contact_submissions')->where('id', $id)->delete();
    }

    public function markRead(int $id, int $adminId): void
    {
        $existing = DB::table('contact_submissions')->where('id', $id)->first();
        if (! $existing || $existing->status !== 'new') {
            return;
        }

        DB::table('contact_submissions')->where('id', $id)->update([
            'status' => 'read',
            'read_at' => $existing->read_at ?? now(),
            'handled_by' => $adminId,
            'updated_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    public function reply(int $id, string $replySubject, string $replyBody, int $adminId): array
    {
        $existing = DB::table('contact_submissions')->where('id', $id)->first();
        if (! $existing) {
            throw new InvalidArgumentException('Message not found.');
        }

        $replySubject = trim($replySubject);
        $replyBody = trim($replyBody);
        if ($replySubject === '') {
            $replySubject = 'Re: '.(string) $existing->subject;
        }
        if (mb_strlen($replyBody) < 5) {
            throw new InvalidArgumentException('Please write a reply of at least 5 characters.');
        }

        $this->mail->sendManualReply((array) $existing, $replySubject, $replyBody, $adminId);

        DB::table('contact_submissions')->where('id', $id)->update([
            'reply_subject' => $replySubject,
            'reply_body' => $replyBody,
            'status' => 'replied',
            'replied_at' => now(),
            'read_at' => $existing->read_at ?? now(),
            'handled_by' => $adminId,
            'updated_at' => now(),
        ]);

        $read = new ContactSubmissionReadService;

        return $read->getSubmission($id) ?? (array) $existing;
    }

    /**
     * Public website contact form submission.
     *
     * @param  array<string, mixed>  $input
     * @return array{submission_code: string, inquiry_type: string, id: int, ack_sent: bool}
     */
    public function submitInquiry(array $input, ?string $ip = null, ?string $userAgent = null): array
    {
        $type = strtolower(trim((string) ($input['inquiry_type'] ?? 'general')));
        if (! in_array($type, ContactSubmissionReadService::INQUIRY_TYPES, true)) {
            $type = 'general';
        }

        $name = trim((string) ($input['name'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $phone = trim((string) ($input['phone'] ?? ''));
        $subject = trim((string) ($input['subject'] ?? ''));
        $message = trim((string) ($input['message'] ?? ''));

        if ($name === '' || mb_strlen($name) < 2) {
            throw new InvalidArgumentException('Please enter your full name.');
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Please enter a valid email address.');
        }
        if ($subject === '' || mb_strlen($subject) < 3) {
            throw new InvalidArgumentException('Please enter a subject or prayer topic.');
        }
        if ($message === '' || mb_strlen($message) < 10) {
            throw new InvalidArgumentException('Please enter a message of at least 10 characters.');
        }

        if (trim((string) ($input['ag_hp_trap'] ?? '')) !== '') {
            throw new InvalidArgumentException('Unable to submit your message. Please try again.');
        }

        $code = $this->generateSubmissionCode();

        $id = (int) DB::table('contact_submissions')->insertGetId([
            'submission_code' => $code,
            'inquiry_type' => $type,
            'full_name' => $name,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'subject' => $subject,
            'message' => $message,
            'status' => 'new',
            'ip_address' => $ip,
            'user_agent' => $userAgent !== null ? mb_substr($userAgent, 0, 500) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $submission = [
            'id' => $id,
            'submission_code' => $code,
            'inquiry_type' => $type,
            'full_name' => $name,
            'email' => $email,
            'subject' => $subject,
        ];

        $ackSent = $this->mail->sendAcknowledgement($submission);

        $this->audit->log(
            'contact_submission',
            'New contact from '.$name.': '.$subject.' ('.$code.').',
            null,
            'info',
            [
                'submission_id' => $id,
                'submission_code' => $code,
                'inquiry_type' => $type,
            ],
        );

        return [
            'id' => $id,
            'submission_code' => $code,
            'inquiry_type' => $type,
            'ack_sent' => $ackSent,
        ];
    }

    private function generateSubmissionCode(): string
    {
        do {
            $code = 'CNT-'.strtoupper(bin2hex(random_bytes(4)));
        } while (DB::table('contact_submissions')->where('submission_code', $code)->exists());

        return $code;
    }
}
