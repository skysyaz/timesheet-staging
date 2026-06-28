# Observability Integration — Deliverable Summary

**Application:** Quatriz TimeSheet (Laravel 13 / Filament 5 / PHP 8.4)  
**Date:** 2026-06-28  
**Status:** Code complete · tests green · production env vars pending credentials

---

## Phase 1 — Discovery & Planning

**Summary:** Confirmed Laravel ^13.8, PHP ^8.3 (8.4 on VPS), PostgreSQL production, SQLite tests, Caddy + PHP-FPM on VPS at `/var/www/timesheet`. Mapped Flare → unhandled exceptions; activitylog → auditable admin actions; Better Uptime → public URL/heartbeat monitors. No conflicting Sentry/custom audit tables beyond existing `approval_logs` (workflow-specific, retained).

| Tool | Scope |
|------|-------|
| Flare | `bootstrap/app.php`, `AttachFlareContext` middleware |
| Activitylog | `User`, `Timesheet`, `Project` models + manual `AuditLogger` |
| Better Uptime | `/up`, `/admin/login`, `/uptime/heartbeat`, `/uptime/queue-heartbeat` |

**Affected:** `TimesheetResource`, `Settings`, `PdfController`, `routes/web.php`, `routes/console.php`, scheduler, queue worker.

**Verification:** `php artisan about`, review `docs/OBSERVABILITY.md`.

**Risks:** Better Uptime monitors require manual dashboard setup (no API token provided).

---

## Phase 2 — Dependencies & Configuration

**Summary:** Installed `spatie/laravel-activitylog` ^5.0 and `spatie/laravel-flare` ^3.0. Published configs and migration. Central `config/observability.php` for uptime/tenant tags.

**Files:**
- `composer.json`, `composer.lock`
- `config/flare.php`, `config/activitylog.php`, `config/observability.php`
- `.env.example`
- `database/migrations/2026_06_28_130421_create_activity_log_table.php`
- `database/migrations/2026_06_28_130500_add_indexes_to_activity_log_table.php`

**Verification:**
```bash
composer audit          # no high/critical advisories
php artisan migrate
```

**Risks:** `FLARE_KEY` and `UPTIME_HEARTBEAT_TOKEN` not committed (by design).

---

## Phase 3 — Activitylog Implementation

**Summary:** `LogsAuditableChanges` trait logs only non-sensitive attributes. `AuditLogger` handles workflow events (submit, approve, reject, revert, settings, PDF exports) with PII redaction. Filament `ActivityLogResource` provides admin-only list, filters, CSV export. Indexes on `created_at`, causer, subject for 10k+ row performance.

**Verification:**
```bash
php artisan test --filter=ActivityLogTest
# Admin UI: /admin/activity-logs
```

**Risks:** spatie v5 removed `batch_uuid` (documented in package UPGRADING.md).

---

## Phase 4 — Flare Implementation

**Summary:** `Flare::handles()` registered when `FLARE_KEY` + `FLARE_REPORT=true`. 404 and 4xx HTTP exceptions filtered. Body/header censor list excludes hours, tasks, notes, passwords, auth secrets. Per-request context via `AttachFlareContext` middleware.

**Verification:**
```bash
# After setting FLARE_KEY in .env:
php artisan flare:test --errors
php artisan test --filter=FlareIntegrationTest
```

**Risks:** Pending credentials — set `FLARE_REPORT=false` until key is available.

---

## Phase 5 — Better Uptime Implementation

**Summary:** Signed heartbeat endpoints return 200 only when scheduler/queue recently succeeded. Scheduler writes cache every minute via `uptime:signal-heartbeat`; queue worker processes `RecordQueueHeartbeat` job every minute.

| Monitor | URL | Interval | Owner |
|---------|-----|----------|-------|
| Admin login | `https://timesheet.quatriz-sd.my/admin/login` | 3 min | Platform ops |
| Health | `https://timesheet.quatriz-sd.my/up` | 3 min | Platform ops |
| Scheduler | `https://timesheet.quatriz-sd.my/uptime/heartbeat?token=TOKEN` | 3 min | Platform ops |
| Queue | `https://timesheet.quatriz-sd.my/uptime/queue-heartbeat?token=TOKEN` | 3 min | Platform ops |

**Escalation contact:** security@skysyaz.my

**Verification:**
```bash
php artisan test --filter=UptimeHeartbeatTest
php artisan uptime:signal-heartbeat
curl "https://timesheet.quatriz-sd.my/uptime/heartbeat?token=TOKEN"
```

**Risks:** **Pending credentials** — `BETTER_UPTIME_ENABLED=false` until token + monitors configured.

---

## Phase 6 — Privacy, Security & Compliance

**Summary:** Passwords, tokens, hours, tasks, notes redacted in both Flare censor config and `AuditLogger`. 90-day retention via `activitylog:clean` daily schedule. Audit log UI restricted to `admin` role via `ActivityLogResource::canAccess()`.

**Verification:** `php artisan test --filter=ObservabilityAccessTest`

---

## Phase 7 — Testing

**Summary:** 132 tests, 0 failures, 0 skipped.

| Test file | Coverage |
|-----------|----------|
| `ActivityLogTest` | Model audit, PM approval, role change, redaction |
| `UptimeHeartbeatTest` | Token, stale/fresh, disabled state |
| `FlareIntegrationTest` | Censor config, HTTP sender, redaction parity |
| `ObservabilityAccessTest` | Admin-only audit UI |

**Verification:** `php artisan test && composer audit`

---

## Phase 8 — Deployment & Runbook

**Summary:** Full checklist, rollback plan, and ops procedures in `docs/OBSERVABILITY.md`.

**Production deploy steps:**
```bash
cd /var/www/timesheet
git pull   # or rsync release
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache
php artisan uptime:signal-heartbeat
```

**Rollback:** Disable `FLARE_REPORT` and `BETTER_UPTIME_ENABLED`, revert code, keep `activity_log` data.

---

## Phase 9 — Documentation

**Summary:** Updated `README.md` with observability quick-start and troubleshooting. Full runbook at `docs/OBSERVABILITY.md`.

---

## Pending credentials checklist

- [ ] `FLARE_KEY` from flareapp.io → set `FLARE_REPORT=true`
- [ ] Generate `UPTIME_HEARTBEAT_TOKEN` → set `BETTER_UPTIME_ENABLED=true`
- [ ] Create 4 Better Uptime monitors (URLs above)
- [ ] Add Better Uptime probe IPs to `HEALTH_CHECK_ALLOWED_IPS`
