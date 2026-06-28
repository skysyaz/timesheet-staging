#!/usr/bin/env bash
# Apply Cloudflare WAF / rate-limit rules for timesheet.skysyaz.my
#
# Prerequisites:
#   1. API token with Zone:Read, WAF:Edit, Firewall Services:Edit
#   2. export CF_API_TOKEN="..."
#   3. export CF_ZONE_ID="..."  # Zone ID for skysyaz.my
#
# Find zone ID:
#   curl -s -H "Authorization: Bearer $CF_API_TOKEN" \
#     "https://api.cloudflare.com/client/v4/zones?name=skysyaz.my" | jq -r '.result[0].id'

set -euo pipefail

ZONE_ID="${CF_ZONE_ID:-}"
TOKEN="${CF_API_TOKEN:-}"
DOMAIN="${CF_DOMAIN:-skysyaz.my}"

if [[ -z "$TOKEN" || -z "$ZONE_ID" ]]; then
  echo "Missing CF_API_TOKEN or CF_ZONE_ID."
  echo ""
  echo "Manual dashboard steps (Cloudflare → skysyaz.my):"
  echo "  1. Security → WAF → Managed rules → Enable OWASP Core Ruleset"
  echo "  2. Security → WAF → Custom rules → Block known bad bots on /admin*"
  echo "  3. Security → Bots → Bot Fight Mode → ON"
  echo "  4. Security → Settings → Security Level → High"
  echo "  5. SSL/TLS → Full (strict)"
  exit 1
fi

API="https://api.cloudflare.com/client/v4/zones/${ZONE_ID}"

# Free plan: Cloudflare Managed Free Ruleset. Paid plans can swap in OWASP Core Ruleset.
MANAGED_RULESET_ID="${CF_MANAGED_RULESET_ID:-77454fe2d30c4220b5701f6fdfb893ba}"

echo "Enabling Cloudflare managed WAF ruleset (${MANAGED_RULESET_ID})..."
curl -sf -X PUT "${API}/rulesets/phases/http_request_firewall_managed/entrypoint" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  --data "$(cat <<EOF
{
  "rules": [
    {
      "action": "execute",
      "expression": "true",
      "action_parameters": {
        "id": "${MANAGED_RULESET_ID}"
      },
      "description": "Cloudflare Managed WAF Ruleset",
      "enabled": true
    }
  ]
}
EOF
)" | jq -r '.success, .errors // empty'

echo "Creating custom WAF rule: challenge suspicious admin login traffic..."
curl -sf -X POST "${API}/rulesets" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  --data "{
    \"name\": \"Timesheet admin protection\",
    \"kind\": \"zone\",
    \"phase\": \"http_request_firewall_custom\",
    \"rules\": [
      {
        \"action\": \"managed_challenge\",
        \"expression\": \"(http.request.uri.path contains \\\"/admin/login\\\") and cf.threat_score gt 10\",
        \"description\": \"Challenge high-threat admin login attempts\",
        \"enabled\": true
      }
    ]
  }" | jq -r '.success, .errors // empty'

echo "Done. Verify in Cloudflare dashboard → Security → WAF."
