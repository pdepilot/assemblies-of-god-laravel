<?php

namespace App\Services\CommunicationHub;

use App\Services\Settings\PlatformSettingsReadService;
use Illuminate\Support\Facades\Schema;

final class ChurchEmailBrandingService
{
    public const LOGO_CID = 'ag-church-logo';

    public function __construct(
        private readonly PlatformSettingsReadService $platformSettings,
    ) {}

    public function logoPath(): ?string
    {
        $configured = $this->platformSettings->churchLogoAbsolutePath();
        if ($configured !== null) {
            return $configured;
        }

        $legacyRoot = rtrim((string) env('PORTAL_LEGACY_ROOT', 'C:/xampp/htdocs/AG_IKENEGBU_CHURCH_WEBSITE'), '/');

        foreach ([
            public_path('site/images/ag-logo.jpeg'),
            public_path('site/images/ag-logo.jpg'),
            public_path('site/images/ag-logo.png'),
            public_path('images/ag-logo.jpeg'),
            public_path('images/ag-logo.jpg'),
            public_path('images/ag-logo.png'),
            $legacyRoot.'/images/ag-logo.jpeg',
            $legacyRoot.'/images/ag-logo.jpg',
            $legacyRoot.'/images/ag-logo.png',
        ] as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    public function logoPublicUrl(): string
    {
        $church = $this->platformSettings->getEditableGroups()['church'] ?? [];
        $logoUrl = trim((string) ($church['logo_url'] ?? ''));
        if ($logoUrl !== '') {
            return $logoUrl;
        }

        return rtrim((string) config('portal.media_base', config('app.url')), '/').'/images/ag-logo.jpeg';
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function logoSrc(array $settings = [], bool $preferCid = true): string
    {
        if ($preferCid && $this->logoPath() !== null) {
            return 'cid:'.self::LOGO_CID;
        }

        $custom = trim((string) ($settings['logo_url'] ?? ''));
        if ($custom !== '') {
            return $custom;
        }

        return $this->logoPublicUrl();
    }

    public function isAlreadyBranded(string $html): bool
    {
        $html = trim($html);
        if ($html === '') {
            return false;
        }

        return str_contains($html, 'cid:'.self::LOGO_CID)
            || str_contains($html, $this->logoPublicUrl())
            || (str_contains(strtolower($html), '<!doctype html') && str_contains($html, 'max-width:640px'));
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function wrapHtml(string $content, array $settings, string $previewText = '', bool $preferCid = true): string
    {
        $emailIdentity = config('identity.email');
        $churchName = e((string) ($settings['from_name'] ?? ($emailIdentity['from_name'] ?? 'Assemblies of God Church Ikenegbu')));
        $website = e((string) ($settings['church_website'] ?? ($emailIdentity['church_website'] ?? config('app.url'))));
        $address = e((string) ($settings['church_address'] ?? ($emailIdentity['church_address'] ?? '11 Archdeacon, Dennis Street, Ikenegbu, Owerri, Imo State')));
        $phone = e((string) ($settings['church_phone'] ?? ($emailIdentity['church_phone'] ?? '+234 800 000 0000')));
        $email = e((string) ($settings['church_email'] ?? ($settings['from_email'] ?? ($emailIdentity['from_email'] ?? 'info@agikenebgu.org'))));
        $logoUrl = e($this->logoSrc($settings, $preferCid));
        $year = date('Y');

        $social = '';
        foreach ([
            'social_facebook' => 'Facebook',
            'social_instagram' => 'Instagram',
            'social_youtube' => 'YouTube',
        ] as $key => $label) {
            if (! empty($settings[$key])) {
                $url = e((string) $settings[$key]);
                $social .= '<a href="'.$url.'" style="color:#c9a227;text-decoration:none;margin:0 8px;">'.$label.'</a>';
            }
        }

        $preheader = $previewText !== ''
            ? '<div style="display:none;max-height:0;overflow:hidden;opacity:0;">'.e($previewText).'</div>'
            : '';

        return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            .'<title>'.$churchName.'</title></head>'
            .'<body style="margin:0;padding:0;background:#f4f6fb;font-family:Arial,Helvetica,sans-serif;color:#1a2b5c;">'
            .$preheader
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f6fb;padding:24px 12px;">'
            .'<tr><td align="center">'
            .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 8px 24px rgba(26,43,92,0.08);">'
            .'<tr><td style="background:#1a2b5c;padding:24px 28px;text-align:center;">'
            .'<img src="'.$logoUrl.'" alt="'.$churchName.'" width="72" height="72" style="border-radius:50%;display:block;margin:0 auto 12px;border:0;">'
            .'<div style="color:#ffffff;font-size:20px;font-weight:700;">'.$churchName.'</div>'
            .'<div style="color:#c9a227;font-size:13px;margin-top:4px;"><a href="'.$website.'" style="color:#c9a227;text-decoration:none;">'.$website.'</a></div>'
            .'</td></tr>'
            .'<tr><td style="padding:28px;line-height:1.6;font-size:15px;">'.$content.'</td></tr>'
            .'<tr><td style="background:#f8f9fc;padding:20px 28px;border-top:1px solid #e8ebf3;text-align:center;font-size:12px;color:#667085;line-height:1.7;">'
            .'<div>'.$address.'</div>'
            .'<div>Phone: '.$phone.' · Email: <a href="mailto:'.$email.'" style="color:#1a2b5c;">'.$email.'</a></div>'
            .($social !== '' ? '<div style="margin-top:10px;">'.$social.'</div>' : '')
            .'<div style="margin-top:12px;">&copy; '.$year.' '.$churchName.'. All rights reserved.</div>'
            .'</td></tr></table></td></tr></table></body></html>';
    }

    /** @param  array<string, string>  $vars */
    public function mergeTags(string $content, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $content = str_replace('{{'.$key.'}}', (string) $value, $content);
        }

        return preg_replace('/\{\{[a-z0-9_]+\}\}/i', '', $content) ?? $content;
    }

    /** @return array<string, string> */
    public function defaultMergeTags(array $settings): array
    {
        return [
            'church_name' => (string) ($settings['from_name'] ?? config('identity.email.from_name', 'Assemblies of God Church Ikenegbu')),
            'pastor_name' => (string) ($settings['pastor_name'] ?? config('identity.email.pastor_name', 'Pastor Emmanuel')),
            'church_email' => (string) ($settings['church_email'] ?? config('identity.email.from_email', 'info@agikenebgu.org')),
            'church_phone' => (string) ($settings['church_phone'] ?? ''),
            'church_website' => (string) ($settings['church_website'] ?? config('app.url')),
        ];
    }

    /** @param  array<string, mixed>  $emailSettings
     * @return array<string, mixed>
     */
    public function resolveBrandingSettings(array $emailSettings): array
    {
        $defaults = [
            'from_name' => config('identity.email.from_name', 'Assemblies of God Church Ikenegbu'),
            'from_email' => config('identity.email.from_email', 'info@agikenebgu.org'),
            'church_address' => config('identity.email.church_address', '11 Archdeacon, Dennis Street, Ikenegbu, Owerri, Imo State'),
            'church_phone' => config('identity.email.church_phone', '+234 800 000 0000'),
            'church_email' => config('identity.email.from_email', 'info@agikenebgu.org'),
            'church_website' => rtrim((string) config('identity.email.church_website', config('portal.media_base', config('app.url'))), '/'),
            'pastor_name' => config('identity.email.pastor_name', 'Pastor Emmanuel'),
            'social_facebook' => '',
            'social_instagram' => '',
            'social_youtube' => '',
        ];

        $merged = array_replace_recursive($defaults, array_filter(
            $emailSettings,
            static fn ($value): bool => $value !== null && $value !== ''
        ));

        if (Schema::hasTable('platform_setting_groups')) {
            $church = $this->platformSettings->getGroup('church');
            $merged['from_name'] = $merged['from_name'] ?: (string) ($church['name'] ?? $defaults['from_name']);
            $merged['church_address'] = $merged['church_address'] ?: trim(implode(', ', array_filter([
                $church['address'] ?? null,
                $church['city'] ?? null,
                $church['state'] ?? null,
            ])));
            $merged['church_phone'] = $merged['church_phone'] ?: (string) ($church['phone'] ?? '');
            $merged['church_email'] = $merged['church_email'] ?: (string) ($church['email'] ?? '');
            $merged['church_website'] = $merged['church_website'] ?: (string) ($church['website'] ?? '');
            $merged['pastor_name'] = $merged['pastor_name'] ?: (string) ($church['pastor'] ?? '');
            $merged['social_facebook'] = $merged['social_facebook'] ?: (string) ($church['social_facebook'] ?? '');
            $merged['social_instagram'] = $merged['social_instagram'] ?: (string) ($church['social_instagram'] ?? '');
            $merged['social_youtube'] = $merged['social_youtube'] ?: (string) ($church['social_youtube'] ?? '');
        }

        return $merged;
    }
}
