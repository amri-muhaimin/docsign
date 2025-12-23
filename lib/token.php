<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/storage.php';

function new_doc_id(): string {
    // 16 hex chars = 64-bit
    return bin2hex(random_bytes(8));
}

function new_token(): string {
    // token shown to user/admin only once
    return bin2hex(random_bytes(16));
}

function find_doc_by_token(string $token): ?string {
    // Token is stored hashed in meta; scan meta files.
    $dir = STORAGE_DIR . '/meta';
    if (!is_dir($dir)) return null;

    $files = glob($dir . '/*.json') ?: [];
    foreach ($files as $f) {
        $docId = basename($f, '.json');
        $meta = load_meta($docId);
        if (!$meta || empty($meta['token_hash'])) continue;
        if (password_verify($token, $meta['token_hash'])) {
            return $docId;
        }
    }
    return null;
}

function update_token_for_doc(string $docId, string $token): bool {
    $meta = load_meta($docId);
    if (!$meta) return false;
    $meta['token_hash'] = password_hash($token, PASSWORD_BCRYPT);
    $meta['token_rotated_at'] = date('c');
    return save_meta($docId, $meta);
}
