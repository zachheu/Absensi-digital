#!/usr/bin/env bash
#
# Backup database, lampiran privat, dan .env.
# Dijalankan oleh cron root setiap hari pukul 02:00.
#
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/absensi-himatif}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/absensi}"
RETENTION_DAYS="${RETENTION_DAYS:-14}"
DB_NAME="${DB_NAME:-absensi_himatif}"
DB_CNF="${DB_CNF:-/etc/absensi-db.cnf}"
STAMP="$(date +%Y-%m-%d_%H%M%S)"

umask 077
install -d -m 700 "$BACKUP_DIR"

if [[ ! -f "$DB_CNF" ]]; then
    echo "File kredensial database $DB_CNF tidak ditemukan." >&2
    exit 1
fi

mysqldump \
    --defaults-extra-file="$DB_CNF" \
    --single-transaction \
    --routines \
    --triggers \
    --no-tablespaces \
    "$DB_NAME" | gzip -9 > "$BACKUP_DIR/database-${STAMP}.sql.gz"

tar -czf "$BACKUP_DIR/storage-${STAMP}.tar.gz" \
    -C "$APP_DIR" \
    .env storage/app/private 2>/dev/null || {
        echo "Sebagian folder storage belum ada, backup storage dilewati." >&2
        rm -f "$BACKUP_DIR/storage-${STAMP}.tar.gz"
    }

(
    cd "$BACKUP_DIR"
    sha256sum database-*.sql.gz storage-*.tar.gz 2>/dev/null > "checksums-${STAMP}.txt" || true
)

find "$BACKUP_DIR" -type f -mtime +"$RETENTION_DAYS" -delete

if command -v rclone >/dev/null 2>&1 && [[ -n "${RCLONE_REMOTE:-}" ]]; then
    rclone copy "$BACKUP_DIR" "$RCLONE_REMOTE" --min-age 1m
fi

echo "Backup selesai: $BACKUP_DIR"
