#!/usr/bin/env bash
#
# Server-side DB backup. Dumps the_saiged database to a gzipped file under
# $BACKUP_DIR (default /var/backups/the-saiged). Keeps last 14 days.

set -euo pipefail

APP_DIR="${APP_DIR:-/opt/the-saiged/app}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/the-saiged}"

mkdir -p "$BACKUP_DIR"
cd "$APP_DIR"

TIMESTAMP=$(date -u +%Y%m%d-%H%M%S)
FILE="$BACKUP_DIR/the-saiged-$TIMESTAMP.sql.gz"

COMPOSE=(docker compose -f docker-compose.yml -f docker-compose.prod.yml)

"${COMPOSE[@]}" exec -T mysql sh -c \
    'mysqldump --single-transaction -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' \
    | gzip > "$FILE"

# Keep last 14 days only
find "$BACKUP_DIR" -name 'the-saiged-*.sql.gz' -mtime +14 -delete

echo "Backup written: $FILE"
