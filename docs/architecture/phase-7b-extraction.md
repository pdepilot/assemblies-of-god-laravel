# Phase 7b extraction design

**Status:** approved for implementation  
**Topology:** two Laravel applications + two databases + shared media disk (v1)  
**Prerequisite:** Phase 7a tooling (`SdtgDatabase`, `sdtg:verify-migration`, `sdtg:copy-data`)

## Locked decisions

| Decision | Choice |
|---|---|
| Identity | SDTG owns its own `admins` + RBAC copy (no shared identity DB). One-time migrate; no live sync in v1. |
| Sequence | Domain DB split → scaffold second app → identity copy → HTTP contracts → AG cleanup |
| Media | Shared filesystem paths under `public/site/…` (same as 7a) |
| New app path | `c:\Laravel-Projects\sdtg-management` |
| Public URL (v1) | `/sdgt` on the SDTG app host |
| Admin URL (v1) | `/admin/sdtg/*` on the SDTG app host |

## Target topology

```
AGC Ikenegbu app ──► AG DB
       │
       └── HTTP (internal token) ──► SDTG Management app ──► SDTG DB
                                              │
                                              └── shared media disk
```

## Data ownership

### SDTG database

**Domain (15 tables after livestream move):**

- All 14 tables from `config/sdtg.php` `tables`
- `sdtg_livestream_settings` (replaces AG `platform_setting_groups` group_key=`livestream`)

**Identity (SDTG app only):**

- `admins` (copy where `platform_access` in `sdtg|both`)
- `roles`, `permissions`, `role_permissions`, `admin_role_assignments` (SDTG-scoped)
- `sessions`, `password_reset_tokens`

### AG database (forever)

Members, donations, website CMS, AG analytics, AG-only admins/RBAC, AG sessions. No `sdtg_*` after soak.

## Cross-product contracts (HTTP)

AG keeps interfaces:

- `App\Contracts\Sdtg\SdtgAnalyticsReadContract`
- `App\Contracts\Sdtg\SdtgMediaMetricsReadContract`

After extract, AG binds HTTP clients. SDTG exposes authenticated internal JSON endpoints (shared secret `SDTG_INTERNAL_API_TOKEN`).

| Contract method | SDTG endpoint (v1) |
|---|---|
| `registrationYears()` | `GET /internal/sdtg/analytics/registration-years` |
| registration row reads used by AG reports | `GET /internal/sdtg/analytics/registrations` (query params as needed) |
| `mediaAssetCount()` | `GET /internal/sdtg/metrics/media-asset-count` |

Auth header: `Authorization: Bearer {SDTG_INTERNAL_API_TOKEN}`  
AG env: `SDTG_APP_URL`, `SDTG_INTERNAL_API_TOKEN`  
SDTG env: `SDTG_INTERNAL_API_TOKEN` (same value)

## App layout

| Path | Role |
|---|---|
| `assemblies-of-god-laravel` | AG ERP + AG public site; redirects `/sdgt` and `/admin/sdtg` to SDTG host when configured |
| `sdtg-management` | SDTG public + SDTG admin + internal read API; default DB = SDTG |

## Cutover order

1. **7b-1** Domain DB: provision SDTG DB, schema, `sdtg:copy-data`, `sdtg:verify-migration`, set `DB_SDTG_CONNECTION` (still one app).
2. **7b-2** Scaffold `sdtg-management` from SDTG-owned surface; point its default connection at SDTG DB.
3. **7b-3** `sdtg:copy-identity`; SDTG app authenticates against its own `admins`.
4. **7b-4** Flip AG contract bindings to HTTP; smoke AG analytics/media.
5. **7b-5** AG redirects/removes local SDTG routes; drop residual SDTG services from AG; unset AG `DB_SDTG_*`.

## Rollback

| Stage | Rollback |
|---|---|
| 7b-1 | Unset `DB_SDTG_CONNECTION` if source `sdtg_*` retained |
| 7b-2/3 | Point traffic back to monolith; ignore SDTG app host |
| 7b-4 | Rebind AG contracts to local/in-process implementations (or restore previous release) |
| 7b-5 | Redeploy monolith with SDTG routes restored from git |

## Non-goals (v1)

- SSO / live admin sync
- Renaming `/sdgt` away
- Separate media bucket
- Namespace purity renames
- Feature/UX changes

## Related docs

- [phase-7a-cutover.md](./phase-7a-cutover.md) — domain DB cutover checklists (feeds 7b-1)
- [application-ownership.md](./application-ownership.md) — application-layer ownership
- [phase-7b-ag-cleanup.md](./phase-7b-ag-cleanup.md) — AG residual code / rollback fallback
