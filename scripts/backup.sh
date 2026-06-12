#!/usr/bin/env bash
set -euo pipefail

if [ -f .env ]; then
  set -a
  # shellcheck disable=SC1091
  source .env
  set +a
fi

BACKUP_DIR="${BACKUP_DIR:-storage/app/backups}"
mkdir -p "$BACKUP_DIR"

STAMP="$(date +%Y%m%d-%H%M%S)"
DB_FILE="$BACKUP_DIR/database-$STAMP.sql"
FILES_FILE="$BACKUP_DIR/public-files-$STAMP.tar.gz"

if [ "${DB_CONNECTION:-}" != "mysql" ]; then
  echo "This backup script expects DB_CONNECTION=mysql."
  exit 1
fi

if [ -z "${DB_DATABASE:-}" ] || [ -z "${DB_USERNAME:-}" ]; then
  echo "DB_DATABASE and DB_USERNAME are required."
  exit 1
fi

if ! command -v mysqldump >/dev/null 2>&1; then
  echo "mysqldump was not found."
  exit 1
fi

MYSQL_PWD="${DB_PASSWORD:-}" mysqldump -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" -u "$DB_USERNAME" "$DB_DATABASE" > "$DB_FILE"

if [ -d storage/app/public ]; then
  tar -czf "$FILES_FILE" storage/app/public
else
  tar -czf "$FILES_FILE" --files-from /dev/null
fi

echo "Database backup: $DB_FILE"
echo "Files backup: $FILES_FILE"
