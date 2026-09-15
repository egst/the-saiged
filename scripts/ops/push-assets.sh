#!/usr/bin/env bash
#
# Local: archive the local dev uploads directory (data/uploads) and
# import it into the SERVER's uploads volume, REPLACING all server
# content (deletes what's there first). Same shape and same pre-launch-
# only caveat as push-db.sh — see notes/deploy.md.
#
# Reads SSH_HOST and APP_DIR from .deploy.env (see scripts/ops/deploy.sh).

set -euo pipefail

cd "$(dirname "$0")/../.."
[ -f .deploy.env ] && source .deploy.env

: "${SSH_HOST:?Set SSH_HOST in .deploy.env}"
APP_DIR="${APP_DIR:-/opt/the-saiged/app}"

echo "!! This OVERWRITES the server's uploaded files with your LOCAL dev uploads."
echo "!! Only run this before the server holds any real uploaded content."
read -p "Type the server host ($SSH_HOST) to confirm: " confirm
[ "$confirm" = "$SSH_HOST" ] || { echo "Aborted."; exit 1; }

TIMESTAMP=$(date -u +%Y%m%d-%H%M%S)
LOCAL_FILE="/tmp/the-saiged-uploads-$TIMESTAMP.tar.gz"
REMOTE_FILE="/tmp/the-saiged-uploads-$TIMESTAMP.tar.gz"

echo "==> Archiving local uploads (data/uploads)"
tar czf "$LOCAL_FILE" -C data/uploads .

echo "==> Uploading archive"
scp "$LOCAL_FILE" "$SSH_HOST:$REMOTE_FILE"

echo "==> Importing into server uploads volume (replaces all server content)"
ssh "$SSH_HOST" "APP_DIR='$APP_DIR' bash '$APP_DIR/scripts/import-assets.sh' '$REMOTE_FILE'"

echo "==> Cleaning up"
ssh "$SSH_HOST" "rm -f '$REMOTE_FILE'"
rm -f "$LOCAL_FILE"

echo "==> Done — server uploads now match local dev data as of $TIMESTAMP"
