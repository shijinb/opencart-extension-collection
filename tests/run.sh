#!/usr/bin/env bash
# Runs every test layer.
#
#   OC_VERSIONS="3.0.3.8 3.0.5.1" tests/run.sh
#
# 1. OCMOD harness: applies all extensions to each pristine OpenCart release (downloaded once into
#    .cache/opencart/<version>) and lints the result.
# 2. Unit tests (no OpenCart, no database).
# 3. Integration tests against MySQL, only when DB_HOST is set (see tests/integration/run.sh).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
VERSIONS="${OC_VERSIONS:-3.0.3.8 3.0.3.9 3.0.4.1 3.0.5.1}"
CACHE="$ROOT/.cache/opencart"
status=0

fetch() {
  local v="$1"
  if [ ! -f "$CACHE/$v/index.php" ]; then
    echo "Downloading OpenCart $v"
    mkdir -p "$CACHE/$v"
    curl -fsSL "https://codeload.github.com/opencart/opencart/tar.gz/refs/tags/$v" \
      | tar xz -C "$CACHE/$v" --strip-components=2 "opencart-$v/upload"
  fi
}

for v in $VERSIONS; do
  fetch "$v"
  echo "== OCMOD harness: OpenCart $v"
  php "$ROOT/tests/ocmod-harness.php" "$CACHE/$v" "$ROOT"/extensions/* | grep -vE '^  OK|^MOD' || status=1
  # grep hides OK lines; re-run quietly for the real exit code.
  php "$ROOT/tests/ocmod-harness.php" "$CACHE/$v" "$ROOT"/extensions/* >/dev/null || status=1
done

echo "== Unit tests"
for t in "$ROOT"/tests/unit/*-test.php; do
  php "$t" | grep -vE '^  ok' || true
  php "$t" >/dev/null || status=1
done

if [ -n "${DB_HOST:-}" ]; then
  for v in $VERSIONS; do
    echo "== Integration tests: OpenCart $v"
    "$ROOT/tests/integration/run.sh" "$CACHE/$v" | grep -vE '^  ok' || status=1
  done
else
  echo "== Integration tests skipped (set DB_HOST, DB_PORT, DB_USER, DB_PASSWORD to run them)"
fi

exit $status
