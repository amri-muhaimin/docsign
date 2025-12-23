<?php
require __DIR__ . '/config.php';

function b64u_dec(string $s): string {
  $s = strtr($s, '-_', '+/');
  $pad = strlen($s) % 4;
  if ($pad) $s .= str_repeat('=', 4 - $pad);
  $raw = base64_decode($s, true);
  return $raw === false ? '' : $raw;
}

function safe_rel_from_key(string $key): string {
  $rel = b64u_dec($key);
  $rel = str_replace('\\','/',$rel);
  $rel = ltrim($rel,'/');
  if ($rel === '' || str_contains($rel,"\0")) return '';
  if (preg_match('#(^|/)\\.\\.(?:/|$)#', $rel)) return '';
  return $rel;
}

function ensure_dir(string $dir): void {
  if (!is_dir($dir)) @mkdir($dir, 0775, true);
}

header('Content-Type: application/json');

$key = $_POST['f'] ?? '';
$b64 = $_POST['signed_pdf_b64'] ?? '';

$rel = safe_rel_from_key($key);
if (!$rel) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'File key tidak valid']); exit; }
if ($b64 === '') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'PDF hasil kosong']); exit; }

$bin = base64_decode($b64, true);
if ($bin === false) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Base64 tidak valid']); exit; }

$maxBytes = MAX_FILE_MB * 1024 * 1024;
if (strlen($bin) > $maxBytes) {
  http_response_code(413);
  echo json_encode(['ok'=>false,'error'=>'File terlalu besar (>' . MAX_FILE_MB . 'MB)']);
  exit;
}

$src = str_replace('\\','/', INCOMING_DIR . '/' . $rel);
if (!is_file($src)) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'File asli tidak ditemukan di incoming']); exit; }

$dst = str_replace('\\','/', SIGNED_DIR . '/' . $rel);
ensure_dir(dirname($dst));

$ok = @file_put_contents($dst, $bin);
if ($ok === false) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'Gagal menulis file ke signed']); exit; }

if (MOVE_ORIGINAL_AFTER_SIGN) {
  $to = str_replace('\\','/', PROCESSED_DIR . '/' . $rel);
  ensure_dir(dirname($to));
  @rename($src, $to); // jika gagal pun tidak fatal
}

echo json_encode([
  'ok' => true,
  'status_url' => 'done.php?f=' . urlencode($key),
]);
