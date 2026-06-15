#!/usr/bin/env bash
#
# Server-side deploy. Run on the production server (manually or via SSH).
# Pulls the latest code from the `production` branch, rebuilds containers,
# backs up the database, applies migrations behind a blocked nginx, and
# resumes serving.

set -euo pipefail

APP_DIR="${APP_DIR:-/opt/the-saiged/app}"
cd "$APP_DIR"

COMPOSE=(docker compose -f docker-compose.yml -f docker-compose.prod.yml)

echo "==> Pulling production branch"
git fetch origin
git checkout production
git reset --hard origin/production

echo "==> Pre-deploy backup"
"$APP_DIR/scripts/backup.sh"

echo "==> Building images"
"${COMPOSE[@]}" build

echo "==> Blocking traffic (stop nginx)"
"${COMPOSE[@]}" stop nginx

echo "==> Recreating php with new image"
"${COMPOSE[@]}" up -d php

echo "==> Waiting for MySQL"
for i in $(seq 1 30); do
    if "${COMPOSE[@]}" exec -T mysql sh -c 'mysqladmin ping -u root -p"$MYSQL_ROOT_PASSWORD" --silent' 2>/dev/null; then
        break
    fi
    sleep 1
done

echo "==> Running migrations"
"${COMPOSE[@]}" exec -T php composer migrate

echo "==> Resuming traffic (recreate nginx with new image)"
"${COMPOSE[@]}" up -d

echo "==> Deploy complete"
