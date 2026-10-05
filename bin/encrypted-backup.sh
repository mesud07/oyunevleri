#!/bin/sh
set -eu

project_dir=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
backup_dir=${BACKUP_DIR:-"$project_dir/storage/backups"}
timestamp=$(date '+%Y%m%d-%H%M%S')
temp_dir=$(mktemp -d "${TMPDIR:-/tmp}/talya-backup.XXXXXX")
trap 'rm -rf -- "$temp_dir"' EXIT HUP INT TERM

: "${DB_HOST:?DB_HOST gerekli}"
: "${DB_DATABASE:?DB_DATABASE gerekli}"
: "${DB_USERNAME:?DB_USERNAME gerekli}"
: "${DB_PASSWORD:?DB_PASSWORD gerekli}"
: "${BACKUP_ENCRYPTION_KEY:?BACKUP_ENCRYPTION_KEY gerekli}"

case "$backup_dir" in
  /*) ;;
  *) echo "BACKUP_DIR mutlak bir yol olmalidir." >&2; exit 2 ;;
esac
if [ "$backup_dir" = "/" ]; then
  echo "BACKUP_DIR kok dizin olamaz." >&2
  exit 2
fi

mkdir -p "$backup_dir"
chmod 0700 "$backup_dir"

payload_dir="$temp_dir/payload"
mkdir -p "$payload_dir"

MYSQL_PWD=$DB_PASSWORD mysqldump \
  --host="$DB_HOST" \
  --port="${DB_PORT:-3306}" \
  --user="$DB_USERNAME" \
  --single-transaction \
  --routines \
  --triggers \
  --default-character-set=utf8mb4 \
  "$DB_DATABASE" > "$payload_dir/database.sql"
chmod 0600 "$payload_dir/database.sql"

archive_paths="database.sql"
if [ -d "$project_dir/storage/faturalar" ]; then
  mkdir -p "$payload_dir/storage"
  cp -R "$project_dir/storage/faturalar" "$payload_dir/storage/faturalar"
  archive_paths="$archive_paths storage/faturalar"
fi
if [ -d "$project_dir/public/uploads" ]; then
  mkdir -p "$payload_dir/public"
  cp -R "$project_dir/public/uploads" "$payload_dir/public/uploads"
  archive_paths="$archive_paths public/uploads"
fi

tar -C "$payload_dir" -cf "$temp_dir/archive.tar" $archive_paths
backup_file="$backup_dir/talya-$timestamp.tar.enc"
openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt \
  -in "$temp_dir/archive.tar" \
  -out "$backup_file" \
  -pass env:BACKUP_ENCRYPTION_KEY
chmod 0600 "$backup_file"
if command -v sha256sum >/dev/null 2>&1; then
  (cd "$backup_dir" && sha256sum "talya-$timestamp.tar.enc" > "talya-$timestamp.tar.enc.sha256")
else
  (cd "$backup_dir" && shasum -a 256 "talya-$timestamp.tar.enc" > "talya-$timestamp.tar.enc.sha256")
fi
chmod 0600 "$backup_file.sha256"

echo "Şifreli yedek oluşturuldu: $backup_file"
