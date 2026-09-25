# Hostinger production (agcikenegbu.org)

## Document root (cannot be changed)

Hostinger Web Hosting serves:

`/home/u238679323/domains/agcikenegbu.org/public_html`

That directory **is** the Laravel application root. hPanel cannot point the domain at `public_html/public`.

| Role | Path |
|---|---|
| Laravel application root | `public_html/` |
| Web document root | `public_html/` (fixed by Hostinger) |
| Front controller | `public_html/index.php` |
| Rewrite / deny rules | `public_html/.htaccess` |
| Laravel `public_path()` | `public_html/public/` (unchanged) |

## Web-accessible vs blocked

**Served as website URLs** (files stay under Laravel `public/`):

- `https://agcikenegbu.org/` → root `index.php`
- `https://agcikenegbu.org/site/...` → `public/site/...` (includes `public/site/uploads/`)
- `https://agcikenegbu.org/portal/...` → `public/portal/...`
- `https://agcikenegbu.org/build/...` → `public/build/...` (Vite production build)
- `https://agcikenegbu.org/storage/...` → `public/storage/...` only (if `php artisan storage:link` created it)

**Must not be readable** (denied in root `.htaccess`):

`.env`, `composer.json`, `composer.lock`, `artisan`, `app/`, `bootstrap/`, `config/`, `database/`, `resources/`, `routes/`, `storage/` (framework), `vendor/`, `tests/`, `tools/`

Church uploads are **not** moved. They remain `public/site/uploads/`.

## Why files are not copied to the repository root

`public_path()` stays `public/`. Upload code, Blade `asset('site/...')`, and Vite still write/read `public/site`, `public/portal`, and `public/build`.

The root `.htaccess` internally maps those URL prefixes to `public/`. That is Git-friendly (one copy of each asset) and does not depend on Hostinger allowing symlinks.

Do **not** set Laravel’s public path to `public_html` itself. `php artisan storage:link` would collide with the real `storage/` directory.

Keep `public/index.php` for `php artisan serve` locally.

## Runtime URLs (APP_URL and PORTAL_*)

Set these in the **server** `.env` (never commit `.env`):

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://agcikenegbu.org
PORTAL_MEDIA_BASE=https://agcikenegbu.org
PORTAL_LEGACY_ADMIN_BASE=https://agcikenegbu.org/portal
```

Do not set `PORTAL_LEGACY_PUBLIC_BASE` or `PORTAL_LEGACY_API_BASE` to this same Laravel origin (the member-portal proxy would loop). Public pages use `url('/api')` even if those vars still mention localhost.

The public homepage `data-api-base` uses Laravel `url('/api')` (same origin). Missing media no longer falls back to `http://localhost/AG_IKENEGBU_CHURCH_WEBSITE` when the visitor host is not loopback.

Then:

```
php artisan config:clear
php artisan config:cache
```

## Deploy from GitHub

On the server, application root = `public_html`.

1. Delete Hostinger’s default `index.html` in `public_html` if it is still present.
2. Ensure root `index.php` and `.htaccess` from this repo are in `public_html`.
3. Do not commit or upload `.env`. Create it on the server.
4. Compile Vite **before** or **on** the server (`npm run build`) so `public/build/` exists. It is gitignored.
5. `composer install --no-dev --optimize-autoloader`
6. `php artisan migrate --force`
7. `php artisan storage:link` if member photos use `/storage/` (optional if everything is under `public/site/uploads`)
8. `php artisan config:cache`

Local `php artisan serve` is unchanged: it still uses `public/`.
