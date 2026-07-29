<?php

namespace App\Services\RegistrationPortals;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PublicPortalPageService
{
    public function __construct(
        private readonly RegistrationPortalReadService $read,
    ) {}

    /**
     * @return array{portal: array<string, mixed>, slug: string, is_admin_preview: bool}
     */
    public function prepare(string $slug): array
    {
        $slug = trim($slug);
        if ($slug === '') {
            throw new NotFoundHttpException('This page needs a valid event registration link.');
        }

        $portal = $this->read->getPortalBySlug($slug, false);
        if ($portal === null) {
            throw new NotFoundHttpException('No registration portal matches this link.');
        }

        if (($portal['fields'] ?? []) === []) {
            app(RegistrationPortalWriteService::class)->ensureDefaultFields((int) $portal['id']);
            $portal = $this->read->getPortalBySlug($slug, false) ?? $portal;
        }

        $status = (string) ($portal['status'] ?? '');
        if ($status === 'open') {
            return [
                'portal' => $portal,
                'slug' => $slug,
                'is_admin_preview' => false,
            ];
        }

        if ($status === 'draft' && auth('admin')->check()) {
            return [
                'portal' => $portal,
                'slug' => $slug,
                'is_admin_preview' => true,
            ];
        }

        if ($status === 'draft') {
            throw new NotFoundHttpException('This event registration portal has not been published yet.');
        }

        // closed / archived — still show page, form disabled in the view
        return [
            'portal' => $portal,
            'slug' => $slug,
            'is_admin_preview' => false,
        ];
    }
}
