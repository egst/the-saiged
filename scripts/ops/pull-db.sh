#!/usr/bin/env bash
#
# Local: take a fresh DB backup on the server and download it to ./db-backups/.
# Reads SSH_HOST and APP_DIR from .deploy.env (see scripts/ops/deploy.sh).

set -euo pipefail

cd "$(dirname "$0")/../.."
[ -f .deploy.env ] && source .deploy.env

: "${SSH_HOST:?Set SSH_HOST in .deploy.env}"
APP_DIR="${APP_DIR:-/opt/the-saiged/app}"
LOCAL_DIR="${LOCAL_DIR:-./db-backups}"

mkdir -p "$LOCAL_DIR"

echo "==> Taking fresh backup on server"
ssh "$SSH_HOST" "APP_DIR='$APP_DIR' bash '$APP_DIR/scripts/backup.sh'"

echo "==> Locating latest backup file"
REMOTE_FILE=$(ssh "$SSH_HOST" "ls -t /var/backups/the-saiged/the-saiged-*.sql.gz | head -1")
echo "    $REMOTE_FILE"

echo "==> Downloading"
scp "$SSH_HOST:$REMOTE_FILE" "$LOCAL_DIR/"

echo "==> Done: $LOCAL_DIR/$(basename "$REMOTE_FILE")"
