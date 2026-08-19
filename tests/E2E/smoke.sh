#!/usr/bin/env bash
set -euo pipefail
BASE_URL="${1:-${BASE_URL:-}}"
if [ -z "$BASE_URL" ]; then echo "Uso: tests/E2E/smoke.sh https://dominio/psico"; exit 2; fi
for path in / /login.php /portal/login.php /saas/login.php; do
  code=$(curl -ksS -o /dev/null -w '%{http_code}' "${BASE_URL%/}${path}")
  case "$code" in 200|301|302) echo "PASS $path -> $code";; *) echo "FAIL $path -> $code" >&2; exit 1;; esac
done
