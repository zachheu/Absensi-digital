# Absensi HIMATIF

Aplikasi web absensi digital untuk pengurus HIMATIF. Absensi dilakukan dengan memindai QR Code dinamis pada kegiatan yang sedang berlangsung, lalu dilengkapi pengajuan izin/sakit, koreksi absensi ber-audit, serta rekap dan ekspor laporan.

Aplikasi ini **bukan** sistem registrasi publik. Akun hanya dibuat oleh admin, dan login menggunakan NPP + password. NPM tetap disimpan sebagai identitas mahasiswa.

## Fitur utama

- Login NPP + password, tanpa registrasi publik.
- Manajemen pengurus, bidang, jabatan kepanitiaan, kegiatan, dan susunan panitia.
- Sesi absensi per kegiatan dengan QR Code dinamis (rotasi 15 detik, berlaku maksimal 20 detik).
- Validasi token QR di server, token disimpan dalam bentuk hash, dan setiap rotasi mencabut token sebelumnya.
- Satu absensi per pengurus per kegiatan; absensi terlambat tetap berstatus hadir dengan penanda `is_late`.
- Pengajuan izin/sakit dengan alasan dan lampiran opsional, disetujui atau ditolak admin.
- Koreksi absensi wajib alasan dan selalu menulis audit log append-only.
- Rekap satu baris per absensi (tidak menghitung ganda walau pengurus punya beberapa jabatan).
- Ekspor rekap ke Excel dan PDF sesuai filter.
- Dashboard terpisah untuk admin dan pengurus.

## Peran

| Peran | Hak akses |
| --- | --- |
| `admin` | Seluruh fitur: data master, pengurus, kegiatan, panitia, sesi QR, persetujuan izin, koreksi, audit, laporan |
| `pengurus` | Melihat kegiatan, memindai QR, mengajukan izin/sakit, melihat absensi sendiri |

Permission khusus `attendance.correct` dapat diberikan admin kepada pengurus tertentu. Admin selalu memiliki permission ini secara otomatis. Koreksi oleh pengurus berizin dibatasi pada kegiatan tempat ia terdaftar sebagai panitia.

Jabatan kepanitiaan (12 jabatan) **tidak** memberikan hak akses aplikasi. Hak akses hanya ditentukan oleh role dan permission.

## Stack

- PHP 8.3
- Laravel 13
- MariaDB 10.11 / MySQL 8
- Tailwind CSS v4 + Vite
- BaconQrCode untuk render QR SVG
- barryvdh/laravel-dompdf untuk PDF
- PhpSpreadsheet untuk Excel
- html5-qrcode untuk pemindaian di browser
- PHPUnit 12

## Kebutuhan sistem

- PHP >= 8.3 dengan ekstensi umum Laravel (`mbstring`, `openssl`, `pdo_mysql`, `zip`, `gd` atau `imagick` bila diperlukan)
- Composer 2
- Node.js 20+ dan npm
- MariaDB 10.11+ atau MySQL 8+

## Instalasi lokal

```bash
git clone <url-repo> absensi-himatif
cd absensi-himatif
composer install
cp .env.example .env
php artisan key:generate
```

Sesuaikan koneksi database di `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absensi_himatif
DB_USERNAME=absensi_himatif
DB_PASSWORD=<password-lokal>
```

Buat database dan user bila belum ada, lalu jalankan migrasi:

```bash
php artisan migrate
```

Isi data master (bidang, jabatan, permission):

```bash
php artisan db:seed --class=OrganizationalFieldSeeder
php artisan db:seed --class=CommitteePositionSeeder
php artisan db:seed --class=PermissionSeeder
```

Atau jalankan seluruh seeder sekaligus. Seeder admin hanya membuat akun bila environment admin diisi:

```bash
php artisan db:seed
```

## Membuat akun admin awal

Isi variabel berikut di `.env` sebelum menjalankan seeder:

```dotenv
HIMATIF_ADMIN_NPP=
HIMATIF_ADMIN_NPM=
HIMATIF_ADMIN_NAME=
HIMATIF_ADMIN_PASSWORD=
HIMATIF_ADMIN_FIELD=STAFF_INTI
HIMATIF_ADMIN_STRUCTURAL_POSITION=sekretaris_umum
HIMATIF_ADMIN_RESET_PASSWORD=
```

- `HIMATIF_ADMIN_PASSWORD` minimal 8 karakter.
- `HIMATIF_ADMIN_FIELD` dan `HIMATIF_ADMIN_STRUCTURAL_POSITION` opsional; isi kode bidang dan kode jabatan struktural (`walikota`, `wakil_walikota`, `sekretaris_umum`, `bendahara_umum`).
- Saat seeder dijalankan ulang, password **tidak** diubah kecuali `HIMATIF_ADMIN_RESET_PASSWORD=true`.
- Setelah reset berhasil, kosongkan kembali `HIMATIF_ADMIN_RESET_PASSWORD` dan jangan commit nilai asli.
- Bila salah satu variabel wajib kosong, seeder hanya memberi peringatan dan tidak membuat akun.

## Menjalankan aplikasi

```bash
npm install
npm run build
php artisan serve
```

Buka `http://127.0.0.1:8000`. Untuk pengembangan frontend, gunakan `npm run dev` di terminal terpisah atau `composer run dev`.

## Pengujian

Test menggunakan database MySQL/MariaDB terpisah agar perilaku `FIELD()` dan constraint konsisten dengan produksi.

Siapkan database test:

```sql
CREATE DATABASE absensi_himatif_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON absensi_himatif_test.* TO 'absensi_himatif'@'localhost';
GRANT ALL PRIVILEGES ON absensi_himatif_test.* TO 'absensi_himatif'@'127.0.0.1';
FLUSH PRIVILEGES;
```

Konfigurasi `phpunit.xml` sudah mengarah ke `absensi_himatif_test`. Jalankan:

```bash
php artisan test --compact
```

Peringatan: `RefreshDatabase` mengosongkan database test. Jangan arahkan `DB_DATABASE` test ke database produksi atau database kerja.

## Struktur penting

```
app/
  Enums/                  enum status, role, jabatan struktural, tipe izin, kategori jabatan
  Http/Controllers/       controller admin dan pengurus
  Http/Middleware/        active, admin
  Http/Requests/          validasi form
  Models/                 model Eloquent
  Services/
    AttendanceService.php absensi, keterlambatan, izin, finalisasi, audit
    QrTokenService.php    issue/rotate/hash/render token QR
database/
  migrations/             skema database
  seeders/                bidang, jabatan, permission, admin
  factories/              factory untuk test
resources/
  css/app.css             token warna army/cream dan komponen Tailwind
  js/                     scanner QR
  views/                  seluruh UI Bahasa Indonesia
routes/web.php            seluruh route web
tests/Feature/            test alur utama
```

## Aturan bisnis penting

- Bidang: HRDO, RE, PR, CNB, plus unit Staff Inti.
- Staff Inti memakai kolom jabatan struktural: Walikota Himpunan, Wakil Walikota Himpunan, Sekretaris Umum, atau Bendahara Umum.
- Ada 12 jabatan kepanitiaan, terpisah dari bidang.
- QR hanya berlaku saat sesi absensi terbuka dan kegiatan tidak draft/selesai.
- Token QR tidak pernah disimpan sebagai plaintext.
- Absensi ganda dicegah oleh unique constraint `(activity_id, user_id)`.
- Terlambat dihitung dari `start_at + late_tolerance_minutes`; status tetap hadir.
- Izin/sakit harus disetujui admin sebelum dihitung pada rekap.
- Persetujuan izin ditolak bila pengurus sudah tercatat hadir atau rekap sudah difinalisasi.
- Koreksi wajib alasan minimal 10 karakter dan menulis audit dalam transaksi yang sama.
- Sesi dibuka saat kegiatan berlangsung; menutup sesi mencabut token QR dan menandai kegiatan selesai.
- Finalisasi rekap menetapkan alpa hanya untuk panitia tanpa absensi dan tanpa izin/sakit disetujui.
- Pengajuan pending tidak otomatis diubah saat finalisasi.
- Rekap tidak menghitung ganda pengurus dengan beberapa jabatan.

## Konfigurasi QR dan rate limit

Lihat `config/himatif.php`:

```php
'qr_ttl_seconds' => 15,
'qr_max_ttl_seconds' => 20,
'login_attempts_per_minute' => 5,
'scan_attempts_per_minute' => 12,
```

## Dokumentasi

- `PROJECT_SPEC.md` - ruang lingkup, kebutuhan, dan aturan bisnis
- `DATABASE_DESIGN.md` - skema, relasi, dan integritas data
- `SECURITY.md` - kontrol keamanan dan checklist produksi
- `DEPLOYMENT.md` - langkah deployment dan operasional, termasuk Oracle Cloud Always Free
- `deploy/TESTING.md` - checklist smoke test, QR perangkat nyata, beban, keamanan, aksesibilitas, dan restore backup
- `IMPLEMENTATION_STATUS.md` - status implementasi, hasil test, dan catatan

## Status

Fitur inti sudah diimplementasikan dan diuji secara lokal. Artefak deployment, CI, backup, dan panduan pengujian sudah disiapkan, tetapi aplikasi **belum** dideploy ke lingkungan produksi. Lihat `IMPLEMENTATION_STATUS.md` untuk rincian dan item lanjutan.
