<?php
/**
 * Deteksi otomatis base URL project, supaya semua link/redirect tetap benar
 * baik project ditaruh di document root langsung, maupun di dalam subfolder
 * seperti htdocs/project/ atau htdocs/pkl/.
 */
if (!defined('BASE_URL')) {
    $projectRoot  = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $documentRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));

    $baseUrl = $documentRoot !== '' ? str_replace($documentRoot, '', $projectRoot) : '';
    define('BASE_URL', $baseUrl === '/' ? '' : $baseUrl);
}
