<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
session_start();
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$db_host = 'localhost';
$db_user = 'root';
$db_pass = 'root';
$db_name = 'bni_enterprises2';

function db_connect()
{
    global $db_host, $db_user, $db_pass, $db_name;
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error)
        return null;
    $conn->set_charset('utf8mb4');
    return $conn;
}

$conn = db_connect();
if (!$conn) {
    die('System Maintenance. Please check back later.');
}

function get_setting($key)
{
    global $conn;
    $stmt = $conn->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $r = $stmt->get_result();
    $row = $r->fetch_assoc();
    return $row ? $row['setting_value'] : null;
}

function sanitize($val)
{
    return htmlspecialchars(strip_tags(trim($val ?? '')), ENT_QUOTES, 'UTF-8');
}

function format_speed($val)
{
    $v = trim($val ?? '');
    if ($v === '')
        return '100km/h';
    if (is_numeric($v))
        return $v . 'km/h';
    return $v;
}

function format_range($val)
{
    $v = trim($val ?? '');
    if ($v === '')
        return '80km Range';
    if (is_numeric($v))
        return $v . 'km Range';
    return $v;
}

function generate_math_captcha()
{
    $n1 = rand(1, 9);
    $n2 = rand(1, 9);
    $_SESSION['captcha_ans'] = $n1 + $n2;
    $width = 120;
    $height = 45;
    $img = imagecreatetruecolor($width, $height);
    $bg = imagecolorallocate($img, 15, 23, 42);
    $fg = imagecolorallocate($img, 248, 250, 252);
    $line = imagecolorallocate($img, 99, 102, 241);
    imagefill($img, 0, 0, $bg);
    for ($i = 0; $i < 8; $i++) {
        imageline($img, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $line);
    }
    for ($i = 0; $i < 80; $i++) {
        imagesetpixel($img, rand(0, $width), rand(0, $height), $line);
    }
    imagestring($img, 5, 30, 15, "$n1 + $n2 = ?", $fg);
    ob_start();
    imagepng($img);
    $data = ob_get_clean();
    imagedestroy($img);
    return 'data:image/png;base64,' . base64_encode($data);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header('Location: ' . $self_page . '?err=' . urlencode('Security token expired. Please refresh the page and try again.'));
        exit;
    }
    $user_captcha = (int) ($_POST['captcha'] ?? 0);
    $real_captcha = (int) ($_SESSION['captcha_ans'] ?? -1);
    if ($user_captcha !== $real_captcha) {
        header('Location: ' . $self_page . '?err=' . urlencode('Security Check Failed! Invalid Captcha.'));
        exit;
    }
    if (isset($_POST['request_bike'])) {
        $name = sanitize($_POST['name']);
        $phone = sanitize($_POST['phone']);
        $details = sanitize($_POST['bike_details']);
        $st = $conn->prepare('INSERT INTO bike_requests (customer_name, customer_phone, bike_details) VALUES (?,?,?)');
        $st->bind_param('sss', $name, $phone, $details);
        $st->execute();
        header('Location: ' . $self_page . '?msg=Request Sent! Our team will contact you.');
        exit;
    }
    if (isset($_POST['request_quote'])) {
        $name = sanitize($_POST['name']);
        $phone = sanitize($_POST['phone']);
        $bike_id = (int) $_POST['bike_id'];
        $details = sanitize($_POST['details']);
        $st = $conn->prepare('INSERT INTO quote_requests (customer_name, customer_phone, bike_id, details) VALUES (?,?,?,?)');
        $st->bind_param('ssis', $name, $phone, $bike_id, $details);
        $st->execute();
        header('Location: ' . $self_page . '?msg=Quote Requested! Check WhatsApp shortly.');
        exit;
    }
}
$n1 = rand(1, 9);
$n2 = rand(1, 9);
$_SESSION['captcha_ans'] = $n1 + $n2;
$captcha_q = "$n1 + $n2 = ?";
$captcha_img_src = generate_math_captcha();
$company_name = get_setting('company_name') ?? 'BNI Enterprises';
$hero_title = get_setting('landing_hero_title') ?? 'The Next Generation of Electric Mobility';
$hero_sub = get_setting('landing_hero_subtitle') ?? 'Eco-friendly, powerful, and designed for the modern world.';
$wa_number = get_setting('company_whatsapp') ?? '';
$view = $_GET['view'] ?? 'home';
$self_page = $_SERVER['PHP_SELF'] ?? 'landing.php';
$is_bike_detail = false;
$bike_detail = null;
$meta_title = sanitize($company_name) . ' | Future of Electric Mobility';
$meta_description = 'Explore premium electric bikes and scooters with modern design, performance, and support.';
$meta_image = 'logo.png';
$meta_canonical = basename($self_page) . '?view=' . urlencode($view);
if ($view === 'bike') {
    $bike_id = max(0, (int) ($_GET['id'] ?? 0));
    if ($bike_id > 0) {
        $bd_stmt = $conn->prepare('SELECT b.id, b.chassis_number, b.model_id, b.color, b.status, b.created_at, b.image as bike_image, m.model_name, m.category, m.image as model_image, m.top_speed, m.max_range
            FROM bikes b
            JOIN models m ON m.id = b.model_id
            WHERE b.id = ?
            LIMIT 1');
        $bd_stmt->bind_param('i', $bike_id);
        $bd_stmt->execute();
        $bd_res = $bd_stmt->get_result();
        $bike_detail = $bd_res->fetch_assoc();
        if ($bike_detail) {
            $is_bike_detail = true;
            $img = $bike_detail['bike_image'] ?: ($bike_detail['model_image'] ?: 'logo.png');
            $meta_title = sanitize($bike_detail['model_name']) . ' | ' . sanitize($company_name);
            $meta_description = 'View ' . sanitize($bike_detail['model_name']) . ' details, category, color, availability and inquiry options.';
            $meta_image = $img;
            $meta_canonical = basename($self_page) . '?view=bike&id=' . (int) $bike_detail['id'];
        } else {
            http_response_code(404);
            $meta_title = 'Bike Not Found | ' . sanitize($company_name);
            $meta_description = 'The requested bike detail page could not be found.';
        }
    }
}
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_url = $scheme . '://' . $host . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$canonical_url = $base_url . '/' . ltrim($meta_canonical, '/');
$meta_image_url = (preg_match('/^https?:\/\//', $meta_image)) ? $meta_image : ($base_url . '/' . ltrim($meta_image, '/'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $meta_title ?></title>
    <meta name="description" content="<?= sanitize($meta_description) ?>">
    <link rel="canonical" href="<?= sanitize($canonical_url) ?>">
    <meta property="og:type" content="<?= $is_bike_detail ? 'product' : 'website' ?>">
    <meta property="og:title" content="<?= $meta_title ?>">
    <meta property="og:description" content="<?= sanitize($meta_description) ?>">
    <meta property="og:url" content="<?= sanitize($canonical_url) ?>">
    <meta property="og:image" content="<?= sanitize($meta_image_url) ?>">
    <meta property="og:site_name" content="<?= sanitize($company_name) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $meta_title ?>">
    <meta name="twitter:description" content="<?= sanitize($meta_description) ?>">
    <meta name="twitter:image" content="<?= sanitize($meta_image_url) ?>">
    <?php if ($is_bike_detail && $bike_detail): ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Product",
      "name": "<?= sanitize($bike_detail['model_name']) ?>",
      "image": "<?= sanitize($meta_image_url) ?>",
      "description": "<?= sanitize($meta_description) ?>",
      "brand": {
        "@type": "Brand",
        "name": "<?= sanitize($company_name) ?>"
      },
      "category": "<?= sanitize($bike_detail['category']) ?>",
      "url": "<?= sanitize($canonical_url) ?>"
    }
    </script>
    <?php endif; ?>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="icon" type="image/png" sizes="96x96" href="favicon-96x96.png">
    <link rel="apple-touch-icon" href="apple-touch-icon.png">
    <link rel="manifest" href="site.webmanifest">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@200;400;600;900&family=Space+Grotesk:wght@300;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/glightbox/3.2.0/css/glightbox.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.5);
            --secondary: #06b6d4;
            --accent: #f43f5e;
            --dark: #0f172a;
            --darker: #020617;
            --glass: rgba(255, 255, 255, 0.03);
            --glass-strong: rgba(255, 255, 255, 0.07);
            --glass-border: rgba(255, 255, 255, 0.1);
            --text: #f8fafc;
            --text-dim: #94a3b8;
            --grad: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--darker);
            color: var(--text);
            line-height: 1.6;
            overflow-x: hidden;
            scroll-behavior: smooth;
        }
        h1, h2, h3, .logo-text { font-family: 'Space+Grotesk', sans-serif; }
        #bg-canvas {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; pointer-events: none;
        }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: var(--darker); }
        ::-webkit-scrollbar-thumb { background: var(--grad); border-radius: 10px; }
        .container { width: 100%; max-width: 1300px; margin: 0 auto; padding: 0 25px; }
        section { padding: 100px 0; position: relative; }
        .glass {
            background: var(--glass);
            backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 30px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .glass:hover {
            background: var(--glass-strong);
            border-color: rgba(99, 102, 241, 0.3);
            transform: translateY(-5px);
        }
        nav {
            position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
            width: 95%; max-width: 1200px;
            background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border); border-radius: 100px;
            padding: 12px 30px; display: flex; justify-content: space-between; align-items: center;
            z-index: 2000; transition: 0.3s;
        }
        .logo-wrap { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .logo-img { height: 35px; filter: drop-shadow(0 0 10px var(--primary-glow)); }
        .logo-text { font-size: 1.3rem; font-weight: 800; background: var(--grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .nav-links { display: flex; gap: 35px; list-style: none; }
        .nav-links a { color: var(--text); text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: 0.3s; opacity: 0.7; }
        .nav-links a:hover { opacity: 1; color: var(--primary); text-shadow: 0 0 10px var(--primary-glow); }
        .hero {
            height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;
            background: radial-gradient(circle at center, rgba(99, 102, 241, 0.1) 0%, transparent 70%);
        }
        .hero-title {
            font-size: clamp(3rem, 10vw, 6.5rem); font-weight: 900; line-height: 0.95; letter-spacing: -3px; margin-bottom: 25px;
            background: linear-gradient(to bottom, #fff 40%, rgba(255,255,255,0.4)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .hero-title span { background: var(--grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero-sub { font-size: clamp(1.1rem, 3vw, 1.5rem); color: var(--text-dim); max-width: 800px; font-weight: 300; margin-bottom: 50px; }
        .btn {
            padding: 16px 40px; border-radius: 100px; font-weight: 800; text-decoration: none; transition: 0.4s;
            display: inline-flex; align-items: center; gap: 12px; border: none; cursor: pointer; text-transform: uppercase; letter-spacing: 1px;
        }
        .btn-main { background: var(--grad); color: white; box-shadow: 0 15px 30px -10px rgba(99, 102, 241, 0.6); }
        .btn-main:hover { transform: scale(1.05) translateY(-3px); box-shadow: 0 20px 40px -10px rgba(99, 102, 241, 0.8); }
        .btn-outline { background: transparent; border: 1px solid var(--glass-border); color: white; backdrop-filter: blur(10px); }
        .btn-outline:hover { background: var(--glass-strong); border-color: var(--primary); }
        .sec-title { font-size: clamp(2.5rem, 5vw, 4rem); font-weight: 900; text-align: center; margin-bottom: 60px; position: relative; }
        .sec-title::before {
            content: attr(data-text); position: absolute; top: -20px; left: 50%; transform: translateX(-50%);
            font-size: 6rem; opacity: 0.03; width: 100%; white-space: nowrap; pointer-events: none;
        }
        .bento { display: grid; grid-template-columns: repeat(12, 1fr); gap: 25px; }
        .bento-card { grid-column: span 12; padding: 40px; }
        @media (min-width: 768px) {
            .bento-card:nth-child(1) { grid-column: span 8; }
            .bento-card:nth-child(2) { grid-column: span 4; }
            .bento-card:nth-child(3) { grid-column: span 4; }
            .bento-card:nth-child(4) { grid-column: span 8; }
        }
        .bike-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
        }
        @media (min-width: 768px) {
            .bike-grid { grid-template-columns: repeat(2, 1fr); }
        }
        .bike-card {
            position: relative; overflow: hidden; padding: 15px;
        }
        .bike-img {
            width: 100%; height: 260px; border-radius: 20px; overflow: hidden; position: relative; background: #0f172a;
            display: flex; align-items: center; justify-content: center; margin-bottom: 20px;
        }
        .bike-img img { width: 100%; height: 100%; object-fit: cover; transition: 0.7s cubic-bezier(0.4, 0, 0.2, 1); }
        .bike-card:hover .bike-img img { transform: scale(1.1) rotate(1deg); filter: brightness(1.1); }
        .bike-link { color: inherit; text-decoration: none; display: block; }
        .detail-wrap { max-width: 1100px; margin: 0 auto; }
        .detail-grid { display: grid; grid-template-columns: 1fr; gap: 25px; }
        @media (min-width: 900px) { .detail-grid { grid-template-columns: 1.2fr 1fr; } }
        .detail-hero-img { width: 100%; min-height: 420px; border-radius: 24px; object-fit: cover; background: #0f172a; }
        .detail-panel { padding: 30px; }
        .detail-kv { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin: 20px 0 25px; }
        .detail-kv .feat-item { background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); border-radius: 14px; padding: 12px; }
        .bike-status {
            position: absolute; top: 15px; left: 15px; padding: 6px 16px; border-radius: 50px;
            font-size: 0.7rem; font-weight: 900; z-index: 10; box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            text-transform: uppercase; letter-spacing: 1px;
        }
        .badge-bestseller { background: linear-gradient(135deg, #f59e0b, #f97316); color: #fff; }
        .badge-newarrival { background: linear-gradient(135deg, #06b6d4, #3b82f6); color: #fff; }
        .badge-lowstock { background: linear-gradient(135deg, #ef4444, #ec4899); color: #fff; animation: pulse-badge 2s infinite; }
        .badge-popular { background: linear-gradient(135deg, #8b5cf6, #a855f7); color: #fff; }
        .badge-default { background: var(--grad); color: #fff; }
        .bike-status i { margin-right: 5px; }
        @keyframes pulse-badge { 0%,100%{box-shadow:0 5px 15px rgba(239,68,68,0.3)} 50%{box-shadow:0 5px 25px rgba(239,68,68,0.6)} }
        .bike-title { font-size: 1.6rem; font-weight: 800; margin-bottom: 10px; }
        .bike-features { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px; }
        .feat-item { display: flex; align-items: center; gap: 10px; font-size: 0.85rem; color: var(--text-dim); }
        .feat-item i { color: var(--primary); font-size: 1rem; }
        .price-request {
            background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.2);
            padding: 12px; border-radius: 15px; text-align: center; color: var(--primary); font-weight: 700; font-size: 0.9rem; margin-bottom: 20px;
        }
        .wa-action {
            background: #25d366; color: white; padding: 15px; border-radius: 18px; display: flex; align-items: center; justify-content: center; gap: 10px;
            font-weight: 800; text-decoration: none; transition: 0.3s;
        }
        .wa-action:hover { background: #128c7e; transform: scale(1.02); box-shadow: 0 10px 20px rgba(37, 211, 102, 0.3); }
        .stats { display: flex; flex-wrap: wrap; justify-content: space-around; gap: 40px; padding: 60px 0; background: rgba(255,255,255,0.02); border-radius: 40px; }
        .stat-item { text-align: center; flex: 1; min-width: 150px; }
        .stat-val { font-size: 3.5rem; font-weight: 900; background: var(--grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-lab { font-size: 0.9rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 2px; }
        .steps { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; }
        .step-card { position: relative; padding: 50px 30px; }
        .step-num {
            position: absolute; top: 20px; right: 30px; font-size: 4rem; font-weight: 900; opacity: 0.05;
        }
        .gallery-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            align-items: flex-start;
        }
        .gallery-item {
            flex: 1 1 280px;
            max-width: 380px;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid var(--glass-border);
            position: relative;
            cursor: pointer;
            background: #0f172a;
        }
        .gallery-item img {
            width: 100%; display: block;
            height: 260px; object-fit: cover;
            transition: transform 0.5s ease, filter 0.5s ease;
        }
        .gallery-item:hover img { transform: scale(1.08); filter: saturate(1.3) brightness(0.7); }
        .gallery-info {
            position: absolute; bottom: 0; left: 0; right: 0;
            background: linear-gradient(to top, rgba(2,6,23,0.95) 0%, rgba(2,6,23,0.6) 60%, transparent 100%);
            padding: 30px 20px 20px;
            transform: translateY(100%);
            transition: transform 0.35s cubic-bezier(0.4,0,0.2,1);
        }
        .gallery-item:hover .gallery-info { transform: translateY(0); }
        .gallery-info h4 { color: #fff; font-size: 1rem; font-weight: 700; margin-bottom: 5px; }
        .gallery-info p { font-size: 0.78rem; color: rgba(255,255,255,0.65); margin: 0; }
        .glightbox-clean .gslide-description {
            background: rgba(15, 23, 42, 0.85) !important;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 20px !important;
            text-align: center !important;
            width: max-content !important;
            height: max-content !important;
            max-width: 90% !important;
            margin: auto !important;
            padding: 20px 30px !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            align-items: center !important;
        }
        .glightbox-clean .gdesc-inner { padding: 0 !important; width: 100%; text-align: center; }
        .glightbox-clean .gslide-title { font-family: 'Space+Grotesk', sans-serif; font-size: 1.5rem !important; font-weight: 800 !important; color: #fff !important; margin-bottom: 5px !important; }
        .glightbox-clean .gslide-desc { font-family: 'Outfit', sans-serif; font-size: 1rem !important; color: #94a3b8 !important; margin: 0 !important; }
        .leaders-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            justify-content: center;
        }
        .leader-card {
            flex: 1 1 280px;
            max-width: 340px;
            padding: 40px 30px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .leader-avatar-wrap {
            position: relative;
            width: 130px; height: 130px;
            margin: 0 auto 25px;
        }
        .leader-avatar-wrap::before {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            background: conic-gradient(
                #6366f1, #a855f7, #ec4899, #06b6d4, #6366f1
            );
            animation: neonSpin 3s linear infinite;
            z-index: 0;
        }
        .leader-avatar-wrap::after {
            content: '';
            position: absolute;
            inset: -6px;
            border-radius: 50%;
            background: conic-gradient(
                #6366f1, #a855f7, #ec4899, #06b6d4, #6366f1
            );
            animation: neonSpin 3s linear infinite;
            filter: blur(10px);
            opacity: 0.6;
            z-index: -1;
        }
        @keyframes neonSpin { 0%{transform:rotate(0deg)} 100%{transform:rotate(360deg)} }
        .leader-img {
            position: relative; z-index: 1;
            width: 130px; height: 130px;
            border-radius: 50%; object-fit: cover;
            border: 4px solid var(--darker);
            display: block;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .leader-card { cursor: pointer; transition: transform 0.3s; }
        .leader-card:hover { transform: translateY(-5px); }
        .leader-card:hover .leader-avatar-wrap::before {
            animation: neonSpin var(--spin-speed, 0.15s) linear infinite, plasmaSpark var(--spark-speed, 0.1s) infinite alternate;
            background: conic-gradient(#0ff, #fff, #a855f7, #6366f1, #0ff);
            inset: calc(-8px * var(--audio-scale, 1));
            filter: brightness(calc(2 * var(--audio-scale, 1))) contrast(1.5);
        }
        .leader-card:hover .leader-avatar-wrap::after {
            animation: neonSpin var(--spin-speed, 0.2s) reverse infinite;
            background: conic-gradient(transparent, #fff, transparent, #06b6d4);
            filter: blur(calc(12px * var(--audio-scale, 1))) brightness(calc(2.5 * var(--audio-scale, 1)));
            inset: calc(-12px * var(--audio-scale, 1));
        }
        .leader-card:hover .leader-img {
            transform: scale(1.08);
            border-color: #fff;
            box-shadow: 0 0 30px 10px rgba(6, 182, 212, 0.6), inset 0 0 15px rgba(255, 255, 255, 0.8);
            filter: brightness(1.1) contrast(1.1);
        }
        @keyframes plasmaSpark {
            0% { transform: scale(1) rotate(0deg); opacity: 1; }
            33% { transform: scale(1.05) rotate(2deg) skewX(2deg); opacity: 0.9; filter: hue-rotate(45deg); }
            66% { transform: scale(0.95) rotate(-2deg) skewX(-2deg); opacity: 1; }
            100% { transform: scale(1.02) rotate(1deg) skewY(2deg); opacity: 0.8; filter: hue-rotate(-20deg); }
        }
        .leader-name {
            font-size: 1.4rem; font-weight: 800;
            background: linear-gradient(135deg, #fff 40%, rgba(255,255,255,0.6));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            margin-bottom: 6px;
        }
        .leader-position {
            font-size: 0.75rem; font-weight: 900; letter-spacing: 2px;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            text-transform: uppercase; margin-bottom: 18px;
        }
        .leader-divider {
            width: 40px; height: 3px; border-radius: 3px;
            background: linear-gradient(90deg, #6366f1, #a855f7);
            margin: 0 auto 18px;
        }
        .leader-quote {
            font-size: 0.9rem; color: var(--text-dim);
            font-style: italic; line-height: 1.7;
            position: relative; padding: 0 10px;
        }
        .leader-quote::before { content: '\201C'; font-size: 2.5rem; color: #6366f1; opacity: 0.4; line-height: 0; vertical-align: -0.6em; margin-right: 4px; }
        .leader-quote::after  { content: '\201D'; font-size: 2.5rem; color: #a855f7; opacity: 0.4; line-height: 0; vertical-align: -0.6em; margin-left: 4px; }
        .footer-map-section {
            padding: 60px 0; background: rgba(255,255,255,0.02);
        }
        footer {
            position: relative;
            background: linear-gradient(180deg, #0f172a 0%, #020617 100%);
            padding: 60px 0 0;
            overflow: hidden;
            border-top: 1px solid rgba(99, 102, 241, 0.2);
        }
        .footer-wave-transition {
            position: absolute;
            bottom: 0; left: 0;
            width: 100%; height: 100%;
            z-index: 0; pointer-events: none;
            opacity: 0.2;
        }
        .footer-wave-transition svg {
            position: absolute;
            bottom: 0; left: 0;
            width: 200%; height: 100%;
        }
        .wave-1 { animation: waveDrift1 15s linear infinite; }
        .wave-2 { animation: waveDrift2 20s linear infinite; }
        .wave-3 { animation: waveDrift3 25s linear infinite; }
        @keyframes waveDrift1 { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
        @keyframes waveDrift2 { 0% { transform: translateX(-50%); } 100% { transform: translateX(0); } }
        @keyframes waveDrift3 { 0% { transform: translateX(-25%); } 100% { transform: translateX(-75%); } }
        .footer-glow {
            position: absolute; top: 30px; left: 50%; width: 800px; height: 300px;
            transform: translateX(-50%); border-radius: 50%;
            background: radial-gradient(ellipse, rgba(99,102,241,0.18), rgba(168,85,247,0.07) 50%, transparent 70%);
            pointer-events: none; z-index: 0; filter: blur(50px);
        }
        .footer-content-area {
            position: relative; z-index: 10; padding: 20px 0 50px;
        }
        .footer-wrap {
            display: grid; grid-template-columns: 1fr; gap: 40px; margin-bottom: 50px;
        }
        @media (min-width: 768px) { .footer-wrap { grid-template-columns: repeat(3, 1fr); gap: 60px; } }
        .footer-head {
            font-size: 1.1rem; font-weight: 800; margin-bottom: 25px;
            background: linear-gradient(135deg, #818cf8, #c084fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            padding-left: 15px; border-left: 3px solid #818cf8;
        }
        .footer-desc { color: #b4b8d4; font-size: 0.9rem; line-height: 1.7; }
        .footer-particles { position: absolute; inset: 0; pointer-events: none; z-index: 1; overflow: hidden; }
        .footer-particle {
            position: absolute; border-radius: 50%;
            opacity: 0; animation: floatParticle 6s infinite ease-in;
        }
        @keyframes floatParticle {
            0%   { opacity: 0; transform: translateY(0) scale(0); }
            15%  { opacity: 0.7; transform: translateY(-20px) scale(1); }
            100% { opacity: 0; transform: translateY(-200px) scale(0.2); }
        }
        .socials { display: flex; gap: 15px; margin-top: 25px; }
        .socials a {
            width: 45px; height: 45px;
            background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(168,85,247,0.1));
            border: 1px solid rgba(129,140,248,0.2);
            display: flex; align-items: center; justify-content: center; border-radius: 15px;
            color: #c4b5fd; transition: 0.4s;
        }
        .socials a:hover {
            background: linear-gradient(135deg, #6366f1, #a855f7);
            color: #fff; transform: translateY(-5px) rotate(8deg);
            box-shadow: 0 8px 25px rgba(99,102,241,0.4);
        }
        .footer-link {
            color: #a5b0d6; text-decoration: none; transition: all 0.3s; display: inline-block;
            font-size: 0.95rem;
        }
        .footer-link:hover { color: #818cf8; padding-left: 8px; }
        .footer-link i { margin-right: 8px; color: #6366f1; }
        .footer-bottom {
            text-align: center; padding: 25px 0; margin-top: 30px;
            border-top: 1px solid rgba(129,140,248,0.12);
            font-size: 0.8rem; color: #6b7099;
            background: rgba(0,0,0,0.15);
        }
        .bike-img-placeholder {
            width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, rgba(99,102,241,0.08), rgba(168,85,247,0.05));
        }
        .bike-img-placeholder svg { width: 60%; max-width: 180px; height: auto; }
        .modal {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.9); backdrop-filter: blur(20px);
            z-index: 3000; align-items: center; justify-content: center; padding: 20px;
        }
        .modal-body {
            background: #0f172a; border: 1px solid var(--glass-border); padding: 45px; border-radius: 40px;
            width: 100%; max-width: 550px; position: relative; box-shadow: 0 0 100px var(--primary-glow);
        }
        .modal-close { position: absolute; top: 25px; right: 30px; font-size: 2rem; cursor: pointer; color: var(--text-dim); }
        input, textarea, select {
            width: 100%; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border);
            padding: 16px 20px; border-radius: 18px; color: white; margin-bottom: 20px; font-size: 1rem; outline: none; transition: 0.3s;
            appearance: none; -webkit-appearance: none; font-family: 'Outfit', sans-serif;
        }
        input:focus, textarea:focus, select:focus { border-color: var(--primary); background: rgba(255,255,255,0.08); }
        select option { background: var(--darker); color: var(--text); }
        .nav-hidden { transform: translate(-50%, -150%) !important; opacity: 0; pointer-events: none; }
        #preloader { position: fixed; inset: 0; background: var(--darker); z-index: 9999; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .loader-ring { width: 80px; height: 80px; border: 5px solid var(--glass-border); border-top-color: var(--primary); border-radius: 50%; animation: spin 1s infinite linear; margin-bottom: 20px; }
        a { position: relative; }
        a::after {
            content: ''; position: absolute; inset: -2px; border-radius: inherit;
            opacity: 0; pointer-events: none; z-index: 10;
            mix-blend-mode: screen; transition: opacity 0.2s;
        }
        a:hover::after {
            opacity: 1;
            box-shadow: 0 0 6px 1px #06b6d4, inset 0 0 4px #6366f1;
            border: 1px solid rgba(255,255,255,0.4);
            animation: sparkHover 0.15s infinite alternate;
        }
        a:active::after {
            opacity: 1;
            inset: -6px;
            box-shadow: 0 0 30px 10px #0ff, inset 0 0 20px #fff;
            background: rgba(255,255,255,0.2);
            border: 2px solid #fff;
            animation: lightningBlink 0.08s infinite alternate;
        }
        @keyframes sparkHover {
            0% { transform: scale(1) translate(0.5px, -0.5px); filter: brightness(1); }
            100% { transform: scale(1.02) translate(-0.5px, 0.5px); filter: brightness(1.4) drop-shadow(0 0 2px #fff); }
        }
        @keyframes lightningBlink {
            0% { transform: scale(1.05); filter: brightness(2); }
            100% { transform: scale(1.15); filter: brightness(3); box-shadow: 0 0 50px 15px #fff, inset 0 0 30px #0ff; }
        }
        @media (max-width: 768px) {
            nav { padding: 10px 20px; }
            .nav-links { display: none; }
            .hero-title { font-size: 3.5rem; }
            section { padding: 60px 0; }
            .sec-title::before { font-size: 3rem; }
        }
        .socials a {
            text-decoration: unset;
        }
        .leader-card.lightning-blast { z-index: 50; position: relative; }
        .leader-card.lightning-blast .leader-avatar-wrap::before,
        .leader-card.lightning-blast .leader-avatar-wrap::after {
            animation: blastExpand 0.7s cubic-bezier(0.1, 0.8, 0.2, 1) forwards !important;
            background: conic-gradient(#fff, #0ff, #fff, #a855f7, #fff) !important;
            filter: brightness(3) !important;
            inset: -8px !important;
        }
        .leader-card.lightning-blast .leader-img {
            animation: imgPop 0.7s cubic-bezier(0.1, 0.8, 0.2, 1) forwards !important;
        }
        @keyframes blastExpand {
            0% { transform: scale(1) rotate(0deg); opacity: 1; }
            50% { transform: scale(3.5) rotate(180deg); opacity: 0.8; }
            100% { transform: scale(7) rotate(360deg); opacity: 0; }
        }
        @keyframes imgPop {
            0% { transform: scale(1.08); filter: brightness(1.1); box-shadow: 0 0 30px #06b6d4; }
            20% { transform: scale(1.4); filter: brightness(2); box-shadow: 0 0 100px 40px #0ff, inset 0 0 40px #fff; border-color: #fff; }
            100% { transform: scale(1); filter: brightness(1); box-shadow: 0 0 0 transparent; border-color: var(--darker); }
        }
    </style>
    <script>
    function imageFallback(img, modelImg) {
        if (modelImg && !img.dataset.triedModel && img.getAttribute('src') !== modelImg) {
            img.dataset.triedModel = 'true';
            img.src = modelImg;
        } else {
            bikePlaceholder(img);
        }
    }
    function bikePlaceholder(img){
        var w=img.parentNode; 
        var cls = img.className || '';
        img.remove();
        var d=document.createElement('div'); 
        d.className='bike-img-placeholder ' + cls;
        if(cls.includes('detail-hero-img')) { d.style.minHeight = '420px'; d.style.borderRadius = '24px'; }
        d.innerHTML='<svg viewBox="0 0 500 350" fill="none" xmlns="http://www.w3.org/2000/svg">'
+'<defs><linearGradient id="eg" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#6366f1"/><stop offset="100%" stop-color="#a855f7"/></linearGradient>'
+'<linearGradient id="eg2" x1="0" y1="0" x2="1" y2="0"><stop offset="0%" stop-color="#06b6d4"/><stop offset="100%" stop-color="#6366f1"/></linearGradient></defs>'
+'<circle cx="115" cy="260" r="55" stroke="url(#eg)" stroke-width="6" opacity="0.5"/>'
+'<circle cx="115" cy="260" r="38" stroke="#6366f1" stroke-width="3" opacity="0.3"/>'
+'<circle cx="115" cy="260" r="6" fill="#a855f7" opacity="0.6"/>'
+'<circle cx="385" cy="260" r="55" stroke="url(#eg)" stroke-width="6" opacity="0.5"/>'
+'<circle cx="385" cy="260" r="38" stroke="#6366f1" stroke-width="3" opacity="0.3"/>'
+'<circle cx="385" cy="260" r="6" fill="#a855f7" opacity="0.6"/>'
+'<path d="M130 245 C140 190, 170 140, 220 120 L310 110 C340 108, 355 115, 365 130 L380 200 L385 245" stroke="url(#eg)" stroke-width="7" stroke-linecap="round" stroke-linejoin="round" fill="none" opacity="0.5"/>'
+'<path d="M185 128 Q220 108, 290 112 Q310 113, 315 118" stroke="url(#eg2)" stroke-width="10" stroke-linecap="round" opacity="0.45"/>'
+'<rect x="200" y="150" width="100" height="45" rx="10" stroke="#6366f1" stroke-width="4" fill="rgba(99,102,241,0.08)" opacity="0.5"/>'
+'<path d="M245 158 L237 172 L248 172 L240 188" stroke="#06b6d4" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" opacity="0.7"/>'
+'<rect x="260" y="160" width="8" height="25" rx="2" fill="#6366f1" opacity="0.3"/>'
+'<rect x="273" y="165" width="8" height="20" rx="2" fill="#a855f7" opacity="0.25"/>'
+'<rect x="286" y="170" width="8" height="15" rx="2" fill="#06b6d4" opacity="0.2"/>'
+'<path d="M370 135 L385 260" stroke="url(#eg)" stroke-width="6" stroke-linecap="round" opacity="0.45"/>'
+'<path d="M355 120 Q370 110, 395 115" stroke="url(#eg2)" stroke-width="6" stroke-linecap="round" opacity="0.5"/>'
+'<circle cx="395" cy="180" r="8" fill="#06b6d4" opacity="0.3"/><circle cx="395" cy="180" r="4" fill="#06b6d4" opacity="0.5"/>'
+'<path d="M100 210 Q115 195, 140 210" stroke="#a855f7" stroke-width="4" stroke-linecap="round" opacity="0.35"/>'
+'<path d="M370 210 Q385 195, 405 210" stroke="#a855f7" stroke-width="4" stroke-linecap="round" opacity="0.35"/>'
+'<text x="250" y="310" text-anchor="middle" fill="#6366f1" font-family="Outfit,sans-serif" font-size="14" font-weight="600" opacity="0.4"> E-BIKE</text>'
+'</svg>';
        w.appendChild(d);
    }
    </script>
<?php
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$base_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
?>
<meta property="og:title" content="BNI Enterprises" />
<meta property="og:description" content="Welcome to BNI Enterprises" />
<meta property="og:image" content="<?= $base_url ?>/logo.png" />
<meta property="og:url" content="<?= sanitize($canonical_url) ?>" />
<meta property="og:type" content="website" />
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="BNI Enterprises" />
<meta name="twitter:image" content="<?= $base_url ?>/logo.png" />
</head>
<body>
    <?php if (!empty($_GET['msg']) || !empty($_GET['err'])): ?>
    <div id="toastMsg" class="glass" style="position:fixed; top:90px; right:20px; z-index:99999; padding:16px 24px; border-left: 4px solid <?= !empty($_GET['err']) ? '#f43f5e' : '#25d366' ?>; animation: slideIn 0.5s forwards; box-shadow: 0 15px 30px rgba(0,0,0,0.5);">
        <div style="font-weight:900; margin-bottom:5px; font-size:1.1rem; color: <?= !empty($_GET['err']) ? '#f43f5e' : '#25d366' ?>;">
            <?= !empty($_GET['err']) ? '<i class="fas fa-exclamation-circle"></i> ERROR' : '<i class="fas fa-check-circle"></i> SUCCESS' ?>
        </div>
        <div style="font-size:0.95rem; color: #fff; max-width:300px; line-height:1.4;"><?= sanitize($_GET['msg'] ?? $_GET['err']) ?></div>
        <span style="position:absolute; top:12px; right:15px; cursor:pointer; color:rgba(255,255,255,0.5); font-size:1.2rem; transition:0.3s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.5)'" onclick="this.parentElement.remove()">&times;</span>
    </div>
    <style>
    @keyframes slideIn { 0% { transform: translateX(120%); opacity: 0; } 100% { transform: translateX(0); opacity: 1; } }
    .socials a {
        text-decoration: unset;
    }
    </style>
    <script>
        setTimeout(() => { 
            const t = document.getElementById('toastMsg'); 
            if(t) { 
                t.style.opacity='0'; 
                t.style.transform='translateX(120%)'; 
                t.style.transition='all 0.5s ease-in-out'; 
                setTimeout(()=>t.remove(), 500); 
            } 
        }, 6000);
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('msg') || urlParams.has('err')) {
            urlParams.delete('msg');
            urlParams.delete('err');
            const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
            window.history.replaceState(null, '', newUrl);
        }
    </script>
    <?php endif; ?>
    <div id="preloader">
        <div class="loader-ring"></div>
        <div class="logo-text">LOADING EXPERIENCE</div>
    </div>
    <canvas id="bg-canvas"></canvas>
    <nav>
        <a href="<?= sanitize($self_page) ?>" class="logo-wrap">
            <img src="logo.png" alt="Logo" class="logo-img" onerror="this.style.display='none'">
            <span class="logo-text"><?= sanitize($company_name) ?></span>
        </a>
        <ul class="nav-links">
            <li><a href="<?= sanitize($self_page) ?>?view=home">Home</a></li>
            <li><a href="<?= sanitize($self_page) ?>?view=bikes">INVENTORY</a></li>
            <li><a href="#vision">VISION</a></li>
            <li><a href="#gallery">GALLERY</a></li>
        </ul>
        <a href="<?= sanitize($self_page) ?>?view=bikes" class="btn btn-main" style="padding: 10px 25px; font-size: 0.75rem;">EXPLORE</a>
    </nav>
    <?php if ($view === 'home'): ?>
        <section class="hero container animate__animated animate__fadeIn">
            <h1 class="hero-title animate__animated animate__zoomIn">
                <?= str_replace(['Electric Bikes', 'Generation'], ['<span>Electric Bikes</span>', '<span>Generation</span>'], sanitize($hero_title)) ?>
            </h1>
            <p class="hero-sub animate__animated animate__fadeInUp animate__delay-1s"><?= sanitize($hero_sub) ?></p>
            <div class="cta-group animate__animated animate__fadeInUp animate__delay-2s">
                <a href="<?= sanitize($self_page) ?>?view=bikes" class="btn btn-main">View Collection <i class="fas fa-arrow-right"></i></a>
                <a href="#vision" class="btn btn-outline">Our Philosophy</a>
            </div>
            <div style="position:absolute; bottom:30px; left:50%; transform:translateX(-50%); opacity:0.5;" class="animate__animated animate__bounce animate__infinite">
                <i class="fas fa-chevron-down"></i>
            </div>
        </section>
        <div class="container">
            <div class="stats glass">
                <div class="stat-item">
                    <div class="stat-val" data-target="5000">0</div>
                    <div class="stat-lab">Riders Joined</div>
                </div>
                <div class="stat-item">
                    <div class="stat-val" data-target="99.9">0</div>
                    <div class="stat-lab">Premium Models</div>
                </div>
                <div class="stat-item">
                    <div class="stat-val" data-target="99.9">0</div>
                    <div class="stat-lab">Eco-Impact %</div>
                </div>
                <div class="stat-item">
                    <div class="stat-val" data-target="99.9">0</div>
                    <div class="stat-lab">Support HRs</div>
                </div>
            </div>
        </div>
        <section id="vision" class="container">
            <h2 class="sec-title" data-text="PHILOSOPHY">OUR PHILOSOPHY</h2>
            <div class="bento">
                <div class="glass bento-card">
                    <i class="fas fa-eye" style="font-size:3rem; color:var(--primary); margin-bottom:20px;"></i>
                    <h3 style="font-size:2rem; margin-bottom:15px;">Visionary Mobility</h3>
                    <p style="font-size:1.05rem; color:var(--text-dim); margin-bottom:14px;"><?= sanitize(get_setting('vision_statement') ?? 'Leading the charge into a sustainable, electrified future.') ?></p>
                    <p style="font-size:0.95rem; color:var(--text-dim);">Our vision is to make electric mobility practical for every household by combining dependable technology, clean energy, and rider-first service.</p>
                </div>
                <div class="glass bento-card" style="text-align:center;">
                    <div style="font-size:3.2rem; font-weight:900; opacity:0.18;"><i class="fas fa-bolt"></i></div>
                    <h4>Pure Power</h4>
                    <p style="font-size:0.85rem; color:var(--text-dim);">Built for responsive acceleration, smooth control, and confidence on daily commutes.</p>
                </div>
                <div class="glass bento-card" style="text-align:center;">
                    <div style="font-size:3.2rem; font-weight:900; opacity:0.18;"><i class="fas fa-earth-asia"></i></div>
                    <h4>Eco First</h4>
                    <p style="font-size:0.85rem; color:var(--text-dim);">Lower emissions and lower running cost, helping riders and cities move toward a cleaner future.</p>
                </div>
                <div class="glass bento-card">
                    <h3 style="font-size:1.8rem; margin-bottom:15px;">Our Daily Mission</h3>
                    <p style="color:var(--text-dim); margin-bottom:12px;"><?= sanitize(get_setting('mission_statement') ?? 'Delivering excellence and innovation in every ride we offer.') ?></p>
                    <p style="color:var(--text-dim); font-size:0.95rem;">We focus on honest guidance, quality products, and reliable after-sales support from first inquiry to long-term ownership.</p>
                </div>
                <div class="glass bento-card">
                    <h3 style="font-size:1.8rem; margin-bottom:15px;">About Us</h3>
                    <p style="color:var(--text-dim); margin-bottom:12px;">We are an electric mobility team dedicated to helping customers choose the right bike for their real-world needs.</p>
                    <p style="color:var(--text-dim); font-size:0.95rem;">Our approach is simple: understand your use case, recommend the right model, and stay available with dependable support after your purchase.</p>
                </div>
            </div>
        </section>
        <section class="container">
            <h2 class="sec-title" data-text="COLLECTION">ELITE SELECTION</h2>
            <div class="bike-grid">
                <?php
                $top_models = $conn->query("SELECT m.*, 
                    COUNT(CASE WHEN b.status='sold' THEN 1 END) as sales_cnt,
                    COUNT(CASE WHEN b.status='in_stock' THEN 1 END) as stock_cnt,
                    MAX(b.created_at) as newest_date
                    FROM models m 
                    LEFT JOIN bikes b ON m.id = b.model_id 
                    GROUP BY m.id 
                    ORDER BY sales_cnt DESC 
                    LIMIT 4");
                $elite_rank = 0;
                while ($m = $top_models->fetch_assoc()):
                    $avail = $conn->query("SELECT * FROM bikes WHERE model_id={$m['id']} AND status='in_stock' LIMIT 1")->fetch_assoc();
                    if (!$avail)
                        continue;
                    $elite_rank++;
                    $days_since = $m['newest_date'] ? floor((time() - strtotime($m['newest_date'])) / 86400) : 999;
                    if ($elite_rank === 1 && $m['sales_cnt'] > 0) {
                        $badge_class = 'badge-bestseller';
                        $badge_icon = 'fa-crown';
                        $badge_text = 'BEST SELLER · ' . $m['sales_cnt'] . ' Sold';
                    } elseif ($days_since <= 30) {
                        $badge_class = 'badge-newarrival';
                        $badge_icon = 'fa-sparkles';
                        $badge_text = 'NEW ARRIVAL';
                    } elseif ($m['stock_cnt'] <= 2 && $m['stock_cnt'] > 0) {
                        $badge_class = 'badge-lowstock';
                        $badge_icon = 'fa-fire';
                        $badge_text = 'LOW STOCK · Only ' . $m['stock_cnt'] . ' Left';
                    } elseif ($m['sales_cnt'] >= 2) {
                        $badge_class = 'badge-popular';
                        $badge_icon = 'fa-chart-line';
                        $badge_text = 'POPULAR · ' . $m['sales_cnt'] . ' Sold';
                    } else {
                        $badge_class = 'badge-default';
                        $badge_icon = 'fa-bolt';
                        $badge_text = 'IN STOCK';
                    }
                    ?>
                <div class="glass bike-card" data-tilt>
                    <div class="bike-status <?= $badge_class ?>"><i class="fas <?= $badge_icon ?>"></i> <?= $badge_text ?></div>
                    <a class="bike-link" href="<?= sanitize($self_page) ?>?view=bike&id=<?= (int) $avail['id'] ?>">
                        <div class="bike-img">
                            <?php $primary_img = $avail['image'] ?: $m['image']; ?>
                            <img src="<?= sanitize($primary_img ?: 'x') ?>" alt="<?= sanitize($m['model_name']) ?>" onerror="imageFallback(this, '<?= sanitize($m['image']) ?>')">
                        </div>
                        <div class="bike-title" style="line-height:1.2; padding-bottom:5px;"><?= sanitize($m['model_name']) ?> <span style="display:block; font-size:1rem; color:var(--text-dim); font-weight:600; margin-top:4px;"><?= sanitize($avail['chassis_number']) ?></span></div>
                    </a>
                    <div class="bike-features">
                        <div class="feat-item"><i class="fas fa-bolt"></i> <?= sanitize($m['category']) ?></div>
                        <div class="feat-item"><i class="fas fa-palette"></i> <?= sanitize($avail['color']) ?></div>
                        <div class="feat-item"><i class="fas fa-tachometer-alt"></i> <?= sanitize(format_speed($m['top_speed'])) ?></div>
                        <div class="feat-item"><i class="fas fa-battery-full"></i> <?= sanitize(format_range($m['max_range'])) ?></div>
                    </div>
                    <div class="price-request"><i class="fab fa-whatsapp"></i> PRICE ON REQUEST</div>
                    <a href="https://wa.me/<?= $wa_number ?>?text=I'm interested in the <?= urlencode($m['model_name']) ?> (Chassis: <?= urlencode($avail['chassis_number']) ?>)" class="wa-action">INQUIRE ON WHATSAPP</a>
                </div>
                <?php endwhile; ?>
            </div>
            <div style="text-align:center; margin-top:50px;">
                <a href="<?= sanitize($self_page) ?>?view=bikes" class="btn btn-outline">Explore Full Fleet <i class="fas fa-chevron-right"></i></a>
            </div>
        </section>
        <section class="container">
            <h2 class="sec-title" data-text="STEPS">YOUR JOURNEY</h2>
            <div class="steps">
                <div class="glass step-card">
                    <div class="step-num">01</div>
                    <i class="fas fa-search" style="font-size:2rem; color:var(--primary); margin-bottom:20px;"></i>
                    <h4>Select Model</h4>
                    <p style="color:var(--text-dim); font-size:0.9rem;">Browse our premium inventory and find the bike that matches your spirit.</p>
                </div>
                <div class="glass step-card">
                    <div class="step-num">02</div>
                    <i class="fab fa-whatsapp" style="font-size:2rem; color:#25d366; margin-bottom:20px;"></i>
                    <h4>Connect</h4>
                    <p style="color:var(--text-dim); font-size:0.9rem;">Start a chat with our experts for pricing, customization, and details.</p>
                </div>
                <div class="glass step-card">
                    <div class="step-num">03</div>
                    <i class="fas fa-file-invoice" style="font-size:2rem; color:var(--secondary); margin-bottom:20px;"></i>
                    <h4>Finalize</h4>
                    <p style="color:var(--text-dim); font-size:0.9rem;">Receive your professional quote and choose your payment plan.</p>
                </div>
                <div class="glass step-card">
                    <div class="step-num">04</div>
                    <i class="fas fa-key" style="font-size:2rem; color:var(--accent); margin-bottom:20px;"></i>
                    <h4>Own</h4>
                    <p style="color:var(--text-dim); font-size:0.9rem;">Collect your keys and ride into the electrified future.</p>
                </div>
            </div>
        </section>
        <section id="gallery" class="container">
            <h2 class="sec-title" data-text="VISUALS">VISUAL MOSAIC</h2>
            <div class="gallery-grid">
                <?php
                $gallery = $conn->query('SELECT * FROM gallery ORDER BY sort_order ASC');
                while ($g = $gallery->fetch_assoc()):
                    ?>
                <a href="<?= $g['image'] ?>" class="glightbox gallery-item"
                   data-title="<?= sanitize($g['title']) ?>"
                   data-description="<?= sanitize($g['description']) ?>">
                    <img src="<?= $g['image'] ?>" alt="<?= sanitize($g['title']) ?>" onerror="this.closest('a').remove();">
                    <div class="gallery-info">
                        <h4><?= sanitize($g['title']) ?></h4>
                        <p><?= sanitize($g['description']) ?></p>
                    </div>
                </a>
                <?php endwhile; ?>
            </div>
        </section>
        <section class="container">
            <h2 class="sec-title" data-text="LEADERS">THE VISIONARIES</h2>
            <div class="leaders-grid">
                <?php
                $leaders = $conn->query('SELECT * FROM leadership ORDER BY sort_order ASC');
                while ($l = $leaders->fetch_assoc()):
                    ?>
                <div class="glass leader-card">
                    <div class="leader-avatar-wrap">
                        <img src="<?= $l['image'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($l['name']) . '&background=6366f1&color=fff&size=200' ?>" class="leader-img" alt="<?= sanitize($l['name']) ?>">
                    </div>
                    <div class="leader-name"><?= sanitize($l['name']) ?></div>
                    <div class="leader-position"><?= strtoupper(sanitize($l['position'])) ?></div>
                    <div class="leader-divider"></div>
                    <p class="leader-quote"><?= sanitize($l['message']) ?></p>
                </div>
                <?php endwhile; ?>
            </div>
        </section>
    <?php elseif ($view === 'bikes'): ?>
        <section class="container" style="padding-top:120px;">
            <h2 class="sec-title" data-text="FLEET">ACTIVE INVENTORY</h2>
            <div class="glass" style="margin-bottom:40px; padding:30px;">
                <form action="<?= sanitize($self_page) ?>" method="GET" style="display:flex; gap:15px; flex-wrap:wrap; align-items:center;">
                    <input type="hidden" name="view" value="bikes">
                    <div style="flex:1; min-width:200px;">
                        <input type="text" name="search" placeholder="Search Chassis or Model..." value="<?= sanitize($_GET['search'] ?? '') ?>" style="margin-bottom:0; width:100%;">
                    </div>
                    <div style="flex:1; min-width:200px; display:none;">
                        <select name="category" style="margin-bottom:0; width:100%; cursor:pointer; background-image: url('data:image/svg+xml;utf8,<svg fill=\"white\" height=\"24\" viewBox=\"0 0 24 24\" width=\"24\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M7 10l5 5 5-5z\"/></svg>'); background-repeat: no-repeat; background-position-x: 95%; background-position-y: center;">
                            <option value="">ALL CATEGORIES</option>
                            <?php
                            $cats = $conn->query('SELECT DISTINCT category FROM models WHERE category IS NOT NULL AND category != ""');
                            while ($c = $cats->fetch_assoc()):
                                ?>
                            <option value="<?= $c['category'] ?>" <?= ($_GET['category'] ?? '') == $c['category'] ? 'selected' : '' ?>><?= $c['category'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div style="flex:1; min-width:200px;">
                        <select name="model_id" style="margin-bottom:0; width:100%; cursor:pointer; background-image: url('data:image/svg+xml;utf8,<svg fill=\"white\" height=\"24\" viewBox=\"0 0 24 24\" width=\"24\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M7 10l5 5 5-5z\"/></svg>'); background-repeat: no-repeat; background-position-x: 95%; background-position-y: center;">
                            <option value="">ALL MODELS</option>
                            <?php
                            $mods = $conn->query('SELECT id, model_name FROM models ORDER BY model_name ASC');
                            while ($m = $mods->fetch_assoc()):
                                ?>
                            <option value="<?= $m['id'] ?>" <?= ($_GET['model_id'] ?? '') == $m['id'] ? 'selected' : '' ?>><?= sanitize($m['model_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div style="flex:1; min-width:150px;">
                        <select name="stock_status" style="margin-bottom:0; width:100%; cursor:pointer; background-image: url('data:image/svg+xml;utf8,<svg fill=\"white\" height=\"24\" viewBox=\"0 0 24 24\" width=\"24\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M7 10l5 5 5-5z\"/></svg>'); background-repeat: no-repeat; background-position-x: 95%; background-position-y: center;">
                            <option value="available" <?= ($_GET['stock_status'] ?? 'all') == 'available' ? 'selected' : '' ?>>Available Only</option>
                            <option value="all" <?= ($_GET['stock_status'] ?? 'all') == 'all' ? 'selected' : '' ?>>Show Sold Also</option>
                        </select>
                    </div>
                    <div style="display:flex; gap:10px; flex:1; min-width:200px;">
                        <button type="submit" class="btn btn-main" style="flex:1; justify-content:center;">FILTER</button>
                        <a href="<?= sanitize($self_page) ?>?view=bikes" class="btn btn-outline" style="flex:1; justify-content:center;">RESET</a>
                    </div>
                </form>
            </div>
            <div class="bike-grid">
                <?php
                $per_page = 10;
                $page_num = max(1, (int) ($_GET['pg'] ?? 1));
                $offset = ($page_num - 1) * $per_page;
                $where_p = ['1=1'];
                if (($_GET['stock_status'] ?? 'all') === 'available') {
                    $where_p[] = "b.status IN ('in_stock', 'returned')";
                }
                if (!empty($_GET['model_id'])) {
                    $mid = (int) $_GET['model_id'];
                    $where_p[] = "b.model_id = $mid";
                }
                if (!empty($_GET['search'])) {
                    $s = mysqli_real_escape_string($conn, $_GET['search']);
                    $where_p[] = "(m.model_name LIKE '%$s%' OR b.chassis_number LIKE '%$s%')";
                }
                if (!empty($_GET['category'])) {
                    $c = mysqli_real_escape_string($conn, $_GET['category']);
                    $where_p[] = "m.category = '$c'";
                }
                $where = implode(' AND ', $where_p);
                $all_bikes = $conn->query("SELECT b.*, m.model_name, m.category, m.image as model_image, m.top_speed, m.max_range 
                    FROM bikes b JOIN models m ON b.model_id = m.id WHERE $where ORDER BY b.status IN ('in_stock', 'returned') DESC, b.created_at DESC LIMIT $offset, $per_page");
                $total_cnt = $conn->query("SELECT COUNT(*) FROM bikes b JOIN models m ON b.model_id = m.id WHERE $where")->fetch_row()[0];
                $total_pages = ceil($total_cnt / $per_page);
                $badge_data = [];
                $bd_q = $conn->query("SELECT m.id, COUNT(CASE WHEN b.status='sold' THEN 1 END) as sold_cnt, COUNT(CASE WHEN b.status='in_stock' THEN 1 END) as stk_cnt, MAX(b.created_at) as newest FROM models m LEFT JOIN bikes b ON m.id=b.model_id GROUP BY m.id ORDER BY sold_cnt DESC");
                $bd_rank = 0;
                while ($bd = $bd_q->fetch_assoc()) {
                    $bd_rank++;
                    $badge_data[$bd['id']] = array_merge($bd, ['rank' => $bd_rank]);
                }
                if ($all_bikes->num_rows > 0):
                    while ($bike = $all_bikes->fetch_assoc()):
                        $img = $bike['image'] ?: $bike['model_image'];
                        $bd = $badge_data[$bike['model_id']] ?? null;
                        $b_days = $bike['created_at'] ? floor((time() - strtotime($bike['created_at'])) / 86400) : 999;
                        if (!in_array($bike['status'], ['in_stock', 'returned'])) {
                            $b_cls = 'badge-lowstock';
                            $b_ico = 'fa-ban';
                            $b_txt = strtoupper(str_replace('_', ' ', $bike['status']));
                        } elseif ($bd && $bd['rank'] === 1 && $bd['sold_cnt'] > 0) {
                            $b_cls = 'badge-bestseller';
                            $b_ico = 'fa-crown';
                            $b_txt = 'BEST SELLER';
                        } elseif ($b_days <= 30) {
                            $b_cls = 'badge-newarrival';
                            $b_ico = 'fa-sparkles';
                            $b_txt = 'NEW ARRIVAL';
                        } elseif ($bd && $bd['stk_cnt'] <= 2) {
                            $b_cls = 'badge-lowstock';
                            $b_ico = 'fa-fire';
                            $b_txt = 'LOW STOCK';
                        } elseif ($bd && $bd['sold_cnt'] >= 2) {
                            $b_cls = 'badge-popular';
                            $b_ico = 'fa-chart-line';
                            $b_txt = 'POPULAR';
                        } else {
                            $b_cls = 'badge-default';
                            $b_ico = 'fa-bolt';
                            $b_txt = 'AVAILABLE';
                        }
                        ?>
                <div class="glass bike-card" data-tilt>
                    <div class="bike-status <?= $b_cls ?>"><i class="fas <?= $b_ico ?>"></i> <?= $b_txt ?></div>
                    <a class="bike-link" href="<?= sanitize($self_page) ?>?view=bike&id=<?= (int) $bike['id'] ?>">
                        <?php $primary_img = $bike['image'] ?: $bike['model_image']; ?>
                        <div class="bike-img">
                            <img src="<?= sanitize($primary_img ?: 'x') ?>" alt="<?= sanitize($bike['model_name']) ?>" onerror="imageFallback(this, '<?= sanitize($bike['model_image']) ?>')">
                        </div>
                        <div class="bike-title" style="line-height:1.2; padding-bottom:5px;"><?= sanitize($bike['model_name']) ?> <span style="display:block; font-size:1rem; color:var(--text-dim); font-weight:600; margin-top:4px;"><?= sanitize($bike['chassis_number']) ?></span></div>
                    </a>
                    <div class="bike-features">
                        <div class="feat-item"><i class="fas fa-fingerprint"></i> <?= sanitize($bike['chassis_number']) ?></div>
                        <div class="feat-item"><i class="fas fa-palette"></i> <?= sanitize($bike['color']) ?></div>
                        <div class="feat-item"><i class="fas fa-tachometer-alt"></i> <?= sanitize(format_speed($bike['top_speed'])) ?></div>
                        <div class="feat-item"><i class="fas fa-battery-full"></i> <?= sanitize(format_range($bike['max_range'])) ?></div>
                    </div>
                    <div style="display:flex; gap:12px;">
                        <a href="https://wa.me/<?= $wa_number ?>?text=Inquiry for <?= urlencode($bike['model_name']) ?> (Chassis: <?= urlencode($bike['chassis_number']) ?>)" class="wa-action" style="flex:1;">INQUIRE</a>
                        <?php if (in_array($bike['status'], ['in_stock', 'returned'])): ?>
                        <button class="btn btn-outline" onclick="openQuoteModal(<?= $bike['id'] ?>, '<?= sanitize($bike['model_name'] . ' - ' . $bike['chassis_number']) ?>')">QUOTE</button>
                        <?php else: ?>
                        <button class="btn btn-outline" onclick="openRequestModal('<?= sanitize($bike['model_name'] . ' - ' . $bike['chassis_number']) ?>')">REQUEST</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile;
                else: ?>
                <div class="glass bento-card" style="text-align:center; padding:100px;">
                    <i class="fas fa-search-minus" style="font-size:4rem; color:var(--text-dim); margin-bottom:25px;"></i>
                    <h3>MODEL NOT FOUND</h3>
                    <p style="margin-bottom:30px;">We can source your specific requirements. Send us a request!</p>
                    <button class="btn btn-main" onclick="openRequestModal()">SEND SPECIFIC REQUEST</button>
                </div>
                <?php endif; ?>
            </div>
            <?php if ($total_pages > 1): ?>
            <div style="display:flex; justify-content:center; gap:10px; margin-top:60px;">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="<?= sanitize($self_page) ?>?view=bikes&pg=<?= $i ?>&search=<?= urlencode($_GET['search'] ?? '') ?>&category=<?= urlencode($_GET['category'] ?? '') ?>&model_id=<?= urlencode($_GET['model_id'] ?? '') ?>&stock_status=<?= urlencode($_GET['stock_status'] ?? 'all') ?>" class="btn <?= $page_num == $i ? 'btn-main' : 'btn-outline' ?>" style="padding:12px 22px;"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </section>
    <?php elseif ($view === 'bike'): ?>
        <section class="container" style="padding-top:120px;">
            <?php if ($is_bike_detail && $bike_detail): ?>
                <div class="detail-wrap">
                    <div style="margin-bottom:20px;">
                        <a href="<?= sanitize($self_page) ?>?view=bikes" class="btn btn-outline" style="padding:10px 18px;"><i class="fas fa-arrow-left"></i> BACK TO INVENTORY</a>
                    </div>
                    <div class="detail-grid">
                        <div class="glass" style="padding:14px;">
                            <?php $primary_img = $bike_detail['bike_image'] ?: $bike_detail['model_image']; ?>
                            <img class="detail-hero-img" src="<?= sanitize($primary_img ?: 'x') ?>" alt="<?= sanitize($bike_detail['model_name']) ?>" onerror="imageFallback(this, '<?= sanitize($bike_detail['model_image']) ?>')">
                        </div>
                        <div class="glass detail-panel">
                            <div class="bike-status badge-default" style="position:static; display:inline-flex; margin-bottom:14px;">
                                <i class="fas fa-bolt"></i> <?= strtoupper(in_array($bike_detail['status'], ['in_stock', 'returned']) ? 'AVAILABLE' : sanitize($bike_detail['status'])) ?>
                            </div>
                            <h1 style="font-size:2.2rem; line-height:1.2; margin-bottom:8px;"><?= sanitize($bike_detail['model_name']) ?> <span style="display:block; font-size:1.2rem; color:var(--text-dim); font-family:'Outfit', sans-serif;"><?= sanitize($bike_detail['chassis_number']) ?></span></h1>
                            <p style="color:var(--text-dim); margin-bottom:6px;"><?= sanitize($bike_detail['category']) ?></p>
                            <p style="color:var(--text-dim); font-size:0.95rem;">Added: <?= $bike_detail['created_at'] ? date('d M Y', strtotime($bike_detail['created_at'])) : 'N/A' ?></p>
                            <div class="detail-kv">
                                <div class="feat-item"><i class="fas fa-fingerprint"></i> Chassis: <?= sanitize($bike_detail['chassis_number']) ?></div>
                                <div class="feat-item"><i class="fas fa-palette"></i> Color: <?= sanitize($bike_detail['color'] ?: 'N/A') ?></div>
                                <div class="feat-item"><i class="fas fa-tachometer-alt"></i> Speed: <?= sanitize(format_speed($bike_detail['top_speed'])) ?></div>
                                <div class="feat-item"><i class="fas fa-battery-full"></i> Range: <?= sanitize(format_range($bike_detail['max_range'])) ?></div>
                                <div class="feat-item"><i class="fas fa-shield-alt"></i> Warranty Included</div>
                                <div class="feat-item"><i class="fas fa-headset"></i> 24/7 Support</div>
                            </div>
                            <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:12px;">
                                <a href="https://wa.me/<?= $wa_number ?>?text=Inquiry for <?= urlencode($bike_detail['model_name']) ?> (Chassis: <?= urlencode($bike_detail['chassis_number']) ?>)" class="wa-action" style="flex:1;">INQUIRE</a>
                                <?php if (in_array($bike_detail['status'], ['in_stock', 'returned'])): ?>
                                <button class="btn btn-outline" style="flex:1; justify-content:center;" onclick="openQuoteModal(<?= (int) $bike_detail['id'] ?>, '<?= sanitize($bike_detail['model_name'] . ' - ' . $bike_detail['chassis_number']) ?>')">QUOTE</button>
                                <?php else: ?>
                                <button class="btn btn-outline" style="flex:1; justify-content:center;" onclick="openRequestModal('<?= sanitize($bike_detail['model_name'] . ' - ' . $bike_detail['chassis_number']) ?>')">REQUEST THIS BIKE</button>
                                <?php endif; ?>
                            </div>
                            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                                <button class="btn btn-outline" style="flex:1; justify-content:center;" onclick="navigator.clipboard.writeText('<?= sanitize($canonical_url) ?>'); this.innerHTML='<i class=&quot;fas fa-check&quot;></i> LINK COPIED';">SHARE LINK</button>
                                <a href="<?= sanitize($self_page) ?>?view=bikes" class="btn btn-main" style="flex:1; justify-content:center;">MORE BIKES</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="glass bento-card" style="text-align:center; padding:100px;">
                    <i class="fas fa-circle-exclamation" style="font-size:4rem; color:var(--text-dim); margin-bottom:25px;"></i>
                    <h3>BIKE NOT FOUND</h3>
                    <p style="margin-bottom:30px;">This bike detail page is unavailable or no longer public.</p>
                    <a href="<?= sanitize($self_page) ?>?view=bikes" class="btn btn-main">GO TO INVENTORY</a>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    <?php
    $map_data = get_setting('company_map_iframe');
    if ($map_data):
        if (strpos($map_data, '<iframe') !== false) {
            preg_match('/src="([^"]+)"/', $map_data, $match);
            $map_url = $match[1] ?? '';
        } else {
            $map_url = $map_data;
        }
        if ($map_url):
            ?>
    <section class="footer-map-section">
        <div class="container" style="height:350px;">
            <iframe src="<?= $map_url ?>" width="100%" height="100%" style="border:0; border-radius:30px; box-shadow: 0 20px 60px rgba(0,0,0,0.4);" allowfullscreen="" loading="lazy"></iframe>
        </div>
    </section>
    <?php endif;
    endif; ?>
    <footer id="contact">
        <div class="footer-wave-transition">
            <svg class="wave-1" viewBox="0 0 1440 320" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <defs><linearGradient id="g1" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#6366f1"/><stop offset="100%" stop-color="#a855f7"/></linearGradient></defs>
                <path fill="url(#g1)" d="M0,128L80,144C160,160,320,192,480,181.3C640,171,800,117,960,112C1120,107,1280,149,1360,170.7L1440,192L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"></path>
                <path fill="url(#g1)" transform="translate(1440, 0)" d="M0,128L80,144C160,160,320,192,480,181.3C640,171,800,117,960,112C1120,107,1280,149,1360,170.7L1440,192L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"></path>
            </svg>
            <svg class="wave-2" viewBox="0 0 1440 320" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <defs><linearGradient id="g2" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#a855f7"/><stop offset="100%" stop-color="#ec4899"/></linearGradient></defs>
                <path fill="url(#g2)" d="M0,192L60,176C120,160,240,128,360,138.7C480,149,600,203,720,213.3C840,224,960,192,1080,165.3C1200,139,1320,117,1380,106.7L1440,96L1440,320L1380,320C1320,320,1200,320,1080,320C960,320,840,320,720,320C600,320,480,320,360,320C240,320,120,320,60,320L0,320Z"></path>
                <path fill="url(#g2)" transform="translate(1440, 0)" d="M0,192L60,176C120,160,240,128,360,138.7C480,149,600,203,720,213.3C840,224,960,192,1080,165.3C1200,139,1320,117,1380,106.7L1440,96L1440,320L1380,320C1320,320,1200,320,1080,320C960,320,840,320,720,320C600,320,480,320,360,320C240,320,120,320,60,320L0,320Z"></path>
            </svg>
            <svg class="wave-3" viewBox="0 0 1440 320" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <defs><linearGradient id="g3" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#06b6d4"/><stop offset="100%" stop-color="#6366f1"/></linearGradient></defs>
                <path fill="url(#g3)" d="M0,96L80,117.3C160,139,320,181,480,176C640,171,800,117,960,117.3C1120,117,1280,171,1360,197.3L1440,224L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"></path>
                <path fill="url(#g3)" transform="translate(1440, 0)" d="M0,96L80,117.3C160,139,320,181,480,176C640,171,800,117,960,117.3C1120,117,1280,171,1360,197.3L1440,224L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"></path>
            </svg>
        </div>
        <div class="footer-glow"></div>
        <div class="footer-particles" id="footerParticles"></div>
        <div class="footer-content-area">
            <div class="container footer-wrap">
                <div>
                    <a href="<?= sanitize($self_page) ?>" class="logo-wrap" style="margin-bottom:25px;">
                        <img src="logo.png" alt="Logo" class="logo-img" onerror="this.style.display='none'">
                        <span class="logo-text"><?= sanitize($company_name) ?></span>
                    </a>
                    <p class="footer-desc"><?= sanitize(get_setting('mission_statement') ?? 'Redefining movement through sustainable innovation.') ?></p>
                    <div class="socials">
                        <?php if ($fb = get_setting('social_facebook')): ?><a href="<?= $fb ?>"><i class="fab fa-facebook-f"></i></a><?php endif; ?>
                        <?php if ($ig = get_setting('social_instagram')): ?><a href="<?= $ig ?>"><i class="fab fa-instagram"></i></a><?php endif; ?>
                        <?php if ($tw = get_setting('social_twitter')): ?><a href="<?= $tw ?>"><i class="fab fa-twitter"></i></a><?php endif; ?>
                    </div>
                </div>
                <div>
                    <h4 class="footer-head">QUICK LINKS</h4>
                    <ul style="list-style:none; line-height:2.8;">
                        <li><a href="<?= sanitize($self_page) ?>?view=home" class="footer-link"><i class="fas fa-home"></i> Home</a></li>
                        <li><a href="<?= sanitize($self_page) ?>?view=bikes" class="footer-link"><i class="fas fa-motorcycle"></i> Inventory</a></li>
                        <li><a href="#vision" class="footer-link"><i class="fas fa-eye"></i> Philosophy</a></li>
                        <li><a href="#" onclick="openRequestModal(); return false;" class="footer-link"><i class="fas fa-paper-plane"></i> Request Bike</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="footer-head">HEADQUARTERS</h4>
                    <ul style="list-style:none; line-height:2.8;">
                        <li><a href="#" class="footer-link"><i class="fas fa-map-marker-alt"></i> <?= sanitize(get_setting('company_address') ?? 'Dera Ghazi Khan, Punjab') ?></a></li>
                        <li><a href="mailto:<?= sanitize(get_setting('company_email') ?? '') ?>" class="footer-link"><i class="fas fa-envelope"></i> <?= sanitize(get_setting('company_email') ?? 'contact@bss.com') ?></a></li>
                        <li><a href="https://wa.me/<?= sanitize(get_setting('company_whatsapp') ?? '') ?>" class="footer-link"><i class="fab fa-whatsapp"></i> +<?= sanitize(get_setting('company_whatsapp') ?? '92000000000') ?></a></li>
                    </ul>
                </div>
            </div>
            <div class="container footer-bottom">
                &copy; <?= date('Y') ?> <?= sanitize($company_name) ?>. ALL RIGHTS RESERVED.
            </div>
        </div>
    </footer>
    <div id="requestModal" class="modal">
        <div class="modal-body">
            <span class="modal-close" onclick="closeModal('requestModal')">&times;</span>
            <h2 style="margin-bottom:25px; background:var(--grad); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">REQUEST A MODEL</h2>
            <form action="<?= sanitize($self_page) ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <label>FULL NAME</label><input type="text" name="name" required placeholder="John Doe">
                <label>WHATSAPP / PHONE</label><input type="text" name="phone" required placeholder="+92 ...">
                <label>SPECIFICATIONS</label><textarea name="bike_details" rows="4" placeholder="Year, Color, Model, Range..."></textarea>
                <label>SECURITY CHECK</label>
                <div style="display:flex; gap:15px; margin-bottom:20px;">
                    <img src="<?= $captcha_img_src ?>" alt="Captcha" style="border-radius:10px; border:1px solid var(--glass-border); height:50px;">
                    <input type="number" name="captcha" required placeholder="Answer" style="margin-bottom:0; flex:1;">
                </div>
                <button type="submit" name="request_bike" class="btn btn-main" style="width:100%;">SUBMIT REQUEST</button>
            </form>
        </div>
    </div>
    <div id="quoteModal" class="modal">
        <div class="modal-body">
            <span class="modal-close" onclick="closeModal('quoteModal')">&times;</span>
            <h2 style="margin-bottom:5px; color:var(--primary);">GET A QUOTE</h2>
            <p id="q_name" style="color:white; font-weight:700; margin-bottom:25px; font-size:1.1rem;"></p>
            <form action="<?= sanitize($self_page) ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="bike_id" id="q_id">
                <label>FULL NAME</label><input type="text" name="name" required placeholder="John Doe">
                <label>WHATSAPP #</label><input type="text" name="phone" required placeholder="+92 ...">
                <label>REQUIREMENTS</label><textarea name="details" rows="3" placeholder="Installment details, accessories..."></textarea>
                <label>SECURITY CHECK</label>
                <div style="display:flex; gap:15px; margin-bottom:20px;">
                    <img src="<?= $captcha_img_src ?>" alt="Captcha" style="border-radius:10px; border:1px solid var(--glass-border); height:50px;">
                    <input type="number" name="captcha" required placeholder="Answer" style="margin-bottom:0; flex:1;">
                </div>
                <button type="submit" name="request_quote" class="btn btn-main" style="width:100%;">REQUEST QUOTE</button>
            </form>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/glightbox/3.2.0/js/glightbox.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.0/vanilla-tilt.min.js"></script>
    <script>
        window.addEventListener('load', () => {
            const pre = document.getElementById('preloader');
            pre.style.opacity = '0';
            setTimeout(() => pre.remove(), 800);
            animateStats();
        });
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        const renderer = new THREE.WebGLRenderer({ canvas: document.getElementById('bg-canvas'), alpha: true, antialias: true });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        renderer.setSize(window.innerWidth, window.innerHeight);
        const particlesGeometry = new THREE.BufferGeometry();
        const counts = 3800;
        const posArray = new Float32Array(counts * 3);
        const basePos = new Float32Array(counts * 3);
        for (let i = 0; i < counts * 3; i++) { posArray[i] = (Math.random() - 0.5) * 24; }
        basePos.set(posArray);
        particlesGeometry.setAttribute('position', new THREE.BufferAttribute(posArray, 3));
        const material = new THREE.PointsMaterial({ size: 0.014, color: '#7c83ff', transparent: true, opacity: 0.5 });
        const particlesMesh = new THREE.Points(particlesGeometry, material);
        scene.add(particlesMesh);
        camera.position.z = 5.6;
        let mouseTX = 0, mouseTY = 0, mouseX = 0, mouseY = 0;
        let lastRawX = 0, lastRawY = 0;
        let hasMouse = false;
        let burst = 0.22; 
        let clickPulse = 0;
        let hueShift = 0;
        let blastPulse = 0;
        let gatherStrength = 0;
        let lastMoveTs = performance.now();
        const vel = new Float32Array(counts * 3);
        function triggerBlast(power = 1) {
            blastPulse = Math.min(3, blastPulse + 1.5 * power);
            clickPulse = Math.max(clickPulse, 0.5 * power);
        }
        document.addEventListener('mousemove', (e) => {
            const moved = Math.hypot(e.clientX - lastRawX, e.clientY - lastRawY);
            lastRawX = e.clientX;
            lastRawY = e.clientY;
            mouseTX = ((e.clientX / window.innerWidth) - 0.5) * 2;
            mouseTY = ((e.clientY / window.innerHeight) - 0.5) * 2;
            hasMouse = true;
            if (moved > 2.2) {
                if (gatherStrength > 0.1) triggerBlast(1 + gatherStrength * 2.5);
                lastMoveTs = performance.now();
            }
        });
        document.addEventListener('mousedown', () => {
            triggerBlast(2.5);
            lastMoveTs = performance.now();
        });
        window.addEventListener('resize', () => {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        });
        const animate = () => {
            requestAnimationFrame(animate);
            mouseX += (mouseTX - mouseX) * 0.06;
            mouseY += (mouseTY - mouseY) * 0.06;
            burst *= 0.985;
            clickPulse *= 0.9;
            blastPulse *= 0.92;
            const energy = burst + clickPulse;
            particlesMesh.rotation.y += 0.0018 + energy * 0.016;
            particlesMesh.rotation.x += 0.0009 + energy * 0.009;
            particlesMesh.rotation.y += mouseX * 0.034;
            particlesMesh.rotation.x += mouseY * 0.024;
            particlesMesh.position.x += ((mouseX * 0.42) - particlesMesh.position.x) * 0.08;
            particlesMesh.position.y += ((-mouseY * 0.34) - particlesMesh.position.y) * 0.08;
            const now = performance.now();
            const idleMs = now - lastMoveTs;
            const attractTarget = (hasMouse && idleMs > 250) ? 1 : 0;
            gatherStrength += (attractTarget - gatherStrength) * 0.15;
            const positions = particlesGeometry.attributes.position.array;
            const mx = mouseX * 6.5;
            const my = -mouseY * 5.2;
            const t = performance.now() * 0.0016;
            for (let i = 0; i < counts * 3; i += 3) {
                const bx = basePos[i];
                const by = basePos[i + 1];
                const bz = basePos[i + 2];
                const dx = mx - bx;
                const dy = my - by;
                const d2 = dx * dx + dy * dy + 0.2;
                const force = Math.min(1.9 / d2, 0.2);
                const attractForce = gatherStrength * Math.min(25.0 / d2, 3.5);
                const blastForce = blastPulse * Math.min(35.0 / d2, 4.5);
                const wobble = Math.sin((bx + by) * 0.6 + t * 2.8) * 0.06;
                vel[i] *= 0.88;
                vel[i + 1] *= 0.88;
                vel[i + 2] *= 0.88;
                vel[i] += dx * attractForce;
                vel[i + 1] += dy * attractForce;
                vel[i] -= dx * blastForce;
                vel[i + 1] -= dy * blastForce;
                vel[i + 2] += (Math.random() - 0.5) * blastForce * 0.35;
                positions[i] = bx + dx * force + vel[i] + wobble;
                positions[i + 1] = by + dy * force + vel[i + 1] + wobble;
                positions[i + 2] = bz + Math.cos((bx - by) * 0.5 + t * 3.2) * (0.04 + force * 0.22) + vel[i + 2];
            }
            particlesGeometry.attributes.position.needsUpdate = true;
            hueShift += 0.8 + Math.abs(mouseX + mouseY) * 6;
            const hue = 228 + Math.sin(hueShift * 0.01) * 16;
            material.color.setHSL(hue / 360, 0.88, 0.68);
            material.opacity = 0.38 + Math.min(energy, 0.34) + Math.min(Math.abs(mouseX) + Math.abs(mouseY), 0.2) + gatherStrength * 0.1;
            material.size = 0.012 + Math.min(energy, 0.028) + Math.min((Math.abs(mouseX) + Math.abs(mouseY)) * 0.006, 0.008) + gatherStrength * 0.006;
            renderer.render(scene, camera);
        };
        animate();
        function animateStats() {
            const stats = document.querySelectorAll('.stat-val');
            stats.forEach(stat => {
                const target = +stat.getAttribute('data-target');
                const update = () => {
                    const current = +stat.innerText;
                    const inc = target / 100;
                    if(current < target) {
                        stat.innerText = Math.ceil(current + inc);
                        setTimeout(update, 20);
                    } else { stat.innerText = target + (target > 99 ? '+' : '%'); }
                };
                update();
            });
        }
        const wooshSound = new Audio('woosh3.wav');
        wooshSound.volume = 0.5;
        wooshSound.loop = true;
        let audioCtx, analyser, source, dataArray;
        let isAudioSetup = false;
        let activeCard = null;
        let animationId;
        let lastAudioTime = 0;
        function setupAudio() {
            if (isAudioSetup) return;
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            analyser = audioCtx.createAnalyser();
            source = audioCtx.createMediaElementSource(wooshSound);
            source.connect(analyser);
            analyser.connect(audioCtx.destination);
            analyser.fftSize = 64;
            dataArray = new Uint8Array(analyser.frequencyBinCount);
            isAudioSetup = true;
        }
        function updateGlow() {
            if (!activeCard || !isAudioSetup) return;
            analyser.getByteFrequencyData(dataArray);
            let sum = 0;
            for(let i = 0; i < dataArray.length; i++) sum += dataArray[i];
            let avg = sum / dataArray.length; 
            let progress = wooshSound.duration ? (wooshSound.currentTime / wooshSound.duration) : 0;
            if (wooshSound.currentTime < lastAudioTime && lastAudioTime > 0.1) {
                triggerBlast(6.0); 
                if (activeCard) {
                    activeCard.classList.remove('lightning-blast');
                    void activeCard.offsetWidth; 
                    activeCard.classList.add('lightning-blast');
                    setTimeout(() => {
                        if (activeCard) activeCard.classList.remove('lightning-blast');
                    }, 700);
                }
            }
            lastAudioTime = wooshSound.currentTime;
            let rawVolume = avg / 255;
            let intensity = Math.pow(rawVolume, 1.2) * (1 + (progress * 3)); 
            activeCard.style.setProperty('--audio-scale', 1 + (intensity * 12)); 
            activeCard.style.setProperty('--spin-speed', Math.max(0.002, 0.25 - (intensity * 0.2)) + 's');
            activeCard.style.setProperty('--spark-speed', Math.max(0.002, 0.15 - (intensity * 0.12)) + 's');
            animationId = requestAnimationFrame(updateGlow);
        }
        document.querySelectorAll('.leader-card').forEach(card => {
            const startEffect = () => {
                if (activeCard === card) return; 
                setupAudio();
                if(audioCtx.state === 'suspended') audioCtx.resume();
                activeCard = card;
                wooshSound.currentTime = 0;
                lastAudioTime = 0;
                wooshSound.play().catch(() => {});
                updateGlow();
            };
            const stopEffect = () => {
                if(activeCard) {
                    activeCard.style.removeProperty('--audio-scale');
                    activeCard.style.removeProperty('--spin-speed');
                    activeCard.style.removeProperty('--spark-speed');
                }
                activeCard = null;
                cancelAnimationFrame(animationId);
                wooshSound.pause();
                wooshSound.currentTime = 0;
            };
            card.addEventListener('mouseenter', startEffect);
            card.addEventListener('mouseleave', stopEffect);
            card.addEventListener('touchstart', startEffect, { passive: true });
            card.addEventListener('touchend', stopEffect);
            card.addEventListener('touchcancel', stopEffect);
            card.addEventListener('contextmenu', (e) => e.preventDefault());
            card.addEventListener('click', function() {
                this.classList.remove('lightning-blast');
                void this.offsetWidth;
                this.classList.add('lightning-blast');
                const imgSrc = this.querySelector('.leader-img').src;
                const name = this.querySelector('.leader-name').innerText;
                const position = this.querySelector('.leader-position').innerText;
                setTimeout(() => {
                    this.classList.remove('lightning-blast');
                    const leaderLightbox = GLightbox({
                        elements: [{
                            href: imgSrc,
                            type: 'image',
                            title: name,
                            description: position
                        }]
                    });
                    leaderLightbox.open();
                }, 700);
            });
        });
        const lightbox = GLightbox({ selector: '.glightbox' });
        function openQuoteModal(id, name) {
            document.getElementById('q_id').value = id;
            document.getElementById('q_name').innerText = "Model: " + name;
            document.getElementById('quoteModal').style.display = 'flex';
        }
        function openRequestModal(prefill = '') { 
            if(prefill) {
                const ta = document.querySelector('#requestModal textarea[name="bike_details"]');
                if(ta) ta.value = "I am interested in requesting a bike similar to: " + prefill;
            }
            document.getElementById('requestModal').style.display = 'flex'; 
        }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }
        window.onclick = (e) => { if(e.target.classList.contains('modal')) e.target.style.display = 'none'; };
        (function() {
            const container = document.getElementById('footerParticles');
            if (!container) return;
            const colors = ['#6366f1','#a855f7','#06b6d4','#ec4899'];
            for (let i = 0; i < 20; i++) {
                const p = document.createElement('div');
                p.className = 'footer-particle';
                p.style.left = Math.random() * 100 + '%';
                p.style.bottom = Math.random() * 30 + '%';
                p.style.animationDelay = Math.random() * 6 + 's';
                p.style.animationDuration = (4 + Math.random() * 4) + 's';
                p.style.background = colors[Math.floor(Math.random() * colors.length)];
                p.style.width = p.style.height = (2 + Math.random() * 3) + 'px';
                container.appendChild(p);
            }
        })();
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if(entry.isIntersecting) entry.target.classList.add('animate__animated', 'animate__fadeInUp');
            });
        }, { threshold: 0.1 });
        document.querySelectorAll('section').forEach(s => observer.observe(s));
        let lastScrollY = window.scrollY;
        const navBar = document.querySelector('nav');
        window.addEventListener('scroll', () => {
            if (window.scrollY > lastScrollY && window.scrollY > 100) {
                navBar.classList.add('nav-hidden');
            } else {
                navBar.classList.remove('nav-hidden');
            }
            lastScrollY = window.scrollY;
        });
    </script>
</body>
</html>
