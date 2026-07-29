<?php

namespace App\Services\RegistrationPortals;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use InvalidArgumentException;
use RuntimeException;

final class PortalQrCodeService
{
    public function __construct(
        private readonly RegistrationPortalReadService $read,
    ) {}

    /**
     * @return array{content: string, content_type: string, disposition: string}
     */
    public function generate(string $slug, int $size = 8, bool $download = false): array
    {
        $slug = trim($slug);
        if ($slug === '') {
            throw new InvalidArgumentException('Missing event slug.');
        }

        $portal = $this->read->getPortalBySlug($slug, false);
        if ($portal === null) {
            throw new InvalidArgumentException('Event not found.');
        }

        $registrationUrl = url('/register/'.$portal['slug']);
        $size = max(2, min(24, $size));
        $pixel = max(120, min(720, $size * 40));
        $safeSlug = preg_replace('/[^a-z0-9\-]/i', '', (string) $portal['slug']) ?: 'event';

        try {
            $renderer = new ImageRenderer(
                new RendererStyle($pixel),
                new SvgImageBackEnd()
            );
            $writer = new Writer($renderer);
            $svg = $writer->writeString($registrationUrl);
        } catch (\Throwable $e) {
            throw new RuntimeException('Could not generate QR code.', 0, $e);
        }

        if ($svg === '') {
            throw new RuntimeException('Could not generate QR code.');
        }

        $disposition = $download
            ? 'attachment; filename="qr-'.$safeSlug.'.svg"'
            : 'inline; filename="qr-'.$safeSlug.'.svg"';

        return [
            'content' => $svg,
            'content_type' => 'image/svg+xml; charset=utf-8',
            'disposition' => $disposition,
        ];
    }
}
