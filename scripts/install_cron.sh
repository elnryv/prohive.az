#!/usr/bin/env bash
# Birlikdə Yük — 4 cron tapşırığını (hourly/daily/payriff_recheck/backup) crontab-a
# idempotent əlavə edir. Artıq mövcud olan sətirlər təkrarlanmır, buna görə bu skript
# neçə dəfə işə salınsa da təhlükəsizdir. Bax README bölmə 4/5.
set -euo pipefail

APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_BIN="$(command -v php)"
LOG_DIR="$APP_ROOT/storage/logs"
mkdir -p "$LOG_DIR"

declare -a MARKERS=(
  "cron/hourly.php"
  "cron/daily.php"
  "cron/payriff_recheck.php"
  "scripts/backup.sh"
)
declare -a JOBS=(
  "0 * * * * $PHP_BIN $APP_ROOT/cron/hourly.php >> $LOG_DIR/hourly.log 2>&1"
  "0 3 * * * $PHP_BIN $APP_ROOT/cron/daily.php >> $LOG_DIR/daily.log 2>&1"
  "*/15 * * * * $PHP_BIN $APP_ROOT/cron/payriff_recheck.php >> $LOG_DIR/payriff_recheck.log 2>&1"
  "30 2 * * * $APP_ROOT/scripts/backup.sh >> $LOG_DIR/backup.log 2>&1"
)

existing="$(crontab -l 2>/dev/null || true)"
updated="$existing"
added=0

for i in "${!JOBS[@]}"; do
  marker="${MARKERS[$i]}"
  job="${JOBS[$i]}"
  # Eyni skriptə istinad edən sətir artıq varsa keç (tam sətir yox, skript yolu axtarılır).
  if echo "$existing" | grep -qF "$marker"; then
    echo "[cron] artıq mövcuddur, keçilir: $marker"
    continue
  fi
  updated="$updated
$job"
  added=$((added + 1))
  echo "[cron] əlavə olunur: $job"
done

if [ "$added" -eq 0 ]; then
  echo "[cron] dəyişiklik yoxdur, hamısı artıq quraşdırılıb."
  exit 0
fi

echo "$updated" | crontab -
echo "[cron] $added yeni tapşırıq əlavə olundu. Yoxlamaq üçün: crontab -l"
