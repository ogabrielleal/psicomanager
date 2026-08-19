#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
if [ -f composer.lock ]; then
  command -v composer >/dev/null || { echo "[FAIL] composer.lock existe, mas composer audit não está disponível" >&2; exit 1; }
  composer audit --locked --no-interaction
else
  echo "[OK] Sem dependências Composer runtime para auditar."
fi
if [ -f package-lock.json ]; then
  command -v npm >/dev/null || { echo "[FAIL] package-lock.json existe, mas npm audit não está disponível" >&2; exit 1; }
  npm audit --omit=dev --audit-level=high
else
  echo "[OK] Sem dependências NPM runtime para auditar."
fi
