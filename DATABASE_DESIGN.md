# DATABASE DESIGN - Absensi HIMATIF

## 1. Prinsip desain

- MariaDB/MySQL dengan engine InnoDB dan charset `utf8mb4`.
- Nama tabel dan kolom menggunakan `snake_case` sesuai konvensi Laravel.
- Primary key memakai unsigned big integer auto increment.
- Foreign key diberi perilaku yang aman: `restrictOnDelete` untuk data historis, `cascadeOnDelete` untuk data turunan sesi/token, dan `nullOnDelete` untuk referensi pelaku.
- Status disimpan sebagai string enum aplikasi agar mudah dibaca dan dimigrasikan.
- Duplikasi absensi dicegah di level database melalui unique constraint.
- Audit tidak memiliki `updated_at` dan tidak disediakan fitur edit/hapus.

## 2. ERD

```mermaid
erDiagram
    ORGANIZATIONAL_FIELDS ||--o{ USERS : "memiliki bidang"
    USERS ||--o{ ACTIVITIES : "membuat"
    USERS ||--o{ COMMITTEE_MEMBERS : "menjadi panitia"
    USERS ||--o{ ATTENDANCES : "memiliki absensi"
    USERS ||--o{ PERMISSION_REQUESTS : "mengajukan"
    USERS ||--o{ PERMISSION_USER : "diberi permission"
    PERMISSIONS ||--o{ PERMISSION_USER : "dimiliki"

    ACTIVITIES ||--o{ COMMITTEE_MEMBERS : "memiliki panitia"
    ACTIVITIES ||--o{ ATTENDANCE_SESSIONS : "memiliki sesi"
    ACTIVITIES ||--o{ ATTENDANCES : "memiliki absensi"
    ACTIVITIES ||--o{ PERMISSION_REQUESTS : "memiliki pengajuan"
    ACTIVITIES ||--o{ ATTENDANCE_AUDITS : "memiliki audit"

    COMMITTEE_POSITIONS ||--o{ COMMITTEE_MEMBERS : "dipakai"

    ATTENDANCE_SESSIONS ||--o{ QR_TOKENS : "menerbitkan"

    ATTENDANCES ||--o{ ATTENDANCE_AUDITS : "diaudit"

    ORGANIZATIONAL_FIELDS {
        bigint id PK
        varchar code UK
        varchar name
        text description
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    USERS {
        bigint id PK
        varchar npp UK
        varchar npm UK
        varchar name
        varchar email UK_NULL
        varchar password
        varchar role
        bigint organizational_field_id FK
        boolean is_active
        timestamp last_login_at
        timestamp created_at
        timestamp updated_at
    }

    COMMITTEE_POSITIONS {
        bigint id PK
        varchar name UK
        varchar category
        text description
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    PERMISSIONS {
        bigint id PK
        varchar name UK
        varchar description
        timestamp created_at
        timestamp updated_at
    }

    PERMISSION_USER {
        bigint id PK
        bigint user_id FK
        bigint permission_id FK
        timestamp created_at
        timestamp updated_at
    }

    ACTIVITIES {
        bigint id PK
        varchar name
        text description
        varchar location
        datetime start_at
        datetime end_at
        smallint late_tolerance_minutes
        varchar status
        bigint created_by FK
        timestamp recap_finalized_at
        timestamp created_at
        timestamp updated_at
    }

    COMMITTEE_MEMBERS {
        bigint id PK
        bigint activity_id FK
        bigint user_id FK
        bigint committee_position_id FK
        timestamp created_at
        timestamp updated_at
    }

    ATTENDANCE_SESSIONS {
        bigint id PK
        bigint activity_id FK
        varchar status
        bigint opened_by FK_NULL
        bigint closed_by FK_NULL
        timestamp opened_at
        timestamp closed_at
        timestamp created_at
        timestamp updated_at
    }

    QR_TOKENS {
        bigint id PK
        bigint attendance_session_id FK
        char token_hash UK
        timestamp issued_at
        timestamp expires_at
        timestamp revoked_at
        timestamp created_at
        timestamp updated_at
    }

    ATTENDANCES {
        bigint id PK
        bigint activity_id FK
        bigint user_id FK
        varchar status
        timestamp check_in_at
        boolean is_late
        varchar source
        text notes
        timestamp created_at
        timestamp updated_at
    }

    PERMISSION_REQUESTS {
        bigint id PK
        bigint activity_id FK
        bigint user_id FK
        varchar type
        text reason
        varchar attachment_path
        varchar status
        bigint reviewed_by FK_NULL
        timestamp reviewed_at
        text review_notes
        timestamp created_at
        timestamp updated_at
    }

    ATTENDANCE_AUDITS {
        bigint id PK
        bigint attendance_id FK
        bigint activity_id FK
        bigint user_id FK
        bigint actor_user_id FK_NULL
        varchar action
        json old_values
        json new_values
        text reason
        timestamp created_at
    }
```

## 3. Detail tabel

### 3.1 `organizational_fields`

Menyimpan bidang dan unit organisasi HIMATIF.

| Kolom | Tipe | Aturan |
| --- | --- | --- |
| `id` | bigint unsigned | PK |
| `code` | varchar(20) | unique, not null |
| `name` | varchar(255) | not null |
| `description` | text | nullable |
| `is_active` | boolean | default true |
| `created_at`, `updated_at` | timestamp | nullable |

Data awal: `HRDO`, `RE`, `PR`, `CNB`, dan `STAFF_INTI` dengan nama `Staff Inti` serta deskripsi `Unit pimpinan himpunan`.

### 3.2 `users`

Tabel bawaan Laravel yang diperluas untuk kebutuhan HIMATIF.

| Kolom | Tipe | Aturan |
| --- | --- | --- |
| `id` | bigint unsigned | PK |
| `npp` | varchar(30) | unique, not null |
| `npm` | varchar(30) | unique, not null |
| `name` | varchar(255) | not null |
| `email` | varchar(255) | nullable, unique |
| `password` | varchar(255) | not null, di-hash |
| `role` | varchar(20) | index, default `pengurus` |
| `organizational_field_id` | bigint unsigned | FK ke `organizational_fields`, nullable, restrict on delete |
| `structural_position` | varchar(40) | index, nullable, enum aplikasi |
| `is_active` | boolean | default true |
| `last_login_at` | timestamp | nullable |
| `remember_token` | varchar(100) | nullable |
| `created_at`, `updated_at` | timestamp | nullable |

Catatan:

- `npp` adalah username login.
- `npm` adalah identitas mahasiswa dan wajib unik.
- Email tidak dipakai untuk login dan boleh kosong.
- Role: `admin`, `pengurus`.
- `structural_position` hanya diisi untuk pengurus Staff Inti: `walikota`, `wakil_walikota`, `sekretaris_umum`, `bendahara_umum`. Kolom ini tidak menambah hak akses.

### 3.3 `committee_positions`

Dua belas jabatan kepanitiaan.

| Kolom | Tipe | Aturan |
| --- | --- | --- |
| `id` | bigint unsigned | PK |
| `name` | varchar(255) | unique, not null |
| `category` | varchar(20) | index, not null |
| `description` | text | nullable |
| `is_active` | boolean | default true |
| `created_at`, `updated_at` | timestamp | nullable |

Kategori: `structural`, `division`.

### 3.4 `permissions` dan `permission_user`

| Tabel | Kolom | Aturan |
| --- | --- | --- |
| `permissions` | `name` | varchar(100), unique |
| `permissions` | `description` | varchar(255), nullable |
| `permission_user` | `user_id` | FK users, cascade on delete |
| `permission_user` | `permission_id` | FK permissions, cascade on delete |
| `permission_user` | `(user_id, permission_id)` | unique |

Permission awal: `attendance.correct`.

### 3.5 `activities`

| Kolom | Tipe | Aturan |
| --- | --- | --- |
| `id` | bigint unsigned | PK |
| `name` | varchar(255) | not null |
| `description` | text | nullable |
| `location` | varchar(255) | nullable |
| `start_at` | datetime | not null, index |
| `end_at` | datetime | not null |
| `late_tolerance_minutes` | smallint unsigned | default 15 |
| `status` | varchar(20) | index, default `draft` |
| `created_by` | bigint unsigned | FK users, restrict on delete |
| `recap_finalized_at` | timestamp | nullable |
| `created_at`, `updated_at` | timestamp | nullable |

Status: `draft`, `published`, `ongoing`, `completed`. Status `ongoing` ditetapkan saat sesi absensi dibuka dan `completed` saat sesi ditutup. `recap_finalized_at` menandai rekap sudah final dan terpisah dari status kegiatan.

### 3.6 `committee_members`

Menghubungkan pengurus, kegiatan, dan jabatan. Satu pengurus boleh memiliki beberapa jabatan pada kegiatan yang sama.

| Kolom | Tipe | Aturan |
| --- | --- | --- |
| `id` | bigint unsigned | PK |
| `activity_id` | bigint unsigned | FK activities, cascade on delete |
| `user_id` | bigint unsigned | FK users, restrict on delete |
| `committee_position_id` | bigint unsigned | FK committee_positions, restrict on delete |
| `created_at`, `updated_at` | timestamp | nullable |

Constraint:

- Unique `committee_members_unique (activity_id, user_id, committee_position_id)`.
- Index `(activity_id, committee_position_id)`.

### 3.7 `attendance_sessions`

| Kolom | Tipe | Aturan |
| --- | --- | --- |
| `id` | bigint unsigned | PK |
| `activity_id` | bigint unsigned | FK activities, cascade on delete |
| `status` | varchar(20) | index, default `open` |
| `opened_by` | bigint unsigned | FK users, nullable, null on delete |
| `closed_by` | bigint unsigned | FK users, nullable, null on delete |
| `opened_at` | timestamp | nullable |
| `closed_at` | timestamp | nullable |
| `created_at`, `updated_at` | timestamp | nullable |

Constraint tambahan:

- Index `(activity_id, status)`. Aplikasi memastikan hanya satu sesi terbuka per kegiatan melalui transaksi dan pengecekan `openSession()`.

Status: `open`, `closed`.

### 3.8 `qr_tokens`

| Kolom | Tipe | Aturan |
| --- | --- | --- |
| `id` | bigint unsigned | PK |
| `attendance_session_id` | bigint unsigned | FK attendance_sessions, cascade on delete |
| `token_hash` | char(64) | unique, not null |
| `issued_at` | timestamp | not null |
| `expires_at` | timestamp | not null |
| `revoked_at` | timestamp | nullable |
| `created_at`, `updated_at` | timestamp | nullable |

Constraint tambahan:

- Index `(attendance_session_id, expires_at)`.

Token asli tidak pernah disimpan. Database hanya menyimpan `SHA-256(token)`.

### 3.9 `attendances`

| Kolom | Tipe | Aturan |
| --- | --- | --- |
| `id` | bigint unsigned | PK |
| `activity_id` | bigint unsigned | FK activities, restrict on delete |
| `user_id` | bigint unsigned | FK users, restrict on delete |
| `status` | varchar(20) | index, default `hadir` |
| `check_in_at` | timestamp | nullable |
| `is_late` | boolean | default false |
| `source` | varchar(20) | default `qr` |
| `notes` | text | nullable |
| `created_at`, `updated_at` | timestamp | nullable |

Constraint:

- Unique `(activity_id, user_id)` mencegah absensi ganda.
- Index `(user_id, status)`.

Status: `hadir`, `izin`, `sakit`, `alpa`.

Source: `qr`, `manual`, `permission`, `system`.

### 3.10 `permission_requests`

| Kolom | Tipe | Aturan |
| --- | --- | --- |
| `id` | bigint unsigned | PK |
| `activity_id` | bigint unsigned | FK activities, restrict on delete |
| `user_id` | bigint unsigned | FK users, restrict on delete |
| `type` | varchar(20) | not null |
| `reason` | text | not null |
| `attachment_path` | varchar(255) | nullable, disk privat |
| `status` | varchar(20) | index, default `pending` |
| `reviewed_by` | bigint unsigned | FK users, nullable, null on delete |
| `reviewed_at` | timestamp | nullable |
| `review_notes` | text | nullable |
| `created_at`, `updated_at` | timestamp | nullable |

Constraint:

- Unique `(activity_id, user_id)`.

Status: `pending`, `approved`, `rejected`.
Tipe: `izin`, `sakit`.

### 3.11 `attendance_audits`

| Kolom | Tipe | Aturan |
| --- | --- | --- |
| `id` | bigint unsigned | PK |
| `attendance_id` | bigint unsigned | FK attendances, restrict on delete |
| `activity_id` | bigint unsigned | FK activities, restrict on delete |
| `user_id` | bigint unsigned | FK users, restrict on delete |
| `actor_user_id` | bigint unsigned | FK users, nullable, null on delete |
| `action` | varchar(50) | not null |
| `old_values` | json | nullable |
| `new_values` | json | not null |
| `reason` | text | not null |
| `created_at` | timestamp | default current timestamp |

Constraint:

- Index `(attendance_id, created_at)`.
- Index `(activity_id, created_at)`.
- Tidak ada `updated_at`; audit bersifat append-only.

Action yang dipakai: `attendance.corrected`, `permission.approved`, `recap.finalized`.

## 4. Relasi dan kardinalitas

| Relasi | Kardinalitas |
| --- | --- |
| Bidang ke pengurus | 1 : N |
| Pengurus ke kegiatan yang dibuat | 1 : N |
| Kegiatan ke panitia | 1 : N |
| Jabatan ke penugasan panitia | 1 : N |
| Pengurus ke penugasan panitia | 1 : N |
| Kegiatan ke sesi absensi | 1 : N |
| Sesi ke token QR | 1 : N |
| Kegiatan ke absensi | 1 : N |
| Pengurus ke absensi | 1 : N |
| Kegiatan ke pengajuan izin | 1 : N |
| Absensi ke audit | 1 : N |
| Pengurus ke permission | N : N melalui `permission_user` |

## 5. Aturan integritas data

1. `users.npp` dan `users.npm` unik.
2. `organizational_fields.code` unik.
3. `committee_positions.name` unik.
4. Penugasan panitia unik per kegiatan + pengurus + jabatan.
5. Absensi unik per kegiatan + pengurus.
6. Pengajuan izin unik per kegiatan + pengurus.
7. Token QR unik berdasarkan hash.
8. Data historis absensi, pengajuan, dan audit memakai `restrictOnDelete` agar tidak terhapus tidak sengaja.
9. Sesi dan token boleh terhapus berantai bila kegiatan dihapus, tetapi aplikasi tidak menyediakan penghapusan kegiatan.
10. Audit ditulis dalam transaksi yang sama dengan perubahan absensi.

## 6. Indeks dan performa

| Tabel | Index | Kegunaan |
| --- | --- | --- |
| `users` | `npp`, `npm`, `role` | Login dan filter |
| `activities` | `start_at`, `status` | Daftar dan filter laporan |
| `attendance_sessions` | `(activity_id, status)` | Mencari sesi terbuka |
| `qr_tokens` | `token_hash`, `(attendance_session_id, expires_at)` | Validasi token dan pencarian token aktif |
| `committee_members` | `(activity_id, committee_position_id)` | Filter jabatan pada laporan |
| `attendances` | `(activity_id, user_id)` unique, `(user_id, status)` | Cegah duplikasi dan rekap per pengurus |
| `permission_requests` | `(activity_id, user_id)` unique, `status` | Cegah pengajuan ganda dan daftar persetujuan |
| `attendance_audits` | `(attendance_id, created_at)`, `(activity_id, created_at)` | Riwayat audit |

## 7. Query rekap dan pencegahan hitung ganda

Rekap dibangun dari tabel `attendances`, bukan dari join langsung ke `committee_members`. Filter jabatan menggunakan subquery `whereExists` ke `committee_members` dengan kunci `user_id` dan `activity_id`.

Dengan pendekatan ini:

- Satu baris absensi tetap menghasilkan satu baris laporan.
- Pengurus dengan dua jabatan pada kegiatan yang sama tidak terduplikasi.
- Label jabatan diambil terpisah sebagai daftar nama jabatan yang dipisahkan koma.
- Ringkasan total dihitung dari koleksi absensi yang sama.

## 8. Migrasi dan seeding

- Seluruh migrasi berada di `database/migrations/` dan sudah dijalankan pada database development.
- Seeder idempotent: `OrganizationalFieldSeeder`, `CommitteePositionSeeder`, `PermissionSeeder`.
- `AdminUserSeeder` hanya membuat akun bila environment admin lengkap.
- Tidak ada migrasi destruktif otomatis. Jangan menjalankan `migrate:fresh` pada database berisi data produksi.

## 9. Backup dan retensi

- Backup harian database dan folder `storage/app/private/permission-attachments`.
- Audit tidak boleh dihapus; retensi minimal mengikuti kebijakan organisasi.
- Simpan backup terenkripsi dan batasi aksesnya.
- Uji restore secara berkala, bukan hanya membuat backup.
