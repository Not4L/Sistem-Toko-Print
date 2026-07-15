<?php
/**
 * Middleware proteksi halaman.
 * Include file ini di baris PALING ATAS setiap halaman yang butuh login.
 *
 * Contoh pakai di dashboard admin:
 *   requireLogin();
 *   requireRole('admin');
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/functions.php';

/**
 * Pastikan user sudah login. Kalau belum, tendang ke halaman login.
 */
function requireLogin(): void
{
    if (empty($_SESSION['user'])) {
        setFlash('error', 'Silakan login terlebih dahulu.');
        redirectTo('/auth/login.php');
    }
}

/**
 * Pastikan role user sesuai yang diizinkan. Panggil setelah requireLogin().
 *
 * @param string ...$allowedRoles contoh: requireRole('admin') atau requireRole('admin', 'kasir')
 */
function requireRole(string ...$allowedRoles): void
{
    $role = $_SESSION['user']['role'] ?? null;

    if (!in_array($role, $allowedRoles, true)) {
        setFlash('error', 'Anda tidak memiliki akses ke halaman ini.');
        redirectToDashboard();
    }
}
