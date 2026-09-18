<?php
ini_set('display_errors', '0');

$request_host = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
$is_local_host = in_array($request_host, ['localhost', '127.0.0.1', '::1', ''], true) || str_ends_with($request_host, '.local');

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

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die('Database Connection Failed');
}
$conn->set_charset('utf8mb4');

$settings = [];
$res_settings = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('company_name', 'branch_name', 'company_whatsapp', 'company_address')");
if ($res_settings) {
    while ($row = $res_settings->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}
$company_name = $settings['company_name'] ?? 'BNI Enterprises';
$branch_name = $settings['branch_name'] ?? '';
$company_whatsapp = $settings['company_whatsapp'] ?? '';
$company_address = $settings['company_address'] ?? '';

$whatsapp_numbers = array_filter(array_map('trim', explode(',', $company_whatsapp)));
$primary_whatsapp = !empty($whatsapp_numbers) ? $whatsapp_numbers[0] : '';
$whatsapp_clean = preg_replace('/[^0-9]/', '', $primary_whatsapp);

$sql = "
    SELECT 
        b.id, 
        b.chassis_number, 
        b.color, 
        b.selling_price, 
        b.image AS bike_image, 
        m.model_name, 
        m.top_speed, 
        m.max_range, 
        m.image AS model_image 
    FROM bikes b 
    JOIN models m ON b.model_id = m.id 
    WHERE b.status = 'in_stock'
    ORDER BY b.id DESC
";
$result = $conn->query($sql);
$bikes = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $speed = trim((string)($row['top_speed'] ?? ''));
        if ($speed === '') {
            $speed = '100 km/h';
        } elseif (is_numeric($speed)) {
            $speed .= ' km/h';
        }

        $range = trim((string)($row['max_range'] ?? ''));
        if ($range === '') {
            $range = '80 km Range';
        } elseif (is_numeric($range)) {
            $range .= ' km Range';
        }

        $row['formatted_speed'] = $speed;
        $row['formatted_range'] = $range;
        $row['formatted_color'] = (!empty($row['color']) && strtolower($row['color']) !== 'unknown') ? $row['color'] : 'Standard';

        $bikes[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($company_name) ?> - Digital Showroom Kiosk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #07090e;
            --panel-bg: rgba(15, 23, 42, 0.72);
            --panel-border: rgba(255, 255, 255, 0.1);
            --accent-cyan: #38bdf8;
            --accent-glow: rgba(56, 189, 248, 0.25);
            --accent-green: #10b981;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;
            user-select: none;
            -webkit-user-select: none;
        }

        body, html {
            width: 100%;
            height: 100%;
            overflow: hidden;
            background-color: var(--bg-dark);
            color: var(--text-main);
            cursor: default;
        }

        .kiosk-app {
            position: relative;
            width: 100vw;
            height: 100vh;
            display: flex;
            flex-direction: column;
            background: radial-gradient(circle at 75% 50%, #151d30 0%, #080c14 55%, #04060a 100%);
        }

        .ambient-glow {
            position: absolute;
            top: 20%;
            right: 15%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.12) 0%, rgba(99, 102, 241, 0.05) 50%, transparent 70%);
            filter: blur(80px);
            pointer-events: none;
            z-index: 1;
        }

        .header-bar {
            position: relative;
            z-index: 10;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            border-bottom: 1px solid var(--panel-border);
            background: rgba(7, 9, 14, 0.65);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            flex-shrink: 0;
        }

        .brand-group {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-logo-img {
            height: 42px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 2px 8px rgba(0,0,0,0.5));
        }

        .brand-titles {
            display: flex;
            flex-direction: column;
        }

        .brand-title {
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #ffffff;
            line-height: 1.2;
        }

        .brand-subtitle {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--accent-cyan);
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .live-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #34d399;
            letter-spacing: 0.5px;
        }

        .live-pulse {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 8px #10b981;
            animation: pulse-dot 1.8s infinite;
        }

        @keyframes pulse-dot {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.3); opacity: 1; box-shadow: 0 0 14px #10b981; }
            100% { transform: scale(0.95); opacity: 0.8; }
        }

        .btn-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--panel-border);
            color: #cbd5e1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-icon:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-1px);
        }

        .btn-icon svg {
            width: 18px;
            height: 18px;
            fill: currentColor;
        }

        .main-stage {
            position: relative;
            flex: 1;
            width: 100%;
            overflow: hidden;
            display: flex;
        }

        .slide-deck {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .slide-item {
            position: absolute;
            inset: 0;
            display: grid;
            grid-template-columns: 46% 54%;
            padding: 24px 40px;
            gap: 24px;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.7s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.7s;
            z-index: 2;
        }

        .slide-item.active {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            z-index: 3;
        }

        .info-col {
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-height: 100%;
            overflow: hidden;
            padding-right: 12px;
            z-index: 4;
        }

        .meta-badges {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .badge-pill {
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .badge-instock {
            background: rgba(56, 189, 248, 0.12);
            color: var(--accent-cyan);
            border: 1px solid rgba(56, 189, 248, 0.25);
        }

        .badge-vin {
            background: rgba(255, 255, 255, 0.06);
            color: #94a3b8;
            border: 1px solid rgba(255, 255, 255, 0.1);
            font-family: monospace;
            font-size: 0.85rem;
        }

        .model-heading {
            font-size: clamp(2.2rem, 4vw, 3.6rem);
            font-weight: 900;
            line-height: 1.1;
            color: #ffffff;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: -0.5px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            text-shadow: 0 4px 20px rgba(0,0,0,0.6);
        }

        .specs-deck {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .spec-card {
            background: var(--panel-bg);
            border: 1px solid var(--panel-border);
            border-radius: 14px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.35);
        }

        .spec-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: rgba(56, 189, 248, 0.12);
            border: 1px solid rgba(56, 189, 248, 0.2);
            color: var(--accent-cyan);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .spec-icon-box svg {
            width: 22px;
            height: 22px;
            fill: currentColor;
        }

        .spec-data {
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .spec-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .spec-value {
            font-size: 1.25rem;
            font-weight: 800;
            color: #ffffff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .price-banner {
            display: inline-flex;
            align-items: center;
            gap: 14px;
            padding: 12px 20px;
            background: linear-gradient(135deg, rgba(56, 189, 248, 0.15) 0%, rgba(99, 102, 241, 0.15) 100%);
            border: 1px solid rgba(56, 189, 248, 0.35);
            border-radius: 12px;
            width: fit-content;
        }

        .price-label {
            font-size: 0.85rem;
            color: var(--accent-cyan);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .price-num {
            font-size: 1.6rem;
            font-weight: 900;
            color: #ffffff;
        }

        .preview-col {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            overflow: hidden;
        }

        .bike-image-element {
            width: 100%;
            height: 100%;
            max-height: 82vh;
            object-fit: contain;
            object-position: center;
            filter: drop-shadow(0 20px 40px rgba(0, 0, 0, 0.7));
            transition: transform 0.8s ease;
        }

        .slide-item.active .bike-image-element {
            animation: bike-in 0.8s ease-out;
        }

        @keyframes bike-in {
            0% { transform: scale(0.92) translateY(15px); opacity: 0; }
            100% { transform: scale(1) translateY(0); opacity: 1; }
        }

        .nav-edge-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            transition: all 0.2s ease;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        }

        .nav-edge-btn:hover {
            background: rgba(56, 189, 248, 0.9);
            color: #04060a;
            border-color: var(--accent-cyan);
            transform: translateY(-50%) scale(1.08);
        }

        .nav-edge-btn svg {
            width: 22px;
            height: 22px;
            fill: currentColor;
        }

        .btn-prev {
            left: 20px;
        }

        .btn-next {
            right: 20px;
        }

        .footer-dock {
            position: relative;
            z-index: 10;
            height: 84px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            background: rgba(7, 9, 14, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-top: 1px solid var(--panel-border);
            flex-shrink: 0;
        }

        .progress-line-holder {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: rgba(255, 255, 255, 0.08);
        }

        .progress-line {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, var(--accent-cyan), #6366f1);
            box-shadow: 0 0 10px var(--accent-cyan);
            transition: width 0.05s linear;
        }

        .footer-contact-block {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .whatsapp-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 10px 18px;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.35);
            border-radius: 12px;
            color: #ffffff;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .whatsapp-action-btn:hover {
            background: #10b981;
            color: #04060a;
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.4);
            transform: translateY(-2px);
        }

        .wa-icon-box {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #10b981;
        }

        .whatsapp-action-btn:hover .wa-icon-box {
            color: #04060a;
        }

        .wa-icon-box svg {
            width: 24px;
            height: 24px;
            fill: currentColor;
        }

        .wa-text-box {
            display: flex;
            flex-direction: column;
        }

        .wa-label {
            font-size: 0.72rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .whatsapp-action-btn:hover .wa-label {
            color: #04060a;
        }

        .wa-number {
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        .company-address-box {
            display: flex;
            flex-direction: column;
            max-width: 380px;
        }

        .addr-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .addr-text {
            font-size: 0.9rem;
            color: #cbd5e1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .footer-controls-block {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .control-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--panel-border);
            padding: 6px 10px;
            border-radius: 12px;
        }

        .ctrl-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: transparent;
            border: none;
            color: #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .ctrl-btn:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
        }

        .ctrl-btn svg {
            width: 16px;
            height: 16px;
            fill: currentColor;
        }

        .slide-counter {
            font-size: 0.9rem;
            font-weight: 700;
            color: #ffffff;
            padding: 0 10px;
            letter-spacing: 1px;
            font-family: monospace;
        }

        .empty-kiosk {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            color: #94a3b8;
            gap: 16px;
        }

        .empty-kiosk h2 {
            font-size: 2rem;
            color: #ffffff;
        }

        @media (max-width: 1024px) {
            .slide-item {
                grid-template-columns: 50% 50%;
                padding: 16px 24px;
            }
            .model-heading {
                font-size: 2.2rem;
            }
            .company-address-box {
                display: none;
            }
        }
    </style>
</head>
<body>

<?php if (empty($bikes)): ?>
    <div class="empty-kiosk">
        <h2>No In-Stock Bikes Available</h2>
        <p>Please check back shortly or update the inventory database.</p>
    </div>
<?php else: ?>

    <div class="kiosk-app" id="kioskApp">
        <div class="ambient-glow"></div>

        <header class="header-bar">
            <div class="brand-group">
                <img src="logo.png" alt="BNI Logo" class="brand-logo-img" onerror="this.style.display='none'">
                <div class="brand-titles">
                    <span class="brand-title"><?= htmlspecialchars($company_name) ?></span>
                    <span class="brand-subtitle"><?= htmlspecialchars($branch_name ?: 'Official Digital Showroom') ?></span>
                </div>
            </div>

            <div class="header-actions">
                <div class="live-tag">
                    <span class="live-pulse"></span>
                    <span><?= count($bikes) ?> IN STOCK</span>
                </div>
                <button class="btn-icon" id="fsToggleBtn" title="Toggle Fullscreen">
                    <svg viewBox="0 0 24 24" id="fsIconExpand"><path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/></svg>
                    <svg viewBox="0 0 24 24" id="fsIconCompress" style="display:none;"><path d="M5 16h3v3h2v-5H5v2zm3-8H5v2h5V5H8v3zm6 11h2v-3h3v-2h-5v5zm2-11V5h-2v5h5V8h-3z"/></svg>
                </button>
            </div>
        </header>

        <main class="main-stage" id="stageArea">
            <button class="nav-edge-btn btn-prev" id="edgePrevBtn" title="Previous Bike (Left Arrow)">
                <svg viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
            </button>

            <div class="slide-deck">
                <?php foreach ($bikes as $index => $bike): 
                    $imgPath = !empty($bike['bike_image']) ? $bike['bike_image'] : $bike['model_image'];
                    $imgUrl = 'serve_img.php?p=' . urlencode(ltrim($imgPath, '/'));
                ?>
                    <div class="slide-item <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>">
                        <div class="info-col">
                            <div class="meta-badges">
                                <span class="badge-pill badge-instock">In Stock & Ready</span>
                                <span class="badge-pill badge-vin">CHASSIS # <?= htmlspecialchars($bike['chassis_number']) ?></span>
                            </div>

                            <h1 class="model-heading" title="<?= htmlspecialchars($bike['model_name']) ?>">
                                <?= htmlspecialchars($bike['model_name']) ?>
                            </h1>

                            <div class="specs-deck">
                                <div class="spec-card">
                                    <div class="spec-icon-box">
                                        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 14h-2v-2h2v2zm0-4h-2V7h2v5z"/></svg>
                                    </div>
                                    <div class="spec-data">
                                        <span class="spec-title">Top Speed</span>
                                        <span class="spec-value"><?= htmlspecialchars($bike['formatted_speed']) ?></span>
                                    </div>
                                </div>

                                <div class="spec-card">
                                    <div class="spec-icon-box">
                                        <svg viewBox="0 0 24 24"><path d="M11 21h-1l1-7H7.5c-.88 0-.33-.75-.31-.78C8.48 10.94 10.42 7.54 13.01 3h1l-1 7h3.51c.4 0 .62.19.4.66C13.84 16.27 11 21 11 21z"/></svg>
                                    </div>
                                    <div class="spec-data">
                                        <span class="spec-title">Battery Range</span>
                                        <span class="spec-value"><?= htmlspecialchars($bike['formatted_range']) ?></span>
                                    </div>
                                </div>

                                <div class="spec-card">
                                    <div class="spec-icon-box">
                                        <svg viewBox="0 0 24 24"><path d="M12 3c-4.97 0-9 4.03-9 9 0 2.12.74 4.07 1.97 5.61L4.35 19.4c-.39.39-.39 1.02 0 1.41.39.39 1.02.39 1.41 0l1.9-1.9C9.28 19.57 10.59 20 12 20c4.97 0 9-4.03 9-9s-4.03-9-9-9zm0 15c-3.31 0-6-2.69-6-6s2.69-6 6-6 6 2.69 6 6-2.69 6-6 6z"/></svg>
                                    </div>
                                    <div class="spec-data">
                                        <span class="spec-title">Color</span>
                                        <span class="spec-value"><?= htmlspecialchars($bike['formatted_color']) ?></span>
                                    </div>
                                </div>

                                <div class="spec-card">
                                    <div class="spec-icon-box">
                                        <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>
                                    </div>
                                    <div class="spec-data">
                                        <span class="spec-title">Stock Unit</span>
                                        <span class="spec-value">Unit #<?= $bike['id'] ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="price-banner">
                                <span class="price-label">Official Price</span>
                                <span class="price-num">
                                    <?= (!empty($bike['selling_price']) && $bike['selling_price'] > 0) ? 'Rs. ' . number_format($bike['selling_price']) : 'Inquire for Price' ?>
                                </span>
                            </div>
                        </div>

                        <div class="preview-col">
                            <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($bike['model_name']) ?>" class="bike-image-element" loading="lazy">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button class="nav-edge-btn btn-next" id="edgeNextBtn" title="Next Bike (Right Arrow)">
                <svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
            </button>
        </main>

        <footer class="footer-dock">
            <div class="progress-line-holder">
                <div class="progress-line" id="progressLine"></div>
            </div>

            <div class="footer-contact-block">
                <?php if(!empty($primary_whatsapp)): ?>
                    <a href="https://wa.me/<?= htmlspecialchars($whatsapp_clean) ?>" target="_blank" class="whatsapp-action-btn" title="Contact sales via WhatsApp">
                        <div class="wa-icon-box">
                            <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                        </div>
                        <div class="wa-text-box">
                            <span class="wa-label">Inquire / Book Now</span>
                            <span class="wa-number"><?= htmlspecialchars($primary_whatsapp) ?></span>
                        </div>
                    </a>
                <?php endif; ?>

                <?php if(!empty($company_address)): ?>
                    <div class="company-address-box">
                        <span class="addr-label">Showroom Location</span>
                        <span class="addr-text" title="<?= htmlspecialchars($company_address) ?>"><?= htmlspecialchars($company_address) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="footer-controls-block">
                <div class="control-pill">
                    <button class="ctrl-btn" id="ctrlPrevBtn" title="Previous Bike">
                        <svg viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
                    </button>
                    <button class="ctrl-btn" id="ctrlPlayPauseBtn" title="Pause/Resume Auto Slideshow">
                        <svg viewBox="0 0 24 24" id="iconPause"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                        <svg viewBox="0 0 24 24" id="iconPlay" style="display:none;"><path d="M8 5v14l11-7z"/></svg>
                    </button>
                    <button class="ctrl-btn" id="ctrlNextBtn" title="Next Bike">
                        <svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
                    </button>
                    <span class="slide-counter" id="slideCounterText">1 / <?= count($bikes) ?></span>
                </div>
            </div>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const slides = document.querySelectorAll('.slide-item');
            const totalSlides = slides.length;
            if (totalSlides === 0) return;

            const progressLine = document.getElementById('progressLine');
            const counterText = document.getElementById('slideCounterText');
            const playPauseBtn = document.getElementById('ctrlPlayPauseBtn');
            const iconPause = document.getElementById('iconPause');
            const iconPlay = document.getElementById('iconPlay');
            const fsToggleBtn = document.getElementById('fsToggleBtn');
            const fsIconExpand = document.getElementById('fsIconExpand');
            const fsIconCompress = document.getElementById('fsIconCompress');

            let currentIndex = 0;
            let isPlaying = true;
            const slideDuration = 7000;
            let slideTimer = null;
            let progressInterval = null;
            let startTime = Date.now();
            let elapsedBeforePause = 0;

            function updateCounter() {
                if (counterText) {
                    counterText.textContent = `${currentIndex + 1} / ${totalSlides}`;
                }
            }

            function goToSlide(index) {
                slides.forEach((s) => s.classList.remove('active'));
                currentIndex = (index + totalSlides) % totalSlides;
                slides[currentIndex].classList.add('active');
                updateCounter();
                elapsedBeforePause = 0;
                startSlideTimer();
            }

            function nextSlide() {
                goToSlide(currentIndex + 1);
            }

            function prevSlide() {
                goToSlide(currentIndex - 1);
            }

            function clearTimers() {
                if (slideTimer) clearTimeout(slideTimer);
                if (progressInterval) clearInterval(progressInterval);
            }

            function startSlideTimer() {
                clearTimers();
                if (!isPlaying) return;

                startTime = Date.now() - elapsedBeforePause;
                const remaining = slideDuration - elapsedBeforePause;

                progressInterval = setInterval(() => {
                    const currentElapsed = Date.now() - startTime;
                    const pct = Math.min((currentElapsed / slideDuration) * 100, 100);
                    if (progressLine) {
                        progressLine.style.width = pct + '%';
                    }
                }, 40);

                slideTimer = setTimeout(() => {
                    nextSlide();
                }, remaining);
            }

            function togglePlayPause() {
                if (isPlaying) {
                    isPlaying = false;
                    elapsedBeforePause = Date.now() - startTime;
                    clearTimers();
                    if (iconPause) iconPause.style.display = 'none';
                    if (iconPlay) iconPlay.style.display = 'block';
                } else {
                    isPlaying = true;
                    if (iconPause) iconPause.style.display = 'block';
                    if (iconPlay) iconPlay.style.display = 'none';
                    startSlideTimer();
                }
            }

            document.getElementById('edgePrevBtn')?.addEventListener('click', () => prevSlide());
            document.getElementById('edgeNextBtn')?.addEventListener('click', () => nextSlide());
            document.getElementById('ctrlPrevBtn')?.addEventListener('click', () => prevSlide());
            document.getElementById('ctrlNextBtn')?.addEventListener('click', () => nextSlide());
            playPauseBtn?.addEventListener('click', () => togglePlayPause());

            fsToggleBtn?.addEventListener('click', () => {
                if (!document.fullscreenElement) {
                    document.documentElement.requestFullscreen().catch(() => {});
                } else {
                    document.exitFullscreen().catch(() => {});
                }
            });

            document.addEventListener('fullscreenchange', () => {
                const isFs = !!document.fullscreenElement;
                if (fsIconExpand && fsIconCompress) {
                    fsIconExpand.style.display = isFs ? 'none' : 'block';
                    fsIconCompress.style.display = isFs ? 'block' : 'none';
                }
            });

            window.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowRight') {
                    nextSlide();
                } else if (e.key === 'ArrowLeft') {
                    prevSlide();
                } else if (e.key === ' ' || e.key === 'Spacebar') {
                    e.preventDefault();
                    togglePlayPause();
                }
            });

            let touchStartX = 0;
            const stage = document.getElementById('stageArea');
            stage?.addEventListener('touchstart', (e) => {
                touchStartX = e.touches[0].clientX;
            }, { passive: true });

            stage?.addEventListener('touchend', (e) => {
                const touchEndX = e.changedTouches[0].clientX;
                const diff = touchEndX - touchStartX;
                if (Math.abs(diff) > 60) {
                    if (diff < 0) {
                        nextSlide();
                    } else {
                        prevSlide();
                    }
                }
            }, { passive: true });

            updateCounter();
            startSlideTimer();
        });
    </script>
<?php endif; ?>
</body>
</html>
