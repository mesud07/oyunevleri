#!/bin/sh
set -eu

if [ "$#" -ne 1 ]; then
  echo "Kullanim: BACKUP_ENCRYPTION_KEY=... $0 /mutlak/yol/talya-YYYYMMDD-HHMMSS.tar.enc" >&2
  exit 2
fi

: "${BACKUP_ENCRYPTION_KEY:?BACKUP_ENCRYPTION_KEY gerekli}"
backup_file=$1
case "$backup_file" in
  /*) ;;
  *) echo "Yedek dosyasi mutlak yol olmalidir." >&2; exit 2 ;;
esac
if [ ! -f "$backup_file" ]; then
  echo "Yedek dosyasi bulunamadi." >&2
  exit 2
fi

backup_dir=$(dirname -- "$backup_file")
backup_name=$(basename -- "$backup_file")
checksum_file="$backup_file.sha256"
if [ ! -f "$checksum_file" ]; then
  echo "SHA-256 dogrulama dosyasi bulunamadi: $checksum_file" >&2
  exit 1
fi

if command -v sha256sum >/dev/null 2>&1; then
  (cd "$backup_dir" && sha256sum -c "$(basename -- "$checksum_file")")
elif command -v shasum >/dev/null 2>&1; then
  (cd "$backup_dir" && shasum -a 256 -c "$(basename -- "$checksum_file")")
else
  echo "sha256sum veya shasum bulunamadi." >&2
  exit 1
fi

temp_dir=$(mktemp -d "${TMPDIR:-/tmp}/talya-backup-verify.XXXXXX")
trap 'rm -rf -- "$temp_dir"' EXIT HUP INT TERM
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 \
  -in "$backup_file" \
  -out "$temp_dir/archive.tar" \
  -pass env:BACKUP_ENCRYPTION_KEY

tar -tf "$temp_dir/archive.tar" > "$temp_dir/files.txt"
grep -qx 'database.sql' "$temp_dir/files.txt" || { echo "Yedekte database.sql bulunamadi." >&2; exit 1; }
grep -q '^storage/faturalar/\|^storage/faturalar$' "$temp_dir/files.txt" || echo "Bilgi: Yedekte henuz fatura arsivi bulunmuyor."
grep -q '^public/uploads/\|^public/uploads$' "$temp_dir/files.txt" || echo "Bilgi: Yedekte henuz yuklenmis dosya bulunmuyor."

echo "Yedek butunlugu, sifre cozme islemi ve zorunlu icerik dogrulandi: $backup_name"
