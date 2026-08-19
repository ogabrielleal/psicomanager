#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
FAIL=0
while IFS= read -r -d '' file; do
  if ! php -l "$file" >/dev/null; then FAIL=1; fi
done < <(find "$ROOT" -type f -name '*.php' -print0)
if [ "$FAIL" -ne 0 ]; then echo "[FAIL] PHP lint" >&2; exit 1; fi
echo "[OK] PHP lint"
