<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/views.php';

date_default_timezone_set('America/Guatemala');
ini_set('display_errors', '0');
ini_set('session.use_strict_mode', '1');
ini_set('session.upload_progress.enabled', '0');

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: DENY');
header('Content-Security-Policy: default-src \'self\'; img-src \'self\' data:; style-src \'self\'; script-src \'self\'; form-action \'self\'; frame-ancestors \'none\'; base-uri \'none\'');
header('Cache-Control: no-store');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('votacion_caserio');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
