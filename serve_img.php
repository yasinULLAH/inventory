<?php
// Prevent MIME-sniffing and UI redressing (Clickjacking)
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
// Block script execution in served documents (e.g. SVG) — image bytes are unaffected
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'");

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

$request_host = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
$is_local_host = in_array($request_host, ['localhost', '127.0.0.1', '::1', ''], true) || str_ends_with($request_host, '.local');

if ($is_local_host) {
    $base_dir = realpath(__DIR__);
} else {
    $base_dir = realpath(dirname(__DIR__) . '/myapp.gobuykar.com');
    if (!$base_dir) {
        $base_dir = realpath(__DIR__);
    }
}

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
$file_is_valid = ($real_file !== false && is_file($real_file) && strpos($real_file, $base_dir) === 0);

if (!$file_is_valid && strpos($clean, 'uploads/bike_') === 0) {
    if ($is_local_host) {
        $db_host = 'localhost';
        $db_user = 'root';
        $db_pass = 'root';
        $db_name = 'bni_enterprises2';
    } else {
        $db_host = 'localhost:3306';
        $db_user = 'gobuykar_yasin';
        $db_pass = 'yasin@1234';
        $db_name = 'gobuykar_bni';
    }
    
    $conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
    if (!$conn->connect_error) {
        $conn->set_charset('utf8mb4');
        $stmt = $conn->prepare('SELECT m.image FROM bikes b JOIN models m ON b.model_id = m.id WHERE b.image = ?');
        if ($stmt) {
            $stmt->bind_param('s', $clean);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                if (!empty($row['image'])) {
                    $fallback_clean = preg_replace('#[^a-zA-Z0-9_/\.\-]#', '', ltrim($row['image'], '/'));
                    $fallback_req = $base_dir . $fallback_clean;
                    $fallback_real = realpath($fallback_req);
                    if ($fallback_real !== false && is_file($fallback_real) && strpos($fallback_real, $base_dir) === 0) {
                        $real_file = $fallback_real;
                        $file_is_valid = true;
                    }
                }
            }
            $stmt->close();
        }
        $conn->close();
    }
}

if (!$file_is_valid) {
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
if ($ext === 'svg') {
    header('Content-Disposition: attachment; filename="' . basename($real_file) . '"');
}
header('Cache-Control: public, max-age=2592000, immutable');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');
readfile($real_file);
exit;