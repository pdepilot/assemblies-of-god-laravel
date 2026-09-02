<?php

namespace App\Services\Members;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

final class DeathCertificatePdfService
{
    /**
     * @param  array<string, mixed>  $member
     * @return array{binary: string, filename: string, certificate_number: string}
     */
    public function generate(array $member): array
    {
        if (($member['status'] ?? '') !== 'deceased') {
            throw new InvalidArgumentException('Death certificates can only be issued for deceased members.');
        }

        $certificateNumber = $this->certificateNumber($member);
        $html = $this->renderHtml($member, $certificateNumber);

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $binary = $dompdf->output() ?? '';
        if ($binary === '') {
            throw new InvalidArgumentException('Unable to generate the death certificate PDF.');
        }

        $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($member['full_name'] ?? 'member')) ?: 'member';
        $filename = 'death-certificate-'.$safeName.'-'.$certificateNumber.'.pdf';

        return [
            'binary' => $binary,
            'filename' => $filename,
            'certificate_number' => $certificateNumber,
        ];
    }

    /**
     * @param  array<string, mixed>  $member
     */
    private function certificateNumber(array $member): string
    {
        $code = preg_replace('/[^A-Za-z0-9]+/', '', (string) ($member['member_code'] ?? 'MEMBER')) ?: 'MEMBER';
        $year = now()->format('Y');

        return 'DC-'.$code.'-'.$year;
    }

    /**
     * @param  array<string, mixed>  $member
     */
    private function renderHtml(array $member, string $certificateNumber): string
    {
        $churchName = e((string) config('identity.public.site_name', config('identity.admin.brand_name', 'Assemblies of God Church')));
        $tagline = e((string) config('identity.public.tagline', ''));
        $address = e((string) config('identity.email.church_address', ''));
        $phone = e((string) config('identity.email.church_phone', ''));
        $pastor = e((string) config('identity.email.pastor_name', 'Pastor'));
        $website = e((string) config('identity.email.church_website', ''));

        $fullName = e((string) ($member['full_name'] ?? 'Unknown'));
        $memberCode = e((string) ($member['member_code'] ?? '—'));
        $department = e((string) ($member['department'] ?? '—'));
        $gender = e(ucfirst((string) ($member['gender'] ?? 'unspecified')));
        $addressLine = e((string) ($member['address'] ?? '—'));
        $memorial = trim((string) ($member['death_notes'] ?? ''));
        $memorialHtml = $memorial !== ''
            ? '<p class="memorial"><strong>Memorial notes:</strong> '.e($memorial).'</p>'
            : '';

        $dateOfBirth = $this->formatDate($member['date_of_birth'] ?? null);
        $dateOfDeath = $this->formatDate($member['date_of_death'] ?? null);
        $joinedDate = $this->formatDate($member['joined_date'] ?? null);
        $issuedOn = e(now()->format('d F Y'));
        $certNo = e($certificateNumber);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
@page { margin: 28px 32px; }
body {
    font-family: DejaVu Sans, sans-serif;
    color: #1a1a1a;
    font-size: 12px;
    line-height: 1.45;
}
.frame {
    border: 3px solid #1f3a5f;
    padding: 22px;
    min-height: 980px;
}
.inner {
    border: 1px solid #c4a35a;
    padding: 28px 32px;
    min-height: 920px;
}
.header { text-align: center; margin-bottom: 18px; }
.church {
    font-size: 20px;
    font-weight: bold;
    color: #1f3a5f;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
.tagline { color: #555; font-size: 11px; margin-top: 4px; }
.contact { color: #666; font-size: 10px; margin-top: 6px; }
.title {
    text-align: center;
    margin: 28px 0 8px;
    font-size: 22px;
    letter-spacing: 2px;
    color: #8b1e1e;
    text-transform: uppercase;
    font-weight: bold;
}
.subtitle {
    text-align: center;
    color: #555;
    font-size: 11px;
    margin-bottom: 24px;
}
.body { text-align: center; margin: 18px 0 28px; font-size: 13px; }
.name {
    font-size: 22px;
    font-weight: bold;
    color: #1f3a5f;
    margin: 14px 0;
    text-decoration: underline;
    text-underline-offset: 4px;
}
.meta {
    width: 100%;
    border-collapse: collapse;
    margin: 18px 0 22px;
}
.meta th, .meta td {
    border: 1px solid #d6d6d6;
    padding: 8px 10px;
    text-align: left;
    font-size: 11px;
}
.meta th {
    width: 34%;
    background: #f5f7fa;
    color: #333;
    font-weight: bold;
}
.memorial {
    margin: 16px 0;
    padding: 10px 12px;
    background: #faf8f2;
    border-left: 3px solid #c4a35a;
    text-align: left;
    font-size: 11px;
}
.attest {
    margin-top: 18px;
    text-align: justify;
    font-size: 12px;
}
.signatures {
    width: 100%;
    margin-top: 48px;
    border-collapse: collapse;
}
.signatures td {
    width: 50%;
    text-align: center;
    vertical-align: top;
    padding: 0 16px;
}
.line {
    border-top: 1px solid #333;
    margin: 40px 20px 8px;
}
.sig-label { font-size: 11px; color: #444; }
.footer {
    margin-top: 36px;
    text-align: center;
    font-size: 10px;
    color: #666;
}
.cert-no { font-family: DejaVu Sans Mono, monospace; }
</style>
</head>
<body>
<div class="frame">
<div class="inner">
    <div class="header">
        <div class="church">{$churchName}</div>
        <div class="tagline">{$tagline}</div>
        <div class="contact">{$address}<br>{$phone}</div>
    </div>

    <div class="title">Certificate of Death</div>
    <div class="subtitle">Church membership memorial certificate</div>

    <div class="body">
        This is to certify that
        <div class="name">{$fullName}</div>
        was a registered member of this church and has been recorded as deceased
        in the church membership register.
    </div>

    <table class="meta">
        <tr><th>Member ID</th><td>{$memberCode}</td></tr>
        <tr><th>Gender</th><td>{$gender}</td></tr>
        <tr><th>Department / Ministry</th><td>{$department}</td></tr>
        <tr><th>Date of birth</th><td>{$dateOfBirth}</td></tr>
        <tr><th>Date joined</th><td>{$joinedDate}</td></tr>
        <tr><th>Date of death</th><td>{$dateOfDeath}</td></tr>
        <tr><th>Last known address</th><td>{$addressLine}</td></tr>
        <tr><th>Certificate number</th><td class="cert-no">{$certNo}</td></tr>
        <tr><th>Date issued</th><td>{$issuedOn}</td></tr>
    </table>

    {$memorialHtml}

    <p class="attest">
        This certificate is issued by the church for memorial and membership-record purposes.
        It confirms the entry in our deceased members register and does not replace a civil
        government death certificate.
    </p>

    <table class="signatures">
        <tr>
            <td>
                <div class="line"></div>
                <div class="sig-label"><strong>{$pastor}</strong><br>Resident Pastor</div>
            </td>
            <td>
                <div class="line"></div>
                <div class="sig-label"><strong>Church Administrator</strong><br>Official Seal / Signature</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        {$website}<br>
        Generated from the church management system · {$certNo}
    </div>
</div>
</div>
</body>
</html>
HTML;
    }

    private function formatDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        try {
            return e(Carbon::parse((string) $value)->format('d F Y'));
        } catch (\Throwable) {
            return e((string) $value);
        }
    }
}
