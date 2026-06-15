#!/usr/bin/env bash
#
# Local: import a gzipped DB dump (typically from db-backups/) into the
# local dev mysql container. DROPS the local database first.
#
# Usage: scripts/ops/import-db.sh db-backups/the-saiged-XXX.sql.gz

set -euo pipefail

cd "$(dirname "$0")/../.."

FILE="${1:-}"
if [ -z "$FILE" ] || [ ! -f "$FILE" ]; then
    echo "Usage: $0 <path/to/dump.sql.gz>"
    echo
    echo "Available dumps:"
    ls -1t db-backups/the-saiged-*.sql.gz 2>/dev/null || echo "  (none)"
    exit 1
fi

read -p "This will REPLACE local DB content. Continue? [y/N] " ans
[[ "$ans" =~ ^[Yy]$ ]] || exit 0

echo "==> Dropping + recreating local DB"
docker compose exec -T mysql sh -c \
    'mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "DROP DATABASE IF EXISTS $MYSQL_DATABASE; CREATE DATABASE $MYSQL_DATABASE;"'

echo "==> Importing $FILE"
gunzip -c "$FILE" | docker compose exec -T mysql sh -c \
    'mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'

echo "==> Imported"
