<?php
require_once __DIR__ . '/bootstrap.php';

function meta_path(string $docId): string {
    return STORAGE_DIR . '/meta/' . $docId . '.json';
}
function original_path(string $docId): string {
    return STORAGE_DIR . '/original/' . $docId . '.pdf';
}
function signed_path(string $docId): string {
    return STORAGE_DIR . '/signed/' . $docId . '.pdf';
}
function signature_path(): string {
    // single signature file (PNG)
    return STORAGE_DIR . '/signatures/signature.png';
}

function load_meta(string $docId): ?array {
    $p = meta_path($docId);
    if (!is_file($p)) return null;
    $raw = @file_get_contents($p);
    if ($raw === false) return null;
    $j = json_decode($raw, true);
    return is_array($j) ? $j : null;
}

function save_meta(string $docId, array $meta): bool {
    $p = meta_path($docId);
    $meta['doc_id'] = $docId;
    $meta['updated_at'] = date('c');
    $tmp = $p . '.tmp';
    $ok = file_put_contents($tmp, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($ok === false) return false;
    return @rename($tmp, $p);
}

function list_docs(int $limit = 50): array {
    $dir = STORAGE_DIR . '/meta';
    $out = [];
    if (!is_dir($dir)) return $out;

    $files = glob($dir . '/*.json') ?: [];
    usort($files, function($a, $b){
        return filemtime($b) <=> filemtime($a);
    });

    foreach (array_slice($files, 0, $limit) as $f) {
        $docId = basename($f, '.json');
        $meta = load_meta($docId);
        if ($meta) $out[] = $meta;
    }
    return $out;
}
