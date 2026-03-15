#!/usr/bin/env bash
set -Eeuo pipefail

# Usage:
#   ./deploy.sh [branch]
#
# Optional environment overrides:
#   APP_DIR=/var/www/newburgh-lodge
#   WEB_USER=www-data
#   WEB_GROUP=www-data
#   PHP_BIN=php
#   COMPOSER_BIN=composer
#   NPM_BIN=npm
#   DEPLOY_BRANCH=main

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="${APP_DIR:-$SCRIPT_DIR}"
BRANCH="${1:-${DEPLOY_BRANCH:-main}}"
WEB_USER="${WEB_USER:-www-data}"
WEB_GROUP="${WEB_GROUP:-www-data}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"
LOCK_FILE="${LOCK_FILE:-/tmp/newburgh-lodge-deploy.lock}"

MAINTENANCE_MODE=0

log() {
  echo "==> $1"
}

cleanup() {
  if [[ "$MAINTENANCE_MODE" -eq 1 ]]; then
    log "Bringing application back up"
    "$PHP_BIN" artisan up || true
  fi
}

trap cleanup EXIT

require_command() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "Missing required command: $1" >&2
    exit 1
  fi
}

require_command git
require_command "$PHP_BIN"
require_command "$COMPOSER_BIN"
require_command "$NPM_BIN"
require_command flock

exec 9>"$LOCK_FILE"
if ! flock -n 9; then
  echo "Another deployment is currently running." >&2
  exit 1
fi

cd "$APP_DIR"

if [[ ! -f artisan ]]; then
  echo "Could not find artisan in $APP_DIR" >&2
  exit 1
fi

log "Entering maintenance mode"
"$PHP_BIN" artisan down --render="errors::503" || true
MAINTENANCE_MODE=1

log "Fetching latest code from origin/$BRANCH"
git fetch --prune origin "$BRANCH"

if git show-ref --verify --quiet "refs/heads/$BRANCH"; then
  git checkout "$BRANCH"
else
  git checkout -b "$BRANCH" "origin/$BRANCH"
fi

git reset --hard "origin/$BRANCH"

log "Installing PHP dependencies"
"$COMPOSER_BIN" install \
  --no-dev \
  --prefer-dist \
  --optimize-autoloader \
  --no-interaction

log "Installing Node dependencies"
if [[ -f package-lock.json ]]; then
  "$NPM_BIN" ci --no-audit --no-fund
else
  "$NPM_BIN" install --no-audit --no-fund
fi

log "Building frontend assets"
"$NPM_BIN" run build

log "Running database migrations"
"$PHP_BIN" artisan migrate --force

log "Warming application caches"
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan optimize

if "$PHP_BIN" artisan list --raw | grep -q '^queue:restart$'; then
  log "Restarting queue workers"
  "$PHP_BIN" artisan queue:restart
fi

log "Fixing writable permissions on storage and bootstrap/cache"
chown -R "$WEB_USER":"$WEB_GROUP" storage bootstrap/cache || true
chmod -R ug+rwX storage bootstrap/cache || true

log "Leaving maintenance mode"
"$PHP_BIN" artisan up
MAINTENANCE_MODE=0

log "Deployment complete"
