# Phase 7b AG cleanup notes

After extract:

- With `SDTG_APP_URL` set, [`routes/public-sdtg.php`](../../routes/public-sdtg.php) and [`routes/sdtg.php`](../../routes/sdtg.php) redirect to the SDTG app.
- With `SDTG_CONTRACTS_DRIVER=http`, AG binds HTTP contract clients (no direct `sdtg_*` reads required for analytics/media counts).
- SDTG PHP under `App\Http\Controllers\Sdtg`, `App\Services\Sdtg`, and related views is **retained** in AG as a rollback fallback while `SDTG_APP_URL` is empty. Delete in a later soak cleanup once production has run dual-host only.
- Ops commands (`sdtg:verify-migration`, `sdtg:copy-data`, `sdtg:copy-identity`, `sdtg:sync-schema`) remain in AG for cutover/rehearsal; mirrored in `sdtg-management`.
