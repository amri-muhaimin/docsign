<?php
declare(strict_types=1);

/**
 * DocSign Local + OneDrive Sync
 * - Tidak ada login
 * - Tidak ada upload dokumen
 */

function norm_path(string $p): string {
  $p = str_replace('\\', '/', $p);
  return rtrim($p, '/');
}

function detect_onedrive_root(): string {
  $candidates = [
    getenv('OneDrive') ?: '',
    getenv('OneDriveCommercial') ?: '',
    getenv('OneDriveConsumer') ?: '',
  ];
  foreach ($candidates as $c) {
    $c = trim($c);
    if ($c !== '' && is_dir($c)) return norm_path($c);
  }
  return '';
}

define('ONEDRIVE_ROOT', detect_onedrive_root());

// Jika auto-detect gagal, isi manual. Contoh:
// define('ONEDRIVE_ROOT', 'C:/Users/Abdul/OneDrive - UPN Veteran Jawa Timur');

define('DOCSIGN_OD', ONEDRIVE_ROOT !== '' ? (ONEDRIVE_ROOT . '/DocSign') : (__DIR__ . '/_onedrive_mock'));

define('INCOMING_DIR',  DOCSIGN_OD . '/incoming');
define('SIGNED_DIR',    DOCSIGN_OD . '/signed');
define('PROCESSED_DIR', DOCSIGN_OD . '/processed'); // opsional

define('MOVE_ORIGINAL_AFTER_SIGN', true); // true: pindah ke processed setelah save
define('SIGNATURE_FILE', __DIR__ . '/signature/signature.png');

define('MAX_FILE_MB', 40); // batas aman
