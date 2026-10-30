#!/usr/bin/env bash
#
# Provisioning awal untuk Absensi HIMATIF di Ubuntu 24.04 (Oracle Cloud
# Always Free / VM biasa). Jalankan sebagai root setelah DNS mengarah ke VM.
#
# Contoh:
#   sudo DOMAIN=absensi.example.com REPO_URL=git@github.com:user/absensi-himatif.git \
#     CERTBOT_EMAIL=admin@example.com bash deploy/provision.sh
#
set -euo pipefail

if [[ ${EUID:-$(id -u)} -ne 0 ]]; then
    echo "Jalankan sebagai root: sudo bash deploy/provision.sh" >&2
    exit 1
fi

DOMAIN="${DOMAIN:-}"
APP_DIR="${APP_DIR:-/var/www/absensi-himatif}"
APP_USER="${APP_USER:-ubuntu}"
REPO_URL="${REPO_URL:-}"
BRANCH="${BRANCH:-main}"
DB_NAME="${DB_NAME:-absensi_himatif}"
DB_USER="${DB_USER:-absensi_himatif}"
DB_PASSWORD="${DB_PASSWORD:-$(openssl rand -hex 24)}"
CERTBOT_EMAIL="${CERTBOT_EMAIL:-}"

if [[ -z "$DOMAIN" ]]; then
    echo "DOMAIN wajib diisi, contoh: DOMAIN=absensi.example.com" >&2
    exit 1
fi

if ! id "$APP_USER" >/dev/null 2>&1; then
    echo "User $APP_USER tidak ditemukan. Set APP_USER sesuai user SSH." >&2
    exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

export DEBIAN_FRONTEND=noninteractive

apt-get update
apt-get upgrade -y
apt-get install -y \
    nginx mariadb-server \
    php8.3-cli php8.3-fpm php8.3-common php8.3-bcmath php8.3-curl \
    php8.3-gd php8.3-intl php8.3-mbstring php8.3-mysql \
    php8.3-opcache php8.3-xml php8.3-zip \
    composer git unzip curl ca-certificates ufw fail2ban \
    certbot python3-certbot-nginx

if ! command -v node >/dev/null 2>&1 || [[ "$(node -p "process.versions.node.split('.')[0]")" -lt 22 ]]; then
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
    apt-get install -y nodejs
fi

cat >/etc/php/8.3/fpm/conf.d/99-absensi.ini <<'PHP'
upload_max_filesize = 3M
post_max_size = 4M
memory_limit = 256M
max_execution_time = 60
expose_php = Off
opcache.enable = 1
opcache.memory_consumption = 128
opcache.validate_timestamps = 0
opcache.save_comments = 1
PHP

systemctl enable --now mariadb
mysql --protocol=socket <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASSWORD}';
ALTER USER '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

cat >/etc/absensi-db.cnf <<EOF
[client]
host=127.0.0.1
user=${DB_USER}
password=${DB_PASSWORD}
EOF
chown root:"$APP_USER" /etc/absensi-db.cnf
chmod 640 /etc/absensi-db.cnf

install -d -o "$APP_USER" -g www-data -m 775 "$APP_DIR"

if [[ -n "$REPO_URL" ]]; then
    if [[ ! -d "$APP_DIR/.git" ]]; then
        sudo -u "$APP_USER" git clone --branch "$BRANCH" "$REPO_URL" "$APP_DIR"
    else
        sudo -u "$APP_USER" git -C "$APP_DIR" fetch --prune origin
        sudo -u "$APP_USER" git -C "$APP_DIR" reset --hard "origin/$BRANCH"
    fi
else
    echo "REPO_URL kosong. Salin kode ke $APP_DIR secara manual, lalu jalankan ulang bagian aplikasi di DEPLOYMENT.md." >&2
fi

if [[ -f "$APP_DIR/artisan" ]]; then
    cd "$APP_DIR"

    if [[ ! -f .env ]]; then
        cp .env.example .env
        chown "$APP_USER":www-data .env
        chmod 600 .env
    fi

    set_env() {
        local key="$1"
        local value="$2"

        php -r '
            $file = $argv[1];
            $key = $argv[2];
            $value = $argv[3];
            $content = file_get_contents($file);
            $line = $key."=".$value;
            $pattern = "/^".preg_quote($key, "/")."=.*$/m";

            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $line, $content, 1);
            } else {
                $content = rtrim($content)."\n".$line."\n";
            }

            file_put_contents($file, $content);
        ' "$APP_DIR/.env" "$key" "$value"
    }

    set_env APP_NAME '"Absensi HIMATIF"'
    set_env APP_ENV production
    set_env APP_DEBUG false
    set_env APP_URL "https://${DOMAIN}"
    set_env APP_TIMEZONE Asia/Jakarta
    set_env APP_LOCALE id
    set_env DB_CONNECTION mysql
    set_env DB_HOST 127.0.0.1
    set_env DB_PORT 3306
    set_env DB_DATABASE "$DB_NAME"
    set_env DB_USERNAME "$DB_USER"
    set_env DB_PASSWORD "$DB_PASSWORD"
    set_env SESSION_DRIVER database
    set_env SESSION_ENCRYPT true
    set_env SESSION_SECURE_COOKIE true
    set_env CACHE_STORE database
    set_env QUEUE_CONNECTION database
    set_env FILESYSTEM_DISK local
    set_env LOG_CHANNEL stack
    set_env LOG_LEVEL warning

    sudo -u "$APP_USER" composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
    sudo -u "$APP_USER" php artisan key:generate --force

    if [[ ! -f public/build/manifest.json ]]; then
        sudo -u "$APP_USER" npm ci
        sudo -u "$APP_USER" npm run build
    fi

    sudo -u "$APP_USER" php artisan migrate --force

    if [[ "${SEED_MASTER:-true}" == "true" ]]; then
        sudo -u "$APP_USER" php artisan db:seed --class=OrganizationalFieldSeeder --force
        sudo -u "$APP_USER" php artisan db:seed --class=CommitteePositionSeeder --force
        sudo -u "$APP_USER" php artisan db:seed --class=PermissionSeeder --force
    fi

    chown -R "$APP_USER":www-data storage bootstrap/cache
    find storage bootstrap/cache -type d -exec chmod 775 {} \;
    find storage bootstrap/cache -type f -exec chmod 664 {} \;
fi

sed \
    -e "s|__DOMAIN__|${DOMAIN}|g" \
    -e "s|__APP_DIR__|${APP_DIR}|g" \
    "$SCRIPT_DIR/nginx/absensi.conf.template" \
    > /etc/nginx/sites-available/absensi-himatif

ln -sfn /etc/nginx/sites-available/absensi-himatif /etc/nginx/sites-enabled/absensi-himatif
rm -f /etc/nginx/sites-enabled/default

nginx -t
systemctl reload nginx

ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

systemctl enable --now fail2ban
systemctl enable --now php8.3-fpm

install -d -m 700 /var/backups/absensi

cat >/etc/cron.d/absensi-himatif <<EOF
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
0 2 * * * root bash ${APP_DIR}/deploy/backup.sh >> /var/log/absensi-backup.log 2>&1
* * * * * ${APP_USER} cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1
EOF
chmod 644 /etc/cron.d/absensi-himatif

cat >/etc/sudoers.d/absensi-deploy <<EOF
${APP_USER} ALL=(root) NOPASSWD: $(command -v systemctl) reload php8.3-fpm, $(command -v systemctl) reload nginx
EOF
chmod 440 /etc/sudoers.d/absensi-deploy
visudo -cf /etc/sudoers.d/absensi-deploy >/dev/null

if [[ -n "$CERTBOT_EMAIL" ]]; then
    certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos --redirect -m "$CERTBOT_EMAIL" || {
        echo "Certbot gagal. Pastikan DNS $DOMAIN sudah mengarah ke IP publik VM, lalu jalankan certbot manual." >&2
    }
else
    echo "CERTBOT_EMAIL kosong. HTTPS belum diaktifkan. Jalankan certbot setelah DNS aktif." >&2
fi

echo
echo "Provisioning selesai."
echo "1. Isi HIMATIF_ADMIN_* di $APP_DIR/.env."
echo "2. Jalankan: sudo -u $APP_USER php $APP_DIR/artisan db:seed --class=AdminUserSeeder --force"
echo "3. Jalankan: sudo -u $APP_USER php $APP_DIR/artisan optimize"
echo "4. Buka https://$DOMAIN dan login."
echo "5. Kredensial database tersimpan di /etc/absensi-db.cnf (jangan dibagikan)."
