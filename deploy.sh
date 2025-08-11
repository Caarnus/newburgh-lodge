#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/lodge"
BRANCH="${1:-main}"
WEB_USER="www-data"
WEB_GROUP="www-data"

cd "$APP_DIR"

echo "==> Entering maintenance mode"
php artisan down --render="errors::503" || true

echo "==> Fetching latest $BRANCH"
git fetch origin "$BRANCH"
git checkout -f "$BRANCH"
git reset --hard "origin/$BRANCH"

echo "==> Installing PHP deps"
composer install --no-dev --prefer-dist --optimize-autoloader

echo "==> Building assets"
# Use npm ci if lockfile is authoritative; fall back to npm install otherwise
if [ -f package-lock.json ]; then
  npm ci
else
  npm install
fi
npm run build

echo "==> Running migrations"
php artisan migrate --force

echo "==> Clearing and warming caches"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Ensuring permissions"
chown -R "$WEB_USER:$WEB_GROUP" storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;

echo "==> Leaving maintenance mode"
php artisan up

echo "==> Deploy complete ✅"
