#!/usr/bin/env bash
#
# Deploy dari branch main ke server yang sudah diprovision.
# Jalankan sebagai user aplikasi (bukan root), dari mana saja:
#   bash /var/www/absensi-himatif/deploy/deploy.sh
#
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/absensi-himatif}"
BRANCH="${BRANCH:-main}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"
RUN_BACKUP="${RUN_BACKUP:-true}"

cd "$APP_DIR"

if [[ ! -f artisan ]]; then
    echo "Tidak menemukan artisan di $APP_DIR" >&2
    exit 1
fi

exec 9>/tmp/absensi-deploy.lock
if ! flock -n 9; then
    echo "Deploy lain sedang berjalan." >&2
    exit 1
fi

if [[ "$RUN_BACKUP" == "true" && -f deploy/backup.sh ]]; then
    echo "Membuat backup sebelum deploy..."
    bash deploy/backup.sh
fi

echo "Mengambil kode terbaru dari origin/$BRANCH..."
git fetch --prune origin
git reset --hard "origin/$BRANCH"

echo "Memasang dependency..."
"$COMPOSER_BIN" install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "Membangun aset..."
"$NPM_BIN" ci
"$NPM_BIN" run build

echo "Menjalankan migrasi..."
"$PHP_BIN" artisan migrate --force

echo "Mengoptimalkan aplikasi..."
"$PHP_BIN" artisan optimize

sudo systemctl reload php8.3-fpm
sudo systemctl reload nginx

echo "Deploy selesai: $(git rev-parse --short HEAD)"
