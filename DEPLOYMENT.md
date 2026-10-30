# DEPLOYMENT - Absensi HIMATIF

## 1. Status

Dokumen ini adalah panduan deployment. Aplikasi saat ini **belum dideploy** ke server produksi. Database produksi belum dibuat dan repo git belum diinisialisasi pada lingkungan pengembangan ini.

## 2. Prasyarat server

- Linux (Ubuntu 22.04/24.04 atau setara)
- PHP 8.3 dengan ekstensi: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip`
- Composer 2
- Node.js 20+ dan npm (hanya untuk build aset)
- MariaDB 10.11+ atau MySQL 8+
- Nginx atau Apache
- PHP-FPM
- Akses SSH dan hak sudo terbatas

### 2.1 Oracle Cloud Always Free

Pilihan gratis yang paling realistis untuk Laravel dan MariaDB adalah VM Oracle Cloud Always Free. Spesifikasi saat ini sekitar 2 OCPU ARM, 12 GB RAM, dan 200 GB block storage, tanpa masa trial. Kuota dapat berubah, jadi cek konsol Oracle sebelum membuat instance.

Langkah pembuatan VM:

1. Buat akun Oracle Cloud. Verifikasi biasanya memerlukan kartu, tetapi resource Always Free tidak ditagih.
2. Buat instance dengan image Ubuntu 24.04, shape `VM.Standard.A1.Flex`, 2 OCPU, 12 GB RAM, boot volume 50 GB.
3. Unduh atau daftarkan SSH key saat membuat instance.
4. Catat public IP, lalu reserve agar tidak berubah.
5. Di security list VCN, buka port 80 dan 443. Port 22 cukup untuk IP admin bila memungkinkan.
6. Arahkan DNS ke public IP. Untuk uji cepat bisa memakai subdomain DuckDNS, tetapi domain sendiri lebih baik untuk organisasi.
7. Jangan memakai proxy Cloudflare mode Flexible. Gunakan DNS only atau Full strict agar HTTPS tidak berputar.

Setelah DNS aktif, dari mesin lokal:

```bash
scp -r deploy ubuntu@<ip>:/tmp/absensi-deploy
ssh ubuntu@<ip>
sudo DOMAIN=absensi.example.com \
  REPO_URL=git@github.com:<user>/absensi-himatif.git \
  CERTBOT_EMAIL=admin@example.com \
  APP_USER=ubuntu \
  bash /tmp/absensi-deploy/provision.sh
```

Script provisioning memasang Nginx, PHP 8.3, MariaDB, Node 22, Composer, Certbot, dan Fail2ban. Script juga membuat database, `.env` produksi, menjalankan migrasi, serta seeder data master. Kredensial database disimpan di `/etc/absensi-db.cnf` dengan permission terbatas.

Setelah provisioning:

```bash
ssh ubuntu@<ip>
sudo nano /var/www/absensi-himatif/.env
```

Isi `HIMATIF_ADMIN_NPP`, `HIMATIF_ADMIN_NPM`, `HIMATIF_ADMIN_NAME`, `HIMATIF_ADMIN_PASSWORD`, `HIMATIF_ADMIN_FIELD`, dan `HIMATIF_ADMIN_STRUCTURAL_POSITION`, lalu:

```bash
cd /var/www/absensi-himatif
php artisan config:clear
php artisan db:seed --class=AdminUserSeeder --force
php artisan optimize
```

Catatan Oracle Always Free:

- Kapasitas ARM di region tertentu bisa penuh saat membuat instance.
- Oracle dapat menarik instance yang idle sesuai kebijakan mereka.
- Tidak ada SLA dan tidak ada dukungan produksi pada free tier.
- Satu VM berarti satu titik kegagalan. Backup harus disalin keluar dari VM.
- Untuk pemakaian resmi jangka panjang, VPS murah lebih aman daripada free tier.

## 3. Struktur direktori yang disarankan

```
/var/www/absensi-himatif/
  current -> releases/2026-10-29/
  releases/
  shared/
    .env
    storage/
```

Gunakan symlink `current` ke release aktif agar rollback mudah dan folder `storage` tetap persisten.

## 4. Langkah deployment

### 4.1 Siapkan kode

```bash
cd /var/www/absensi-himatif
git clone <url-repo> releases/2026-10-29
cd releases/2026-10-29
composer install --no-dev --optimize-autoloader --no-interaction
```

### 4.2 Siapkan environment

```bash
cp .env.example .env
php artisan key:generate
```

Isi `.env` produksi. Contoh:

```dotenv
APP_NAME="Absensi HIMATIF"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://absensi.himatif.example

APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absensi_himatif
DB_USERNAME=absensi_himatif
DB_PASSWORD=<password-kuat>

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

LOG_CHANNEL=stack
LOG_LEVEL=warning

HIMATIF_ADMIN_NPP=<npp-admin>
HIMATIF_ADMIN_NPM=<npm-admin>
HIMATIF_ADMIN_NAME=<nama-admin>
HIMATIF_ADMIN_PASSWORD=<password-admin-kuat>
HIMATIF_ADMIN_FIELD=STAFF_INTI
HIMATIF_ADMIN_STRUCTURAL_POSITION=sekretaris_umum
HIMATIF_ADMIN_RESET_PASSWORD=
```

Jangan commit `.env`. Batasi permission file:

```bash
chmod 600 .env
```

### 4.3 Siapkan database

```sql
CREATE DATABASE absensi_himatif CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'absensi_himatif'@'127.0.0.1' IDENTIFIED BY '<password-kuat>';
GRANT ALL PRIVILEGES ON absensi_himatif.* TO 'absensi_himatif'@'127.0.0.1';
FLUSH PRIVILEGES;
```

### 4.4 Migrasi

```bash
php artisan config:clear
php artisan migrate --force
```

Peringatan:

- Jangan menjalankan `migrate:fresh` atau `migrate:refresh` di produksi.
- Jangan menghapus data atau migrasi tanpa persetujuan dan backup.
- Uji migrasi di staging terlebih dahulu.

### 4.5 Seeder data master

Urutan aman:

```bash
php artisan db:seed --class=OrganizationalFieldSeeder --force
php artisan db:seed --class=CommitteePositionSeeder --force
php artisan db:seed --class=PermissionSeeder --force
php artisan db:seed --class=AdminUserSeeder --force
```

Seeder idempotent dan aman dijalankan ulang.

Catatan penting: `AdminUserSeeder` membaca environment langsung. Bila konfigurasi sudah di-cache, jalankan `php artisan config:clear` sebelum seeding admin. Setelah selesai, baru jalankan `config:cache`.

Setelah akun admin berhasil dibuat:

1. Kosongkan `HIMATIF_ADMIN_RESET_PASSWORD`.
2. Ganti password admin melalui aplikasi atau seeder dengan flag reset.
3. Jangan menyimpan password asli di catatan publik.

### 4.6 Build aset

```bash
npm ci
npm run build
```

Pastikan folder `public/build` terbentuk.

### 4.7 Permission file

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

Jangan memberi permission `777`.

### 4.8 Optimasi

```bash
php artisan optimize
```

Perintah ini menjalankan cache konfigurasi, route, view, dan event. Setelah mengubah `.env`, jalankan `php artisan optimize:clear` lalu `php artisan optimize` lagi.

### 4.9 Restart PHP-FPM

```bash
sudo systemctl restart php8.3-fpm
```

## 5. Konfigurasi Nginx

```nginx
server {
    listen 80;
    server_name absensi.himatif.example;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name absensi.himatif.example;

    root /var/www/absensi-himatif/current/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/absensi.himatif.example/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/absensi.himatif.example/privkey.pem;

    client_max_body_size 3m;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ^~ /storage/app/private {
        deny all;
        return 404;
    }
}
```

Catatan:

- Document root wajib mengarah ke `public`, bukan root proyek.
- Folder `storage/app/private` tidak boleh dapat diakses langsung.
- Ukuran upload disesuaikan dengan batas lampiran 2 MB.

## 6. Scheduler dan queue

Saat ini tidak ada job terjadwal maupun job queue yang wajib. Aplikasi menggunakan:

- `SESSION_DRIVER=database`
- `CACHE_STORE=database`
- `QUEUE_CONNECTION=database`

Tabel `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, dan `failed_jobs` sudah tersedia melalui migrasi bawaan. Bila nanti menambahkan job, aktifkan worker:

```bash
php artisan queue:work --tries=3 --timeout=90
```

Gunakan Supervisor agar worker otomatis hidup kembali.

## 7. Backup

Backup wajib mencakup:

1. Database `absensi_himatif`.
2. Folder `storage/app/private/permission-attachments`.
3. File `.env` (disimpan aman dan terpisah).
4. `public/build` tidak wajib, dapat dibangun ulang.

Contoh backup harian:

```bash
mysqldump --single-transaction --routines --triggers \
  -u absensi_himatif -p absensi_himatif \
  | gzip > /backup/absensi-$(date +%F).sql.gz

tar -czf /backup/lampiran-$(date +%F).tar.gz \
  /var/www/absensi-himatif/shared/storage/app/private/permission-attachments
```

Simpan backup terenkripsi di lokasi terpisah dan uji restore minimal sebulan sekali.

Script `deploy/backup.sh` sudah menangani database, lampiran privat, `.env`, dan checksum. Script dijalankan cron root setiap pukul 02:00 dan menyimpan hasil di `/var/backups/absensi` dengan retensi 14 hari.

Backup di VM yang sama belum cukup. Salin keluar dari VM, misalnya ke Cloudflare R2, S3, atau disk eksternal. Script mendukung rclone secara opsional:

```bash
apt-get install -y rclone
rclone config                      # buat remote, contoh nama: r2
```

Lalu tambahkan konfigurasi pada cron backup:

```bash
RCLONE_REMOTE=r2:absensi-backup
```

Setelah itu jalankan `sudo bash /var/www/absensi-himatif/deploy/backup.sh` dan pastikan file muncul di bucket. R2 free tier memberi 10 GB penyimpanan, 1 juta operasi Class A, 10 juta Class B, dan egress gratis, jadi cukup untuk dump database dan lampiran.

Rollback database hanya boleh dari backup yang sudah diverifikasi, dan jangan pernah menguji restore di database produksi.

## 8. Rollback

### Rollback aplikasi

1. Arahkan symlink `current` ke release sebelumnya.
2. Jalankan `php artisan optimize:clear` bila perlu.
3. Restart PHP-FPM.

```bash
ln -sfn /var/www/absensi-himatif/releases/<release-sebelumnya> \
  /var/www/absensi-himatif/current
sudo systemctl reload php8.3-fpm
```

### Rollback database

- Lakukan hanya bila migrasi terbaru reversible dan tidak menghilangkan data.
- Utamakan restore dari backup daripada `migrate:rollback` di produksi.
- Koordinasikan dengan pemilik data sebelum melakukan rollback.
- Jangan pernah menghapus database produksi untuk "memperbaiki" masalah.

## 9. Monitoring dan operasional

Pantau:

- Status PHP-FPM dan Nginx.
- Ruang disk, terutama `storage/logs` dan lampiran.
- Koneksi dan ukuran database.
- Error 5xx pada log Nginx dan `storage/logs/laravel.log`.
- Percobaan login gagal berulang.
- Audit koreksi yang tidak wajar.
- Keberhasilan backup harian.

Perintah berguna:

```bash
tail -f storage/logs/laravel.log
php artisan about
php artisan migrate:status
php artisan route:list --except-vendor
```

## 10. Checklist go-live

- [ ] DNS dan sertifikat HTTPS aktif.
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` baru.
- [ ] Kredensial database kuat dan user terbatas.
- [ ] Migrasi berhasil tanpa error.
- [ ] Seeder bidang, jabatan, permission berhasil.
- [ ] Akun admin dibuat dan password diganti.
- [ ] `HIMATIF_ADMIN_RESET_PASSWORD` kosong.
- [ ] `npm run build` sukses dan manifest ada.
- [ ] Permission `storage` dan `bootstrap/cache` benar.
- [ ] `php artisan optimize` berhasil.
- [ ] Login, buka sesi, scan QR, izin, koreksi, finalisasi, dan ekspor diuji di staging.
- [ ] Backup otomatis berjalan dan restore diuji.
- [ ] Log tidak memuat token atau password.
- [ ] Folder privat tidak dapat diakses dari browser.
- [ ] Kontak admin dan prosedur insiden tersedia.

## 11. CI/CD dan deploy otomatis

Workflow CI ada di `.github/workflows/ci.yml`. Setiap push ke `main` dan pull request menjalankan Pint, build aset, dan seluruh test dengan MariaDB 10.11.

Workflow deploy ada di `.github/workflows/deploy.yml` dan hanya berjalan manual. Tambahkan secrets berikut di repository GitHub:

- `SSH_HOST`: IP atau domain server.
- `SSH_USER`: user SSH, misalnya `ubuntu`.
- `SSH_PRIVATE_KEY`: private key yang boleh login ke server.
- `APP_DIR`: opsional, default `/var/www/absensi-himatif`.

Setelah secrets siap, jalankan workflow Deploy dari tab Actions. Workflow akan menjalankan `deploy/deploy.sh` di server. Script membuat backup, mengambil kode terbaru, memasang dependency, build aset, migrasi, optimasi, lalu reload PHP-FPM dan Nginx.

Deploy manual:

```bash
ssh ubuntu@<ip>
cd /var/www/absensi-himatif
bash deploy/deploy.sh
```

Rollback aplikasi:

```bash
cd /var/www/absensi-himatif
git log --oneline -5
git reset --hard <commit-sebelumnya>
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan optimize:clear
php artisan optimize
sudo systemctl reload php8.3-fpm
```

Jangan rollback database tanpa backup dan persetujuan pemilik data.

Panduan pengujian setelah deploy ada di `deploy/TESTING.md`, mencakup smoke test, QR di perangkat nyata, uji beban k6, keamanan, aksesibilitas, dan restore backup.

## 12. Catatan khusus

- Jangan menjalankan `php artisan migrate:fresh` di server berisi data.
- Jangan menyalin database development ke produksi.
- Jangan menggunakan password contoh dari dokumentasi.
- Jangan menaruh `.env` di dalam document root.
- Jangan menonaktifkan CSRF atau validasi server untuk "mempercepat" absensi.
- Aplikasi ini belum melalui audit keamanan eksternal; lakukan review sebelum go-live.
