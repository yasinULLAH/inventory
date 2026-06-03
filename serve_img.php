<?php
// Prevent MIME-sniffing and UI redressing (Clickjacking)
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Disable error display to prevent server path leakage
ini_set('display_errors', '0');

$p = $_GET['p'] ?? '';

// Block empty requests, external URLs, and Null Byte (\0) injections
if ($p === '' || strpos($p, 'http') === 0 || strpos($p, "\0") !== false) {
    http_response_code(400);
    exit;
}

// Clean basic characters
$clean = preg_replace('#[^a-zA-Z0-9_/\.\-]#', '', $p);
$clean = ltrim($clean, '/');

// Define and verify the absolute base directory
$base_dir = realpath(dirname(__DIR__) . '/myapp.gobuykar.com');
if (!$base_dir) {
    http_response_code(500); // Server configuration error
    exit;
}
$base_dir .= DIRECTORY_SEPARATOR;

// Construct requested path and get its real, resolved path
$requested_file = $base_dir . $clean;
$real_file = realpath($requested_file);

// STRICT PATH TRAVERSAL CHECK: 
// 1. File must exist
// 2. Must be a file (not a directory)
// 3. The resolved path MUST explicitly start with our base directory
if ($real_file === false || !is_file($real_file) || strpos($real_file, $base_dir) !== 0) {
    http_response_code(404);
    exit;
}

// Allowed MIME types mapping
$allowed_types = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
    'svg'  => 'image/svg+xml',
    'ico'  => 'image/x-icon',
    'avif' => 'image/avif',
];

// STRICT EXTENSION WHITELIST: Reject anything that isn't explicitly an image (e.g. .php, .env, .htaccess)
$ext = strtolower(pathinfo($real_file, PATHINFO_EXTENSION));
if (!isset($allowed_types[$ext])) {
    http_response_code(403);
    exit;
}

// Serve the image securely
header('Content-Type: ' . $allowed_types[$ext]);
header('Cache-Control: public, max-age=2592000, immutable');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');
readfile($real_file);
exit;