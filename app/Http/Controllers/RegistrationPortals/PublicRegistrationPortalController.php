<?php

namespace App\Http\Controllers\RegistrationPortals;

use App\Services\RegistrationPortals\PublicPortalPageService;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PublicRegistrationPortalController
{
    public function __construct(
        private readonly PublicPortalPageService $pages,
    ) {}

    public function show(string $slug): Response
    {
        try {
            $page = $this->pages->prepare($slug);
        } catch (NotFoundHttpException $e) {
            return response()->view('registration-portals.public-error', [
                'title' => 'Registration not available',
                'message' => $e->getMessage(),
                'hint' => 'If you are an organizer, open the portal in admin and publish it, or use Preview Form while signed in.',
            ], 404);
        }

        return response()->view('registration-portals.public', [
            'portal' => $page['portal'],
            'slug' => $page['slug'],
            'isAdminPreview' => $page['is_admin_preview'],
            'landing' => $page['portal']['landing_config'] ?? [],
            'fields' => $page['portal']['fields'] ?? [],
            'canRegister' => ($page['portal']['status'] ?? '') === 'open' || $page['is_admin_preview'],
        ]);
    }
}
