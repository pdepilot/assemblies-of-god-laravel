# Strangler-Fig Cutover Checklist

Laravel admin (`C:\Laravel-Projects\assemblies-of-god-laravel`) runs against the same `assemblies_of_god` MySQL database as the legacy Core PHP portal (`C:\xampp\htdocs\AG_IKENEGBU_CHURCH_WEBSITE`). Cutover is incremental — not a big-bang rewrite.

## Parallel run (pre-cutover)

1. Clone production MySQL to a staging host (`mysqldump` + restore).
2. Point Laravel `.env` at the clone; run `php artisan migrate` (idempotent guards only).
3. Run legacy portal against the same clone for side-by-side validation.
4. Exercise migrated modules (M0–M10) in Laravel; compare outputs with legacy screens and exports.

## Public traffic beacon

1. Legacy beacon: `api/track-traffic.php`
2. Laravel beacon: `POST /api/track-traffic` (alias `POST /api/track-traffic.php`)
3. Payload: JSON `{ event, visitor_key, session_key, pageview_key?, path, title?, referrer?, duration_seconds?, site_area? }`
4. Switch the front-end beacon URL in staging first; confirm `site_sessions` / `site_pageviews` rows appear.
5. Rate limit: 120 requests / 5 minutes / IP via `rate_limits` table.

### Point legacy site at Laravel (staging)

1. In the **legacy** site root, copy `config/cutover.local.php.example` → `config/cutover.local.php`.
2. Set `laravel_traffic_beacon_url` to your Laravel `APP_URL` + `/api/track-traffic`, e.g. `http://127.0.0.1:8000/api/track-traffic` for local `php artisan serve`.
3. Alternatively set env `AG_LARAVEL_TRAFFIC_BEACON_URL` on the legacy host (no file copy).
4. In Laravel `.env`, set `APP_URL` to the public Laravel base URL (not `http://localhost` on staging).
5. Set `TRAFFIC_BEACON_CORS_ORIGINS` to the legacy public origin(s), comma-separated, or `*` for local dev only.
6. Browse the legacy public site with cookie consent accepted; traffic should land in Laravel `site_sessions` / `site_pageviews`.

## Staging validation

Run on the staging Laravel host after migrations and beacon wiring:

```bash
php artisan analytics:validate-cutover
```

Checks: database connection, traffic/reports tables, reports storage writable, beacon ingest smoke test, recent traffic (24h), and `APP_URL`.

The admin **Migration Cutover** dashboard (`/analytics/cutover`) mirrors the same validation results and shows the recommended beacon URL plus legacy config snippet.

## DNS defer

- Do **not** change production DNS until checklist sign-off.
- Keep legacy portal as fallback; Laravel can run on a subdomain (e.g. `admin-staging.example.org`) during validation.

## Validation steps

| Area | Check |
|------|-------|
| Auth / RBAC | Login, role gates, permission denials (403) |
| Site traffic | Beacon ingest, dashboard KPIs, purge |
| Reports | CSV membership, events, SDTG, ERP financial summary |
| ERP / donations | Totals match legacy for sample periods |
| Communication | Templates, logs, contact inbox |
| Rollback | Document mysqldump restore; never `DROP` production tables from Laravel migrations |

## Admin entry points (Laravel)

Admin UI is under the `/admin` prefix:

- Login: `/admin/login`
- Dashboard: `/admin/dashboard`
- Site Traffic: `/admin/analytics/site-traffic`
- Reports Hub: `/admin/analytics/reports`
- Migration Cutover dashboard: `/admin/analytics/cutover`

Legacy bookmark redirects: `/login` → `/admin/login`, `/dashboard` → `/admin/dashboard`.

## Public homepage (M11)

- Laravel serves the church homepage at `/` (assets in `public/site/`).
- Nav CTAs for About / Events / Sermons / Donate / SDTG still point at `PORTAL_LEGACY_PUBLIC_BASE` until those pages migrate.
- Testimonials JS calls `PORTAL_LEGACY_API_BASE`.
- Homepage hero/preloader reads `website-pages.json` via `PORTAL_WEBSITE_PAGES_PATH` (defaults to legacy portal storage).

## Sign-off criteria

- [ ] All M0–M11 milestone tests pass (`php artisan test`)
- [ ] Beacon traffic validated on staging clone for 48+ hours
- [ ] Report CSVs spot-checked against legacy exports
- [ ] Finance lead approves ERP report summary
- [ ] Public homepage visual parity checked on staging
- [ ] Rollback runbook tested on clone
