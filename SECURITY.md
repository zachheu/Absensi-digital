# SECURITY - Absensi HIMATIF

## 1. Prinsip

- Tidak ada registrasi publik. Akun hanya dibuat admin.
- Hak akses ditentukan oleh role dan permission, bukan jabatan kepanitiaan.
- Validasi kritis dilakukan di server, bukan hanya di browser.
- Data absensi dan audit tidak dihapus dari aplikasi.
- Kredensial dan token tidak ditulis ke log atau source code.
- Prinsip least privilege: pengurus hanya melihat data yang relevan.

## 2. Autentikasi

| Kontrol | Implementasi |
| --- | --- |
| Kredensial | NPP + password |
| Hash password | Cast `hashed` Eloquent, algoritma bawaan Laravel (bcrypt/argon) |
| Session regeneration | Setelah login berhasil |
| Logout | Invalidasi session dan regenerate CSRF token |
| Remember me | Opsional melalui checkbox login |
| Akun nonaktif | Ditolak saat login dan diputus middleware `active` |
| Login throttling | 5 percobaan per menit per NPP + IP |
| Last login | Disimpan di `last_login_at` |

Catatan:

- Tidak ada fitur lupa password mandiri. Reset dilakukan admin.
- Email tidak dipakai untuk login.
- Pesan gagal login dibuat generik ("NPP atau password salah") agar tidak membocorkan keberadaan akun.
- Akun nonaktif tetap diberi pesan khusus agar pengurus tahu harus menghubungi admin.

## 3. Otorisasi

### Role

- `admin`: akses penuh melalui middleware `admin`.
- `pengurus`: akses fitur pengurus.

### Permission

Gate `attendance.correct` didefinisikan di `AppServiceProvider`:

```php
Gate::define('attendance.correct', function (User $user): bool {
    return $user->hasPermission(Permission::ATTENDANCE_CORRECT);
});
```

Admin otomatis lolos melalui `hasPermission()` yang mengembalikan `true` untuk admin.

### Cakupan koreksi pengurus

Pengurus ber-permission hanya boleh mengoreksi absensi pada kegiatan tempat ia terdaftar sebagai panitia. Pengecekan dilakukan di `CorrectionController::authorizeCorrection()` dan mengembalikan 403 bila di luar cakupan.

### Middleware

- `auth`: wajib login.
- `active`: memutus sesi pengguna yang dinonaktifkan.
- `admin`: hanya role admin.
- `guest`: halaman login hanya untuk tamu.

## 4. Keamanan QR Code

| Aspek | Implementasi |
| --- | --- |
| Entropi token | 32 byte acak dari `random_bytes(32)`, direpresentasikan 64 karakter hex |
| Penyimpanan | Hanya `SHA-256(token)` di kolom `token_hash` |
| Rotasi | Setiap polling token mencabut token lama lalu menerbitkan token baru |
| TTL | 15 detik, dibatasi maksimal 20 detik melalui `config/himatif.php` |
| Validasi format | Regex `^[a-f0-9]{64}$` |
| Validasi server | Hash cocok, belum kedaluwarsa, belum dicabut, sesi terbuka, kegiatan valid, rekap belum final |
| Server sebagai sumber kebenaran | Token tidak dapat dipalsukan dari sisi klien |
| Rate limit scan | 12 percobaan per menit per pengguna/IP |
| Token setelah sesi ditutup | Semua token aktif dicabut saat sesi ditutup |

Yang perlu dipahami:

- QR bersifat sementara dan tidak dapat dipakai ulang setelah rotasi.
- Token lama yang sudah dicabut akan ditolak walaupun belum kedaluwarsa.
- Endpoint token hanya dapat diakses admin.
- Token plaintext hanya dikirim ke admin yang menampilkan QR dan tidak disimpan di database.

## 5. Validasi input

- Form request Laravel untuk login, pengguna, kegiatan, izin/sakit, dan koreksi.
- Validasi tipe enum menggunakan `Rule::enum`.
- Validasi tanggal selesai harus setelah tanggal mulai.
- Validasi toleransi keterlambatan 0-240 menit.
- Validasi alasan izin minimal 10 karakter, maksimal 2000 karakter.
- Validasi alasan koreksi minimal 10 karakter.
- Validasi filter laporan menggunakan whitelist status dan `exists` untuk relasi.
- Semua query menggunakan Eloquent/parameter binding.

## 6. Upload lampiran

| Kontrol | Nilai |
| --- | --- |
| Tipe file | JPG, JPEG, PNG, PDF |
| Ukuran maksimal | 2 MB |
| Lokasi | Disk `local` (`storage/app/private/permission-attachments`) |
| Akses publik | Tidak ada route publik untuk mengunduh lampiran |
| Nama file | Diacak oleh Laravel |
| Penggantian | Lampiran lama dihapus saat pengajuan ulang |

Rekomendasi lanjutan:

- Tambahkan pemindaian malware bila lampiran akan diunduh pengguna.
- Tambahkan endpoint unduh dengan otorisasi eksplisit bila fitur pratinjau dibutuhkan.
- Pastikan web server tidak menyajikan folder `storage/app/private`.

## 7. Audit dan integritas data

- Audit ditulis dalam transaksi database yang sama dengan perubahan absensi.
- Koreksi tanpa alasan ditolak sebelum menyentuh data.
- Audit menyimpan nilai sebelum dan sesudah dalam JSON.
- Tidak ada route edit/hapus audit.
- Foreign key `restrictOnDelete` melindungi absensi dan audit dari penghapusan tidak sengaja.
- Unique constraint mencegah duplikasi absensi dan pengajuan.
- Finalisasi rekap berjalan dalam transaksi dan idempotent.

## 8. Perlindungan web umum

| Risiko | Mitigasi |
| --- | --- |
| CSRF | Token `@csrf` pada seluruh form |
| XSS | Escaping Blade `{{ }}`; hindari `{!! !!}` untuk data pengguna |
| SQL injection | Eloquent dan parameter binding |
| Mass assignment | `$fillable` eksplisit pada model |
| Session fixation | Regenerate session setelah login |
| Clickjacking | Tambahkan header `X-Frame-Options` atau CSP di web server |
| MIME sniffing | Tambahkan header `X-Content-Type-Options: nosniff` |
| Information disclosure | `APP_DEBUG=false` di produksi |
| Brute force | Rate limit login dan scan |
| Akses langsung file privat | Folder storage tidak berada di document root |

## 9. Data sensitif dan logging

- Password tidak pernah ditampilkan atau dicatat.
- Token QR plaintext tidak dicatat.
- Lampiran izin/sakit bersifat privat.
- `APP_KEY` wajib unik per lingkungan dan tidak boleh dibagikan.
- Kredensial database hanya di `.env` dan tidak di-commit.
- Log aplikasi dapat memuat NPP dan nama pengurus; batasi akses log.
- Jangan mencetak isi `.env` ke terminal bersama atau tangkapan layar.

## 10. Konfigurasi produksi

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://absensi.himatif.example
APP_TIMEZONE=Asia/Jakarta
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
LOG_LEVEL=warning
```

Checklist tambahan:

- Gunakan HTTPS dan redirect paksa dari HTTP.
- Set cookie `Secure`, `HttpOnly`, dan `SameSite=Lax`.
- Batasi akses `/admin` dan seluruh aplikasi dengan firewall bila perlu.
- Nonaktifkan directory listing.
- Jalankan `php artisan optimize` setelah konfigurasi final.
- Pastikan permission folder `storage` dan `bootstrap/cache` benar, tanpa akses world-write.
- Backup database dan lampiran secara terenkripsi.
- Rotasi kredensial admin setelah serah terima.

## 11. Threat model ringkas

| Skenario | Dampak | Mitigasi |
| --- | --- | --- |
| QR difoto dan dibagikan | Orang lain dapat memindai | Token 15 detik, rotasi, pencabutan token lama, validasi sesi |
| Replay token lama | Absensi ganda | Token dicabut setiap rotasi dan saat sesi ditutup |
| Absensi ganda | Rekap ganda | Unique constraint dan pengecekan existing |
| Pengurus mengoreksi kegiatan lain | Manipulasi data | Cakupan koreksi dibatasi kegiatan panitia |
| Menghapus jejak koreksi | Audit hilang | Append-only dan tanpa route hapus |
| Brute force login | Akun diambil alih | Rate limit, password hash, akun nonaktif |
| Upload file berbahaya | Eksekusi kode | Whitelist MIME, ukuran, disk privat, tanpa eksekusi |
| IDOR pada absensi | Melihat data orang lain | Query dibatasi user untuk halaman pengurus |
| Finalisasi ganda | Data alpa ganda | Transaksi, pengecekan `recap_finalized_at`, unique constraint |
| Approval setelah hadir | Data konflik | Guard hadir dan rekap final |

## 12. Batasan keamanan saat ini

- Belum ada 2FA.
- Belum ada reset password mandiri.
- Belum ada pemindaian malware lampiran.
- Belum ada endpoint unduh lampiran ber-otorisasi.
- Belum ada audit untuk perubahan data master (pengurus, bidang, jabatan, kegiatan).
- Audit hanya mencakup absensi dan finalisasi rekap.
- Belum ada pengujian penetrasi eksternal.

## 13. Checklist sebelum go-live

- [ ] `APP_ENV=production` dan `APP_DEBUG=false`.
- [ ] `APP_KEY` baru dan aman.
- [ ] HTTPS aktif dan cookie secure.
- [ ] Kredensial database produksi kuat dan terbatas.
- [ ] Password admin awal kuat dan diganti setelah serah terima.
- [ ] `HIMATIF_ADMIN_RESET_PASSWORD` dikosongkan.
- [ ] Rate limit login dan scan diuji.
- [ ] Backup otomatis dan uji restore.
- [ ] Folder privat tidak dapat diakses langsung.
- [ ] Log tidak memuat token atau password.
- [ ] Test otomatis lulus di lingkungan staging.
- [ ] Prosedur insiden dan kontak admin ditetapkan.
