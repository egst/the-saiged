#!/usr/bin/env bash
#
# Server-side: replace the uploads volume's contents entirely with the
# contents of a given tar.gz archive. Run via
# scripts/ops/push-assets.sh over SSH, or directly on the server.
#
# Usage: scripts/import-assets.sh <path/to/uploads.tar.gz>

set -euo pipefail

APP_DIR="${APP_DIR:-/opt/the-saiged/app}"
cd "$APP_DIR"

FILE="${1:?Usage: $0 <path/to/uploads.tar.gz>}"
[ -f "$FILE" ] || { echo "File not found: $FILE"; exit 1; }

COMPOSE=(docker compose -f docker-compose.yml -f docker-compose.prod.yml)

echo "==> Clearing existing uploads volume contents"
"${COMPOSE[@]}" exec -T php sh -c 'find /var/www/the-saiged/data/uploads -mindepth 1 -delete'

echo "==> Extracting $FILE into the uploads volume"
"${COMPOSE[@]}" exec -T php sh -c 'tar xzf - -C /var/www/the-saiged/data/uploads' < "$FILE"

# tar's archived "." entry can carry the source directory's own mode onto
# the mount root — nginx (running as its own user, not the file owner)
# then can't even traverse into it, no matter what the contents look like.
echo "==> Normalizing uploads volume root permission"
"${COMPOSE[@]}" exec -T php sh -c 'chmod 755 /var/www/the-saiged/data/uploads'

echo "==> Done"
