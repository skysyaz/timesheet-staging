#!/usr/bin/env bash
# Deploy Quatriz TimeSheet to staging on the VPS (IP-only access).
# Usage: SSHPASS='...' ./scripts/deploy-staging-vps.sh
set -euo pipefail

HOST="${DEPLOY_HOST:-root@194.233.86.248}"
REMOTE_PATH="${STAGING_PATH:-/var/www/timesheet-staging}"
LOCAL_PATH="$(cd "$(dirname "$0")/.." && pwd)"

if ! command -v sshpass >/dev/null 2>&1; then
  echo "sshpass is required" >&2
  exit 1
fi

export SSHPASS="${SSHPASS:?Set SSHPASS to the VPS password}"

echo "Building assets..."
(cd "$LOCAL_PATH" && npm run build)

echo "Syncing files to staging ($REMOTE_PATH)..."
SSHPASS="$SSHPASS" sshpass -e rsync -az --delete \
  --exclude '.git' \
  --exclude 'node_modules' \
  --exclude 'vendor' \
  --exclude '.env' \
  --exclude 'storage/logs/*' \
  --exclude '.phpunit.result.cache' \
  --exclude 'tests' \
  -e "ssh -o StrictHostKeyChecking=no" \
  "$LOCAL_PATH/" "$HOST:$REMOTE_PATH/"

echo "Running remote staging deploy steps..."
SSHPASS="$SSHPASS" sshpass -e ssh -o StrictHostKeyChecking=no "$HOST" "bash -s" <<REMOTE
set -euo pipefail
cd "$REMOTE_PATH"

if [[ ! -f .env ]]; then
  echo "Missing $REMOTE_PATH/.env — run ./scripts/setup-staging-vps.sh first." >&2
  exit 1
fi

mkdir -p storage/framework/cache/tmp
composer install --no-dev --optimize-autoloader --no-interaction
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

if ! grep -q '^APP_KEY=base64:' .env; then
  sudo -u www-data php artisan key:generate --force
fi

sudo -u www-data php artisan migrate --force
php scripts/generate-favicons.php
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan view:clear
sudo -u www-data php artisan filament:optimize-clear
sudo -u www-data php artisan icons:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
systemctl restart timesheet-staging-queue 2>/dev/null || true
echo "Staging deploy complete."
REMOTE

STAGING_URL="${STAGING_URL:-http://194.233.86.248/admin/login}"
echo ""
echo "Staging URL: $STAGING_URL"
