<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class TrafficBeaconCors
{
    public function handle(Request $request, Closure $next): Response
    {
        $origin = (string) $request->headers->get('Origin', '');
        $allowed = config('traffic.beacon_cors_origins', ['*']);
        $allowOrigin = in_array('*', $allowed, true) ? '*' : $this->matchOrigin($origin, $allowed);

        if ($request->isMethod('OPTIONS')) {
            return response('', 204)
                ->header('Access-Control-Allow-Origin', $allowOrigin)
                ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept')
                ->header('Access-Control-Max-Age', '86400');
        }

        $response = $next($request);

        if ($allowOrigin !== '') {
            $response->headers->set('Access-Control-Allow-Origin', $allowOrigin);
            $response->headers->set('Vary', 'Origin');
        }

        return $response;
    }

    /** @param list<string> $allowed */
    private function matchOrigin(string $origin, array $allowed): string
    {
        if ($origin === '') {
            return '';
        }

        foreach ($allowed as $candidate) {
            if (strcasecmp($origin, $candidate) === 0) {
                return $origin;
            }
        }

        return '';
    }
}
