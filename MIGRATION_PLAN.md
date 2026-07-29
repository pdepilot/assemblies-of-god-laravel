# Assemblies of God — Laravel Migration Plan

## Strategy (approved)

**Strangler-fig migration** — Laravel connects to the existing `assemblies_of_god` MySQL database. No table renames. Migrations use `Schema::hasTable()` guards so production data stays intact while SQLite powers tests.

- **Legacy:** Core PHP at `C:\xampp\htdocs\AG_IKENEGBU_CHURCH_WEBSITE`
- **Laravel:** `C:\Laravel-Projects\assemblies-of-god-laravel`
- **Schema inventory:** 199 tables in SQL exports + ~5 bootstrap-only tables (~204 total)
- **Tests:** Pest + SQLite in-memory (`phpunit.xml`); production/staging uses MySQL

## Migration rules

1. Do not redesign UI unless requested.
2. Preserve all business logic from legacy `*Service.php` classes.
3. Thin controllers, Form Requests, Read/Write services, Policies, idempotent migrations.
4. Never `DROP` production tables — rollback = restore mysqldump.

## Progress

| Milestone | Status | Notes |
|-----------|--------|-------|
| M0 — Database assessment | Done | Strangler-fig strategy approved |
| M1 — Shared DB connection | Done | `.env.example` → `assemblies_of_god` |
| M2 — Auth & RBAC foundation | Done | `admins` guard, RBAC tables, `RbacReadService`, login audit |
| M3 — Members & ministries | Done | Members CRUD + church visitors + ministries settings/roster |
| M4 — Sunday School | Mostly done | Core + advanced modules migrated |
| M5 — Events & registration | Done | Church events CMS + registration portals admin |
| M6 — Donations & stewardship | Done | Donations admin, commitment programs/givers, pledges |
| M7 — Financial ERP | Done | Dashboard, COA, journals, income/expenses, vendors, projects, audit |
| M8 — Communication hub | Done | Dashboard, templates, logs, settings, notifications, newsletter drafts, contact inbox, subscribers |
| M9 — Media, SDTG, website | Done | Sermons, broadcasts, SDTG admin, website CMS |
| M10 — Analytics & cutover | Done | Site traffic, reports hub, cutover dashboard, public beacon API |
| M11 — Public homepage | Done | Public `/` Blade port + shell; admin under `/admin`; sibling pages bridged |

## Phase order (dependency-aware)

### Phase 1 — Core framework ✅
- `admins`, admin sessions, login attempts, security tables
- `roles`, `permissions`, `role_permissions`, `admin_role_assignments`, `admin_permissions`, `admin_roles`
- `platform_setting_groups`, `platform_backups`
- `RbacReadService` (legacy-compatible permission checks)
- Login audit trail (`LoginAuditWriteService`)

### Phase 2 — Church structure ✅
- `members`, `member_status_history`, `visitors`
- `ministry_settings`, `ministry_members`, `ministry_attendance`

### Phase 3 — Sunday School ✅
- Core + register + advanced modules migrated

### Phase 4 — Members portal & church attendance
- `attendance_records`, member portal columns

### Phase 5 — Events & registration portals ✅
- `church_events` — admin CRUD, publish/pause, recurring sync
- `registration_portals`, `registration_fields`, `registrants`, `registrant_answers`

### Phase 6 — Donations & stewardship ✅
- `donations`, `donation_categories` (seeded)
- `commitment_programs`, `commitment_givers`
- `pledges`, `pledge_payments`
- Manual donation recording, stats, RBAC for `finance` role
### Phase 7 — Financial ERP ✅
- Core `erp_*` tables: fiscal years, accounts, journals, income/expenses, vendors, projects
- Bootstrap seed: COA, categories, bank registers, settings
- Dashboard stats, journal post workflow, income→receipt sync, expense approval queue
- Audit trail read-only index

### Phase 8 — Communication hub & email ✅
- Hub tables: channels, settings, templates, logs, newsletter drafts, notification center
- Email foundation: `email_settings`, `email_templates`, `email_history` (log fallback)
- Contact inbox: `contact_submissions` status/reply workflow
- Site newsletter: `site_newsletter_subscribers` admin list + status toggle
- Deferred: SMTP send, SMS gateways, campaigns, automation cron, analytics charts

### Phase 9 — Sermons, broadcast, SDTG, website CMS ✅
- Sermons library, live broadcasts, SDTG admin modules, website CMS pages

### Phase 10 — Reports, traffic analytics, cutover ✅
- `site_sessions`, `site_pageviews`, `site_geo_cache`, `generated_reports`
- Public beacon API (`POST /api/track-traffic`) with rate limiting
- Admin dashboards: Site Traffic, Reports Hub (CSV: membership, events, SDTG, ERP financial), Migration Cutover
- Staging validation: `php artisan analytics:validate-cutover`, legacy `config/cutover.local.php` beacon bridge
- See `CUTOVER.md` for strangler-fig parallel-run checklist

### Phase 11 — Public homepage ✅
- Public church homepage at Laravel `/` (fidelity Blade port of legacy `index.php`)
- Shared public shell (topbar / nav / footer); AG + SDTG sibling pages served via `LegacyHtmlBridge` (rewritten legacy HTML + local `/site` & `/sdgt` assets)
- Admin CMS moved under `/admin` (login at `/admin/login`)
- Static assets under `public/site/` and `public/sdgt/`; media fallback via `PORTAL_MEDIA_BASE` when a file is missing locally
- Traffic beacon on public layout

## Local setup

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=assemblies_of_god
DB_USERNAME=root
DB_PASSWORD=
```

Run migrations against a **clone** of production first, never against live data until validated.

```bash
php artisan migrate
php artisan test
```

## Architecture pattern

```
Route → Controller → Form Request → Policy → Read/Write Service → Eloquent / Query Builder
```

Legacy reference: `portal/includes/*Service.php` and `app/Modules/*`.
