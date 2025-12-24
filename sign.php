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
$abs = $rel ? (INCOMING_DIR . '/' . $rel) : '';

if (!$rel || !is_file($abs)) {
  http_response_code(404);
  echo "File tidak ditemukan di incoming.";
  exit;
}

?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Tanda Tangan - <?php echo htmlspecialchars(basename($rel)); ?></title>
  <link rel="stylesheet" href="public/app.css" />
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div class="brand">
        <div class="mark">DS</div>
        <div class="title">
          <b>Tanda Tangan Dokumen</b>
          <span class="muted small"><?php echo htmlspecialchars($rel); ?></span>
        </div>
      </div>
      <div style="display:flex; gap:10px;">
        <a class="btn secondary" href="index.php">Kembali</a>
        <a class="btn secondary" href="settings_signature.php">Atur TTD</a>
      </div>
    </div>

    <div class="card">
      <div class="muted small">Langkah: pilih halaman → drag tanda tangan → (opsional) duplikat → atur skala → simpan.</div>
      <hr class="hr" />

      <div class="card" style="padding:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:center; justify-content:space-between;">
        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
          <button id="prevBtn" class="secondary">◀ Prev</button>
          <div class="badge">Halaman: <span id="pageNum">1</span> / <span id="pageCount">?</span></div>
          <button id="nextBtn" class="secondary">Next ▶</button>

          <button id="addSigBtn" class="secondary">+ Tambah</button>
          <button id="dupSigBtn" class="secondary">⎘ Duplikat</button>
          <button id="delSigBtn" class="secondary">Hapus</button>

          <div class="badge">Skala</div>
          <input id="sigScale" type="range" min="20" max="200" value="100" />
        </div>

        <button id="saveBtn">Simpan (ke OneDrive Signed)</button>
      </div>

      <div id="msg" class="muted small" style="margin-top:10px;"></div>

      <div id="viewer" class="card" style="margin-top:14px; padding:14px; position:relative; overflow:auto;">
        <canvas id="pdfCanvas" style="display:block; margin:0 auto; border-radius:14px;"></canvas>
      </div>
    </div>
  </div>

  <!-- PDF.js + PDF-Lib via CDN (OneDrive butuh internet anyway) -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>

  <script>
    window.__PDF_URL__ = "serve.php?k=in&f=<?php echo urlencode($key); ?>";
    window.__FILE_KEY__ = "<?php echo htmlspecialchars($key, ENT_QUOTES); ?>";
  </script>
  <script src="public/sign.js"></script>
</body>
</html>
