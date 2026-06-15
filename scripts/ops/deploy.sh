#!/usr/bin/env bash
#
# Local: trigger a production deploy by running scripts/deploy.sh on the
# server via SSH.
#
# Requires .deploy.env at repo root (gitignored) with:
#   SSH_HOST=user@server
#   APP_DIR=/opt/the-saiged/app   # optional, defaults to this

set -euo pipefail

cd "$(dirname "$0")/../.."
[ -f .deploy.env ] && source .deploy.env

: "${SSH_HOST:?Set SSH_HOST in .deploy.env}"
APP_DIR="${APP_DIR:-/opt/the-saiged/app}"

ssh "$SSH_HOST" "APP_DIR='$APP_DIR' bash '$APP_DIR/scripts/deploy.sh'"
