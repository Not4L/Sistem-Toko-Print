<?php
/**
 * Koneksi database menggunakan PDO.
 * PDO dengan prepared statement dipakai di seluruh aplikasi supaya
 * parameter query selalu di-bind, bukan digabung manual ke string SQL
 * -> ini proteksi utama terhadap SQL Injection.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'sistem_print');
define('DB_USER', 'root');
define('DB_PASS', '');

function getConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // prepared statement asli, bukan emulasi
            ]);
        } catch (PDOException $e) {
            // Jangan tampilkan detail error DB ke user (celah informasi sensitif)
            error_log('DB Connection Error: ' . $e->getMessage());
            die('Koneksi database gagal. Silakan coba lagi nanti.');
        }
    }

    return $pdo;
}
