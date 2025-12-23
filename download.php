<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/storage.php';

$id = strtolower(trim($_GET['id'] ?? ''));
$type = strtolower(trim($_GET['type'] ?? 'original'));

if (!preg_match('/^[a-f0-9]{16}$/', $id)) {
    http_response_code(400);
    echo "Bad request";
    exit;
}

$path = ($type === 'signed') ? signed_path($id) : original_path($id);
if (!is_file($path)) {
    http_response_code(404);
    echo "Not found";
    exit;
}

$filename = ($type === 'signed') ? ("signed_" . $id . ".pdf") : ("original_" . $id . ".pdf");

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: no-store');
readfile($path);
exit;
