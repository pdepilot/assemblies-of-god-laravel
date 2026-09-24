<?php

namespace App\Services\Portal;

use App\Models\Admin;
use App\Services\Auth\RbacNavAccessService;
use App\Support\RbacPlatform;
use Illuminate\Support\Facades\Route;

final class PortalNavService
{
    public function __construct(
        private readonly RbacNavAccessService $navAccess,
    ) {}

    /** @return array<string, mixed> */
    public function cmsConfig(?Admin $admin = null, ?string $platform = null): array
    {
        $platform = RbacPlatform::normalize($platform ?? RbacPlatform::current(), RbacPlatform::AG);

        return [
            'brand' => [], // Filled by CmsShellBootService from identity.php
            'nav' => $this->navForAdmin($admin, $platform),
            'notifications' => config('portal.notifications'),
            'platform' => $platform,
        ];
    }

    /**
     * Preferred landing href when a scoped admin logs in or is bounced from a denied page.
     */
    public function homeHrefForAdmin(?Admin $admin, ?string $platform = null): string
    {
        $platform = RbacPlatform::normalize($platform ?? RbacPlatform::current(), RbacPlatform::AG);
        $dashboard = $this->route('dashboard');

        if ($admin === null || ! $this->navAccess->isScoped($admin)) {
            return $dashboard;
        }

        $homeNavId = $this->navAccess->preferredHomeNavId($admin);
        if ($homeNavId !== 'dashboard') {
            $href = $this->navAccess->hrefForNavId($homeNavId);
            if ($href !== '#' && $this->navAccess->canShowNavItem($admin, $homeNavId)) {
                // Only use preferred home when it belongs to the active platform nav.
                foreach ($this->navForAdmin($admin, $platform) as $item) {
                    if (($item['type'] ?? null) === 'group') {
                        foreach ($item['children'] ?? [] as $child) {
                            if ((string) ($child['id'] ?? '') === $homeNavId) {
                                return $href;
                            }
                        }
                    } elseif ((string) ($item['id'] ?? '') === $homeNavId) {
                        return $href;
                    }
                }
            }
        }

        foreach ($this->navForAdmin($admin, $platform) as $item) {
            if (($item['type'] ?? null) === 'group') {
                foreach ($item['children'] ?? [] as $child) {
                    $id = (string) ($child['id'] ?? '');
                    if ($id === 'dashboard') {
                        continue;
                    }
                    $href = (string) ($child['href'] ?? '');
                    if ($href !== '' && $href !== '#') {
                        return $href;
                    }
                }
                continue;
            }

            $id = (string) ($item['id'] ?? '');
            if ($id === 'dashboard') {
                continue;
            }
            $href = (string) ($item['href'] ?? '');
            if ($href !== '' && $href !== '#') {
                return $href;
            }
        }

        return $dashboard;
    }

    /**
     * Sidebar tree filtered by platform context, then by RBAC when enforcement is on.
     *
     * @return list<array<string, mixed>>
     */
    public function navForAdmin(?Admin $admin, ?string $platform = null): array
    {
        $platform = RbacPlatform::normalize($platform ?? RbacPlatform::current(), RbacPlatform::AG);
        $nav = $this->stripSdtgNav($this->filterNavByPlatform($this->fullNav($platform), $platform));

        if ($admin === null
            || ! $this->navAccess->isEnforcementEnabled()
            || $this->navAccess->shouldBypass($admin)) {
            return $nav;
        }

        $filtered = [];
        foreach ($nav as $item) {
            if (($item['type'] ?? null) === 'group') {
                $children = [];
                foreach ($item['children'] ?? [] as $child) {
                    if ($this->navAccess->canShowNavItem($admin, (string) ($child['id'] ?? ''))) {
                        $children[] = $child;
                    }
                }
                if ($children === []) {
                    continue;
                }
                $item['children'] = $children;
                $filtered[] = $item;
                continue;
            }

            if ($this->navAccess->canShowNavItem($admin, (string) ($item['id'] ?? ''))) {
                $filtered[] = $item;
            }
        }

        return $filtered;
    }

    public function resolveActivePage(?string $path = null, ?string $platform = null): string
    {
        $platform = RbacPlatform::normalize($platform ?? RbacPlatform::current(), RbacPlatform::AG);
        $path = trim((string) ($path ?? request()->path()), '/');

        if ($path === '' || $path === 'dashboard' || $path === 'admin/dashboard' || $path === 'admin') {
            return 'dashboard';
        }

        $bestId = null;
        $bestLength = -1;

        foreach ($this->stripSdtgNav($this->filterNavByPlatform($this->fullNav($platform), $platform)) as $item) {
            if (($item['type'] ?? null) === 'group') {
                foreach ($item['children'] ?? [] as $child) {
                    $length = $this->hrefMatchLength($child['href'] ?? '', $path);
                    if ($length > $bestLength) {
                        $bestLength = $length;
                        $bestId = (string) $child['id'];
                    }
                }
                continue;
            }

            $length = $this->hrefMatchLength($item['href'] ?? '', $path);
            if ($length > $bestLength) {
                $bestLength = $length;
                $bestId = (string) $item['id'];
            }
        }

        if ($bestId !== null) {
            return $bestId;
        }

        return $this->aliasActivePage($path, $platform) ?? 'dashboard';
    }

    private function hrefMatchLength(string $href, string $path): int
    {
        if ($href === '' || $href === '#') {
            return -1;
        }

        $hrefPath = str_starts_with($href, 'http')
            ? trim((string) parse_url($href, PHP_URL_PATH), '/')
            : trim($href, '/');

        if ($hrefPath === '') {
            return -1;
        }

        if ($hrefPath === $path || str_starts_with($path, $hrefPath.'/')) {
            return strlen($hrefPath);
        }

        return -1;
    }

    private function aliasActivePage(string $path, string $platform): ?string
    {
        $aliases = [
            'admin/sunday-school/attendance' => 'attendance',
            'admin/sunday-school' => 'sunday-school',
            'sunday-school' => 'sunday-school',
            'members/deceased' => 'deceased',
            'admin/members/deceased' => 'deceased',
            'members' => 'members',
            'visitors' => 'visitors',
            'events' => 'events',
            'donations' => 'donations',
            'commitments' => 'recurring-donations',
            'pledges' => 'partnerships',
            'contact' => 'contact',
            'newsletter-subscribers' => 'newsletter-subscribers',
            'admin/testimonies' => 'ag-testimonies',
            'sermons' => 'sermons',
            'financial-erp' => 'erp-launch',
            'registration-portals' => 'rp-portals',
            'admin/website/worship' => 'worship-schedule',
            'admin/website/activities' => 'homepage-activities',
            'website' => 'pages',
            'communication-hub/email-center' => 'ch-email',
            'communication-hub/automation' => 'ch-automation',
            'communication-hub/campaigns' => 'ch-campaigns',
            'communication-hub/scheduled' => 'ch-scheduled',
            'communication-hub/queue' => 'ch-queue',
            'communication-hub/recipients' => 'ch-recipients',
            'communication-hub/analytics' => 'ch-analytics',
            'communication-hub/ai-assistant' => 'ch-ai',
            'communication-hub' => 'ch-dashboard',
            'analytics/site-traffic' => 'site-traffic',
            'analytics/reports' => 'reports',
            'admin/analytics/reports' => 'reports',
            'admin/reports' => 'reports',
            'analytics/cutover' => 'cutover',
            'settings' => 'settings',
            'admin/settings' => 'settings',
            'security/bans' => 'security-bans',
            'security/activity-logs' => 'activity-logs',
            'ministries/settings' => 'ministry-settings',
            'ministries/age-transfers' => 'ministry-age-transfers',
            'ministries/children' => 'children',
            'ministries/teens' => 'teens',
            'ministries/youths' => 'youths',
            'ministries/men' => 'men',
            'ministries/women' => 'women',
            'ministries/widows' => 'widows',
            'ministries/music' => 'music',
            'ministries/choir' => 'choir',
            'ministries/ushers' => 'ushers',
            'ministries/media' => 'media',
        ];

        foreach ($aliases as $prefix => $id) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return $id;
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $nav
     * @return list<array<string, mixed>>
     */
    private function filterNavByPlatform(array $nav, string $platform): array
    {
        $out = [];
        foreach ($nav as $item) {
            $itemPlatform = RbacPlatform::normalize(
                (string) ($item['platform'] ?? RbacPlatform::AG),
                RbacPlatform::AG
            );
            // "shared" reserved for future; shared-system is topbar-only, not sidebar business modules.
            if ($itemPlatform === RbacPlatform::BOTH) {
                $out[] = $item;
                continue;
            }
            if ($itemPlatform === $platform) {
                $out[] = $item;
            }
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $nav
     * @return list<array<string, mixed>>
     */
    private function stripSdtgNav(array $nav): array
    {
        $out = [];
        foreach ($nav as $item) {
            if ($this->isSdtgNavItem($item)) {
                continue;
            }
            if (($item['type'] ?? null) === 'group') {
                $children = [];
                foreach ($item['children'] ?? [] as $child) {
                    if (! $this->isSdtgNavItem(is_array($child) ? $child : [])) {
                        $children[] = $child;
                    }
                }
                if ($children === []) {
                    continue;
                }
                $item['children'] = $children;
            }
            $out[] = $item;
        }

        return $out;
    }

    /** @param array<string, mixed> $item */
    private function isSdtgNavItem(array $item): bool
    {
        $haystack = strtolower(trim(
            (string) ($item['id'] ?? '').' '.(string) ($item['label'] ?? '').' '.(string) ($item['href'] ?? '')
        ));

        if ($haystack === '') {
            return false;
        }

        if (str_contains($haystack, 'send down thy glory') || str_contains($haystack, '/sdtg')) {
            return true;
        }

        return (bool) preg_match('/(^|[^a-z])sdtg([^a-z]|$)/', $haystack);
    }

    /** @return list<array<string, mixed>> */
    private function fullNav(string $platform): array
    {
        $dashboardHref = $this->route('dashboard');

        $agBrand = (string) config('identity.admin.brand_name', config('portal.brand.name', 'AGC IKENEGBU'));

        return [
            [
                'id' => 'dashboard',
                'label' => 'Dashboard',
                'icon' => 'fa-gauge-high',
                'href' => $dashboardHref,
                'platform' => $platform,
            ],
            [
                'id' => 'ag',
                'label' => $agBrand,
                'type' => 'group',
                'platform' => RbacPlatform::AG,
                'children' => [
                    $this->item('members', 'Members', 'fa-users', 'members.index'),
                    $this->item('deceased', 'Deceased', 'fa-cross', 'members.deceased'),
                    $this->item('ministry-settings', 'Ministry Settings', 'fa-sliders', 'ministries.settings.index'),
                    $this->item('ministry-age-transfers', 'Age Transfers', 'fa-people-arrows', 'ministries.age-transfers.index'),
                    $this->ministry('children', 'Children Ministry', 'fa-child', 'children'),
                    $this->item('sunday-school', 'Sunday School', 'fa-book-open', 'ss.analytics.index'),
                    $this->ministry('teens', 'Teen Ministry', 'fa-user-graduate', 'teens'),
                    $this->ministry('youths', 'Youth Ministry', 'fa-people-group', 'youths'),
                    $this->ministry('men', "Men's Ministry", 'fa-person', 'men'),
                    $this->ministry('women', "Women's Ministry", 'fa-person-dress', 'women'),
                    $this->ministry('widows', 'Widows', 'fa-person-dress', 'widows'),
                    $this->ministry('music', 'Music', 'fa-music', 'music'),
                    $this->ministry('choir', 'Choir', 'fa-users-line', 'choir'),
                    $this->ministry('ushers', 'Ushering', 'fa-door-open', 'ushers'),
                    $this->ministry('media', 'Media Team', 'fa-video', 'media'),
                    $this->item('visitors', 'Visitors', 'fa-handshake', 'visitors.index'),
                    $this->item('attendance', 'Attendance', 'fa-clipboard-check', 'ss.attendance.index'),
                    $this->item('events', 'Events', 'fa-calendar-days', 'events.index'),
                    $this->item('sermons', 'Sermons & Live', 'fa-book-bible', 'sermon.dashboard'),
                    $this->item('donations', 'Donations', 'fa-hand-holding-heart', 'donations.index'),
                    $this->item('recurring-donations', 'Recurring Giving', 'fa-rotate', 'commitments.index'),
                    $this->item('partnerships', 'Kingdom Partnership', 'fa-handshake-angle', 'pledges.index'),
                    $this->item('stewardship', 'Stewardship', 'fa-chart-line', 'donations.index'),
                    $this->item('contact', 'Contact Inbox', 'fa-envelope-open-text', 'contact.submissions.index'),
                    $this->item('newsletter-subscribers', 'Newsletter Subscribers', 'fa-at', 'newsletter-subscribers.index'),
                    $this->item('ag-testimonies', 'Testimonies', 'fa-quote-left', 'testimonies.index'),
                    $this->item('reports', 'Reports', 'fa-chart-pie', 'analytics.reports.index'),
                    $this->item('messages', 'Send Emails', 'fa-envelope', 'communication-hub.dashboard'),
                ],
            ],
            [
                'id' => 'communication-hub',
                'label' => 'COMMUNICATION HUB',
                'type' => 'group',
                'platform' => RbacPlatform::AG,
                'children' => [
                    $this->item('ch-dashboard', 'Communication Dashboard', 'fa-gauge-high', 'communication-hub.dashboard'),
                    $this->item('ch-birthdays', 'Birthday Calendar', 'fa-cake-candles', 'communication-hub.birthdays.index'),
                    $this->item('ch-email', 'Email Center', 'fa-envelope-open-text', 'communication-hub.email-center.index'),
                    $this->item('ch-sms', 'SMS Center', 'fa-comment-sms', 'communication-hub.sms-center.index'),
                    $this->item('ch-templates', 'Templates', 'fa-file-lines', 'communication-hub.templates.index'),
                    $this->item('ch-automation', 'Automation Rules', 'fa-robot', 'communication-hub.automation.index'),
                    $this->item('ch-newsletter', 'Newsletter Builder', 'fa-newspaper', 'communication-hub.newsletter-drafts.index'),
                    $this->item('ch-campaigns', 'Campaign Manager', 'fa-bullhorn', 'communication-hub.campaigns.index'),
                    $this->item('ch-scheduled', 'Scheduled Messages', 'fa-clock', 'communication-hub.scheduled.index'),
                    $this->item('ch-queue', 'Communication Queue', 'fa-list-check', 'communication-hub.queue.index'),
                    $this->item('ch-recipients', 'Recipient Groups', 'fa-user-group', 'communication-hub.recipients.index'),
                    $this->item('ch-logs', 'Communication Logs', 'fa-clipboard-list', 'communication-hub.logs.index'),
                    $this->item('ch-analytics', 'Analytics', 'fa-chart-line', 'communication-hub.analytics.index'),
                    $this->item('ch-notifications', 'Notification Center', 'fa-bell', 'communication-hub.notifications.index'),
                    $this->item('ch-ai', 'AI Message Assistant', 'fa-wand-magic-sparkles', 'communication-hub.ai-assistant.index'),
                ],
            ],
            [
                'id' => 'financial-erp',
                'label' => 'FINANCIAL ERP',
                'type' => 'group',
                'platform' => RbacPlatform::AG,
                'children' => [
                    $this->item('erp-launch', 'Open Financial ERP', 'fa-chart-line', 'financial-erp.launch'),
                ],
            ],
            [
                'id' => 'registration-portals',
                'label' => 'REGISTRATION PORTALS',
                'type' => 'group',
                'platform' => RbacPlatform::AG,
                'children' => [
                    $this->item('rp-portals', 'All Portals', 'fa-door-open', 'registration-portals.index'),
                    $this->item('rp-create', 'Create Portal', 'fa-plus-circle', 'registration-portals.create'),
                ],
            ],
            [
                'id' => 'website',
                'label' => 'WEBSITE MANAGEMENT',
                'type' => 'group',
                'platform' => RbacPlatform::AG,
                'children' => [
                    $this->item('pages', 'Page Manager', 'fa-file-lines', 'website.pages.index'),
                    $this->item('promotions', 'Promotion Banner', 'fa-bullhorn', 'website.promotions.index'),
                    $this->item('blog', 'Blog', 'fa-newspaper', 'website.blog.index'),
                    $this->item('about-content', 'About Content', 'fa-church', 'website.about.edit'),
                    $this->item('worship-schedule', 'Our Worship', 'fa-calendar-days', 'website.worship.edit'),
                    $this->item('homepage-activities', 'Homepage Activities', 'fa-hands', 'website.activities.edit'),
                    $this->item('team-section', 'Team Sections', 'fa-people-group', 'website.team.index'),
                    $this->item('media-library', 'Media Library', 'fa-folder-open', 'website.media.index'),
                    $this->item('seo', 'SEO Manager', 'fa-magnifying-glass-chart', 'website.seo.index'),
                    $this->item('site-traffic', 'Site Traffic', 'fa-chart-area', 'analytics.site-traffic.index'),
                ],
            ],
            [
                'id' => 'system',
                'label' => 'SYSTEM',
                'type' => 'group',
                'platform' => RbacPlatform::AG,
                'children' => [
                    $this->item('cutover', 'Migration Cutover', 'fa-route', 'analytics.cutover.index'),
                    $this->item('settings', 'Settings', 'fa-gear', 'settings.index'),
                    $this->item('security-bans', 'Banned IPs', 'fa-ban', 'security.bans.index'),
                    $this->item('activity-logs', 'Activity Logs', 'fa-clock-rotate-left', 'security.activity-logs.index'),
                ],
            ],
        ];
    }

    /** @return array{id: string, label: string, icon: string, href: string} */
    private function item(string $id, string $label, string $icon, string $routeName): array
    {
        return [
            'id' => $id,
            'label' => $label,
            'icon' => $icon,
            'href' => $this->route($routeName),
        ];
    }

    /** @return array{id: string, label: string, icon: string, href: string} */
    private function hrefItem(string $id, string $label, string $icon, string $href): array
    {
        return [
            'id' => $id,
            'label' => $label,
            'icon' => $icon,
            'href' => $href,
        ];
    }

    /** @return array{id: string, label: string, icon: string, href: string} */
    private function ministry(string $id, string $label, string $icon, string $ministryKey): array
    {
        return [
            'id' => $id,
            'label' => $label,
            'icon' => $icon,
            'href' => $this->route('ministries.module.index', ['ministryKey' => $ministryKey]),
        ];
    }

    /** @return array{id: string, label: string, icon: string, href: string} */
    private function legacy(string $id, string $label, string $icon, string $path): array
    {
        return [
            'id' => $id,
            'label' => $label,
            'icon' => $icon,
            'href' => $this->legacyUrl($path),
        ];
    }

    /** @param array<string, mixed> $params */
    private function route(string $routeName, array $params = []): string
    {
        if (Route::has($routeName)) {
            return route($routeName, $params);
        }

        return '#';
    }

    private function legacyUrl(string $path): string
    {
        return rtrim((string) config('portal.legacy_admin_base'), '/').'/'.ltrim($path, '/');
    }
}
