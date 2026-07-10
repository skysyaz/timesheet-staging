#!/usr/bin/env bash
# Dump Postgres for this app install and upload the gzip archive to Cloudflare R2.
#
# On the VPS (production):
#   CLOUDFLARE_API_TOKEN=... ./scripts/backup-db-to-r2.sh
# Or put CLOUDFLARE_API_TOKEN in /root/.config/timesheet/r2.env (chmod 600).
#
# Production VPS install (outside deploy rsync --delete path):
#   /usr/local/sbin/timesheet-backup-db-to-r2.sh
# Credentials: /root/.config/timesheet/r2.env (chmod 600)
# Cron (Malaysia time):
#   CRON_TZ=Asia/Kuala_Lumpur
#   0 5 * * * /usr/local/sbin/timesheet-backup-db-to-r2.sh >> /var/log/timesheet-db-backup.log 2>&1
set -euo pipefail

APP_PATH="${APP_PATH:-/var/www/timesheet}"
R2_ACCOUNT_ID="${R2_ACCOUNT_ID:-3cb921ee9ff93956079a49b5121f645e}"
R2_BUCKET="${R2_BUCKET:-timesheet}"
R2_PREFIX="${R2_PREFIX:-backups}"
CRED_FILE="${R2_CREDENTIALS_FILE:-/root/.config/timesheet/r2.env}"
LOG_PREFIX="[timesheet-db-backup]"

if [[ -f "$CRED_FILE" ]]; then
  set -a
  # shellcheck disable=SC1090
  source "$CRED_FILE"
  set +a
fi

TOKEN="${CLOUDFLARE_API_TOKEN:-${CF_API_TOKEN:-}}"
if [[ -z "$TOKEN" ]]; then
  echo "$LOG_PREFIX CLOUDFLARE_API_TOKEN is required (env or $CRED_FILE)" >&2
  exit 1
fi

if [[ ! -f "$APP_PATH/.env" ]]; then
  echo "$LOG_PREFIX Missing $APP_PATH/.env" >&2
  exit 1
fi

env_get() {
  local key="$1"
  grep -E "^${key}=" "$APP_PATH/.env" | head -n1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//"
}

DB_CONNECTION="$(env_get DB_CONNECTION)"
if [[ "$DB_CONNECTION" != "pgsql" ]]; then
  echo "$LOG_PREFIX Expected DB_CONNECTION=pgsql, got '${DB_CONNECTION:-empty}'" >&2
  exit 1
fi

DB_HOST="$(env_get DB_HOST)"
DB_PORT="$(env_get DB_PORT)"
DB_DATABASE="$(env_get DB_DATABASE)"
DB_USERNAME="$(env_get DB_USERNAME)"
DB_PASSWORD="$(env_get DB_PASSWORD)"

: "${DB_HOST:?Missing DB_HOST}"
: "${DB_PORT:?Missing DB_PORT}"
: "${DB_DATABASE:?Missing DB_DATABASE}"
: "${DB_USERNAME:?Missing DB_USERNAME}"

STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
OBJECT_KEY="${R2_PREFIX}/${DB_DATABASE}-${STAMP}.sql.gz"
TMP_DIR="$(mktemp -d /tmp/timesheet-db-backup.XXXXXX)"
DUMP_FILE="${TMP_DIR}/${DB_DATABASE}-${STAMP}.sql.gz"
RESPONSE_FILE="${TMP_DIR}/r2-response.json"

cleanup() {
  rm -rf "$TMP_DIR"
}
trap cleanup EXIT

echo "$LOG_PREFIX Starting dump of ${DB_DATABASE} at ${STAMP}"

export PGPASSWORD="${DB_PASSWORD:-}"
pg_dump \
  -h "$DB_HOST" \
  -p "$DB_PORT" \
  -U "$DB_USERNAME" \
  -d "$DB_DATABASE" \
  --no-owner \
  --no-acl \
  | gzip -c > "$DUMP_FILE"

SIZE_BYTES="$(wc -c < "$DUMP_FILE" | tr -d ' ')"
echo "$LOG_PREFIX Dump ready (${SIZE_BYTES} bytes) → r2://${R2_BUCKET}/${OBJECT_KEY}"

HTTP_CODE="$(
  curl -sS -o "$RESPONSE_FILE" -w '%{http_code}' \
    -X PUT \
    "https://api.cloudflare.com/client/v4/accounts/${R2_ACCOUNT_ID}/r2/buckets/${R2_BUCKET}/objects/${OBJECT_KEY}" \
    -H "Authorization: Bearer ${TOKEN}" \
    -H 'Content-Type: application/gzip' \
    --data-binary @"${DUMP_FILE}"
)"

if [[ "$HTTP_CODE" != "200" ]]; then
  echo "$LOG_PREFIX Upload failed (HTTP ${HTTP_CODE})" >&2
  cat "$RESPONSE_FILE" >&2 || true
  exit 1
fi

if ! grep -q '"success":true' "$RESPONSE_FILE"; then
  echo "$LOG_PREFIX Upload response not successful:" >&2
  cat "$RESPONSE_FILE" >&2 || true
  exit 1
fi

echo "$LOG_PREFIX Upload complete: ${OBJECT_KEY} (${SIZE_BYTES} bytes)"
