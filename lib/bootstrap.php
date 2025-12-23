<?php
require_once __DIR__ . '/../config.php';

date_default_timezone_set('Asia/Jakarta');

// Safer session defaults
if (session_status() === PHP_SESSION_NONE) {
    // local http: secure=false; adjust if using https
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => false,
    ]);
    session_start();
}

// Ensure storage folders exist
$dirs = [
    STORAGE_DIR,
    STORAGE_DIR . '/original',
    STORAGE_DIR . '/signed',
    STORAGE_DIR . '/meta',
    STORAGE_DIR . '/signatures',
];
foreach ($dirs as $d) {
    if (!is_dir($d)) {
        @mkdir($d, 0755, true);
    }
}
