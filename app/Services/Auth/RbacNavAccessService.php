<?php

namespace App\Services\Auth;

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Maps sidebar nav ids → permission modules and builds CMS_NAV_ACCESS for the shell.
 */
final class RbacNavAccessService
{
    /** @var list<string> highest priority first */
    private const DASHBOARD_TYPE_PRIORITY = [
        'super_admin',
        'church_admin',
        'sdtg',
        'finance',
        'sunday_school',
        'youth',
        'teens',
        'children',
        'men',
        'women',
        'widowers',
        'widows',
        'music',
        'choir',
        'ushering',
        'media',
        'events',
        'communications',
    ];

    public function __construct(
        private readonly RbacReadService $rbac,
    ) {}

    /** @return array<string, string> nav item id => module key */
    public function navModuleMap(): array
    {
        return [
            'dashboard' => 'dashboard',
            'members' => 'members',
            'ministry-settings' => 'ministry_settings',
            'children' => 'children',
            'sunday-school' => 'sunday_school',
            'teens' => 'teens',
            'youths' => 'youths',
            'men' => 'men',
            'women' => 'women',
            'widowers' => 'widowers',
            'widows' => 'widows',
            'music' => 'music',
            'choir' => 'choir',
            'ushers' => 'ushering',
            'media' => 'media_team',
            'visitors' => 'visitors',
            'attendance' => 'attendance',
            'departments' => 'departments',
            'events' => 'events',
            'sermons' => 'sermons',
            'donations' => 'donations',
            'recurring-donations' => 'recurring_donations',
            'partnerships' => 'partnerships',
            'stewardship' => 'donations',
            'erp-launch' => 'financial_erp',
            'contact' => 'contact',
            'newsletter-subscribers' => 'contact',
            'ag-testimonies' => 'testimonies',
            'reports' => 'reports',
            'messages' => 'messages',
            'ch-dashboard' => 'communication_hub',
            'ch-birthdays' => 'communication_hub',
            'ch-email' => 'communication_hub',
            'ch-sms' => 'communication_hub',
            'ch-templates' => 'communication_hub',
            'ch-automation' => 'communication_hub',
            'ch-newsletter' => 'communication_hub',
            'ch-campaigns' => 'communication_hub',
            'ch-scheduled' => 'communication_hub',
            'ch-queue' => 'communication_hub',
            'ch-recipients' => 'communication_hub',
            'ch-logs' => 'communication_hub',
            'ch-analytics' => 'communication_hub',
            'ch-notifications' => 'communication_hub',
            'ch-ai' => 'communication_hub',
            'speakers' => 'sdtg',
            'registrations' => 'sdtg',
            'gallery' => 'sdtg',
            'announcements' => 'sdtg',
            'sdtg-content' => 'sdtg',
            'sdtg-sponsors' => 'sdtg',
            'livestream' => 'sdtg',
            'volunteers' => 'sdtg',
            'testimonies' => 'sdtg',
            'prayer-requests' => 'sdtg',
            'memory-submissions' => 'sdtg',
            'sdtg-donations' => 'sdtg',
            'sdtg-media-library' => 'sdtg',
            'rp-create' => 'registration_portals',
            'rp-portals' => 'registration_portals',
            'pages' => 'website_pages',
            'blog' => 'website_pages',
            'about-content' => 'website_pages',
            'team-section' => 'website_pages',
            'media-library' => 'media_library',
            'seo' => 'website_seo',
            'site-traffic' => 'website_traffic',
            'cutover' => 'settings',
            'settings' => 'settings',
            'security-bans' => 'security',
            'activity-logs' => 'audit_logs',
        ];
    }

    /**
     * dashboard_type => preferred nav id (used for landing + sidebar home).
     *
     * @return array<string, string>
     */
    public function dashboardTypeNavMap(): array
    {
        return [
            'super_admin' => 'dashboard',
            'church_admin' => 'dashboard',
            'youth' => 'youths',
            'teens' => 'teens',
            'children' => 'children',
            'men' => 'men',
            'women' => 'women',
            'widowers' => 'widowers',
            'widows' => 'widows',
            'music' => 'music',
            'choir' => 'choir',
            'ushering' => 'ushers',
            'sunday_school' => 'sunday-school',
            'finance' => 'donations',
            'sdtg' => 'speakers',
            'media' => 'media',
            'events' => 'events',
            'communications' => 'ch-dashboard',
        ];
    }

    public function isEnforcementEnabled(): bool
    {
        return $this->rbac->isRbacEnforcementEnabled();
    }

    public function shouldBypass(Admin $admin): bool
    {
        return $this->rbac->isSuperAdmin($admin) || $this->rbac->hasLegacyFullAccess($admin);
    }

    /**
     * True when enforcement is on and this admin is permission-scoped (not full bypass).
     */
    public function isScoped(Admin $admin): bool
    {
        return $this->isEnforcementEnabled() && ! $this->shouldBypass($admin);
    }

    /**
     * Highest-priority dashboard_type from the admin's assigned roles.
     */
    public function preferredDashboardType(Admin $admin): string
    {
        if (! Schema::hasTable('admin_role_assignments') || ! Schema::hasTable('roles')) {
            return 'church_admin';
        }

        $types = DB::table('admin_role_assignments as ara')
            ->join('roles as r', 'r.id', '=', 'ara.role_id')
            ->where('ara.admin_id', $admin->id)
            ->where('r.is_active', true)
            ->whereNotNull('r.dashboard_type')
            ->pluck('r.dashboard_type')
            ->map(static fn ($type) => trim((string) $type))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($types === []) {
            return 'church_admin';
        }

        foreach (self::DASHBOARD_TYPE_PRIORITY as $preferred) {
            if (in_array($preferred, $types, true)) {
                return $preferred;
            }
        }

        return $types[0];
    }

    public function preferredHomeNavId(Admin $admin): string
    {
        $navId = $this->preferredHomeNavIdWithoutDashboardGuard($admin);
        if ($navId !== 'dashboard' && $this->canShowNavItem($admin, $navId)) {
            return $navId;
        }

        return 'dashboard';
    }

    /**
     * Absolute URL for a nav id using known Laravel routes (fallback #).
     */
    public function hrefForNavId(string $navId): string
    {
        return match ($navId) {
            'dashboard' => $this->namedRoute('dashboard'),
            'donations', 'stewardship' => $this->namedRoute('donations.index'),
            'recurring-donations' => $this->namedRoute('commitments.index'),
            'partnerships' => $this->namedRoute('pledges.index'),
            'erp-launch' => $this->namedRoute('financial-erp.launch'),
            'reports' => $this->namedRoute('analytics.reports.index'),
            'sunday-school' => $this->namedRoute('ss.analytics.index'),
            'attendance' => $this->namedRoute('ss.attendance.index'),
            'events' => $this->namedRoute('events.index'),
            'members' => $this->namedRoute('members.index'),
            'visitors' => $this->namedRoute('visitors.index'),
            'ch-dashboard', 'messages' => $this->namedRoute('communication-hub.dashboard'),
            'speakers' => $this->namedRoute('sdtg.speakers.index'),
            'children' => $this->namedRoute('ministries.module.index', ['ministryKey' => 'children']),
            'teens' => $this->namedRoute('ministries.module.index', ['ministryKey' => 'teens']),
            'youths' => $this->namedRoute('ministries.module.index', ['ministryKey' => 'youths']),
            'men' => $this->namedRoute('ministries.module.index', ['ministryKey' => 'men']),
            'women' => $this->namedRoute('ministries.module.index', ['ministryKey' => 'women']),
            'widowers' => $this->namedRoute('ministries.module.index', ['ministryKey' => 'widowers']),
            'widows' => $this->namedRoute('ministries.module.index', ['ministryKey' => 'widows']),
            'music' => $this->namedRoute('ministries.module.index', ['ministryKey' => 'music']),
            'choir' => $this->namedRoute('ministries.module.index', ['ministryKey' => 'choir']),
            'ushers' => $this->namedRoute('ministries.module.index', ['ministryKey' => 'ushers']),
            'media' => $this->namedRoute('ministries.module.index', ['ministryKey' => 'media']),
            'settings' => $this->namedRoute('settings.index'),
            default => '#',
        };
    }

    /**
     * @return array{
     *   rbac_enabled: bool,
     *   fail_open: bool,
     *   super_admin_bypass: bool,
     *   items: array<string, bool>|object
     * }
     */
    public function getNavAccess(Admin $admin): array
    {
        if (! $this->isEnforcementEnabled()) {
            return [
                'rbac_enabled' => false,
                'fail_open' => true,
                'super_admin_bypass' => false,
                'items' => (object) [],
            ];
        }

        if ($this->shouldBypass($admin)) {
            return [
                'rbac_enabled' => true,
                'fail_open' => false,
                'super_admin_bypass' => true,
                'items' => (object) [],
            ];
        }

        $items = [];
        foreach ($this->navModuleMap() as $navId => $module) {
            $items[$navId] = $this->canViewModule($admin, $module);
        }

        $items['dashboard'] = $this->canShowNavItem($admin, 'dashboard');

        return [
            'rbac_enabled' => true,
            'fail_open' => false,
            'super_admin_bypass' => false,
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDashboardAccess(Admin $admin, string $homeRoute): array
    {
        $nav = $this->getNavAccess($admin);

        return [
            'rbac_enabled' => $nav['rbac_enabled'],
            'fail_open' => $nav['fail_open'],
            'super_admin_bypass' => $nav['super_admin_bypass'],
            'sections' => (object) [],
            'widgets' => (object) [],
            'quick_actions' => (object) [],
            'nav_items' => $nav['items'],
            'presentation' => [
                'title' => 'Dashboard',
                'subtitle' => 'Live overview from Members, Visitors, and platform activity.',
                'brand_subtitle' => 'Church Management System',
                'home_route' => $homeRoute,
            ],
        ];
    }

    public function canShowNavItem(Admin $admin, string $navId): bool
    {
        $navId = trim($navId);
        if ($navId === '') {
            return true;
        }

        if (! $this->isEnforcementEnabled() || $this->shouldBypass($admin)) {
            return true;
        }

        if ($navId === 'dashboard') {
            return $this->preferredHomeNavIdWithoutDashboardGuard($admin) === 'dashboard'
                && $this->canViewModule($admin, 'dashboard');
        }

        $module = $this->navModuleMap()[$navId] ?? null;
        if ($module === null) {
            // Unmapped items stay visible to avoid locking new screens by accident.
            return true;
        }

        return $this->canViewModule($admin, $module);
    }

    public function canViewModule(Admin $admin, string $module): bool
    {
        $module = trim($module);
        if ($module === '') {
            return true;
        }

        if ($this->rbac->can($admin, $module.'.view')) {
            return true;
        }

        // If they have any permission in the module (create/edit/…), show the nav entry.
        foreach ($this->rbac->permissionKeys($admin) as $key) {
            if ($key === '*' || str_starts_with($key, $module.'.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve preferred nav without applying the dashboard sidebar suppress rule
     * (avoids recursion inside canShowNavItem('dashboard')).
     */
    private function preferredHomeNavIdWithoutDashboardGuard(Admin $admin): string
    {
        $type = $this->preferredDashboardType($admin);
        $navId = $this->dashboardTypeNavMap()[$type] ?? 'dashboard';

        if ($navId === 'dashboard') {
            return 'dashboard';
        }

        $module = $this->navModuleMap()[$navId] ?? null;
        if ($module !== null && $this->canViewModule($admin, $module)) {
            return $navId;
        }

        return 'dashboard';
    }

    /** @param array<string, mixed> $params */
    private function namedRoute(string $name, array $params = []): string
    {
        if (Route::has($name)) {
            return route($name, $params);
        }

        return '#';
    }
}
