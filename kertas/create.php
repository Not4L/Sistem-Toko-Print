<?php
require_once __DIR__ . '/../includes/auth_middleware.php';
requireLogin();
requireRole('admin'); // hanya admin yang boleh kelola master data

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/form_input.php';

$pdo    = getConnection();
$errors = [];
$old    = ['nama_jenis' => '', 'harga_per_lembar' => '', 'stok' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'Sesi form tidak valid, silakan coba lagi.';
    } else {
        $old = [
            'nama_jenis'       => trim($_POST['nama_jenis'] ?? ''),
            'harga_per_lembar' => $_POST['harga_per_lembar'] ?? '',
            'stok'             => $_POST['stok'] ?? '',
        ];

        // Validasi server-side (reusable function -> dipakai juga di edit.php)
        $errors = validateKertas($old, $pdo);

        if (empty($errors)) {
            // Operasi ini menyentuh 2 tabel (kertas + stok_log) -> wajib pakai transaction
            // supaya kalau salah satu insert gagal, semuanya di-rollback (data tidak setengah tersimpan).
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    'INSERT INTO kertas (nama_jenis, harga_per_lembar, stok) VALUES (?, ?, ?)'
                );
                $stmt->execute([$old['nama_jenis'], (int) $old['harga_per_lembar'], (int) $old['stok']]);
                $newId = (int) $pdo->lastInsertId();

                if ((int) $old['stok'] > 0) {
                    $stmt = $pdo->prepare(
                        'INSERT INTO stok_log (id_kertas, jumlah_perubahan, tipe, keterangan) VALUES (?, ?, ?, ?)'
                    );
                    $stmt->execute([$newId, (int) $old['stok'], 'masuk', 'Stok awal saat kertas dibuat']);
                }

                $pdo->commit();

                setFlash('success', 'Data kertas berhasil ditambahkan.');
                redirectTo('/kertas/index.php');
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('Create kertas failed: ' . $e->getMessage());
                $errors['general'] = 'Gagal menyimpan data. Silakan coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Kertas</title>
    <link rel="stylesheet" href="<?= url('/css/style.css') ?>">
</head>
<body>
<div class="container">
    <h1>Tambah Data Kertas</h1>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error"><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= url('/kertas/create.php') ?>" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

        <?php renderInput([
            'name'  => 'nama_jenis',
            'label' => 'Nama Jenis Kertas',
            'value' => $old['nama_jenis'],
            'error' => $errors['nama_jenis'] ?? null,
            'attrs' => 'required minlength="3" maxlength="50"',
        ]); ?>

        <?php renderInput([
            'name'  => 'harga_per_lembar',
            'label' => 'Harga per Lembar (Rp)',
            'type'  => 'number',
            'value' => (string) $old['harga_per_lembar'],
            'error' => $errors['harga_per_lembar'] ?? null,
            'attrs' => 'required min="0" step="1"',
        ]); ?>

        <?php renderInput([
            'name'  => 'stok',
            'label' => 'Stok Awal',
            'type'  => 'number',
            'value' => (string) $old['stok'],
            'error' => $errors['stok'] ?? null,
            'attrs' => 'required min="0" step="1"',
        ]); ?>

        <button type="submit">Simpan</button>
        <a href="<?= url('/kertas/index.php') ?>">Batal</a>
    </form>
</div>
</body>
</html>
