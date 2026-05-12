<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

$db_host = 'localhost';
$db_user = 'root';
$db_pass = 'root';
$db_name = 'bni_enterprises2';

function db_connect() {
    global $db_host, $db_user, $db_pass, $db_name;
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) return null;
    $conn->set_charset('utf8mb4');
    return $conn;
}

$conn = db_connect();
if (!$conn) { die("System Maintenance. Please check back later."); }

function get_setting($key) {
    global $conn;
    $stmt = $conn->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $r = $stmt->get_result();
    $row = $r->fetch_assoc();
    return $row ? $row['setting_value'] : null;
}

function sanitize($val) {
    return htmlspecialchars(strip_tags(trim($val ?? '')), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['request_bike'])) {
        $name = sanitize($_POST['name']);
        $phone = sanitize($_POST['phone']);
        $details = sanitize($_POST['bike_details']);
        $st = $conn->prepare('INSERT INTO bike_requests (customer_name, customer_phone, bike_details) VALUES (?,?,?)');
        $st->bind_param('sss', $name, $phone, $details);
        $st->execute();
        header('Location: landing.php?msg=Request Sent! Our team will contact you.');
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
        header('Location: landing.php?msg=Quote Requested! Check WhatsApp shortly.');
        exit;
    }
}

$company_name = get_setting('company_name') ?? 'BNI Enterprises';
$hero_title = get_setting('landing_hero_title') ?? 'The Next Generation of Electric Mobility';
$hero_sub = get_setting('landing_hero_subtitle') ?? 'Eco-friendly, powerful, and designed for the modern world.';
$wa_number = get_setting('company_whatsapp') ?? '';
$view = $_GET['view'] ?? 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($company_name) ?> | Future of Electric Mobility</title>
    
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

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: var(--darker); }
        ::-webkit-scrollbar-thumb { background: var(--grad); border-radius: 10px; }

        .container { width: 100%; max-width: 1300px; margin: 0 auto; padding: 0 25px; }
        section { padding: 100px 0; position: relative; }

        /* Multi-Layer Glass */
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

        /* Header / Nav */
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

        /* Hero - Cinematic */
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

        /* Section Decorations */
        .sec-title { font-size: clamp(2.5rem, 5vw, 4rem); font-weight: 900; text-align: center; margin-bottom: 60px; position: relative; }
        .sec-title::before {
            content: attr(data-text); position: absolute; top: -20px; left: 50%; transform: translateX(-50%);
            font-size: 6rem; opacity: 0.03; width: 100%; white-space: nowrap; pointer-events: none;
        }

        /* Feature Bento */
        .bento { display: grid; grid-template-columns: repeat(12, 1fr); gap: 25px; }
        .bento-card { grid-column: span 12; padding: 40px; }
        @media (min-width: 768px) {
            .bento-card:nth-child(1) { grid-column: span 8; }
            .bento-card:nth-child(2) { grid-column: span 4; }
            .bento-card:nth-child(3) { grid-column: span 4; }
            .bento-card:nth-child(4) { grid-column: span 8; }
        }

        /* Bike Grid - 2 col desktop */
        .bike-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
        }
        @media (min-width: 768px) {
            .bike-grid { grid-template-columns: repeat(2, 1fr); }
        }

        /* Bike Cards - Premium Glow */
        .bike-card {
            position: relative; overflow: hidden; padding: 15px;
        }
        .bike-img {
            width: 100%; height: 260px; border-radius: 20px; overflow: hidden; position: relative; background: #0f172a;
            display: flex; align-items: center; justify-content: center; margin-bottom: 20px;
        }
        .bike-img img { width: 100%; height: 100%; object-fit: cover; transition: 0.7s cubic-bezier(0.4, 0, 0.2, 1); }
        .bike-card:hover .bike-img img { transform: scale(1.1) rotate(1deg); filter: brightness(1.1); }
        
        .bike-status {
            position: absolute; top: 15px; left: 15px; padding: 6px 16px; border-radius: 50px;
            font-size: 0.7rem; font-weight: 900; z-index: 10; box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            text-transform: uppercase; letter-spacing: 1px;
        }
        /* Callout badge variants */
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

        /* Stats Section */
        .stats { display: flex; flex-wrap: wrap; justify-content: space-around; gap: 40px; padding: 60px 0; background: rgba(255,255,255,0.02); border-radius: 40px; }
        .stat-item { text-align: center; flex: 1; min-width: 150px; }
        .stat-val { font-size: 3.5rem; font-weight: 900; background: var(--grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-lab { font-size: 0.9rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 2px; }

        /* Steps */
        .steps { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; }
        .step-card { position: relative; padding: 50px 30px; }
        .step-num {
            position: absolute; top: 20px; right: 30px; font-size: 4rem; font-weight: 900; opacity: 0.05;
        }

        /* Gallery - Mosaic */
        .gallery-grid { columns: 2; column-gap: 25px; }
        @media (min-width: 992px) { .gallery-grid { columns: 3; } }
        .gallery-item {
            break-inside: avoid; margin-bottom: 25px; border-radius: 25px; overflow: hidden; border: 1px solid var(--glass-border); position: relative;
        }
        .gallery-item img { width: 100%; display: block; transition: 0.5s ease-in-out; }
        .gallery-item:hover img { transform: scale(1.08); filter: saturate(1.2); }
        .gallery-info {
            position: absolute; inset: 0; background: linear-gradient(to top, rgba(2,6,23,0.8), transparent);
            display: flex; flex-direction: column; justify-content: flex-end; padding: 25px; opacity: 0; transition: 0.3s;
        }
        .gallery-item:hover .gallery-info { opacity: 1; }

        .footer-map-section {
            padding: 60px 0; background: rgba(255,255,255,0.02);
        }

        footer {
            position: relative; overflow: hidden;
            background: linear-gradient(180deg, #0c1225 0%, #0f0a2e 30%, #130d35 60%, #0a0618 100%);
            padding: 0 0 0;
        }

        /* SVG wave top decoration */
        .footer-wave-area {
            width: 100%; height: 120px; position: relative; overflow: hidden;
        }
        .footer-wave-area svg {
            position: absolute; bottom: 0; left: 0; width: 200%; height: 120px; min-width: 1200px;
        }
        .wave-1 { animation: waveDrift1 7s ease-in-out infinite; }
        .wave-2 { animation: waveDrift2 9s ease-in-out infinite; }
        .wave-3 { animation: waveDrift3 11s ease-in-out infinite; }
        @keyframes waveDrift1 { 0%,100%{transform:translateX(0)} 50%{transform:translateX(-25%)} }
        @keyframes waveDrift2 { 0%,100%{transform:translateX(-10%)} 50%{transform:translateX(-35%)} }
        @keyframes waveDrift3 { 0%,100%{transform:translateX(-5%)} 50%{transform:translateX(-30%)} }

        /* Glow */
        .footer-glow {
            position: absolute; top: 80px; left: 50%; width: 800px; height: 250px;
            transform: translateX(-50%); border-radius: 50%;
            background: radial-gradient(ellipse, rgba(99,102,241,0.15), rgba(168,85,247,0.06) 50%, transparent 70%);
            pointer-events: none; z-index: 0; filter: blur(40px);
        }

        .footer-content-area {
            position: relative; z-index: 10; padding: 60px 0 40px;
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

        /* Floating particles */
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

        /* Bike placeholder SVG for broken images */
        .bike-img-placeholder {
            width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, rgba(99,102,241,0.08), rgba(168,85,247,0.05));
        }
        .bike-img-placeholder svg { width: 60%; max-width: 180px; height: auto; }

        /* Modals */
        .modal {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.9); backdrop-filter: blur(20px);
            z-index: 3000; align-items: center; justify-content: center; padding: 20px;
        }
        .modal-body {
            background: #0f172a; border: 1px solid var(--glass-border); padding: 45px; border-radius: 40px;
            width: 100%; max-width: 550px; position: relative; box-shadow: 0 0 100px var(--primary-glow);
        }
        .modal-close { position: absolute; top: 25px; right: 30px; font-size: 2rem; cursor: pointer; color: var(--text-dim); }

        input, textarea {
            width: 100%; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border);
            padding: 16px 20px; border-radius: 18px; color: white; margin-bottom: 20px; font-size: 1rem; outline: none; transition: 0.3s;
        }
        input:focus { border-color: var(--primary); background: rgba(255,255,255,0.08); }

        /* Loading */
        #preloader { position: fixed; inset: 0; background: var(--darker); z-index: 9999; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .loader-ring { width: 80px; height: 80px; border: 5px solid var(--glass-border); border-top-color: var(--primary); border-radius: 50%; animation: spin 1s infinite linear; margin-bottom: 20px; }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 768px) {
            nav { padding: 10px 20px; }
            .nav-links { display: none; }
            .hero-title { font-size: 3.5rem; }
            section { padding: 60px 0; }
            .sec-title::before { font-size: 3rem; }
        }
    </style>
    <script>
    function bikePlaceholder(img){
        var w=img.parentNode; img.remove();
        var d=document.createElement('div'); d.className='bike-img-placeholder';
        d.innerHTML='<svg viewBox="0 0 640 480" fill="none" xmlns="http://www.w3.org/2000/svg"><g opacity="0.35"><circle cx="140" cy="340" r="90" stroke="#6366f1" stroke-width="12"/><circle cx="140" cy="340" r="40" stroke="#a855f7" stroke-width="8"/><circle cx="500" cy="340" r="90" stroke="#6366f1" stroke-width="12"/><circle cx="500" cy="340" r="40" stroke="#a855f7" stroke-width="8"/><path d="M140 340 L260 160 L360 160 L420 80 L480 80" stroke="#6366f1" stroke-width="12" stroke-linecap="round" stroke-linejoin="round"/><path d="M260 160 L340 340 L500 340" stroke="#6366f1" stroke-width="12" stroke-linecap="round" stroke-linejoin="round"/><path d="M340 340 L400 200 L480 80" stroke="#a855f7" stroke-width="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M300 140 L440 140 L470 100" stroke="#06b6d4" stroke-width="8" stroke-linecap="round"/><circle cx="260" cy="160" r="8" fill="#6366f1"/></g></svg>';
        w.appendChild(d);
    }
    </script>
</head>
<body>

    <div id="preloader">
        <div class="loader-ring"></div>
        <div class="logo-text">LOADING EXPERIENCE</div>
    </div>

    <canvas id="bg-canvas"></canvas>

    <nav>
        <a href="landing.php" class="logo-wrap">
            <img src="logo.png" alt="Logo" class="logo-img" onerror="this.style.display='none'">
            <span class="logo-text"><?= sanitize($company_name) ?></span>
        </a>
        <ul class="nav-links">
            <li><a href="landing.php?view=home">SURFACE</a></li>
            <li><a href="landing.php?view=bikes">INVENTORY</a></li>
            <li><a href="#vision">VISION</a></li>
            <li><a href="#gallery">GALLERY</a></li>
        </ul>
        <a href="landing.php?view=bikes" class="btn btn-main" style="padding: 10px 25px; font-size: 0.75rem;">EXPLORE</a>
    </nav>

    <?php if ($view === 'home'): ?>
        <!-- Hero Section -->
        <section class="hero container animate__animated animate__fadeIn">
            <h1 class="hero-title animate__animated animate__zoomIn">
                <?= str_replace(['Electric Bikes', 'Generation'], ['<span>Electric Bikes</span>', '<span>Generation</span>'], sanitize($hero_title)) ?>
            </h1>
            <p class="hero-sub animate__animated animate__fadeInUp animate__delay-1s"><?= sanitize($hero_sub) ?></p>
            <div class="cta-group animate__animated animate__fadeInUp animate__delay-2s">
                <a href="landing.php?view=bikes" class="btn btn-main">View Collection <i class="fas fa-arrow-right"></i></a>
                <a href="#vision" class="btn btn-outline">Our Philosophy</a>
            </div>
            
            <div style="position:absolute; bottom:30px; left:50%; transform:translateX(-50%); opacity:0.5;" class="animate__animated animate__bounce animate__infinite">
                <i class="fas fa-chevron-down"></i>
            </div>
        </section>

        <!-- Stats Section -->
        <div class="container">
            <div class="stats glass">
                <div class="stat-item">
                    <div class="stat-val" data-target="500">0</div>
                    <div class="stat-lab">Riders Joined</div>
                </div>
                <div class="stat-item">
                    <div class="stat-val" data-target="15">0</div>
                    <div class="stat-lab">Premium Models</div>
                </div>
                <div class="stat-item">
                    <div class="stat-val" data-target="100">0</div>
                    <div class="stat-lab">Eco-Impact %</div>
                </div>
                <div class="stat-item">
                    <div class="stat-val" data-target="24">0</div>
                    <div class="stat-lab">Support HRs</div>
                </div>
            </div>
        </div>

        <!-- Philosophy Bento -->
        <section id="vision" class="container">
            <h2 class="sec-title" data-text="PHILOSOPHY">OUR PHILOSOPHY</h2>
            <div class="bento">
                <div class="glass bento-card">
                    <i class="fas fa-eye" style="font-size:3rem; color:var(--primary); margin-bottom:20px;"></i>
                    <h3 style="font-size:2rem; margin-bottom:15px;">Visionary Mobility</h3>
                    <p style="font-size:1.1rem; color:var(--text-dim);"><?= sanitize(get_setting('vision_statement') ?? 'Leading the charge into a sustainable, electrified future.') ?></p>
                </div>
                <div class="glass bento-card" style="text-align:center;">
                    <div style="font-size:4rem; font-weight:900; opacity:0.1;">⚡</div>
                    <h4>Pure Power</h4>
                    <p style="font-size:0.8rem; color:var(--text-dim);">Engineered for performance without compromise.</p>
                </div>
                <div class="glass bento-card" style="text-align:center;">
                    <div style="font-size:4rem; font-weight:900; opacity:0.1;">🌍</div>
                    <h4>Eco First</h4>
                    <p style="font-size:0.8rem; color:var(--text-dim);">Zero emissions, infinite possibilities for our planet.</p>
                </div>
                <div class="glass bento-card">
                    <h3 style="font-size:1.8rem; margin-bottom:15px;">Our Daily Mission</h3>
                    <p style="color:var(--text-dim);"><?= sanitize(get_setting('mission_statement') ?? 'Delivering excellence and innovation in every ride we offer.') ?></p>
                </div>
            </div>
        </section>

        <!-- Elite Selection -->
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
                    if (!$avail) continue;
                    $elite_rank++;
                    // Smart badge logic from DB data
                    $days_since = $m['newest_date'] ? floor((time() - strtotime($m['newest_date'])) / 86400) : 999;
                    if ($elite_rank === 1 && $m['sales_cnt'] > 0) {
                        $badge_class = 'badge-bestseller'; $badge_icon = 'fa-crown'; $badge_text = 'BEST SELLER · '.$m['sales_cnt'].' Sold';
                    } elseif ($days_since <= 30) {
                        $badge_class = 'badge-newarrival'; $badge_icon = 'fa-sparkles'; $badge_text = 'NEW ARRIVAL';
                    } elseif ($m['stock_cnt'] <= 2 && $m['stock_cnt'] > 0) {
                        $badge_class = 'badge-lowstock'; $badge_icon = 'fa-fire'; $badge_text = 'LOW STOCK · Only '.$m['stock_cnt'].' Left';
                    } elseif ($m['sales_cnt'] >= 2) {
                        $badge_class = 'badge-popular'; $badge_icon = 'fa-chart-line'; $badge_text = 'POPULAR · '.$m['sales_cnt'].' Sold';
                    } else {
                        $badge_class = 'badge-default'; $badge_icon = 'fa-bolt'; $badge_text = 'IN STOCK';
                    }
                ?>
                <div class="glass bike-card" data-tilt>
                    <div class="bike-status <?= $badge_class ?>"><i class="fas <?= $badge_icon ?>"></i> <?= $badge_text ?></div>
                    <div class="bike-img">
                        <img src="<?= $m['image'] ?: '' ?>" alt="<?= sanitize($m['model_name']) ?>" onerror="bikePlaceholder(this)">
                    </div>
                    <div class="bike-title"><?= sanitize($m['model_name']) ?></div>
                    <div class="bike-features">
                        <div class="feat-item"><i class="fas fa-bolt"></i> <?= sanitize($m['category']) ?></div>
                        <div class="feat-item"><i class="fas fa-palette"></i> <?= sanitize($avail['color']) ?></div>
                        <div class="feat-item"><i class="fas fa-tachometer-alt"></i> 100km/h</div>
                        <div class="feat-item"><i class="fas fa-battery-full"></i> 80km Range</div>
                    </div>
                    <div class="price-request"><i class="fab fa-whatsapp"></i> PRICE ON REQUEST</div>
                    <a href="https://wa.me/<?= $wa_number ?>?text=I'm interested in the <?= urlencode($m['model_name']) ?>" class="wa-action">INQUIRE ON WHATSAPP</a>
                </div>
                <?php endwhile; ?>
            </div>
            
            <div style="text-align:center; margin-top:50px;">
                <a href="landing.php?view=bikes" class="btn btn-outline">Explore Full Fleet <i class="fas fa-chevron-right"></i></a>
            </div>
        </section>

        <!-- The Process -->
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

        <!-- Visual Mosaic -->
        <section id="gallery" class="container">
            <h2 class="sec-title" data-text="VISUALS">VISUAL MOSAIC</h2>
            <div class="gallery-grid">
                <?php
                $gallery = $conn->query('SELECT * FROM gallery ORDER BY sort_order ASC');
                while($g = $gallery->fetch_assoc()):
                ?>
                <a href="<?= $g['image'] ?>" class="glightbox gallery-item">
                    <img src="<?= $g['image'] ?>" alt="<?= sanitize($g['title']) ?>" onerror="this.closest('a').remove();">
                    <div class="gallery-info">
                        <h4 style="color:white;"><?= sanitize($g['title']) ?></h4>
                        <p style="font-size:0.8rem; color:rgba(255,255,255,0.7);"><?= sanitize($g['description']) ?></p>
                    </div>
                </a>
                <?php endwhile; ?>
            </div>
        </section>

        <!-- Leadership -->
        <section class="container">
            <h2 class="sec-title" data-text="LEADERS">THE VISIONARIES</h2>
            <div class="bike-grid">
                <?php
                $leaders = $conn->query('SELECT * FROM leadership ORDER BY sort_order ASC');
                while($l = $leaders->fetch_assoc()):
                ?>
                <div class="glass leader-card" style="padding:40px;">
                    <img src="<?= $l['image'] ?: 'https://via.placeholder.com/150' ?>" class="leader-img" alt="<?= sanitize($l['name']) ?>">
                    <h4 style="font-size:1.4rem;"><?= sanitize($l['name']) ?></h4>
                    <p style="color:var(--primary); font-weight:700; font-size:0.8rem; margin-bottom:15px;"><?= strtoupper(sanitize($l['position'])) ?></p>
                    <p style="font-size:0.9rem; color:var(--text-dim); font-style:italic;">"<?= sanitize($l['message']) ?>"</p>
                </div>
                <?php endwhile; ?>
            </div>
        </section>

    <?php elseif ($view === 'bikes'): ?>
        <!-- Full Inventory View -->
        <section class="container" style="padding-top:120px;">
            <h2 class="sec-title" data-text="FLEET">ACTIVE INVENTORY</h2>
            
            <div class="glass" style="margin-bottom:40px; padding:30px;">
                <form action="landing.php" method="GET" style="display:flex; gap:20px; flex-wrap:wrap;">
                    <input type="hidden" name="view" value="bikes">
                    <input type="text" name="search" placeholder="Search Chassis or Model..." value="<?= sanitize($_GET['search'] ?? '') ?>" style="flex:1; margin-bottom:0;">
                    <select name="category" style="width:250px; background:rgba(255,255,255,0.05); border:1px solid var(--glass-border); color:white; border-radius:18px; padding:0 15px;">
                        <option value="">ALL CATEGORIES</option>
                        <?php
                        $cats = $conn->query('SELECT DISTINCT category FROM models');
                        while($c = $cats->fetch_assoc()):
                        ?>
                        <option value="<?= $c['category'] ?>" <?= ($_GET['category'] ?? '') == $c['category'] ? 'selected' : '' ?>><?= $c['category'] ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn btn-main">FILTER</button>
                    <a href="landing.php?view=bikes" class="btn btn-outline">RESET</a>
                </form>
            </div>

            <div class="bike-grid">
                <?php
                $per_page = 9; $page_num = max(1, (int) ($_GET['pg'] ?? 1)); $offset = ($page_num - 1) * $per_page;
                $where_p = ["b.status='in_stock'"];
                if (!empty($_GET['search'])) {
                    $s = mysqli_real_escape_string($conn, $_GET['search']);
                    $where_p[] = "(m.model_name LIKE '%$s%' OR b.chassis_number LIKE '%$s%')";
                }
                if (!empty($_GET['category'])) {
                    $c = mysqli_real_escape_string($conn, $_GET['category']);
                    $where_p[] = "m.category = '$c'";
                }
                $where = implode(' AND ', $where_p);

                $all_bikes = $conn->query("SELECT b.*, m.model_name, m.category, m.image as model_image 
                    FROM bikes b JOIN models m ON b.model_id = m.id WHERE $where ORDER BY b.created_at DESC LIMIT $offset, $per_page");
                $total_cnt = $conn->query("SELECT COUNT(*) FROM bikes b JOIN models m ON b.model_id = m.id WHERE $where")->fetch_row()[0];
                $total_pages = ceil($total_cnt / $per_page);

                // Pre-fetch badge data per model
                $badge_data = [];
                $bd_q = $conn->query("SELECT m.id, COUNT(CASE WHEN b.status='sold' THEN 1 END) as sold_cnt, COUNT(CASE WHEN b.status='in_stock' THEN 1 END) as stk_cnt, MAX(b.created_at) as newest FROM models m LEFT JOIN bikes b ON m.id=b.model_id GROUP BY m.id ORDER BY sold_cnt DESC");
                $bd_rank = 0;
                while($bd = $bd_q->fetch_assoc()) { $bd_rank++; $badge_data[$bd['id']] = array_merge($bd, ['rank'=>$bd_rank]); }

                if ($all_bikes->num_rows > 0):
                    while ($bike = $all_bikes->fetch_assoc()):
                        $img = $bike['image'] ?: $bike['model_image'];
                        // Determine badge for this bike
                        $bd = $badge_data[$bike['model_id']] ?? null;
                        $b_days = $bike['created_at'] ? floor((time()-strtotime($bike['created_at']))/86400) : 999;
                        if ($bd && $bd['rank']===1 && $bd['sold_cnt']>0) { $b_cls='badge-bestseller'; $b_ico='fa-crown'; $b_txt='BEST SELLER'; }
                        elseif ($b_days<=30) { $b_cls='badge-newarrival'; $b_ico='fa-sparkles'; $b_txt='NEW ARRIVAL'; }
                        elseif ($bd && $bd['stk_cnt']<=2) { $b_cls='badge-lowstock'; $b_ico='fa-fire'; $b_txt='LOW STOCK'; }
                        elseif ($bd && $bd['sold_cnt']>=2) { $b_cls='badge-popular'; $b_ico='fa-chart-line'; $b_txt='POPULAR'; }
                        else { $b_cls='badge-default'; $b_ico='fa-bolt'; $b_txt='AVAILABLE'; }
                ?>
                <div class="glass bike-card" data-tilt>
                    <div class="bike-status <?= $b_cls ?>"><i class="fas <?= $b_ico ?>"></i> <?= $b_txt ?></div>
                    <div class="bike-img">
                        <img src="<?= $img ?: '' ?>" alt="<?= sanitize($bike['model_name']) ?>" onerror="bikePlaceholder(this)">
                    </div>
                    <div class="bike-title"><?= sanitize($bike['model_name']) ?></div>
                    <div class="bike-features">
                        <div class="feat-item"><i class="fas fa-fingerprint"></i> <?= sanitize($bike['chassis_number']) ?></div>
                        <div class="feat-item"><i class="fas fa-palette"></i> <?= sanitize($bike['color']) ?></div>
                        <div class="feat-item"><i class="fas fa-shield-alt"></i> Warranty Inc.</div>
                        <div class="feat-item"><i class="fas fa-headset"></i> 24/7 Support</div>
                    </div>
                    <div style="display:flex; gap:12px;">
                        <a href="https://wa.me/<?= $wa_number ?>?text=Inquiry for <?= urlencode($bike['model_name']) ?> (<?= urlencode($bike['chassis_number']) ?>)" class="wa-action" style="flex:1;">INQUIRE</a>
                        <button class="btn btn-outline" onclick="openQuoteModal(<?= $bike['id'] ?>, '<?= sanitize($bike['model_name']) ?>')">QUOTE</button>
                    </div>
                </div>
                <?php endwhile; else: ?>
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
                <?php for($i=1; $i<=$total_pages; $i++): ?>
                <a href="landing.php?view=bikes&pg=<?= $i ?>&search=<?= urlencode($_GET['search'] ?? '') ?>&category=<?= urlencode($_GET['category'] ?? '') ?>" class="btn <?= $page_num == $i ? 'btn-main' : 'btn-outline' ?>" style="padding:12px 22px;"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <!-- Map Section - ABOVE footer -->
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
    <?php endif; endif; ?>

    <footer id="contact">
        <!-- Animated SVG Waves -->
        <div class="footer-wave-area">
            <svg class="wave-1" viewBox="0 0 2400 120" preserveAspectRatio="none">
                <path d="M0,40 C200,100 400,0 600,50 C800,100 1000,10 1200,60 C1400,110 1600,20 1800,70 C2000,110 2200,30 2400,60 L2400,120 L0,120Z" fill="#6366f1"/>
            </svg>
            <svg class="wave-2" viewBox="0 0 2400 120" preserveAspectRatio="none">
                <path d="M0,70 C300,20 500,100 800,50 C1100,0 1300,90 1600,40 C1900,0 2100,80 2400,30 L2400,120 L0,120Z" fill="#a855f7"/>
            </svg>
            <svg class="wave-3" viewBox="0 0 2400 120" preserveAspectRatio="none">
                <path d="M0,90 C400,30 600,110 1000,50 C1400,0 1600,100 2000,40 C2200,10 2300,80 2400,50 L2400,120 L0,120Z" fill="#06b6d4"/>
            </svg>
        </div>
        <div class="footer-glow"></div>

        <!-- Floating Particles -->
        <div class="footer-particles" id="footerParticles"></div>

        <div class="footer-content-area">
            <div class="container footer-wrap">
                <div>
                    <a href="landing.php" class="logo-wrap" style="margin-bottom:25px;">
                        <img src="logo.png" alt="Logo" class="logo-img" onerror="this.style.display='none'">
                        <span class="logo-text"><?= sanitize($company_name) ?></span>
                    </a>
                    <p class="footer-desc"><?= sanitize(get_setting('mission_statement') ?? 'Redefining movement through sustainable innovation.') ?></p>
                    <div class="socials">
                        <?php if($fb = get_setting('social_facebook')): ?><a href="<?= $fb ?>"><i class="fab fa-facebook-f"></i></a><?php endif; ?>
                        <?php if($ig = get_setting('social_instagram')): ?><a href="<?= $ig ?>"><i class="fab fa-instagram"></i></a><?php endif; ?>
                        <?php if($tw = get_setting('social_twitter')): ?><a href="<?= $tw ?>"><i class="fab fa-twitter"></i></a><?php endif; ?>
                    </div>
                </div>
                <div>
                    <h4 class="footer-head">QUICK LINKS</h4>
                    <ul style="list-style:none; line-height:2.8;">
                        <li><a href="landing.php?view=home" class="footer-link"><i class="fas fa-home"></i> Surface</a></li>
                        <li><a href="landing.php?view=bikes" class="footer-link"><i class="fas fa-motorcycle"></i> Inventory</a></li>
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

    <!-- Modals -->
    <div id="requestModal" class="modal">
        <div class="modal-body">
            <span class="modal-close" onclick="closeModal('requestModal')">&times;</span>
            <h2 style="margin-bottom:25px; background:var(--grad); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">REQUEST A MODEL</h2>
            <form action="landing.php" method="POST">
                <label>FULL NAME</label><input type="text" name="name" required placeholder="John Doe">
                <label>WHATSAPP / PHONE</label><input type="text" name="phone" required placeholder="+92 ...">
                <label>SPECIFICATIONS</label><textarea name="bike_details" rows="4" placeholder="Year, Color, Model, Range..."></textarea>
                <button type="submit" name="request_bike" class="btn btn-main" style="width:100%;">SUBMIT REQUEST</button>
            </form>
        </div>
    </div>

    <div id="quoteModal" class="modal">
        <div class="modal-body">
            <span class="modal-close" onclick="closeModal('quoteModal')">&times;</span>
            <h2 style="margin-bottom:5px; color:var(--primary);">GET A QUOTE</h2>
            <p id="q_name" style="color:white; font-weight:700; margin-bottom:25px; font-size:1.1rem;"></p>
            <form action="landing.php" method="POST">
                <input type="hidden" name="bike_id" id="q_id">
                <label>FULL NAME</label><input type="text" name="name" required placeholder="John Doe">
                <label>WHATSAPP #</label><input type="text" name="phone" required placeholder="+92 ...">
                <label>REQUIREMENTS</label><textarea name="details" rows="3" placeholder="Installment details, accessories..."></textarea>
                <button type="submit" name="request_quote" class="btn btn-main" style="width:100%;">REQUEST QUOTE</button>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/glightbox/3.2.0/js/glightbox.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.0/vanilla-tilt.min.js"></script>
    
    <script>
        // Preloader
        window.addEventListener('load', () => {
            const pre = document.getElementById('preloader');
            pre.style.opacity = '0';
            setTimeout(() => pre.remove(), 800);
            animateStats();
        });

        // 3D Particles Environment
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        const renderer = new THREE.WebGLRenderer({ canvas: document.getElementById('bg-canvas'), alpha: true, antialias: true });
        renderer.setSize(window.innerWidth, window.innerHeight);
        
        const geometry = new THREE.TorusGeometry(10, 3, 16, 100);
        const particlesGeometry = new THREE.BufferGeometry();
        const counts = 3000;
        const posArray = new Float32Array(counts * 3);
        for(let i=0; i < counts * 3; i++) { posArray[i] = (Math.random() - 0.5) * 20; }
        particlesGeometry.setAttribute('position', new THREE.BufferAttribute(posArray, 3));
        
        const material = new THREE.PointsMaterial({ size: 0.008, color: '#6366f1', transparent: true, opacity: 0.3 });
        const particlesMesh = new THREE.Points(particlesGeometry, material);
        scene.add(particlesMesh);
        camera.position.z = 5;

        let mouseX = 0, mouseY = 0;
        document.addEventListener('mousemove', (e) => { mouseX = e.clientX; mouseY = e.clientY; });

        const animate = () => {
            requestAnimationFrame(animate);
            particlesMesh.rotation.y += 0.001;
            particlesMesh.rotation.x += 0.0005;
            if (mouseX > 0) {
                particlesMesh.rotation.x += (mouseY * 0.00001);
                particlesMesh.rotation.y += (mouseX * 0.00001);
            }
            renderer.render(scene, camera);
        };
        animate();

        // Stats Animation
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

        // Tilt & Lightbox
        VanillaTilt.init(document.querySelectorAll("[data-tilt]"), { max: 8, speed: 500, glare: true, "max-glare": 0.15, scale: 1.02 });
        const lightbox = GLightbox({ selector: '.glightbox' });

        function openQuoteModal(id, name) {
            document.getElementById('q_id').value = id;
            document.getElementById('q_name').innerText = "Model: " + name;
            document.getElementById('quoteModal').style.display = 'flex';
        }
        function openRequestModal() { document.getElementById('requestModal').style.display = 'flex'; }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }

        window.onclick = (e) => { if(e.target.classList.contains('modal')) e.target.style.display = 'none'; };

        // Footer Floating Particles
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

        // Scroll Reveal
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if(entry.isIntersecting) entry.target.classList.add('animate__animated', 'animate__fadeInUp');
            });
        }, { threshold: 0.1 });
        document.querySelectorAll('section').forEach(s => observer.observe(s));
    </script>
</body>
</html>
