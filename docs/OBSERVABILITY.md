# Observability Runbook — Quatriz TimeSheet

Production admin timesheet app with three integrated tools:

| Tool | Package / service | Purpose |
|------|-------------------|---------|
| **Flare** | `spatie/laravel-flare` | Exception and error reporting |
| **Activity log** | `spatie/laravel-activitylog` | Admin/user audit trail |
| **Better Uptime** | External monitors + signed heartbeats | Uptime and cron/queue liveness |

**Stack:** Laravel 13, PHP 8.4, PostgreSQL, VPS (Caddy + PHP-FPM) at `/var/www/timesheet`  
**Production URL:** https://timesheet.quatriz-sd.my

---

## Environment variables

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `FLARE_KEY` | For Flare | *(empty)* | Flare project API key |
| `FLARE_REPORT` | No | `false` | Enable sending errors to Flare |
| `FLARE_TRACE` | No | `true` | Enable Flare request tracing |
| `FLARE_SAMPLER_RATE` | No | `0.1` | Trace sampling rate (0–1) |
| `FLARE_TENANT_TAG` | No | `quatriz` | Custom tag on every Flare report |
| `ACTIVITYLOG_ENABLED` | No | `true` | Master switch for audit logging |
| `ACTIVITYLOG_RETENTION_DAYS` | No | `90` | Purge entries older than N days |
| `BETTER_UPTIME_ENABLED` | No | `false` | Enable heartbeat endpoints |
| `UPTIME_HEARTBEAT_TOKEN` | For uptime | *(empty)* | Shared secret for monitor URLs |
| `UPTIME_SCHEDULER_STALE_MINUTES` | No | `5` | Scheduler heartbeat TTL |
| `UPTIME_QUEUE_STALE_MINUTES` | No | `5` | Queue worker heartbeat TTL |
| `HEALTH_CHECK_ALLOWED_IPS` | Recommended | *(empty)* | Comma-separated IPs for `/up` |

Generate a heartbeat token:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

---

## Deployment checklist

1. **Composer**
   ```bash
   composer install --no-dev --optimize-autoloader
   composer audit
   ```

2. **Environment**
   - Copy new vars from `.env.example` into production `.env`
   - Set `FLARE_KEY` and `FLARE_REPORT=true` when Flare credentials are ready
   - Set `BETTER_UPTIME_ENABLED=true` and `UPTIME_HEARTBEAT_TOKEN` when monitors are ready
   - Add Better Uptime probe IPs to `HEALTH_CHECK_ALLOWED_IPS` for `/up`

3. **Database**
   ```bash
   php artisan migrate --force
   ```

4. **Scheduler** (must run every minute on VPS)
   ```bash
   * * * * * cd /var/www/timesheet && php artisan schedule:run >> /dev/null 2>&1
   ```

5. **Queue worker** (required for queue heartbeat job)
   ```bash
   php artisan queue:work --sleep=3 --tries=3
   ```

6. **Cache config**
   ```bash
   php artisan config:cache
   php artisan route:cache
   ```

7. **Verify**
   ```bash
   php artisan test
   php artisan uptime:signal-heartbeat
   curl -s -H "X-Uptime-Token: YOUR_TOKEN" "https://timesheet.quatriz-sd.my/uptime/heartbeat"
   php artisan flare:test --errors   # after FLARE_KEY is set
   ```

### Rollback plan

1. Set `FLARE_REPORT=false` and `BETTER_UPTIME_ENABLED=false`
2. `php artisan config:clear && php artisan config:cache`
3. Revert code to previous release tag
4. Do **not** roll back `activity_log` migration if audit data must be preserved

---

## Better Uptime monitors

Create these monitors in [Better Uptime / Better Stack](https://betteruptime.com).  
**Owner:** Platform ops · **Escalation:** security@skysyaz.my · **Interval:** ≤ 5 minutes

| Monitor | URL | Expected | Notes |
|---------|-----|----------|-------|
| Admin login | `https://timesheet.quatriz-sd.my/admin/login` | HTTP 200 | Public page |
| Health check | `https://timesheet.quatriz-sd.my/up` | HTTP 200 | Restrict via `HEALTH_CHECK_ALLOWED_IPS` |
| Scheduler heartbeat | `https://timesheet.quatriz-sd.my/uptime/heartbeat` | HTTP 200 | 503 if cron stopped. Send the token as an `X-Uptime-Token` request header (Better Stack heartbeats support custom headers); **do not** put it in the query string |
| Queue heartbeat | `https://timesheet.quatriz-sd.my/uptime/queue-heartbeat` | HTTP 200 | 503 if queue worker down. Token via `X-Uptime-Token` header |

**DNS / firewall:** Monitors must reach the app over HTTPS (port 443). Allow Better Uptime probe IPs through Cloudflare/WAF if `/up` is IP-restricted.

**Status:** Pending credentials — set `BETTER_UPTIME_ENABLED=true` after monitors are configured.

---

## Flare

### Setup

1. Create a project at [flareapp.io](https://flareapp.io)
2. Add `FLARE_KEY` to `.env`, set `FLARE_REPORT=true`
3. Run `php artisan flare:test --errors` — event should appear within 60 seconds

### Context attached to reports

- `tenant`, `application`, `environment`, `request_id`
- `user_id`, `user_role` (when authenticated)

### Redacted fields (never sent to Flare)

`password`, `hours`, `tasks`, `notes`, `salary`, `rate`, auth secrets, `Authorization` header

### Ignored exceptions

- `404 Not Found`
- HTTP exceptions with status &lt; 500

### Ops: triaging a Flare spike

1. Open Flare → sort by occurrence count and first seen
2. Check `environment` and `request_id` context
3. Correlate with deploy time and Better Uptime incidents
4. If PII leak suspected: verify `config/flare.php` censor list, redeploy, mark occurrence resolved
5. For noise: add class to `Flare::filterExceptionsUsing` in `bootstrap/app.php`

---

## Activity log

### What is logged

| Source | Events |
|--------|--------|
| **Models** (`LogsAuditableChanges`) | User (name, email, role), Timesheet (status, project, week — not hours/tasks), Project (code, name, status, PM/PD) |
| **Manual** (`AuditLogger`) | Submit, approve, reject, revert, settings save, PDF exports |

### Admin UI

Filament → **Administration → Audit Log** (`/admin/activity-logs`) — **admin role only**

Filters: log name, subject type, date range. CSV export via header action.

### Retention

Daily `activitylog:clean` removes entries older than `ACTIVITYLOG_RETENTION_DAYS` (default 90).

### Ops: restoring / replaying entries

Activity log is append-only. To investigate:

```sql
SELECT * FROM activity_log WHERE causer_id = ? ORDER BY created_at DESC LIMIT 50;
```

There is no replay mechanism — use entries for forensic audit only.

---

## Scheduled tasks

| Task | Schedule | Purpose |
|------|----------|---------|
| `uptime:signal-heartbeat` | Every minute | Writes scheduler cache key |
| `RecordQueueHeartbeat` job | Every minute | Dispatched to queue; worker writes queue cache key |
| `activitylog:clean` | Daily | Retention purge |

---

## Verification commands

```bash
# Full test suite
php artisan test

# Security audit
composer audit

# Heartbeat (local)
php artisan uptime:signal-heartbeat
curl -H "X-Uptime-Token: YOUR_TOKEN" "http://localhost:8000/uptime/heartbeat"

# Flare (requires key)
php artisan flare:test --errors

# Audit log migration
php artisan migrate:status | grep activity_log
```

---

## Files reference

| Path | Role |
|------|------|
| `config/flare.php` | Flare SDK, censor list |
| `config/activitylog.php` | Retention, enable flag |
| `config/observability.php` | Uptime + tenant tags |
| `app/Support/AuditLogger.php` | Manual audit + redaction |
| `app/Models/Concerns/LogsAuditableChanges.php` | Model trait |
| `app/Filament/Resources/ActivityLogResource.php` | Admin audit UI |
| `app/Http/Controllers/UptimeHeartbeatController.php` | Heartbeat endpoints |
| `app/Console/Commands/SignalUptimeHeartbeat.php` | Scheduler signal |
| `app/Jobs/RecordQueueHeartbeat.php` | Queue liveness |
| `app/Http/Middleware/AttachFlareContext.php` | Per-request Flare context |
| `bootstrap/app.php` | Flare exception handler, `/up` health |
| `routes/console.php` | Schedule definitions |
| `routes/web.php` | Uptime routes |
