<?php

namespace App\Services\CommunicationHub;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Testing\Fakes\MailFake;
use InvalidArgumentException;

class HubMailConfigurator
{
    public const MAILER = 'communication_hub';

    public function __construct(
        private readonly HubSettingsReadService $settings,
        private readonly ChurchEmailBrandingService $branding,
    ) {}

    /** @return array<string, mixed> */
    public function emailSettings(): array
    {
        return $this->settings->getSettings()['email'] ?? [];
    }

    public function isSmtpConfigured(): bool
    {
        $settings = $this->emailSettings();

        return trim((string) ($settings['smtp_host'] ?? '')) !== '';
    }

    public function configure(): void
    {
        $settings = $this->emailSettings();
        $host = trim((string) ($settings['smtp_host'] ?? ''));

        if ($host === '') {
            throw new InvalidArgumentException(
                'SMTP is not configured. Open Communication Hub → Email / SMS settings and enter your Hostinger SMTP host, port, username, and password.'
            );
        }

        $port = (int) ($settings['smtp_port'] ?? 587);
        if ($port <= 0) {
            $port = 587;
        }

        $fromEmail = trim((string) ($settings['from_email'] ?? ''));
        if ($fromEmail === '' || ! filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('A valid From email is required in Email / SMS settings.');
        }

        Config::set([
            'mail.default' => self::MAILER,
            'mail.mailers.'.self::MAILER => [
                'transport' => 'smtp',
                'scheme' => $this->resolveScheme($settings, $port),
                'host' => $host,
                'port' => $port,
                'username' => (string) ($settings['smtp_user'] ?? ''),
                'password' => (string) ($settings['smtp_pass'] ?? ''),
                'timeout' => 30,
                'local_domain' => parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost',
            ],
            'mail.from' => [
                'address' => $fromEmail,
                'name' => trim((string) ($settings['from_name'] ?? 'Assemblies of God Ikenegbu')) ?: 'Assemblies of God Ikenegbu',
            ],
        ]);

        if (! Mail::getFacadeRoot() instanceof MailFake) {
            Mail::purge(self::MAILER);
        }
    }

    /**
     * @param  array{email: string, name: string}  $recipient
     */
    public function sendHtml(array $recipient, string $subject, string $bodyHtml): void
    {
        $this->configure();

        $settings = $this->branding->resolveBrandingSettings($this->emailSettings());
        $replyTo = trim((string) ($settings['reply_to'] ?? $this->emailSettings()['reply_to'] ?? ''));

        if (! $this->branding->isAlreadyBranded($bodyHtml)) {
            $bodyHtml = $this->branding->wrapHtml($bodyHtml, $settings, $subject);
        }

        $textBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], ["\n", "\n", "\n", "\n\n"], $bodyHtml)));
        $logoPath = $this->branding->logoPath();

        Mail::html($bodyHtml, function ($message) use ($recipient, $subject, $replyTo, $textBody, $logoPath, $bodyHtml) {
            $message->to($recipient['email'], $recipient['name'] !== '' ? $recipient['name'] : null)
                ->subject($subject);

            if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $message->replyTo($replyTo);
            }

            if ($textBody !== '') {
                $message->text($textBody);
            }

            if ($logoPath !== null && str_contains($bodyHtml, 'cid:'.ChurchEmailBrandingService::LOGO_CID)) {
                $message->getSymfonyMessage()->embedFromPath(
                    $logoPath,
                    ChurchEmailBrandingService::LOGO_CID
                );
            }
        });
    }

    /** @param array<string, mixed> $settings */
    private function resolveScheme(array $settings, int $port): string
    {
        $encryption = strtolower(trim((string) ($settings['smtp_encryption'] ?? '')));

        if (in_array($encryption, ['ssl', 'smtps'], true) || $port === 465) {
            return 'smtps';
        }

        return 'smtp';
    }
}
