#!/usr/bin/env bash
# Ensure Laravel storage/bootstrap paths are writable by www-data.
# Tar deploys from Windows can leave storage/ read-only (mode 555).
set -euo pipefail

APP_PATH="${1:?Usage: $0 /var/www/timesheet-staging}"

mkdir -p \
  "$APP_PATH/storage/framework/cache/data" \
  "$APP_PATH/storage/framework/cache/tmp" \
  "$APP_PATH/storage/framework/sessions" \
  "$APP_PATH/storage/framework/views" \
  "$APP_PATH/storage/logs" \
  "$APP_PATH/bootstrap/cache"

chown -R www-data:www-data "$APP_PATH/storage" "$APP_PATH/bootstrap/cache"
chmod -R ug+rwX "$APP_PATH/storage" "$APP_PATH/bootstrap/cache"
find "$APP_PATH/storage" "$APP_PATH/bootstrap/cache" -type d -exec chmod 775 {} \;
find "$APP_PATH/storage" "$APP_PATH/bootstrap/cache" -type f -exec chmod 664 {} \;

echo "Permissions fixed for $APP_PATH"
