# IMPLEMENTATION STATUS - Absensi HIMATIF

Terakhir diperbarui: 29 Oktober 2026

## 1. Ringkasan

Fitur inti aplikasi sudah diimplementasikan dan diuji secara lokal. Seluruh test otomatis lulus. Aplikasi belum dideploy, database produksi belum dibuat, dan repo git belum diinisialisasi.

| Area | Status |
| --- | --- |
| Skema database | Selesai, 12 migrasi berhasil |
| Model, enum, factory, seeder | Selesai |
| Autentikasi dan otorisasi | Selesai |
| Manajemen pengurus, bidang, jabatan | Selesai |
| Manajemen kegiatan dan panitia | Selesai |
| Sesi absensi dan QR dinamis | Selesai |
| Absensi QR | Selesai |
| Izin/sakit dan persetujuan | Selesai |
| Koreksi dan audit | Selesai |
| Finalisasi rekap | Selesai |
| Laporan dan ekspor | Selesai |
| UI Bahasa Indonesia | Selesai |
| Aset Tailwind/Vite | Selesai, build sukses |
| Test otomatis | Selesai, 54 test lulus |
| Smoke test HTTP | Selesai di lokal |
| Artefak deployment | Selesai, belum dijalankan di cloud |
| CI/CD | Workflow siap, belum aktif di GitHub |
| Dokumentasi wajib | Selesai |
| Deployment produksi | Belum |
| Audit keamanan eksternal | Belum |

## 2. Yang sudah dikerjakan

### 2.1 Fondasi

- Laravel 13.39, PHP 8.3.6, MariaDB 10.11, Node 26.7.
- Konfigurasi timezone `Asia/Jakarta` melalui `APP_TIMEZONE`.
- Database development `absensi_himatif` dan database test `absensi_himatif_test`.
- 12 migrasi berhasil dijalankan.
- Seeder bidang, jabatan, permission, dan admin berbasis environment.
- Factory untuk seluruh model utama.
- Konfigurasi QR dan rate limit di `config/himatif.php`.
- Tailwind v4 dengan token warna hijau army dan cream.
- Build aset Vite sukses.

### 2.2 Autentikasi dan otorisasi

- Login NPP + password tanpa registrasi publik.
- Rate limit login 5 percobaan per menit per NPP + IP.
- Middleware `active` memutus sesi pengguna nonaktif.
- Middleware `admin` membatasi halaman admin.
- Role `admin` dan `pengurus`.
- Permission `attendance.correct` dengan pivot `permission_user`.
- Gate `attendance.correct` dan `manage-data`.
- Admin otomatis memiliki seluruh permission.
- Admin tidak dapat menonaktifkan atau menurunkan role akun sendiri.

### 2.3 Data master

- Lima bidang: HRDO, RE, PR, CNB, dan Staff Inti.
- Jabatan struktural per pengurus untuk Staff Inti: Walikota Himpunan, Wakil Walikota Himpunan, Sekretaris Umum, Bendahara Umum.
- Dua belas jabatan kepanitiaan dengan kategori structural/division.
- Jabatan tidak memberikan hak akses.
- CRUD pengurus dengan validasi NPP/NPM unik.
- Pengaturan bidang dan jabatan struktural oleh admin.
- Pemberian permission khusus kepada pengurus.

### 2.4 Kegiatan dan kepanitiaan

- CRUD kegiatan.
- Status `draft`, `published`, `ongoing`, `completed`.
- Form edit hanya dapat mengubah `draft` menjadi `published`.
- Status `ongoing` ditetapkan saat sesi dibuka; `completed` ditetapkan saat sesi ditutup.
- Penambahan, pemindahan jabatan, dan penghapusan panitia per kegiatan.
- Pemindahan jabatan ditolak bila rekap sudah difinalisasi, kegiatan sudah selesai, atau jabatan tujuan sudah dipegang pengurus yang sama.
- Satu pengurus boleh memiliki beberapa jabatan pada kegiatan yang sama.
- Perubahan `start_at`/toleransi ditolak bila sudah ada absensi.

### 2.5 Sesi dan QR

- Buka/tutup sesi absensi.
- Membuka sesi mengubah kegiatan menjadi `ongoing`.
- Menutup sesi mencabut seluruh token aktif dan mengubah kegiatan menjadi `completed`.
- QR dinamis dengan rotasi 15 detik dan TTL maksimal 20 detik.
- Token acak 32 byte, disimpan sebagai hash SHA-256.
- Setiap polling token mencabut token lama.
- Endpoint token hanya untuk admin.
- Render QR dalam SVG.
- Scanner memakai html5-qrcode.

### 2.6 Absensi

- Validasi token di server.
- Penolakan token kedaluwarsa, dicabut, atau sesi tertutup.
- Penolakan scan pada kegiatan draft/selesai dan rekap final.
- Satu absensi per pengurus per kegiatan.
- Pesan "sudah tercatat" untuk scan ulang.
- Penanda keterlambatan berdasarkan toleransi kegiatan.
- Status terlambat tetap `hadir` dengan `is_late = true`.
- Rate limit scan 12 percobaan per menit.

### 2.7 Izin dan sakit

- Pengajuan oleh panitia pada kegiatan yang belum final.
- Alasan minimal 10 karakter.
- Lampiran JPG/JPEG/PNG/PDF maksimal 2 MB di disk privat.
- Satu pengajuan per kegiatan per pengurus.
- Pengajuan ditolak boleh diajukan ulang dengan baris yang sama.
- Persetujuan membuat/memperbarui absensi menjadi izin/sakit.
- Penolakan wajib catatan.
- Guard: tidak bisa menyetujui bila sudah hadir atau rekap final.
- Guard: tidak bisa mengajukan bila sudah hadir.

### 2.8 Koreksi dan audit

- Form koreksi untuk admin dan pengurus ber-permission.
- Pengurus ber-permission dibatasi pada kegiatan tempat ia menjadi panitia.
- Alasan koreksi wajib minimal 10 karakter.
- Perubahan dan audit ditulis dalam satu transaksi.
- Audit menyimpan nilai sebelum/sesudah, aktor, alasan, dan waktu.
- Audit append-only, tanpa route edit/hapus.
- Halaman riwayat audit per absensi dan daftar audit global dengan filter.

### 2.9 Rekap dan ekspor

- Rekap satu baris per absensi.
- Filter tanggal, kegiatan, pengurus, bidang, jabatan, status, dan keterlambatan.
- Ringkasan total, hadir, izin, sakit, alpa, terlambat.
- Ekspor Excel dengan PhpSpreadsheet.
- Ekspor PDF dengan DomPDF.
- Finalisasi rekap menetapkan alpa hanya untuk panitia tanpa absensi dan tanpa izin/sakit disetujui.
- Finalisasi idempotent dan menolak bila sesi masih terbuka.
- Pengajuan pending tidak diubah saat finalisasi.

### 2.10 UI

- Layout responsif.
- Login, dashboard admin, dashboard pengurus.
- Daftar dan detail kegiatan.
- Halaman scan QR dan tampilan QR.
- Halaman absensi saya.
- Halaman izin/sakit.
- Halaman admin pengguna, bidang, jabatan, persetujuan izin, audit.
- Halaman laporan dan template PDF.
- Halaman error 403.
- Seluruh teks UI Bahasa Indonesia.
- Palet hijau army dan cream.

### 2.11 Persiapan deployment

- Script provisioning Oracle/Ubuntu di `deploy/provision.sh`.
- Konfigurasi Nginx di `deploy/nginx/absensi.conf.template`.
- Script deploy dan backup di `deploy/deploy.sh` dan `deploy/backup.sh`.
- Workflow CI di `.github/workflows/ci.yml`.
- Workflow deploy manual di `.github/workflows/deploy.yml`.
- Panduan pengujian pasca-deploy di `deploy/TESTING.md`.
- Script uji beban k6 di `tests/load/smoke.js`.

## 3. Hasil pengujian

### 3.1 Test otomatis

Perintah:

```bash
php artisan test --compact
```

Hasil terakhir:

```
54 passed
199 assertions
```

Test dijalankan pada database MySQL/MariaDB test `absensi_himatif_test`, bukan SQLite.

Cakupan file test:

| File | Fokus |
| --- | --- |
| `tests/Feature/ExampleTest.php` | Root redirect ke dashboard |
| `tests/Feature/AuthenticationTest.php` | Login, logout, akun nonaktif, akses admin/pengurus |
| `tests/Feature/QrAttendanceTest.php` | Token valid, ganda, kedaluwarsa, dicabut, sesi tutup, terlambat, rekap final, rotasi, hash, otorisasi |
| `tests/Feature/AttendanceCorrectionTest.php` | Koreksi admin, permission, cakupan kegiatan, validasi alasan, audit |
| `tests/Feature/PermissionRequestFlowTest.php` | Pengajuan, non-panitia, persetujuan, penolakan, pengajuan ulang, guard hadir/final |
| `tests/Feature/ReportAndRecapTest.php` | Dedup laporan, filter, ekspor Excel/PDF, finalisasi alpa, sesi terbuka, idempotensi, otorisasi |
| `tests/Feature/SeederTest.php` | Lima bidang, 12 jabatan, permission, idempotensi, admin env, field dan jabatan struktural, reset password |
| `tests/Feature/AdminUserManagementTest.php` | Simpan, ubah, kosongkan, dan validasi jabatan struktural |

### 3.2 Smoke test HTTP

Dijalankan dengan `php artisan serve` pada port sementara:

| Request | Hasil |
| --- | --- |
| `GET /login` | 200, HTML |
| `GET /` | 302 ke `/dashboard` |
| `GET /dashboard` tanpa login | 302 ke `/login` |
| `POST /login` admin | 302 ke `/dashboard` |
| `GET /dashboard` admin | 200 |
| `GET /admin/users` | 200, jabatan Sekretaris Umum tampil |
| `GET /admin/users/create` | 200, pilihan jabatan struktural tampil |
| `GET /build/assets/...css` | 200 |

Tidak ada error pada log server saat smoke test.

### 3.3 Build dan cache

- `npm run build` sukses.
- `php artisan view:cache` sukses.
- `vendor/bin/pint --format agent` sukses.
- Test dijalankan ulang setelah Pint dan tetap lulus.

## 4. Perbaikan yang dilakukan selama pengembangan

- Cast `$request->string()` menjadi string pada query filter.
- Pengajuan izin memakai data tervalidasi, bukan input mentah.
- Guard persetujuan izin saat pengurus sudah hadir atau rekap final.
- Eager load jabatan panitia pada laporan agar tidak N+1.
- Daftar calon pengguna pada form panitia hanya dimuat untuk admin.
- Factory audit disederhanakan agar konsisten dengan relasi.
- Status kegiatan tidak dapat diubah bebas dari form edit.
- Duplikasi nama route `activities.create` dihapus.
- Timezone aplikasi dipatch ke `APP_TIMEZONE`.
- Template PDF tidak lagi memanggil method pada nilai enum yang sudah divalidasi.

## 5. Yang belum dikerjakan

1. **Akun admin awal**: sudah dibuat melalui `AdminUserSeeder` dengan role admin, status aktif, bidang Staff Inti, dan jabatan struktural Sekretaris Umum. Password tersimpan di `.env` dan tidak dicetak di dokumentasi.
2. **Inisialisasi git dan commit**: repo sudah diinisialisasi lokal; remote dan push belum.
3. **Deployment**: artefak sudah siap, tetapi belum ada server, domain, database produksi, atau sertifikat.
4. **CI/CD**: workflow sudah ditulis, tetapi belum berjalan karena repo belum di-push ke GitHub.
5. **Audit keamanan eksternal**: belum dilakukan.
6. **Uji beban**: belum dilakukan.
7. **Uji perangkat nyata**: scanner QR belum diuji di berbagai kamera dan browser ponsel.
8. **Pratinjau lampiran**: lampiran tersimpan privat dan belum ada endpoint unduh ber-otorisasi.
9. **Notifikasi**: belum ada email/WhatsApp/push.
10. **2FA**: belum ada.
11. **Reset password mandiri**: belum ada.
12. **Audit data master**: perubahan pengurus/bidang/jabatan/kegiatan belum masuk audit.
13. **Pembatalan finalisasi**: belum ada.
14. **Pengujian aksesibilitas**: belum dilakukan secara formal.

## 6. Keputusan produk

1. Pengajuan izin/sakit yang ditolak boleh diajukan ulang dengan baris yang sama. **Sudah dikonfirmasi: ya.**
2. Koreksi oleh pengurus ber-permission dibatasi pada kegiatan tempat ia menjadi panitia. **Sudah dikonfirmasi: ya, dipertahankan.**
3. Setelah sesi ditutup, status kegiatan langsung menjadi `completed`. **Sudah dikonfirmasi: ya, langsung selesai.**
4. Finalisasi rekap belum dapat dibatalkan. Masih menunggu konfirmasi.
5. Lampiran izin/sakit belum dapat dipratinjau dari aplikasi. Masih menunggu konfirmasi.

## 7. Risiko dan catatan operasional

- **Config cache dan seeder admin**: `AdminUserSeeder` membaca `env()` langsung. Jalankan `php artisan config:clear` sebelum seeding admin, lalu cache ulang.
- **Database test**: `RefreshDatabase` mengosongkan database test. Jangan arahkan ke database kerja atau produksi.
- **QR dan kamera**: kualitas kamera, pencahayaan, dan jarak pindai perlu diuji di lapangan.
- **Waktu server**: keterlambatan bergantung pada jam server. Pastikan NTP aktif.
- **Lampiran privat**: pastikan web server tidak menyajikan `storage/app/private`.
- **Audit terbatas**: hanya mencakup absensi dan finalisasi, belum data master.
- **Tidak ada penghapusan kegiatan**: saat ini tidak ada fitur hapus, sehingga data historis relatif aman.
- **Backup**: script dan cron sudah disiapkan, tetapi belum aktif karena belum deploy.

## 8. Langkah berikutnya

1. Konfirmasi identitas admin awal dan keputusan bisnis pada bagian 6.
2. Inisialisasi git, buat commit awal, dan push ke repo organisasi. **Commit lokal selesai, push menunggu remote.**
3. Siapkan VM Oracle Cloud Always Free, domain, dan akses SSH.
4. Jalankan `deploy/provision.sh`, lalu seed admin produksi dan ganti password.
5. Uji manual end-to-end di browser: login admin, buat kegiatan, tambah panitia, buka sesi, scan dari ponsel, ajukan izin, setujui, koreksi, finalisasi, ekspor.
6. Jalankan checklist `deploy/TESTING.md`: smoke test, QR perangkat nyata, uji beban, keamanan, aksesibilitas, dan restore backup.
7. Aktifkan GitHub Actions dan backup harian, lalu lakukan audit keamanan sebelum go-live.
