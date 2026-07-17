#!/usr/bin/env bash
# Birlikdə Yük — gecəlik yedəkləmə: MySQL dump + uploads qovluğu.
# Crontab nümunəsi (hər gün 02:30): 30 2 * * * /var/www/yuk/scripts/backup.sh >> /var/www/yuk/storage/logs/backup.log 2>&1
set -euo pipefail

APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CONFIG_FILE="$APP_ROOT/config.php"
BACKUP_ROOT="${BIRLIKDE_BACKUP_DIR:-/var/backups/birlikde-yuk}"
STAMP="$(date +%Y%m%d-%H%M%S)"
KEEP_DAYS=14

if [ ! -f "$CONFIG_FILE" ]; then
  echo "[backup] config.php tapılmadı: $CONFIG_FILE" >&2
  exit 1
fi

DB_HOST=$(php -r "echo (require '$CONFIG_FILE')['db']['host'];")
DB_PORT=$(php -r "echo (require '$CONFIG_FILE')['db']['port'];")
DB_NAME=$(php -r "echo (require '$CONFIG_FILE')['db']['name'];")
DB_USER=$(php -r "echo (require '$CONFIG_FILE')['db']['user'];")
DB_PASS=$(php -r "echo (require '$CONFIG_FILE')['db']['pass'];")

mkdir -p "$BACKUP_ROOT/db" "$BACKUP_ROOT/uploads"

echo "[backup] $STAMP: mysqldump başladı ($DB_NAME)"
MYSQL_PWD="$DB_PASS" mysqldump \
  --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USER" \
  --single-transaction --quick --routines --triggers \
  "$DB_NAME" | gzip > "$BACKUP_ROOT/db/${DB_NAME}-${STAMP}.sql.gz"

echo "[backup] $STAMP: uploads rsync başladı"
rsync -a --delete "$APP_ROOT/public/uploads/" "$BACKUP_ROOT/uploads/"

echo "[backup] $STAMP: ${KEEP_DAYS} gündən köhnə dump-lar silinir"
find "$BACKUP_ROOT/db" -name '*.sql.gz' -mtime +"$KEEP_DAYS" -delete

echo "[backup] $STAMP: tamamlandı → $BACKUP_ROOT"
