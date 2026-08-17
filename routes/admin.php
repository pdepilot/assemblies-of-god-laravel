<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    require __DIR__.'/auth.php';

    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
        ->middleware(['auth:admin', 'admin.idle', 'admin.rbac'])
        ->name('dashboard');

    Route::middleware(['auth:admin', 'admin.idle', 'admin.rbac'])->group(function () {
    Route::match(['get', 'post'], '/handlers/dashboard-handler', [\App\Http\Controllers\Portal\DashboardHandlerController::class, 'handle'])
        ->name('handlers.dashboard');

    Route::get('/settings', [\App\Http\Controllers\Settings\SettingsController::class, 'index'])
        ->name('settings.index');
    Route::post('/settings/group', [\App\Http\Controllers\Settings\SettingsController::class, 'updateGroup'])
        ->name('settings.group.update');
    Route::post('/settings/preferences', [\App\Http\Controllers\Settings\SettingsController::class, 'updatePreferences'])
        ->name('settings.preferences.update');

    Route::get('/settings/rbac/roles/create', [\App\Http\Controllers\Settings\RbacRolesController::class, 'create'])
        ->name('settings.rbac.roles.create');
    Route::post('/settings/rbac/roles', [\App\Http\Controllers\Settings\RbacRolesController::class, 'store'])
        ->name('settings.rbac.roles.store');
    Route::get('/settings/rbac/roles/{role}/edit', [\App\Http\Controllers\Settings\RbacRolesController::class, 'edit'])
        ->whereNumber('role')
        ->name('settings.rbac.roles.edit');
    Route::put('/settings/rbac/roles/{role}', [\App\Http\Controllers\Settings\RbacRolesController::class, 'update'])
        ->whereNumber('role')
        ->name('settings.rbac.roles.update');
    Route::delete('/settings/rbac/roles/{role}', [\App\Http\Controllers\Settings\RbacRolesController::class, 'destroy'])
        ->whereNumber('role')
        ->name('settings.rbac.roles.destroy');
    Route::post('/settings/rbac/assignments', [\App\Http\Controllers\Settings\RbacRolesController::class, 'syncAdminRoles'])
        ->name('settings.rbac.assignments.sync');

    Route::get('/settings/admins/create', [\App\Http\Controllers\Settings\AdminsController::class, 'create'])
        ->name('settings.admins.create');
    Route::post('/settings/admins', [\App\Http\Controllers\Settings\AdminsController::class, 'store'])
        ->name('settings.admins.store');
    Route::get('/settings/admins/{admin}/edit', [\App\Http\Controllers\Settings\AdminsController::class, 'edit'])
        ->whereNumber('admin')
        ->name('settings.admins.edit');
    Route::get('/settings/admins/{admin}/permissions', [\App\Http\Controllers\Settings\AdminsController::class, 'permissions'])
        ->whereNumber('admin')
        ->name('settings.admins.permissions');
    Route::put('/settings/admins/{admin}', [\App\Http\Controllers\Settings\AdminsController::class, 'update'])
        ->whereNumber('admin')
        ->name('settings.admins.update');
    Route::post('/settings/admins/{admin}/status', [\App\Http\Controllers\Settings\AdminsController::class, 'updateStatus'])
        ->whereNumber('admin')
        ->name('settings.admins.status');
    Route::delete('/settings/admins/{admin}', [\App\Http\Controllers\Settings\AdminsController::class, 'destroy'])
        ->whereNumber('admin')
        ->name('settings.admins.destroy');

    Route::get('/security/bans', [\App\Http\Controllers\Security\DeviceBansController::class, 'index'])
        ->name('security.bans.index');
    Route::get('/security/bans/{ban}', [\App\Http\Controllers\Security\DeviceBansController::class, 'show'])
        ->whereNumber('ban')
        ->name('security.bans.show');
    Route::delete('/security/bans/{ban}', [\App\Http\Controllers\Security\DeviceBansController::class, 'destroy'])
        ->whereNumber('ban')
        ->name('security.bans.destroy');

    Route::get('/security/activity-logs', [\App\Http\Controllers\Security\ActivityLogsController::class, 'index'])
        ->name('security.activity-logs.index');
    Route::get('/security/activity-logs/export', [\App\Http\Controllers\Security\ActivityLogsController::class, 'export'])
        ->name('security.activity-logs.export');

    Route::redirect('/sunday-school', '/admin/sunday-school/analytics');
    Route::get('/sunday-school/classes', [\App\Http\Controllers\SundaySchool\ClassesController::class, 'index'])
        ->name('ss.classes.index');
    Route::get('/sunday-school/classes/create', [\App\Http\Controllers\SundaySchool\ClassesController::class, 'create'])
        ->name('ss.classes.create');
    Route::post('/sunday-school/classes/seed-defaults', [\App\Http\Controllers\SundaySchool\ClassesController::class, 'seedDefaults'])
        ->name('ss.classes.seed');
    Route::post('/sunday-school/classes', [\App\Http\Controllers\SundaySchool\ClassesController::class, 'store'])
        ->name('ss.classes.store');
    Route::get('/sunday-school/classes/{class}/edit', [\App\Http\Controllers\SundaySchool\ClassesController::class, 'edit'])
        ->name('ss.classes.edit');
    Route::put('/sunday-school/classes/{class}', [\App\Http\Controllers\SundaySchool\ClassesController::class, 'update'])
        ->name('ss.classes.update');
    Route::post('/sunday-school/classes/{class}/archive', [\App\Http\Controllers\SundaySchool\ClassesController::class, 'archive'])
        ->name('ss.classes.archive');
    Route::delete('/sunday-school/classes/{class}', [\App\Http\Controllers\SundaySchool\ClassesController::class, 'destroy'])
        ->name('ss.classes.destroy');

    Route::get('/sunday-school/students', [\App\Http\Controllers\SundaySchool\StudentsController::class, 'index'])
        ->name('ss.students.index');
    Route::get('/sunday-school/students/create', [\App\Http\Controllers\SundaySchool\StudentsController::class, 'create'])
        ->name('ss.students.create');
    Route::post('/sunday-school/students', [\App\Http\Controllers\SundaySchool\StudentsController::class, 'store'])
        ->name('ss.students.store');
    Route::get('/sunday-school/students/{student}/edit', [\App\Http\Controllers\SundaySchool\StudentsController::class, 'edit'])
        ->name('ss.students.edit');
    Route::put('/sunday-school/students/{student}', [\App\Http\Controllers\SundaySchool\StudentsController::class, 'update'])
        ->name('ss.students.update');
    Route::post('/sunday-school/students/{student}/transfer', [\App\Http\Controllers\SundaySchool\StudentsController::class, 'transfer'])
        ->name('ss.students.transfer');
    Route::post('/sunday-school/students/{student}/archive', [\App\Http\Controllers\SundaySchool\StudentsController::class, 'archive'])
        ->name('ss.students.archive');
    Route::delete('/sunday-school/students/{student}', [\App\Http\Controllers\SundaySchool\StudentsController::class, 'destroy'])
        ->name('ss.students.destroy');

    Route::get('/sunday-school/teachers', [\App\Http\Controllers\SundaySchool\TeachersController::class, 'index'])
        ->name('ss.teachers.index');
    Route::get('/sunday-school/teachers/create', [\App\Http\Controllers\SundaySchool\TeachersController::class, 'create'])
        ->name('ss.teachers.create');
    Route::post('/sunday-school/teachers', [\App\Http\Controllers\SundaySchool\TeachersController::class, 'store'])
        ->name('ss.teachers.store');
    Route::get('/sunday-school/teachers/{teacher}/edit', [\App\Http\Controllers\SundaySchool\TeachersController::class, 'edit'])
        ->name('ss.teachers.edit');
    Route::put('/sunday-school/teachers/{teacher}', [\App\Http\Controllers\SundaySchool\TeachersController::class, 'update'])
        ->name('ss.teachers.update');
    Route::post('/sunday-school/teachers/{teacher}/suspend', [\App\Http\Controllers\SundaySchool\TeachersController::class, 'suspend'])
        ->name('ss.teachers.suspend');
    Route::post('/sunday-school/teachers/{teacher}/activate', [\App\Http\Controllers\SundaySchool\TeachersController::class, 'activate'])
        ->name('ss.teachers.activate');
    Route::delete('/sunday-school/teachers/{teacher}', [\App\Http\Controllers\SundaySchool\TeachersController::class, 'destroy'])
        ->name('ss.teachers.destroy');

    Route::get('/sunday-school/attendance', [\App\Http\Controllers\SundaySchool\AttendanceController::class, 'index'])
        ->name('ss.attendance.index');
    Route::post('/sunday-school/attendance', [\App\Http\Controllers\SundaySchool\AttendanceController::class, 'store'])
        ->name('ss.attendance.store');
    Route::post('/sunday-school/attendance/void', [\App\Http\Controllers\SundaySchool\AttendanceController::class, 'void'])
        ->name('ss.attendance.void');
    Route::get('/sunday-school/attendance/removals', [\App\Http\Controllers\SundaySchool\AttendanceController::class, 'removals'])
        ->name('ss.attendance.removals');

    Route::get('/sunday-school/offerings', [\App\Http\Controllers\SundaySchool\OfferingsController::class, 'index'])
        ->name('ss.offerings.index');
    Route::post('/sunday-school/offerings', [\App\Http\Controllers\SundaySchool\OfferingsController::class, 'store'])
        ->name('ss.offerings.store');

    Route::get('/sunday-school/lessons', [\App\Http\Controllers\SundaySchool\LessonsController::class, 'index'])
        ->name('ss.lessons.index');
    Route::get('/sunday-school/lessons/create', [\App\Http\Controllers\SundaySchool\LessonsController::class, 'create'])
        ->name('ss.lessons.create');
    Route::post('/sunday-school/lessons', [\App\Http\Controllers\SundaySchool\LessonsController::class, 'store'])
        ->name('ss.lessons.store');
    Route::get('/sunday-school/lessons/{lesson}', [\App\Http\Controllers\SundaySchool\LessonsController::class, 'show'])
        ->name('ss.lessons.show');
    Route::get('/sunday-school/lessons/{lesson}/edit', [\App\Http\Controllers\SundaySchool\LessonsController::class, 'edit'])
        ->name('ss.lessons.edit');
    Route::put('/sunday-school/lessons/{lesson}', [\App\Http\Controllers\SundaySchool\LessonsController::class, 'update'])
        ->name('ss.lessons.update');
    Route::delete('/sunday-school/lessons/{lesson}', [\App\Http\Controllers\SundaySchool\LessonsController::class, 'destroy'])
        ->name('ss.lessons.destroy');

    Route::get('/sunday-school/reports', [\App\Http\Controllers\SundaySchool\ReportsController::class, 'index'])
        ->name('ss.reports.index');
    Route::get('/sunday-school/reports/export', [\App\Http\Controllers\SundaySchool\ReportsController::class, 'export'])
        ->name('ss.reports.export');

    Route::get('/sunday-school/analytics', [\App\Http\Controllers\SundaySchool\AnalyticsController::class, 'index'])
        ->name('ss.analytics.index');

    Route::get('/sunday-school/visitors', [\App\Http\Controllers\SundaySchool\VisitorsController::class, 'index'])
        ->name('ss.visitors.index');
    Route::post('/sunday-school/visitors', [\App\Http\Controllers\SundaySchool\VisitorsController::class, 'store'])
        ->name('ss.visitors.store');
    Route::put('/sunday-school/visitors/{visitor}', [\App\Http\Controllers\SundaySchool\VisitorsController::class, 'update'])
        ->name('ss.visitors.update');

    Route::get('/sunday-school/promotions', [\App\Http\Controllers\SundaySchool\PromotionsController::class, 'index'])
        ->name('ss.promotions.index');
    Route::post('/sunday-school/promotions/bulk', [\App\Http\Controllers\SundaySchool\PromotionsController::class, 'bulkPromote'])
        ->name('ss.promotions.bulk');
    Route::post('/sunday-school/promotions/evaluate', [\App\Http\Controllers\SundaySchool\PromotionsController::class, 'evaluateYear'])
        ->name('ss.promotions.evaluate');
    Route::post('/sunday-school/promotions/students/{student}/promote', [\App\Http\Controllers\SundaySchool\PromotionsController::class, 'promoteStudent'])
        ->name('ss.promotions.promote');
    Route::post('/sunday-school/promotions/students/{student}/graduate', [\App\Http\Controllers\SundaySchool\PromotionsController::class, 'graduateStudent'])
        ->name('ss.promotions.graduate');

    Route::get('/sunday-school/awards', [\App\Http\Controllers\SundaySchool\AwardsController::class, 'index'])
        ->name('ss.awards.index');
    Route::post('/sunday-school/awards/generate', [\App\Http\Controllers\SundaySchool\AwardsController::class, 'generate'])
        ->name('ss.awards.generate');
    Route::post('/sunday-school/awards/{award}/approve', [\App\Http\Controllers\SundaySchool\AwardsController::class, 'approve'])
        ->name('ss.awards.approve');
    Route::post('/sunday-school/awards/{award}/reject', [\App\Http\Controllers\SundaySchool\AwardsController::class, 'reject'])
        ->name('ss.awards.reject');
    Route::post('/sunday-school/awards/{award}/publish', [\App\Http\Controllers\SundaySchool\AwardsController::class, 'publish'])
        ->name('ss.awards.publish');

    Route::get('/sunday-school/certificates', [\App\Http\Controllers\SundaySchool\CertificatesController::class, 'index'])
        ->name('ss.certificates.index');
    Route::post('/sunday-school/certificates/awards/{award}/generate', [\App\Http\Controllers\SundaySchool\CertificatesController::class, 'generateFromAward'])
        ->name('ss.certificates.generate-from-award');
    Route::post('/sunday-school/certificates/{certificate}/regenerate', [\App\Http\Controllers\SundaySchool\CertificatesController::class, 'regenerate'])
        ->name('ss.certificates.regenerate');
    Route::delete('/sunday-school/certificates/{certificate}', [\App\Http\Controllers\SundaySchool\CertificatesController::class, 'destroy'])
        ->name('ss.certificates.destroy');
    Route::get('/sunday-school/certificates/{certificate}/download', [\App\Http\Controllers\SundaySchool\CertificatesController::class, 'download'])
        ->name('ss.certificates.download');
    Route::get('/sunday-school/certificates/{certificate}/preview', [\App\Http\Controllers\SundaySchool\CertificatesController::class, 'preview'])
        ->name('ss.certificates.preview');

    Route::get('/sunday-school/notifications', [\App\Http\Controllers\SundaySchool\NotificationsController::class, 'index'])
        ->name('ss.notifications.index');
    Route::post('/sunday-school/notifications', [\App\Http\Controllers\SundaySchool\NotificationsController::class, 'store'])
        ->name('ss.notifications.store');
    Route::post('/sunday-school/notifications/send', [\App\Http\Controllers\SundaySchool\NotificationsController::class, 'sendPending'])
        ->name('ss.notifications.send');

    Route::get('/sunday-school/superintendent/offerings', [\App\Http\Controllers\SundaySchool\SuperintendentOfferingsController::class, 'index'])
        ->name('ss.superintendent.offerings');

    Route::get('/members', [\App\Http\Controllers\Members\MembersController::class, 'index'])
        ->name('members.index');
    Route::get('/members/create', [\App\Http\Controllers\Members\MembersController::class, 'create'])
        ->name('members.create');
    Route::post('/members', [\App\Http\Controllers\Members\MembersController::class, 'store'])
        ->name('members.store');
    Route::get('/members/{member}', [\App\Http\Controllers\Members\MembersController::class, 'show'])
        ->name('members.show');
    Route::get('/members/{member}/edit', [\App\Http\Controllers\Members\MembersController::class, 'edit'])
        ->name('members.edit');
    Route::put('/members/{member}', [\App\Http\Controllers\Members\MembersController::class, 'update'])
        ->name('members.update');

    Route::get('/visitors', [\App\Http\Controllers\Visitors\VisitorsController::class, 'index'])
        ->name('visitors.index');
    Route::get('/visitors/create', [\App\Http\Controllers\Visitors\VisitorsController::class, 'create'])
        ->name('visitors.create');
    Route::post('/visitors', [\App\Http\Controllers\Visitors\VisitorsController::class, 'store'])
        ->name('visitors.store');
    Route::get('/visitors/{visitor}', [\App\Http\Controllers\Visitors\VisitorsController::class, 'show'])
        ->name('visitors.show');
    Route::get('/visitors/{visitor}/edit', [\App\Http\Controllers\Visitors\VisitorsController::class, 'edit'])
        ->name('visitors.edit');
    Route::put('/visitors/{visitor}', [\App\Http\Controllers\Visitors\VisitorsController::class, 'update'])
        ->name('visitors.update');
    Route::post('/visitors/{visitor}/record-return', [\App\Http\Controllers\Visitors\VisitorsController::class, 'recordReturn'])
        ->name('visitors.record-return');
    Route::post('/visitors/{visitor}/promote', [\App\Http\Controllers\Visitors\VisitorsController::class, 'promote'])
        ->name('visitors.promote');
    Route::delete('/visitors/{visitor}', [\App\Http\Controllers\Visitors\VisitorsController::class, 'destroy'])
        ->name('visitors.destroy');

    Route::get('/ministries/settings', [\App\Http\Controllers\Ministries\MinistrySettingsController::class, 'index'])
        ->name('ministries.settings.index');
    Route::put('/ministries/settings/{setting}', [\App\Http\Controllers\Ministries\MinistrySettingsController::class, 'update'])
        ->name('ministries.settings.update');
    Route::get('/ministries/age-transfers', [\App\Http\Controllers\Ministries\MinistryAgeTransferController::class, 'index'])
        ->name('ministries.age-transfers.index');
    Route::post('/ministries/age-transfers/run', [\App\Http\Controllers\Ministries\MinistryAgeTransferController::class, 'run'])
        ->name('ministries.age-transfers.run');

    Route::get('/ministries/{ministryKey}', [\App\Http\Controllers\Ministries\MinistryModuleController::class, 'index'])
        ->name('ministries.module.index');
    Route::post('/ministries/{ministryKey}/register', [\App\Http\Controllers\Ministries\MinistryModuleController::class, 'register'])
        ->name('ministries.module.register');
    Route::post('/ministries/{ministryKey}/import-member', [\App\Http\Controllers\Ministries\MinistryModuleController::class, 'importMember'])
        ->name('ministries.module.import-member');
    Route::post('/ministries/{ministryKey}/people/{person}/archive', [\App\Http\Controllers\Ministries\MinistryModuleController::class, 'archive'])
        ->name('ministries.module.archive');
    Route::post('/ministries/{ministryKey}/attendance', [\App\Http\Controllers\Ministries\MinistryModuleController::class, 'recordAttendance'])
        ->name('ministries.module.attendance');

    Route::get('/events', [\App\Http\Controllers\Events\EventsController::class, 'index'])
        ->name('events.index');
    Route::get('/events/create', [\App\Http\Controllers\Events\EventsController::class, 'create'])
        ->name('events.create');
    Route::post('/events', [\App\Http\Controllers\Events\EventsController::class, 'store'])
        ->name('events.store');
    Route::get('/events/{event}', [\App\Http\Controllers\Events\EventsController::class, 'show'])
        ->name('events.show');
    Route::get('/events/{event}/edit', [\App\Http\Controllers\Events\EventsController::class, 'edit'])
        ->name('events.edit');
    Route::put('/events/{event}', [\App\Http\Controllers\Events\EventsController::class, 'update'])
        ->name('events.update');
    Route::post('/events/{event}/set-published', [\App\Http\Controllers\Events\EventsController::class, 'setPublished'])
        ->name('events.set-published');
    Route::delete('/events/{event}', [\App\Http\Controllers\Events\EventsController::class, 'destroy'])
        ->name('events.destroy');

    Route::get('/registration-portals', [\App\Http\Controllers\RegistrationPortals\RegistrationPortalsController::class, 'index'])
        ->name('registration-portals.index');
    Route::get('/registration-portals/create', [\App\Http\Controllers\RegistrationPortals\RegistrationPortalsController::class, 'create'])
        ->name('registration-portals.create');
    Route::post('/registration-portals', [\App\Http\Controllers\RegistrationPortals\RegistrationPortalsController::class, 'store'])
        ->name('registration-portals.store');
    Route::get('/registration-portals/{registrationPortal}', [\App\Http\Controllers\RegistrationPortals\RegistrationPortalsController::class, 'show'])
        ->name('registration-portals.show');
    Route::get('/registration-portals/{registrationPortal}/edit', [\App\Http\Controllers\RegistrationPortals\RegistrationPortalsController::class, 'edit'])
        ->name('registration-portals.edit');
    Route::put('/registration-portals/{registrationPortal}', [\App\Http\Controllers\RegistrationPortals\RegistrationPortalsController::class, 'update'])
        ->name('registration-portals.update');
    Route::post('/registration-portals/{registrationPortal}/status', [\App\Http\Controllers\RegistrationPortals\RegistrationPortalsController::class, 'updateStatus'])
        ->name('registration-portals.update-status');
    Route::delete('/registration-portals/{registrationPortal}', [\App\Http\Controllers\RegistrationPortals\RegistrationPortalsController::class, 'destroy'])
        ->name('registration-portals.destroy');
    Route::get('/registration-portals/{registrationPortal}/registrants', [\App\Http\Controllers\RegistrationPortals\RegistrantsController::class, 'index'])
        ->name('registration-portals.registrants.index');
    Route::get('/registration-portals/{registrationPortal}/registrants/{registrant}', [\App\Http\Controllers\RegistrationPortals\RegistrantsController::class, 'show'])
        ->whereNumber('registrant')
        ->name('registration-portals.registrants.show');
    Route::post('/registration-portals/{registrationPortal}/registrants/{registrant}/status', [\App\Http\Controllers\RegistrationPortals\RegistrantsController::class, 'updateStatus'])
        ->name('registration-portals.registrants.update-status');
    Route::delete('/registration-portals/{registrationPortal}/registrants/{registrant}', [\App\Http\Controllers\RegistrationPortals\RegistrantsController::class, 'destroy'])
        ->whereNumber('registrant')
        ->name('registration-portals.registrants.destroy');
    Route::get('/registration-portals/{registrationPortal}/qr', \App\Http\Controllers\RegistrationPortals\PortalQrPageController::class)
        ->name('registration-portals.qr');

    Route::get('/donations', [\App\Http\Controllers\Donations\DonationsController::class, 'index'])
        ->name('donations.index');
    Route::get('/donations/create', [\App\Http\Controllers\Donations\DonationsController::class, 'create'])
        ->name('donations.create');
    Route::post('/donations', [\App\Http\Controllers\Donations\DonationsController::class, 'store'])
        ->name('donations.store');

    Route::get('/commitments', [\App\Http\Controllers\Commitments\CommitmentsController::class, 'index'])
        ->name('commitments.index');
    Route::get('/commitments/create', [\App\Http\Controllers\Commitments\CommitmentsController::class, 'create'])
        ->name('commitments.create');
    Route::post('/commitments', [\App\Http\Controllers\Commitments\CommitmentsController::class, 'store'])
        ->name('commitments.store');
    Route::get('/commitments/{commitment}', [\App\Http\Controllers\Commitments\CommitmentsController::class, 'show'])
        ->name('commitments.show');
    Route::post('/commitments/{commitment}/givers', [\App\Http\Controllers\Commitments\CommitmentsController::class, 'storeGiver'])
        ->name('commitments.givers.store');
    Route::post('/commitments/givers/{giver}/payment', [\App\Http\Controllers\Commitments\CommitmentsController::class, 'recordPayment'])
        ->name('commitments.givers.payment');

    Route::get('/pledges', [\App\Http\Controllers\Pledges\PledgesController::class, 'index'])
        ->name('pledges.index');
    Route::get('/pledges/create', [\App\Http\Controllers\Pledges\PledgesController::class, 'create'])
        ->name('pledges.create');
    Route::post('/pledges', [\App\Http\Controllers\Pledges\PledgesController::class, 'store'])
        ->name('pledges.store');
    Route::get('/pledges/{pledge}', [\App\Http\Controllers\Pledges\PledgesController::class, 'show'])
        ->name('pledges.show');
    Route::post('/pledges/{pledge}/payment', [\App\Http\Controllers\Pledges\PledgesController::class, 'recordPayment'])
        ->name('pledges.payment');

    Route::get('/financial-erp', [\App\Http\Controllers\FinancialErp\DashboardController::class, 'index'])
        ->name('financial-erp.dashboard');
    Route::get('/financial-erp/launch', \App\Http\Controllers\FinancialErp\LaunchController::class)
        ->name('financial-erp.launch');
    Route::get('/financial-erp/accounts', [\App\Http\Controllers\FinancialErp\AccountsController::class, 'index'])
        ->name('financial-erp.accounts.index');
    Route::get('/financial-erp/accounts/create', [\App\Http\Controllers\FinancialErp\AccountsController::class, 'create'])
        ->name('financial-erp.accounts.create');
    Route::post('/financial-erp/accounts', [\App\Http\Controllers\FinancialErp\AccountsController::class, 'store'])
        ->name('financial-erp.accounts.store');
    Route::get('/financial-erp/accounts/{account}/edit', [\App\Http\Controllers\FinancialErp\AccountsController::class, 'edit'])
        ->name('financial-erp.accounts.edit');
    Route::put('/financial-erp/accounts/{account}', [\App\Http\Controllers\FinancialErp\AccountsController::class, 'update'])
        ->name('financial-erp.accounts.update');
    Route::delete('/financial-erp/accounts/{account}', [\App\Http\Controllers\FinancialErp\AccountsController::class, 'destroy'])
        ->name('financial-erp.accounts.destroy');

    Route::get('/financial-erp/journals', [\App\Http\Controllers\FinancialErp\JournalsController::class, 'index'])
        ->name('financial-erp.journals.index');
    Route::get('/financial-erp/journals/create', [\App\Http\Controllers\FinancialErp\JournalsController::class, 'create'])
        ->name('financial-erp.journals.create');
    Route::post('/financial-erp/journals', [\App\Http\Controllers\FinancialErp\JournalsController::class, 'store'])
        ->name('financial-erp.journals.store');
    Route::get('/financial-erp/journals/{journal}', [\App\Http\Controllers\FinancialErp\JournalsController::class, 'show'])
        ->name('financial-erp.journals.show');
    Route::post('/financial-erp/journals/{journal}/post', [\App\Http\Controllers\FinancialErp\JournalsController::class, 'post'])
        ->name('financial-erp.journals.post');

    Route::get('/financial-erp/income', [\App\Http\Controllers\FinancialErp\IncomeController::class, 'index'])
        ->name('financial-erp.income.index');
    Route::get('/financial-erp/income/create', [\App\Http\Controllers\FinancialErp\IncomeController::class, 'create'])
        ->name('financial-erp.income.create');
    Route::post('/financial-erp/income', [\App\Http\Controllers\FinancialErp\IncomeController::class, 'store'])
        ->name('financial-erp.income.store');
    Route::get('/financial-erp/income/{income}', [\App\Http\Controllers\FinancialErp\IncomeController::class, 'show'])
        ->name('financial-erp.income.show');

    Route::get('/financial-erp/expenses', [\App\Http\Controllers\FinancialErp\ExpensesController::class, 'index'])
        ->name('financial-erp.expenses.index');
    Route::get('/financial-erp/expenses/create', [\App\Http\Controllers\FinancialErp\ExpensesController::class, 'create'])
        ->name('financial-erp.expenses.create');
    Route::post('/financial-erp/expenses', [\App\Http\Controllers\FinancialErp\ExpensesController::class, 'store'])
        ->name('financial-erp.expenses.store');
    Route::get('/financial-erp/expenses/{expense}', [\App\Http\Controllers\FinancialErp\ExpensesController::class, 'show'])
        ->name('financial-erp.expenses.show');

    Route::get('/financial-erp/vendors', [\App\Http\Controllers\FinancialErp\VendorsController::class, 'index'])
        ->name('financial-erp.vendors.index');
    Route::get('/financial-erp/vendors/create', [\App\Http\Controllers\FinancialErp\VendorsController::class, 'create'])
        ->name('financial-erp.vendors.create');
    Route::post('/financial-erp/vendors', [\App\Http\Controllers\FinancialErp\VendorsController::class, 'store'])
        ->name('financial-erp.vendors.store');
    Route::get('/financial-erp/vendors/{vendor}/edit', [\App\Http\Controllers\FinancialErp\VendorsController::class, 'edit'])
        ->name('financial-erp.vendors.edit');
    Route::put('/financial-erp/vendors/{vendor}', [\App\Http\Controllers\FinancialErp\VendorsController::class, 'update'])
        ->name('financial-erp.vendors.update');

    Route::get('/financial-erp/projects', [\App\Http\Controllers\FinancialErp\ProjectsController::class, 'index'])
        ->name('financial-erp.projects.index');
    Route::get('/financial-erp/projects/create', [\App\Http\Controllers\FinancialErp\ProjectsController::class, 'create'])
        ->name('financial-erp.projects.create');
    Route::post('/financial-erp/projects', [\App\Http\Controllers\FinancialErp\ProjectsController::class, 'store'])
        ->name('financial-erp.projects.store');
    Route::get('/financial-erp/projects/{project}', [\App\Http\Controllers\FinancialErp\ProjectsController::class, 'show'])
        ->name('financial-erp.projects.show');

    Route::get('/financial-erp/audit', [\App\Http\Controllers\FinancialErp\AuditLogController::class, 'index'])
        ->name('financial-erp.audit.index');

    Route::get('/communication-hub', [\App\Http\Controllers\CommunicationHub\DashboardController::class, 'index'])
        ->name('communication-hub.dashboard');
    Route::get('/communication-hub/birthdays', [\App\Http\Controllers\CommunicationHub\BirthdayController::class, 'index'])
        ->name('communication-hub.birthdays.index');
    Route::post('/communication-hub/birthdays/auto-email', [\App\Http\Controllers\CommunicationHub\BirthdayController::class, 'updateAutoEmail'])
        ->name('communication-hub.birthdays.auto-email');
    Route::post('/communication-hub/birthdays/auto-sms', [\App\Http\Controllers\CommunicationHub\BirthdayController::class, 'updateAutoSms'])
        ->name('communication-hub.birthdays.auto-sms');

    Route::get('/communication-hub/email-center', [\App\Http\Controllers\CommunicationHub\EmailCenterController::class, 'index'])
        ->name('communication-hub.email-center.index');
    Route::post('/communication-hub/email-center', [\App\Http\Controllers\CommunicationHub\EmailCenterController::class, 'compose'])
        ->name('communication-hub.email-center.compose');
    Route::get('/communication-hub/sms-center', [\App\Http\Controllers\CommunicationHub\SmsCenterController::class, 'index'])
        ->name('communication-hub.sms-center.index');
    Route::post('/communication-hub/sms-center', [\App\Http\Controllers\CommunicationHub\SmsCenterController::class, 'compose'])
        ->name('communication-hub.sms-center.compose');
    Route::get('/communication-hub/templates', [\App\Http\Controllers\CommunicationHub\TemplatesController::class, 'index'])
        ->name('communication-hub.templates.index');
    Route::get('/communication-hub/templates/create', [\App\Http\Controllers\CommunicationHub\TemplatesController::class, 'create'])
        ->name('communication-hub.templates.create');
    Route::post('/communication-hub/templates', [\App\Http\Controllers\CommunicationHub\TemplatesController::class, 'store'])
        ->name('communication-hub.templates.store');
    Route::get('/communication-hub/templates/{template}', [\App\Http\Controllers\CommunicationHub\TemplatesController::class, 'show'])
        ->name('communication-hub.templates.show');
    Route::get('/communication-hub/templates/{template}/edit', [\App\Http\Controllers\CommunicationHub\TemplatesController::class, 'edit'])
        ->name('communication-hub.templates.edit');
    Route::put('/communication-hub/templates/{template}', [\App\Http\Controllers\CommunicationHub\TemplatesController::class, 'update'])
        ->name('communication-hub.templates.update');

    Route::get('/communication-hub/automation', [\App\Http\Controllers\CommunicationHub\AutomationController::class, 'index'])
        ->name('communication-hub.automation.index');
    Route::get('/communication-hub/automation/create', [\App\Http\Controllers\CommunicationHub\AutomationController::class, 'create'])
        ->name('communication-hub.automation.create');
    Route::post('/communication-hub/automation', [\App\Http\Controllers\CommunicationHub\AutomationController::class, 'store'])
        ->name('communication-hub.automation.store');
    Route::get('/communication-hub/automation/{rule}/edit', [\App\Http\Controllers\CommunicationHub\AutomationController::class, 'edit'])
        ->name('communication-hub.automation.edit');
    Route::put('/communication-hub/automation/{rule}', [\App\Http\Controllers\CommunicationHub\AutomationController::class, 'update'])
        ->name('communication-hub.automation.update');
    Route::post('/communication-hub/automation/{rule}/toggle', [\App\Http\Controllers\CommunicationHub\AutomationController::class, 'toggle'])
        ->name('communication-hub.automation.toggle');

    Route::get('/communication-hub/campaigns', [\App\Http\Controllers\CommunicationHub\CampaignsController::class, 'index'])
        ->name('communication-hub.campaigns.index');
    Route::get('/communication-hub/campaigns/create', [\App\Http\Controllers\CommunicationHub\CampaignsController::class, 'create'])
        ->name('communication-hub.campaigns.create');
    Route::post('/communication-hub/campaigns', [\App\Http\Controllers\CommunicationHub\CampaignsController::class, 'store'])
        ->name('communication-hub.campaigns.store');
    Route::get('/communication-hub/campaigns/{campaign}/edit', [\App\Http\Controllers\CommunicationHub\CampaignsController::class, 'edit'])
        ->name('communication-hub.campaigns.edit');
    Route::put('/communication-hub/campaigns/{campaign}', [\App\Http\Controllers\CommunicationHub\CampaignsController::class, 'update'])
        ->name('communication-hub.campaigns.update');
    Route::delete('/communication-hub/campaigns/{campaign}', [\App\Http\Controllers\CommunicationHub\CampaignsController::class, 'destroy'])
        ->name('communication-hub.campaigns.destroy');

    Route::get('/communication-hub/scheduled', [\App\Http\Controllers\CommunicationHub\ScheduledMessagesController::class, 'index'])
        ->name('communication-hub.scheduled.index');
    Route::get('/communication-hub/scheduled/create', [\App\Http\Controllers\CommunicationHub\ScheduledMessagesController::class, 'create'])
        ->name('communication-hub.scheduled.create');
    Route::post('/communication-hub/scheduled', [\App\Http\Controllers\CommunicationHub\ScheduledMessagesController::class, 'store'])
        ->name('communication-hub.scheduled.store');
    Route::get('/communication-hub/scheduled/{message}/edit', [\App\Http\Controllers\CommunicationHub\ScheduledMessagesController::class, 'edit'])
        ->name('communication-hub.scheduled.edit');
    Route::put('/communication-hub/scheduled/{message}', [\App\Http\Controllers\CommunicationHub\ScheduledMessagesController::class, 'update'])
        ->name('communication-hub.scheduled.update');
    Route::post('/communication-hub/scheduled/{message}/cancel', [\App\Http\Controllers\CommunicationHub\ScheduledMessagesController::class, 'cancel'])
        ->name('communication-hub.scheduled.cancel');
    Route::delete('/communication-hub/scheduled/{message}', [\App\Http\Controllers\CommunicationHub\ScheduledMessagesController::class, 'destroy'])
        ->name('communication-hub.scheduled.destroy');

    Route::get('/communication-hub/queue', [\App\Http\Controllers\CommunicationHub\QueueController::class, 'index'])
        ->name('communication-hub.queue.index');
    Route::post('/communication-hub/queue/process', [\App\Http\Controllers\CommunicationHub\QueueController::class, 'process'])
        ->name('communication-hub.queue.process');
    Route::delete('/communication-hub/queue/failed', [\App\Http\Controllers\CommunicationHub\QueueController::class, 'destroyFailed'])
        ->name('communication-hub.queue.destroy-failed');

    Route::get('/communication-hub/recipients', [\App\Http\Controllers\CommunicationHub\RecipientsController::class, 'index'])
        ->name('communication-hub.recipients.index');

    Route::get('/communication-hub/analytics', [\App\Http\Controllers\CommunicationHub\AnalyticsController::class, 'index'])
        ->name('communication-hub.analytics.index');

    Route::get('/communication-hub/ai-assistant', [\App\Http\Controllers\CommunicationHub\AiAssistantController::class, 'index'])
        ->name('communication-hub.ai-assistant.index');
    Route::post('/communication-hub/ai-assistant', [\App\Http\Controllers\CommunicationHub\AiAssistantController::class, 'store'])
        ->name('communication-hub.ai-assistant.store');

    Route::get('/communication-hub/logs', [\App\Http\Controllers\CommunicationHub\LogsController::class, 'index'])
        ->name('communication-hub.logs.index');
    Route::get('/communication-hub/logs/{log}', [\App\Http\Controllers\CommunicationHub\LogsController::class, 'show'])
        ->name('communication-hub.logs.show');

    Route::get('/communication-hub/settings', [\App\Http\Controllers\CommunicationHub\SettingsController::class, 'edit'])
        ->name('communication-hub.settings.edit');
    Route::put('/communication-hub/settings', [\App\Http\Controllers\CommunicationHub\SettingsController::class, 'update'])
        ->name('communication-hub.settings.update');

    Route::get('/communication-hub/notifications', [\App\Http\Controllers\CommunicationHub\NotificationsController::class, 'index'])
        ->name('communication-hub.notifications.index');
    Route::post('/communication-hub/notifications/{hubNotification}/read', [\App\Http\Controllers\CommunicationHub\NotificationsController::class, 'markRead'])
        ->name('communication-hub.notifications.read');

    Route::get('/communication-hub/newsletter-drafts', [\App\Http\Controllers\CommunicationHub\NewsletterDraftsController::class, 'index'])
        ->name('communication-hub.newsletter-drafts.index');
    Route::get('/communication-hub/newsletter-drafts/create', [\App\Http\Controllers\CommunicationHub\NewsletterDraftsController::class, 'create'])
        ->name('communication-hub.newsletter-drafts.create');
    Route::post('/communication-hub/newsletter-drafts', [\App\Http\Controllers\CommunicationHub\NewsletterDraftsController::class, 'store'])
        ->name('communication-hub.newsletter-drafts.store');
    Route::get('/communication-hub/newsletter-drafts/{newsletterDraft}', [\App\Http\Controllers\CommunicationHub\NewsletterDraftsController::class, 'show'])
        ->name('communication-hub.newsletter-drafts.show');
    Route::get('/communication-hub/newsletter-drafts/{newsletterDraft}/edit', [\App\Http\Controllers\CommunicationHub\NewsletterDraftsController::class, 'edit'])
        ->name('communication-hub.newsletter-drafts.edit');
    Route::put('/communication-hub/newsletter-drafts/{newsletterDraft}', [\App\Http\Controllers\CommunicationHub\NewsletterDraftsController::class, 'update'])
        ->name('communication-hub.newsletter-drafts.update');

    Route::get('/contact/submissions', [\App\Http\Controllers\Contact\SubmissionsController::class, 'index'])
        ->name('contact.submissions.index');
    Route::get('/contact/submissions/{submission}', [\App\Http\Controllers\Contact\SubmissionsController::class, 'show'])
        ->name('contact.submissions.show');
    Route::put('/contact/submissions/{submission}', [\App\Http\Controllers\Contact\SubmissionsController::class, 'update'])
        ->name('contact.submissions.update');
    Route::post('/contact/submissions/{submission}/reply', [\App\Http\Controllers\Contact\SubmissionsController::class, 'reply'])
        ->name('contact.submissions.reply');
    Route::delete('/contact/submissions/{submission}', [\App\Http\Controllers\Contact\SubmissionsController::class, 'destroy'])
        ->name('contact.submissions.destroy');

    Route::get('/testimonies', [\App\Http\Controllers\Testimonies\TestimoniesController::class, 'index'])
        ->name('testimonies.index');
    Route::get('/testimonies/{testimony}', [\App\Http\Controllers\Testimonies\TestimoniesController::class, 'show'])
        ->name('testimonies.show');
    Route::put('/testimonies/{testimony}', [\App\Http\Controllers\Testimonies\TestimoniesController::class, 'update'])
        ->name('testimonies.update');
    Route::delete('/testimonies/{testimony}', [\App\Http\Controllers\Testimonies\TestimoniesController::class, 'destroy'])
        ->name('testimonies.destroy');

    Route::get('/newsletter-subscribers', [\App\Http\Controllers\Newsletter\SubscribersController::class, 'index'])
        ->name('newsletter-subscribers.index');
    Route::post('/newsletter-subscribers/send', [\App\Http\Controllers\Newsletter\SubscribersController::class, 'send'])
        ->name('newsletter-subscribers.send');
    Route::get('/newsletter-subscribers/{subscriber}', [\App\Http\Controllers\Newsletter\SubscribersController::class, 'show'])
        ->name('newsletter-subscribers.show');
    Route::post('/newsletter-subscribers/{subscriber}/toggle-status', [\App\Http\Controllers\Newsletter\SubscribersController::class, 'toggleStatus'])
        ->name('newsletter-subscribers.toggle-status');
    Route::delete('/newsletter-subscribers/{subscriber}', [\App\Http\Controllers\Newsletter\SubscribersController::class, 'destroy'])
        ->name('newsletter-subscribers.destroy');

    Route::get('/sermons', [\App\Http\Controllers\Sermons\DashboardController::class, 'index'])
        ->name('sermon.dashboard');
    Route::get('/sermons/library', [\App\Http\Controllers\Sermons\SermonsController::class, 'index'])
        ->name('sermon.sermons.index');
    Route::get('/sermons/library/create', [\App\Http\Controllers\Sermons\SermonsController::class, 'create'])
        ->name('sermon.sermons.create');
    Route::post('/sermons/library', [\App\Http\Controllers\Sermons\SermonsController::class, 'store'])
        ->name('sermon.sermons.store');
    Route::get('/sermons/library/{sermon}', [\App\Http\Controllers\Sermons\SermonsController::class, 'show'])
        ->name('sermon.sermons.show');
    Route::get('/sermons/library/{sermon}/edit', [\App\Http\Controllers\Sermons\SermonsController::class, 'edit'])
        ->name('sermon.sermons.edit');
    Route::put('/sermons/library/{sermon}', [\App\Http\Controllers\Sermons\SermonsController::class, 'update'])
        ->name('sermon.sermons.update');
    Route::post('/sermons/library/{sermon}/publish', [\App\Http\Controllers\Sermons\SermonsController::class, 'publish'])
        ->name('sermon.sermons.publish');
    Route::post('/sermons/library/{sermon}/archive', [\App\Http\Controllers\Sermons\SermonsController::class, 'archive'])
        ->name('sermon.sermons.archive');

    Route::get('/sermons/broadcasts', [\App\Http\Controllers\Sermons\BroadcastsController::class, 'index'])
        ->name('sermon.broadcasts.index');
    Route::get('/sermons/broadcasts/create', [\App\Http\Controllers\Sermons\BroadcastsController::class, 'create'])
        ->name('sermon.broadcasts.create');
    Route::post('/sermons/broadcasts', [\App\Http\Controllers\Sermons\BroadcastsController::class, 'store'])
        ->name('sermon.broadcasts.store');
    Route::get('/sermons/broadcasts/{broadcast}', [\App\Http\Controllers\Sermons\BroadcastsController::class, 'show'])
        ->name('sermon.broadcasts.show');
    Route::get('/sermons/broadcasts/{broadcast}/edit', [\App\Http\Controllers\Sermons\BroadcastsController::class, 'edit'])
        ->name('sermon.broadcasts.edit');
    Route::put('/sermons/broadcasts/{broadcast}', [\App\Http\Controllers\Sermons\BroadcastsController::class, 'update'])
        ->name('sermon.broadcasts.update');
    Route::post('/sermons/broadcasts/{broadcast}/start', [\App\Http\Controllers\Sermons\BroadcastsController::class, 'start'])
        ->name('sermon.broadcasts.start');
    Route::post('/sermons/broadcasts/{broadcast}/stop', [\App\Http\Controllers\Sermons\BroadcastsController::class, 'stop'])
        ->name('sermon.broadcasts.stop');

    Route::get('/website', [\App\Http\Controllers\Website\DashboardController::class, 'index'])
        ->name('website.dashboard');
    Route::get('/website/blog', [\App\Http\Controllers\Website\BlogController::class, 'index'])
        ->name('website.blog.index');
    Route::get('/website/blog/create', [\App\Http\Controllers\Website\BlogController::class, 'create'])
        ->name('website.blog.create');
    Route::post('/website/blog', [\App\Http\Controllers\Website\BlogController::class, 'store'])
        ->name('website.blog.store');
    Route::get('/website/blog/{post}', [\App\Http\Controllers\Website\BlogController::class, 'show'])
        ->name('website.blog.show');
    Route::get('/website/blog/{post}/edit', [\App\Http\Controllers\Website\BlogController::class, 'edit'])
        ->name('website.blog.edit');
    Route::put('/website/blog/{post}', [\App\Http\Controllers\Website\BlogController::class, 'update'])
        ->name('website.blog.update');
    Route::post('/website/blog/{post}/publish', [\App\Http\Controllers\Website\BlogController::class, 'publish'])
        ->name('website.blog.publish');

    Route::get('/website/about/edit', [\App\Http\Controllers\Website\AboutContentController::class, 'edit'])
        ->name('website.about.edit');
    Route::put('/website/about', [\App\Http\Controllers\Website\AboutContentController::class, 'update'])
        ->name('website.about.update');
    Route::match(['post', 'put'], '/website/about/reset', [\App\Http\Controllers\Website\AboutContentController::class, 'reset'])
        ->name('website.about.reset');

    Route::get('/website/team', [\App\Http\Controllers\Website\TeamController::class, 'index'])
        ->name('website.team.index');
    Route::get('/website/team/create', [\App\Http\Controllers\Website\TeamController::class, 'create'])
        ->name('website.team.create');
    Route::post('/website/team', [\App\Http\Controllers\Website\TeamController::class, 'store'])
        ->name('website.team.store');
    Route::get('/website/team/{member}/edit', [\App\Http\Controllers\Website\TeamController::class, 'edit'])
        ->name('website.team.edit');
    Route::put('/website/team/{member}', [\App\Http\Controllers\Website\TeamController::class, 'update'])
        ->name('website.team.update');

    Route::get('/website/pages', [\App\Http\Controllers\Website\PagesController::class, 'index'])
        ->name('website.pages.index');
    Route::get('/website/pages/hero/edit', [\App\Http\Controllers\Website\PagesController::class, 'editHero'])
        ->name('website.pages.hero.edit');
    Route::put('/website/pages/hero', [\App\Http\Controllers\Website\PagesController::class, 'updateHero'])
        ->name('website.pages.hero.update');
    Route::get('/website/pages/{pageKey}/edit', [\App\Http\Controllers\Website\PagesController::class, 'edit'])
        ->where('pageKey', '[A-Za-z0-9\-]+')
        ->name('website.pages.edit');
    Route::put('/website/pages/{pageKey}', [\App\Http\Controllers\Website\PagesController::class, 'update'])
        ->where('pageKey', '[A-Za-z0-9\-]+')
        ->name('website.pages.update');

    Route::get('/website/seo', [\App\Http\Controllers\Website\SeoController::class, 'index'])
        ->name('website.seo.index');
    Route::get('/website/seo/{pageKey}/edit', [\App\Http\Controllers\Website\SeoController::class, 'edit'])
        ->name('website.seo.edit');
    Route::put('/website/seo/{pageKey}', [\App\Http\Controllers\Website\SeoController::class, 'update'])
        ->name('website.seo.update');

    Route::get('/website/media', [\App\Http\Controllers\Website\MediaLibraryController::class, 'index'])
        ->name('website.media.index');

    Route::get('/analytics/site-traffic', [\App\Http\Controllers\Analytics\SiteTrafficController::class, 'index'])
        ->name('analytics.site-traffic.index');
    Route::get('/analytics/site-traffic/dashboard', [\App\Http\Controllers\Analytics\SiteTrafficController::class, 'dashboard'])
        ->name('analytics.site-traffic.dashboard');
    Route::get('/analytics/site-traffic/sessions/{sessionKey}', [\App\Http\Controllers\Analytics\SiteTrafficController::class, 'session'])
        ->where('sessionKey', '[A-Za-z0-9\-]+')
        ->name('analytics.site-traffic.session');
    Route::get('/analytics/site-traffic/visitors/{visitorKey}', [\App\Http\Controllers\Analytics\SiteTrafficController::class, 'visitor'])
        ->where('visitorKey', '[A-Za-z0-9\-]+')
        ->name('analytics.site-traffic.visitor');
    Route::post('/analytics/site-traffic/purge', [\App\Http\Controllers\Analytics\SiteTrafficController::class, 'purge'])
        ->name('analytics.site-traffic.purge');
    Route::redirect('/reports', '/admin/analytics/reports');
    Route::get('/analytics/reports', [\App\Http\Controllers\Analytics\ReportsController::class, 'index'])
        ->name('analytics.reports.index');
    Route::post('/analytics/reports/generate', [\App\Http\Controllers\Analytics\ReportsController::class, 'generate'])
        ->name('analytics.reports.generate');
    Route::get('/analytics/reports/{report}/download', [\App\Http\Controllers\Analytics\ReportsController::class, 'download'])
        ->name('analytics.reports.download');
    Route::delete('/analytics/reports/{report}', [\App\Http\Controllers\Analytics\ReportsController::class, 'destroy'])
        ->name('analytics.reports.destroy');
    Route::get('/analytics/cutover', [\App\Http\Controllers\Analytics\CutoverController::class, 'index'])
        ->name('analytics.cutover.index');
    });
});
