<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Services\Newsletter\NewsletterSubscribeWriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NewsletterSubscribeController extends Controller
{
    public function __construct(
        private readonly NewsletterSubscribeWriteService $subscribers,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $result = $this->subscribers->subscribe($request);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ]);
    }
}
