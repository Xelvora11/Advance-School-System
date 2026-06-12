#!/usr/bin/env bash
set -euo pipefail

if [ -n "${COMPOSER_BIN:-}" ]; then
  composer_cmd=("$COMPOSER_BIN")
elif command -v composer >/dev/null 2>&1; then
  composer_cmd=("composer")
elif [ -f /tmp/composer.phar ]; then
  composer_cmd=("php" "/tmp/composer.phar")
else
  echo "Composer was not found. Install Composer or set COMPOSER_BIN."
  exit 1
fi

php artisan down || true

"${composer_cmd[@]}" install --no-dev --optimize-autoloader --no-interaction

if command -v npm >/dev/null 2>&1; then
  npm ci --no-audit --no-fund || npm install --no-audit --no-fund
  npm run build
fi

php artisan migrate --force
php artisan storage:link || true
php artisan optimize:clear
php artisan route:list >/dev/null
php artisan view:cache
php artisan optimize

php artisan up

echo "Deployment complete."
