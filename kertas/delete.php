<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
requireLogin();
requireRole('admin');

require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('/kertas/index.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('error', 'Sesi form tidak valid, silakan coba lagi.');
    redirectTo('/kertas/index.php');
}

$id = (int) ($_POST['id_kertas'] ?? 0);

/**
 * Pakai SOFT DELETE (is_deleted = 1), bukan hard delete (DELETE FROM).
 * Pertimbangan: kertas ini kemungkinan besar sudah dipakai di tabel
 * transaksi (riwayat penjualan). Kalau di-hard-delete dan FK constraint-nya
 * RESTRICT, query akan gagal total; kalau CASCADE, histori transaksi ikut
 * hilang -> laporan jadi rusak. Soft delete menjaga data historis tetap utuh
 * sambil menyembunyikan item dari daftar aktif.
 */
$stmt = $pdo->prepare('UPDATE kertas SET is_deleted = 1 WHERE id_kertas = ?');
$stmt->execute([$id]);

if ($stmt->rowCount() > 0) {
    setFlash('success', 'Data kertas berhasil dihapus.');
} else {
    setFlash('error', 'Data kertas tidak ditemukan.');
}

redirectTo('/kertas/index.php');
