-- =====================================================================
-- DDL (Data Definition Language) Script
-- Sistem Manajemen Tempat Print - PKL Project
-- Database: sistem_print
-- =====================================================================

CREATE DATABASE IF NOT EXISTS sistem_print
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE sistem_print;

-- ---------------------------------------------------------------------
-- Tabel: users
-- Menyimpan akun penjaga print (admin & kasir). Pelanggan tidak login,
-- dicatat langsung di tabel transaksi sesuai alur bisnis di lapangan.
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id_user        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username       VARCHAR(50)  NOT NULL,
    password       VARCHAR(255) NOT NULL,          -- hasil password_hash() bcrypt
    role           ENUM('admin', 'kasir') NOT NULL DEFAULT 'kasir',
    nama_lengkap   VARCHAR(100) NOT NULL,
    is_active      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_users_username UNIQUE (username)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: kertas
-- Master data jenis kertas. is_deleted dipakai untuk soft delete karena
-- kertas bisa saja sudah dipakai di transaksi lama (jangan hard delete
-- data yang punya riwayat transaksi -> bisa merusak laporan histori).
-- ---------------------------------------------------------------------
CREATE TABLE kertas (
    id_kertas        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_jenis       VARCHAR(50)    NOT NULL,
    harga_per_lembar INT UNSIGNED   NOT NULL,
    stok             INT            NOT NULL DEFAULT 0,
    is_deleted       TINYINT(1)     NOT NULL DEFAULT 0,
    created_at       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_kertas_nama UNIQUE (nama_jenis)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: transaksi
-- Setiap transaksi cetak. FK ke kertas & users pakai ON DELETE RESTRICT
-- supaya data master tidak bisa dihapus paksa kalau masih dipakai di
-- histori transaksi -> menjaga integritas laporan.
-- ---------------------------------------------------------------------
CREATE TABLE transaksi (
    id_transaksi      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_kertas         INT UNSIGNED NOT NULL,
    id_user           INT UNSIGNED NOT NULL,
    nama_pelanggan    VARCHAR(100) NOT NULL,
    jumlah_lembar     INT UNSIGNED NOT NULL,
    total_harga       INT UNSIGNED NOT NULL,
    status_pembayaran ENUM('lunas', 'belum') NOT NULL DEFAULT 'lunas',
    created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_transaksi_kertas FOREIGN KEY (id_kertas)
        REFERENCES kertas(id_kertas) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_transaksi_user FOREIGN KEY (id_user)
        REFERENCES users(id_user) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel: stok_log
-- Log dummy keluar-masuk stok kertas.
-- ---------------------------------------------------------------------
CREATE TABLE stok_log (
    id_log            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_kertas         INT UNSIGNED NOT NULL,
    jumlah_perubahan  INT          NOT NULL,
    tipe              ENUM('masuk', 'keluar') NOT NULL,
    keterangan        VARCHAR(150) NULL,
    created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_stoklog_kertas FOREIGN KEY (id_kertas)
        REFERENCES kertas(id_kertas) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- DUMMY DATA (minimal 10 record per tabel, untuk keperluan testing)
-- Semua password dummy = "123" (sudah di-hash pakai bcrypt,
-- kompatibel dengan password_verify() di PHP). Password sependek ini
-- HANYA untuk testing lokal -- register.php tetap mewajibkan minimal
-- 8 karakter untuk akun baru yang didaftarkan lewat form.
-- =====================================================================

INSERT INTO users (username, password, role, nama_lengkap, is_active) VALUES
('admin1',  '$2b$12$mOYP9jhOMNIQQZ//CDONI.2/vSSNt0O0lDcj9sFjWqd71oSse/9Oq', 'admin', 'Budi Santoso', 1),
('admin2',  '$2b$12$mOYP9jhOMNIQQZ//CDONI.2/vSSNt0O0lDcj9sFjWqd71oSse/9Oq', 'admin', 'Siti Aminah', 1),
('kasir1',  '$2b$12$mOYP9jhOMNIQQZ//CDONI.2/vSSNt0O0lDcj9sFjWqd71oSse/9Oq', 'kasir', 'Ahmad Fauzi', 1),
('kasir2',  '$2b$12$mOYP9jhOMNIQQZ//CDONI.2/vSSNt0O0lDcj9sFjWqd71oSse/9Oq', 'kasir', 'Dewi Lestari', 1),
('kasir3',  '$2b$12$mOYP9jhOMNIQQZ//CDONI.2/vSSNt0O0lDcj9sFjWqd71oSse/9Oq', 'kasir', 'Rizky Ramadhan', 1),
('kasir4',  '$2b$12$mOYP9jhOMNIQQZ//CDONI.2/vSSNt0O0lDcj9sFjWqd71oSse/9Oq', 'kasir', 'Putri Wulandari', 1),
('kasir5',  '$2b$12$mOYP9jhOMNIQQZ//CDONI.2/vSSNt0O0lDcj9sFjWqd71oSse/9Oq', 'kasir', 'Agus Setiawan', 1),
('kasir6',  '$2b$12$mOYP9jhOMNIQQZ//CDONI.2/vSSNt0O0lDcj9sFjWqd71oSse/9Oq', 'kasir', 'Nadia Anggraini', 1),
('kasir7',  '$2b$12$mOYP9jhOMNIQQZ//CDONI.2/vSSNt0O0lDcj9sFjWqd71oSse/9Oq', 'kasir', 'Fajar Nugroho', 0),
('kasir8',  '$2b$12$mOYP9jhOMNIQQZ//CDONI.2/vSSNt0O0lDcj9sFjWqd71oSse/9Oq', 'kasir', 'Melati Sari', 1);

-- Catatan: hanya HVS A4 yang benar-benar dipakai di produksi (Rp2000/lembar).
-- Jenis lain hanya data dummy tambahan untuk uji fitur search/sort/pagination.
INSERT INTO kertas (nama_jenis, harga_per_lembar, stok, is_deleted) VALUES
('HVS A4 70gsm',   2000, 500, 0),
('HVS A4 80gsm',   2500, 300, 0),
('HVS F4 70gsm',   2200, 200, 0),
('HVS F4 80gsm',   2700, 150, 0),
('Foto Glossy A4', 5000, 100, 0),
('Foto Glossy A6', 2000, 120, 0),
('Kertas Karton',  3000,  80, 0),
('Kertas Sticker', 3500,  60, 0),
('HVS A3 70gsm',   4000,  50, 0),
('Kertas Undangan',6000,  40, 1);

INSERT INTO transaksi (id_kertas, id_user, nama_pelanggan, jumlah_lembar, total_harga, status_pembayaran) VALUES
(1, 3, 'Rina',           10,  20000, 'lunas'),
(1, 3, 'Doni',            5,  10000, 'lunas'),
(1, 4, 'Yusuf',          20,  40000, 'lunas'),
(2, 4, 'Fitri',           8,  20000, 'lunas'),
(1, 5, 'Bayu',           15,  30000, 'belum'),
(3, 5, 'Sari',           12,  26400, 'lunas'),
(1, 6, 'Hendra',         25,  50000, 'lunas'),
(5, 6, 'Lia',             4,  20000, 'lunas'),
(1, 7, 'Wawan',          30,  60000, 'belum'),
(6, 7, 'Citra',           6,  12000, 'lunas'),
(1, 8, 'Andi',            9,  18000, 'lunas');

INSERT INTO stok_log (id_kertas, jumlah_perubahan, tipe, keterangan) VALUES
(1, 500, 'masuk', 'Stok awal HVS A4 70gsm'),
(1, -10, 'keluar', 'Terpakai transaksi cetak'),
(2, 300, 'masuk', 'Stok awal HVS A4 80gsm'),
(3, 200, 'masuk', 'Stok awal HVS F4 70gsm'),
(4, 150, 'masuk', 'Stok awal HVS F4 80gsm'),
(5, 100, 'masuk', 'Stok awal Foto Glossy A4'),
(5,  -4, 'keluar', 'Terpakai transaksi cetak'),
(6, 120, 'masuk', 'Stok awal Foto Glossy A6'),
(7,  80, 'masuk', 'Stok awal Kertas Karton'),
(8,  60, 'masuk', 'Stok awal Kertas Sticker'),
(9,  50, 'masuk', 'Stok awal HVS A3 70gsm');
