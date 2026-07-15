# Sistem Manajemen Tempat Print — Fase 1 (Bulan 1-2)

## Cara menjalankan
1. Pakai XAMPP/Laragon (Apache + MySQL + PHP 8+).
2. Import `database.sql` lewat phpMyAdmin (akan otomatis membuat database `sistem_print`, tabel, FK, dan 10+ data dummy per tabel).
3. Cek kredensial di `config/database.php` (default: user `root`, password kosong — sesuaikan kalau beda).
4. Taruh folder `project` ini di `htdocs` (XAMPP) atau `www` (Laragon).
5. Buka localhost
6. Login pakai salah satu akun dummy, contoh:
   - Admin: `admin1` / `123`
   - Kasir: `kasir1` / `123`

   (password sengaja dibuat pendek untuk mempermudah testing lokal; kalau daftar akun baru lewat form register, tetap wajib minimal 8 karakter)

## Struktur folder
```
project/
├── database.sql              # DDL + dummy data
├── config/
│   ├── database.php          # koneksi PDO
│   └── session.php           # config session (httponly cookie) + CSRF helper
├── includes/
│   ├── functions.php         # helper: e(), flash message, pagination, validasi
│   ├── auth_middleware.php   # requireLogin(), requireRole()
│   └── form_input.php        # reusable component field form
├── auth/
│   ├── register.php
│   ├── login.php
│   └── logout.php
├── dashboard/
│   ├── admin.php
│   └── kasir.php
├── kertas/                   # modul master data (CRUD)
│   ├── index.php             # list + search + sort + pagination
│   ├── create.php
│   ├── edit.php
│   └── delete.php            # soft delete
└── css/style.css
```

## Mapping requirement -> implementasi

| Requirement | Lokasi |
|---|---|
| PK auto increment tiap tabel | `database.sql` — semua `INT UNSIGNED AUTO_INCREMENT PRIMARY KEY` |
| FK integritas referensial | `database.sql` — `fk_transaksi_kertas`, `fk_transaksi_user`, `fk_stoklog_kertas` |
| Timestamp tiap tabel | `database.sql` — `created_at`, `updated_at` di semua tabel |
| Dummy data ≥10/tabel | `database.sql` — bagian INSERT |
| Password hashing | `auth/register.php` (`password_hash`), `auth/login.php` (`password_verify`) |
| Proteksi SQL Injection | `config/database.php` (PDO prepared statement, `EMULATE_PREPARES` false) dipakai di semua query |
| Proteksi XSS | `includes/functions.php` fungsi `e()` dipakai di semua output ke HTML |
| HTTP-only cookie session | `config/session.php` — `session_set_cookie_params(['httponly' => true, ...])` |
| Redirect ke dashboard setelah login | `includes/functions.php` — `redirectToDashboard()`, dipanggil dari `auth/login.php` |
| Pesan error | Semua form (`register.php`, `login.php`, `create.php`, `edit.php`) tampilkan `$errors` per-field + `renderFlash()` |
| Validasi required/tipe/panjang/unique | `includes/functions.php` — `validateKertas()`, juga inline di `register.php` |
| Database transaction multi-tabel | `kertas/create.php` — insert `kertas` + `stok_log` dibungkus `beginTransaction()`/`commit()`/`rollBack()` |
| Flash message | `setFlash()` / `getFlash()` / `renderFlash()` di `includes/functions.php` |
| DRY di CRUD | `validateKertas()` dipakai bareng oleh `create.php` & `edit.php` |
| Reusable form component | `includes/form_input.php` — `renderInput()` |
| CRUD create | `kertas/create.php` — validasi client (`required`, `minlength`, dst di HTML5) + server |
| CRUD read | `kertas/index.php` — pagination, search (`LIKE`), sorting (whitelist kolom) |
| CRUD update | `kertas/edit.php` — form pre-populated dari data existing |
| CRUD delete | `kertas/delete.php` — konfirmasi via `confirm()` di JS + soft delete (`is_deleted`) |

## Catatan keamanan tambahan
- **CSRF**: tiap form POST menyertakan `csrf_token` yang divalidasi pakai `hash_equals()`.
- **Session fixation**: `session_regenerate_id(true)` dipanggil setelah login berhasil.
- **Brute force**: percobaan login dibatasi 5x per session di `auth/login.php`.
- **Role-based access**: `requireRole()` membatasi siapa yang boleh ke halaman mana (admin vs kasir).
- **Kenapa soft delete untuk kertas**: karena kertas kemungkinan sudah dipakai di histori transaksi — hard delete akan memicu FK error (RESTRICT) atau merusak laporan (kalau CASCADE).

## Yang belum termasuk di fase ini
- Modul transaksi cetak (dashboard kasir baru stub) — direncanakan fase berikutnya sesuai timeline PKL.
- Modul stok_log baru dipakai otomatis saat tambah kertas baru; UI khusus stok belum dibuat.
