#!/usr/bin/env bash
# Purge Cloudflare edge cache for favicon assets after deploy.
#
# Usage (from project root or on VPS with .env loaded):
#   CF_API_TOKEN=... CF_ZONE_ID=... ./scripts/purge-cloudflare-cache.sh
#
# Find zone ID (quatriz-sd.my is a separate zone from skysyaz.my):
#   curl -s -H "Authorization: Bearer $CF_API_TOKEN" \
#     "https://api.cloudflare.com/client/v4/zones?name=quatriz-sd.my" | jq -r '.result[0].id'
#
# Dashboard purge (no API token): Cloudflare → quatriz-sd.my → Caching →
#   Purge by URL → https://timesheet.quatriz-sd.my/favicon.ico

set -euo pipefail

TOKEN="${CF_API_TOKEN:-}"
ZONE_ID="${CF_ZONE_ID:-}"
BASE_URL="${CF_PURGE_BASE_URL:-https://timesheet.quatriz-sd.my}"

if [[ -z "$TOKEN" || -z "$ZONE_ID" ]]; then
  echo "Skipping Cloudflare purge (set CF_API_TOKEN and CF_ZONE_ID to enable)."
  exit 0
fi

FILES=(
  "${BASE_URL}/favicon.ico"
  "${BASE_URL}/branding/favicon.ico"
  "${BASE_URL}/branding/favicon-16x16.png"
  "${BASE_URL}/branding/favicon-32x32.png"
  "${BASE_URL}/branding/apple-touch-icon.png"
  "${BASE_URL}/apple-touch-icon.png"
  "${BASE_URL}/site.webmanifest"
)

payload=$(printf '"%s",' "${FILES[@]}")
payload="{\"files\":[${payload%,}]}"

echo "Purging Cloudflare cache for favicon URLs..."
curl -sf -X POST "https://api.cloudflare.com/client/v4/zones/${ZONE_ID}/purge_cache" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  --data "$payload" | jq -r '.success, .errors // empty'

echo "Cloudflare purge complete."
