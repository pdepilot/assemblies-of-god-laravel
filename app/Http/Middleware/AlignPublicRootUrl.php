<?php

namespace App\Http\Middleware;

use App\Support\PortalPublicUrl;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * When APP_URL is still a loopback address but the visitor reached a public
 * host (typical misconfigured Hostinger .env), generate URLs from the request.
 */
final class AlignPublicRootUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $requestHost = $request->getHost();

        if (PortalPublicUrl::isLoopbackHost(is_string($configuredHost) ? $configuredHost : null)
            && ! PortalPublicUrl::isLoopbackHost($requestHost)) {
            URL::forceRootUrl($request->getSchemeAndHttpHost());
        }

        $forwarded = strtolower((string) $request->header('X-Forwarded-Proto', ''));
        if (($forwarded === 'https' || $request->isSecure()) && ! PortalPublicUrl::isLoopbackHost($requestHost)) {
            URL::forceScheme('https');
        }

        return $next($request);
    }
}
