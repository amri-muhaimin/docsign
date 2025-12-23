<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/storage.php';

require_admin_login();

$signatureExists = is_file(signature_path());
$docs = list_docs(30);

function h($s){ return htmlspecialchars((string)$s); }
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin - DocSign Mini</title>
  <link rel="stylesheet" href="public/app.css">
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div class="brand">
        <div class="mark">DS</div>
        <div class="title">
          <b>Admin Panel</b>
          <span>signature & token dokumen</span>
        </div>
      </div>
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn secondary" href="index.php">Beranda</a>
        <a class="btn secondary" href="admin_logout.php">Logout</a>
      </div>
    </div>

    <div class="grid">
      <div class="card">
        <h2>Tanda tangan (PNG)</h2>
        <?php if ($signatureExists): ?>
          <div class="alert ok"><b>Signature tersedia</b>Anda dapat mengganti jika diperlukan.</div>
        <?php else: ?>
          <div class="alert warn"><b>Belum ada signature</b>Upload PNG transparan agar hasil lebih rapi.</div>
        <?php endif; ?>

        <div class="hr"></div>

        <form action="upload_signature.php" method="post" enctype="multipart/form-data">
          <label for="sig">Upload signature (PNG)</label>
          <input type="file" id="sig" name="sig" accept="image/png" required>
          <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
            <button type="submit" class="btn">Upload / Ganti</button>
            <?php if ($signatureExists): ?>
              <a class="btn secondary" href="signature_image.php" target="_blank" rel="noopener">Preview</a>
            <?php endif; ?>
          </div>
          <p class="muted small" style="margin-top:10px;">
            Tips: gunakan PNG dengan background transparan, ukuran kira-kira 800×300 px.
          </p>
        </form>
      </div>

      <div class="card">
        <h2>Catatan penggunaan</h2>
        <ul class="muted small" style="margin:0; padding-left:18px;">
          <li>Mahasiswa upload → sistem memberi Doc ID + Token.</li>
          <li>Admin login → buka halaman tanda tangan memakai token.</li>
          <li>Bisa menempatkan banyak tanda tangan, dan <b>duplicate</b> untuk mempercepat.</li>
          <li>Jika token hilang, gunakan tombol <b>Generate Token Baru</b> di tabel dokumen.</li>
        </ul>
        <div class="hr"></div>
        <p class="muted small">
          Untuk keamanan, token lama akan tidak berlaku setelah diganti.
        </p>
      </div>
    </div>

    <div class="card" style="margin-top:14px;">
      <h2>Dokumen terbaru</h2>
      <?php if (!$docs): ?>
        <div class="alert warn"><b>Belum ada dokumen</b>Silakan upload dari halaman beranda.</div>
      <?php else: ?>
        <div class="table">
          <div class="thead">
            <div>Doc ID</div>
            <div>Nama</div>
            <div>Status</div>
            <div>Aksi</div>
          </div>

          <?php foreach ($docs as $m): 
              $id = $m['doc_id'] ?? '';
              $name = $m['original_name'] ?? '-';
              $signed = is_file(signed_path($id));
          ?>
            <div class="trow">
              <div><span class="code"><?php echo h($id); ?></span></div>
              <div class="muted small"><?php echo h($name); ?></div>
              <div>
                <?php if ($signed): ?>
                  <span class="badge" style="border-color: rgba(72,230,166,.35)">✅ Signed</span>
                <?php else: ?>
                  <span class="badge">⏳ Pending</span>
                <?php endif; ?>
              </div>
              <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a class="btn secondary" href="status.php?id=<?php echo urlencode($id); ?>" target="_blank" rel="noopener">Status</a>
                <button class="btn" type="button" onclick="genToken('<?php echo h($id); ?>')">Generate Token Baru</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script src="public/admin.js"></script>
</body>
</html>
