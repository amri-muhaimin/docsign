<?php
require_once __DIR__ . '/lib/auth.php';

$redirect = $_GET['redirect'] ?? 'admin.php';
$redirect = $redirect ?: 'admin.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw = $_POST['password'] ?? '';
    if (do_admin_login($pw)) {
        header('Location: ' . $redirect);
        exit;
    } else {
        $error = 'Password salah.';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login</title>
  <link rel="stylesheet" href="public/app.css">
</head>
<body>
  <div class="wrap">
    <div class="topbar">
      <div class="brand">
        <div class="mark">DS</div>
        <div class="title">
          <b>Admin</b>
          <span>login untuk tanda tangan & kelola signature</span>
        </div>
      </div>
      <a class="btn secondary" href="index.php">Beranda</a>
    </div>

    <div class="card" style="max-width:520px;">
      <?php if ($error): ?>
        <div class="alert err"><b>Gagal login</b><?php echo htmlspecialchars($error); ?></div>
        <div class="hr"></div>
      <?php endif; ?>

      <form method="post" action="admin_login.php?redirect=<?php echo urlencode($redirect); ?>">
        <label for="password">Password admin</label>
        <input type="password" id="password" name="password" placeholder="Masukkan password admin" required>

        <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
          <button type="submit" class="btn">Masuk</button>
          <a class="btn secondary" href="index.php">Batal</a>
        </div>

        <p class="muted small" style="margin-top:12px;">
          Default password: <span class="code">ChangeMe!12345</span> (sebaiknya segera diganti di <span class="code">config.php</span>).
        </p>
      </form>
    </div>
  </div>
</body>
</html>
