#!/usr/bin/env bash
# Model-level integration tests against a real MySQL database.
#
# Usage: tests/integration/run.sh <opencart-upload-dir>
# Needs: mysql client, PHP with mysqli, and DB_HOST / DB_PORT / DB_USER / DB_PASSWORD.
# Creates (and drops) the database ${DB_DATABASE:-oc_extension_test}.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
CORE="$(cd "$1" && pwd)"
export DB_HOST="${DB_HOST:-127.0.0.1}" DB_PORT="${DB_PORT:-3306}" DB_USER="${DB_USER:-root}" DB_PASSWORD="${DB_PASSWORD:-}"
export DB_DATABASE="${DB_DATABASE:-oc_extension_test}"

mysql_cmd() { MYSQL_PWD="$DB_PASSWORD" mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$@"; }

PATCHED="$(mktemp -d)"
trap 'rm -rf "$PATCHED"; mysql_cmd -e "DROP DATABASE IF EXISTS \`$DB_DATABASE\`" >/dev/null 2>&1 || true' EXIT

echo "Database: creating $DB_DATABASE from $CORE/install/opencart.sql"
mysql_cmd -e "DROP DATABASE IF EXISTS \`$DB_DATABASE\`; CREATE DATABASE \`$DB_DATABASE\` CHARACTER SET utf8mb4"
{
  echo "SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';"
  grep -v "^--" "$CORE/install/opencart.sql"  # the OpenCart installer skips these lines too
  sed 's/^--.*//' "$ROOT/extensions/search-only-product-tags/install.sql"
  cat "$ROOT/tests/integration/fixtures.sql"
} | mysql_cmd "$DB_DATABASE"

echo "Applying OCMOD modifications"
OCMOD_DUMP="$PATCHED" php "$ROOT/tests/ocmod-harness.php" "$CORE" "$ROOT"/extensions/* | tail -1

export OC_CORE="$CORE" OC_PATCHED="$PATCHED" OC_EXTENSIONS="$ROOT/extensions"
status=0
for suite in "$ROOT"/tests/integration/*-suite.php; do
  echo "Suite: $(basename "$suite")"
  php "$suite" || status=1
done
exit $status
