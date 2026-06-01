<?php
header('X-Content-Type-Options: nosniff');

$p = $_GET['p'] ?? '';
if ($p === '' || strpos($p, 'http') === 0 || strpos($p, '..') !== false) {
    http_response_code(400);
    exit;
}

$clean = preg_replace('#[^a-zA-Z0-9_/\.\-]#', '', $p);
$clean = ltrim($clean, '/');

$base = dirname(__DIR__) . '/myapp.gobuykar.com/';
$file = $base . $clean;

if (!file_exists($file) || !is_file($file)) {
    http_response_code(404);
    exit;
}

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$types = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
    'svg'  => 'image/svg+xml',
    'ico'  => 'image/x-icon',
    'avif' => 'image/avif',
];

header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Cache-Control: public, max-age=2592000');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');
readfile($file);
