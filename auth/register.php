<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/form_input.php';

$pdo    = getConnection();
$errors = [];
$old    = ['username' => '', 'nama_lengkap' => '', 'role' => 'kasir'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Proteksi CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'Sesi form tidak valid, silakan coba lagi.';
    } else {
        $username     = trim($_POST['username'] ?? '');
        $password     = $_POST['password'] ?? '';
        $passwordConf = $_POST['password_confirm'] ?? '';
        $namaLengkap  = trim($_POST['nama_lengkap'] ?? '');
        $role         = $_POST['role'] ?? 'kasir';

        $old = compact('username', 'namaLengkap', 'role') + $old;
        $old['nama_lengkap'] = $namaLengkap;

        // --- Validasi server-side ---
        if ($username === '') {
            $errors['username'] = 'Username wajib diisi.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $username)) {
            $errors['username'] = 'Username 4-20 karakter, hanya huruf/angka/underscore.';
        } else {
            $stmt = $pdo->prepare('SELECT id_user FROM users WHERE username = ?');
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors['username'] = 'Username sudah digunakan.'; // unique constraint
            }
        }

        if ($namaLengkap === '' || mb_strlen($namaLengkap) > 100) {
            $errors['nama_lengkap'] = 'Nama lengkap wajib diisi (maks 100 karakter).';
        }

        if (!in_array($role, ['admin', 'kasir'], true)) {
            $errors['role'] = 'Role tidak valid.';
        }

        if (strlen($password) < 8) {
            $errors['password'] = 'Password minimal 8 karakter.';
        } elseif ($password !== $passwordConf) {
            $errors['password_confirm'] = 'Konfirmasi password tidak sama.';
        }

        // --- Simpan kalau lolos validasi ---
        if (empty($errors)) {
            $hashed = password_hash($password, PASSWORD_BCRYPT); // hashing wajib, jangan simpan plaintext

            $stmt = $pdo->prepare(
                'INSERT INTO users (username, password, role, nama_lengkap) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$username, $hashed, $role, $namaLengkap]);

            setFlash('success', 'Registrasi berhasil, silakan login.');
            redirectTo('/auth/login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Registrasi - Sistem Print</title>
    <link rel="stylesheet" href="<?= url('/css/style.css') ?>">
</head>
<body>
<div class="auth-box">
    <h1>Registrasi Akun</h1>

    <?php renderFlash(); ?>
    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error"><?= e($errors['general']) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= url('/auth/register.php') ?>" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

        <?php renderInput([
            'name'  => 'username',
            'label' => 'Username',
            'value' => $old['username'],
            'error' => $errors['username'] ?? null,
            'attrs' => 'required minlength="4" maxlength="20" pattern="[a-zA-Z0-9_]+"',
        ]); ?>

        <?php renderInput([
            'name'  => 'nama_lengkap',
            'label' => 'Nama Lengkap',
            'value' => $old['nama_lengkap'],
            'error' => $errors['nama_lengkap'] ?? null,
            'attrs' => 'required maxlength="100"',
        ]); ?>

        <div class="form-group <?= isset($errors['role']) ? 'has-error' : '' ?>">
            <label for="field-role">Role</label>
            <select id="field-role" name="role" required>
                <option value="kasir" <?= ($old['role'] === 'kasir') ? 'selected' : '' ?>>Kasir</option>
                <option value="admin" <?= ($old['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>

        <?php renderInput([
            'name'  => 'password',
            'label' => 'Password',
            'type'  => 'password',
            'error' => $errors['password'] ?? null,
            'attrs' => 'required minlength="8"',
        ]); ?>

        <?php renderInput([
            'name'  => 'password_confirm',
            'label' => 'Konfirmasi Password',
            'type'  => 'password',
            'error' => $errors['password_confirm'] ?? null,
            'attrs' => 'required minlength="8"',
        ]); ?>

        <button type="submit">Daftar</button>
    </form>
    <p><a href="<?= url('/auth/login.php') ?>">Sudah punya akun? Login</a></p>
</div>
</body>
</html>
