<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/storage.php';
require_once __DIR__ . '/lib/token.php';

function fail(string $msg, int $code = 400) {
    http_response_code($code);
    echo "<!doctype html><meta charset='utf-8'><link rel='stylesheet' href='public/app.css'>";
    echo "<div class='wrap'><div class='card'><div class='alert err'><b>Gagal</b>" . htmlspecialchars($msg) . "</div>";
    echo "<p><a class='btn secondary' href='index.php'>Kembali</a></p></div></div>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Metode tidak valid', 405);

if (empty($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
    fail('Upload gagal. Pastikan file PDF dipilih.');
}

$f = $_FILES['pdf'];
if ($f['size'] > MAX_PDF_BYTES) fail('Ukuran file melebihi batas.');

$mime = mime_content_type($f['tmp_name']);
if ($mime !== 'application/pdf' && $mime !== 'application/octet-stream') {
    fail('File harus berupa PDF.');
}

$docId = new_doc_id();
$token = new_token();

$origPath = original_path($docId);
if (!move_uploaded_file($f['tmp_name'], $origPath)) {
    fail('Gagal menyimpan file di server.');
}

// Basic magic check
$head = file_get_contents($origPath, false, null, 0, 4);
if ($head !== '%PDF') {
    @unlink($origPath);
    fail('File yang diupload tidak terdeteksi sebagai PDF valid.');
}

$meta = [
    'doc_id' => $docId,
    'created_at' => date('c'),
    'token_hash' => password_hash($token, PASSWORD_BCRYPT),
    'original_name' => $f['name'],
    'original_bytes' => $f['size'],
    'signed_at' => null,
];
if (!save_meta($docId, $meta)) {
    @unlink($origPath);
    fail('Gagal menyimpan metadata.');
}

$signUrl = 'sign.php?token=' . urlencode($token);
$statusUrl = 'status.php?id=' . urlencode($docId);
$origDl = 'download.php?id=' . urlencode($docId) . '&type=original';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Upload Berhasil</title>
  <link rel="stylesheet" href="public/app.css">
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div class="brand">
        <div class="mark">DS</div>
        <div class="title">
          <b>Upload berhasil</b>
          <span>Doc ID & token siap digunakan</span>
        </div>
      </div>
      <a class="btn secondary" href="index.php">Beranda</a>
    </div>

    <div class="card">
      <div class="alert ok">
        <b>Dokumen tersimpan</b>
        Simpan informasi di bawah ini agar proses tanda tangan berjalan lancar.
      </div>

      <div class="hr"></div>

      <p><span class="badge">Doc ID: <span class="code"><?php echo htmlspecialchars($docId); ?></span></span></p>

      <p class="muted small">Token (berikan ke dosen / admin):</p>
      <p><span class="code" style="user-select:all"><?php echo htmlspecialchars($token); ?></span></p>

      <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:12px;">
        <a class="btn secondary" href="<?php echo htmlspecialchars($origDl); ?>">Download PDF asli</a>
        <a class="btn secondary" href="<?php echo htmlspecialchars($statusUrl); ?>">Cek status</a>
        <a class="btn" href="<?php echo htmlspecialchars($signUrl); ?>">Buka halaman tanda tangan (Admin)</a>
      </div>

      <div class="hr"></div>
      <p class="muted small">
        Catatan: Halaman tanda tangan memerlukan login admin. Jika token hilang, admin dapat membuat token baru lewat halaman Admin.
      </p>
    </div>
  </div>
</body>
</html>
