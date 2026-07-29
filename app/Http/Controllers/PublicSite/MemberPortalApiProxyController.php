<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Services\Auth\LoginAuditWriteService;
use App\Services\Security\DeviceBanService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Proxies member-portal API calls to the legacy host while keeping the browser on Laravel origin.
 */
final class MemberPortalApiProxyController extends Controller
{
    private const ACTIONS = [
        'login',
        'logout',
        'dashboard',
        'check-setup',
        'set-password',
        'change-password',
        'generate-statement',
        'download-statement',
        'download-receipt',
    ];

    public function __construct(
        private readonly LoginAuditWriteService $audit,
        private readonly DeviceBanService $bans,
    ) {}

    public function memberPortal(Request $request, string $action): Response|JsonResponse
    {
        $action = strtolower($action);
        if (! in_array($action, self::ACTIONS, true)) {
            abort(404);
        }

        if ($action === 'login' && strtoupper($request->method()) === 'POST') {
            return $this->loginWithBanGuard($request);
        }

        return $this->forward($request, 'member-portal-'.$action.'.php');
    }

    public function memberSelfRegister(Request $request): Response|JsonResponse
    {
        return $this->forward($request, 'member-self-register.php');
    }

    private function loginWithBanGuard(Request $request): Response|JsonResponse
    {
        $source = DeviceBanService::SOURCE_MEMBER_PORTAL_LOGIN;
        $fingerprint = $this->audit->deviceFingerprint($request);
        $activeBan = $this->bans->getActiveBan($fingerprint, $source);

        if ($activeBan !== null) {
            $expires = Carbon::parse((string) $activeBan['ban_expires'])->format('F j, Y \a\t g:i A');
            $message = ((int) $activeBan['ban_level'] >= 2)
                ? 'This device has been blocked for 90 days due to repeated unauthorized access attempts.'
                : 'This device has been temporarily blocked for security reasons. Access will be restored on '.$expires.'.';

            return response()->json([
                'success' => false,
                'ok' => false,
                'message' => $message,
                'banned' => true,
            ], 403);
        }

        $response = $this->forward($request, 'member-portal-login.php');
        $payload = $this->decodeJsonBody($response);
        if ($payload === null) {
            return $response;
        }

        $identifier = trim((string) $request->input('identifier', ''));
        $succeeded = ! empty($payload['success']);

        if ($succeeded) {
            $this->audit->recordAttempt(
                $request,
                null,
                true,
                null,
                $source,
                $identifier,
            );

            return $response;
        }

        $banResult = $this->audit->recordAttempt(
            $request,
            null,
            false,
            'invalid_credentials',
            $source,
            $identifier,
        );

        if (is_array($banResult) && ! empty($banResult['banned'])) {
            $payload['success'] = false;
            $payload['ok'] = false;
            $payload['banned'] = true;
            $payload['message'] = (string) ($banResult['message'] ?? 'This device has been temporarily restricted for security reasons.');

            return response()->json($payload, 403);
        }

        $failures = $this->bans->consecutiveFailures($fingerprint, $source);
        $warningAt = max(1, (int) config('portal.warning_at_attempt', 3));
        if ($failures === $warningAt) {
            $payload['message'] = 'Warning: One login attempt remaining before temporary device restriction.';
        }

        return response()->json($payload, $response->getStatusCode());
    }

    /** @return array<string, mixed>|null */
    private function decodeJsonBody(Response|JsonResponse $response): ?array
    {
        $decoded = json_decode($response->getContent(), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function forward(Request $request, string $legacyFile): Response|JsonResponse
    {
        $legacyBase = rtrim((string) config('portal.legacy_public_base'), '/');
        $url = $legacyBase.'/api/'.$legacyFile;
        if ($request->getQueryString()) {
            $url .= '?'.$request->getQueryString();
        }

        $domain = parse_url($legacyBase, PHP_URL_HOST) ?: 'localhost';
        $cookies = $request->session()->get('member_portal_cookies', []);
        if (! is_array($cookies)) {
            $cookies = [];
        }

        $method = strtoupper($request->method());
        $csrfToken = $this->extractCsrfToken($request);

        // Legacy public CSRF is session-bound. Re-sync cookie jar + token before state-changing posts.
        if ($method === 'POST') {
            [$cookies, $csrfToken] = $this->syncLegacyPublicSession($legacyBase, $domain, $cookies);
            $request->session()->put('member_portal_cookies', $cookies);
        }

        try {
            $pending = Http::timeout(30)
                ->withCookies($cookies, $domain)
                ->withHeaders(array_filter([
                    'Accept' => $request->header('Accept', 'application/json'),
                    'X-Requested-With' => $request->header('X-Requested-With', 'XMLHttpRequest'),
                    'X-CSRF-TOKEN' => $csrfToken !== '' ? $csrfToken : null,
                    'Referer' => $legacyBase.'/member-portal/login.php',
                    'Origin' => $legacyBase,
                ]));

            if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
                // Browser sends FormData; Laravel parses it — raw getContent() is empty.
                $fields = $request->request->all();
                if ($csrfToken !== '') {
                    $fields['csrf_token'] = $csrfToken;
                }

                $response = $request->allFiles() !== []
                    ? $this->sendWithFiles($pending, $method, $url, $fields, $request)
                    : $pending->asForm()->send($method, $url, ['form_params' => $fields]);
            } else {
                $response = $pending->get($url);
            }
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => 'Member portal API is unreachable. Ensure the legacy site (XAMPP) is running.',
            ], 502);
        }

        $merged = $cookies;
        foreach ($response->cookies() as $cookie) {
            $merged[$cookie->getName()] = $cookie->getValue();
        }
        $request->session()->put('member_portal_cookies', $merged);

        $out = response($response->body(), $response->status());
        $contentType = $response->header('Content-Type');
        if (is_string($contentType) && $contentType !== '') {
            $out->header('Content-Type', $contentType);
        }
        $disposition = $response->header('Content-Disposition');
        if (is_string($disposition) && $disposition !== '') {
            $out->header('Content-Disposition', $disposition);
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function sendWithFiles(
        PendingRequest $pending,
        string $method,
        string $url,
        array $fields,
        Request $request,
    ): \Illuminate\Http\Client\Response {
        foreach ($request->allFiles() as $key => $file) {
            $files = is_array($file) ? $file : [$file];
            foreach ($files as $index => $uploaded) {
                if (! $uploaded instanceof UploadedFile || ! $uploaded->isValid()) {
                    continue;
                }

                $name = is_array($file) ? $key.'['.$index.']' : $key;
                $pending = $pending->attach(
                    $name,
                    fopen($uploaded->getRealPath(), 'r'),
                    $uploaded->getClientOriginalName()
                );
            }
        }

        return $pending->send($method, $url, ['multipart' => $this->toMultipart($fields)]);
    }

    /**
     * @param  array<string, string>  $cookies
     * @return array{0: array<string, string>, 1: string}
     */
    private function syncLegacyPublicSession(string $legacyBase, string $domain, array $cookies): array
    {
        try {
            $pending = Http::timeout(12)
                ->withHeaders(['Accept' => 'text/html'])
                ->withOptions(['allow_redirects' => false]);
            if ($cookies !== []) {
                $pending = $pending->withCookies($cookies, $domain);
            }

            $response = $pending->get($legacyBase.'/member-portal/login.php');

            foreach ($response->cookies() as $cookie) {
                $cookies[$cookie->getName()] = $cookie->getValue();
            }

            $token = $this->extractCsrfFromHtml($response->body());

            // Already signed in → login.php redirects; load dashboard HTML for a CSRF token.
            if ($token === '' && $response->redirect()) {
                $dash = Http::timeout(12)
                    ->withHeaders(['Accept' => 'text/html'])
                    ->withOptions(['allow_redirects' => false])
                    ->withCookies($cookies, $domain)
                    ->get($legacyBase.'/member-portal/index.php');

                foreach ($dash->cookies() as $cookie) {
                    $cookies[$cookie->getName()] = $cookie->getValue();
                }
                $token = $this->extractCsrfFromHtml($dash->body());
            }

            return [$cookies, $token];
        } catch (Throwable $e) {
            report($e);

            return [$cookies, ''];
        }
    }

    private function extractCsrfFromHtml(string $html): string
    {
        if (preg_match('/<meta\s+name=["\']csrf-token["\']\s+content=["\']([^"\']+)["\']/i', $html, $m) === 1) {
            return $m[1];
        }
        if (preg_match('/<meta\s+content=["\']([^"\']+)["\']\s+name=["\']csrf-token["\']/i', $html, $m) === 1) {
            return $m[1];
        }

        return '';
    }

    private function extractCsrfToken(Request $request): string
    {
        $header = trim((string) $request->header('X-CSRF-TOKEN', ''));
        if ($header !== '') {
            return $header;
        }

        return trim((string) $request->input('csrf_token', ''));
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return list<array{name: string, contents: string}>
     */
    private function toMultipart(array $fields): array
    {
        $parts = [];
        foreach ($fields as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $i => $item) {
                    $parts[] = [
                        'name' => $name.'['.$i.']',
                        'contents' => is_scalar($item) || $item === null ? (string) $item : (string) json_encode($item),
                    ];
                }
                continue;
            }

            $parts[] = [
                'name' => (string) $name,
                'contents' => $value === null ? '' : (string) $value,
            ];
        }

        return $parts;
    }
}
