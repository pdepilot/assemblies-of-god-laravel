# Phase 7a cutover runbook

**Topology:** single Laravel application, two databases (AG/default + dedicated SDTG).  
**Feeds into:** Phase 7b-1 (domain DB) before app extraction — see [phase-7b-extraction.md](./phase-7b-extraction.md).  
**Still deferred to 7b:** second Laravel app, independent `admins`/RBAC copy.

Decisions locked for 7a:

- Media/files remain on shared disk/object storage
- Short maintenance / write freeze for SDTG during cutover
- Phase 6 contracts stay local implementations on the SDTG connection
- Livestream runtime moves to `sdtg_livestream_settings` (SDTG connection) before or during domain copy

## Environment

```env
# After SDTG DB is ready:
DB_SDTG_CONNECTION=sdtg
DB_SDTG_HOST=127.0.0.1
DB_SDTG_PORT=3306
DB_SDTG_DATABASE=assemblies_of_god_sdtg
DB_SDTG_USERNAME=...
DB_SDTG_PASSWORD=...
```

Until `DB_SDTG_CONNECTION` is set, `App\Support\SdtgDatabase` uses the default connection (no behavior change).

## Commands

```bash
# Compare source vs target (exit 0 pass, 1 mismatch, 2 config error)
php artisan sdtg:verify-migration --source=mysql --target=sdtg

# Optional: copy rows after schema exists on target
php artisan sdtg:copy-data --source=mysql --target=sdtg --truncate
php artisan sdtg:copy-data --source=mysql --target=sdtg --dry-run
```

---

## Pre-cutover checklist

### Planning / access
- [ ] Phase 7a topology approved
- [ ] SDTG database provisioned; credentials available
- [ ] `sdtg` connection present in `config/database.php`
- [ ] Maintenance window scheduled; stakeholders notified
- [ ] Rollback owner named

### Backup
- [ ] Full backup of source DB taken and restore tested
- [ ] Backup identifiers recorded in cutover log

### Inventory
- [ ] Confirm tables in `config/sdtg.php` → `tables` (14 domain + `sdtg_livestream_settings`)
- [ ] Capture source row counts (or run verify after dry-run copy)
- [ ] Confirm no unexpected FKs from AG tables into `sdtg_*`
- [ ] Media remains on shared storage (confirmed)

### Dry-run
- [ ] Schema applied on target (import or migrate)
- [ ] `php artisan sdtg:copy-data --source=… --target=sdtg --dry-run`
- [ ] Copy on staging/clone: `sdtg:copy-data --truncate`
- [ ] `php artisan sdtg:verify-migration --source=… --target=sdtg` exits `0`
- [ ] Staging smoke: SDTG admin, public `/sdgt`, AG analytics SDTG filters, website media count
- [ ] Rollback drill: clear `DB_SDTG_CONNECTION` (or point services at default); confirm app healthy

### Freeze
- [ ] SDTG write freeze started (admin + public memory/gallery)
- [ ] Final source row-count snapshot after freeze

---

## Post-cutover verification checklist

### Immediate
- [ ] Production env has `DB_SDTG_CONNECTION=sdtg` and correct `DB_SDTG_*`
- [ ] `php artisan sdtg:verify-migration --source=… --target=sdtg` exits `0`  
      (If source `sdtg_*` already dropped, compare against frozen count file instead)
- [ ] SDTG admin login + dashboard + one write OK
- [ ] Public `/sdgt` + gallery API + memory submit OK
- [ ] AG Analytics SDTG period filters populate
- [ ] AG Website media index `sdtg_media` count OK
- [ ] Logs show no missing `sdtg_*` on default connection
- [ ] Write freeze lifted only after passes

### Soak (24–72h)
- [ ] Monitor errors / connection failures
- [ ] Target SDTG DB on backup schedule
- [ ] Decide retain / rename / drop source `sdtg_*` after soak

### Rollback trigger
If immediate checks fail: restore previous env (unset `DB_SDTG_CONNECTION` or restore release), restore DB from backup if tables were dropped, keep freeze until stable.

---

## Rollback (7a)

1. Unset `DB_SDTG_CONNECTION` (or redeploy previous release).
2. If source `sdtg_*` retained: traffic resumes on default DB.
3. If source dropped: restore from pre-cutover backup.
4. Re-run SDTG smoke tests.
