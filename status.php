<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/storage.php';

$docId = $_GET['id'] ?? '';
$docId = strtolower(trim($docId));
if ($docId !== '' && !preg_match('/^[a-f0-9]{16}$/', $docId)) {
    $docId = '';
}

$meta = $docId ? load_meta($docId) : null;
$hasOriginal = $docId && is_file(original_path($docId));
$hasSigned = $docId && is_file(signed_path($docId));

?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Status Dokumen</title>
  <link rel="stylesheet" href="public/app.css">
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div class="brand">
        <div class="mark">DS</div>
        <div class="title">
          <b>Status Dokumen</b>
          <span>cek file asli & hasil tanda tangan</span>
        </div>
      </div>
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn secondary" href="index.php">Beranda</a>
        <a class="btn secondary" href="admin_login.php">Admin</a>
      </div>
    </div>

    <div class="card">
      <form action="status.php" method="get">
        <label for="id">Doc ID</label>
        <input type="text" id="id" name="id" value="<?php echo htmlspecialchars($docId); ?>" placeholder="contoh: a1b2c3d4e5f6..." required>
        <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
          <button type="submit" class="btn secondary">Cek</button>
        </div>
      </form>

      <div class="hr"></div>

      <?php if (!$docId): ?>
        <div class="alert warn"><b>Masukkan Doc ID</b>Gunakan Doc ID yang didapat saat upload.</div>
      <?php elseif (!$meta || !$hasOriginal): ?>
        <div class="alert err"><b>Tidak ditemukan</b>Dokumen dengan Doc ID tersebut tidak ada.</div>
      <?php else: ?>
        <p><span class="badge">Doc ID: <span class="code"><?php echo htmlspecialchars($docId); ?></span></span></p>
        <p class="muted small">Nama file: <?php echo htmlspecialchars($meta['original_name'] ?? '-'); ?></p>

        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px;">
          <a class="btn secondary" href="download.php?id=<?php echo urlencode($docId); ?>&type=original">Download PDF asli</a>
          <?php if ($hasSigned): ?>
            <a class="btn" href="download.php?id=<?php echo urlencode($docId); ?>&type=signed">Download PDF bertanda tangan</a>
          <?php else: ?>
            <span class="badge">Belum ditandatangani</span>
          <?php endif; ?>
        </div>

        <?php if (!empty($meta['signed_at'])): ?>
          <p class="muted small" style="margin-top:12px;">Ditandatangani pada: <span class="code"><?php echo htmlspecialchars($meta['signed_at']); ?></span></p>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
