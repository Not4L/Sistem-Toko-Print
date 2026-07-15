<?php
/**
 * Konfigurasi session global.
 * Wajib di-include SEBELUM session_start() dipanggil di file manapun.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,        // habis saat browser ditutup
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']), // true kalau pakai HTTPS
        'httponly' => true,     // cookie tidak bisa diakses lewat JS -> proteksi XSS mencuri session
        'samesite' => 'Lax',    // proteksi tambahan dari CSRF lintas situs
    ]);
    session_start();
}

/**
 * Generate & simpan CSRF token di session kalau belum ada.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validasi CSRF token yang dikirim dari form.
 */
function verifyCsrfToken(?string $token): bool
{
    return !empty($token) && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}
