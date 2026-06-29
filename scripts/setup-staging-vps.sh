#!/usr/bin/env bash
# One-time staging environment setup on the VPS (IP-only HTTP access).
# Usage: SSHPASS='...' ./scripts/setup-staging-vps.sh
set -euo pipefail

HOST="${DEPLOY_HOST:-root@194.233.86.248}"
STAGING_PATH="${STAGING_PATH:-/var/www/timesheet-staging}"
STAGING_IP="${STAGING_IP:-194.233.86.248}"
PRODUCTION_PATH="${PRODUCTION_PATH:-/var/www/timesheet}"
STAGING_DB="${STAGING_DB:-timesheet_staging}"

if ! command -v sshpass >/dev/null 2>&1; then
  echo "sshpass is required" >&2
  exit 1
fi

export SSHPASS="${SSHPASS:?Set SSHPASS to the VPS password}"

echo "Setting up staging on $HOST..."

SSHPASS="$SSHPASS" sshpass -e ssh -o StrictHostKeyChecking=no "$HOST" "bash -s" <<REMOTE
set -euo pipefail

STAGING_PATH="$STAGING_PATH"
STAGING_IP="$STAGING_IP"
PRODUCTION_PATH="$PRODUCTION_PATH"
STAGING_DB="$STAGING_DB"

if [[ ! -f "\$PRODUCTION_PATH/.env" ]]; then
  echo "Production .env not found at \$PRODUCTION_PATH/.env" >&2
  exit 1
fi

DB_PASSWORD=\$(grep '^DB_PASSWORD=' "\$PRODUCTION_PATH/.env" | cut -d= -f2- | sed 's/^"//;s/"$//')
DB_USERNAME=\$(grep '^DB_USERNAME=' "\$PRODUCTION_PATH/.env" | cut -d= -f2- | sed 's/^"//;s/"$//')
DB_HOST=\$(grep '^DB_HOST=' "\$PRODUCTION_PATH/.env" | cut -d= -f2- | sed 's/^"//;s/"$//')
DB_PORT=\$(grep '^DB_PORT=' "\$PRODUCTION_PATH/.env" | cut -d= -f2- | sed 's/^"//;s/"$//')

echo "Creating staging database (if needed)..."
PGPASSWORD="\$DB_PASSWORD" psql -U "\$DB_USERNAME" -h "\$DB_HOST" -p "\$DB_PORT" -d postgres -tc \\
  "SELECT 1 FROM pg_database WHERE datname = '\$STAGING_DB'" | grep -q 1 \\
  || PGPASSWORD="\$DB_PASSWORD" psql -U "\$DB_USERNAME" -h "\$DB_HOST" -p "\$DB_PORT" -d postgres -c \\
    "CREATE DATABASE \$STAGING_DB OWNER \$DB_USERNAME;"

echo "Creating staging directory..."
mkdir -p "\$STAGING_PATH/storage/framework/cache/tmp"
mkdir -p "\$STAGING_PATH/storage/logs"
mkdir -p "\$STAGING_PATH/bootstrap/cache"
chown -R www-data:www-data "\$STAGING_PATH/storage" "\$STAGING_PATH/bootstrap/cache" 2>/dev/null || true
chmod -R 775 "\$STAGING_PATH/storage" "\$STAGING_PATH/bootstrap/cache" 2>/dev/null || true

if [[ ! -s "\$STAGING_PATH/.env" ]] || ! grep -q '^APP_URL=' "\$STAGING_PATH/.env" 2>/dev/null; then
  echo "Writing staging .env..."
  cat > "\$STAGING_PATH/.env" <<ENV
APP_NAME="Quatriz TimeSheet (Staging)"
APP_ENV=staging
APP_KEY=
APP_DEBUG=true
APP_URL=http://\$STAGING_IP
APP_TIMEZONE=Asia/Kuala_Lumpur

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=pgsql
DB_HOST=\$DB_HOST
DB_PORT=\$DB_PORT
DB_DATABASE=\$STAGING_DB
DB_USERNAME=\$DB_USERNAME
DB_PASSWORD=\$DB_PASSWORD

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=false
SESSION_ENCRYPT=false
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

SECURITY_CSP_REPORT_ONLY=true
SECURITY_CSP_ENFORCE=false
SECURITY_COOP=same-origin
SECURITY_CORP=same-origin
UI_CONSISTENT_BUTTONS=true

MAIL_MAILER=log
MAIL_FROM_ADDRESS=staging@localhost
MAIL_FROM_NAME="Quatriz TimeSheet (Staging)"

VITE_APP_NAME="Quatriz TimeSheet (Staging)"

FLARE_REPORT=false
FLARE_TENANT_TAG=quatriz-staging
ACTIVITYLOG_ENABLED=true
SITE_TRAFFIC_ENABLED=true
TIMESHEET_NOTIFICATIONS_QUEUE=true
BETTER_UPTIME_ENABLED=false
MFA_REQUIRED_FOR_ADMIN=false
ENV
  chown www-data:www-data "\$STAGING_PATH/.env"
  chmod 640 "\$STAGING_PATH/.env"
fi

CADDYFILE="/etc/caddy/Caddyfile"
if ! grep -q 'BEGIN timesheet-staging' "\$CADDYFILE"; then
  echo "Adding Caddy staging site block..."
  cat >> "\$CADDYFILE" <<CADDY

# BEGIN timesheet-staging
http://${STAGING_IP} {
    root * ${STAGING_PATH}/public
    encode gzip zstd

    php_fastcgi unix//run/php/php8.4-fpm.sock {
        resolve_root_symlink
    }

    file_server
    try_files {path} {path}/ /index.php?{query}
}
# END timesheet-staging
CADDY
  caddy validate --config "\$CADDYFILE"
  systemctl reload caddy
fi

QUEUE_UNIT="/etc/systemd/system/timesheet-staging-queue.service"
if [[ ! -f "\$QUEUE_UNIT" ]]; then
  echo "Creating staging queue worker..."
  cat > "\$QUEUE_UNIT" <<UNIT
[Unit]
Description=Quatriz Timesheet Staging Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=5
WorkingDirectory=\$STAGING_PATH
Environment=TMPDIR=\$STAGING_PATH/storage/framework/cache/tmp
ExecStart=/usr/bin/php artisan queue:work database --sleep=3 --tries=3 --max-time=3600
StandardOutput=append=\$STAGING_PATH/storage/logs/queue.log
StandardError=append=\$STAGING_PATH/storage/logs/queue.log

[Install]
WantedBy=multi-user.target
UNIT
  systemctl daemon-reload
  systemctl enable --now timesheet-staging-queue
fi

CRON_LINE="* * * * * cd \$STAGING_PATH && php artisan schedule:run >> /dev/null 2>&1"
if ! crontab -l 2>/dev/null | grep -Fq "\$STAGING_PATH"; then
  echo "Adding staging scheduler cron..."
  (crontab -l 2>/dev/null; echo "\$CRON_LINE") | crontab -
fi

echo "Staging infrastructure ready at \$STAGING_PATH"
echo "Next: ./scripts/deploy-staging-vps.sh"
REMOTE

echo ""
echo "Setup complete. Deploy code with: SSHPASS='...' ./scripts/deploy-staging-vps.sh"
echo "Staging URL: http://${STAGING_IP}/login"
