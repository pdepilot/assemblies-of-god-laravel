<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Services\PublicSite\LegacyHtmlBridge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class LegacyBridgeController extends Controller
{
    public function __construct(
        private readonly LegacyHtmlBridge $bridge,
    ) {}

    public function about(): Response
    {
        return $this->html($this->bridge->render('about.php'));
    }

    public function activity(): Response
    {
        return $this->html($this->bridge->render('activity.php'));
    }

    public function event(): Response
    {
        return $this->html($this->bridge->render('event.php'));
    }

    public function blog(): Response
    {
        return $this->html($this->bridge->render('blog.php'));
    }

    public function contact(): Response
    {
        return $this->html($this->bridge->render('contact.php'));
    }

    public function donate(): Response
    {
        return $this->html($this->bridge->render('donate.php'));
    }

    public function privacy(): Response
    {
        return $this->html($this->bridge->render('privacy.php'));
    }

    public function terms(): Response
    {
        return $this->html($this->bridge->render('terms.php'));
    }

    public function sermons(): Response
    {
        return $this->html($this->bridge->render('sermon-library/index.php'));
    }

    public function memberPortalLogin(Request $request): Response|RedirectResponse
    {
        $qs = $request->getQueryString();
        $path = 'member-portal/login'.($qs ? '?'.$qs : '');
        $result = $this->bridge->renderMemberPortal($path);

        if (($result['status'] ?? '') === 'redirect') {
            $to = (string) ($result['to'] ?? route('public.member-portal'));
            // Already authenticated → dashboard. Never redirect login → login (browser loop).
            if (str_contains($to, '/member-portal/login')) {
                $request->session()->forget('member_portal_cookies');
                $fresh = $this->bridge->renderMemberPortal($path);
                if (($fresh['status'] ?? '') === 'ok') {
                    return $this->html((string) ($fresh['html'] ?? ''));
                }

                return $this->html(
                    '<!DOCTYPE html><html><body><p>Could not load the member portal login page. '
                    .'Ensure XAMPP Apache is running, then retry.</p></body></html>'
                );
            }

            return redirect()->to($to);
        }

        return $this->html((string) ($result['html'] ?? ''));
    }

    public function memberPortalHome(Request $request): Response|RedirectResponse
    {
        $result = $this->bridge->renderMemberPortal('member-portal/');

        if (($result['status'] ?? '') === 'redirect') {
            return redirect()->route('public.member-portal.login');
        }

        return $this->html((string) ($result['html'] ?? ''));
    }

    public function join(): RedirectResponse
    {
        return redirect()->route('public.member-portal.login', ['mode' => 'join']);
    }

    private function html(string $body): Response
    {
        return response($body, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
