# Application ownership (Phase 6 + 7b)

This document is the source of truth for **application-layer** ownership of AG vs SDTG.
Physical split details: [phase-7a-cutover.md](./phase-7a-cutover.md), [phase-7b-extraction.md](./phase-7b-extraction.md).

## Shared infrastructure (pre-extract / transitional)

Until Phase 7b-5 cleanup completes in the AG monolith, these may still exist in both codebases as copies:

| Area | Location | Notes |
|---|---|---|
| Admin identity | `App\Models\Admin` | **7b:** each app owns its `admins` table (SDTG copy is independent) |
| Auth / login security | `App\Services\Auth\*`, `App\Services\Security\*` | Copied into SDTG app; not a shared runtime |
| RBAC engine | `RbacReadService`, `RbacWriteService`, `RbacPlatform`, `RbacNavAccessService` | SDTG app filters to SDTG platform roles only |
| Portal shell / nav boot | `App\Services\Portal\*` | Context-strict nav; SDTG app boots SDTG shell only |
| Public asset URLs | `PublicSite\PublicAssetResolver` | Shared media paths on disk |
| Middleware aliases | `bootstrap/app.php` | `admin.idle`, `sdtg.idle`, `admin.rbac` |

## AG-owned components

| Layer | Location |
|---|---|
| Controllers | Domain folders under `App\Http\Controllers\` (Members, Events, Donations, Website, …) — **not** SDTG after extract |
| Services | Matching domain folders under `App\Services\` |
| Routes | `routes/admin.php`, `routes/auth.php`, AG public site |
| Data | AG domain tables; AG admins/RBAC |

AG must not mutate `sdtg_*` tables. Reads go through **cross-product contracts** only (local in 7a; HTTP in 7b-4+).

## SDTG-owned components

| Layer | Location |
|---|---|
| App root (7b) | `c:\Laravel-Projects\sdtg-management` |
| Admin controllers | `App\Http\Controllers\Sdtg\*` |
| Admin services | `App\Services\Sdtg\*` |
| Policy | `App\Policies\SdtgPolicy` |
| Form requests | `App\Http\Requests\Sdtg\*` |
| Data | `sdtg_*` tables + `sdtg_livestream_settings` + SDTG-owned identity tables |
| Admin routes | `routes/sdtg.php` → `sdtg-auth.php` + `sdtg-admin.php` |
| Admin shell | `SdtgLayout` / `layouts/sdtg-app` |
| Public | `routes/public-sdtg.php`, `PublicSdtg*`, legacy `/sdgt` bridge |

### Livestream settings

Runtime livestream state lives in **`sdtg_livestream_settings`** on the SDTG connection (not AG `platform_setting_groups`).

## Cross-product contracts

| Contract | Consumer | Purpose |
|---|---|---|
| `SdtgAnalyticsReadContract` | AG Analytics report services | Registration years + rows |
| `SdtgMediaMetricsReadContract` | AG Website media index | Media asset count |

**Rules**

- Prefer new methods on these contracts over AG querying `sdtg_*`.
- Phase 7a: local implementations via `SdtgDatabase`.
- Phase 7b-4+: AG binds HTTP clients; SDTG serves `/internal/sdtg/*` with shared bearer token.

## Extraction phases

| Phase | Summary |
|---|---|
| 7a | Single app, optional second DB connection |
| 7b-1 | Domain DB cutover (`sdtg_*` + livestream settings) |
| 7b-2 | Scaffold `sdtg-management` |
| 7b-3 | Independent admins/RBAC copy |
| 7b-4 | HTTP contract clients on AG |
| 7b-5 | Remove/redirect SDTG surface from AG |

## Non-goals (v1 extract)

- SSO / live admin sync
- Namespace churn (`Ag\` renames)
- Eloquent models for every `sdtg_*` table
- Feature or UX changes
- Separate media object storage
