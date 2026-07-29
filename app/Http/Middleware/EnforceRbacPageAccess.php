<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Services\Auth\RbacNavAccessService;
use App\Services\Portal\PortalNavService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * When RBAC enforcement is on, block pages the admin's roles cannot view.
 */
final class EnforceRbacPageAccess
{
    public function __construct(
        private readonly RbacNavAccessService $access,
        private readonly PortalNavService $nav,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Admin|null $admin */
        $admin = auth('admin')->user();
        if ($admin === null || ! $this->access->isEnforcementEnabled() || $this->access->shouldBypass($admin)) {
            return $next($request);
        }

        $navId = $this->nav->resolveActivePage($request->path());

        // Scoped roles land on their module home — bounce them off the main admin overview.
        if ($navId === 'dashboard') {
            $home = $this->nav->homeHrefForAdmin($admin);
            if (! $this->urlsMatch($home, route('dashboard'))) {
                return redirect()->to($home);
            }

            return $next($request);
        }

        if ($this->access->canShowNavItem($admin, $navId)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'You do not have permission to access this page.',
                'nav_id' => $navId,
            ], 403);
        }

        $home = $this->nav->homeHrefForAdmin($admin);

        return redirect()
            ->to($home)
            ->withErrors(['rbac' => 'You do not have permission to open that page. Your sidebar shows only modules allowed for your roles.']);
    }

    private function urlsMatch(string $left, string $right): bool
    {
        return rtrim($left, '/') === rtrim($right, '/');
    }
}
