#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
echo "== PsicoManager AI Security Gate =="
bash scripts/check-php-syntax.sh
php scripts/check-placeholders.php
php scripts/check-tenant-scope.php
php scripts/check-secrets.php
php scripts/check-permissions.php
php scripts/check-csrf.php
php scripts/check-public-hardening.php
bash scripts/check-dependencies.sh
php scripts/check-security-headers.php
php tests/run.php
echo "[PASS] Gate de segurança concluído sem falhas bloqueantes."
