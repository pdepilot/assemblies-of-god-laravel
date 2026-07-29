<?php

namespace App\Http\Controllers\RegistrationPortals;

use App\Services\RegistrationPortals\PortalQrCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;
use RuntimeException;

final class PortalQrController
{
    public function __construct(
        private readonly PortalQrCodeService $qr,
    ) {}

    public function __invoke(Request $request): Response
    {
        try {
            $payload = $this->qr->generate(
                (string) $request->query('slug', ''),
                (int) $request->query('size', 8),
                $request->boolean('download'),
            );
        } catch (InvalidArgumentException $e) {
            $status = $e->getMessage() === 'Event not found.' ? 404 : 400;

            return response($e->getMessage(), $status)
                ->header('Content-Type', 'text/plain; charset=utf-8');
        } catch (RuntimeException $e) {
            return response($e->getMessage(), 500)
                ->header('Content-Type', 'text/plain; charset=utf-8');
        }

        return response($payload['content'], 200, [
            'Content-Type' => $payload['content_type'],
            'Content-Disposition' => $payload['disposition'],
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
