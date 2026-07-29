<?php

namespace App\Http\Controllers\FinancialErp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

/**
 * Keeps the browser on Laravel (/erp/...) while forwarding to the legacy XAMPP ERP app.
 */
final class ErpLegacyProxyController extends Controller
{
    private const SESSION_COOKIES = 'erp_legacy_cookies';

    public function __invoke(Request $request, ?string $path = null): SymfonyResponse
    {
        $path = trim(str_replace('\\', '/', (string) $path), '/');
        $legacyBase = rtrim((string) config('portal.legacy_public_base'), '/');
        $legacyErp = $legacyBase.'/erp';
        $url = $path === '' ? $legacyErp.'/' : $legacyErp.'/'.$path;
        if ($request->getQueryString()) {
            $url .= '?'.$request->getQueryString();
        }

        $domain = parse_url($legacyBase, PHP_URL_HOST) ?: 'localhost';
        $cookies = $request->session()->get(self::SESSION_COOKIES, []);
        if (! is_array($cookies)) {
            $cookies = [];
        }

        // Release the session lock before the slow upstream call so parallel
        // browser requests (settings tab, API) are not blocked for up to 30s.
        $request->session()->save();

        $method = strtoupper($request->method());

        try {
            $pending = Http::timeout(30)
                ->withOptions(['allow_redirects' => false])
                ->withHeaders([
                    'Accept' => $request->header('Accept', '*/*'),
                    'X-Requested-With' => $request->header('X-Requested-With', ''),
                    'Referer' => url('/erp/login'),
                    'Origin' => rtrim(url('/'), '/'),
                ]);

            if ($cookies !== []) {
                $pending = $pending->withCookies($cookies, $domain);
            }

            $response = $this->send($pending, $request, $method, $url);
        } catch (Throwable $e) {
            report($e);

            return response(
                '<!DOCTYPE html><html><body style="font-family:system-ui;padding:2rem">'
                .'<h1>Financial ERP unavailable</h1>'
                .'<p>Could not reach the legacy ERP at <code>'.e($legacyErp).'</code>. '
                .'Start XAMPP Apache and retry.</p></body></html>',
                502
            );
        }

        $merged = $cookies;
        foreach ($response->cookies() as $cookie) {
            $merged[$cookie->getName()] = $cookie->getValue();
        }
        if ($merged !== $cookies) {
            $request->session()->start();
            $request->session()->put(self::SESSION_COOKIES, $merged);
            $request->session()->save();
        }

        if ($response->redirect()) {
            $location = (string) ($response->header('Location') ?? '');
            $mapped = $this->mapLocation($location, $legacyBase);

            return redirect()->to($mapped !== '' ? $mapped : url('/erp/login'));
        }

        $body = $response->body();
        $contentType = (string) ($response->header('Content-Type') ?? '');

        if ($this->isHtml($contentType, $body)) {
            $body = $this->rewriteHtml($body, $legacyBase);
        } elseif ($this->isJson($contentType, $body)) {
            $body = $this->rewriteJson($body, $legacyBase);
        }

        $out = response($body, $response->status());
        if ($contentType !== '') {
            $out->header('Content-Type', $contentType);
        }

        // Prevent stale ERP JS/CSS from sticking in the browser while iterating.
        if (preg_match('#\.(js|css)(\?|$)#i', $path) === 1 || str_contains($path, 'assets/js/') || str_contains($path, 'assets/css/')) {
            $out->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $out->header('Pragma', 'no-cache');
        }

        return $out;
    }

    private function send(PendingRequest $pending, Request $request, string $method, string $url): \Illuminate\Http\Client\Response
    {
        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $fields = $request->request->all();

            return $pending->asForm()->send($method, $url, ['form_params' => $fields]);
        }

        if ($method === 'DELETE') {
            return $pending->delete($url);
        }

        return $pending->get($url);
    }

    private function isHtml(string $contentType, string $body): bool
    {
        if (str_contains(strtolower($contentType), 'text/html')) {
            return true;
        }

        $trim = ltrim($body);

        return str_starts_with($trim, '<!DOCTYPE') || str_starts_with($trim, '<html');
    }

    private function isJson(string $contentType, string $body): bool
    {
        if (str_contains(strtolower($contentType), 'application/json')) {
            return true;
        }

        $trim = ltrim($body);

        return $trim !== '' && ($trim[0] === '{' || $trim[0] === '[');
    }

    private function rewriteHtml(string $html, string $legacyBase): string
    {
        $appErp = rtrim(url('/erp'), '/');
        $adminDash = url('/admin/dashboard');
        $adminLogin = url('/admin/login');
        $legacyErp = $legacyBase.'/erp';
        $siteAsset = url('/site');

        $replacements = [
            $legacyErp.'/' => $appErp.'/',
            $legacyErp => $appErp,
            $legacyBase.'/images/' => $siteAsset.'/images/',
            $legacyBase.'/portal/login' => $adminLogin,
            $legacyBase.'/portal/' => $adminDash,
            $legacyBase.'/portal' => $adminDash,
            '/AG_IKENEGBU_CHURCH_WEBSITE/erp/' => $appErp.'/',
            '/AG_IKENEGBU_CHURCH_WEBSITE/images/' => $siteAsset.'/images/',
            '/AG_IKENEGBU_CHURCH_WEBSITE/portal/login' => $adminLogin,
            '/AG_IKENEGBU_CHURCH_WEBSITE/portal/' => $adminDash,
            '/AG_IKENEGBU_CHURCH_WEBSITE/portal' => $adminDash,
            'http://localhost/AG_IKENEGBU_CHURCH_WEBSITE/erp/' => $appErp.'/',
            'http://127.0.0.1/AG_IKENEGBU_CHURCH_WEBSITE/erp/' => $appErp.'/',
            'http://localhost/AG_IKENEGBU_CHURCH_WEBSITE/portal/login' => $adminLogin,
            'http://localhost/AG_IKENEGBU_CHURCH_WEBSITE/portal/' => $adminDash,
            'http://127.0.0.1/AG_IKENEGBU_CHURCH_WEBSITE/portal/login' => $adminLogin,
            'http://127.0.0.1/AG_IKENEGBU_CHURCH_WEBSITE/portal/' => $adminDash,
            '../portal/' => $adminDash,
            '../portal' => $adminDash,
        ];

        $html = str_replace(array_keys($replacements), array_values($replacements), $html);

        // Ensure CMS exit links are marked external so ERP soft-nav never intercepts them.
        $html = preg_replace_callback(
            '#<a\b([^>]*\bhref="'.preg_quote($adminDash, '#').'"[^>]*)>#i',
            static function (array $m): string {
                $attrs = $m[1];
                if (! str_contains($attrs, 'data-erp-external')) {
                    $attrs .= ' data-erp-external="1" data-erp-leave="1"';
                }

                return '<a'.$attrs.'>';
            },
            $html
        ) ?? $html;

        // Near-real-time CMS activity toasts while admins work inside legacy ERP.
        if (! str_contains($html, 'data-cms-notify-bridge="1"')) {
            $cmsNotifyUrlJs = json_encode(
                url('/admin/handlers/dashboard-handler?action=notifications'),
                JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            );
            $bridge = <<<HTML
<script data-cms-notify-bridge="1">
(function () {
  var url = {$cmsNotifyUrlJs};
  var latest = 0;
  var ready = false;
  function toast(msg) {
    if (typeof window.toast === 'function') window.toast(msg, 'info');
  }
  function poll() {
    var q = url + (latest ? ((url.indexOf('?') >= 0 ? '&' : '?') + 'since_id=' + encodeURIComponent(String(latest))) : '');
    fetch(q, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (d) {
        if (!d || !d.success) return;
        var items = Array.isArray(d.notifications) ? d.notifications : [];
        if (ready && latest && items.length) {
          items.slice().reverse().forEach(function (n) {
            var title = n.title || 'CMS update';
            var text = n.text || '';
            toast(text ? (title + ': ' + text) : title);
          });
        }
        if (typeof d.latest_id === 'number' && d.latest_id > latest) latest = d.latest_id;
        ready = true;
      })
      .catch(function () {});
  }
  setTimeout(poll, 1200);
  setInterval(poll, 8000);
})();
</script>
HTML;
            if (stripos($html, '</body>') !== false) {
                $html = preg_replace('/<\/body>/i', $bridge.'</body>', $html, 1) ?? ($html.$bridge);
            } else {
                $html .= $bridge;
            }
        }

        return $html;
    }

    private function rewriteJson(string $json, string $legacyBase): string
    {
        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return $json;
        }

        if (isset($decoded['redirect']) && is_string($decoded['redirect'])) {
            $decoded['redirect'] = $this->mapLocation($decoded['redirect'], $legacyBase) ?: $decoded['redirect'];
        }

        return (string) json_encode($decoded, JSON_UNESCAPED_SLASHES);
    }

    private function mapLocation(string $location, string $legacyBase): string
    {
        $location = trim($location);
        if ($location === '') {
            return '';
        }

        // Relative ERP path (e.g. dashboard, login?reason=…)
        if (! preg_match('#^https?://#i', $location) && ! str_starts_with($location, '/')) {
            return url('/erp/'.ltrim($location, '/'));
        }

        $path = (string) (parse_url($location, PHP_URL_PATH) ?? '');
        $query = parse_url($location, PHP_URL_QUERY);
        $querySuffix = is_string($query) && $query !== '' ? '?'.$query : '';

        if (preg_match('#/erp(?:/(.*))?$#', $path, $m) === 1) {
            $rest = trim((string) ($m[1] ?? ''), '/');

            return url('/erp'.($rest !== '' ? '/'.$rest : '')).$querySuffix;
        }

        // Absolute path under legacy site erp folder
        $legacyPath = rtrim((string) (parse_url($legacyBase, PHP_URL_PATH) ?? ''), '/');
        $prefix = $legacyPath.'/erp';
        if ($legacyPath !== '' && str_starts_with($path, $prefix)) {
            $rest = trim(substr($path, strlen($prefix)), '/');

            return url('/erp'.($rest !== '' ? '/'.$rest : '')).$querySuffix;
        }

        if (str_starts_with($path, '/erp')) {
            return url($path).$querySuffix;
        }

        return $location;
    }
}
