#!/usr/bin/env bash
set -euo pipefail

php artisan config:clear
php artisan route:list >/dev/null
php artisan view:cache
npm run build
php artisan test
php artisan optimize:clear

echo "Healthcheck passed: routes, views, assets, and tests are ready."
