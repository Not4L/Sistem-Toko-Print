<?php
require_once __DIR__ . '/../config/app.php';

/**
 * Helper function yang dipakai berulang di banyak modul (prinsip DRY).
 */

/**
 * Escape output supaya aman ditampilkan di HTML -> proteksi XSS.
 * SELALU pakai ini setiap kali menampilkan data dari database/user ke HTML.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Set flash message yang akan tampil sekali saja di halaman berikutnya.
 * $type: 'success' | 'error' | 'warning'
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Ambil & hapus flash message (supaya tidak muncul lagi di reload berikutnya).
 */
function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render flash message sebagai HTML (dipanggil di reusable component).
 */
function renderFlash(): void
{
    $flash = getFlash();
    if ($flash) {
        echo '<div class="alert alert-' . e($flash['type']) . '">' . e($flash['message']) . '</div>';
    }
}

/**
 * Helper pagination: hitung offset & total halaman.
 */
function getPagination(int $totalRows, int $perPage = 10, int $currentPage = 1): array
{
    $totalPages  = max(1, (int) ceil($totalRows / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset      = ($currentPage - 1) * $perPage;

    return [
        'total_rows'   => $totalRows,
        'per_page'     => $perPage,
        'total_pages'  => $totalPages,
        'current_page' => $currentPage,
        'offset'       => $offset,
    ];
}

/**
 * Redirect helper.
 */
function redirectTo(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

/**
 * Kembalikan path lengkap termasuk BASE_URL, dipakai untuk href/action di HTML.
 * Contoh: url('/css/style.css') -> '/project/css/style.css'
 */
function url(string $path): string
{
    return BASE_URL . $path;
}

/**
 * Redirect ke dashboard sesuai role user yang sedang login.
 */
function redirectToDashboard(): void
{
    $role = $_SESSION['user']['role'] ?? null;

    if ($role === 'admin') {
        redirectTo('/dashboard/admin.php');
    } elseif ($role === 'kasir') {
        redirectTo('/dashboard/kasir.php');
    } else {
        redirectTo('/auth/login.php');
    }
}

/**
 * Validasi generik untuk form kertas (dipakai bareng di create & edit -> DRY).
 * Mengecek: required, tipe data, panjang karakter, unique constraint.
 *
 * @return array daftar pesan error, kosong kalau valid
 */
function validateKertas(array $data, PDO $pdo, ?int $excludeId = null): array
{
    $errors = [];

    $nama  = trim($data['nama_jenis'] ?? '');
    $harga = $data['harga_per_lembar'] ?? '';
    $stok  = $data['stok'] ?? '';

    // required
    if ($nama === '') {
        $errors['nama_jenis'] = 'Nama jenis kertas wajib diisi.';
    } elseif (mb_strlen($nama) < 3 || mb_strlen($nama) > 50) {
        // panjang karakter
        $errors['nama_jenis'] = 'Nama jenis kertas harus 3-50 karakter.';
    } else {
        // unique constraint (kecuali dirinya sendiri saat edit)
        $sql = 'SELECT id_kertas FROM kertas WHERE nama_jenis = ? AND is_deleted = 0';
        $params = [$nama];
        if ($excludeId !== null) {
            $sql .= ' AND id_kertas != ?';
            $params[] = $excludeId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetch()) {
            $errors['nama_jenis'] = 'Nama jenis kertas sudah terdaftar.';
        }
    }

    // tipe data & required
    if ($harga === '' || !is_numeric($harga)) {
        $errors['harga_per_lembar'] = 'Harga per lembar wajib berupa angka.';
    } elseif ((int) $harga < 0) {
        $errors['harga_per_lembar'] = 'Harga tidak boleh negatif.';
    }

    if ($stok === '' || !is_numeric($stok)) {
        $errors['stok'] = 'Stok wajib berupa angka.';
    } elseif ((int) $stok < 0) {
        $errors['stok'] = 'Stok tidak boleh negatif.';
    }

    return $errors;
}
