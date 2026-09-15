#!/usr/bin/env bash
#
# Local: dump the LOCAL dev DB and import it into the SERVER's database,
# REPLACING all server content. This is the opposite direction of
# pull-db.sh/import-db.sh (server -> local, safe, read-only on the
# server) — push-db.sh is destructive on the server.
#
# Only meant for the pre-launch phase, while the server holds nothing
# but your own dev/test content and a full override is fine. Once the
# site has real visitor-facing data, this script should not be used —
# delete it (or at least stop running it) at that point.
#
# Takes a server-side backup first (scripts/backup.sh) as a safety net,
# so an accidental run is still recoverable via scripts/ops/pull-db.sh.
#
# Reads SSH_HOST and APP_DIR from .deploy.env (see scripts/ops/deploy.sh).

set -euo pipefail

cd "$(dirname "$0")/../.."
[ -f .deploy.env ] && source .deploy.env

: "${SSH_HOST:?Set SSH_HOST in .deploy.env}"
APP_DIR="${APP_DIR:-/opt/the-saiged/app}"

echo "!! This OVERWRITES the server's database with your LOCAL dev data."
echo "!! Only run this before the server holds any real content."
read -p "Type the server host ($SSH_HOST) to confirm: " confirm
[ "$confirm" = "$SSH_HOST" ] || { echo "Aborted."; exit 1; }

TIMESTAMP=$(date -u +%Y%m%d-%H%M%S)
LOCAL_FILE="/tmp/the-saiged-push-$TIMESTAMP.sql.gz"
REMOTE_FILE="/tmp/the-saiged-push-$TIMESTAMP.sql.gz"

echo "==> Dumping local dev DB"
docker compose exec -T mysql sh -c \
    'mysqldump --single-transaction -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' \
    | gzip > "$LOCAL_FILE"

echo "==> Backing up server DB first (safety net)"
ssh "$SSH_HOST" "APP_DIR='$APP_DIR' bash '$APP_DIR/scripts/backup.sh'"

echo "==> Uploading dump"
scp "$LOCAL_FILE" "$SSH_HOST:$REMOTE_FILE"

echo "==> Importing into server DB (replaces all server content)"
ssh "$SSH_HOST" "APP_DIR='$APP_DIR' bash '$APP_DIR/scripts/import-db.sh' '$REMOTE_FILE'"

echo "==> Cleaning up"
ssh "$SSH_HOST" "rm -f '$REMOTE_FILE'"
rm -f "$LOCAL_FILE"

echo "==> Done — server DB now matches local dev data as of $TIMESTAMP"
