<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/storage.php';

require_admin_login();

function fail(string $msg) {
    echo "<!doctype html><meta charset='utf-8'><link rel='stylesheet' href='public/app.css'>";
    echo "<div class='wrap'><div class='card'><div class='alert err'><b>Gagal</b>" . htmlspecialchars($msg) . "</div>";
    echo "<p><a class='btn secondary' href='admin.php'>Kembali</a></p></div></div>";
    exit;
}

function make_background_transparent(string $source, string $dest): bool {
    if (!function_exists('imagecreatefrompng')) return false;

    $img = imagecreatefrompng($source);
    if (!$img) return false;

    imagealphablending($img, false);
    imagesavealpha($img, true);

    $w = imagesx($img);
    $h = imagesy($img);

    $bgColor = imagecolorat($img, 0, 0);
    $bg = imagecolorsforindex($img, $bgColor);
    $transparent = imagecolorallocatealpha($img, $bg['red'], $bg['green'], $bg['blue'], 127);

    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $rgba = imagecolorsforindex($img, imagecolorat($img, $x, $y));
            if ($rgba['alpha'] === 0 && $rgba['red'] === $bg['red'] && $rgba['green'] === $bg['green'] && $rgba['blue'] === $bg['blue']) {
                imagesetpixel($img, $x, $y, $transparent);
            }
        }
    }

    $saved = imagepng($img, $dest);
    imagedestroy($img);
    return $saved;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Metode tidak valid.');

if (empty($_FILES['sig']) || $_FILES['sig']['error'] !== UPLOAD_ERR_OK) {
    fail('Upload gagal. Pastikan file PNG dipilih.');
}
$f = $_FILES['sig'];
if ($f['size'] > SIGNATURE_MAX_BYTES) fail('Ukuran file terlalu besar.');

$mime = mime_content_type($f['tmp_name']);
if ($mime !== 'image/png' && $mime !== 'application/octet-stream') {
    fail('Signature harus PNG.');
}

$tmp = $f['tmp_name'];

// Basic PNG signature bytes check
$head = file_get_contents($tmp, false, null, 0, 8);
if ($head !== "\x89PNG\r\n\x1a\n") {
    fail('File tidak terdeteksi sebagai PNG valid.');
}

$dest = signature_path();
if (!make_background_transparent($tmp, $dest)) {
    if (!move_uploaded_file($tmp, $dest)) {
        fail('Gagal menyimpan signature.');
    }
}

header('Location: admin.php');
exit;
