<?php

namespace App\Services\PublicSite;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Fetches legacy public HTML and rewrites asset/nav URLs so pages keep visual fidelity on Laravel.
 */
final class LegacyHtmlBridge
{
    public function __construct(
        private readonly PublicAssetResolver $assets,
        private readonly \App\Services\Sdtg\SdtgPublicGalleryReadService $sdtgGallery,
        private readonly \App\Services\Website\SeoReadService $seo,
    ) {}

    public function render(string $legacyPath, string $area = 'ag'): string
    {
        $result = $this->fetch($legacyPath, $area);
        if (($result['status'] ?? '') === 'redirect') {
            // Non-member callers expect HTML; surface a tiny bounce page.
            $to = e((string) ($result['to'] ?? url('/')));

            return '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url='.$to.'"><script>location.replace('.json_encode($result['to'] ?? url('/'), JSON_UNESCAPED_SLASHES).');</script></head><body></body></html>';
        }

        return (string) ($result['html'] ?? '');
    }

    /**
     * Member portal pages may 302 between login/dashboard. Do not follow those
     * redirects (they can bounce into Laravel and loop). Map them instead.
     *
     * @return array{status: 'ok', html: string}|array{status: 'redirect', to: string}
     */
    public function renderMemberPortal(string $legacyPath): array
    {
        return $this->fetch($legacyPath, 'member');
    }

    /**
     * @return array{status: 'ok', html: string}|array{status: 'redirect', to: string}
     */
    private function fetch(string $legacyPath, string $area): array
    {
        $legacyBase = rtrim((string) config('portal.legacy_public_base'), '/');
        // Apache MultiViews 301s *.php → extensionless; start extensionless to avoid a hop.
        $normalizedPath = preg_replace('/\.php(\?|$)/i', '$1', ltrim($legacyPath, '/')) ?? ltrim($legacyPath, '/');
        $url = $legacyBase.'/'.$normalizedPath;
        $domain = parse_url($legacyBase, PHP_URL_HOST) ?: 'localhost';
        $requestKind = $this->memberPortalPageKind($normalizedPath);

        try {
            $cookies = [];
            if ($area === 'member') {
                $cookies = session('member_portal_cookies', []);
                if (! is_array($cookies)) {
                    $cookies = [];
                }
            }

            $response = null;
            for ($hop = 0; $hop < 6; $hop++) {
                $pending = Http::timeout(12)
                    ->withHeaders(['Accept' => 'text/html'])
                    ->withOptions(['allow_redirects' => false]);

                if ($area === 'member' && $cookies !== []) {
                    $pending = $pending->withCookies($cookies, $domain);
                }

                $response = $pending->get($url);

                if ($area === 'member') {
                    foreach ($response->cookies() as $cookie) {
                        $cookies[$cookie->getName()] = $cookie->getValue();
                    }
                    session(['member_portal_cookies' => $cookies]);
                }

                if (! $response->redirect()) {
                    break;
                }

                $location = $this->resolveRedirectUrl($url, (string) ($response->header('Location') ?? ''));
                if ($location === '') {
                    break;
                }

                // Same legacy host: follow MultiViews / trailing-slash rewrites.
                // Only surface a browser redirect for member-portal auth bounces (login ↔ dashboard).
                if ($this->isUnderLegacyBase($location, $legacyBase)) {
                    $locationKind = $this->memberPortalPageKind((string) (parse_url($location, PHP_URL_PATH) ?? ''));
                    if ($area === 'member' && $requestKind !== 'other' && $locationKind !== 'other' && $locationKind !== $requestKind) {
                        $mapped = $this->mapMemberPortalRedirect($location);

                        return ['status' => 'redirect', 'to' => $mapped ?? url('/member-portal/login')];
                    }

                    $url = $location;
                    continue;
                }

                if ($area === 'member') {
                    $mapped = $this->mapMemberPortalRedirect($location);

                    return ['status' => 'redirect', 'to' => $mapped ?? url('/member-portal/login')];
                }

                // Non-member off-host redirect: stop and show fallback rather than looping.
                break;
            }

            if ($response === null || ! $response->successful() || trim($response->body()) === '') {
                return [
                    'status' => 'ok',
                    'html' => $this->fallbackPage($legacyPath, $area, 'Legacy page returned an empty response.'),
                ];
            }

            return [
                'status' => 'ok',
                'html' => $this->rewrite($response->body(), $area, $normalizedPath),
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'status' => 'ok',
                'html' => $this->fallbackPage($legacyPath, $area, 'Could not reach the legacy public site. Start XAMPP Apache or check PORTAL_LEGACY_PUBLIC_BASE.'),
            ];
        }
    }

    private function memberPortalPageKind(string $path): string
    {
        $path = strtolower(str_replace('\\', '/', $path));
        if (str_contains($path, 'member-portal/login') || str_ends_with($path, '/login')) {
            return 'login';
        }
        if (str_contains($path, 'member-portal')) {
            return 'dashboard';
        }

        return 'other';
    }

    private function isUnderLegacyBase(string $url, string $legacyBase): bool
    {
        $legacyHost = strtolower((string) (parse_url($legacyBase, PHP_URL_HOST) ?? ''));
        $urlHost = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));
        if ($urlHost !== '' && $legacyHost !== '' && $urlHost !== $legacyHost) {
            return false;
        }

        $legacyPath = rtrim((string) (parse_url($legacyBase, PHP_URL_PATH) ?? ''), '/');
        $urlPath = (string) (parse_url($url, PHP_URL_PATH) ?? '');

        return $legacyPath === '' || str_starts_with($urlPath, $legacyPath);
    }

    private function resolveRedirectUrl(string $currentUrl, string $location): string
    {
        $location = trim($location);
        if ($location === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $location) === 1) {
            return $location;
        }

        $parts = parse_url($currentUrl) ?: [];
        $scheme = $parts['scheme'] ?? 'http';
        $host = $parts['host'] ?? 'localhost';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $basePath = $parts['path'] ?? '/';

        if (str_starts_with($location, '/')) {
            return $scheme.'://'.$host.$port.$location;
        }

        $dir = preg_replace('#/[^/]*$#', '/', $basePath) ?: '/';

        return $scheme.'://'.$host.$port.$dir.$location;
    }

    private function mapMemberPortalRedirect(string $location): ?string
    {
        $location = trim($location);
        if ($location === '') {
            return null;
        }

        $path = (string) (parse_url($location, PHP_URL_PATH) ?? $location);
        $query = parse_url($location, PHP_URL_QUERY);
        $querySuffix = is_string($query) && $query !== '' ? '?'.$query : '';

        if (str_contains($path, 'member-portal/login')) {
            return url('/member-portal/login').$querySuffix;
        }

        if (str_contains($path, 'member-portal')) {
            return url('/member-portal');
        }

        return null;
    }

    public function rewrite(string $html, string $area = 'ag', string $legacyPath = ''): string
    {
        $appBase = rtrim(url('/'), '/');
        $legacyBase = rtrim((string) config('portal.legacy_public_base'), '/');
        $mediaBase = rtrim((string) config('portal.media_base'), '/');
        $siteAsset = url('/site');
        $sdtgAsset = url('/site/sdgt');

        $agDirs = 'css|js|lib|images|img|videos|uploads';
        $sdtgDirs = 'css|js|img|videos';

        // 1) Absolute legacy asset URLs → local /site (or /site/sdgt) — do this BEFORE swapping page bases.
        if ($area === 'sdtg') {
            $html = preg_replace(
                '#'.preg_quote($legacyBase, '#').'/sdgt/('.$sdtgDirs.')/#i',
                $sdtgAsset.'/$1/',
                $html
            ) ?? $html;
            $html = preg_replace(
                '#'.preg_quote($legacyBase, '#').'/('.$agDirs.')/#i',
                $siteAsset.'/$1/',
                $html
            ) ?? $html;
        } else {
            $html = preg_replace(
                '#'.preg_quote($legacyBase, '#').'/('.$agDirs.')/#i',
                $siteAsset.'/$1/',
                $html
            ) ?? $html;
        }

        // Same for common localhost variants if config base differs slightly.
        foreach ([$mediaBase, 'http://localhost/AG_IKENEGBU_CHURCH_WEBSITE', 'http://127.0.0.1/AG_IKENEGBU_CHURCH_WEBSITE'] as $base) {
            $base = rtrim((string) $base, '/');
            if ($base === '' || $base === $legacyBase) {
                continue;
            }
            if ($area === 'sdtg') {
                $html = preg_replace('#'.preg_quote($base, '#').'/sdgt/('.$sdtgDirs.')/#i', $sdtgAsset.'/$1/', $html) ?? $html;
            }
            $html = preg_replace('#'.preg_quote($base, '#').'/('.$agDirs.')/#i', $siteAsset.'/$1/', $html) ?? $html;
        }

        // 2) Relative asset paths.
        if ($area === 'sdtg') {
            $html = preg_replace('#\b(href|src|data-src|poster)=([\'"])(css|js|img|videos)/#i', '$1=$2'.$sdtgAsset.'/$3/', $html) ?? $html;
            $html = preg_replace('#\b(href|src|data-src)=([\'"])\.\./(css|js|lib|images|img|videos|uploads)/#i', '$1=$2'.$siteAsset.'/$3/', $html) ?? $html;
            // JS playlist-style strings: '../videos/glory1.mp4' inside inline scripts
            $html = preg_replace('#([\'"])\.\./videos/#i', '$1'.$siteAsset.'/videos/', $html) ?? $html;
            $html = preg_replace('#([\'"])videos/#i', '$1'.$sdtgAsset.'/videos/', $html) ?? $html;
        } else {
            $html = preg_replace('#\b(href|src|data-src|poster)=([\'"])\.\./(css|js|lib|images|img|videos|uploads)/#i', '$1=$2'.$siteAsset.'/$3/', $html) ?? $html;
            $html = preg_replace('#\b(href|src|data-src|poster)=([\'"])(css|js|lib|images|img|videos|uploads)/#i', '$1=$2'.$siteAsset.'/$3/', $html) ?? $html;
            // sermon-library local assets (style.css / script.js next to index)
            $html = preg_replace('#\b(href|src)=([\'"])(style\.css|script\.js)\2#i', '$1=$2'.$siteAsset.'/sermon-library/$3$2', $html) ?? $html;
            $html = preg_replace('#url\(([\'"]?)\.\./(images|img|videos)/#i', 'url($1'.$siteAsset.'/$2/', $html) ?? $html;
            $html = preg_replace('#url\(([\'"]?)(images|img|videos)/#i', 'url($1'.$siteAsset.'/$2/', $html) ?? $html;
            // Member portal may load portal JS from the legacy admin tree.
            $html = preg_replace(
                '#'.preg_quote($legacyBase, '#').'/portal/#i',
                $mediaBase.'/portal/',
                $html
            ) ?? $html;
        }

        // Member portal: pin API + page globals to Laravel same-origin routes (API is proxied).
        if ($area === 'member') {
            $apiBase = url('/api');
            $loginUrl = url('/member-portal/login');
            $dashUrl = url('/member-portal');
            $joinApi = url('/api/member-self-register.php');
            $html = preg_replace(
                '/window\.MEMBER_PORTAL_API\s*=\s*.*?;/',
                'window.MEMBER_PORTAL_API = '.json_encode($apiBase, JSON_UNESCAPED_SLASHES).';',
                $html
            ) ?? $html;
            $html = preg_replace(
                '/window\.MEMBER_PORTAL_LOGIN\s*=\s*.*?;/',
                'window.MEMBER_PORTAL_LOGIN = '.json_encode($loginUrl, JSON_UNESCAPED_SLASHES).';',
                $html
            ) ?? $html;
            $html = preg_replace(
                '/window\.MEMBER_PORTAL_DASH\s*=\s*.*?;/',
                'window.MEMBER_PORTAL_DASH = '.json_encode($dashUrl, JSON_UNESCAPED_SLASHES).';',
                $html
            ) ?? $html;
            $html = preg_replace(
                '/window\.MEMBER_JOIN_API\s*=\s*.*?;/',
                'window.MEMBER_JOIN_API = '.json_encode($joinApi, JSON_UNESCAPED_SLASHES).';',
                $html
            ) ?? $html;
        }

        // 3) Page URL host swaps (non-asset links still on legacy host).
        $replacements = [
            $legacyBase.'/' => $appBase.'/',
            $legacyBase => $appBase,
            '/AG_IKENEGBU_CHURCH_WEBSITE/' => $appBase.'/',
            'http://localhost/AG_IKENEGBU_CHURCH_WEBSITE/' => $appBase.'/',
            'http://127.0.0.1/AG_IKENEGBU_CHURCH_WEBSITE/' => $appBase.'/',
        ];
        $html = str_replace(array_keys($replacements), array_values($replacements), $html);

        // Public contact form must hit Laravel, not a rewritten /handlers/contact-handler 404.
        $contactSubmitUrl = url('/api/contact');
        $html = preg_replace(
            '#(?:https?://[^"\'\s]+)?/?(?:AG_IKENEGBU_CHURCH_WEBSITE/)?handlers/contact-handler(?:\.php)?#i',
            $contactSubmitUrl,
            $html
        ) ?? $html;
        $laravelCsrf = csrf_token();
        $html = preg_replace(
            '/(<form[^>]*class=["\'][^"\']*contact-form[^"\']*["\'][^>]*>)(\s*)(<input[^>]*name=["\']csrf_token["\'][^>]*>)/i',
            '$1$2<input type="hidden" name="_token" value="'.e($laravelCsrf).'">',
            $html,
            1
        ) ?? $html;
        // If the csrf_token input was not nested as expected, still inject Laravel CSRF near the form.
        if (! str_contains($html, 'name="_token"') && str_contains($html, 'contact-form')) {
            $html = preg_replace(
                '/(<form[^>]*class=["\'][^"\']*contact-form[^"\']*["\'][^>]*>)/i',
                '$1<input type="hidden" name="_token" value="'.e($laravelCsrf).'">',
                $html,
                1
            ) ?? $html;
        }

        // 4) Fix any leftover app-root asset URLs caused by host swaps
        // e.g. http://127.0.0.1:8000/css/blog.css → /site/css/blog.css
        $html = preg_replace('#'.preg_quote($appBase, '#').'/('.$agDirs.')/#i', $siteAsset.'/$1/', $html) ?? $html;
        $html = preg_replace('#'.preg_quote($appBase, '#').'/sdgt/('.$sdtgDirs.')/#i', $sdtgAsset.'/$1/', $html) ?? $html;

        // 5) Nav / CTA path normalization.
        if ($area === 'sdtg') {
            $sdtgLinks = [
                'about', 'speakers', 'gallery', 'livestream', 'contact',
                'registration', 'donate', 'privacy', 'terms', 'index',
            ];
            foreach ($sdtgLinks as $page) {
                $target = $page === 'index' ? $appBase.'/sdgt' : $appBase.'/sdgt/'.$page;
                $html = str_replace(
                    ['href="'.$page.'"', "href='".$page."'", 'href="'.$page.'.php"', "href='".$page.".php'"],
                    ['href="'.$target.'"', "href='".$target."'", 'href="'.$target.'"', "href='".$target."'"],
                    $html
                );
            }
            $html = str_replace(
                ['href="./"', "href='./'", 'href="index.php"', "href='index.php'"],
                ['href="'.$appBase.'/sdgt"', "href='".$appBase."/sdgt'", 'href="'.$appBase.'/sdgt"', "href='".$appBase."/sdgt'"],
                $html
            );
        } else {
            $linkMap = [
                'href="about.php"' => 'href="'.$appBase.'/about"',
                "href='about.php'" => "href='".$appBase."/about'",
                'href="about"' => 'href="'.$appBase.'/about"',
                'href="./"' => 'href="'.$appBase.'/"',
                'href="activity"' => 'href="'.$appBase.'/activity"',
                'href="activity.php"' => 'href="'.$appBase.'/activity"',
                'href="event"' => 'href="'.$appBase.'/event"',
                'href="event.php"' => 'href="'.$appBase.'/event"',
                'href="blog"' => 'href="'.$appBase.'/blog"',
                'href="blog.php"' => 'href="'.$appBase.'/blog"',
                'href="contact"' => 'href="'.$appBase.'/contact"',
                'href="contact.php"' => 'href="'.$appBase.'/contact"',
                'href="donate"' => 'href="'.$appBase.'/donate"',
                'href="donate.php"' => 'href="'.$appBase.'/donate"',
                'href="privacy"' => 'href="'.$appBase.'/privacy"',
                'href="terms"' => 'href="'.$appBase.'/terms"',
                'href="sermon-library/"' => 'href="'.$appBase.'/sermons"',
                'href="sermon-library/index"' => 'href="'.$appBase.'/sermons"',
                'href="sermon-library/index.php"' => 'href="'.$appBase.'/sermons"',
                'href="sermon.php"' => 'href="'.$appBase.'/sermons"',
                'href="/sdgt/"' => 'href="'.$appBase.'/sdgt"',
                'href="/sdgt"' => 'href="'.$appBase.'/sdgt"',
                'href="member-portal/login"' => 'href="'.$appBase.'/member-portal/login"',
                'href="member-portal/"' => 'href="'.$appBase.'/member-portal"',
                'href="member-portal"' => 'href="'.$appBase.'/member-portal"',
                'href="member-portal/login.php"' => 'href="'.$appBase.'/member-portal/login"',
            ];
            $html = str_replace(array_keys($linkMap), array_values($linkMap), $html);
        }

        $html = str_replace(
            'href="'.$siteAsset.'/images/ag-logo.jpeg"',
            'href="'.$this->assets->url('images/ag-logo.jpeg').'"',
            $html
        );

        if ($area === 'sdtg' && preg_match('#(^|/)gallery(\.php)?$#i', trim($legacyPath, '/')) === 1) {
            $html = $this->injectSdtgGalleryBootstrap($html);
        }

        return $this->applySeoMeta($html, $legacyPath, $area);
    }

    private function applySeoMeta(string $html, string $legacyPath, string $area): string
    {
        if ($area === 'member' || ! str_contains(strtolower($html), '<head')) {
            return $html;
        }

        $key = $this->seo->keyFromLegacyPath($legacyPath, $area);
        $canonical = match ($key) {
            'home' => url('/'),
            'about' => url('/about'),
            'contact' => url('/contact'),
            'blog' => url('/blog'),
            'activity' => url('/activity'),
            'event' => url('/event'),
            'donate' => url('/donate'),
            'privacy' => url('/privacy'),
            'terms' => url('/terms'),
            'sermons' => url('/sermons'),
            'sdtg' => url('/sdgt'),
            default => url('/'),
        };
        $meta = $this->seo->forKey($key, $canonical);
        $title = e($meta['title']);
        $description = e($meta['meta_description']);
        $canonicalEsc = e($meta['canonical']);
        $ogImage = trim((string) $meta['og_image']);
        if ($ogImage !== '' && ! str_starts_with($ogImage, 'http')) {
            $ogImage = $this->assets->url($ogImage);
        }
        if ($ogImage === '') {
            $ogImage = $this->assets->url('images/ag-logo.jpeg');
        }
        $ogImageEsc = e($ogImage);

        if (preg_match('/<title\b[^>]*>.*?<\/title>/is', $html) === 1) {
            $html = preg_replace('/<title\b[^>]*>.*?<\/title>/is', '<title>'.$title.'</title>', $html, 1) ?? $html;
        } else {
            $html = preg_replace('/<head([^>]*)>/i', '<head$1><title>'.$title.'</title>', $html, 1) ?? $html;
        }

        if (preg_match('/<meta\s+name=["\']description["\'][^>]*>/i', $html) === 1) {
            $html = preg_replace(
                '/<meta\s+name=["\']description["\'][^>]*>/i',
                '<meta name="description" content="'.$description.'">',
                $html,
                1
            ) ?? $html;
        } else {
            $html = preg_replace(
                '/<\/title>/i',
                '</title>'."\n".'<meta name="description" content="'.$description.'">',
                $html,
                1
            ) ?? $html;
        }

        if (preg_match('/<link\s+rel=["\']canonical["\'][^>]*>/i', $html) === 1) {
            $html = preg_replace(
                '/<link\s+rel=["\']canonical["\'][^>]*>/i',
                '<link rel="canonical" href="'.$canonicalEsc.'">',
                $html,
                1
            ) ?? $html;
        } else {
            $html = preg_replace(
                '/<meta\s+name=["\']description["\'][^>]*>/i',
                '$0'."\n".'<link rel="canonical" href="'.$canonicalEsc.'">',
                $html,
                1
            ) ?? $html;
        }

        $ogBlock = "\n".implode("\n", [
            '<meta property="og:type" content="website">',
            '<meta property="og:title" content="'.$title.'">',
            '<meta property="og:description" content="'.$description.'">',
            '<meta property="og:url" content="'.$canonicalEsc.'">',
            '<meta property="og:image" content="'.$ogImageEsc.'">',
        ]);

        // Avoid duplicating OG tags on every rewrite pass.
        if (! str_contains($html, 'property="og:title"')) {
            $html = preg_replace(
                '/<link\s+rel=["\']canonical["\'][^>]*>/i',
                '$0'.$ogBlock,
                $html,
                1
            ) ?? $html;
        }

        return $html;
    }

    private function injectSdtgGalleryBootstrap(string $html): string
    {
        $payload = json_encode(
            $this->sdtgGallery->bootstrap(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        if ($payload === false) {
            return $html;
        }

        $script = '<script>window.SDTG_GALLERY_BOOTSTRAP = '.$payload.';</script>';

        if (preg_match('#<script>\s*window\.SDTG_GALLERY_BOOTSTRAP\s*=#i', $html) === 1) {
            $html = preg_replace(
                '#<script>\s*window\.SDTG_GALLERY_BOOTSTRAP\s*=.*?</script>#is',
                $script,
                $html,
                1
            ) ?? $html;
        } else {
            $html = str_ireplace('</body>', $script.'</body>', $html);
        }

        $apiUrl = url('/api/sdtg-gallery');
        $html = preg_replace(
            "#const API_URL = ['\"][^'\"]*['\"]#",
            "const API_URL = '".$apiUrl."'",
            $html
        ) ?? $html;

        $memoryUrl = url('/api/sdtg-memory');
        $html = preg_replace(
            "#fetch\\(['\"]\\.\\./api/submit-sdtg-memory\\.php['\"]#",
            "fetch('".$memoryUrl."'",
            $html
        ) ?? $html;
        $html = preg_replace(
            "#fetch\\(['\"]/api/submit-sdtg-memory\\.php['\"]#",
            "fetch('".$memoryUrl."'",
            $html
        ) ?? $html;

        return $html;
    }

    private function fallbackPage(string $legacyPath, string $area, string $message): string
    {
        $title = $area === 'sdtg'
            ? (string) config('identity.public.sdtg_label', 'Send Down Thy Glory')
            : (string) config('identity.public.short_name', 'AG Ikenebgu');
        $home = e(url('/'));
        $legacy = e(rtrim((string) config('portal.legacy_public_base'), '/').'/'.ltrim($legacyPath, '/'));
        $msg = e($message);

        return <<<HTML
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$title}</title>
<link rel="stylesheet" href="{$home}/site/css/bootstrap.min.css">
<link rel="stylesheet" href="{$home}/site/css/style.css">
<link rel="stylesheet" href="{$home}/site/css/brand.css">
</head><body class="ag-site-body p-5">
<div class="container py-5">
  <h1 class="mb-3">{$title}</h1>
  <p class="text-muted">{$msg}</p>
  <p><a class="btn btn-primary me-2" href="{$home}">Back to homepage</a>
  <a class="btn btn-outline-primary" href="{$legacy}" target="_blank" rel="noopener">Open legacy page</a></p>
</div></body></html>
HTML;
    }
}
