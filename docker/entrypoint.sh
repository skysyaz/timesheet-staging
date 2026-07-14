#!/bin/sh
# Container entrypoint: mirrors the runtime steps of scripts/deploy-staging-vps.sh
# so the containerized app behaves the same as the native VPS deploy.
# Runs once per container start, before supervisor launches the processes.
set -e

echo "[entrypoint] Clearing stale caches..."
php artisan optimize:clear || true

echo "[entrypoint] Running database migrations..."
php artisan migrate --force

# Cache config and views (same as the VPS deploy). Routes are intentionally
# NOT cached: the /up health route is a closure and cannot be serialized.
echo "[entrypoint] Caching config and views..."
php artisan config:cache
php artisan view:cache

echo "[entrypoint] Generating favicons..."
php scripts/generate-favicons.php || echo "[entrypoint] favicon generation skipped"

# Recreate storage dirs at runtime: the persistent volume mounted at
# storage/app shadows anything created there at build time, so Livewire's
# temp-upload dir must be ensured here or "Attach" fails silently.
echo "[entrypoint] Ensuring storage directories exist and are writable..."
mkdir -p \
  storage/app/private/livewire-tmp \
  storage/app/public \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache
chmod -R 775 storage bootstrap/cache || true

echo "[entrypoint] Starting supervisor..."
exec "$@"
