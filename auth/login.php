<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/form_input.php';

$pdo   = getConnection();
$error = null;
$old   = ['username' => ''];

// Kalau sudah login, jangan biarkan buka halaman login lagi
if (!empty($_SESSION['user'])) {
    redirectToDashboard();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi form tidak valid, silakan coba lagi.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $old['username'] = $username;

        // Proteksi brute force sederhana: batasi percobaan login per session
        $_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? 0;

        if ($_SESSION['login_attempts'] >= 5) {
            $error = 'Terlalu banyak percobaan login. Coba lagi nanti.';
        } elseif ($username === '' || $password === '') {
            $error = 'Username dan password wajib diisi.';
        } else {
            // Prepared statement -> aman dari SQL Injection
            $stmt = $pdo->prepare(
                'SELECT id_user, username, password, role, nama_lengkap, is_active
                 FROM users WHERE username = ?'
            );
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                $_SESSION['login_attempts']++;
                // Pesan generik sengaja disamakan (username salah / password salah)
                // supaya penyerang tidak bisa menebak username mana yang valid.
                $error = 'Username atau password salah.';
            } elseif (!$user['is_active']) {
                $error = 'Akun ini sudah dinonaktifkan. Hubungi admin.';
            } else {
                // Login berhasil
                unset($_SESSION['login_attempts']);
                session_regenerate_id(true); // cegah session fixation

                $_SESSION['user'] = [
                    'id_user'      => $user['id_user'],
                    'username'     => $user['username'],
                    'role'         => $user['role'],
                    'nama_lengkap' => $user['nama_lengkap'],
                ];

                setFlash('success', 'Selamat datang, ' . $user['nama_lengkap'] . '!');
                redirectToDashboard();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Sistem Print</title>
    <link rel="stylesheet" href="<?= url('/css/style.css') ?>">
</head>
<body>
<div class="auth-box">
    <h1>Login</h1>

    <?php renderFlash(); ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= url('/auth/login.php') ?>" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

        <?php renderInput([
            'name'  => 'username',
            'label' => 'Username',
            'value' => $old['username'],
            'attrs' => 'required autofocus',
        ]); ?>

        <?php renderInput([
            'name'  => 'password',
            'label' => 'Password',
            'type'  => 'password',
            'attrs' => 'required',
        ]); ?>

        <button type="submit">Login</button>
    </form>
    <p><a href="<?= url('/auth/register.php') ?>">Belum punya akun? Daftar</a></p>
</div>
</body>
</html>
