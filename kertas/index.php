<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
requireLogin(); // admin & kasir boleh lihat, hanya admin yang boleh edit/hapus (dicek di tombol & di server create/edit/delete)

require_once __DIR__ . '/../config/database.php';
$pdo = getConnection();

// --- SEARCH ---
$search = trim($_GET['search'] ?? '');

// --- SORTING (whitelist kolom supaya tidak bisa disuntik lewat query string) ---
$allowedSorts = ['nama_jenis', 'harga_per_lembar', 'stok', 'created_at'];
$sortBy  = in_array($_GET['sort'] ?? '', $allowedSorts, true) ? $_GET['sort'] : 'nama_jenis';
$sortDir = (($_GET['dir'] ?? 'asc') === 'desc') ? 'DESC' : 'ASC';

// --- Hitung total baris untuk pagination ---
$countSql = 'SELECT COUNT(*) FROM kertas WHERE is_deleted = 0 AND nama_jenis LIKE ?';
$stmt = $pdo->prepare($countSql);
$stmt->execute(['%' . $search . '%']);
$totalRows = (int) $stmt->fetchColumn();

$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$pagination  = getPagination($totalRows, 10, $currentPage);

// --- Ambil data halaman ini ---
$sql = "SELECT id_kertas, nama_jenis, harga_per_lembar, stok, created_at
        FROM kertas
        WHERE is_deleted = 0 AND nama_jenis LIKE ?
        ORDER BY {$sortBy} {$sortDir}
        LIMIT ? OFFSET ?";
// sort kolom aman karena sudah divalidasi lewat whitelist $allowedSorts di atas
// PDO tidak boleh mencampur parameter bernama (:limit) dengan tanda tanya (?)
// dalam satu prepare() -> di sini semua dibuat positional (?) supaya konsisten
$stmt = $pdo->prepare($sql);
$stmt->bindValue(1, '%' . $search . '%', PDO::PARAM_STR);
$stmt->bindValue(2, $pagination['per_page'], PDO::PARAM_INT);
$stmt->bindValue(3, $pagination['offset'], PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

function sortLink(string $column, string $label, string $currentSort, string $currentDir, string $search): string
{
    $nextDir = ($currentSort === $column && $currentDir === 'ASC') ? 'desc' : 'asc';
    $arrow   = $currentSort === $column ? ($currentDir === 'ASC' ? ' ▲' : ' ▼') : '';
    $url = '?sort=' . urlencode($column) . '&dir=' . $nextDir . '&search=' . urlencode($search);
    return '<a href="' . e($url) . '">' . e($label) . $arrow . '</a>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Master Kertas</title>
    <link rel="stylesheet" href="<?= url('/css/style.css') ?>">
</head>
<body>
<div class="container">
    <?php renderFlash(); ?>
    <h1>Data Master Kertas</h1>

    <div class="toolbar">
        <form method="GET" action="<?= url('/kertas/index.php') ?>" class="search-form">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Cari nama jenis kertas...">
            <button type="submit">Cari</button>
        </form>
        <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
            <a class="btn" href="<?= url('/kertas/create.php') ?>">+ Tambah Kertas</a>
        <?php endif; ?>
    </div>

    <table>
        <thead>
        <tr>
            <th><?= sortLink('nama_jenis', 'Nama Jenis', $sortBy, $sortDir, $search) ?></th>
            <th><?= sortLink('harga_per_lembar', 'Harga/Lembar', $sortBy, $sortDir, $search) ?></th>
            <th><?= sortLink('stok', 'Stok', $sortBy, $sortDir, $search) ?></th>
            <th>Aksi</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="4">Tidak ada data ditemukan.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e($row['nama_jenis']) ?></td>
                <td>Rp<?= number_format((int) $row['harga_per_lembar'], 0, ',', '.') ?></td>
                <td><?= (int) $row['stok'] ?></td>
                <td>
                    <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
                        <a href="<?= url('/kertas/edit.php') ?>?id=<?= (int) $row['id_kertas'] ?>">Edit</a>
                        <form method="POST" action="<?= url('/kertas/delete.php') ?>" style="display:inline"
                              onsubmit="return confirm('Yakin hapus &quot;<?= e($row['nama_jenis']) ?>&quot;?');">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="id_kertas" value="<?= (int) $row['id_kertas'] ?>">
                            <button type="submit" class="btn-danger">Hapus</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="pagination">
        <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
            <a href="?page=<?= $p ?>&sort=<?= e($sortBy) ?>&dir=<?= strtolower($sortDir) ?>&search=<?= e($search) ?>"
               class="<?= $p === $pagination['current_page'] ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
    </div>

    <p><a href="<?= url('/dashboard/' . e($_SESSION['user']['role']) . '.php') ?>">&laquo; Kembali ke dashboard</a></p>
</div>
</body>
</html>
