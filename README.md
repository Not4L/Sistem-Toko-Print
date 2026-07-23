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

## Catatan keamanan tambahan
- **CSRF**: tiap form POST menyertakan `csrf_token` yang divalidasi pakai `hash_equals()`.
- **Session fixation**: `session_regenerate_id(true)` dipanggil setelah login berhasil.
- **Brute force**: percobaan login dibatasi 5x per session di `auth/login.php`.
- **Role-based access**: `requireRole()` membatasi siapa yang boleh ke halaman mana (admin vs kasir).
- **Kenapa soft delete untuk kertas**: karena kertas kemungkinan sudah dipakai di histori transaksi — hard delete akan memicu FK error (RESTRICT) atau merusak laporan (kalau CASCADE).

## Yang belum termasuk di fase ini
- Modul transaksi cetak (dashboard kasir baru stub) — direncanakan fase berikutnya sesuai timeline PKL.
- Modul stok_log baru dipakai otomatis saat tambah kertas baru; UI khusus stok belum dibuat..
