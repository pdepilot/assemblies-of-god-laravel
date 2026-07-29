<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Services\Sdtg\SdtgPublicGalleryReadService;
use Illuminate\Http\JsonResponse;

final class PublicSdtgGalleryController extends Controller
{
    public function __construct(
        private readonly SdtgPublicGalleryReadService $gallery,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->gallery->bootstrap(),
        ]);
    }
}
