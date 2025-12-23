<?php
require __DIR__ . '/config.php';

$p = SIGNATURE_FILE;
if (!is_file($p)) {
  http_response_code(404);
  echo "Signature belum ada. Silakan atur di settings_signature.php";
  exit;
}

header('Content-Type: image/png');
header('Cache-Control: no-store');
readfile($p);
