#!/bin/sh
set -eu

project_dir=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)

env_value() {
  php -r 'require $argv[1] . "/bootstrap.php"; echo (string) App\Core\Config::get($argv[2], "");' "$project_dir" "$1"
}

if [ -z "${DB_HOST:-}" ]; then DB_HOST=$(env_value DB_HOST); export DB_HOST; fi
if [ -z "${DB_PORT:-}" ]; then DB_PORT=$(env_value DB_PORT); export DB_PORT; fi
if [ -z "${DB_DATABASE:-}" ]; then DB_DATABASE=$(env_value DB_DATABASE); export DB_DATABASE; fi
if [ -z "${DB_USERNAME:-}" ]; then DB_USERNAME=$(env_value DB_USERNAME); export DB_USERNAME; fi
if [ -z "${DB_PASSWORD:-}" ]; then DB_PASSWORD=$(env_value DB_PASSWORD); export DB_PASSWORD; fi
if [ -z "${BACKUP_ENCRYPTION_KEY:-}" ]; then BACKUP_ENCRYPTION_KEY=$(env_value BACKUP_ENCRYPTION_KEY); export BACKUP_ENCRYPTION_KEY; fi
if [ -z "${BACKUP_DIR:-}" ]; then BACKUP_DIR=$(env_value BACKUP_DIR); export BACKUP_DIR; fi
if [ -z "${BACKUP_RETENTION_DAYS:-}" ]; then BACKUP_RETENTION_DAYS=$(env_value BACKUP_RETENTION_DAYS); export BACKUP_RETENTION_DAYS; fi

: "${BACKUP_DIR:?BACKUP_DIR gerekli}"
: "${BACKUP_ENCRYPTION_KEY:?BACKUP_ENCRYPTION_KEY gerekli}"

retention_days=${BACKUP_RETENTION_DAYS:-30}
case "$retention_days" in
  ''|*[!0-9]*) echo "BACKUP_RETENTION_DAYS pozitif bir tam sayi olmalidir." >&2; exit 2 ;;
esac
if [ "$retention_days" -lt 1 ]; then
  echo "BACKUP_RETENTION_DAYS en az 1 olmalidir." >&2
  exit 2
fi

mkdir -p "$BACKUP_DIR"
lock_file="$BACKUP_DIR/.daily-backup.lock"
if command -v flock >/dev/null 2>&1; then
  exec 9>"$lock_file"
  if ! flock -n 9; then
    echo "Gunluk yedekleme zaten calisiyor."
    exit 0
  fi
fi

today=$(date '+%Y-%m-%d')
success_marker="$BACKUP_DIR/.last-successful-date"
if [ "${1:-}" = "--if-due" ] && [ -f "$success_marker" ] && [ "$(cat "$success_marker")" = "$today" ]; then
  exit 0
fi

/bin/sh "$project_dir/bin/encrypted-backup.sh"

backup_file=$(find "$BACKUP_DIR" -maxdepth 1 -type f -name 'talya-*.tar.enc' -print | sort | tail -n 1)
if [ -z "$backup_file" ]; then
  echo "Yeni yedek dosyasi belirlenemedi." >&2
  exit 1
fi

/bin/sh "$project_dir/bin/verify-encrypted-backup.sh" "$backup_file"
printf '%s' "$today" > "$success_marker"
chmod 0600 "$success_marker"

find "$BACKUP_DIR" -maxdepth 1 -type f \
  \( -name 'talya-*.tar.enc' -o -name 'talya-*.tar.enc.sha256' \) \
  -mtime "+$retention_days" -delete

echo "Gunluk yedekleme tamamlandi; $retention_days gunden eski yedekler temizlendi."
