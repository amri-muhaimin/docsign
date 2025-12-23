<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/token.php';
require_once __DIR__ . '/lib/storage.php';

header('Content-Type: application/json');

require_admin_login();

$docId = strtolower(trim($_POST['doc_id'] ?? ''));
if (!preg_match('/^[a-f0-9]{16}$/', $docId)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Doc ID tidak valid']);
    exit;
}
$meta = load_meta($docId);
if (!$meta) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Dokumen tidak ditemukan']);
    exit;
}

$token = new_token();
if (!update_token_for_doc($docId, $token)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Gagal update token']);
    exit;
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base = $scheme . '://' . $host . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\');
$signUrl = $base . '/sign.php?token=' . urlencode($token);

echo json_encode(['ok' => true, 'sign_url' => $signUrl]);
exit;
