<?php
require __DIR__ . '/config.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_FILES['sig']) || $_FILES['sig']['error'] !== UPLOAD_ERR_OK) {
    $msg = 'Upload gagal. Coba ulang.';
  } else {
    $tmp = $_FILES['sig']['tmp_name'];
    $size = $_FILES['sig']['size'] ?? 0;
    if ($size > 2 * 1024 * 1024) {
      $msg = 'File terlalu besar. Maks 2MB.';
    } else {
      $info = @getimagesize($tmp);
      if (!$info || ($info[2] ?? 0) !== IMAGETYPE_PNG) {
        $msg = 'Harus PNG. Disarankan PNG transparan.';
      } else {
        @mkdir(dirname(SIGNATURE_FILE), 0775, true);
        if (@move_uploaded_file($tmp, SIGNATURE_FILE)) $msg = 'Berhasil menyimpan tanda tangan.';
        else $msg = 'Gagal menyimpan file signature.';
      }
    }
  }
}
?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Atur Tanda Tangan</title>
  <link rel="stylesheet" href="public/app.css" />
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div class="brand">
        <div class="mark">DS</div>
        <div class="title">
          <b>Atur Tanda Tangan</b>
          <span>PNG transparan (disarankan)</span>
        </div>
      </div>
      <a class="btn secondary" href="index.php">Kembali</a>
    </div>

    <div class="card grid">
      <div class="card">
        <h2>Upload TTD (PNG)</h2>
        <?php if ($msg): ?>
          <div class="alert ok" style="margin-bottom:12px;"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
          <label>File PNG Tanda Tangan</label>
          <input type="file" name="sig" accept="image/png" required />
          <div style="height:12px"></div>
          <button type="submit">Simpan</button>
          <p class="muted small">File disimpan di: <span class="code"><?php echo htmlspecialchars(SIGNATURE_FILE); ?></span></p>
        </form>
      </div>

      <div class="card">
        <h2>Preview</h2>
        <?php if (is_file(SIGNATURE_FILE)): ?>
          <div style="background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.12); border-radius:14px; padding:12px;">
            <img src="signature_image.php" style="max-width:100%; height:auto;" alt="signature preview" />
          </div>
        <?php else: ?>
          <div class="alert warn"><b>Belum ada tanda tangan.</b></div>
        <?php endif; ?>
        <p class="muted small">Tips: buat PNG dengan background transparan agar hasil rapi.</p>
      </div>
    </div>
  </div>
</body>
</html>
