#!/usr/bin/env bash
#
# Renew Let's Encrypt cert (webroot challenge served by nginx). Run from
# root cron — typically once a day; certbot is a no-op if not due.
#
# Reloads nginx after a successful renewal so the new cert is picked up.

set -euo pipefail

APP_DIR="${APP_DIR:-/opt/the-saiged/app}"

certbot renew --webroot -w /var/www/certbot --quiet

# Reload nginx if it's running. The cert symlink (/etc/letsencrypt/live/current)
# is mounted into the container, so it sees the new cert files on reload.
cd "$APP_DIR"
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T nginx nginx -s reload 2>/dev/null || true
