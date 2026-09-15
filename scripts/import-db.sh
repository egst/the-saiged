#!/usr/bin/env bash
#
# Server-side: import a gzipped DB dump, REPLACING the production
# database entirely (DROP + recreate first). Run via
# scripts/ops/push-db.sh over SSH, or directly on the server.
#
# Usage: scripts/import-db.sh <path/to/dump.sql.gz>

set -euo pipefail

APP_DIR="${APP_DIR:-/opt/the-saiged/app}"
cd "$APP_DIR"

FILE="${1:?Usage: $0 <path/to/dump.sql.gz>}"
[ -f "$FILE" ] || { echo "File not found: $FILE"; exit 1; }

COMPOSE=(docker compose -f docker-compose.yml -f docker-compose.prod.yml)

echo "==> Dropping + recreating production DB"
"${COMPOSE[@]}" exec -T mysql sh -c \
    'mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "DROP DATABASE IF EXISTS $MYSQL_DATABASE; CREATE DATABASE $MYSQL_DATABASE;"'

echo "==> Importing $FILE"
gunzip -c "$FILE" | "${COMPOSE[@]}" exec -T mysql sh -c \
    'mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'

echo "==> Imported"
