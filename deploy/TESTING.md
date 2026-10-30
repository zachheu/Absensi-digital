# PANDUAN PENGUJIAN - Absensi HIMATIF

Dokumen ini dipakai setelah aplikasi dideploy. Kerjakan berurutan dari yang paling penting.

## 1. Sebelum pengujian

- Domain sudah mengarah ke IP publik VM.
- HTTPS aktif dan tidak ada peringatan sertifikat.
- `APP_ENV=production`, `APP_DEBUG=false`.
- Password admin sudah diganti dari password awal.
- Backup harian sudah berjalan minimal sekali.
- Akun pengurus uji sudah dibuat, minimal satu admin dan dua pengurus.

## 2. Smoke test fungsional

| No | Skenario | Hasil yang diharapkan |
| --- | --- | --- |
| 1 | Buka `/login` | Halaman login tampil tanpa error |
| 2 | Login NPP dan password salah | Tetap di login, ada pesan error |
| 3 | Login admin valid | Masuk dashboard |
| 4 | Buat pengurus baru | Akun muncul di daftar pengurus |
| 5 | Buat kegiatan dan tambah panitia | Panitia tersimpan dengan jabatan |
| 6 | Buka sesi absensi | QR tampil dan berputar tiap 15 detik |
| 7 | Scan QR dari akun pengurus | Absensi tercatat satu kali |
| 8 | Scan ulang kegiatan sama | Ditolak, tidak ada baris ganda |
| 9 | Ajukan izin atau sakit | Pengajuan masuk status pending |
| 10 | Setujui pengajuan | Status berubah dan masuk rekap |
| 11 | Koreksi absensi dengan alasan | Data berubah dan audit bertambah |
| 12 | Tutup sesi | QR mati, kegiatan menjadi selesai |
| 13 | Finalisasi rekap | Alpa diisi untuk panitia tanpa absensi/izin |
| 14 | Ekspor Excel dan PDF | File terunduh dan isinya sesuai filter |

## 3. Uji QR di perangkat nyata

Minimal tiga perangkat: satu Android Chrome, satu iPhone Safari, satu Android lain atau tablet.

| No | Kondisi | Yang dicek |
| --- | --- | --- |
| 1 | Kamera normal, cahaya cukup | QR terbaca dan absensi tercatat |
| 2 | Cahaya redup | QR tetap terbaca atau muncul pesan jelas |
| 3 | Layar admin redup | QR masih terbaca |
| 4 | Scan setelah 20 detik | Ditolak karena token kedaluwarsa, minta scan ulang |
| 5 | Dua pengurus scan hampir bersamaan | Keduanya tercatat, tidak ada deadlock |
| 6 | Scan kegiatan yang sesinya sudah ditutup | Ditolak dengan pesan jelas |
| 7 | Scan saat kegiatan draft | Ditolak |
| 8 | Izin kamera ditolak | Muncul instruksi mengaktifkan izin kamera |
| 9 | Jaringan lambat atau putus | Tidak ada absensi ganda, muncul pesan gagal |
| 10 | Halaman di-refresh saat scan | Sesi login dan halaman tetap stabil |

Catat merek, versi OS, versi browser, dan hasilnya.

## 4. Uji beban

Gunakan k6 dari mesin lokal, bukan dari VM yang sama.

```bash
BASE_URL=https://domain-anda NPP=<npp-uji> PASSWORD=<password-uji> \
VUS=5 DURATION=1m k6 run tests/load/smoke.js
```

Tahapan:

1. Jalankan 1 VU selama 30 detik sebagai baseline.
2. Naikkan 5 VU selama 1 menit.
3. Naikkan 10 VU selama 1 menit hanya bila baseline stabil.
4. Hentikan bila error rate di atas 1 persen atau p95 di atas 1,5 detik.

Yang dicatat: request per detik, p95, error rate, CPU, RAM, dan jumlah koneksi database.

Catatan: free tier 2 OCPU tidak untuk beban besar. Hasil uji beban adalah batas aman awal, bukan jaminan kapasitas produksi.

## 5. Uji keamanan

### 5.1 Pemeriksaan dasar

- `curl -I https://domain-anda` memastikan HTTPS, HSTS setelah certbot, dan header keamanan.
- `nmap -p 22,80,443 <ip>` memastikan hanya port itu yang terbuka.
- Pastikan port 3306 tidak terbuka dari internet.
- Pastikan `/storage/app/private` dan `/.env` menghasilkan 403 atau 404.
- Pastikan `/up` mengembalikan 200.
- Pastikan `APP_DEBUG=false` tidak menampilkan stack trace saat error.

### 5.2 OWASP ZAP baseline

Jalankan dari mesin lokal:

```bash
docker run --rm -t ghcr.io/zaproxy/zaproxy:stable \
  zap-baseline.py -t https://domain-anda -r zap-report.html
```

Periksa temuan high dan medium. Jangan menyerang server tanpa izin pemilik.

### 5.3 Uji otorisasi manual

| No | Skenario | Hasil yang diharapkan |
| --- | --- | --- |
| 1 | Pengurus membuka `/admin/users` | 403 |
| 2 | Pengurus mengubah absensi pengurus lain tanpa permission | 403 |
| 3 | Pengurus ber-permission mengoreksi kegiatan luar kepanitiaannya | 403 |
| 4 | Admin menonaktifkan akun sendiri | Ditolak |
| 5 | Admin menurunkan role sendiri | Ditolak |
| 6 | IDOR: ganti ID kegiatan/absensi di URL | 403 atau 404, bukan data orang lain |
| 7 | CSRF: kirim POST tanpa token | 419 |
| 8 | Login salah berulang | Kena rate limit |
| 9 | Scan berulang | Kena rate limit scan |
| 10 | Upload lampiran lebih dari 2 MB | Ditolak |

### 5.4 Audit keamanan eksternal

Audit pihak ketiga tidak bisa digantikan oleh pengujian internal. Minimal minta review independen sebelum data absensi resmi dipakai permanen.

## 6. Uji aksesibilitas

- Lighthouse di Chrome DevTools, target accessibility minimal 90.
- axe DevTools, tidak ada serious atau critical.
- Navigasi keyboard: Tab, Enter, Escape, dan fokus terlihat di semua form.
- Kontras teks army/cream memenuhi WCAG AA.
- Form punya label, pesan error terbaca, dan status tidak hanya dibedakan warna.
- Uji pembaca layar dasar di Android TalkBack atau iOS VoiceOver pada halaman login, dashboard, dan scan.

## 7. Uji backup dan restore

1. Jalankan `sudo bash deploy/backup.sh`.
2. Pastikan file database, storage, dan checksum terbentuk di `/var/backups/absensi`.
3. Unduh satu salinan ke mesin lain.
4. Di database uji terpisah, restore dump dan buka aplikasi.
5. Pastikan jumlah pengurus, kegiatan, absensi, dan lampiran sama.
6. Catat waktu restore dan kendala.

Jangan menguji restore di database produksi.

## 8. Fitur yang belum bisa diuji

Fitur berikut belum dibangun, jadi belum ada yang bisa diuji sampai scope disetujui:

- Pratinjau atau unduh lampiran izin/sakit ber-otorisasi.
- Pembatalan finalisasi rekap.
- Audit perubahan data master.
- Notifikasi email, WhatsApp, atau push.
- 2FA.
- Reset password mandiri.

## 9. Kriteria lulus

- Semua smoke test fungsional lulus.
- Uji QR di minimal tiga perangkat lulus, tanpa absensi ganda.
- Error rate uji beban di bawah 1 persen dan p95 di bawah 1,5 detik pada beban yang disepakati.
- Tidak ada temuan high pada pemeriksaan keamanan dasar dan ZAP.
- Tidak ada serious atau critical pada axe.
- Backup dan restore terbukti berhasil.
