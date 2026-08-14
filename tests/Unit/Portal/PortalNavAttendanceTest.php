<?php

use App\Services\Portal\PortalNavService;

test('sidebar attendance points to laravel sunday school attendance', function () {
    $nav = app(PortalNavService::class)->cmsConfig()['nav'];
    $ag = collect($nav)->firstWhere('id', 'ag');
    $attendance = collect($ag['children'] ?? [])->firstWhere('id', 'attendance');

    expect($attendance)->not->toBeNull();
    expect($attendance['href'])->toBe(route('ss.attendance.index'));
    expect($attendance['href'])->not->toContain('/portal/attendance');
});

test('attendance page resolves active nav id', function () {
    $service = app(PortalNavService::class);

    expect($service->resolveActivePage('admin/sunday-school/attendance'))->toBe('attendance');
});

test('sidebar stewardship points to laravel donations hub', function () {
    $nav = app(PortalNavService::class)->cmsConfig()['nav'];
    $ag = collect($nav)->firstWhere('id', 'ag');
    $stewardship = collect($ag['children'] ?? [])->firstWhere('id', 'stewardship');

    expect($stewardship)->not->toBeNull();
    expect($stewardship['href'])->toBe(route('donations.index'));
    expect($stewardship['href'])->not->toContain('/portal/stewardship');
});

test('sidebar site testimonies points to laravel testimonies module', function () {
    $nav = app(PortalNavService::class)->cmsConfig()['nav'];
    $ag = collect($nav)->firstWhere('id', 'ag');
    $testimonies = collect($ag['children'] ?? [])->firstWhere('id', 'ag-testimonies');

    expect($testimonies)->not->toBeNull();
    expect($testimonies['href'])->toBe(route('testimonies.index'));
    expect($testimonies['href'])->not->toContain('/portal/testimonies');
});

test('site testimonies page resolves active nav id', function () {
    $service = app(PortalNavService::class);

    expect($service->resolveActivePage('admin/testimonies'))->toBe('ag-testimonies');
});

test('sidebar automation rules points to laravel communication hub', function () {
    $nav = app(PortalNavService::class)->cmsConfig()['nav'];
    $hub = collect($nav)->firstWhere('id', 'communication-hub');
    $automation = collect($hub['children'] ?? [])->firstWhere('id', 'ch-automation');

    expect($automation)->not->toBeNull();
    expect($automation['href'])->toBe(route('communication-hub.automation.index'));
    expect($automation['href'])->not->toContain('/portal/communication-hub/automation');
});

test('automation page resolves active nav id', function () {
    $service = app(PortalNavService::class);

    expect($service->resolveActivePage('admin/communication-hub/automation'))->toBe('ch-automation');
});

test('sidebar campaign manager points to laravel communication hub', function () {
    $nav = app(PortalNavService::class)->cmsConfig()['nav'];
    $hub = collect($nav)->firstWhere('id', 'communication-hub');
    $campaigns = collect($hub['children'] ?? [])->firstWhere('id', 'ch-campaigns');

    expect($campaigns)->not->toBeNull();
    expect($campaigns['href'])->toBe(route('communication-hub.campaigns.index'));
    expect($campaigns['href'])->not->toContain('/portal/communication-hub/campaigns');
});

test('campaigns page resolves active nav id', function () {
    $service = app(PortalNavService::class);

    expect($service->resolveActivePage('admin/communication-hub/campaigns'))->toBe('ch-campaigns');
});

test('sidebar scheduled messages points to laravel communication hub', function () {
    $nav = app(PortalNavService::class)->cmsConfig()['nav'];
    $hub = collect($nav)->firstWhere('id', 'communication-hub');
    $scheduled = collect($hub['children'] ?? [])->firstWhere('id', 'ch-scheduled');

    expect($scheduled)->not->toBeNull();
    expect($scheduled['href'])->toBe(route('communication-hub.scheduled.index'));
    expect($scheduled['href'])->not->toContain('/portal/communication-hub/scheduled');
});

test('scheduled page resolves active nav id', function () {
    $service = app(PortalNavService::class);

    expect($service->resolveActivePage('admin/communication-hub/scheduled'))->toBe('ch-scheduled');
});

test('sidebar communication queue points to laravel communication hub', function () {
    $nav = app(PortalNavService::class)->cmsConfig()['nav'];
    $hub = collect($nav)->firstWhere('id', 'communication-hub');
    $queue = collect($hub['children'] ?? [])->firstWhere('id', 'ch-queue');

    expect($queue)->not->toBeNull();
    expect($queue['href'])->toBe(route('communication-hub.queue.index'));
    expect($queue['href'])->not->toContain('/portal/communication-hub/queue');
});

test('queue page resolves active nav id', function () {
    $service = app(PortalNavService::class);

    expect($service->resolveActivePage('admin/communication-hub/queue'))->toBe('ch-queue');
});

test('sidebar recipient groups points to laravel communication hub', function () {
    $nav = app(PortalNavService::class)->cmsConfig()['nav'];
    $hub = collect($nav)->firstWhere('id', 'communication-hub');
    $recipients = collect($hub['children'] ?? [])->firstWhere('id', 'ch-recipients');

    expect($recipients)->not->toBeNull();
    expect($recipients['href'])->toBe(route('communication-hub.recipients.index'));
    expect($recipients['href'])->not->toContain('/portal/communication-hub/recipients');
});

test('recipients page resolves active nav id', function () {
    $service = app(PortalNavService::class);

    expect($service->resolveActivePage('admin/communication-hub/recipients'))->toBe('ch-recipients');
});

test('sidebar communication analytics points to laravel communication hub', function () {
    $nav = app(PortalNavService::class)->cmsConfig()['nav'];
    $hub = collect($nav)->firstWhere('id', 'communication-hub');
    $analytics = collect($hub['children'] ?? [])->firstWhere('id', 'ch-analytics');

    expect($analytics)->not->toBeNull();
    expect($analytics['href'])->toBe(route('communication-hub.analytics.index'));
    expect($analytics['href'])->not->toContain('/portal/communication-hub/analytics');
});

test('analytics page resolves active nav id', function () {
    $service = app(PortalNavService::class);

    expect($service->resolveActivePage('admin/communication-hub/analytics'))->toBe('ch-analytics');
});

test('sidebar ai message assistant points to laravel communication hub', function () {
    $nav = app(PortalNavService::class)->cmsConfig()['nav'];
    $hub = collect($nav)->firstWhere('id', 'communication-hub');
    $ai = collect($hub['children'] ?? [])->firstWhere('id', 'ch-ai');

    expect($ai)->not->toBeNull();
    expect($ai['href'])->toBe(route('communication-hub.ai-assistant.index'));
    expect($ai['href'])->not->toContain('/portal/communication-hub/ai-assistant');
});

test('ai assistant page resolves active nav id', function () {
    $service = app(PortalNavService::class);

    expect($service->resolveActivePage('admin/communication-hub/ai-assistant'))->toBe('ch-ai');
});

test('sidebar financial erp opens separate erp login launch', function () {
    $nav = app(PortalNavService::class)->cmsConfig()['nav'];
    $erp = collect($nav)->firstWhere('id', 'financial-erp');
    $children = collect($erp['children'] ?? []);

    expect($children)->toHaveCount(1);
    expect($children->firstWhere('id', 'erp-launch')['href'])->toBe(route('financial-erp.launch'));
    expect($children->firstWhere('id', 'erp-launch')['label'])->toBe('Open Financial ERP');
    expect($children->pluck('href')->implode(' '))->not->toContain('/erp/sso');
});
