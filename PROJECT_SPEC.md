# PROJECT SPEC - Absensi Digital HIMATIF

## 1. Latar belakang dan tujuan

HIMATIF membutuhkan pencatatan kehadiran pengurus pada kegiatan organisasi yang lebih tertib dibanding daftar hadir manual. Tujuan sistem:

1. Mencatat kehadiran pengurus secara cepat melalui QR Code dinamis.
2. Menyediakan mekanisme izin/sakit yang dapat diaudit.
3. Memungkinkan koreksi data dengan jejak audit yang tidak dapat dihapus dari aplikasi.
4. Menghasilkan rekap yang konsisten, tanpa perhitungan ganda, dan dapat diekspor.

## 2. Ruang lingkup

### Termasuk

- Login NPP + password tanpa registrasi publik.
- Manajemen pengurus oleh admin.
- Manajemen bidang, jabatan kepanitiaan, kegiatan, dan susunan panitia.
- Sesi absensi per kegiatan dan QR Code dinamis.
- Absensi QR oleh pengurus.
- Pengajuan dan persetujuan izin/sakit.
- Koreksi absensi dengan audit.
- Rekap, filter, ekspor Excel, dan ekspor PDF.
- Dashboard admin dan pengurus.

### Tidak termasuk

- Registrasi mandiri atau reset password mandiri via email.
- Integrasi SIAKAD atau sistem akademik kampus.
- Absensi berbasis GPS, wajah, atau sidik jari.
- Notifikasi WhatsApp, email, atau push.
- Multi-organisasi selain HIMATIF.
- Aplikasi mobile native.
- Pembayaran atau fitur keuangan.

## 3. Aktor dan hak akses

| Aktor | Deskripsi | Hak akses |
| --- | --- | --- |
| Admin | Pengurus dengan role `admin` | Seluruh fitur dan data |
| Pengurus | Pengurus dengan role `pengurus` | Kegiatan, absensi QR, izin/sakit, absensi sendiri |
| Pengurus ber-permission | Pengurus dengan `attendance.correct` | Koreksi absensi pada kegiatan tempat ia menjadi panitia |

Catatan:

- Admin otomatis memiliki semua permission.
- Jabatan kepanitiaan tidak memberikan hak akses aplikasi.
- Bidang tidak menentukan hak akses.

## 4. Alur utama

### 4.1 Login

1. Pengurus membuka halaman login.
2. Pengurus memasukkan NPP dan password.
3. Sistem membatasi percobaan login (5 percobaan per menit per NPP + IP).
4. Akun nonaktif ditolak dan sesi yang sedang berjalan diputus middleware `active`.
5. Setelah berhasil, `last_login_at` diperbarui dan pengguna diarahkan ke dashboard.

### 4.2 Pengelolaan kegiatan

1. Admin membuat kegiatan dengan nama, waktu mulai/selesai, lokasi, dan toleransi keterlambatan.
2. Status awal kegiatan adalah `draft` atau `published`.
3. Admin menambahkan panitia beserta jabatan kepanitiaan.
4. Admin membuka sesi absensi saat kegiatan berlangsung.
5. Membuka sesi mengubah status kegiatan menjadi `ongoing`.
6. Menutup sesi mencabut seluruh token QR yang masih aktif dan mengubah status kegiatan menjadi `completed`.

### 4.3 Absensi QR

1. Admin membuka sesi dan menampilkan QR Code.
2. QR berisi token acak 64 karakter hex dan diperbarui setiap 15 detik.
3. Setiap rotasi mencabut token sebelumnya.
4. Pengurus memindai QR melalui halaman scan.
5. Server memvalidasi format token, hash, masa berlaku, status sesi, status kegiatan, dan finalisasi rekap.
6. Bila valid, sistem membuat satu baris absensi berstatus `hadir`.
7. Bila waktu check-in melewati `start_at + late_tolerance_minutes`, `is_late` diset `true`.
8. Pemindaian ulang mengembalikan pesan "sudah tercatat" tanpa membuat baris baru.

### 4.4 Izin dan sakit

1. Pengurus yang terdaftar sebagai panitia dapat mengajukan izin/sakit.
2. Pengajuan wajib memiliki alasan minimal 10 karakter.
3. Lampiran opsional: JPG, JPEG, PNG, atau PDF, maksimal 2 MB.
4. Satu pengurus hanya memiliki satu pengajuan per kegiatan.
5. Pengajuan berstatus `pending` atau `approved` tidak dapat diajukan ulang.
6. Pengajuan yang ditolak boleh diajukan ulang; baris yang sama di-reset menjadi `pending`.
7. Admin menyetujui atau menolak.
8. Penolakan wajib menyertakan catatan alasan.
9. Persetujuan membuat atau memperbarui absensi menjadi status `izin`/`sakit` dengan sumber `permission`.
10. Persetujuan ditolak bila pengurus sudah tercatat hadir atau rekap sudah difinalisasi.

### 4.5 Koreksi absensi

1. Admin atau pengurus ber-permission dapat membuka form koreksi.
2. Pengurus ber-permission hanya boleh mengoreksi kegiatan tempat ia menjadi panitia.
3. Perubahan status, waktu check-in, dan catatan disimpan dalam transaksi.
4. Setiap koreksi wajib memiliki alasan minimal 10 karakter.
5. Audit ditulis pada transaksi yang sama dengan perubahan absensi.
6. Audit bersifat append-only dan tidak memiliki fitur edit/hapus dari aplikasi.

### 4.6 Finalisasi rekap

1. Finalisasi hanya dapat dilakukan admin.
2. Finalisasi ditolak bila masih ada sesi absensi terbuka.
3. Sistem menetapkan `alpa` untuk panitia yang:
   - belum memiliki absensi, dan
   - tidak memiliki pengajuan izin/sakit berstatus `approved`.
4. Pengajuan `pending` dan `rejected` tidak diubah.
5. Finalisasi mengisi `recap_finalized_at`; status kegiatan sudah menjadi `completed` sejak sesi ditutup.
6. Finalisasi bersifat idempotent; pemanggilan ulang tidak membuat data baru.
7. Setelah finalisasi, QR dan persetujuan izin baru tidak dapat diproses.

### 4.7 Rekap dan ekspor

1. Rekap menampilkan satu baris per absensi.
2. Pengurus dengan beberapa jabatan pada kegiatan yang sama tetap dihitung satu kali.
3. Filter tersedia: rentang tanggal, kegiatan, pengurus, bidang, jabatan, status, dan keterlambatan.
4. Ringkasan menampilkan total, hadir, izin, sakit, alpa, dan terlambat.
5. Ekspor Excel dan PDF mengikuti filter yang sedang aktif.

## 5. Kebutuhan fungsional

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-01 | Login NPP + password | Wajib |
| FR-02 | Logout dan invalidasi sesi | Wajib |
| FR-03 | Middleware akun aktif | Wajib |
| FR-04 | CRUD pengurus oleh admin | Wajib |
| FR-05 | Manajemen bidang | Wajib |
| FR-06 | Manajemen jabatan kepanitiaan | Wajib |
| FR-07 | Manajemen kegiatan | Wajib |
| FR-08 | Manajemen susunan panitia per kegiatan | Wajib |
| FR-09 | Buka/tutup sesi absensi | Wajib |
| FR-10 | QR dinamis dengan rotasi dan TTL | Wajib |
| FR-11 | Absensi QR dengan validasi server | Wajib |
| FR-12 | Pencegahan absensi ganda | Wajib |
| FR-13 | Penanda keterlambatan | Wajib |
| FR-14 | Pengajuan izin/sakit | Wajib |
| FR-15 | Persetujuan/penolakan izin/sakit | Wajib |
| FR-16 | Koreksi absensi dengan alasan | Wajib |
| FR-17 | Audit log append-only | Wajib |
| FR-18 | Finalisasi rekap dan penetapan alpa | Wajib |
| FR-19 | Rekap dengan filter | Wajib |
| FR-20 | Ekspor Excel | Wajib |
| FR-21 | Ekspor PDF | Wajib |
| FR-22 | Dashboard admin dan pengurus | Wajib |
| FR-23 | Pemberian permission khusus | Wajib |

## 6. Aturan bisnis

| ID | Aturan |
| --- | --- |
| BR-01 | Login hanya dengan NPP + password; tidak ada registrasi publik. |
| BR-02 | NPM wajib unik dan disimpan sebagai identitas mahasiswa. |
| BR-03 | Bidang: HRDO, RE, PR, CNB, dan Staff Inti. Staff Inti berisi Walikota Himpunan, Wakil Walikota Himpunan, Sekretaris Umum, dan Bendahara Umum. |
| BR-04 | Ada 12 jabatan kepanitiaan, terpisah dari bidang. |
| BR-05 | Jabatan tidak memberikan hak akses otomatis. |
| BR-06 | Admin selalu memiliki seluruh permission. |
| BR-07 | Permission `attendance.correct` dapat diberikan ke pengurus. |
| BR-08 | Pengurus ber-permission hanya dapat mengoreksi kegiatan tempat ia menjadi panitia. |
| BR-09 | Satu absensi per pengurus per kegiatan (unique). |
| BR-10 | Terlambat bukan status terpisah; status tetap `hadir` dengan `is_late = true`. |
| BR-11 | QR berlaku maksimal 20 detik dan dirotasi setiap 15 detik. |
| BR-12 | Token QR disimpan sebagai hash SHA-256, bukan plaintext. |
| BR-13 | Setiap rotasi mencabut token sebelumnya. |
| BR-14 | QR hanya valid saat sesi terbuka, kegiatan bukan draft/selesai, dan rekap belum final. |
| BR-15 | Satu pengajuan izin/sakit per pengurus per kegiatan. |
| BR-16 | Pengajuan ditolak boleh diajukan ulang dengan baris yang sama. |
| BR-17 | Persetujuan izin/sakit ditolak bila sudah hadir atau rekap final. |
| BR-18 | Koreksi wajib alasan minimal 10 karakter. |
| BR-19 | Audit koreksi ditulis atomik bersama perubahan. |
| BR-20 | Audit tidak dapat diubah/dihapus dari aplikasi. |
| BR-21 | Finalisasi menetapkan alpa hanya untuk yang tanpa absensi dan tanpa izin disetujui. |
| BR-22 | Pengajuan pending tidak diubah saat finalisasi. |
| BR-23 | Status `ongoing` dan `completed` hanya ditetapkan lewat aksi sesi/finalisasi, bukan form edit. |
| BR-24 | Rekap tidak boleh menghitung ganda. |

## 7. Kebutuhan non-fungsional

### Keamanan

- Password di-hash dengan algoritma bawaan Laravel.
- CSRF aktif pada seluruh form.
- Escaping Blade untuk mencegah XSS.
- Query Eloquent/parameter binding untuk mencegah SQL injection.
- Rate limit login dan scan.
- Validasi tipe dan ukuran file lampiran.
- Lampiran disimpan di disk privat.

### Kinerja

- Index pada kolom filter utama: status, tanggal kegiatan, relasi absensi.
- Unique constraint mencegah duplikasi di level database.
- Pagination pada daftar pengurus, kegiatan, pengajuan, audit, dan rekap.

### Usabilitas

- Seluruh teks UI berbahasa Indonesia.
- Warna utama hijau army, warna tambahan cream.
- Navigasi responsif untuk desktop dan ponsel.
- Pesan error validasi berbahasa Indonesia.

### Operasional

- Zona waktu default `Asia/Jakarta`.
- Konfigurasi QR dan rate limit terpusat di `config/himatif.php`.
- Seeder idempotent dan aman dijalankan ulang.
- Tidak ada migrasi destruktif otomatis di produksi.

## 8. Siklus status kegiatan

```
draft -> published -> ongoing -> completed
```

- `draft`: tidak terlihat oleh pengurus, QR tidak dapat dibuka.
- `published`: terlihat pengurus, belum ada sesi.
- `ongoing`: sesi absensi dibuka.
- `completed`: sesi absensi ditutup; rekap belum tentu final.

Form edit hanya dapat mengubah `draft` menjadi `published`. Status `ongoing` ditetapkan saat sesi dibuka, dan `completed` ditetapkan saat sesi ditutup. Finalisasi rekap hanya mengisi `recap_finalized_at`, bukan mengubah status lagi.

## 9. Kriteria penerimaan

1. Pengurus dapat login dengan NPP + password dan tidak dapat mengakses halaman admin.
2. Admin dapat membuat kegiatan, menambah panitia, dan membuka sesi QR.
3. Token QR lama tidak dapat dipakai setelah rotasi.
4. Token kedaluwarsa, dicabut, atau sesi tertutup ditolak server.
5. Pemindaian ganda tidak membuat baris absensi kedua.
6. Absensi setelah batas toleransi memiliki `is_late = true` dan status `hadir`.
7. Pengajuan izin/sakit yang disetujui menghasilkan absensi berstatus sesuai jenis.
8. Persetujuan izin ditolak bila pengurus sudah hadir.
9. Koreksi tanpa alasan ditolak dan tidak menulis audit.
10. Koreksi yang valid menulis audit dengan nilai sebelum/sesudah.
11. Pengurus tanpa permission tidak dapat mengoreksi.
12. Finalisasi menetapkan alpa hanya pada panitia yang tepat.
13. Rekap menampilkan satu baris per absensi.
14. Ekspor Excel dan PDF mengikuti filter.
15. Seluruh test otomatis lulus.

## 10. Keputusan produk

1. Pengajuan izin/sakit yang ditolak boleh diajukan ulang dengan baris yang sama. **Sudah dikonfirmasi: ya, dipertahankan.**
2. Cakupan koreksi pengurus ber-permission dibatasi pada kegiatan tempat ia menjadi panitia. **Sudah dikonfirmasi: ya, dipertahankan.**
3. Status kegiatan setelah sesi ditutup langsung menjadi `completed`. **Sudah dikonfirmasi: ya, langsung selesai.**
4. Apakah perlu fitur pembatalan finalisasi rekap? Saat ini tidak ada.
5. Apakah perlu notifikasi eksternal? Saat ini tidak ada.
6. Unit Staff Inti ditambahkan sebagai bidang ke-5 dengan jabatan struktural per pengurus: Walikota Himpunan, Wakil Walikota Himpunan, Sekretaris Umum, dan Bendahara Umum. **Sudah dikonfirmasi: ya.**
