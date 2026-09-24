<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Http\Controllers\PublicSite\HomeController::class, 'index'])
    ->name('public.home');

Route::get('/robots.txt', [\App\Http\Controllers\PublicSite\SeoDiscoveryController::class, 'robots'])
    ->name('public.robots');
Route::get('/ads.txt', [\App\Http\Controllers\PublicSite\SeoDiscoveryController::class, 'adsTxt'])
    ->name('public.ads-txt');
Route::get('/sitemap.xml', [\App\Http\Controllers\PublicSite\SeoDiscoveryController::class, 'sitemapIndex'])
    ->name('public.sitemap.xml');
Route::get('/sitemap-pages.xml', [\App\Http\Controllers\PublicSite\SeoDiscoveryController::class, 'sitemapPages'])
    ->name('public.sitemap.pages');
Route::get('/sitemap-blog.xml', [\App\Http\Controllers\PublicSite\SeoDiscoveryController::class, 'sitemapBlog'])
    ->name('public.sitemap.blog');
Route::get('/sitemap-sermons.xml', [\App\Http\Controllers\PublicSite\SeoDiscoveryController::class, 'sitemapSermons'])
    ->name('public.sitemap.sermons');
Route::get('/sitemap-images.xml', [\App\Http\Controllers\PublicSite\SeoDiscoveryController::class, 'sitemapImages'])
    ->name('public.sitemap.images');
Route::get('/feed', [\App\Http\Controllers\PublicSite\SeoDiscoveryController::class, 'feed'])
    ->name('public.feed');
Route::get('/sitemap', [\App\Http\Controllers\PublicSite\SeoDiscoveryController::class, 'htmlSitemap'])
    ->name('public.sitemap');

Route::get('/about', [\App\Http\Controllers\PublicSite\AboutController::class, 'show'])
    ->name('public.about');
Route::get('/activity', [\App\Http\Controllers\PublicSite\CmsPageController::class, 'show'])
    ->defaults('pageKey', 'activity')
    ->name('public.activity');
Route::get('/ministries', [\App\Http\Controllers\PublicSite\CmsPageController::class, 'show'])
    ->defaults('pageKey', 'activity')
    ->name('public.ministries');
Route::get('/event', [\App\Http\Controllers\PublicSite\CmsPageController::class, 'show'])
    ->defaults('pageKey', 'event')
    ->name('public.event');
Route::get('/events', [\App\Http\Controllers\PublicSite\CmsPageController::class, 'show'])
    ->defaults('pageKey', 'event')
    ->name('public.events');
Route::get('/api/blog/suggest', [\App\Http\Controllers\PublicSite\BlogController::class, 'suggest'])
    ->middleware('throttle:60,1')
    ->name('public.blog.suggest');
Route::get('/blog', [\App\Http\Controllers\PublicSite\BlogController::class, 'index'])
    ->name('public.blog');
Route::get('/blog/category/{category}', [\App\Http\Controllers\PublicSite\BlogController::class, 'category'])
    ->where('category', '[A-Za-z0-9\-]+')
    ->name('public.blog.category');
Route::get('/blog/tag/{tag}', [\App\Http\Controllers\PublicSite\BlogController::class, 'tag'])
    ->where('tag', '[A-Za-z0-9\-]+')
    ->name('public.blog.tag');
Route::get('/blog/{slug}', [\App\Http\Controllers\PublicSite\BlogController::class, 'show'])
    ->where('slug', '[A-Za-z0-9\-]+')
    ->name('public.blog.show');
Route::get('/contact', [\App\Http\Controllers\PublicSite\CmsPageController::class, 'show'])
    ->defaults('pageKey', 'contact')
    ->name('public.contact');
Route::post('/api/contact', [\App\Http\Controllers\PublicSite\PublicContactController::class, 'submit'])
    ->middleware('throttle:10,1')
    ->name('public.contact.submit');
Route::post('/api/newsletter/subscribe', [\App\Http\Controllers\PublicSite\NewsletterSubscribeController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('public.newsletter.subscribe');
Route::post('/api/testimony/submit', [\App\Http\Controllers\PublicSite\PublicTestimonyController::class, 'submit'])
    ->middleware('throttle:10,1')
    ->name('public.testimony.submit');
Route::post('/api/submit-site-testimony.php', [\App\Http\Controllers\PublicSite\PublicTestimonyController::class, 'submit'])
    ->middleware('throttle:10,1')
    ->name('public.testimony.submit.legacy');
Route::get('/donate', [\App\Http\Controllers\PublicSite\DonateController::class, 'show'])
    ->name('public.donate');
Route::get('/privacy', [\App\Http\Controllers\PublicSite\CmsPageController::class, 'show'])
    ->defaults('pageKey', 'privacy')
    ->name('public.privacy');
Route::get('/terms', [\App\Http\Controllers\PublicSite\CmsPageController::class, 'show'])
    ->defaults('pageKey', 'terms')
    ->name('public.terms');
Route::get('/cookie-policy', [\App\Http\Controllers\PublicSite\CmsPageController::class, 'show'])
    ->defaults('pageKey', 'cookie-policy')
    ->name('public.cookie-policy');
Route::get('/cookies', fn () => redirect()->route('public.cookie-policy'));
Route::get('/leadership', [\App\Http\Controllers\PublicSite\LeadershipController::class, 'show'])
    ->name('public.leadership');
Route::get('/statement-of-faith', [\App\Http\Controllers\PublicSite\CmsPageController::class, 'show'])
    ->defaults('pageKey', 'statement-of-faith')
    ->name('public.statement-of-faith');
Route::get('/mission-vision', [\App\Http\Controllers\PublicSite\CmsPageController::class, 'show'])
    ->defaults('pageKey', 'mission-vision')
    ->name('public.mission-vision');
Route::get('/editorial-policy', [\App\Http\Controllers\PublicSite\CmsPageController::class, 'show'])
    ->defaults('pageKey', 'editorial-policy')
    ->name('public.editorial-policy');
Route::get('/accessibility', [\App\Http\Controllers\PublicSite\CmsPageController::class, 'show'])
    ->defaults('pageKey', 'accessibility')
    ->name('public.accessibility');
Route::get('/disclaimer', [\App\Http\Controllers\PublicSite\CmsPageController::class, 'show'])
    ->defaults('pageKey', 'disclaimer')
    ->name('public.disclaimer');
Route::get('/faq', [\App\Http\Controllers\PublicSite\CmsPageController::class, 'show'])
    ->defaults('pageKey', 'faq')
    ->name('public.faq');
Route::get('/sermons', [\App\Http\Controllers\PublicSite\CmsPageController::class, 'show'])
    ->defaults('pageKey', 'sermons')
    ->name('public.sermons');
Route::get('/sermons/{slug}', [\App\Http\Controllers\PublicSite\PublicSermonController::class, 'show'])
    ->where('slug', '[A-Za-z0-9\-_]+')
    ->name('public.sermons.show');
Route::get('/sermon', fn () => redirect()->route('public.sermons'));
Route::get('/sermon-library/{any?}', fn () => redirect()->route('public.sermons'))
    ->where('any', '.*');
Route::get('/join', [\App\Http\Controllers\PublicSite\LegacyBridgeController::class, 'join'])
    ->name('public.join');
Route::get('/member-portal/login', [\App\Http\Controllers\PublicSite\LegacyBridgeController::class, 'memberPortalLogin'])
    ->name('public.member-portal.login');
Route::get('/member-portal', [\App\Http\Controllers\PublicSite\LegacyBridgeController::class, 'memberPortalHome'])
    ->name('public.member-portal');
// Do not use permanentRedirect('/member-portal/', …): Laravel collapses the URI and
// overwrites the GET route above with a self-referential 301 (ERR_TOO_MANY_REDIRECTS).

Route::match(['get', 'post'], '/api/member-portal-{action}.php', [\App\Http\Controllers\PublicSite\MemberPortalApiProxyController::class, 'memberPortal'])
    ->where('action', 'login|logout|dashboard|check-setup|set-password|change-password|generate-statement|download-statement|download-receipt')
    ->name('public.member-portal.api');
Route::match(['get', 'post'], '/api/member-self-register.php', [\App\Http\Controllers\PublicSite\MemberPortalApiProxyController::class, 'memberSelfRegister'])
    ->name('public.member-portal.join-api');

Route::get('/register/{slug}', [\App\Http\Controllers\RegistrationPortals\PublicRegistrationPortalController::class, 'show'])
    ->where('slug', '[A-Za-z0-9\-]+')
    ->name('public.registration-portal');
Route::post('/api/submit-portal-registration.php', \App\Http\Controllers\RegistrationPortals\PublicRegistrationSubmitController::class)
    ->middleware('throttle:20,1')
    ->name('public.registration-portal.submit');
Route::get('/api/portal-qr.php', \App\Http\Controllers\RegistrationPortals\PortalQrController::class)
    ->middleware('throttle:60,1')
    ->name('public.registration-portal.qr');

Route::permanentRedirect('/login', '/portal/login');
Route::permanentRedirect('/admin/login', '/portal/login');
Route::permanentRedirect('/portal/login.php', '/portal/login');
Route::permanentRedirect('/dashboard', '/admin/dashboard');
Route::permanentRedirect('/logout', '/admin/logout');

// Legacy Financial ERP (XAMPP) proxied onto Laravel origin — /erp/login, /erp/dashboard, assets, handlers.
Route::match(['get', 'post', 'put', 'patch', 'delete'], '/erp/{path?}', \App\Http\Controllers\FinancialErp\ErpLegacyProxyController::class)
    ->where('path', '.*')
    ->name('erp.legacy');
