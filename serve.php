<?php
require __DIR__ . '/config.php';

function b64u_dec(string $s): string {
  $s = strtr($s, '-_', '+/');
  $pad = strlen($s) % 4;
  if ($pad) $s .= str_repeat('=', 4 - $pad);
  $raw = base64_decode($s, true);
  return $raw === false ? '' : $raw;
}

function safe_resolve(string $base, string $rel): string {
  $base = rtrim(str_replace('\\','/',$base),'/');
  $rel  = ltrim(str_replace('\\','/',$rel),'/');

  if ($rel === '' || str_contains($rel, "\0")) return '';
  // tolak path traversal
  if (preg_match('#(^|/)\\.\\.(?:/|$)#', $rel)) return '';

  $abs = $base . '/' . $rel;
  $realBase = realpath($base);
  $realAbs  = realpath($abs);

  if (!$realBase || !$realAbs) return '';
  $realBase = str_replace('\\','/',$realBase);
  $realAbs  = str_replace('\\','/',$realAbs);

  if (stripos($realAbs, $realBase) !== 0) return '';
  if (strtolower(pathinfo($realAbs, PATHINFO_EXTENSION)) !== 'pdf') return '';
  return $realAbs;
}

$k = $_GET['k'] ?? 'in';
$key = $_GET['f'] ?? '';
$rel = b64u_dec($key);

$base = ($k === 'out') ? SIGNED_DIR : INCOMING_DIR;
$path = safe_resolve($base, $rel);

if ($path === '' || !is_file($path)) {
  http_response_code(404);
  echo "PDF tidak ditemukan.";
  exit;
}

$fn = basename($path);
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . addslashes($fn) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

readfile($path);
