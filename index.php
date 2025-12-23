<?php
require __DIR__ . '/config.php';

function ensure_dir(string $dir): void {
  if (!is_dir($dir)) @mkdir($dir, 0775, true);
}

function b64u_enc(string $s): string {
  return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
}

function list_pdfs(string $baseDir): array {
  $out = [];
  if (!is_dir($baseDir)) return $out;

  $baseDir = rtrim(str_replace('\\','/',$baseDir),'/');
  $it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS)
  );
  foreach ($it as $file) {
    /** @var SplFileInfo $file */
    if (!$file->isFile()) continue;
    $ext = strtolower($file->getExtension());
    if ($ext !== 'pdf') continue;

    $abs = str_replace('\\','/',$file->getPathname());
    $rel = ltrim(substr($abs, strlen($baseDir)), '/');
    $out[] = [
      'rel' => $rel,
      'abs' => $abs,
      'mtime' => $file->getMTime(),
      'size' => $file->getSize(),
    ];
  }
  usort($out, fn($a,$b) => $b['mtime'] <=> $a['mtime']);
  return $out;
}

ensure_dir(INCOMING_DIR);
ensure_dir(SIGNED_DIR);
if (MOVE_ORIGINAL_AFTER_SIGN) ensure_dir(PROCESSED_DIR);

$files = list_pdfs(INCOMING_DIR);

function fmt_bytes(int $b): string {
  $u = ['B','KB','MB','GB'];
  $i = 0;
  $v = $b;
  while ($v >= 1024 && $i < count($u)-1) { $v/=1024; $i++; }
  return sprintf('%.1f %s', $v, $u[$i]);
}

?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>DocSign Local (OneDrive)</title>
  <link rel="stylesheet" href="public/app.css" />
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div class="brand">
        <div class="mark">DS</div>
        <div class="title">
          <b>DocSign Local</b>
          <span>Input: OneDrive incoming → Output: OneDrive signed</span>
        </div>
      </div>
      <div style="display:flex; gap:10px; align-items:center;">
        <a class="btn secondary" href="settings_signature.php">Atur TTD</a>
        <a class="btn secondary" href="" onclick="location.reload();return false;">Refresh</a>
      </div>
    </div>

    <div class="card">
      <div class="badge">INCOMING: <span class="code"><?php echo htmlspecialchars(INCOMING_DIR); ?></span></div>
      <div style="height:10px"></div>
      <div class="badge">SIGNED: <span class="code"><?php echo htmlspecialchars(SIGNED_DIR); ?></span></div>
      <?php if (MOVE_ORIGINAL_AFTER_SIGN): ?>
        <div style="height:10px"></div>
        <div class="badge">PROCESSED: <span class="code"><?php echo htmlspecialchars(PROCESSED_DIR); ?></span></div>
      <?php endif; ?>
      <hr class="hr" />

      <?php if (!$files): ?>
        <div class="alert warn">
          <b>Belum ada PDF di incoming.</b>
          <div class="muted">Letakkan file PDF ke folder incoming (OneDrive sinkron), lalu refresh.</div>
        </div>
      <?php else: ?>
        <div class="thead">
          <div>Modified</div><div>Nama File</div><div>Size</div><div>Aksi</div>
        </div>

        <div class="table">
          <?php foreach ($files as $f): 
            $key = b64u_enc($f['rel']);
            $signedAbs = str_replace('\\','/', SIGNED_DIR . '/' . $f['rel']);
            $signedExists = is_file($signedAbs);
          ?>
            <div class="trow card" style="padding:12px 12px;">
              <div class="muted"><?php echo date('Y-m-d H:i', $f['mtime']); ?></div>
              <div style="min-width:0">
                <div style="font-weight:700; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                  <?php echo htmlspecialchars($f['rel']); ?>
                </div>
                <?php if ($signedExists): ?>
                  <div class="muted small">✅ Sudah ada hasil di signed</div>
                <?php endif; ?>
              </div>
              <div class="muted"><?php echo fmt_bytes($f['size']); ?></div>
              <div style="display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
                <a class="btn secondary" href="serve.php?k=in&f=<?php echo urlencode($key); ?>" target="_blank">Lihat PDF</a>
                <a class="btn" href="sign.php?f=<?php echo urlencode($key); ?>">Tanda Tangani</a>
                <?php if ($signedExists): ?>
                  <a class="btn secondary" href="serve.php?k=out&f=<?php echo urlencode($key); ?>" target="_blank">Buka Signed</a>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <hr class="hr" />
      <div class="muted small">
        Tips: agar mahasiswa tidak melihat file teman, buat subfolder per NPM di incoming & signed (mis. incoming/2208.../).
      </div>
    </div>
  </div>
</body>
</html>
