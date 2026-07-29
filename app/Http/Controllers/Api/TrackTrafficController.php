<?php

namespace App\Http\Controllers\Api;

use App\Services\Analytics\SiteTrafficIngestService;
use App\Services\Analytics\TrafficRateLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TrackTrafficController
{
    public function __construct(
        private readonly SiteTrafficIngestService $traffic,
        private readonly TrafficRateLimitService $rateLimit,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $ip = (string) ($request->ip() ?? '');

        if (! $this->rateLimit->allow($ip)) {
            return response()->json(['success' => false, 'message' => 'Too many requests. Please try again later.'], 429);
        }

        $payload = $request->json()->all();

        if ($payload === []) {
            $payload = $request->all();
        }

        try {
            $result = $this->traffic->ingest(
                $payload,
                $ip,
                (string) ($request->userAgent() ?? '')
            );

            return response()->json([
                'success' => true,
                'session_key' => $result['session_key'],
                'pageview_key' => $result['pageview_key'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable) {
            return response()->json(['success' => false, 'message' => 'Unable to record traffic.'], 500);
        }
    }
}
