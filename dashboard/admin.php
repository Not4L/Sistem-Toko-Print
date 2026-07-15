<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
requireLogin();
requireRole('admin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="<?= url('/css/style.css') ?>">
</head>
<body>
<div class="container">
    <?php renderFlash(); ?>
    <h1>Dashboard Admin</h1>
    <p>Halo, <?= e($_SESSION['user']['nama_lengkap']) ?> (admin)</p>
    <nav>
        <a href="<?= url('/kertas/index.php') ?>">Kelola Data Master Kertas</a> |
        <a href="<?= url('/auth/logout.php') ?>">Logout</a>
    </nav>
</div>
</body>
</html>
