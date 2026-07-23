    <?php
require_once __DIR__ . '/../includes/auth_middleware.php';
requireLogin();
requireRole('admin');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/form_input.php';

$pdo = getConnection();
$id  = (int) ($_GET['id'] ?? $_POST['id_kertas'] ?? 0); 




$stmt = $pdo->prepare('SELECT * FROM kertas WHERE id_kertas = ? AND is_deleted = 0');
$stmt->execute([$id]);
$kertas = $stmt->fetch();

if (!$kertas) {
    setFlash('error', 'Data kertas tidak ditemukan.');
    redirectTo('/kertas/index.php');
}

$errors = [];
$old = [
    'nama_jenis'       => $kertas['nama_jenis'],
    'harga_per_lembar' => $kertas['harga_per_lembar'],
    'stok'             => $kertas['stok'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'Sesi form tidak valid, silakan coba lagi.';
    } else {
        $old = [
            'nama_jenis'       => trim($_POST['nama_jenis'] ?? ''),
            'harga_per_lembar' => $_POST['harga_per_lembar'] ?? '',
            'stok'             => $_POST['stok'] ?? '',
        ];

        $stmtCheck = $pdo->prepare('SELECT updated_at FROM kertas WHERE id_kertas = ?');
$stmtCheck->execute([$id]);
$updatedAtSekarang = $stmtCheck->fetchColumn();

if ($updatedAtSekarang !== ($_POST['updated_at_check'] ?? '')) {
    $errors['general'] = 'Data ini sudah diubah orang lain sejak kamu buka form. Silakan refresh dan coba lagi.';
}
        $errors = validateKertas($old, $pdo, $id);

        if (empty($errors)) {
            $stmt = $pdo->prepare(
                'UPDATE kertas SET nama_jenis = ?, harga_per_lembar = ?, stok = ? WHERE id_kertas = ?'
            );
            $stmt->execute([$old['nama_jenis'], (int) $old['harga_per_lembar'], (int) $old['stok'], $id]);

            setFlash('success', 'Data kertas berhasil diperbarui.');
            redirectTo('/kertas/index.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Kertas</title>
    <link rel="stylesheet" href="<?= url('/css/style.css') ?>">
</head>
<body>
<div class="container">
    <h1>Edit Data Kertas</h1>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error"><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= url('/kertas/edit.php') ?>?id=<?= (int) $id ?>" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="id_kertas" value="<?= (int) $id ?>">
        <input type="hidden" name="updated_at_check" value="<?= e($kertas['updated_at']) ?>">

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
            'label' => 'Stok',
            'type'  => 'number',
            'value' => (string) $old['stok'],
            'error' => $errors['stok'] ?? null,
            'attrs' => 'required min="0" step="1"',
        ]); ?>

        <button type="submit">Simpan Perubahan</button>
        <a href="<?= url('/kertas/index.php') ?>">Batal</a>
    </form>
</div>
</body>
</html>
