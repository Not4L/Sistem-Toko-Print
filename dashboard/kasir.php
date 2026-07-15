<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
requireLogin();
requireRole('kasir', 'admin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Kasir</title>
    <link rel="stylesheet" href="<?= url('/css/style.css') ?>">
</head>
<body>
<div class="container">
    <?php renderFlash(); ?>
    <h1>Dashboard Kasir</h1>
    <p>Halo, <?= e($_SESSION['user']['nama_lengkap']) ?> (kasir)</p>
    <nav>
        <a href="<?= url('/kertas/index.php') ?>">Lihat Data Kertas</a> |
        <a href="<?= url('/auth/logout.php') ?>">Logout</a>
    </nav>
    <p><em>Modul transaksi cetak menyusul di fase berikutnya.</em></p>
</div>
</body>
</html>
