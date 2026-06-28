# Quatriz TimeSheet

Admin-facing weekly timesheet application built with **Laravel 13**, **Filament 5**, and **PostgreSQL**.

- **Production:** https://timesheet.quatriz-sd.my
- **Staging (VPS IP):** http://194.233.86.248/admin/login
- **Admin panel:** `/admin`
- **PHP:** ^8.3 (8.4 on VPS)
- **Hosting:** VPS with Caddy + PHP-FPM (`/var/www/timesheet`)

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install && npm run build
php artisan serve
```

Run the scheduler and queue in separate terminals for full parity with production:

```bash
php artisan schedule:work
php artisan queue:work
```

## Testing

```bash
php artisan test
composer audit
```

## Observability

This app integrates three production monitoring tools:

| Tool | Purpose |
|------|---------|
| [Flare](https://flareapp.io) | Exception and error tracking |
| [spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog) | Admin audit trail |
| [Better Uptime](https://betteruptime.com) | Uptime + scheduler/queue heartbeats |

**Full setup, env vars, monitor URLs, and ops runbook:** [docs/OBSERVABILITY.md](docs/OBSERVABILITY.md)

### Quick start (observability)

1. Copy observability vars from `.env.example`
2. `php artisan migrate` (creates `activity_log` table)
3. Flare: set `FLARE_KEY` + `FLARE_REPORT=true`, then `php artisan flare:test --errors`
4. Better Uptime: set `BETTER_UPTIME_ENABLED=true`, generate `UPTIME_HEARTBEAT_TOKEN`, create monitors (see runbook)
5. Audit log: log in as admin → **Administration → Audit Log**

### Troubleshooting

| Symptom | Check |
|---------|-------|
| No Flare events | `FLARE_KEY` set, `FLARE_REPORT=true`, `php artisan config:clear` |
| Heartbeat 503 | Cron running? `php artisan schedule:run` · Queue worker up? |
| Heartbeat 403 | `UPTIME_HEARTBEAT_TOKEN` matches monitor URL query string |
| Audit log empty | `ACTIVITYLOG_ENABLED=true`, migration applied |
| `/up` fails from monitor | Add probe IP to `HEALTH_CHECK_ALLOWED_IPS` |

## Security

- Security contact: see `/.well-known/security.txt`
- Production: `APP_DEBUG=false`, `SESSION_ENCRYPT=true`, CSP enforcement after verification

## License

MIT
