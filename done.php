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

$key = $_GET['f'] ?? '';
$rel = safe_rel_from_key($key);
$dst = $rel ? (SIGNED_DIR . '/' . $rel) : '';

?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Selesai</title>
  <link rel="stylesheet" href="public/app.css" />
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div class="brand">
        <div class="mark">DS</div>
        <div class="title">
          <b>Selesai</b>
          <span>PDF tersimpan ke OneDrive signed</span>
        </div>
      </div>
      <a class="btn secondary" href="index.php">Kembali</a>
    </div>

    <div class="card">
      <?php if ($rel && is_file($dst)): ?>
        <div class="alert ok">
          <b>Berhasil disimpan.</b>
          <div class="muted small"><?php echo htmlspecialchars($dst); ?></div>
        </div>
        <div style="height:14px"></div>
        <a class="btn" href="serve.php?k=out&f=<?php echo urlencode($key); ?>" target="_blank">Buka PDF Signed</a>
      <?php else: ?>
        <div class="alert warn">
          <b>File signed belum ditemukan.</b>
          <div class="muted small">Silakan cek folder signed di OneDrive.</div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
