<?php
session_start();
$db_host = 'localhost';
$db_user = 'root';
$db_pass = 'root';
$db_name = 'bni_enterprises2';
$app_version = '2.0.0';
$author = 'Yasin Ullah';
$_SESSION['captcha_lifetime'] = $_SESSION['captcha_lifetime'] ?? time() + 300;
if (time() > $_SESSION['captcha_lifetime']) {
    unset($_SESSION['captcha_code']);
    $_SESSION['captcha_lifetime'] = time() + 300;
}

$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('<div style="padding:40px;text-align:center;font-family:sans-serif"><h2>🚫 Invalid Request</h2><p>Security token missing or expired. Please refresh the page and try again.</p></div>');
    }
}

function get_client_ip()
{
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

$ip_address = get_client_ip();
$attempt_key = 'login_attempts_' . $ip_address;
$ban_key = 'banned_ip_' . $ip_address;
$_SESSION[$attempt_key] = $_SESSION[$attempt_key] ?? ['count' => 0, 'time' => time()];
if (isset($_SESSION[$ban_key]) && $_SESSION[$ban_key] > time()) {
    die('<div style="padding:40px;text-align:center;font-family:sans-serif"><h2>🚫 Access Denied</h2><p>Too many failed login attempts. Your IP has been temporarily banned. Please try again after 3 hours.</p></div>');
}

function record_failed_attempt()
{
    global $attempt_key, $ban_key;
    $_SESSION[$attempt_key]['count']++;
    $_SESSION[$attempt_key]['time'] = time();
    if ($_SESSION[$attempt_key]['count'] >= 7) {
        $_SESSION[$ban_key] = time() + (3 * 3600);
        $_SESSION[$attempt_key] = ['count' => 0, 'time' => time()];
    }
}

function reset_attempts()
{
    global $attempt_key;
    $_SESSION[$attempt_key] = ['count' => 0, 'time' => time()];
}

function db_connect($create_db = false)
{
    global $db_host, $db_user, $db_pass, $db_name;
    if ($create_db) {
        $conn = new mysqli($db_host, $db_user, $db_pass);
    } else {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    }
    if ($conn->connect_error) {
        error_log('DB Connection Error: ' . $conn->connect_error);
        return null;
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function current_user($conn)
{
    if (!isset($_SESSION['user_id']))
        return null;
    $stmt = $conn->prepare('SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON u.role_id=r.id WHERE u.id=? LIMIT 1');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function has_permission($conn, $page, $action = 'view')
{
    $user = current_user($conn);
    if (!$user)
        return false;
    if ($user['role_name'] === 'Administrator')
        return true;
    $col = 'can_view';
    if ($action === 'add')
        $col = 'can_add';
    if ($action === 'edit')
        $col = 'can_edit';
    if ($action === 'delete')
        $col = 'can_delete';
    $stmt = $conn->prepare("SELECT $col FROM role_permissions WHERE role_id=? AND page=? LIMIT 1");
    $stmt->bind_param('is', $user['role_id'], $page);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res && $res[$col] == 1;
}

function require_permission($conn, $page, $action = 'view')
{
    if (!has_permission($conn, $page, $action)) {
        $user = current_user($conn);
        $fallback = 'index.php';
        if ($user) {
            $stmt = $conn->prepare('SELECT page FROM role_permissions WHERE role_id=? AND can_view=1 LIMIT 1');
            $stmt->bind_param('i', $user['role_id']);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $fallback = $res ? 'index.php?page=' . $res['page'] : 'index.php?logout=1';
        }
        die('<meta http-equiv="refresh" content="10;url=' . $fallback . '"><div style="padding:40px;text-align:center;font-family:sans-serif"><h2>⛔ Access Denied</h2><p>You do not have permission to ' . $action . ' ' . $page . '.</p><p style="font-size:0.9rem;color:#888">Auto-redirecting in 10 seconds...</p><a href="' . $fallback . '" style="display:inline-block;padding:8px 16px;background:#4a9eff;color:#fff;text-decoration:none;border-radius:2px;margin-top:10px">Go Back</a></div>');
    }
}

function generate_svg_captcha($text)
{
    header('Content-Type: image/svg+xml');
    $width = 120;
    $height = 40;
    $svg = '<?xml version="1.0" encoding="UTF-8"?>';
    $svg .= '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">';
    $svg .= '<rect width="100%" height="100%" fill="#f9f9f9" rx="2" ry="2" />';
    for ($i = 0; $i < 6; $i++) {
        $x1 = rand(0, $width);
        $y1 = rand(0, $height);
        $x2 = rand(0, $width);
        $y2 = rand(0, $height);
        $svg .= '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="#bbb" stroke-width="2" opacity="0.6" />';
    }
    $svg .= '<text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" font-family="monospace" font-size="18" font-weight="bold" fill="#333" letter-spacing="1">' . $text . '</text>';
    $svg .= '</svg>';
    echo $svg;
    exit;
}

if (isset($_GET['captcha'])) {
    $operand1 = rand(1, 10);
    $operand2 = rand(1, 10);
    $operator = ['+', '-'][rand(0, 1)];
    if ($operator === '-' && $operand2 > $operand1) {
        $temp = $operand1;
        $operand1 = $operand2;
        $operand2 = $temp;
    }
    $result = ($operator == '+') ? ($operand1 + $operand2) : ($operand1 - $operand2);
    $_SESSION['captcha_code'] = $result;
    $_SESSION['captcha_lifetime'] = time() + 300;
    $equation = $operand1 . ' ' . $operator . ' ' . $operand2 . ' = ?';
    generate_svg_captcha($equation);
}

function install_database()
{
    global $db_name;
    $conn = db_connect(true);
    if (!$conn)
        return false;
    $conn->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db($db_name);
    $tables = [
        'CREATE TABLE IF NOT EXISTS `settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `setting_key` VARCHAR(100) UNIQUE NOT NULL,
            `setting_value` TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        'CREATE TABLE IF NOT EXISTS `suppliers` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `contact` VARCHAR(100),
            `address` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        'CREATE TABLE IF NOT EXISTS `customers` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `phone` VARCHAR(50),
            `cnic` VARCHAR(20),
            `is_filer` TINYINT(1) DEFAULT 1,
            `address` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        'CREATE TABLE IF NOT EXISTS `models` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `model_code` VARCHAR(50) NOT NULL,
            `model_name` VARCHAR(255) NOT NULL,
            `category` VARCHAR(100),
            `short_code` VARCHAR(20),
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        'CREATE TABLE IF NOT EXISTS `accessories` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `sku` VARCHAR(100) UNIQUE,
            `purchase_price` DECIMAL(15,2) DEFAULT 0.00,
            `selling_price` DECIMAL(15,2) DEFAULT 0.00,
            `current_stock` INT DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        'CREATE TABLE IF NOT EXISTS `purchase_orders` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_date` DATE,
            `supplier_id` INT,
            `total_units` INT,
            `total_amount` DECIMAL(15,2) DEFAULT 0.00,
            `notes` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        "CREATE TABLE IF NOT EXISTS `bikes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `purchase_order_id` INT,
            `order_date` DATE,
            `inventory_date` DATE,
            `chassis_number` VARCHAR(100) UNIQUE NOT NULL,
            `motor_number` VARCHAR(100),
            `model_id` INT,
            `color` VARCHAR(50),
            `purchase_price` DECIMAL(15,2),
            `selling_price` DECIMAL(15,2) NULL,
            `selling_date` DATE NULL,
            `customer_id` INT NULL,
            `tax_amount` DECIMAL(15,2) DEFAULT 0,
            `margin` DECIMAL(15,2) DEFAULT 0,
            `status` ENUM('in_stock','sold','returned','reserved') DEFAULT 'in_stock',
            `return_date` DATE NULL,
            `return_amount` DECIMAL(15,2) NULL,
            `return_notes` TEXT NULL,
            `safeguard_notes` TEXT NULL,
            `notes` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`model_id`) REFERENCES `models`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        'CREATE TABLE IF NOT EXISTS `sale_accessories` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `bike_id` INT NOT NULL,
            `accessory_id` INT NOT NULL,
            `quantity` INT NOT NULL,
            `unit_price` DECIMAL(15,2) NOT NULL,
            `discount_amount` DECIMAL(15,2) DEFAULT 0.00,
            `final_price` DECIMAL(15,2) NOT NULL,
            FOREIGN KEY (`bike_id`) REFERENCES `bikes`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`accessory_id`) REFERENCES `accessories`(`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        "CREATE TABLE IF NOT EXISTS `payments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `payment_date` DATE NOT NULL,
            `payment_type` ENUM('cash','cheque','bank_transfer','online','other') NOT NULL,
            `amount` DECIMAL(15,2) NOT NULL,
            `cheque_number` VARCHAR(50) NULL,
            `bank_name` VARCHAR(100) NULL,
            `cheque_date` DATE NULL,
            `transaction_type` ENUM('purchase','sale','installment','expense_payment','supplier_payment','customer_refund') NOT NULL,
            `reference_id` INT NULL,
            `party_name` VARCHAR(255),
            `notes` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `installments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `bike_id` INT NOT NULL,
            `customer_id` INT NOT NULL,
            `due_date` DATE NOT NULL,
            `installment_amount` DECIMAL(15,2) NOT NULL,
            `amount_paid` DECIMAL(15,2) DEFAULT 0.00,
            `penalty_fee` DECIMAL(15,2) DEFAULT 0.00,
            `status` ENUM('pending','paid','overdue','cancelled') DEFAULT 'pending',
            `payment_id` INT NULL,
            `notes` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`bike_id`) REFERENCES `bikes`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`payment_id`) REFERENCES `payments`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `ledger` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `entry_date` DATE,
            `entry_type` ENUM('debit','credit'),
            `amount` DECIMAL(15,2),
            `party_type` ENUM('customer','supplier','other'),
            `party_id` INT,
            `description` TEXT,
            `reference_type` VARCHAR(50),
            `reference_id` INT,
            `balance` DECIMAL(15,2),
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        'CREATE TABLE IF NOT EXISTS `roles` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) UNIQUE NOT NULL,
            `description` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        'CREATE TABLE IF NOT EXISTS `role_permissions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `role_id` INT NOT NULL,
            `page` VARCHAR(50) NOT NULL,
            `can_view` TINYINT(1) DEFAULT 0,
            `can_add` TINYINT(1) DEFAULT 0,
            `can_edit` TINYINT(1) DEFAULT 0,
            `can_delete` TINYINT(1) DEFAULT 0,
            UNIQUE KEY `role_page` (`role_id`,`page`),
            FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        'CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(50) UNIQUE NOT NULL,
            `password_hash` VARCHAR(255) NOT NULL,
            `full_name` VARCHAR(255),
            `role_id` INT,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        "CREATE TABLE IF NOT EXISTS `income_expenses` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `entry_date` DATE NOT NULL,
            `type` ENUM('income','expense') NOT NULL,
            `category` VARCHAR(100) NOT NULL,
            `amount` DECIMAL(15,2) NOT NULL,
            `payment_method` ENUM('cash','cheque','bank_transfer','online','other') DEFAULT 'cash',
            `reference` VARCHAR(255),
            `notes` TEXT,
            `created_by` INT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `quotations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `quote_date` DATE NOT NULL,
            `customer_id` INT,
            `bike_id` INT,
            `accessories_json` TEXT,
            `quoted_price` DECIMAL(15,2) NOT NULL,
            `valid_until` DATE,
            `status` ENUM('pending','accepted','rejected','converted') DEFAULT 'pending',
            `notes` TEXT,
            `created_by` INT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`bike_id`) REFERENCES `bikes`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
    foreach ($tables as $sql) {
        if (!$conn->query($sql)) {
            $conn->close();
            return false;
        }
    }
    $defaults = [
        ['company_name', 'BNI Enterprises'],
        ['branch_name', 'Dera (Ahmed Metro)'],
        ['tax_rate', '0.1'],
        ['currency', 'Rs.'],
        ['tax_on', 'purchase_price'],
        ['theme', 'dark'],
        ['admin_password', password_hash('admin123', PASSWORD_DEFAULT)],
        ['show_purchase_on_invoice', '0'],
        ['session_timeout_idle', '2400'],
        ['session_timeout_absolute', '28800'],
    ];
    $stmt = $conn->prepare('INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES (?, ?)');
    foreach ($defaults as $d) {
        $stmt->bind_param('ss', $d[0], $d[1]);
        $stmt->execute();
    }
    $stmt->close();
    $conn->query("INSERT IGNORE INTO roles (id, name, description) VALUES (1,'Administrator','Full access')");
    $conn->query("INSERT IGNORE INTO roles (id, name, description) VALUES (2,'Manager','Limited access')");
    $admin_hash = password_hash('admin123!', PASSWORD_DEFAULT);
    $conn->query("INSERT IGNORE INTO users (id, username, password_hash, full_name, role_id, is_active) VALUES (1,'admin','$admin_hash','System Administrator',1,1)");
    $pages = ['dashboard', 'inventory', 'purchase', 'sale', 'customers', 'suppliers', 'models', 'reports', 'returns', 'payments', 'settings', 'roles', 'users', 'income_expense', 'accessories', 'quotations', 'installments'];
    foreach ($pages as $p) {
        $conn->query("INSERT IGNORE INTO role_permissions (role_id, page, can_view, can_add, can_edit, can_delete) VALUES (1,'$p',1,1,1,1)");
    }
    $conn->query("INSERT IGNORE INTO role_permissions (role_id, page, can_view, can_add, can_edit, can_delete) VALUES (2,'dashboard',1,0,0,0)");
    $stmt->close();
    $models_seed = [
        ['LY SI', 'LY SI Electric Bike', 'Electric Bike', 'LY'],
        ['T9 Sports', 'T9 Sports Electric Bike', 'Electric Bike', 'T9'],
        ['T9 Sports LFP', 'T9 Sports LFP Electric Bike', 'Electric Bike', 'T9 LFP'],
        ['T9 Eco', 'T9 Eco Electric Bike', 'Electric Bike', 'T9 Eco'],
        ['Thrill Pro', 'Thrill Pro Electric Bike', 'Electric Bike', 'TP'],
        ['Thrill Pro LFP', 'Thrill Pro LFP Electric Bike', 'Electric Bike', 'TP LFP'],
        ['E8S M2', 'E8S M2 Electric Scooter', 'Electric Scooter', 'E8S'],
        ['E8S Pro', 'E8S Pro Electric Scooter', 'Electric Scooter', 'E8S Pro'],
        ['M6 K6', 'M6 K6 Electric Bike', 'Electric Bike', 'M6'],
        ['M6 NP', 'M6 NP Electric Bike', 'Electric Bike', 'M6 NP'],
        ['M6 Lithium NP', 'M6 Lithium NP Electric Bike', 'Electric Bike', 'M6 L'],
        ['Premium', 'Premium Electric Bike', 'Electric Bike', 'Premium'],
        ['W. Bike H2', 'W. Bike H2 Electric Bike', 'Electric Bike', 'W. Bike'],
    ];
    $r = $conn->query('SELECT COUNT(*) as c FROM `models`');
    $row = $r->fetch_assoc();
    if ($row['c'] == 0) {
        $stmt = $conn->prepare('INSERT INTO `models` (`model_code`,`model_name`,`category`,`short_code`) VALUES (?,?,?,?)');
        foreach ($models_seed as $m) {
            $stmt->bind_param('ssss', $m[0], $m[1], $m[2], $m[3]);
            $stmt->execute();
        }
        $stmt->close();
    }
    $r2 = $conn->query('SELECT COUNT(*) as c FROM `suppliers`');
    $row2 = $r2->fetch_assoc();
    if ($row2['c'] == 0) {
        $conn->query("INSERT INTO `suppliers` (`name`,`contact`,`address`) VALUES ('Default Supplier','0300-0000000','Pakistan')");
    }
    $r3 = $conn->query('SELECT COUNT(*) as c FROM `customers`');
    $row3 = $r3->fetch_assoc();
    if ($row3['c'] == 0) {
        $customers_seed = [
            ['Ahmed Ali', '0321-1234567', '35201-1234567-1', 1, 'Dera Ghazi Khan, Punjab'],
            ['Muhammad Usman', '0333-7654321', '35201-7654321-3', 0, 'Muzaffargarh, Punjab'],
            ['Bilal Hussain', '0345-9876543', '35201-9876543-5', 1, 'Rajanpur, Punjab'],
            ['Zafar Iqbal', '0312-4567890', '35201-4567890-7', 0, 'Layyah, Punjab'],
        ];
        $stmt = $conn->prepare('INSERT INTO `customers` (`name`,`phone`,`cnic`,`is_filer`,`address`) VALUES (?,?,?,?,?)');
        foreach ($customers_seed as $c) {
            $stmt->bind_param('sssis', $c[0], $c[1], $c[2], $c[3], $c[4]);
            $stmt->execute();
        }
        $stmt->close();
    }
    $r4 = $conn->query('SELECT COUNT(*) as c FROM `accessories`');
    $row4 = $r4->fetch_assoc();
    if ($row4['c'] == 0) {
        $accessories_seed = [
            ['Helmet', 'HLM001', 500, 750, 20],
            ['Charger 60V', 'CHR60V01', 1500, 2200, 15],
            ['Tyre Puncture Kit', 'TPK001', 300, 500, 30],
            ['Disc Lock', 'DLCK001', 800, 1200, 10],
            ['Basket', 'BSKT001', 600, 900, 25],
        ];
        $stmt = $conn->prepare('INSERT INTO `accessories` (`name`,`sku`,`purchase_price`,`selling_price`,`current_stock`) VALUES (?,?,?,?,?)');
        foreach ($accessories_seed as $acc) {
            $stmt->bind_param('ssddi', $acc[0], $acc[1], $acc[2], $acc[3], $acc[4]);
            $stmt->execute();
        }
        $stmt->close();
    }
    $conn->close();
    return true;
}

function get_setting($key)
{
    $conn = db_connect();
    if (!$conn)
        return null;
    $stmt = $conn->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $r = $stmt->get_result();
    $row = $r->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $row ? $row['setting_value'] : null;
}

function fmt_money($val)
{
    global $currency;
    return $currency . ' ' . number_format((float) $val, 2);
}

function fmt_date($d)
{
    if (!$d || $d === '0000-00-00')
        return '-';
    try {
        $dt = new DateTime($d);
        return $dt->format('d/m/Y');
    } catch (Exception $e) {
        return $d;
    }
}

function sanitize($val)
{
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

$db_exists = false;
$test_conn = db_connect(true);
if ($test_conn) {
    $r = $test_conn->query("SHOW DATABASES LIKE '$db_name'");
    if ($r && $r->num_rows > 0) {
        $test_conn->select_db($db_name);
        $r2 = $test_conn->query("SHOW TABLES LIKE 'settings'");
        if ($r2 && $r2->num_rows > 0) {
            $db_exists = true;
        }
    }
    $test_conn->close();
}
if (isset($_POST['do_install'])) {
    if (install_database()) {
        $db_exists = true;
        header('Location: index.php');
        exit;
    }
}
if ($db_exists) {
    $theme = get_setting('theme') ?? 'dark';
    $idle_timeout = (int) (get_setting('session_timeout_idle') ?? 2400);
    $absolute_timeout = (int) (get_setting('session_timeout_absolute') ?? 28800);
    if (!isset($_SESSION['user_id'])) {
        if (isset($_POST['do_login'])) {
            $uname = trim($_POST['username'] ?? '');
            $upass = $_POST['password'] ?? '';
            $captcha = $_POST['captcha_code'] ?? '';
            if (empty($captcha) || $_SESSION['captcha_code'] != $captcha) {
                record_failed_attempt();
                $login_error = 'Invalid CAPTCHA.';
            } else {
                $conn_temp = db_connect();
                $stmt = $conn_temp->prepare('SELECT id, password_hash, is_active, role_id FROM users WHERE username=? LIMIT 1');
                $stmt->bind_param('s', $uname);
                $stmt->execute();
                $u = $stmt->get_result()->fetch_assoc();
                if ($u && $u['is_active'] && password_verify($upass, $u['password_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $u['id'];
                    $_SESSION['login_time'] = time();
                    $_SESSION['last_active'] = time();
                    reset_attempts();
                    unset($_SESSION['captcha_code']);
                    $redirect = 'index.php';
                    $stmt_rp = $conn_temp->prepare('SELECT page FROM role_permissions WHERE role_id=? AND can_view=1 ORDER BY id LIMIT 1');
                    $stmt_rp->bind_param('i', $u['role_id']);
                    $stmt_rp->execute();
                    $rp_res = $stmt_rp->get_result()->fetch_assoc();
                    if ($rp_res) {
                        $redirect = 'index.php?page=' . $rp_res['page'];
                    }
                    header('Location: ' . $redirect);
                    exit;
                } else {
                    record_failed_attempt();
                    $login_error = 'Invalid username or password.';
                    unset($_SESSION['captcha_code']);
                }
                $conn_temp->close();
            }
        }
    } else {
        if (time() - $_SESSION['login_time'] > $absolute_timeout) {
            session_destroy();
            header('Location: index.php?msg=session_expired');
            exit;
        }
        if (time() - ($_SESSION['last_active'] ?? $_SESSION['login_time']) > $idle_timeout) {
            session_destroy();
            header('Location: index.php?msg=idle_logout');
            exit;
        }
        $_SESSION['last_active'] = time();
        if (isset($_GET['logout'])) {
            session_destroy();
            header('Location: index.php');
            exit;
        }
    }
}
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? '';
$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';
if ($db_exists && isset($_SESSION['user_id'])) {
    $conn = db_connect();
    $currency = get_setting('currency') ?? 'Rs.';
    $tax_rate = (float) (get_setting('tax_rate') ?? 0.1);
    $tax_on = get_setting('tax_on') ?? 'purchase_price';
    $protected_pages = ['purchase', 'inventory', 'sale', 'returns', 'payments', 'customers', 'suppliers', 'models', 'reports', 'customer_ledger', 'supplier_ledger', 'settings', 'roles', 'users', 'income_expense', 'accessories', 'quotations', 'installments'];
    if (in_array($page, $protected_pages)) {
        require_permission($conn, $page, 'view');
    }
    if ($page === 'roles' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_permission($conn, 'roles', 'edit');
        if (isset($_POST['save_role'])) {
            $id = (int) ($_POST['id'] ?? 0);
            $name = sanitize($_POST['name'] ?? '');
            $desc = sanitize($_POST['description'] ?? '');
            if (empty($name)) {
                $err = 'Role name cannot be empty.';
                goto end_roles_post;
            }
            if ($id == 1 && $name !== 'Administrator') {
                $name = 'Administrator';
            }
            if ($id) {
                $stmt = $conn->prepare('UPDATE roles SET name=?, description=? WHERE id=?');
                $stmt->bind_param('ssi', $name, $desc, $id);
                $stmt->execute();
            } else {
                $stmt = $conn->prepare('INSERT INTO roles (name, description) VALUES (?,?)');
                $stmt->bind_param('ss', $name, $desc);
                $stmt->execute();
                $id = $conn->insert_id;
            }
            $conn->query("DELETE FROM role_permissions WHERE role_id=$id");
            $all_pages_perm = ['dashboard', 'inventory', 'purchase', 'sale', 'customers', 'suppliers', 'models', 'reports', 'returns', 'payments', 'settings', 'roles', 'users', 'income_expense', 'accessories', 'quotations', 'installments'];
            $stmtp = $conn->prepare('INSERT INTO role_permissions (role_id, page, can_view, can_add, can_edit, can_delete) VALUES (?,?,?,?,?,?)');
            foreach ($all_pages_perm as $p) {
                $v = isset($_POST['perm'][$p]['view']) ? 1 : 0;
                $a = isset($_POST['perm'][$p]['add']) ? 1 : 0;
                $e = isset($_POST['perm'][$p]['edit']) ? 1 : 0;
                $d = isset($_POST['perm'][$p]['delete']) ? 1 : 0;
                $stmtp->bind_param('isiiii', $id, $p, $v, $a, $e, $d);
                $stmtp->execute();
            }
            $msg = 'Role and permissions saved successfully.';
            header('Location: index.php?page=roles&msg=' . urlencode($msg));
            exit;
        }
        if (isset($_POST['delete_role'])) {
            require_permission($conn, 'roles', 'delete');
            $id = (int) $_POST['id'];
            if ($id == 1) {
                $err = 'Administrator role cannot be deleted.';
            } else {
                $stmt_users = $conn->prepare('SELECT COUNT(*) FROM users WHERE role_id = ?');
                $stmt_users->bind_param('i', $id);
                $stmt_users->execute();
                $user_count = $stmt_users->get_result()->fetch_row()[0];
                if ($user_count > 0) {
                    $err = 'Cannot delete role: There are users assigned to this role.';
                } else {
                    $stmt = $conn->prepare('DELETE FROM roles WHERE id=?');
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $msg = 'Role deleted successfully.';
                }
            }
            header('Location: index.php?page=roles&msg=' . urlencode($msg) . '&err=' . urlencode($err));
            exit;
        }
        end_roles_post:;
    }
    if ($page === 'users' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_permission($conn, 'users', 'edit');
        if (isset($_POST['save_user'])) {
            $id = (int) ($_POST['id'] ?? 0);
            $username = sanitize($_POST['username'] ?? '');
            $full_name = sanitize($_POST['full_name'] ?? '');
            $role_id = (int) ($_POST['role_id'] ?? 2);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $pass = $_POST['password'] ?? '';
            if (empty($username) || empty($role_id)) {
                $err = 'Username and Role are required.';
                goto end_users_post;
            }
            if (!preg_match('/^(?=.*[!@#$%^&*-])(?=.*[0-9])(?=.*[A-Za-z]).{8,}$/', $pass) && !empty($pass)) {
                $err = 'Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.';
                goto end_users_post;
            }
            if ($id) {
                $user_q = $conn->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
                $user_q->bind_param('si', $username, $id);
                $user_q->execute();
                if ($user_q->get_result()->num_rows > 0) {
                    $err = 'Username already exists.';
                    goto end_users_post;
                }
                if ($pass) {
                    $hash = password_hash($pass, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare('UPDATE users SET username=?, full_name=?, role_id=?, is_active=?, password_hash=? WHERE id=?');
                    $stmt->bind_param('ssissi', $username, $full_name, $role_id, $is_active, $hash, $id);
                } else {
                    $stmt = $conn->prepare('UPDATE users SET username=?, full_name=?, role_id=?, is_active=? WHERE id=?');
                    $stmt->bind_param('ssiii', $username, $full_name, $role_id, $is_active, $id);
                }
                $stmt->execute();
                $msg = 'User updated successfully.';
            } else {
                if (empty($pass)) {
                    $err = 'Password is required for new users.';
                    goto end_users_post;
                }
                $user_q = $conn->prepare('SELECT id FROM users WHERE username = ?');
                $user_q->bind_param('s', $username);
                $user_q->execute();
                if ($user_q->get_result()->num_rows > 0) {
                    $err = 'Username already exists.';
                    goto end_users_post;
                }
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('INSERT INTO users (username, password_hash, full_name, role_id, is_active) VALUES (?,?,?,?,?)');
                $stmt->bind_param('sssii', $username, $hash, $full_name, $role_id, $is_active);
                $stmt->execute();
                $msg = 'User added successfully.';
            }
            header('Location: index.php?page=users&msg=' . urlencode($msg) . '&err=' . urlencode($err));
            exit;
        }
        if (isset($_POST['delete_user'])) {
            require_permission($conn, 'users', 'delete');
            $id = (int) $_POST['id'];
            if ($id == 1 || $id == $_SESSION['user_id']) {
                $err = 'Cannot delete administrative or currently logged-in user.';
            } else {
                $stmt = $conn->prepare('DELETE FROM users WHERE id=?');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $msg = 'User deleted successfully.';
            }
            header('Location: index.php?page=users&msg=' . urlencode($msg) . '&err=' . urlencode($err));
            exit;
        }
        end_users_post:;
    }
    if ($page === 'income_expense' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_permission($conn, 'income_expense', 'add');
        if (isset($_POST['save_entry'])) {
            $id = (int) ($_POST['id'] ?? 0);
            $entry_date = sanitize($_POST['entry_date'] ?? date('Y-m-d'));
            $type = sanitize($_POST['type'] ?? 'expense');
            $category = sanitize($_POST['category'] ?? '');
            $amount = (float) ($_POST['amount'] ?? 0);
            $payment_method = sanitize($_POST['payment_method'] ?? 'cash');
            $reference = sanitize($_POST['reference'] ?? '');
            $notes = sanitize($_POST['notes'] ?? '');
            $created_by = $_SESSION['user_id'];
            if (empty($entry_date) || empty($type) || empty($category) || $amount <= 0) {
                $err = 'All required fields must be filled and amount must be positive.';
                goto end_income_expense_post;
            }
            if ($id) {
                require_permission($conn, 'income_expense', 'edit');
                $stmt = $conn->prepare('UPDATE income_expenses SET entry_date=?, type=?, category=?, amount=?, payment_method=?, reference=?, notes=? WHERE id=?');
                $stmt->bind_param('sssdsssi', $entry_date, $type, $category, $amount, $payment_method, $reference, $notes, $id);
            } else {
                $stmt = $conn->prepare('INSERT INTO income_expenses (entry_date, type, category, amount, payment_method, reference, notes, created_by) VALUES (?,?,?,?,?,?,?,?)');
                $stmt->bind_param('sssdsssi', $entry_date, $type, $category, $amount, $payment_method, $reference, $notes, $created_by);
            }
            $stmt->execute();
            $msg = 'Entry saved successfully.';
            header('Location: index.php?page=income_expense&msg=' . urlencode($msg) . '&err=' . urlencode($err));
            exit;
        }
        if (isset($_POST['delete_entry'])) {
            require_permission($conn, 'income_expense', 'delete');
            $id = (int) $_POST['id'];
            $stmt = $conn->prepare('DELETE FROM income_expenses WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $msg = 'Entry deleted successfully.';
            header('Location: index.php?page=income_expense&msg=' . urlencode($msg) . '&err=' . urlencode($err));
            exit;
        }
        end_income_expense_post:;
    }
    if (isset($_POST['toggle_theme'])) {
        $new_theme = ($theme === 'dark') ? 'light' : 'dark';
        $stmt = $conn->prepare("UPDATE settings SET setting_value=? WHERE setting_key='theme'");
        $stmt->bind_param('s', $new_theme);
        $stmt->execute();
        $stmt->close();
        header('Location: index.php?' . http_build_query($_GET));
        exit;
    }
    if ($page === 'purchase' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_purchase'])) {
        require_permission($conn, 'purchase', 'add');
        $order_date = sanitize($_POST['order_date'] ?? date('Y-m-d'));
        $inventory_date = sanitize($_POST['inventory_date'] ?? date('Y-m-d'));
        $supplier_id = (int) ($_POST['supplier_id'] ?? 0);
        $po_notes = sanitize($_POST['po_notes'] ?? '');
        $bikes_data = $_POST['bikes'] ?? [];
        $payments_data = $_POST['payments'] ?? [];
        if (empty($order_date) || empty($inventory_date) || $supplier_id <= 0 || empty($bikes_data)) {
            $err = 'Purchase order requires date, supplier and at least one bike.';
            goto end_purchase_post;
        }
        $total_units = count($bikes_data);
        $po_total_amount = 0;
        foreach ($bikes_data as $b) {
            $po_total_amount += (float) ($b['purchase_price'] ?? 0);
        }
        $conn->begin_transaction();
        try {
            $po_stmt = $conn->prepare('INSERT INTO purchase_orders (order_date,supplier_id,total_units,total_amount,notes) VALUES (?,?,?,?,?)');
            $po_stmt->bind_param('sidss', $order_date, $supplier_id, $total_units, $po_total_amount, $po_notes);
            $po_stmt->execute();
            $po_id = $conn->insert_id;
            $po_stmt->close();
            $bike_stmt = $conn->prepare("INSERT INTO bikes (purchase_order_id,order_date,inventory_date,chassis_number,motor_number,model_id,color,purchase_price,tax_amount,status,safeguard_notes,notes) VALUES (?,?,?,?,?,?,?,?,?,'in_stock',?,?)");
            $saved_count = 0;
            $errors_list = [];
            foreach ($bikes_data as $b) {
                $chassis = sanitize($b['chassis'] ?? '');
                $motor = sanitize($b['motor'] ?? '');
                $model_id = (int) ($b['model_id'] ?? 0);
                $color = sanitize($b['color'] ?? '');
                $pp = (float) ($b['purchase_price'] ?? 0);
                $safe_notes = sanitize($b['safeguard_notes'] ?? '');
                $bnotes = sanitize($b['notes'] ?? '');
                if (empty($chassis) || $model_id <= 0 || $pp <= 0) {
                    $errors_list[] = 'Bike entry requires Chassis, Model, and Purchase Price. Skipping incomplete bike.';
                    continue;
                }
                $tax = ($pp * $tax_rate);
                $bike_stmt->bind_param('issssisddss', $po_id, $order_date, $inventory_date, $chassis, $motor, $model_id, $color, $pp, $tax, $safe_notes, $bnotes);
                if (!$bike_stmt->execute()) {
                    $errors_list[] = "Chassis $chassis: " . $bike_stmt->error;
                } else {
                    $saved_count++;
                }
            }
            $bike_stmt->close();
            foreach ($payments_data as $p) {
                $pay_type = sanitize($p['payment_type'] ?? 'cash');
                $pay_amount = (float) ($p['amount'] ?? 0);
                $chq_num = $pay_type === 'cheque' ? sanitize($p['cheque_number'] ?? '') : null;
                $bank_name = $pay_type === 'cheque' ? sanitize($p['bank_name'] ?? '') : null;
                $chq_date = $pay_type === 'cheque' && !empty($p['cheque_date']) ? $p['cheque_date'] : null;
                if ($pay_amount > 0) {
                    $sup_r = $conn->query("SELECT name FROM suppliers WHERE id=$supplier_id");
                    $sup_row = $sup_r ? $sup_r->fetch_assoc() : null;
                    $party = $sup_row ? $sup_row['name'] : 'Unknown Supplier';
                    $pay_stmt = $conn->prepare("INSERT INTO payments (payment_date, payment_type, amount, cheque_number, bank_name, cheque_date, transaction_type, reference_id, party_name, notes) VALUES (?,?,?,?,?,?,'supplier_payment',?,?,?)");
                    $pay_stmt->bind_param('ssdsssiss',$order_date, $pay_type, $pay_amount, $chq_num, $bank_name, $chq_date, $po_id, $party, $po_notes);
                    $pay_stmt->execute();
                    $pay_stmt->close();
                }
            }
            $conn->commit();
            if (!empty($errors_list)) {
                $err = "Saved $saved_count bikes. Some errors occurred: " . implode('; ', $errors_list);
            } else {
                $msg = "Purchase order saved. $saved_count bike(s) added to inventory.";
            }
        } catch (Exception $e) {
            $conn->rollback();
            $err = 'Transaction failed: ' . $e->getMessage();
        }
        header('Location: index.php?page=purchase&msg=' . urlencode($msg) . '&err=' . urlencode($err));
        exit;
        end_purchase_post:;
    }
    if ($page === 'suppliers' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'add') {
            require_permission($conn, 'suppliers', 'add');
            $name = sanitize($_POST['name'] ?? '');
            $contact = sanitize($_POST['contact'] ?? '');
            $address = sanitize($_POST['address'] ?? '');
            if (empty($name)) {
                $err = 'Supplier name is required.';
            } else {
                $st = $conn->prepare('INSERT INTO suppliers (name,contact,address) VALUES (?,?,?)');
                $st->bind_param('sss', $name, $contact, $address);
                $st->execute();
                $st->close();
                $msg = 'Supplier added successfully.';
            }
        } elseif ($action === 'edit') {
            require_permission($conn, 'suppliers', 'edit');
            $sid = (int) ($_POST['id'] ?? 0);
            $name = sanitize($_POST['name'] ?? '');
            $contact = sanitize($_POST['contact'] ?? '');
            $address = sanitize($_POST['address'] ?? '');
            if (empty($name) || $sid <= 0) {
                $err = 'Supplier ID and name are required.';
            } else {
                $st = $conn->prepare('UPDATE suppliers SET name=?,contact=?,address=? WHERE id=?');
                $st->bind_param('sssi', $name, $contact, $address, $sid);
                $st->execute();
                $st->close();
                $msg = 'Supplier updated successfully.';
            }
        } elseif ($action === 'delete') {
            require_permission($conn, 'suppliers', 'delete');
            $sid = (int) ($_POST['id'] ?? 0);
            $stmt_check = $conn->prepare('SELECT COUNT(*) FROM purchase_orders WHERE supplier_id = ?');
            $stmt_check->bind_param('i', $sid);
            $stmt_check->execute();
            $order_count = $stmt_check->get_result()->fetch_row()[0];
            if ($order_count > 0) {
                $err = 'Cannot delete supplier: There are associated purchase orders.';
            } else {
                $st = $conn->prepare('DELETE FROM suppliers WHERE id=?');
                $st->bind_param('i', $sid);
                $st->execute();
                $st->close();
                $msg = 'Supplier deleted.';
            }
        }
        header('Location: index.php?page=suppliers&msg=' . urlencode($msg) . '&err=' . urlencode($err));
        exit;
    }
    if ($page === 'customers' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'add') {
            require_permission($conn, 'customers', 'add');
            $name = sanitize($_POST['name'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $cnic = sanitize($_POST['cnic'] ?? '');
            $is_filer = isset($_POST['is_filer']) ? 1 : 0;
            $address = sanitize($_POST['address'] ?? '');
            if (empty($name)) {
                $err = 'Customer name is required.';
            } else {
                $st = $conn->prepare('INSERT INTO customers (name,phone,cnic,is_filer,address) VALUES (?,?,?,?,?)');
                $st->bind_param('sssis', $name, $phone, $cnic, $is_filer, $address);
                $st->execute();
                $st->close();
                $msg = 'Customer added.';
            }
        } elseif ($action === 'edit') {
            require_permission($conn, 'customers', 'edit');
            $cid = (int) ($_POST['id'] ?? 0);
            $name = sanitize($_POST['name'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $cnic = sanitize($_POST['cnic'] ?? '');
            $is_filer = isset($_POST['is_filer']) ? 1 : 0;
            $address = sanitize($_POST['address'] ?? '');
            if (empty($name) || $cid <= 0) {
                $err = 'Customer ID and name are required.';
            } else {
                $st = $conn->prepare('UPDATE customers SET name=?,phone=?,cnic=?,is_filer=?,address=? WHERE id=?');
                $st->bind_param('ssiisi', $name, $phone, $cnic, $is_filer, $address, $cid);
                $st->execute();
                $st->close();
                $msg = 'Customer updated.';
            }
        } elseif ($action === 'delete') {
            require_permission($conn, 'customers', 'delete');
            $cid = (int) ($_POST['id'] ?? 0);
            $stmt_check = $conn->prepare('SELECT COUNT(*) FROM bikes WHERE customer_id = ?');
            $stmt_check->bind_param('i', $cid);
            $stmt_check->execute();
            $bike_count = $stmt_check->get_result()->fetch_row()[0];
            if ($bike_count > 0) {
                $err = 'Cannot delete customer: There are associated bike sales.';
            } else {
                $st = $conn->prepare('DELETE FROM customers WHERE id=?');
                $st->bind_param('i', $cid);
                $st->execute();
                $st->close();
                $msg = 'Customer deleted.';
            }
        }
        header('Location: index.php?page=customers&msg=' . urlencode($msg) . '&err=' . urlencode($err));
        exit;
    }
    if ($page === 'models' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'add') {
            require_permission($conn, 'models', 'add');
            $mc = sanitize($_POST['model_code'] ?? '');
            $mn = sanitize($_POST['model_name'] ?? '');
            $cat = sanitize($_POST['category'] ?? '');
            $sc = sanitize($_POST['short_code'] ?? '');
            if (empty($mc) || empty($mn)) {
                $err = 'Model code and name are required.';
            } else {
                $st = $conn->prepare('INSERT INTO models (model_code,model_name,category,short_code) VALUES (?,?,?,?)');
                $st->bind_param('ssss', $mc, $mn, $cat, $sc);
                $st->execute();
                $st->close();
                $msg = 'Model added.';
            }
        } elseif ($action === 'edit') {
            require_permission($conn, 'models', 'edit');
            $mid = (int) ($_POST['id'] ?? 0);
            $mc = sanitize($_POST['model_code'] ?? '');
            $mn = sanitize($_POST['model_name'] ?? '');
            $cat = sanitize($_POST['category'] ?? '');
            $sc = sanitize($_POST['short_code'] ?? '');
            if (empty($mc) || empty($mn) || $mid <= 0) {
                $err = 'Model ID, code and name are required.';
            } else {
                $st = $conn->prepare('UPDATE models SET model_code=?,model_name=?,category=?,short_code=? WHERE id=?');
                $st->bind_param('ssssi', $mc, $mn, $cat, $sc, $mid);
                $st->execute();
                $st->close();
                $msg = 'Model updated.';
            }
        } elseif ($action === 'delete') {
            require_permission($conn, 'models', 'delete');
            $mid = (int) ($_POST['id'] ?? 0);
            $stmt_check = $conn->prepare('SELECT COUNT(*) FROM bikes WHERE model_id = ?');
            $stmt_check->bind_param('i', $mid);
            $stmt_check->execute();
            $bike_count = $stmt_check->get_result()->fetch_row()[0];
            if ($bike_count > 0) {
                $err = 'Cannot delete model: There are associated bikes.';
            } else {
                $st = $conn->prepare('DELETE FROM models WHERE id=?');
                $st->bind_param('i', $mid);
                $st->execute();
                $st->close();
                $msg = 'Model deleted.';
            }
        }
        header('Location: index.php?page=models&msg=' . urlencode($msg) . '&err=' . urlencode($err));
        exit;
    }
    if ($page === 'accessories' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'add') {
            require_permission($conn, 'accessories', 'add');
            $name = sanitize($_POST['name'] ?? '');
            $sku = sanitize($_POST['sku'] ?? '');
            $purchase_price = (float) ($_POST['purchase_price'] ?? 0);
            $selling_price = (float) ($_POST['selling_price'] ?? 0);
            $current_stock = (int) ($_POST['current_stock'] ?? 0);
            if (empty($name) || empty($sku) || $purchase_price < 0 || $selling_price < 0 || $current_stock < 0) {
                $err = 'All fields are required and prices/stock must be non-negative.';
            } else {
                $st = $conn->prepare('INSERT INTO accessories (name,sku,purchase_price,selling_price,current_stock) VALUES (?,?,?,?,?)');
                $st->bind_param('ssddi', $name, $sku, $purchase_price, $selling_price, $current_stock);
                $st->execute();
                $st->close();
                $msg = 'Accessory added.';
            }
        } elseif ($action === 'edit') {
            require_permission($conn, 'accessories', 'edit');
            $acc_id = (int) ($_POST['id'] ?? 0);
            $name = sanitize($_POST['name'] ?? '');
            $sku = sanitize($_POST['sku'] ?? '');
            $purchase_price = (float) ($_POST['purchase_price'] ?? 0);
            $selling_price = (float) ($_POST['selling_price'] ?? 0);
            $current_stock = (int) ($_POST['current_stock'] ?? 0);
            if (empty($name) || empty($sku) || $purchase_price < 0 || $selling_price < 0 || $current_stock < 0 || $acc_id <= 0) {
                $err = 'All fields are required and prices/stock must be non-negative.';
            } else {
                $st = $conn->prepare('UPDATE accessories SET name=?,sku=?,purchase_price=?,selling_price=?,current_stock=? WHERE id=?');
                $st->bind_param('ssddii', $name, $sku, $purchase_price, $selling_price, $current_stock, $acc_id);
                $st->execute();
                $st->close();
                $msg = 'Accessory updated.';
            }
        } elseif ($action === 'delete') {
            require_permission($conn, 'accessories', 'delete');
            $acc_id = (int) ($_POST['id'] ?? 0);
            $stmt_check = $conn->prepare('SELECT COUNT(*) FROM sale_accessories WHERE accessory_id = ?');
            $stmt_check->bind_param('i', $acc_id);
            $stmt_check->execute();
            $sale_count = $stmt_check->get_result()->fetch_row()[0];
            if ($sale_count > 0) {
                $err = 'Cannot delete accessory: It has been sold with bikes.';
            } else {
                $st = $conn->prepare('DELETE FROM accessories WHERE id=?');
                $st->bind_param('i', $acc_id);
                $st->execute();
                $st->close();
                $msg = 'Accessory deleted.';
            }
        }
        header('Location: index.php?page=accessories&msg=' . urlencode($msg) . '&err=' . urlencode($err));
        exit;
    }
    if ($page === 'quotations' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['save_quote'])) {
            require_permission($conn, 'quotations', 'add');
            $id = (int) ($_POST['id'] ?? 0);
            $quote_date = sanitize($_POST['quote_date'] ?? date('Y-m-d'));
            $customer_id = (int) ($_POST['customer_id'] ?? 0);
            $bike_id = (int) ($_POST['bike_id'] ?? 0);
            $quoted_price = (float) ($_POST['quoted_price'] ?? 0);
            $valid_until = sanitize($_POST['valid_until'] ?? '');
            $notes = sanitize($_POST['notes'] ?? '');
            $accessories_data = $_POST['accessories'] ?? [];
            $created_by = $_SESSION['user_id'];
            if (empty($quote_date) || $customer_id <= 0 || $bike_id <= 0 || $quoted_price <= 0 || empty($valid_until)) {
                $err = 'All required fields must be filled.';
                goto end_quotations_post;
            }
            $accessories_json = json_encode($accessories_data);
            if ($id) {
                require_permission($conn, 'quotations', 'edit');
                $stmt = $conn->prepare('UPDATE quotations SET quote_date=?, customer_id=?, bike_id=?, accessories_json=?, quoted_price=?, valid_until=?, notes=? WHERE id=?');
                $stmt->bind_param('siisdsis', $quote_date, $customer_id, $bike_id, $accessories_json, $quoted_price, $valid_until, $notes, $id);
            } else {
                $stmt = $conn->prepare('INSERT INTO quotations (quote_date, customer_id, bike_id, accessories_json, quoted_price, valid_until, notes, created_by) VALUES (?,?,?,?,?,?,?,?)');
                $stmt->bind_param('siisdsis', $quote_date, $customer_id, $bike_id, $accessories_json, $quoted_price, $valid_until, $notes, $created_by);
            }
            $stmt->execute();
            $msg = 'Quotation saved successfully.';
            header('Location: index.php?page=quotations&msg=' . urlencode($msg) . '&err=' . urlencode($err));
            exit;
        }
        if (isset($_POST['convert_quote_to_sale'])) {
            require_permission($conn, 'quotations', 'edit');
            require_permission($conn, 'sale', 'add');
            $quote_id = (int) ($_POST['quote_id'] ?? 0);
            if ($quote_id <= 0) {
                $err = 'Invalid quotation ID.';
                goto end_quotations_post;
            }
            $quote_r = $conn->query("SELECT * FROM quotations WHERE id=$quote_id AND status='pending'");
            $quote = $quote_r ? $quote_r->fetch_assoc() : null;
            if (!$quote) {
                $err = 'Quotation not found or already converted/cancelled.';
                goto end_quotations_post;
            }
            $bike_id = $quote['bike_id'];
            $selling_price = $quote['quoted_price'];
            $customer_id = $quote['customer_id'];
            $accessories_data = json_decode($quote['accessories_json'], true);
            $sale_date = date('Y-m-d');
            $conn->begin_transaction();
            try {
                $br = $conn->query("SELECT * FROM bikes WHERE id=$bike_id AND status='in_stock'");
                $bike = $br ? $br->fetch_assoc() : null;
                if (!$bike) {
                    throw new Exception('Bike not found or already sold.');
                }
                $base = ($tax_on === 'selling_price') ? $selling_price : $bike['purchase_price'];
                $tax_amount = ($base * $tax_rate);
                $margin = $selling_price - $bike['purchase_price'] - $tax_amount;
                $st = $conn->prepare("UPDATE bikes SET selling_price=?,selling_date=?,customer_id=?,tax_amount=?,margin=?,status='sold' WHERE id=?");
                $st->bind_param('dsiddi', $selling_price, $sale_date, $customer_id, $tax_amount, $margin, $bike_id);
                $st->execute();
                if (!empty($accessories_data)) {
                    $sa_stmt = $conn->prepare('INSERT INTO sale_accessories (bike_id, accessory_id, quantity, unit_price, discount_amount, final_price) VALUES (?,?,?,?,?,?)');
                    foreach ($accessories_data as $acc) {
                        $acc_input = $acc['id'] ?? '';
                        $qty = (int) ($acc['quantity'] ?? 0);
                        $unit_p = (float) ($acc['unit_price'] ?? 0);
                        $disc = (float) ($acc['discount'] ?? 0);
                        $final_p = (float) ($acc['final_price'] ?? 0);

                        if (!empty($acc_input) && $qty > 0) {
                            if (!is_numeric($acc_input)) {
                                $new_name = sanitize($acc_input);
                                $dummy_sku = 'CST-' . time() . '-' . rand(10, 99);
                                $ins = $conn->prepare('INSERT INTO accessories (name, sku, current_stock) VALUES (?, ?, 0)');
                                $ins->bind_param('ss', $new_name, $dummy_sku);
                                $ins->execute();
                                $acc_id = $conn->insert_id;
                            } else {
                                $acc_id = (int) $acc_input;
                            }
                            if ($acc_id > 0) {
                                $sa_stmt->bind_param('iiiddd', $bike_id, $acc_id, $qty, $unit_p, $disc, $final_p);
                                $sa_stmt->execute();
                                $conn->query("UPDATE accessories SET current_stock = current_stock - $qty WHERE id = $acc_id");
                            }
                        }
                    }
                }
                $cust_r = $conn->query("SELECT name FROM customers WHERE id=$customer_id");
                $cust_row = $cust_r ? $cust_r->fetch_assoc() : null;
                $party_name = $cust_row ? $cust_row['name'] : 'Walk-in Customer';
                $payment_notes = "Sale from Quotation #$quote_id";
                $pay_st = $conn->prepare("INSERT INTO payments (payment_date, payment_type, amount, transaction_type, reference_id, party_name, notes) VALUES (?,'cash',?,'sale',?,?,?)");
                $pay_st->bind_param('sdiss', $sale_date, $selling_price, $bike_id, $party_name, $payment_notes);
                $pay_st->execute();

                $led_st = $conn->prepare("INSERT INTO ledger (entry_date,entry_type,amount,party_type,party_id,description,reference_type,reference_id,balance) VALUES (?,'debit',?,'customer',?,?,'sale',?,?)");
                $desc = 'Sale of Chassis: ' . $bike['chassis_number'] . ' from Quote #' . $quote_id;
                $led_st->bind_param('sdisid', $sale_date, $selling_price, $customer_id, $desc, $bike_id, $selling_price);
                $led_st->execute();

                $led_dp_st = $conn->prepare("INSERT INTO ledger (entry_date,entry_type,amount,party_type,party_id,description,reference_type,reference_id,balance) VALUES (?,'credit',?,'customer',?,?,'payment',?,?)");
                $desc_dp = 'Payment for Quote #' . $quote_id;
                $led_dp_st->bind_param('sdisid', $sale_date, $selling_price, $customer_id, $desc_dp, $bike_id, $selling_price);
                $led_dp_st->execute();

                $conn->query("UPDATE quotations SET status='converted' WHERE id=$quote_id");
                $conn->commit();
                $_SESSION['last_sale_bike_id'] = $bike_id;
                $msg = 'Quotation converted to sale successfully. Margin: ' . fmt_money($margin);
            } catch (Exception $e) {
                $conn->rollback();
                $err = 'Failed to convert quotation to sale: ' . $e->getMessage();
            }
            header('Location: index.php?page=quotations&msg=' . urlencode($msg) . '&err=' . urlencode($err));
            exit;
        }
        if (isset($_POST['delete_quote'])) {
            require_permission($conn, 'quotations', 'delete');
            $id = (int) $_POST['id'];
            $stmt = $conn->prepare('DELETE FROM quotations WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $msg = 'Quotation deleted successfully.';
            header('Location: index.php?page=quotations&msg=' . urlencode($msg) . '&err=' . urlencode($err));
            exit;
        }
        end_quotations_post:;
    }
    if ($page === 'sale' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_sale'])) {
        require_permission($conn, 'sale', 'add');
        $bike_id = (int) ($_POST['bike_id'] ?? 0);
        $selling_price = (float) ($_POST['selling_price'] ?? 0);
        $selling_date = sanitize($_POST['selling_date'] ?? date('Y-m-d'));
        $customer_id = (int) ($_POST['customer_id'] ?? 0);
        $down_payment = (float) ($_POST['down_payment'] ?? 0);
        $total_installments = (int) ($_POST['total_installments'] ?? 0);
        $installment_amount = (float) ($_POST['installment_amount'] ?? 0);
        $first_due_date = sanitize($_POST['first_due_date'] ?? '');
        $payment_method_dp = sanitize($_POST['payment_method_dp'] ?? 'cash');
        $cheque_number_dp = sanitize($_POST['cheque_number_dp'] ?? '');
        $bank_name_dp = sanitize($_POST['bank_name_dp'] ?? '');
        $cheque_date_dp = !empty($_POST['cheque_date_dp']) ? $_POST['cheque_date_dp'] : null;
        $sale_notes = sanitize($_POST['sale_notes'] ?? '');
        $selected_accessories = $_POST['selected_accessories'] ?? [];
        if ($bike_id && $selling_price > 0 && $selling_date && $down_payment >= 0) {
            $conn->begin_transaction();
            try {
                $br = $conn->query("SELECT * FROM bikes WHERE id=$bike_id AND status='in_stock'");
                $bike = $br ? $br->fetch_assoc() : null;
                if (!$bike) {
                    throw new Exception('Bike not found or already sold.');
                }
                $base = ($tax_on === 'selling_price') ? $selling_price : $bike['purchase_price'];
                $tax_amount = ($base * $tax_rate);
                $margin = $selling_price - $bike['purchase_price'] - $tax_amount;
                $st = $conn->prepare("UPDATE bikes SET selling_price=?,selling_date=?,customer_id=?,tax_amount=?,margin=?,status='sold',notes=? WHERE id=?");
                $st->bind_param('dsiddsi', $selling_price, $selling_date, $customer_id, $tax_amount, $margin, $sale_notes, $bike_id);
                $st->execute();
                $st->close();
                if (!empty($selected_accessories)) {
                    $sa_stmt = $conn->prepare('INSERT INTO sale_accessories (bike_id, accessory_id, quantity, unit_price, discount_amount, final_price) VALUES (?,?,?,?,?,?)');
                    foreach ($selected_accessories as $key => $data) {
                        $acc_input = $data['id'] ?? '';
                        $qty = (int) ($data['quantity'] ?? 0);
                        $unit_price = (float) ($data['unit_price'] ?? 0);
                        $discount = (float) ($data['discount'] ?? 0);
                        $final_price = (float) ($data['final_price'] ?? 0);

                        if (!empty($acc_input) && $qty > 0) {
                            if (!is_numeric($acc_input)) {
                                $new_name = sanitize($acc_input);
                                $dummy_sku = 'CST-' . time() . '-' . rand(10, 99);
                                $ins = $conn->prepare('INSERT INTO accessories (name, sku, current_stock) VALUES (?, ?, 0)');
                                $ins->bind_param('ss', $new_name, $dummy_sku);
                                $ins->execute();
                                $acc_id = $conn->insert_id;
                            } else {
                                $acc_id = (int) $acc_input;
                            }
                            if ($acc_id > 0) {
                                $sa_stmt->bind_param('iiiddd', $bike_id, $acc_id, $qty, $unit_price, $discount, $final_price);
                                $sa_stmt->execute();
                                $conn->query("UPDATE accessories SET current_stock = current_stock - $qty WHERE id = $acc_id");
                            }
                        }
                    }
                }
                $cust_r = $conn->query("SELECT name FROM customers WHERE id=$customer_id");
                $cust_row = $cust_r ? $cust_r->fetch_assoc() : null;
                $party_name = $cust_row ? $cust_row['name'] : 'Walk-in Customer';
                $payment_notes = 'Down Payment for Chassis: ' . $bike['chassis_number'];
                $pay_st = $conn->prepare("INSERT INTO payments (payment_date, payment_type, amount, cheque_number, bank_name, cheque_date, transaction_type, reference_id, party_name, notes) VALUES (?,?,?,?,?,?,'sale',?,?,?)");
                $pay_st->bind_param('ssdsssiss',$selling_date, $payment_method_dp, $down_payment, $cheque_number_dp, $bank_name_dp, $cheque_date_dp, $bike_id, $party_name, $payment_notes);
                $pay_st->execute();
                $dp_payment_id = $conn->insert_id;
                $pay_st->close();

                $total_acc_price = 0;
                if (!empty($selected_accessories)) {
                    foreach ($selected_accessories as $key => $data) {
                        $total_acc_price += (float) ($data['final_price'] ?? 0);
                    }
                }
                $total_sale_amount = $selling_price + $total_acc_price;

                $led_st = $conn->prepare("INSERT INTO ledger (entry_date,entry_type,amount,party_type,party_id,description,reference_type,reference_id,balance) VALUES (?,'debit',?,'customer',?,?,'sale',?,?)");
                $desc = 'Sale of Chassis: ' . $bike['chassis_number'];
                $led_st->bind_param('sdisid', $selling_date, $total_sale_amount, $customer_id, $desc, $bike_id, $total_sale_amount);
                $led_st->execute();
                $led_st->close();

                if ($down_payment > 0) {
                    $led_dp_st = $conn->prepare("INSERT INTO ledger (entry_date,entry_type,amount,party_type,party_id,description,reference_type,reference_id,balance) VALUES (?,'credit',?,'customer',?,?,'down_payment',?,?)");
                    $desc_dp = 'Down Payment for Chassis: ' . $bike['chassis_number'];
                    $led_dp_st->bind_param('sdisid', $selling_date, $down_payment, $customer_id, $desc_dp, $bike_id, $down_payment);
                    $led_dp_st->execute();
                    $led_dp_st->close();
                }

                $remaining_balance = $total_sale_amount - $down_payment;

                if ($customer_id == 0 && round($remaining_balance, 2) > 0) {
                    throw new Exception('Walk-in customers must pay the full amount upfront. Partial payments are not allowed.');
                }

                if ($total_installments > 0 && $installment_amount > 0 && $remaining_balance > 0) {
                    $installment_per_month = $remaining_balance / $total_installments;
                    $current_date = new DateTime($first_due_date);
                    $inst_stmt = $conn->prepare("INSERT INTO installments (bike_id, customer_id, due_date, installment_amount, status, notes) VALUES (?,?,?,?,'pending',?)");
                    for ($i = 0; $i < $total_installments; $i++) {
                        $due_date = $current_date->format('Y-m-d');
                        $inst_notes = 'Installment ' . ($i + 1) . ' for Chassis ' . $bike['chassis_number'];
                        $inst_stmt->bind_param('iisds', $bike_id, $customer_id, $due_date, $installment_per_month, $inst_notes);
                        $inst_stmt->execute();
                        $current_date->modify('+1 month');
                    }
                    $msg .= ' Installment plan created.';
                }
                $conn->commit();
                $_SESSION['last_sale_bike_id'] = $bike_id;
                $msg = 'Sale recorded successfully. Margin: ' . fmt_money($margin) . '. ' . $msg;
            } catch (Exception $e) {
                $conn->rollback();
                $err = 'Sale transaction failed: ' . $e->getMessage();
            }
        } else {
            $err = 'Please fill all required fields correctly.';
        }
        header('Location: index.php?page=sale&msg=' . urlencode($msg) . '&err=' . urlencode($err));
        exit;
    }
    if ($page === 'returns' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_return'])) {
        require_permission($conn, 'returns', 'add');
        $bike_id = (int) ($_POST['bike_id'] ?? 0);
        $return_date = sanitize(!empty($_POST['return_date']) ? $_POST['return_date'] : date('Y-m-d'));
        $return_amount = (float) ($_POST['return_amount'] ?? 0);
        $refund_method = sanitize($_POST['refund_method'] ?? 'cash');
        $cheque_number = sanitize($_POST['cheque_number'] ?? '');
        $bank_name = sanitize($_POST['bank_name'] ?? '');
        $cheque_date = !empty($_POST['cheque_date']) ? $_POST['cheque_date'] : null;
        $return_notes = sanitize($_POST['return_notes'] ?? '');
        if ($bike_id <= 0 || empty($return_date) || $return_amount < 0) {
            $err = 'Please fill all required fields correctly.';
            goto end_returns_post;
        }
        $conn->begin_transaction();
        try {
            $bike_q = $conn->query("SELECT b.chassis_number, b.customer_id, b.selling_price, c.name AS cust_name FROM bikes b LEFT JOIN customers c ON b.customer_id=c.id WHERE b.id=$bike_id");
            $bike_info = $bike_q ? $bike_q->fetch_assoc() : null;
            if (!$bike_info) {
                throw new Exception('Bike not found for return.');
            }

            $acc_q = $conn->query("SELECT SUM(final_price) as total_acc FROM sale_accessories WHERE bike_id=$bike_id");
            $acc_total = $acc_q ? (float) ($acc_q->fetch_assoc()['total_acc'] ?? 0) : 0;
            $full_reversal_amount = $bike_info['selling_price'] + $acc_total;

            $st = $conn->prepare("UPDATE bikes SET status='returned', return_date=?, return_amount=?, return_notes=? WHERE id=? AND status='sold'");
            $st->bind_param('sdsi', $return_date, $return_amount, $return_notes, $bike_id);
            $st->execute();
            if ($st->affected_rows === 0) {
                throw new Exception("Bike not found or not in 'sold' status to be returned.");
            }
            $st->close();

            $party_name = $bike_info['cust_name'] ?? 'Unknown Customer';
            $pay_st = $conn->prepare("INSERT INTO payments (payment_date, payment_type, amount, cheque_number, bank_name, cheque_date, transaction_type, reference_id, party_name, notes) VALUES (?,?,?,?,?,?,'customer_refund',?,?,?)");
            $pay_st->bind_param('ssdsssiss',$return_date, $refund_method, $return_amount, $cheque_number, $bank_name, $cheque_date, $bike_id, $party_name, $return_notes);
            $pay_st->execute();
            $pay_st->close();

            $led_st1 = $conn->prepare("INSERT INTO ledger (entry_date,entry_type,amount,party_type,party_id,description,reference_type,reference_id,balance) VALUES (?,'credit',?,'customer',?,?,'return_reversal',?,?)");
            $desc1 = 'Bike Return (Reversal) for Chassis: ' . $bike_info['chassis_number'];
            $led_st1->bind_param('sdisid', $return_date, $full_reversal_amount, $bike_info['customer_id'], $desc1, $bike_id, $full_reversal_amount);
            $led_st1->execute();
            $led_st1->close();

            if ($return_amount > 0) {
                $led_st2 = $conn->prepare("INSERT INTO ledger (entry_date,entry_type,amount,party_type,party_id,description,reference_type,reference_id,balance) VALUES (?,'debit',?,'customer',?,?,'return_refund',?,?)");
                $desc2 = 'Refund given for Chassis: ' . $bike_info['chassis_number'];
                $led_st2->bind_param('sdisid', $return_date, $return_amount, $bike_info['customer_id'], $desc2, $bike_id, $return_amount);
                $led_st2->execute();
                $led_st2->close();
            }

            $conn->commit();
            $msg = 'Return processed successfully.';
        } catch (Exception $e) {
            $conn->rollback();
            $err = 'Return transaction failed: ' . $e->getMessage();
        }
        header('Location: index.php?page=returns&msg=' . urlencode($msg) . '&err=' . urlencode($err));
        exit;
        end_returns_post:;
    }
    if ($page === 'payments' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_permission($conn, 'payments', 'edit');
        if ($action === 'status') {
            $pid = (int) ($_POST['id'] ?? 0);
            $new_status = sanitize($_POST['status'] ?? '');
            if (!in_array($new_status, ['pending', 'cleared', 'bounced', 'cancelled'])) {
                $err = 'Invalid status.';
                goto end_payments_post;
            }
            $stmt = $conn->prepare('UPDATE payments SET status=? WHERE id=? AND payment_type="cheque"');
            $stmt->bind_param('si', $new_status, $pid);
            $stmt->execute();
            $msg = 'Payment status updated.';
        }
        if ($action === 'delete') {
            require_permission($conn, 'payments', 'delete');
            $pid = (int) ($_POST['id'] ?? 0);
            $stmt = $conn->prepare('DELETE FROM payments WHERE id=?');
            $stmt->bind_param('i', $pid);
            $stmt->execute();
            $msg = 'Payment entry deleted.';
        }
        header('Location: index.php?page=payments&msg=' . urlencode($msg) . '&err=' . urlencode($err));
        exit;
        end_payments_post:;
    }
    if ($page === 'installments' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_permission($conn, 'installments', 'edit');
        if ($action === 'pay_installment') {
            $installment_id = (int) ($_POST['installment_id'] ?? 0);
            $payment_date = sanitize($_POST['payment_date'] ?? date('Y-m-d'));
            $payment_type = sanitize($_POST['payment_type'] ?? 'cash');
            $amount_paid = (float) ($_POST['amount_paid'] ?? 0);
            $penalty_fee = (float) ($_POST['penalty_fee'] ?? 0);
            $cheque_number = sanitize($_POST['cheque_number'] ?? '');
            $bank_name = sanitize($_POST['bank_name'] ?? '');
            $cheque_date = !empty($_POST['cheque_date']) ? $_POST['cheque_date'] : null;
            if ($installment_id <= 0 || empty($payment_date) || $amount_paid <= 0) {
                $err = 'Missing required installment payment details.';
                goto end_installments_post;
            }
            $conn->begin_transaction();
            try {
                $inst_q = $conn->query("SELECT i.bike_id, i.customer_id, i.installment_amount, i.amount_paid, b.chassis_number, c.name AS cust_name FROM installments i JOIN bikes b ON i.bike_id=b.id JOIN customers c ON i.customer_id=c.id WHERE i.id=$installment_id FOR UPDATE");
                $inst = $inst_q->fetch_assoc();
                if (!$inst) {
                    throw new Exception('Installment not found.');
                }
                $total_payment = $amount_paid + $penalty_fee;
                $payment_notes = 'Installment payment for Chassis ' . $inst['chassis_number'] . " (ID: $installment_id)";
                $pay_st = $conn->prepare("INSERT INTO payments (payment_date, payment_type, amount, cheque_number, bank_name, cheque_date, transaction_type, reference_id, party_name, notes) VALUES (?,?,?,?,?,?,'installment',?,?,?)");
                $pay_st->bind_param('ssdsssiss',$payment_date, $payment_type, $total_payment, $cheque_number, $bank_name, $cheque_date, $installment_id, $inst['cust_name'], $payment_notes);
                $pay_st->execute();
                $payment_id = $conn->insert_id;
                $new_amount_paid = $inst['amount_paid'] + $amount_paid;
                $new_penalty_fee = $inst['penalty_fee'] + $penalty_fee;
                $new_status = ($new_amount_paid >= $inst['installment_amount']) ? 'paid' : 'pending';
                $upd_inst_stmt = $conn->prepare('UPDATE installments SET amount_paid=?, penalty_fee=?, status=?, payment_id=? WHERE id=?');
                $upd_inst_stmt->bind_param('ddsi', $new_amount_paid, $new_penalty_fee, $new_status, $payment_id, $installment_id);
                $upd_inst_stmt->execute();
                $led_st = $conn->prepare("INSERT INTO ledger (entry_date,entry_type,amount,party_type,party_id,description,reference_type,reference_id,balance) VALUES (?,'credit',?,'customer',?,?,'installment',?,?)");
                $desc = 'Installment payment for Chassis: ' . $inst['chassis_number'];
                $led_st->bind_param('sdisid', $payment_date, $total_payment, $inst['customer_id'], $desc, $installment_id, $total_payment);
                $led_st->execute();
                $conn->commit();
                $msg = 'Installment recorded successfully.';
            } catch (Exception $e) {
                $conn->rollback();
                $err = 'Installment payment failed: ' . $e->getMessage();
            }
            header('Location: index.php?page=installments&msg=' . urlencode($msg) . '&err=' . urlencode($err));
            exit;
        }
        end_installments_post:;
    }
    if ($page === 'inventory' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'delete') {
            require_permission($conn, 'inventory', 'delete');
            $bid = (int) ($_POST['id'] ?? 0);
            $stmt_check_sold = $conn->prepare('SELECT status FROM bikes WHERE id = ?');
            $stmt_check_sold->bind_param('i', $bid);
            $stmt_check_sold->execute();
            $bike_status = $stmt_check_sold->get_result()->fetch_assoc()['status'] ?? '';
            if ($bike_status === 'sold' || $bike_status === 'returned') {
                $err = 'Cannot delete a sold or returned bike. Please adjust its status if necessary.';
            } else {
                $stmt = $conn->prepare('DELETE FROM bikes WHERE id=?');
                $stmt->bind_param('i', $bid);
                $stmt->execute();
                $msg = 'Bike deleted from inventory.';
            }
        }
        if ($action === 'edit') {
            require_permission($conn, 'inventory', 'edit');
            $bid = (int) ($_POST['id'] ?? 0);
            $color = sanitize($_POST['color'] ?? '');
            $pp = (float) ($_POST['purchase_price'] ?? 0);
            $status = sanitize($_POST['status'] ?? 'in_stock');
            $notes = sanitize($_POST['notes'] ?? '');
            $safe = sanitize($_POST['safeguard_notes'] ?? '');
            if ($bid <= 0 || $pp < 0) {
                $err = 'Invalid bike ID or purchase price.';
            } else {
                $stmt = $conn->prepare('UPDATE bikes SET color=?, purchase_price=?, status=?, notes=?, safeguard_notes=? WHERE id=?');
                $stmt->bind_param('sddssi', $color, $pp, $status, $notes, $safe, $bid);
                $stmt->execute();
                $msg = 'Bike updated.';
            }
        }
        if ($action === 'bulk_delete') {
            require_permission($conn, 'inventory', 'delete');
            $ids = $_POST['selected_bikes'] ?? [];
            $ids = array_map('intval', $ids);
            if (!empty($ids)) {
                $conn->begin_transaction();
                try {
                    $errors_found = false;
                    foreach ($ids as $id) {
                        $stmt_check_sold = $conn->prepare('SELECT status FROM bikes WHERE id = ?');
                        $stmt_check_sold->bind_param('i', $id);
                        $stmt_check_sold->execute();
                        $bike_status = $stmt_check_sold->get_result()->fetch_assoc()['status'] ?? '';
                        if ($bike_status === 'sold' || $bike_status === 'returned') {
                            $err .= "Cannot delete bike ID $id (status: $bike_status). ";
                            $errors_found = true;
                        } else {
                            $stmt_delete = $conn->prepare('DELETE FROM bikes WHERE id = ?');
                            $stmt_delete->bind_param('i', $id);
                            $stmt_delete->execute();
                        }
                    }
                    if ($errors_found) {
                        throw new Exception('Some bikes could not be deleted due to their status.');
                    }
                    $conn->commit();
                    $msg = count($ids) . ' bike(s) deleted.';
                } catch (Exception $e) {
                    $conn->rollback();
                    $err .= 'Bulk deletion failed: ' . $e->getMessage();
                }
            }
        }
        if ($action === 'bulk_export') {
            $ids = $_POST['selected_bikes'] ?? [];
            $ids = array_map('intval', $ids);
            if (!empty($ids)) {
                $id_str = implode(',', $ids);
                $er = $conn->query("SELECT b.*, m.model_name, m.model_code FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE b.id IN ($id_str)");
                $csv_data = "Sr,Chassis,Motor,Model,Color,Purchase Price,Status,Selling Price,Selling Date,Margin\n";
                $sr = 1;
                while ($row = $er->fetch_assoc()) {
                    $csv_data .= "$sr,{$row['chassis_number']},{$row['motor_number']},{$row['model_name']},{$row['color']},{$row['purchase_price']},{$row['status']},{$row['selling_price']},{$row['selling_date']},{$row['margin']}\n";
                    $sr++;
                }
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="inventory_export_' . date('Ymd') . '.csv"');
                echo $csv_data;
                exit;
            }
        }
        header('Location: index.php?page=inventory&msg=' . urlencode($msg) . '&err=' . urlencode($err));
        exit;
    }
    if ($page === 'settings' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
        require_permission($conn, 'settings', 'edit');
        $fields = ['company_name', 'branch_name', 'tax_rate', 'currency', 'tax_on', 'show_purchase_on_invoice', 'session_timeout_idle', 'session_timeout_absolute'];
        $st = $conn->prepare('UPDATE settings SET setting_value=? WHERE setting_key=?');
        foreach ($fields as $f) {
            if (isset($_POST[$f])) {
                $val = sanitize($_POST[$f]);
                $st->bind_param('ss', $val, $f);
                $st->execute();
            }
        }
        if (!empty($_POST['new_password'])) {
            $new_password = $_POST['new_password'];
            if (!preg_match('/^(?=.*[!@#$%^&*-])(?=.*[0-9])(?=.*[A-Za-z]).{8,}$/', $new_password)) {
                $err = 'New password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.';
            } else {
                $np = password_hash($new_password, PASSWORD_DEFAULT);
                $conn->query("UPDATE users SET password_hash='" . mysqli_real_escape_string($conn, $np) . "' WHERE id=" . (int)$_SESSION['user_id']);
                $msg .= ' Password updated.';
            }
        }
        $st->close();
        $msg .= 'Settings saved.';
        header('Location: index.php?page=settings&msg=' . urlencode($msg) . '&err=' . urlencode($err));
        exit;
    }
    if ($page === 'settings' && isset($_GET['action']) && $_GET['action'] === 'backup') {
        require_permission($conn, 'settings', 'view');
        $tables_list = ['settings', 'suppliers', 'customers', 'models', 'accessories', 'purchase_orders', 'bikes', 'sale_accessories', 'payments', 'installments', 'ledger', 'roles', 'role_permissions', 'users', 'income_expenses', 'quotations'];
        $sql_dump = "-- BNI Enterprises Database Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n-- Author: $author\n\n";
        $sql_dump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        foreach ($tables_list as $tbl) {
            $sql_dump .= "TRUNCATE TABLE `$tbl`;\n";
            $r = $conn->query("SELECT * FROM `$tbl`");
            if ($r && $r->num_rows > 0) {
                while ($row = $r->fetch_assoc()) {
                    $vals = array_map(function ($v) use ($conn) {
                        return $v === null ? 'NULL' : "'" . mysqli_real_escape_string($conn, $v) . "'";
                    }, array_values($row));
                    $cols = '`' . implode('`,`', array_keys($row)) . '`';
                    $sql_dump .= "INSERT INTO `$tbl` ($cols) VALUES (" . implode(',', $vals) . ");\n";
                }
            }
            $sql_dump .= "\n";
        }
        $sql_dump .= "SET FOREIGN_KEY_CHECKS=1;\n";
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="bni_backup_' . date('Ymd_His') . '.sql"');
        echo $sql_dump;
        exit;
    }
    if ($page === 'settings' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_db']) && isset($_FILES['backup_file'])) {
        require_permission($conn, 'settings', 'edit');
        $file = $_FILES['backup_file'];
        if ($file['error'] === UPLOAD_ERR_OK && pathinfo($file['name'], PATHINFO_EXTENSION) === 'sql') {
            $sql_content = file_get_contents($file['tmp_name']);
            $conn->query('SET FOREIGN_KEY_CHECKS=0;');
            if ($conn->multi_query($sql_content)) {
                while ($conn->more_results() && $conn->next_result()) {
                    ;
                }
                $conn->query('SET FOREIGN_KEY_CHECKS=1;');
                $msg = 'Database restored successfully.';
            } else {
                $err = 'Restore failed: ' . $conn->error;
                $conn->query('SET FOREIGN_KEY_CHECKS=1;');
            }
        } else {
            $err = 'Invalid file uploaded. Please upload a valid .sql file.';
        }
        header('Location: index.php?page=settings&msg=' . urlencode($msg) . '&err=' . urlencode($err));
        exit;
    }
    if ($page === 'customer_ledger' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
        $sel_cust = (int) ($_GET['cust_id'] ?? 0);
        $amount = (float) $_POST['amount'];
        $pay_date = sanitize($_POST['payment_date']);
        $pay_method = sanitize($_POST['payment_method']);
        $notes = sanitize($_POST['notes']);
        if ($amount > 0 && $sel_cust > 0) {
            $party_name = $conn->query("SELECT name FROM customers WHERE id=$sel_cust")->fetch_assoc()['name'] ?? 'Unknown';
            $st = $conn->prepare("INSERT INTO payments (payment_date, payment_type, amount, transaction_type, party_name, notes) VALUES (?, ?, ?, 'sale', ?, ?)");
            $st->bind_param('ssdss', $pay_date, $pay_method, $amount, $party_name, $notes);
            $st->execute();
            $led = $conn->prepare("INSERT INTO ledger (entry_date, entry_type, amount, party_type, party_id, description, reference_type) VALUES (?, 'credit', ?, 'customer', ?, ?, 'payment')");
            $desc = 'Payment Received: ' . $notes;
            $led->bind_param('sdis', $pay_date, $amount, $sel_cust, $desc);
            $led->execute();
            $msg = 'Payment recorded successfully.';
        } else {
            $err = 'Invalid payment amount or customer.';
        }
        header("Location: index.php?page=customer_ledger&cust_id=$sel_cust&msg=" . urlencode($msg) . '&err=' . urlencode($err));
        exit;
    }
    if ($page === 'supplier_ledger' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_sup_payment'])) {
        $sel_sup = (int) ($_GET['sup_id'] ?? 0);
        $amount = (float) $_POST['amount'];
        $pay_date = sanitize($_POST['payment_date']);
        $pay_method = sanitize($_POST['payment_method']);
        $notes = sanitize($_POST['notes']);
        if ($amount > 0 && $sel_sup > 0) {
            $party_name = $conn->query("SELECT name FROM suppliers WHERE id=$sel_sup")->fetch_assoc()['name'] ?? 'Unknown';
            $st = $conn->prepare("INSERT INTO payments (payment_date, payment_type, amount, transaction_type, reference_id, party_name, notes) VALUES (?, ?, ?, 'supplier_payment', 0, ?, ?)");
            $st->bind_param('ssdss', $pay_date, $pay_method, $amount, $party_name, $notes);
            $st->execute();
            $msg = 'Supplier payment recorded successfully.';
        } else {
            $err = 'Invalid payment amount or supplier.';
        }
        header("Location: index.php?page=supplier_ledger&sup_id=$sel_sup&msg=" . urlencode($msg) . '&err=' . urlencode($err));
        exit;
    }
    if ($page === 'inventory' && isset($_GET['export_csv']) && $_GET['export_csv'] == 1) {
        $status_f = sanitize($_GET['status_f'] ?? '');
        $model_f = (int) ($_GET['model_f'] ?? 0);
        $color_f = sanitize($_GET['color_f'] ?? '');
        $search_f = sanitize($_GET['search_f'] ?? '');
        $date_from = sanitize($_GET['date_from'] ?? '');
        $date_to = sanitize($_GET['date_to'] ?? '');
        $where_parts = ['1=1'];
        if ($status_f && in_array($status_f, ['in_stock', 'sold', 'returned', 'reserved']))
            $where_parts[] = "b.status='$status_f'";
        if ($model_f)
            $where_parts[] = "b.model_id=$model_f";
        if ($color_f)
            $where_parts[] = "b.color LIKE '%" . mysqli_real_escape_string($conn, $color_f) . "%'";
        if ($search_f)
            $where_parts[] = "(b.chassis_number LIKE '%" . mysqli_real_escape_string($conn, $search_f) . "%' OR b.motor_number LIKE '%" . mysqli_real_escape_string($conn, $search_f) . "%' OR m.model_name LIKE '%" . mysqli_real_escape_string($conn, $search_f) . "%' OR b.color LIKE '%" . mysqli_real_escape_string($conn, $search_f) . "%')";
        if ($date_from)
            $where_parts[] = "b.inventory_date >= '" . mysqli_real_escape_string($conn, $date_from) . "'";
        if ($date_to)
            $where_parts[] = "b.inventory_date <= '" . mysqli_real_escape_string($conn, $date_to) . "'";
        $where = implode(' AND ', $where_parts);
        $er = $conn->query("SELECT b.*, m.model_name, m.model_code, c.name as cust_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON b.customer_id=c.id WHERE $where ORDER BY b.id DESC");
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="inventory_' . date('Ymd') . '.csv"');
        echo "Sr,Chassis,Motor,Model,Color,Purchase Price,Status,Selling Price,Selling Date,Customer,Margin\n";
        $sr = 1;
        while ($row = $er->fetch_assoc()) {
            echo "$sr,\"{$row['chassis_number']}\",\"{$row['motor_number']}\",\"{$row['model_name']}\",\"{$row['color']}\",{$row['purchase_price']},{$row['status']},{$row['selling_price']},\"{$row['selling_date']}\",\"{$row['cust_name']}\",{$row['margin']}\n";
            $sr++;
        }
        exit;
    }
    if (isset($_GET['ajax'])) {
        if ($_GET['ajax'] === 'check_chassis') {
            $chassis = sanitize($_GET['chassis'] ?? '');
            $r = $conn->query("SELECT id FROM bikes WHERE chassis_number='" . mysqli_real_escape_string($conn, $chassis) . "'");
            echo ($r && $r->num_rows > 0) ? '1' : '0';
        } elseif ($_GET['ajax'] === 'get_suppliers') {
            $suppliers_list_ajax = $conn->query('SELECT id, name FROM suppliers ORDER BY name');
            echo json_encode($suppliers_list_ajax->fetch_all(MYSQLI_ASSOC));
        } elseif ($_GET['ajax'] === 'get_models') {
            $models_list_ajax = $conn->query('SELECT id, model_code, model_name FROM models ORDER BY model_name');
            echo json_encode($models_list_ajax->fetch_all(MYSQLI_ASSOC));
        } elseif ($_GET['ajax'] === 'get_customers') {
            $customers_list_ajax = $conn->query('SELECT id, name, phone, is_filer FROM customers ORDER BY name');
            echo json_encode($customers_list_ajax->fetch_all(MYSQLI_ASSOC));
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $db_exists ? (get_setting('theme') ?? 'dark') : 'dark' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BNI Enterprises - Bike Dealer Management System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.11.3/r-2.2.9/datatables.min.css"/>
<style>
:root {
--animate-duration: 0.15s;
--bg: #2b2b2b;
--bg2: #1e1e1e;
--bg3: #333333;
--surface: #3c3c3c;
--border: #555555;
--text: #d4d4d4;
--text2: #aaaaaa;
--text3: #777777;
--accent: #4a9eff;
--accent-h: #2a7edf;
--success: #4ec94e;
--success-h: #3aab3a;
--danger: #e74c3c;
--danger-h: #c0392b;
--warning: #e0a800;
--input-bg: #ffffff;
--input-text: #222222;
--input-border: #888888;
--sidebar-w: 220px;
--topbar-h: 48px;
--font: 'Segoe UI', Arial, Consolas, monospace;
}
[data-theme="light"] {
--bg: #f0f0f0;
--bg2: #e0e0e0;
--bg3: #d8d8d8;
--surface: #ffffff;
--border: #bbbbbb;
--text: #222222;
--text2: #555555;
--text3: #888888;
--accent: #1a6fc4;
--accent-h: #0d5aad;
--success: #2a8a2a;
--success-h: #1e6e1e;
--danger: #c0392b;
--danger-h: #962d22;
--warning: #b07d00;
--input-bg: #ffffff;
--input-text: #111111;
--input-border: #aaaaaa;
--bg2: #e8e8e8;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{font-size:14px;-webkit-text-size-adjust:none}
body{font-family:var(--font);background:var(--bg);color:var(--text);min-height:100vh;display:flex;flex-direction:column}
a{color:var(--accent);text-decoration:none}
a:hover{text-decoration:underline}
input,select,textarea,button{font-family:var(--font);font-size:0.9rem}
button{cursor:pointer}
table{border-collapse:collapse;width:100%}
img{display:block;max-width:100%}
.layout{display:flex;min-height:100vh;flex-direction:row}
.sidebar{width:var(--sidebar-w);background:var(--bg2);border-right:2px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:100;overflow-y:auto;transition:width 0.2s, transform 0.2s}
.sidebar::-webkit-scrollbar { width: 6px; }
.sidebar::-webkit-scrollbar-track { background: transparent; }
.sidebar::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }
.sidebar::-webkit-scrollbar-thumb:hover { background: var(--text3); }
.sidebar-header{padding:12px 10px;border-bottom:2px solid var(--border);display:flex;align-items:center;justify-content:center;gap:10px;text-align:left}
.sidebar-header .logo{width:35px;height:35px;object-fit:contain;flex-shrink:0}
.sidebar-header .company{font-size:0.85rem;font-weight:700;color:var(--accent);line-height:1.3}
.sidebar-header .branch{font-size:0.72rem;color:var(--text2);margin-top:2px}
.sidebar nav ul{list-style:none;padding:0;margin:0}
.sidebar nav ul li a{display:flex;align-items:center;gap:8px;padding:10px 14px;color:var(--text);font-size:0.83rem;border-bottom:1px solid var(--border);transition:background 0.15s}
.sidebar nav ul li a:hover{background:var(--surface);text-decoration:none}
.sidebar nav ul li a.active{background:var(--accent);color:#fff}
.sidebar nav ul li a .icon{font-size:1rem;min-width:18px;text-align:center}
.sidebar-footer{margin-top:auto;padding:10px;border-top:2px solid var(--border)}
.sidebar-footer form{display:inline}
.sidebar-footer button{background:var(--danger);color:#fff;border:1px solid var(--danger-h);padding:6px 14px;font-size:0.8rem;border-radius:2px;width:100%}
.main-wrap{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-height:100vh;transition:margin-left 0.2s;min-width:0}
.topbar{height:var(--topbar-h);background:var(--bg2);border-bottom:2px solid var(--border);display:flex;align-items:center;padding:0 16px;position:sticky;top:0;z-index:50;gap:10px}
.topbar .hamburger{display:flex;background:none;border:1px solid var(--border);color:var(--text);padding:5px 8px;border-radius:2px;font-size:1.1rem;cursor:pointer}
@media (min-width: 601px) {
body.sidebar-collapsed { --sidebar-w: 60px; }
body.sidebar-collapsed .sidebar { overflow-x: hidden; white-space: nowrap; }
body.sidebar-collapsed .sidebar-header .header-text { display: none; }
body.sidebar-collapsed .sidebar-header { padding: 15px 0; }
body.sidebar-collapsed nav ul li a { font-size: 0; justify-content: center; padding: 12px 0; }
body.sidebar-collapsed nav ul li a .icon { font-size: 1.2rem; margin: 0; }
body.sidebar-collapsed .sidebar-footer form button { font-size: 0; padding: 10px 0; }
body.sidebar-collapsed .sidebar-footer form button::after { content: '🚪'; font-size: 1.1rem; }
}
.topbar .page-title{font-size:0.95rem;font-weight:700;color:var(--text);flex:1}
.topbar .topbar-actions{display:flex;gap:8px;align-items:center}
.topbar .topbar-actions form button{background:var(--surface);border:1px solid var(--border);color:var(--text);padding:4px 10px;font-size:0.78rem;border-radius:2px}
.topbar .topbar-actions form button:hover{background:var(--bg3)}
.content{flex:1;padding:16px;overflow-x:hidden;max-width:100%}
.toast-wrap{position:fixed;top:60px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:8px}
.toast{padding:10px 18px;border-radius:2px;font-size:0.85rem;border:1px solid;min-width:220px;max-width:340px;animation:fadeIn 0.15s;font-weight:600}
.toast.success{background:#1e4d1e;border-color:var(--success);color:#b8f0b8}
.toast.error{background:#4d1e1e;border-color:var(--danger);color:#f0b8b8}
[data-theme="light"] .toast.success{background:#d4f4d4;border-color:var(--success);color:#1a4d1a}
[data-theme="light"] .toast.error{background:#f4d4d4;border-color:var(--danger);color:#4d1a1a}
@keyframes fadeIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
.fieldset{border:2px solid var(--border);padding:12px 14px;margin-bottom:14px;border-radius:2px;min-width:0;max-width:100%;animation: animate__fadeInUp 0.15s;}
.fieldset legend{font-size:0.8rem;font-weight:700;padding:0 6px;color:var(--accent);text-transform:uppercase;letter-spacing:0.5px}
.form-row{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:10px}
.form-group{display:flex;flex-direction:column;gap:3px;flex:1;min-width:140px}
.form-group label{font-size:0.78rem;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:0.3px}
.form-group label .req{color:var(--danger);margin-left:2px}
.form-group input,.form-group select,.form-group textarea{background:var(--input-bg);color:var(--input-text);border:1px solid var(--input-border);padding:7px 9px;border-radius:1px;font-size:0.87rem;outline:none;transition:border-color 0.15s}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:var(--accent)}
.form-group textarea{resize:vertical;min-height:60px}
.form-group select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%23888'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 8px center;padding-right:26px}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border:1px solid;border-radius:2px;font-size:0.83rem;font-weight:600;cursor:pointer;text-decoration:none;white-space:nowrap;min-height:34px;transition:background 0.15s,border-color 0.15s}
.btn-primary{background:var(--accent);border-color:var(--accent-h);color:#fff}
.btn-primary:hover{background:var(--accent-h);text-decoration:none;color:#fff}
.btn-success{background:var(--success);border-color:var(--success-h);color:#fff}
.btn-success:hover{background:var(--success-h);text-decoration:none;color:#fff}
.btn-danger{background:var(--danger);border-color:var(--danger-h);color:#fff}
.btn-danger:hover{background:var(--danger-h);text-decoration:none;color:#fff}
.btn-default{background:var(--surface);border-color:var(--border);color:var(--text)}
.btn-default:hover{background:var(--bg3);text-decoration:none}
.btn-warning{background:var(--warning);border-color:#a06000;color:#fff}
.btn-warning:hover{background:#a06000;text-decoration:none;color:#fff}
.btn-sm{padding:4px 9px;font-size:0.77rem;min-height:28px}
.card-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px}
.card{background:var(--bg2);border:2px solid var(--border);padding:12px 14px;border-radius:2px;display:flex;align-items:center;gap:12px;animation: animate__fadeIn 0.15s}
.card .card-icon{font-size:1.8rem;min-width:40px;text-align:center}
.card .card-body .card-label{font-size:0.73rem;color:var(--text2);text-transform:uppercase;letter-spacing:0.5px;font-weight:700}
.card .card-body .card-value{font-size:1.3rem;font-weight:700;color:var(--text)}
.card .card-body .card-sub{font-size:0.75rem;color:var(--text3)}
.card.accent{border-color:var(--accent)}
.card.success{border-color:var(--success)}
.card.danger{border-color:var(--danger)}
.card.warning{border-color:var(--warning)}
.split-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.split-grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.data-table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;margin-bottom:14px;width:100%;max-width:100%}
.data-table{width:100%;border:1px solid var(--border);font-size:0.82rem}
.data-table th,.data-table td{border:1px solid var(--border);padding:6px 9px;white-space:nowrap}
.data-table th{background:var(--bg2);color:var(--text);font-weight:700;text-align:left;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.3px;cursor:pointer;user-select:none}
.data-table th:hover{background:var(--surface)}
.data-table tbody tr:nth-child(even){background:var(--bg)}
.data-table tbody tr:nth-child(odd){background:var(--surface)}
.data-table tbody tr:hover{background:var(--bg3)}
.data-table tbody tr.row-sold{background:#1a3d1a !important}
.data-table tbody tr.row-returned{background:#3d1a1a !important}
.data-table tbody tr.row-reserved{background:#3d3000 !important}
[data-theme="light"] .data-table tbody tr.row-sold{background:#d4f4d4 !important}
[data-theme="light"] .data-table tbody tr.row-returned{background:#f4d4d4 !important}
[data-theme="light"] .data-table tbody tr.row-reserved{background:#f4f0d4 !important}
.data-table tfoot tr{background:var(--bg2);font-weight:700}
.data-table .actions-col{white-space:nowrap;display:flex;gap:4px;flex-wrap:wrap}
.pagination{display:flex;gap:4px;align-items:center;flex-wrap:wrap;margin-top:10px}
.pagination a,.pagination span{padding:5px 10px;border:1px solid var(--border);background:var(--surface);color:var(--text);font-size:0.8rem;border-radius:1px;text-decoration:none}
.pagination a:hover{background:var(--accent);color:#fff;border-color:var(--accent-h)}
.pagination .active-page{background:var(--accent);color:#fff;border-color:var(--accent-h)}
.badge{display:inline-block;padding:2px 7px;border-radius:1px;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.3px}
.badge-success{background:#1a4d1a;color:#8af08a}
.badge-danger{background:#4d1a1a;color:#f08a8a}
.badge-warning{background:#4d3a00;color:#f0c858}
.badge-info{background:#1a2d4d;color:#8ab8f0}
.badge-default{background:var(--surface);color:var(--text2);border:1px solid var(--border)}
[data-theme="light"] .badge-success{background:#d4f4d4;color:#1a4d1a}
[data-theme="light"] .badge-danger{background:#f4d4d4;color:#4d1a1a}
[data-theme="light"] .badge-warning{background:#f4e8d4;color:#4d2a00}
[data-theme="light"] .badge-info{background:#d4e8f4;color:#1a2d4d}
.filter-bar{display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;margin-bottom:12px;padding:10px;background:var(--bg2);border:1px solid var(--border)}
.filter-bar .form-group{min-width:120px;flex:0 0 auto}
.filter-bar .form-group label{font-size:0.72rem}
.filter-bar .form-group input,.filter-bar .form-group select{font-size:0.82rem;padding:5px 7px}
.modal-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:500;align-items:flex-start;justify-content:center;padding-top:6vh}
.modal-overlay.open{display:flex}
.modal{background:var(--bg2);border:2px solid var(--border);padding:18px;width:90%;max-width:500px;max-height:85vh;overflow-y:auto;border-radius:2px;position:relative;animation: animate__zoomIn 0.3s;}
.modal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;border-bottom:1px solid var(--border);padding-bottom:8px}
.modal-header h3{font-size:0.9rem;font-weight:700;color:var(--accent);text-transform:uppercase}
.modal-close{background:var(--danger);border:none;color:#fff;padding:3px 8px;font-size:0.9rem;cursor:pointer;border-radius:1px}
.bike-row{background:var(--surface);border:1px solid var(--border);padding:10px;margin-bottom:8px;border-radius:2px;position:relative}
.bike-row-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
.bike-row-num{font-size:0.78rem;font-weight:700;color:var(--accent);text-transform:uppercase}
.bike-row-del{background:var(--danger);border:none;color:#fff;padding:2px 8px;font-size:0.78rem;cursor:pointer;border-radius:1px}
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--bg)}
.login-box{background:var(--bg2);border:2px solid var(--border);padding:30px;width:340px;border-radius:2px;animation: animate__fadeIn 0.15s}
.login-box h2{font-size:1.1rem;font-weight:700;color:var(--accent);text-align:center;margin-bottom:4px;text-transform:uppercase}
.login-box .login-sub{font-size:0.78rem;color:var(--text2);text-align:center;margin-bottom:20px}
.login-box .form-group{margin-bottom:12px}
.login-box .login-btn{width:100%;background:var(--accent);border:1px solid var(--accent-h);color:#fff;padding:9px;font-size:0.9rem;font-weight:700;border-radius:2px;cursor:pointer;margin-top:4px}
.login-box .login-btn:hover{background:var(--accent-h)}
.login-err{color:var(--danger);font-size:0.82rem;text-align:center;margin-bottom:10px;padding:6px;background:#4d1e1e;border:1px solid var(--danger);border-radius:1px}
[data-theme="light"] .login-err{background:#f4d4d4}
.install-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--bg)}
.install-box{background:var(--bg2);border:2px solid var(--accent);padding:30px;width:400px;border-radius:2px;text-align:center;animation: animate__fadeIn 0.15s}
.install-box h2{color:var(--accent);font-size:1.1rem;margin-bottom:8px}
.install-box p{color:var(--text2);font-size:0.83rem;margin-bottom:18px}
.sub-tabs{display:flex;gap:0;margin-bottom:14px;border-bottom:2px solid var(--border);flex-wrap:wrap}
.sub-tab{padding:7px 14px;background:var(--surface);border:1px solid var(--border);border-bottom:none;color:var(--text);font-size:0.8rem;font-weight:600;cursor:pointer;text-decoration:none;border-radius:2px 2px 0 0;margin-right:2px}
.sub-tab:hover{background:var(--bg3);text-decoration:none;color:var(--text)}
.sub-tab.active{background:var(--accent);color:#fff;border-color:var(--accent)}
.sub-panel{display:none}
.sub-panel.active{display:block}
.invoice-wrap{background:#fff;color:#111;padding:20px;font-family:Arial,sans-serif;font-size:12px;max-width:700px;margin:0 auto;border:1px solid #ccc}
.invoice-header{text-align:center;margin-bottom:16px;border-bottom:2px solid #333;padding-bottom:10px}
.invoice-header h1{font-size:1.3rem;color:#1a1a1a;font-weight:700}
.invoice-header h2{font-size:0.85rem;color:#555;font-weight:400}
.invoice-section{margin-bottom:12px}
.invoice-section h3{font-size:0.78rem;text-transform:uppercase;font-weight:700;border-bottom:1px solid #ccc;padding-bottom:3px;margin-bottom:6px}
.invoice-table{width:100%;border-collapse:collapse;font-size:0.83rem}
.invoice-table th,.invoice-table td{border:1px solid #ccc;padding:5px 8px}
.invoice-table th{background:#f0f0f0;font-weight:700}
.invoice-total{text-align:right;font-size:0.95rem;font-weight:700;margin-top:10px;padding:8px;background:#f0f0f0;border:1px solid #ccc}
.invoice-footer{text-align:center;margin-top:16px;border-top:1px solid #ccc;padding-top:8px;font-size:0.75rem;color:#777}
.timeline{list-style:none;padding:0;margin:0}
.timeline li{display:flex;gap:12px;padding:8px 0;border-bottom:1px solid var(--border)}
.timeline-dot{width:12px;height:12px;border-radius:50%;background:var(--accent);margin-top:4px;flex-shrink:0}
.timeline-content{flex:1}
.timeline-date{font-size:0.75rem;color:var(--text3)}
.timeline-text{font-size:0.83rem;color:var(--text)}
.stats-row{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px}
.stat-box{background:var(--bg2);border:1px solid var(--border);padding:8px 14px;border-radius:1px;flex:1;min-width:100px;text-align:center}
.stat-box .stat-val{font-size:1.1rem;font-weight:700;color:var(--accent)}
.stat-box .stat-lbl{font-size:0.72rem;color:var(--text2);text-transform:uppercase}
.sidebar-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:90}
.print-btn-wrap{margin-bottom:10px}
.select2-container {
    width: 100% !important;
    min-width: 180px !important;
}
.select2-container--default .select2-selection--single {
    background-color: var(--input-bg) !important;
    border: 1px solid var(--input-border) !important;
    border-radius: 1px !important;
    height: 34px !important;
    display: flex !important;
    align-items: center !important;
    font-size: 0.87rem !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: var(--input-text) !important;
    line-height: 32px !important;
    padding-left: 8px !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 32px !important;
    right: 5px !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow b {
    border-color: var(--input-text) transparent transparent transparent !important;
}
.select2-container--default.select2-container--focus .select2-selection--single {
    border-color: var(--accent) !important;
}
.select2-dropdown {
    background-color: var(--input-bg) !important;
    border: 1px solid var(--accent) !important;
    border-radius: 1px !important;
}
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background-color: var(--accent) !important;
    color: #fff !important;
}
.select2-container--default .select2-results__option--selectable {
    color: var(--input-text) !important;
}
.select2-search input {
    background-color: var(--bg) !important;
    color: var(--text) !important;
    border: 1px solid var(--border) !important;
}
.dataTables_wrapper .dataTables_filter input, .dataTables_wrapper .dataTables_length select {
    background-color: var(--input-bg);
    color: var(--input-text);
    border: 1px solid var(--input-border);
    padding: 7px 9px;
    border-radius: 1px;
    font-size: 0.87rem;
    outline: none;
    transition: border-color 0.15s;
    margin-left: 0.5em;
    margin-right: 0.5em;
}
.dataTables_wrapper .dataTables_filter label, .dataTables_wrapper .dataTables_length label, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate {
    color: var(--text) !important;
    font-size: 0.85rem;
    padding: 10px 0;
}
.dataTables_wrapper select {
    background-color: var(--input-bg) !important;
    color: var(--input-text) !important;
    border: 1px solid var(--input-border) !important;
    padding: 4px !important;
    border-radius: 2px !important;
    outline: none !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 0.5em 1em;
    margin-left: 2px;
    border: 1px solid var(--border);
    background-color: var(--surface);
    color: var(--text) !important;
    border-radius: 1px;
    cursor: pointer;
    font-size: 0.8rem;
    min-width: 44px; 
    min-height: 34px; 
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background-color: var(--accent);
    color: #fff !important;
    border-color: var(--accent-h);
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current, .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
    background-color: var(--accent);
    color: #fff !important;
    border-color: var(--accent-h);
}
.dataTables_wrapper .dataTables_processing {
    background-color: var(--bg2);
    color: var(--text);
    border: 1px solid var(--border);
}
.validation-error {
    color: var(--danger);
    font-size: 0.75rem;
    margin-top: 2px;
    padding-left: 2px;
}
.just-validate-error-field {
    border-color: var(--danger) !important;
}
.just-validate-error-label {
    color: var(--danger) !important;
}
@media print{
.sidebar,.topbar,.filter-bar,.pagination,.btn,.actions-col,.print-btn-wrap,.no-print,.dataTables_filter,.dataTables_length,.dataTables_info,.dataTables_paginate{display:none!important}
.main-wrap{margin-left:0!important}
.content{padding:0!important}
body{background:#fff!important;color:#111!important}
.data-table th,.data-table td{color:#111!important;background:#fff!important;border-color:#666!important}
.invoice-wrap{border:none!important;padding:0!important}
.invoice-footer{position:fixed;bottom:0;width:100%;text-align:center;padding:10px;font-size:10px;color:#777;border-top:1px solid #ccc;background:#fff;}
}
@media(max-width:900px){
.card-grid{grid-template-columns:repeat(2,1fr)}
.split-grid-3{grid-template-columns:1fr 1fr}
}
@media(max-width:600px){
.page-title .title-text{display:none}
.card-grid, .split-grid, .split-grid-3{grid-template-columns:1fr}
.sidebar{transform:translateX(-100%)}
.sidebar.open{transform:translateX(0)}
.sidebar-overlay.open{display:block}
.main-wrap{margin-left:0}
.form-row{flex-direction:column}
.form-group{min-width:0}
.filter-bar{flex-direction:column}
.data-table th,.data-table td{font-size:0.75rem;padding:4px 6px}
.btn{font-size:0.78rem;padding:6px 10px}
.modal{width:98%;padding:12px}
.stats-row{flex-direction:column}
.dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_length {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}
.dataTables_wrapper .dataTables_filter input, .dataTables_wrapper .dataTables_length select {
    width: 100%;
    margin-left: 0;
}
.dataTables_wrapper .dataTables_info {
    text-align: center;
}
.dataTables_wrapper .dataTables_paginate {
    justify-content: center;
    display: flex;
    flex-wrap: wrap;
}
}
a.paginate_button.current {
    background: #322727 !important;
}
</style>
</head>
<body>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://unpkg.com/just-validate@latest/dist/just-validate.production.min.js"></script>
<script src="chart.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.11.3/r-2.2.9/datatables.min.js"></script>
<script>
if (localStorage.getItem('sidebarCollapsed') === '1' && window.innerWidth > 600) {
    document.body.classList.add('sidebar-collapsed');
}
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('msg')) Swal.fire({ title: 'Success!', text: urlParams.get('msg'), icon: 'success', timer: 3000, showConfirmButton: false });
    if (urlParams.get('err')) Swal.fire({ title: 'Error!', text: urlParams.get('err'), icon: 'error', confirmButtonColor: '#d33' });
    
    if (urlParams.has('msg') || urlParams.has('err')) {
        urlParams.delete('msg');
        urlParams.delete('err');
        window.history.replaceState(null, '', window.location.pathname + '?' + urlParams.toString());
    }

    $('table.data-table:not(.no-dt)').DataTable({
        responsive: true,
        pagingType: 'full_numbers',
        lengthMenu: [10, 25, 50, 100],
        pageLength: 100,
        stateSave: true,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search records...",
            lengthMenu: "_MENU_",
            paginate: {
                first: "«",
                last: "»",
                next: "›",
                previous: "‹"
            }
        },
        columnDefs: [
            { targets: 'no-sort', orderable: false }
        ]
    });
    $('select:not([name$="_length"]):not(.swal2-select)').select2({
        minimumResultsForSearch: 10, 
        placeholder: '-- Select --',
        allowClear: false,
        theme: 'default'
    });
    var csrfToken = "<?= $_SESSION['csrf_token'] ?? '' ?>";
    $('form[method="POST"]').append('<input type="hidden" name="csrf_token" value="' + csrfToken + '">');
    $(document).on('DOMNodeInserted', function(e) {
        if (e.target && e.target.nodeType === 1) {
            $(e.target).find('select:not([name$="_length"]):not(.swal2-select)').select2({
                minimumResultsForSearch: 10,
                placeholder: '-- Select --',
                allowClear: false,
                theme: 'default'
            });
        }
    });
    window.originalAlert = window.alert;
    window.alert = function(message) {
        Swal.fire({
            title: 'Alert',
            text: message,
            icon: 'info',
            confirmButtonText: 'OK',
            customClass: {
                popup: 'animate__animated animate__fadeInUp animate__faster'
            }
        });
    };
    window.originalConfirm = window.confirm;
    window.confirm = function(message) {
        return Swal.fire({
            title: 'Are you sure?',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes',
            cancelButtonText: 'No',
            customClass: {
                popup: 'animate__animated animate__fadeInUp animate__faster'
            }
        }).then((result) => {
            return result.isConfirmed;
        });
    };
    var forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        if (!form.hasAttribute('novalidate')) { 
            var validator = new JustValidate(form, {
                errorFieldCssClass: 'just-validate-error-field',
                errorLabelCssClass: 'just-validate-error-label',
                focusInvalidField: true,
                lockForm: true,
            });
            form.querySelectorAll('input[required], select[required], textarea[required]').forEach(function(input) {
                validator.addField(input, [{ rule: 'required', errorMessage: 'This field is required' }]);
            });
            form.querySelectorAll('input[type="email"]').forEach(function(input) {
                validator.addField(input, [{ rule: 'email', errorMessage: 'Enter a valid email' }]);
            });
            form.querySelectorAll('input[type="number"]').forEach(function(input) {
                validator.addField(input, [{ rule: 'number', errorMessage: 'Must be a number' }]);
                if (input.min) validator.addField(input, [{ rule: 'minNumber', value: parseFloat(input.min), errorMessage: 'Must be at least ' + input.min }]);
                if (input.max) validator.addField(input, [{ rule: 'maxNumber', value: parseFloat(input.max), errorMessage: 'Must be at most ' + input.max }]);
            });
            form.querySelectorAll('input[minlength]').forEach(function(input) {
                validator.addField(input, [{ rule: 'minLength', value: parseInt(input.minlength), errorMessage: 'Minimum ' + input.minlength + ' characters' }]);
            });
            form.querySelectorAll('input[maxlength]').forEach(function(input) {
                validator.addField(input, [{ rule: 'maxLength', value: parseInt(input.maxlength), errorMessage: 'Maximum ' + input.maxlength + ' characters' }]);
            });
            if (form.querySelector('input[name="password"]') || form.querySelector('input[name="new_password"]')) {
                validator.addField(form.querySelector('input[name="password"]') || form.querySelector('input[name="new_password"]'), [
                    { rule: 'minLength', value: 8, errorMessage: 'Minimum 8 characters' },
                    { rule: 'customRegexp', value: /^(?=.*[!@#$%^&*-])(?=.*[0-9])(?=.*[A-Za-z]).{8,}$/, errorMessage: 'Must include uppercase, lowercase, number, and special character' }
                ]);
            }
            validator.onSuccess((event) => {
                const form = event.target;
                const btn = form.querySelector('button[type="submit"][name], input[type="submit"][name]');
                if (btn && btn.name && !form.querySelector('input[name="' + btn.name + '"]')) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = btn.name;
                    hidden.value = btn.value || '1';
                    form.appendChild(hidden);
                }
                if (form.classList.contains('ajax-form')) {
                    const enteredNameInput = form.querySelector('[name="name"]') || form.querySelector('[name="model_name"]');
                    const enteredName = enteredNameInput ? enteredNameInput.value : null;
                    $.ajax({
                        type: form.method || 'POST',
                        url: form.action,
                        data: $(form).serialize(),
                        success: function() {
                            if (form.id === 'supplierForm') closeSupplierModal(enteredName);
                            else if (form.id === 'modelForm') closeModelModal(enteredName);
                            else if (form.id === 'customerForm') closeCustomerModal(enteredName);
                            form.reset();
                            Swal.fire({ title: 'Success', text: 'Added successfully!', icon: 'success', timer: 1500, showConfirmButton: false });
                        },
                        error: function() {
                            Swal.fire('Error', 'Failed to add. Please try again.', 'error');
                        }
                    });
                } else {
                    form.submit();
                }
            });
        }
    });
    document.querySelectorAll('form[onsubmit*="confirm("]').forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault(); 
            const confirmMessage = this.getAttribute('onsubmit').match(/confirm\('([^']+)'\)/)[1];
            Swal.fire({
                title: 'Confirm Delete',
                text: confirmMessage,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                customClass: {
                    popup: 'animate__animated animate__shakeX animate__faster'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    this.removeAttribute('onsubmit'); 
                    this.submit(); 
                }
            });
        });
    });
});
</script>
<?php if (!$db_exists): ?>
<div class="install-wrap">
<div class="install-box animate__animated animate__fadeInDown">
<div style="font-size:2.5rem;margin-bottom:10px">⚡</div>
<h2>BNI Enterprises Setup</h2>
<p>Welcome! The database needs to be installed. Click the button below to create the database and all required tables automatically.</p>
<?php if (isset($_GET['db_error'])): ?>
<div class="login-err animate__animated animate__shakeX">Database connection failed. Please check your credentials in index.php.</div>
<?php endif; ?>
<form method="POST">
<button type="submit" name="do_install" class="btn btn-primary animate__animated animate__pulse animate__infinite" style="width:100%;font-size:0.95rem;padding:10px">⚡ Install Database</button>
</form>
<p style="margin-top:14px;font-size:0.75rem;color:var(--text3)">Created by: <?= $author ?> | v<?= $app_version ?> | <a href="https://www.yasinbss.com" target="_blank">Website: https://www.yasinbss.com</a> | WhatsApp: 03361593533</p>
</div>
</div>
<?php elseif (!isset($_SESSION['user_id'])): ?>
<div class="login-wrap">
<div class="login-box animate__animated animate__fadeInUp">
<div style="font-size:2.5rem;text-align:center;margin-bottom:8px">⚡</div>
<h2>BNI Enterprises</h2>
<div class="login-sub"><?= sanitize(get_setting('branch_name') ?? 'Dera (Ahmed Metro)') ?></div>
<?php if (isset($_GET['msg'])): ?>
<div class="login-err animate__animated animate__shakeX"><?= $_GET['msg'] === 'idle_logout' ? 'Session expired due to inactivity.' : 'Your session has expired. Please login again.' ?></div>
<?php endif; ?>
<?php if (isset($login_error)): ?>
<div class="login-err animate__animated animate__shakeX"><?= $login_error ?></div>
<?php endif; ?>
<form method="POST" id="loginForm">
<div class="form-group"><label>Username <span class="req">*</span></label><input type="text" name="username" required autocomplete="username" placeholder="admin"></div>
<div class="form-group" style="margin-top:10px"><label>Password <span class="req">*</span></label><input type="password" name="password" required autocomplete="current-password" placeholder="••••••••"></div>
<div class="form-group" style="margin-top:10px">
    <label>CAPTCHA <span class="req">*</span></label>
    <div style="display:flex;align-items:center;gap:5px;">
        <input type="text" name="captcha_code" required placeholder="Enter result" style="flex:1;">
        <img src="index.php?captcha=1&amp;<?= time() ?>" alt="CAPTCHA" style="height:34px;width:100px;border:1px solid var(--border);border-radius:2px;cursor:pointer" onclick="this.src='index.php?captcha=1&amp;'+Date.now()">
    </div>
</div>
<button type="submit" name="do_login" class="login-btn animate__animated animate__pulse animate__infinite" style="margin-top:14px">🔐 Login</button>
</form>
<p style="margin-top:14px;font-size:0.75rem;color:var(--text3);text-align:center">Created by: <?= $author ?> | v<?= $app_version ?> | <a href="https://www.yasinbss.com" target="_blank">Website: https://www.yasinbss.com</a> | WhatsApp: 03361593533</p>
</div>
</div>
<script>
    const loginValidator = new JustValidate('#loginForm', {
        errorFieldCssClass: 'just-validate-error-field',
        errorLabelCssClass: 'just-validate-error-label',
        focusInvalidField: true,
        lockForm: true,
    });
    loginValidator
        .addField('input[name="username"]', [{ rule: 'required', errorMessage: 'Username is required' }])
        .addField('input[name="password"]', [{ rule: 'required', errorMessage: 'Password is required' }])
        .addField('input[name="captcha_code"]', [{ rule: 'required', errorMessage: 'Captcha is required' }])
        .onSuccess((event) => {
            const form = event.target;
            const btn = form.querySelector('[type="submit"]');
            if (btn && btn.name) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = btn.name;
                hidden.value = btn.value || '1';
                form.appendChild(hidden);
            }
            form.submit();
        });
</script>
<?php
else:
    $company_name = get_setting('company_name') ?? 'BNI Enterprises';
    $branch_name = get_setting('branch_name') ?? 'Dera (Ahmed Metro)';
    $currency = get_setting('currency') ?? 'Rs.';
    $all_nav = [
        ['dashboard', '🏠', 'Dashboard'],
        ['purchase', '📦', 'Purchase Entry'],
        ['inventory', '📋', 'Inventory / Stock'],
        ['sale', '🛒', 'Sales Entry'],
        ['returns', '↩', 'Returns'],
        ['payments', '💳', 'Payments Register'],
        ['installments', '🗓️', 'Installments'],
        ['quotations', '📝', 'Quotations'],
        ['income_expense', '💰', 'Income/Expense'],
        ['customer_ledger', '👤', 'Customer Ledger'],
        ['supplier_ledger', '🏭', 'Supplier Ledger'],
        ['reports', '📊', 'Reports'],
        ['models', '🚲', 'Models'],
        ['accessories', '🛠️', 'Accessories'],
        ['customers', '👥', 'Customers'],
        ['suppliers', '🏢', 'Suppliers'],
        ['users', '👨‍💼', 'Users'],
        ['roles', '🔑', 'Roles & Permissions'],
        ['settings', '⚙', 'Settings'],
    ];
    $pages_nav = [];
    foreach ($all_nav as $nav) {
        if (has_permission($conn, $nav[0], 'view')) {
            $pages_nav[] = $nav;
        }
    }
?>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
<div class="sidebar" id="sidebar">
<div class="sidebar-header">
<img src="logo.png" alt="Logo" class="logo">
<div class="header-text">
<div class="company">⚡ <?= sanitize($company_name) ?></div>
<div class="branch"><?= sanitize($branch_name) ?></div>
</div>
</div>
<nav>
<ul>
<?php foreach ($pages_nav as $pn): ?>
<li><a href="index.php?page=<?= $pn[0] ?>" class="<?= $page === $pn[0] ? 'active' : '' ?> animate__animated animate__fadeInLeft"><span class="icon"><?= $pn[1] ?></span><?= $pn[2] ?></a></li>
<?php endforeach; ?>
</ul>
</nav>
<div class="sidebar-footer">
<p style="margin-top:14px;font-size:0.75rem;color:var(--text3);text-align:center">Created by: <?= $author ?><br><a href="https://www.yasinbss.com" target="_blank">Website: https://www.yasinbss.com</a><br>WhatsApp: 03361593533</p>
<form method="GET" action="index.php"><input type="hidden" name="logout" value="1"><button type="submit">🚪 Logout</button></form>
</div>
</div>
<div class="main-wrap animate__animated animate__fadeIn">
<div class="topbar">
<button class="hamburger" onclick="toggleSidebar()">☰</button>
<div class="page-title">
<?php foreach ($pages_nav as $pn) { if ($pn[0] === $page) echo $pn[1] . ' <span class="title-text">' . $pn[2] . '</span>'; } ?>
</div>
<div class="topbar-actions">
<?php $cu = current_user($conn);
    if ($cu): ?><span style="font-size:0.8rem;color:var(--text2);margin-right:10px">👤 <?= sanitize($cu['full_name'] ?: $cu['username']) ?> (<?= sanitize($cu['role_name']) ?>)</span><?php endif; ?>
<form method="POST" action="index.php?<?= http_build_query(array_merge($_GET, [])) ?>">
<button type="submit" name="toggle_theme" title="Toggle Theme"><?= ($theme ?? 'dark') === 'dark' ? '☀' : '🌙' ?></button>
</form>
<span style="font-size:0.75rem;color:var(--text3)"><?= date('d/m/Y H:i') ?></span>
</div>
</div>
<div class="content">
<?php
    $per_page = 20;
    $current_pg = max(1, (int) ($_GET['pg'] ?? 1));
    $offset = ($current_pg - 1) * $per_page;
    if ($page === 'dashboard'):
        require_permission($conn, 'dashboard', 'view');
        $total_stock = $conn->query("SELECT COUNT(*) as c FROM bikes WHERE status='in_stock'")->fetch_assoc()['c'];
        $total_sold = $conn->query("SELECT COUNT(*) as c FROM bikes WHERE status='sold'")->fetch_assoc()['c'];
        $total_returned = $conn->query("SELECT COUNT(*) as c FROM bikes WHERE status='returned'")->fetch_assoc()['c'];
        $total_purchase_val = $conn->query('SELECT SUM(purchase_price) as s FROM bikes')->fetch_assoc()['s'] ?? 0;
        $total_sales_val = $conn->query("SELECT SUM(selling_price) as s FROM bikes WHERE status='sold'")->fetch_assoc()['s'] ?? 0;
        $total_tax = $conn->query("SELECT SUM(tax_amount) as s FROM bikes WHERE status='sold'")->fetch_assoc()['s'] ?? 0;
        $total_margin = $conn->query("SELECT SUM(margin) as s FROM bikes WHERE status='sold'")->fetch_assoc()['s'] ?? 0;
        $pending_payments = $conn->query("SELECT COUNT(*) as c, SUM(amount) as s FROM payments WHERE payment_type='cheque' AND status='pending'")->fetch_assoc();
        $todays_sales = $conn->query("SELECT COUNT(*) as c, SUM(selling_price) as s FROM bikes WHERE status='sold' AND selling_date = CURDATE()")->fetch_assoc();
        $total_customers = $conn->query('SELECT COUNT(*) as c FROM customers')->fetch_assoc()['c'];
        $total_suppliers = $conn->query('SELECT COUNT(*) as c FROM suppliers')->fetch_assoc()['c'];
        $total_expenses = $conn->query("SELECT SUM(amount) as s FROM income_expenses WHERE type='expense'")->fetch_assoc()['s'] ?? 0;
        $overdue_installments = $conn->query("SELECT COUNT(*) as c, SUM(installment_amount - amount_paid) as s FROM installments WHERE status='pending' AND due_date < CURDATE()")->fetch_assoc();
        $sales_trend = $conn->query("SELECT DATE_FORMAT(selling_date,'%Y-%m') as ym, SUM(selling_price) as total FROM bikes WHERE status='sold' AND selling_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY ym ORDER BY ym");
        $chart_labels = [];
        $chart_sales = [];
        while ($r = $sales_trend->fetch_assoc()) {
            $chart_labels[] = $r['ym'];
            $chart_sales[] = $r['total'];
        }
        $model_stock = $conn->query("SELECT m.model_name, COUNT(b.id) as cnt FROM models m LEFT JOIN bikes b ON m.id=b.model_id WHERE b.status='in_stock' GROUP BY m.id HAVING cnt > 0");
        $ms_labels = [];
        $ms_data = [];
        while ($r = $model_stock->fetch_assoc()) {
            $ms_labels[] = $r['model_name'];
            $ms_data[] = $r['cnt'];
        }
        $ie_summary = $conn->query('SELECT type, SUM(amount) as total FROM income_expenses GROUP BY type');
        $ie_data = ['income' => 0, 'expense' => 0];
        while ($r = $ie_summary->fetch_assoc()) {
            $ie_data[$r['type']] = $r['total'];
        }
        ?>
<div class="card-grid">
<div class="card accent"><div class="card-icon">📦</div><div class="card-body"><div class="card-label">In Stock</div><div class="card-value"><?= number_format($total_stock) ?></div><div class="card-sub">bikes</div></div></div>
<div class="card success"><div class="card-icon">✅</div><div class="card-body"><div class="card-label">Total Sold</div><div class="card-value"><?= number_format($total_sold) ?></div><div class="card-sub">bikes</div></div></div>
<div class="card danger"><div class="card-icon">↩</div><div class="card-body"><div class="card-label">Returned</div><div class="card-value"><?= number_format($total_returned) ?></div><div class="card-sub">bikes</div></div></div>
<div class="card warning"><div class="card-icon">💰</div><div class="card-body"><div class="card-label">Purchase Value</div><div class="card-value" style="font-size:1rem"><?= $currency ?> <?= number_format($total_purchase_val) ?></div></div></div>
<div class="card success"><div class="card-icon">💵</div><div class="card-body"><div class="card-label">Sales Value</div><div class="card-value" style="font-size:1rem"><?= $currency ?> <?= number_format($total_sales_val) ?></div></div></div>
<div class="card"><div class="card-icon">🧾</div><div class="card-body"><div class="card-label">Total Tax Paid</div><div class="card-value" style="font-size:1rem"><?= $currency ?> <?= number_format($total_tax, 2) ?></div></div></div>
<div class="card success"><div class="card-icon">📈</div><div class="card-body"><div class="card-label">Total Profit</div><div class="card-value" style="font-size:1rem;color:var(--success)"><?= $currency ?> <?= number_format($total_margin) ?></div></div></div>
<div class="card"><div class="card-icon">💳</div><div class="card-body"><div class="card-label">Pending Cheques</div><div class="card-value" style="font-size:1rem;color:var(--warning)"><?= number_format($pending_payments['c'] ?? 0) ?></div><div class="card-sub"><?= $currency ?> <?= number_format($pending_payments['s'] ?? 0) ?></div></div></div>
<div class="card success"><div class="card-icon">🔥</div><div class="card-body"><div class="card-label">Today's Sales</div><div class="card-value"><?= number_format($todays_sales['c']) ?></div><div class="card-sub"><?= $currency ?> <?= number_format($todays_sales['s'] ?? 0) ?></div></div></div>
<div class="card danger"><div class="card-icon">💸</div><div class="card-body"><div class="card-label">Total Expenses</div><div class="card-value" style="font-size:1rem;color:var(--danger)"><?= $currency ?> <?= number_format($total_expenses) ?></div></div></div>
<div class="card accent"><div class="card-icon">👥</div><div class="card-body"><div class="card-label">Customers</div><div class="card-value"><?= number_format($total_customers) ?></div></div></div>
<div class="card warning"><div class="card-icon">🏭</div><div class="card-body"><div class="card-label">Suppliers</div><div class="card-value"><?= number_format($total_suppliers) ?></div></div></div>
</div>
<fieldset class="fieldset"><legend>⚡ Quick Actions</legend>
<div style="display:flex;gap:8px;flex-wrap:wrap">
<?php if (has_permission($conn, 'sale', 'add')): ?><a href="index.php?page=sale" class="btn btn-success animate__animated animate__pulse">🛒 New Sale</a><?php endif; ?>
<?php if (has_permission($conn, 'purchase', 'add')): ?><a href="index.php?page=purchase" class="btn btn-primary animate__animated animate__pulse">📦 New Purchase</a><?php endif; ?>
<?php if (has_permission($conn, 'customers', 'add')): ?><a href="index.php?page=customers" class="btn btn-default">👥 Add Customer</a><?php endif; ?>
<?php if (has_permission($conn, 'income_expense', 'add')): ?><a href="index.php?page=income_expense" class="btn btn-default">💰 Add Expense</a><?php endif; ?>
<?php if (has_permission($conn, 'returns', 'add')): ?><a href="index.php?page=returns" class="btn btn-warning">↩ Process Return</a><?php endif; ?>
<?php if (has_permission($conn, 'inventory', 'view')): ?><a href="index.php?page=inventory" class="btn btn-default" style="background:#1a2d4d;color:#8ab8f0;border-color:#1a2d4d">📋 View Inventory</a><?php endif; ?>
<?php if (has_permission($conn, 'quotations', 'add')): ?><a href="index.php?page=quotations" class="btn btn-info">📝 New Quotation</a><?php endif; ?>
<?php if (has_permission($conn, 'installments', 'view')): ?><a href="index.php?page=installments" class="btn btn-default">🗓️ Installments</a><?php endif; ?>
<?php if (has_permission($conn, 'reports', 'view')): ?>
<a href="index.php?page=reports&sub=daily" class="btn btn-default">📆 Daily Report</a>
<a href="index.php?page=reports&sub=profit" class="btn btn-default">📈 Profit Report</a>
<?php endif; ?>
<?php if (has_permission($conn, 'payments', 'view')): ?><a href="index.php?page=payments" class="btn btn-default">💳 Payments</a><?php endif; ?>
<?php if (has_permission($conn, 'settings', 'view')): ?><a href="index.php?page=settings" class="btn btn-default">⚙ Settings</a><?php endif; ?>
</div>
</fieldset>
<div class="split-grid" style="margin-bottom:16px;">
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>📈 Sales Trend (Last 6 Months)</legend><div style="position:relative;height:250px;width:100%"><canvas id="salesChart"></canvas></div></fieldset>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>📊 Model-wise Stock</legend><div style="position:relative;height:250px;width:100%"><canvas id="stockChart"></canvas></div></fieldset>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>💰 Income vs Expense</legend><div style="position:relative;height:250px;width:100%"><canvas id="ieChart"></canvas></div></fieldset>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>🚲 Inventory Status</legend><div style="position:relative;height:250px;width:100%"><canvas id="statusChart"></canvas></div></fieldset>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Chart.defaults.color = 'var(--text2)';
    const commonOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { color: 'var(--border)' } }, y: { grid: { color: 'var(--border)' } } } };
    const pieOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { color: 'var(--text)' } } } };
    new Chart(document.getElementById('salesChart'), {
        type: 'line',
        data: { labels: <?= json_encode($chart_labels) ?>, datasets: [{ label: 'Sales (<?= $currency ?>)', data: <?= json_encode($chart_sales) ?>, borderColor: '#4ec94e', tension: 0.3, fill: true, backgroundColor: 'rgba(78, 201, 78, 0.1)' }] },
        options: commonOptions
    });
    new Chart(document.getElementById('stockChart'), {
        type: 'doughnut',
        data: { labels: <?= json_encode($ms_labels) ?>, datasets: [{ data: <?= json_encode($ms_data) ?>, backgroundColor: ['#4a9eff','#4ec94e','#e74c3c','#e0a800','#9b59b6','#34495e','#16a085'] }] },
        options: pieOptions
    });
    new Chart(document.getElementById('ieChart'), {
        type: 'bar',
        data: { labels: ['Income', 'Expense'], datasets: [{ label: 'Amount (<?= $currency ?>)', data: [<?= $ie_data['income'] ?? 0 ?>, <?= $ie_data['expense'] ?? 0 ?>], backgroundColor: ['#4ec94e', '#e74c3c'] }] },
        options: commonOptions
    });
    new Chart(document.getElementById('statusChart'), {
        type: 'pie',
        data: { labels: ['In Stock', 'Sold', 'Returned'], datasets: [{ data: [<?= $total_stock ?>, <?= $total_sold ?>, <?= $total_returned ?>], backgroundColor: ['#4a9eff', '#4ec94e', '#e74c3c'] }] },
        options: pieOptions
    });
});
</script>
<?php if ($pending_payments['c'] > 0): ?>
<div style="background:#3d2a00;border:1px solid var(--warning);padding:8px 14px;margin-bottom:12px;border-radius:2px;font-size:0.82rem;color:#f0c858" class="animate__animated animate__headShake">
⚠ <strong><?= $pending_payments['c'] ?> pending cheque payment(s)</strong> totaling <?= $currency ?> <?= number_format($pending_payments['s'] ?? 0) ?> — <a href="index.php?page=payments">View Payments →</a>
</div>
<?php endif; ?>
<?php if ($overdue_installments['c'] > 0): ?>
<div style="background:#4d1e1e;border:1px solid var(--danger);padding:8px 14px;margin-bottom:12px;border-radius:2px;font-size:0.82rem;color:#f0b8b8" class="animate__animated animate__headShake">
🚨 <strong><?= $overdue_installments['c'] ?> overdue installment(s)</strong> totaling <?= $currency ?> <?= number_format($overdue_installments['s'] ?? 0) ?> — <a href="index.php?page=installments&status_f=overdue">View Overdue Installments →</a>
</div>
<?php endif; ?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>📊 Model-wise Stock Summary</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Model</th><th>Category</th><th>Inventory</th><th>Sold</th><th>Returned</th><th>Available</th></tr></thead>
<tbody>
<?php
        $model_summary = $conn->query("SELECT m.model_name, m.category,
    SUM(CASE WHEN 1=1 THEN 1 ELSE 0 END) as total_inv,
    SUM(CASE WHEN b.status='sold' THEN 1 ELSE 0 END) as sold_cnt,
    SUM(CASE WHEN b.status='returned' THEN 1 ELSE 0 END) as ret_cnt,
    SUM(CASE WHEN b.status='in_stock' THEN 1 ELSE 0 END) as avail_cnt
    FROM models m LEFT JOIN bikes b ON m.id=b.model_id
    GROUP BY m.id, m.model_name, m.category ORDER BY m.model_name");
        $ms_totals = [0, 0, 0, 0];
        while ($ms = $model_summary->fetch_assoc()):
            $ms_totals[0] += $ms['total_inv'];
            $ms_totals[1] += $ms['sold_cnt'];
            $ms_totals[2] += $ms['ret_cnt'];
            $ms_totals[3] += $ms['avail_cnt'];
            ?>
<tr>
<td><?= sanitize($ms['model_name']) ?></td>
<td><?= sanitize($ms['category']) ?></td>
<td><?= $ms['total_inv'] ?></td>
<td><span class="badge badge-success"><?= $ms['sold_cnt'] ?></span></td>
<td><span class="badge badge-danger"><?= $ms['ret_cnt'] ?></span></td>
<td><span class="badge badge-info"><?= $ms['avail_cnt'] ?></span></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td><strong>TOTAL</strong></td><td></td><td><strong><?= $ms_totals[0] ?></strong></td><td><strong><?= $ms_totals[1] ?></strong></td><td><strong><?= $ms_totals[2] ?></strong></td><td><strong><?= $ms_totals[3] ?></strong></td></tr></tfoot>
</table>
</div>
</fieldset>
<div class="split-grid">
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>🛒 Recent 10 Sales</legend>
<div class="data-table-wrap">
<table class="data-table no-dt">
<thead><tr><th>Date</th><th>Chassis</th><th>Model</th><th>Price</th><th>Margin</th></tr></thead>
<tbody>
<?php
        $recent_sales = $conn->query("SELECT b.chassis_number, b.selling_date, b.selling_price, b.margin, m.model_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE b.status='sold' ORDER BY b.selling_date DESC LIMIT 10");
        while ($rs = $recent_sales->fetch_assoc()):
            ?>
<tr class="row-sold">
<td><?= fmt_date($rs['selling_date']) ?></td>
<td><?= sanitize($rs['chassis_number']) ?></td>
<td><?= sanitize($rs['model_name']) ?></td>
<td><?= $currency ?> <?= number_format($rs['selling_price']) ?></td>
<td style="color:<?= $rs['margin'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><?= $currency ?> <?= number_format($rs['margin']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</fieldset>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>📦 Recent 10 Purchases</legend>
<div class="data-table-wrap">
<table class="data-table no-dt">
<thead><tr><th>Date</th><th>Chassis</th><th>Model</th><th>Price</th><th>Status</th></tr></thead>
<tbody>
<?php
        $recent_purch = $conn->query('SELECT b.chassis_number, b.inventory_date, b.purchase_price, b.status, m.model_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id ORDER BY b.created_at DESC LIMIT 10');
        while ($rp = $recent_purch->fetch_assoc()):
            $st_badge = $rp['status'] === 'sold' ? 'badge-success' : ($rp['status'] === 'returned' ? 'badge-danger' : ($rp['status'] === 'reserved' ? 'badge-warning' : 'badge-info'));
            ?>
<tr class="row-<?= $rp['status'] ?>">
<td><?= fmt_date($rp['inventory_date']) ?></td>
<td><?= sanitize($rp['chassis_number']) ?></td>
<td><?= sanitize($rp['model_name']) ?></td>
<td><?= $currency ?> <?= number_format($rp['purchase_price']) ?></td>
<td><span class="badge <?= $st_badge ?>"><?= strtoupper($rp['status']) ?></span></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</fieldset>
</div>
<?php elseif ($page === 'purchase'): ?>
<?php
        $suppliers_list = $conn->query('SELECT id, name FROM suppliers ORDER BY name');
        $models_list = $conn->query('SELECT id, model_code, model_name FROM models ORDER BY model_name');
?>
<form method="POST" id="purchaseForm" class="animate__animated animate__fadeIn">
<input type="hidden" name="save_purchase" value="1">
<fieldset class="fieldset"><legend>📦 Purchase Order Details</legend>
<div class="form-row">
<div class="form-group"><label>Order Date <span class="req">*</span></label><input type="date" name="order_date" value="<?= date('Y-m-d') ?>" required></div>
<div class="form-group"><label>Inventory Date <span class="req">*</span></label><input type="date" name="inventory_date" value="<?= date('Y-m-d') ?>" required></div>
<div class="form-group">
<label>Supplier <span class="req">*</span></label>
<div style="display:flex;gap:4px">
<select name="supplier_id" required style="flex:1">
<option value="">-- Select Supplier --</option>
<?php $suppliers_list->data_seek(0);
        while ($sup = $suppliers_list->fetch_assoc()): ?>
<option value="<?= $sup['id'] ?>"><?= sanitize($sup['name']) ?></option>
<?php endwhile; ?>
</select>
<button type="button" class="btn btn-default btn-sm" onclick="openSupplierModal()">+</button>
</div>
</div>
</div>
<div class="form-row">
<div class="form-group"><label>Notes</label><textarea name="po_notes" rows="2" placeholder="Any additional notes..."></textarea></div>
</div>
</fieldset>
<fieldset class="fieldset"><legend>💵 Payments for this Purchase</legend>
<div id="paymentsList"></div>
<button type="button" class="btn btn-primary btn-sm" onclick="addPaymentRow()" style="margin-top:6px">+ Add Payment</button>
</fieldset>
<fieldset class="fieldset"><legend>🚲 Bike Units</legend>
<div id="bikesList"></div>
<button type="button" class="btn btn-success" onclick="addBikeRow()" style="margin-top:6px">+ Add Bike</button>
</fieldset>
<div style="display:flex;gap:8px;flex-wrap:wrap">
<button type="submit" name="save_purchase" class="btn btn-primary">💾 Save Purchase Order</button>
<a href="index.php?page=inventory" class="btn btn-default">← Back to Inventory</a>
</div>
</form>
<div class="modal-overlay" id="addSupplierModal">
<div class="modal">
<div class="modal-header"><h3>Add New Supplier</h3><button class="modal-close" onclick="closeSupplierModal()">✕</button></div>
<form id="supplierForm" class="ajax-form" method="POST" action="index.php?page=suppliers&action=add">
<div class="form-group" style="margin-bottom:8px"><label>Name <span class="req">*</span></label><input type="text" name="name" required></div>
<div class="form-group" style="margin-bottom:8px"><label>Contact</label><input type="text" name="contact"></div>
<div class="form-group" style="margin-bottom:12px"><label>Address</label><textarea name="address" rows="2"></textarea></div>
<button type="submit" class="btn btn-primary">Save Supplier</button>
</form>
</div>
</div>
<div class="modal-overlay" id="addModelModal">
<div class="modal">
<div class="modal-header"><h3>Add New Model</h3><button class="modal-close" onclick="closeModelModal()">✕</button></div>
<form id="modelForm" class="ajax-form" method="POST" action="index.php?page=models&action=add">
<div class="form-group" style="margin-bottom:8px"><label>Model Code <span class="req">*</span></label><input type="text" name="model_code" required></div>
<div class="form-group" style="margin-bottom:8px"><label>Model Name <span class="req">*</span></label><input type="text" name="model_name" required></div>
<div class="form-group" style="margin-bottom:8px"><label>Category</label><input type="text" name="category" value="Electric Bike"></div>
<div class="form-group" style="margin-bottom:12px"><label>Short Code</label><input type="text" name="short_code"></div>
<button type="submit" class="btn btn-primary">Save Model</button>
</form>
</div>
</div>
<script>
var prefillModelId = <?= (int) ($_GET['model_id'] ?? 0) ?>;
var bikeCount = 0;
var paymentCount = 0;
var modelsOptions = `<?php $models_list->data_seek(0);
        $mo = '';
        while ($m = $models_list->fetch_assoc())
            $mo .= '<option value="' . $m['id'] . '">' . $m['model_code'] . ' - ' . $m['model_name'] . '</option>';
        echo $mo; ?>`;
var allSuppliers = <?= json_encode($conn->query('SELECT id, name FROM suppliers ORDER BY name')->fetch_all(MYSQLI_ASSOC)) ?>;
function addBikeRow() {
    bikeCount++;
    var d = document.createElement('div');
    d.className = 'bike-row animate__animated animate__fadeInDown';
    d.id = 'bikeRow_'+bikeCount;
    d.innerHTML = `<div class="bike-row-header"><span class="bike-row-num">🚲 Bike #${bikeCount}</span><button type="button" class="bike-row-del" onclick="removeBikeRow(${bikeCount})">✕ Remove</button></div>
    <div class="form-row">
    <div class="form-group"><label>Chassis Number <span class="req">*</span></label><input type="text" name="bikes[${bikeCount}][chassis]" required placeholder="e.g. KIU-2024-001" onblur="checkChassis(this)"></div>
    <div class="form-group"><label>Motor Number</label><input type="text" name="bikes[${bikeCount}][motor]" placeholder="e.g. MT-001"></div>
    <div class="form-group"><label>Model <span class="req">*</span></label>
    <div style="display:flex;gap:4px"><select name="bikes[${bikeCount}][model_id]" required class="select2-enable" style="flex:1"><option value="">-- Model --</option>${modelsOptions}</select>
    <button type="button" class="btn btn-default btn-sm" onclick="openModelModal()">+</button></div></div>
    </div>
    <div class="form-row">
    <div class="form-group"><label>Color</label><input type="text" name="bikes[${bikeCount}][color]" placeholder="Red, Black, White..."></div>
    <div class="form-group"><label>Purchase Price (Rs.) <span class="req">*</span></label><input type="number" name="bikes[${bikeCount}][purchase_price]" step="0.01" min="0" required placeholder="0.00"></div>
    <div class="form-group"><label>Safeguard Notes</label><input type="text" name="bikes[${bikeCount}][safeguard_notes]" placeholder="Helmet, Tyre, Warranty..."></div>
    </div>
    <div class="form-row">
    <div class="form-group"><label>Notes</label><input type="text" name="bikes[${bikeCount}][notes]" placeholder="Any notes..."></div>
    </div>`;
    document.getElementById('bikesList').appendChild(d);
    if (prefillModelId && bikeCount === 1) {
        $(d).find(`select[name="bikes[${bikeCount}][model_id]"]`).val(prefillModelId).trigger('change');
    }
    $(d).find('.select2-enable').select2({
        minimumResultsForSearch: 10,
        placeholder: '-- Select --',
        allowClear: false,
        theme: 'default'
    });
}
function addPaymentRow() {
    paymentCount++;
    var d = document.createElement('div');
    d.className = 'bike-row animate__animated animate__fadeInDown'; 
    d.id = 'paymentRow_'+paymentCount;
    d.innerHTML = `<div class="bike-row-header"><span class="bike-row-num">💵 Payment #${paymentCount}</span><button type="button" class="bike-row-del" onclick="removePaymentRow(${paymentCount})">✕ Remove</button></div>
    <div class="form-row">
    <div class="form-group"><label>Payment Type <span class="req">*</span></label>
        <select name="payments[${paymentCount}][payment_type]" onchange="togglePaymentFields(this, ${paymentCount})" required>
            <option value="cash">Cash</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="cheque">Cheque</option>
            <option value="online">Online</option>
        </select>
    </div>
    <div class="form-group"><label>Amount (Rs.) <span class="req">*</span></label><input type="number" name="payments[${paymentCount}][amount]" step="0.01" min="0" required placeholder="0.00"></div>
    </div>
    <div id="paymentChequeFields_${paymentCount}" style="display:none" class="form-row">
        <div class="form-group"><label>Cheque Number</label><input type="text" name="payments[${paymentCount}][cheque_number]" placeholder="CHQ-001"></div>
        <div class="form-group"><label>Bank Name</label><input type="text" name="payments[${paymentCount}][bank_name]" placeholder="HBL, MCB..."></div>
        <div class="form-group"><label>Cheque Date</label><input type="date" name="payments[${paymentCount}][cheque_date]"></div>
    </div>`;
    document.getElementById('paymentsList').appendChild(d);
}
function togglePaymentFields(selectElement, index) {
    var chequeFields = document.getElementById('paymentChequeFields_' + index);
    if (selectElement.value === 'cheque') {
        chequeFields.style.display = 'flex';
        $(chequeFields).find('input').attr('required', true);
    } else {
        chequeFields.style.display = 'none';
        $(chequeFields).find('input').removeAttr('required');
    }
}
function removeBikeRow(n) {
    var el = document.getElementById('bikeRow_'+n);
    if (el) el.remove();
}
function removePaymentRow(n) {
    var el = document.getElementById('paymentRow_'+n);
    if (el) el.remove();
}
function checkChassis(inp) {
    var val = inp.value.trim();
    if (!val) {
        inp.style.borderColor = '';
        inp.title = '';
        return;
    }
    $.ajax({
        url: 'index.php?ajax=check_chassis&chassis='+encodeURIComponent(val),
        type: 'GET',
        success: function(response) {
            if (response === '1') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Chassis Number Exists!',
                    text: 'WARNING: Chassis number "' + val + '" already exists in the system!',
                    customClass: {
                        popup: 'animate__animated animate__shakeX'
                    }
                });
                inp.classList.add('just-validate-error-field');
                inp.title = 'WARNING: This chassis number already exists!';
            } else {
                inp.classList.remove('just-validate-error-field');
                inp.style.borderColor = 'var(--success)';
                inp.title = 'Chassis number is unique.';
            }
        },
        error: function() {
            Swal.fire('Error', 'Could not check chassis number uniqueness.', 'error');
        }
    });
}
function openSupplierModal() {
    document.getElementById('addSupplierModal').classList.add('open');
}
function closeSupplierModal(selectName) {
    document.getElementById('addSupplierModal').classList.remove('open');
    $.ajax({
        url: 'index.php?ajax=get_suppliers',
        type: 'GET',
        cache: false,
        success: function(response) {
            var newOptions = JSON.parse(response);
            var supplierSelect = $('select[name="supplier_id"]');
            var currentVal = supplierSelect.val();
            supplierSelect.empty();
            supplierSelect.append('<option value="">-- Select Supplier --</option>');
            var newValToSelect = currentVal;
            var sName = selectName ? selectName.trim().toLowerCase() : null;
            newOptions.forEach(function(sup) {
                supplierSelect.append(`<option value="${sup.id}">${sup.name}</option>`);
                if (sName && sup.name.toLowerCase() === sName) newValToSelect = sup.id;
            });
            supplierSelect.val(newValToSelect).trigger('change');
        }
    });
}
function openModelModal() {
    document.getElementById('addModelModal').classList.add('open');
}
function closeModelModal(selectName) {
    document.getElementById('addModelModal').classList.remove('open');
    $.ajax({
        url: 'index.php?ajax=get_models',
        type: 'GET',
        cache: false,
        success: function(response) {
            var models = JSON.parse(response);
            var newModelId = null;
            var sName = selectName ? selectName.trim().toLowerCase() : null;
            if (sName) {
                var found = models.find(m => m.model_name.toLowerCase() === sName || m.model_code.toLowerCase() === sName);
                if (found) newModelId = found.id;
            }
            modelsOptions = models.map(m => `<option value="${m.id}">${m.model_code} - ${m.model_name}</option>`).join('');
            $('select[name$="[model_id]"]').each(function() {
                var currentVal = $(this).val();
                $(this).empty().append('<option value="">-- Model --</option>' + modelsOptions);
                if (!currentVal && newModelId) {
                    $(this).val(newModelId).trigger('change');
                } else {
                    $(this).val(currentVal).trigger('change');
                }
            });
        }
    });
}
addBikeRow();
addPaymentRow();
$(document).ready(function() {
    $('select[name="supplier_id"]').select2({
        minimumResultsForSearch: 10,
        placeholder: '-- Select Supplier --',
        allowClear: false,
        theme: 'default'
    });
});
</script>
<?php
    elseif ($page === 'inventory'):
        $status_f = sanitize($_GET['status_f'] ?? '');
        $model_f = (int) ($_GET['model_f'] ?? 0);
        $color_f = sanitize($_GET['color_f'] ?? '');
        $search_f = sanitize($_GET['search_f'] ?? '');
        $date_from = $_GET['date_from'] ?? '';
        $date_to = $_GET['date_to'] ?? '';
        $where_parts = ['1=1'];
        if ($status_f && in_array($status_f, ['in_stock', 'sold', 'returned', 'reserved']))
            $where_parts[] = "b.status='$status_f'";
        if ($model_f)
            $where_parts[] = "b.model_id=$model_f";
        if ($color_f)
            $where_parts[] = "b.color LIKE '%" . mysqli_real_escape_string($conn, $color_f) . "%'";
        if ($search_f)
            $where_parts[] = "(b.chassis_number LIKE '%" . mysqli_real_escape_string($conn, $search_f) . "%' OR b.motor_number LIKE '%" . mysqli_real_escape_string($conn, $search_f) . "%' OR m.model_name LIKE '%" . mysqli_real_escape_string($conn, $search_f) . "%' OR b.color LIKE '%" . mysqli_real_escape_string($conn, $search_f) . "%')";
        if ($date_from)
            $where_parts[] = "b.inventory_date >= '" . mysqli_real_escape_string($conn, $date_from) . "'";
        if ($date_to)
            $where_parts[] = "b.inventory_date <= '" . mysqli_real_escape_string($conn, $date_to) . "'";
        $where = implode(' AND ', $where_parts);
        $bikes_result = $conn->query("SELECT b.*, m.model_name, m.model_code, c.name as cust_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON b.customer_id=c.id WHERE $where ORDER BY b.created_at DESC");
        $models_filter_list = $conn->query('SELECT id, model_code FROM models ORDER BY model_name');
        $edit_bike_id = (int) ($_GET['edit_id'] ?? 0);
        $edit_bike = null;
        if ($edit_bike_id) {
            $er = $conn->query("SELECT * FROM bikes WHERE id=$edit_bike_id");
            $edit_bike = $er ? $er->fetch_assoc() : null;
        }
        $view_bike_id = (int) ($_GET['view_id'] ?? 0);
        $view_bike = null;
        if ($view_bike_id) {
            $vr = $conn->query("SELECT b.*, m.model_name, m.model_code, m.category, c.name as cust_name, c.phone as cust_phone, c.cnic as cust_cnic, s.name as sup_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON b.customer_id=c.id LEFT JOIN purchase_orders po ON b.purchase_order_id=po.id LEFT JOIN suppliers s ON po.supplier_id=s.id WHERE b.id=$view_bike_id");
            $view_bike = $vr ? $vr->fetch_assoc() : null;
        }
?>
<?php if ($view_bike): ?>
<div class="print-btn-wrap no-print"><button onclick="window.print()" class="btn btn-default btn-sm">🖨 Print</button> <a href="index.php?page=inventory" class="btn btn-default btn-sm">← Back</a></div>
<fieldset class="fieldset animate__animated animate__fadeIn"><legend>🚲 Bike Details — <?= sanitize($view_bike['chassis_number']) ?></legend>
<div class="split-grid-3" style="margin-bottom:12px">
<?php
            $detail_fields = [
                ['Chassis Number', $view_bike['chassis_number']],
                ['Motor Number', $view_bike['motor_number']],
                ['Model', $view_bike['model_name'] . ' (' . $view_bike['model_code'] . ')'],
                ['Category', $view_bike['category']],
                ['Color', $view_bike['color']],
                ['Status', strtoupper($view_bike['status'])],
                ['Purchase Price', fmt_money($view_bike['purchase_price'])],
                ['Selling Price', $view_bike['selling_price'] ? fmt_money($view_bike['selling_price']) : '-'],
                ['Tax Amount', fmt_money($view_bike['tax_amount'])],
                ['Margin', $view_bike['margin'] ? fmt_money($view_bike['margin']) : '-'],
                ['Order Date', fmt_date($view_bike['order_date'])],
                ['Inventory Date', fmt_date($view_bike['inventory_date'])],
                ['Selling Date', fmt_date($view_bike['selling_date'])],
                ['Customer', $view_bike['cust_name'] ?? '-'],
                ['Customer Phone', $view_bike['cust_phone'] ?? '-'],
                ['Supplier', $view_bike['sup_name'] ?? '-'],
                ['Safeguard Notes', $view_bike['safeguard_notes'] ?? '-'],
            ];
            foreach ($detail_fields as $df):
                ?>
<div style="background:var(--bg2);border:1px solid var(--border);padding:8px 10px;border-radius:1px">
<div style="font-size:0.72rem;color:var(--text2);text-transform:uppercase;font-weight:700;margin-bottom:3px"><?= $df[0] ?></div>
<div style="font-size:0.87rem;color:var(--text)"><?= sanitize($df[1] ?? '-') ?></div>
</div>
<?php endforeach; ?>
</div>
<?php if ($view_bike['notes']): ?>
<div style="margin-top:8px"><strong style="font-size:0.78rem;color:var(--text2)">NOTES:</strong> <span style="font-size:0.85rem"><?= sanitize($view_bike['notes']) ?></span></div>
<?php endif; ?>
<hr style="border-color:var(--border);margin:14px 0">
<h4 style="font-size:0.82rem;color:var(--accent);text-transform:uppercase;margin-bottom:10px">📅 Bike History Timeline</h4>
<ul class="timeline">
<li><div class="timeline-dot" style="background:#4a9eff"></div><div class="timeline-content"><div class="timeline-date"><?= fmt_date($view_bike['order_date']) ?></div><div class="timeline-text">📦 <strong>Purchased</strong> — <?= sanitize($view_bike['sup_name'] ?? 'Unknown Supplier') ?> | <?= fmt_money($view_bike['purchase_price']) ?></div></div></li>
<li><div class="timeline-dot" style="background:#4ec94e"></div><div class="timeline-content"><div class="timeline-date"><?= fmt_date($view_bike['inventory_date']) ?></div><div class="timeline-text">📋 <strong>Added to Inventory</strong> — Status: IN STOCK</div></div></li>
<?php if ($view_bike['status'] === 'sold' || $view_bike['selling_date']): ?>
<li><div class="timeline-dot" style="background:#4ec94e"></div><div class="timeline-content"><div class="timeline-date"><?= fmt_date($view_bike['selling_date']) ?></div><div class="timeline-text">🛒 <strong>Sold</strong> to <?= sanitize($view_bike['cust_name'] ?? 'Cash Customer') ?> — <?= fmt_money($view_bike['selling_price']) ?> | Margin: <?= fmt_money($view_bike['margin']) ?></div></div></li>
<?php endif; ?>
<?php if ($view_bike['status'] === 'returned' || $view_bike['return_date']): ?>
<li><div class="timeline-dot" style="background:#e74c3c"></div><div class="timeline-content"><div class="timeline-date"><?= fmt_date($view_bike['return_date']) ?></div><div class="timeline-text">↩ <strong>Returned</strong> — Amount: <?= fmt_money($view_bike['return_amount']) ?> | Notes: <?= sanitize($view_bike['return_notes'] ?? '-') ?></div></div></li>
<?php endif; ?>
</ul>
</fieldset>
<?php else: ?>
<form method="POST" id="bulkForm" action="index.php?page=inventory&action=bulk_delete">
<div class="filter-bar no-print">
<form method="GET" id="filterForm" action="index.php" style="display:contents">
<input type="hidden" name="page" value="inventory">
<div class="form-group"><label>Search</label><input type="text" name="search_f" value="<?= sanitize($search_f) ?>" placeholder="Chassis, Motor, Model, Color"></div>
<div class="form-group"><label>Status</label>
<select name="status_f">
<option value="">All</option>
<option value="in_stock" <?= $status_f === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
<option value="sold" <?= $status_f === 'sold' ? 'selected' : '' ?>>Sold</option>
<option value="returned" <?= $status_f === 'returned' ? 'selected' : '' ?>>Returned</option>
<option value="reserved" <?= $status_f === 'reserved' ? 'selected' : '' ?>>Reserved</option>
</select>
</div>
<div class="form-group"><label>Model</label>
<select name="model_f">
<option value="0">All Models</option>
<?php $models_filter_list->data_seek(0);
            while ($mf = $models_filter_list->fetch_assoc()): ?>
<option value="<?= $mf['id'] ?>" <?= $model_f == $mf['id'] ? 'selected' : '' ?>><?= sanitize($mf['model_code']) ?></option>
<?php endwhile; ?>
</select>
</div>
<div class="form-group"><label>Color</label><input type="text" name="color_f" value="<?= sanitize($color_f) ?>" placeholder="Color"></div>
<div class="form-group"><label>From</label><input type="date" name="date_from" value="<?= $date_from ?>"></div>
<div class="form-group"><label>To</label><input type="date" name="date_to" value="<?= $date_to ?>"></div>
<div class="form-group" style="justify-content:flex-end">
<button type="submit" class="btn btn-primary btn-sm">🔍 Apply Filters</button>
<a href="index.php?page=inventory" class="btn btn-default btn-sm">Reset</a>
<a href="index.php?page=inventory&export_csv=1&status_f=<?= urlencode($status_f) ?>&model_f=<?= $model_f ?>&color_f=<?= urlencode($color_f) ?>&search_f=<?= urlencode($search_f) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>" class="btn btn-default btn-sm">⬇ CSV</a>
</div>
</form>
</div>
<div style="display:flex;gap:8px;margin-bottom:8px;flex-wrap:wrap;align-items:center" class="no-print animate__animated animate__fadeInLeft">
<span style="font-size:0.8rem;color:var(--text2)">Total: <?= $bikes_result->num_rows ?> record(s)</span>
<?php if (has_permission($conn, 'purchase', 'add')): ?><a href="index.php?page=purchase" class="btn btn-success btn-sm">+ New Purchase</a><?php endif; ?>
<?php if (has_permission($conn, 'inventory', 'delete')): ?><button type="submit" name="bulk_action" value="bulk_delete" class="btn btn-danger btn-sm" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Delete Selected?', text: 'Are you sure you want to delete selected bikes? This cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete them!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🗑 Delete Selected</button><?php endif; ?>
<button type="submit" form="bulkExportForm" class="btn btn-default btn-sm">⬇ Export Selected</button>
<button onclick="window.print()" type="button" class="btn btn-default btn-sm">🖨 Print</button>
<button type="button" class="btn btn-default btn-sm" onclick="toggleSelectAll()">☑ Select All</button>
</div>
<div class="data-table-wrap">
<table class="data-table" id="invTable">
<thead>
<tr>
<th style="width:30px"><input type="checkbox" id="selectAll" onchange="toggleSelectAll()" class="no-sort"></th>
<th>Sr#</th>
<th>Chassis</th>
<th>Motor#</th>
<th>Model</th>
<th>Color</th>
<th>Purchase Price</th>
<th>Status</th>
<th>Selling Price</th>
<th>Selling Date</th>
<th>Margin</th>
<th class="no-sort">Actions</th>
</tr>
</thead>
<tbody>
<?php
            $sr = 1;
            $total_pp = 0;
            $total_sp = 0;
            $total_mg = 0;
            while ($bike = $bikes_result->fetch_assoc()):
                $st_badge = $bike['status'] === 'sold' ? 'badge-success' : ($bike['status'] === 'returned' ? 'badge-danger' : ($bike['status'] === 'reserved' ? 'badge-warning' : 'badge-info'));
                $total_pp += $bike['purchase_price'];
                $total_sp += $bike['selling_price'] ?? 0;
                $total_mg += $bike['margin'] ?? 0;
                ?>
<tr class="row-<?= $bike['status'] ?>">
<td><input type="checkbox" name="selected_bikes[]" value="<?= $bike['id'] ?>" class="bike-check"></td>
<td><?= $sr++ ?></td>
<td style="font-family:Consolas,monospace;font-size:0.8rem"><?= sanitize($bike['chassis_number']) ?></td>
<td style="font-family:Consolas,monospace;font-size:0.8rem"><?= sanitize($bike['motor_number'] ?? '-') ?></td>
<td><?= sanitize($bike['model_name'] ?? '-') ?></td>
<td><?= sanitize($bike['color'] ?? '-') ?></td>
<td><?= fmt_money($bike['purchase_price']) ?></td>
<td><span class="badge <?= $st_badge ?>"><?= strtoupper($bike['status']) ?></span></td>
<td><?= $bike['selling_price'] ? fmt_money($bike['selling_price']) : '-' ?></td>
<td><?= fmt_date($bike['selling_date']) ?></td>
<td style="color:<?= ($bike['margin'] ?? 0) >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><?= $bike['status'] === 'sold' ? fmt_money($bike['margin']) : '-' ?></td>
<td>
<div class="actions-col">
<a href="index.php?page=inventory&view_id=<?= $bike['id'] ?>" class="btn btn-default btn-sm" title="View">👁</a>
<?php if ($bike['status'] === 'in_stock' && has_permission($conn, 'sale', 'add')): ?>
<a href="index.php?page=sale&bike_id=<?= $bike['id'] ?>" class="btn btn-success btn-sm" title="Sell">🛒</a>
<?php endif; ?>
<?php if ($bike['status'] === 'sold' && has_permission($conn, 'returns', 'add')): ?>
<a href="index.php?page=returns&bike_id=<?= $bike['id'] ?>" class="btn btn-warning btn-sm" title="Return">↩</a>
<?php endif; ?>
<?php if (has_permission($conn, 'inventory', 'edit')): ?>
<a href="index.php?page=inventory&edit_id=<?= $bike['id'] ?>" class="btn btn-primary btn-sm" title="Edit">✏</a>
<?php endif; ?>
<?php if (has_permission($conn, 'inventory', 'delete')): ?>
<form method="POST" action="index.php?page=inventory&action=delete" style="display:inline">
<input type="hidden" name="id" value="<?= $bike['id'] ?>">
<button type="submit" class="btn btn-danger btn-sm" title="Delete" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Delete this bike?', text: 'Are you sure you want to delete this bike? This cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🗑</button>
</form>
<?php endif; ?>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot>
<tr>
<td colspan="6"><strong>PAGE TOTAL</strong></td>
<td><strong><?= fmt_money($total_pp) ?></strong></td>
<td></td>
<td><strong><?= fmt_money($total_sp) ?></strong></td>
<td></td>
<td style="color:<?= $total_mg >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><strong><?= fmt_money($total_mg) ?></strong></td>
<td></td>
</tr>
</tfoot>
</table>
</div>
</form>
<form method="POST" id="bulkExportForm" action="index.php?page=inventory&action=bulk_export">
<div id="hiddenBikeIds"></div>
</form>
<?php if ($edit_bike): ?>
<div class="modal-overlay open" id="editBikeModal">
<div class="modal animate__animated animate__zoomIn">
<div class="modal-header"><h3>✏ Edit Bike — <?= sanitize($edit_bike['chassis_number']) ?></h3><a href="index.php?page=inventory" class="modal-close">✕</a></div>
<form id="editBikeForm" method="POST" action="index.php?page=inventory&action=edit">
<input type="hidden" name="id" value="<?= $edit_bike['id'] ?>">
<div class="form-group" style="margin-bottom:8px"><label>Color</label><input type="text" name="color" value="<?= sanitize($edit_bike['color']) ?>"></div>
<div class="form-group" style="margin-bottom:8px"><label>Purchase Price</label><input type="number" name="purchase_price" step="0.01" value="<?= $edit_bike['purchase_price'] ?>"></div>
<div class="form-group" style="margin-bottom:8px"><label>Status</label>
<select name="status">
<option value="in_stock" <?= $edit_bike['status'] === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
<option value="sold" <?= $edit_bike['status'] === 'sold' ? 'selected' : '' ?>>Sold</option>
<option value="returned" <?= $edit_bike['status'] === 'returned' ? 'selected' : '' ?>>Returned</option>
<option value="reserved" <?= $edit_bike['status'] === 'reserved' ? 'selected' : '' ?>>Reserved</option>
</select>
</div>
<div class="form-group" style="margin-bottom:8px"><label>Safeguard Notes</label><input type="text" name="safeguard_notes" value="<?= sanitize($edit_bike['safeguard_notes'] ?? '') ?>"></div>
<div class="form-group" style="margin-bottom:12px"><label>Notes</label><textarea name="notes" rows="2"><?= sanitize($edit_bike['notes'] ?? '') ?></textarea></div>
<button type="submit" class="btn btn-primary">💾 Save Changes</button>
</form>
</div>
</div>
<?php endif; ?>
<script>
function toggleSelectAll() {
    var chk = document.getElementById('selectAll').checked;
    document.querySelectorAll('.bike-check').forEach(function(c){ c.checked = chk; });
}
document.getElementById('bulkExportForm').addEventListener('submit', function(){
    var hidden = document.getElementById('hiddenBikeIds');
    hidden.innerHTML = '';
    document.querySelectorAll('.bike-check:checked').forEach(function(c){
        var inp = document.createElement('input');
        inp.type='hidden'; inp.name='selected_bikes[]'; inp.value=c.value;
        hidden.appendChild(inp);
    });
});
</script>
<?php endif; ?>
<?php
    elseif ($page === 'sale'):
        $prefill_bike_id = (int) ($_GET['bike_id'] ?? 0);
        $prefill_bike = null;
        if ($prefill_bike_id) {
            $pr = $conn->query("SELECT b.*, m.model_name, m.model_code FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE b.id=$prefill_bike_id AND b.status='in_stock'");
            $prefill_bike = $pr ? $pr->fetch_assoc() : null;
        }
        $sale_model_id = (int) ($_GET['model_id'] ?? 0);
        $sale_where = "b.status='in_stock'";
        if ($sale_model_id)
            $sale_where .= " AND b.model_id=$sale_model_id";
        $bikes_instock = $conn->query("SELECT b.id, b.chassis_number, b.color, b.purchase_price, m.model_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE $sale_where ORDER BY b.created_at DESC");
        $customers_list = $conn->query('SELECT id, name, phone, cnic, is_filer FROM customers ORDER BY name');
        $accessories_list = $conn->query('SELECT id, name, selling_price, current_stock FROM accessories WHERE current_stock > 0 ORDER BY name');
        $last_sale_bike_id = $_SESSION['last_sale_bike_id'] ?? 0;
        unset($_SESSION['last_sale_bike_id']);
?>
<form method="POST" id="saleForm" class="animate__animated animate__fadeIn">
<input type="hidden" name="save_sale" value="1">
<fieldset class="fieldset"><legend>🛒 Sale Details</legend>
<div class="form-row">
<div class="form-group">
<label>Select Bike <span class="req">*</span></label>
<select name="bike_id" id="bikeSelect" required onchange="fillBikeDetails(this)">
<option value="">-- Select Bike (Chassis / Model / Color) --</option>
<?php
        $auto_sel = false;
        while ($bs = $bikes_instock->fetch_assoc()):
            $sel_attr = '';
            if ($prefill_bike_id == $bs['id']) {
                $sel_attr = 'selected';
            } elseif (isset($sale_model_id) && $sale_model_id > 0 && !$auto_sel) {
                $sel_attr = 'selected';
                $auto_sel = true;
            }
            ?>
<option value="<?= $bs['id'] ?>" data-pp="<?= $bs['purchase_price'] ?>" <?= $sel_attr ?>>
<?= sanitize($bs['chassis_number']) ?> | <?= sanitize($bs['model_name']) ?> | <?= sanitize($bs['color']) ?> | Pp: <?= fmt_money($bs['purchase_price']) ?>
</option>
<?php endwhile; ?>
</select>
</div>
<div class="form-group"><label>Selling Date <span class="req">*</span></label><input type="date" name="selling_date" value="<?= date('Y-m-d') ?>" required></div>
</div>
<div class="form-row">
<div class="form-group"><label>Selling Price (<?= $currency ?>) <span class="req">*</span></label><input type="number" name="selling_price" id="sellingPrice" step="0.01" min="0" required placeholder="0.00" oninput="calcMargin()"></div>
<div class="form-group"><label>Purchase Price</label><input type="text" id="purchasePriceDisplay" readonly style="background:var(--bg3);color:var(--text2)" placeholder="Auto-filled"></div>
<div class="form-group"><label>Tax Amount (<?= $tax_rate * 100 ?>% of <?= $tax_on === 'selling_price' ? 'Selling' : 'Purchase' ?> Price)</label><input type="text" id="taxDisplay" readonly style="background:var(--bg3);color:var(--text2)" placeholder="Auto-calculated"></div>
<div class="form-group"><label>Margin / Profit</label><input type="text" id="marginDisplay" readonly style="background:var(--bg3);font-weight:700" placeholder="Auto-calculated"></div>
</div>
<div class="form-row">
<div class="form-group">
<label>Customer <span class="req">*</span></label>
<div style="display:flex;gap:4px">
<select name="customer_id" id="customerSel" class="select2-enable" required style="flex:1" onchange="updateFilerStatus(this)">
<option value="0" data-is-filer="1">-- Walk-in / Cash Customer --</option>
<?php $customers_list->data_seek(0);
        while ($cl = $customers_list->fetch_assoc()): ?>
<option value="<?= $cl['id'] ?>" data-is-filer="<?= $cl['is_filer'] ?>"><?= sanitize($cl['name']) ?> — <?= sanitize($cl['phone']) ?></option>
<?php endwhile; ?>
</select>
<button type="button" class="btn btn-default btn-sm" onclick="openCustomerModal()">+</button>
</div>
</div>
<div class="form-group"><label>Customer Filer Status</label><input type="text" id="filerStatusDisplay" readonly style="background:var(--bg3);color:var(--text2)" value="Filer"></div>
<div class="form-group"><label>Down Payment (<?= $currency ?>) <span class="req">*</span></label><input type="number" name="down_payment" id="downPayment" step="0.01" min="0" value="0.00" oninput="calcRemainingBalance()" required></div>
<div class="form-group"><label>Payment Method (Down Payment) <span class="req">*</span></label>
<select name="payment_method_dp" id="payTypeDp" onchange="toggleChequeFieldsDp(this.value)">
<option value="cash">Cash</option>
<option value="cheque">Cheque</option>
<option value="bank_transfer">Bank Transfer</option>
<option value="online">Online</option>
</select>
</div>
</div>
<div id="chequeFieldsDp" style="display:none" class="form-row">
<div class="form-group"><label>Cheque Number</label><input type="text" name="cheque_number_dp" placeholder="CHQ-001"></div>
<div class="form-group"><label>Bank Name</label><input type="text" name="bank_name_dp" placeholder="HBL, MCB..."></div>
<div class="form-group"><label>Cheque Date</label><input type="date" name="cheque_date_dp"></div>
</div>
<div class="form-row">
    <div class="form-group"><label>Total Amount Due</label><input type="text" id="totalAmountDue" readonly style="background:var(--bg3);color:var(--text2)"></div>
    <div class="form-group"><label>Remaining Balance</label><input type="text" id="remainingBalance" readonly style="background:var(--bg3);color:var(--text2)"></div>
</div>
<div class="form-row">
    <div class="form-group"><label>Total Installments</label><input type="number" name="total_installments" id="totalInstallments" min="0" value="0" oninput="calcInstallments()"></div>
    <div class="form-group"><label>Installment Amount</label><input type="number" name="installment_amount" id="installmentAmount" step="0.01" min="0" value="0.00" readonly style="background:var(--bg3);color:var(--text2)"></div>
    <div class="form-group"><label>First Due Date</label><input type="date" name="first_due_date" id="firstDueDate" value="<?= date('Y-m-d', strtotime('+1 month')) ?>"></div>
</div>
</fieldset>
<fieldset class="fieldset"><legend>🛠️ Accessories Sold</legend>
    <div id="accessoriesList"></div>
    <button type="button" class="btn btn-default btn-sm" onclick="addAccessoryRow()" style="margin-top:6px">+ Add Accessory</button>
</fieldset>
<div class="form-row">
    <div class="form-group"><label>Sale Notes</label><textarea name="sale_notes" rows="2" placeholder="Any notes..."></textarea></div>
</div>
<div style="display:flex;gap:8px;flex-wrap:wrap">
<button type="submit" name="save_sale" class="btn btn-success">💾 Record Sale</button>
<a href="index.php?page=inventory" class="btn btn-default">← Back to Inventory</a>
</div>
</form>
<?php if ($last_sale_bike_id): ?>
<div style="margin-top:16px">
<a href="index.php?page=sale&print_invoice=<?= $last_sale_bike_id ?>" class="btn btn-primary" target="_blank">🖨 Print Invoice</a>
</div>
<?php endif; ?>
<?php
        $print_inv_id = (int) ($_GET['print_invoice'] ?? 0);
        if ($print_inv_id):
            $inv_r = $conn->query("SELECT b.*, m.model_name, m.model_code, m.category, c.name as cust_name, c.phone as cust_phone, c.cnic as cust_cnic, c.address as cust_addr, c.is_filer FROM bikes b LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON b.customer_id=c.id WHERE b.id=$print_inv_id");
            $inv = $inv_r ? $inv_r->fetch_assoc() : null;
            $show_pp = get_setting('show_purchase_on_invoice') == '1';
            $inv_no = 'INV-' . date('Ymd') . '-' . str_pad($print_inv_id, 3, '0', STR_PAD_LEFT);
            if ($inv):
                ?>
<div class="invoice-wrap" id="invoiceArea">
<div class="invoice-header">
<h1>⚡ <?= sanitize(get_setting('company_name') ?? 'BNI Enterprises') ?></h1>
<h2><?= sanitize(get_setting('branch_name') ?? 'Dera (Ahmed Metro)') ?></h2>
<div style="font-size:0.8rem;margin-top:4px">Sale Invoice</div>
</div>
<div style="display:flex;justify-content:space-between;margin-bottom:12px;font-size:0.82rem">
<div><strong>Invoice #:</strong> <?= $inv_no ?><br><strong>Date:</strong> <?= fmt_date($inv['selling_date']) ?></div>
<div style="text-align:right"><strong>Customer:</strong> <?= sanitize($inv['cust_name'] ?? 'Walk-in Customer') ?><br>
<?php if ($inv['cust_phone']): ?><strong>Phone:</strong> <?= sanitize($inv['cust_phone']) ?><br><?php endif; ?>
<?php if ($inv['cust_cnic']): ?><strong>CNIC:</strong> <?= sanitize($inv['cust_cnic']) ?> (<?= $inv['is_filer'] ? 'Filer' : 'Non-Filer' ?>)<br><?php endif; ?>
</div>
</div>
<div class="invoice-section">
<h3>Bike Details</h3>
<table class="invoice-table">
<thead><tr><th>Field</th><th>Details</th></tr></thead>
<tbody>
<tr><td>Model</td><td><?= sanitize($inv['model_name']) ?> (<?= sanitize($inv['model_code']) ?>)</td></tr>
<tr><td>Category</td><td><?= sanitize($inv['category']) ?></td></tr>
<tr><td>Chassis No.</td><td style="font-family:Consolas,monospace"><?= sanitize($inv['chassis_number']) ?></td></tr>
<tr><td>Motor No.</td><td style="font-family:Consolas,monospace"><?= sanitize($inv['motor_number'] ?? '-') ?></td></tr>
<tr><td>Color</td><td><?= sanitize($inv['color']) ?></td></tr>
<?php
                $sold_acc_r = $conn->query('SELECT sa.*, a.name FROM sale_accessories sa JOIN accessories a ON sa.accessory_id=a.id WHERE sa.bike_id=' . $inv['id']);
                if ($sold_acc_r->num_rows > 0):
                    ?>
<tr><td colspan="2">
    <table style="width:100%;border:none;margin-top:5px;font-size:0.8em">
    <thead><tr style="background:#f9f9f9"><th>Accessory</th><th>Qty</th><th>Unit Price</th><th>Discount</th><th>Total</th></tr></thead>
    <tbody>
    <?php while ($sa = $sold_acc_r->fetch_assoc()): ?>
    <tr><td><?= sanitize($sa['name']) ?></td><td><?= $sa['quantity'] ?></td><td><?= fmt_money($sa['unit_price']) ?></td><td><?= fmt_money($sa['discount_amount']) ?></td><td><?= fmt_money($sa['final_price']) ?></td></tr>
    <?php endwhile; ?>
    </tbody>
    </table>
</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
<div class="invoice-section">
<h3>Payment Details</h3>
<table class="invoice-table">
<thead><tr><th>Description</th><th style="text-align:right">Amount</th></tr></thead>
<tbody>
<?php if ($show_pp): ?>
<tr><td>Purchase Price</td><td style="text-align:right"><?= fmt_money($inv['purchase_price']) ?></td></tr>
<?php endif; ?>
<tr><td>Selling Price</td><td style="text-align:right"><?= fmt_money($inv['selling_price']) ?></td></tr>
<tr><td>Tax (<?= get_setting('tax_rate') * 100 ?? 0.1 ?>%)</td><td style="text-align:right"><?= fmt_money($inv['tax_amount']) ?></td></tr>
<?php
                $dp_amount = $conn->query("SELECT SUM(amount) FROM payments WHERE transaction_type='sale' AND reference_id=" . $inv['id'] . " AND payment_date='" . $inv['selling_date'] . "'")->fetch_row()[0] ?? 0;
                $installments_r = $conn->query('SELECT installment_amount, amount_paid, penalty_fee FROM installments WHERE bike_id=' . $inv['id']);
                $total_installments = 0;
                $total_paid_installments = 0;
                $total_penalty = 0;
                while ($inst = $installments_r->fetch_assoc()) {
                    $total_installments += $inst['installment_amount'];
                    $total_paid_installments += $inst['amount_paid'];
                    $total_penalty += $inst['penalty_fee'];
                }
?>
<?php if ($dp_amount > 0): ?><tr><td>Down Payment Received</td><td style="text-align:right"><?= fmt_money($dp_amount) ?></td></tr><?php endif; ?>
<?php if ($total_installments > 0): ?><tr><td>Total Installments</td><td style="text-align:right"><?= fmt_money($total_installments) ?></td></tr><?php endif; ?>
<?php if ($total_paid_installments > 0): ?><tr><td>Installments Paid</td><td style="text-align:right"><?= fmt_money($total_paid_installments) ?></td></tr><?php endif; ?>
<?php if ($total_penalty > 0): ?><tr><td>Total Penalty</td><td style="text-align:right"><?= fmt_money($total_penalty) ?></td></tr><?php endif; ?>
</tbody>
</table>
<div class="invoice-total">Total Amount: <?= fmt_money($inv['selling_price'] + $total_installments + $total_penalty) ?></div>
<div class="invoice-total">Amount Due: <?= fmt_money(($inv['selling_price'] + $total_installments + $total_penalty) - ($dp_amount + $total_paid_installments)) ?></div>
</div>
<div class="invoice-footer">Created by: Yasin Ullah – Bannu Software Solutions<br>Website: <a href="https://www.yasinbss.com" target="_blank">https://www.yasinbss.com</a><br>WhatsApp: 03361593533</div>
</div>
<div class="no-print" style="margin-top:10px"><button onclick="window.print()" class="btn btn-primary">🖨 Print Invoice</button></div>
<?php endif; ?>
<?php endif; ?>
<div class="modal-overlay" id="addCustModal">
<div class="modal">
<div class="modal-header"><h3>Add New Customer</h3><button class="modal-close" onclick="closeCustomerModal()">✕</button></div>
<form id="customerForm" class="ajax-form" method="POST" action="index.php?page=customers&action=add">
<div class="form-group" style="margin-bottom:8px"><label>Name <span class="req">*</span></label><input type="text" name="name" required></div>
<div class="form-group" style="margin-bottom:8px"><label>Phone</label><input type="text" name="phone"></div>
<div class="form-group" style="margin-bottom:8px"><label>CNIC</label><input type="text" name="cnic" placeholder="XXXXX-XXXXXXX-X"></div>
<div class="form-group" style="margin-bottom:8px"><label style="display:flex;align-items:center;gap:6px;"><input type="checkbox" name="is_filer" value="1" checked> Is Filer?</label></div>
<div class="form-group" style="margin-bottom:12px"><label>Address</label><textarea name="address" rows="2"></textarea></div>
<button type="submit" class="btn btn-primary">Save Customer</button>
</form>
</div>
</div>
<script>
var taxRate = <?= $tax_rate ?>;
var taxOn = '<?= $tax_on ?>';
var accessoryPrices = {};
var accessoriesCount = 0;
var allAccessories = <?= json_encode($conn->query('SELECT id, name, selling_price, current_stock FROM accessories')->fetch_all(MYSQLI_ASSOC)) ?>;
allAccessories.forEach(function(acc) {
    accessoryPrices[acc.id] = acc;
});
function fillBikeDetails(sel) {
    var opt = sel.options[sel.selectedIndex];
    var pp = opt.dataset.pp || 0;
    document.getElementById('purchasePriceDisplay').value = pp ? parseFloat(pp).toLocaleString('en-PK',{minimumFractionDigits:2}) : '';
    calcMargin();
    calcRemainingBalance();
}
function updateFilerStatus(selectElement) {
    var selectedOption = selectElement.options[selectElement.selectedIndex];
    var isFiler = selectedOption.dataset.isFiler;
    document.getElementById('filerStatusDisplay').value = isFiler == '1' ? 'Filer' : 'Non-Filer';
    calcMargin(); 
}
function calcMargin() {
    var sp = parseFloat(document.getElementById('sellingPrice').value) || 0;
    var pp = parseFloat(document.getElementById('purchasePriceDisplay').value.replace(/,/g,'')) || 0;
    var base = taxOn === 'selling_price' ? sp : pp;
    var tax = (base * taxRate); 
    var margin = sp - pp - tax;
    document.getElementById('taxDisplay').value = '<?= $currency ?> ' + tax.toFixed(2);
    var md = document.getElementById('marginDisplay');
    md.value = '<?= $currency ?> ' + margin.toFixed(2);
    md.style.color = margin >= 0 ? 'var(--success)' : 'var(--danger)';
    calcRemainingBalance();
}
function calcRemainingBalance() {
    var sellingPrice = parseFloat(document.getElementById('sellingPrice').value) || 0;
    var totalAccessoriesPrice = 0;
    document.querySelectorAll('[name$="[final_price]"]').forEach(function(input) {
        totalAccessoriesPrice += parseFloat(input.value) || 0;
    });
    var totalAmountDue = sellingPrice + totalAccessoriesPrice;
    
    var custSel = document.getElementById('customerSel');
    if (custSel && custSel.value == '0') {
        document.getElementById('downPayment').value = totalAmountDue.toFixed(2);
        document.getElementById('downPayment').readOnly = true;
        document.getElementById('totalInstallments').value = '0';
        document.getElementById('totalInstallments').readOnly = true;
    } else {
        var dpInput = document.getElementById('downPayment');
        if (dpInput.readOnly) {
            dpInput.readOnly = false;
        }
        var instInput = document.getElementById('totalInstallments');
        if (instInput.readOnly) {
            instInput.readOnly = false;
        }
    }
    
    var downPayment = parseFloat(document.getElementById('downPayment').value) || 0;
    var remainingBalance = totalAmountDue - downPayment;
    document.getElementById('totalAmountDue').value = '<?= $currency ?> ' + totalAmountDue.toFixed(2);
    document.getElementById('remainingBalance').value = '<?= $currency ?> ' + remainingBalance.toFixed(2);
    calcInstallments();
}
function calcInstallments() {
    var remainingBalance = parseFloat(document.getElementById('remainingBalance').value.replace(/[^0-9.-]/g, '')) || 0;
    var totalInstallments = parseInt(document.getElementById('totalInstallments').value) || 0;
    var installmentAmount = 0;
    if (totalInstallments > 0) {
        installmentAmount = remainingBalance / totalInstallments;
    }
    document.getElementById('installmentAmount').value = installmentAmount.toFixed(2);
}
function toggleChequeFieldsDp(val) {
    var chequeFields = document.getElementById('chequeFieldsDp');
    if (val === 'cheque') {
        chequeFields.style.display = 'flex';
        $(chequeFields).find('input').attr('required', true);
    } else {
        chequeFields.style.display = 'none';
        $(chequeFields).find('input').removeAttr('required');
    }
}
function addAccessoryRow() {
    accessoriesCount++;
    var d = document.createElement('div');
    d.className = 'bike-row animate__animated animate__fadeInDown';
    d.id = 'accessoryRow_' + accessoriesCount;
    var optionsHtml = '<option value="">-- Select Accessory --</option>';
    allAccessories.forEach(function(acc) {
        optionsHtml += `<option value="${acc.id}" data-price="${acc.selling_price}" data-stock="${acc.current_stock}">${acc.name} (Stock: ${acc.current_stock})</option>`;
    });
    d.innerHTML = `<div class="bike-row-header"><span class="bike-row-num">🛠️ Accessory #${accessoriesCount}</span><button type="button" class="bike-row-del" onclick="removeAccessoryRow(${accessoriesCount})">✕ Remove</button></div>
    <div class="form-row">
        <div class="form-group" style="flex:2"><label>Accessory <span class="req">*</span></label>
            <select name="selected_accessories[${accessoriesCount}][id]" required class="select2-enable" onchange="updateAccessoryDetails(this, ${accessoriesCount})">
                ${optionsHtml}
            </select>
            <span id="accStock_${accessoriesCount}" style="font-size:0.75rem;color:var(--text3)"></span>
        </div>
        <div class="form-group"><label>Quantity <span class="req">*</span></label><input type="number" name="selected_accessories[${accessoriesCount}][quantity]" value="1" min="1" required oninput="calculateAccessoryPrice(${accessoriesCount})"></div>
        <div class="form-group"><label>Unit Price</label><input type="number" name="selected_accessories[${accessoriesCount}][unit_price]" step="0.01" min="0" oninput="calculateAccessoryPrice(${accessoriesCount})"></div>
        <div class="form-group"><label>Discount</label><input type="number" name="selected_accessories[${accessoriesCount}][discount]" value="0.00" step="0.01" min="0" oninput="calculateAccessoryPrice(${accessoriesCount})"></div>
        <div class="form-group"><label>Final Price</label><input type="number" name="selected_accessories[${accessoriesCount}][final_price]" step="0.01" min="0" readonly style="background:var(--bg3);color:var(--text2)"></div>
    </div>`;
    document.getElementById('accessoriesList').appendChild(d);
    $(d).find('.select2-enable').select2({
        minimumResultsForSearch: 0,
        placeholder: '-- Select Accessory --',
        allowClear: false,
        tags: true,
        theme: 'default'
    });
}
function removeAccessoryRow(n) {
    document.getElementById('accessoryRow_' + n).remove();
    calcRemainingBalance();
}
function updateAccessoryDetails(selectElement, index) {
    var selectedOption = selectElement.options[selectElement.selectedIndex];
    var price = selectedOption && selectedOption.dataset ? (selectedOption.dataset.price || 0) : 0;
    var stock = selectedOption && selectedOption.dataset ? (selectedOption.dataset.stock || 0) : 0;
    document.querySelector(`#accessoryRow_${index} input[name="selected_accessories[${index}][unit_price]"]`).value = price;
    document.querySelector(`#accStock_${index}`).innerText = `Available: ${stock}`;
    calculateAccessoryPrice(index);
}
function calculateAccessoryPrice(index) {
    var quantity = parseInt(document.querySelector(`#accessoryRow_${index} input[name="selected_accessories[${index}][quantity]"]`).value) || 0;
    var unitPrice = parseFloat(document.querySelector(`#accessoryRow_${index} input[name="selected_accessories[${index}][unit_price]"]`).value) || 0;
    var discount = parseFloat(document.querySelector(`#accessoryRow_${index} input[name="selected_accessories[${index}][discount]"]`).value) || 0;
    var finalPrice = (quantity * unitPrice) - discount;
    document.querySelector(`#accessoryRow_${index} input[name="selected_accessories[${index}][final_price]"]`).value = finalPrice.toFixed(2);
    calcRemainingBalance();
}
function openCustomerModal() {
    document.getElementById('addCustModal').classList.add('open');
}
function closeCustomerModal(selectName) {
    document.getElementById('addCustModal').classList.remove('open');
    $.ajax({
        url: 'index.php?ajax=get_customers',
        type: 'GET',
        cache: false,
        success: function(response) {
            var newOptions = JSON.parse(response);
            var customerSelect = $('#customerSel');
            var currentVal = customerSelect.val();
            customerSelect.empty();
            customerSelect.append('<option value="0" data-is-filer="1">-- Walk-in / Cash Customer --</option>');
            var newValToSelect = currentVal;
            var sName = selectName ? selectName.trim().toLowerCase() : null;
            newOptions.forEach(function(cust) {
                customerSelect.append(`<option value="${cust.id}" data-is-filer="${cust.is_filer}">${cust.name} — ${cust.phone}</option>`);
                if (sName && cust.name.toLowerCase() === sName) newValToSelect = cust.id;
            });
            customerSelect.val(newValToSelect).trigger('change');
        }
    });
}
window.onload = function() {
    var sel = document.getElementById('bikeSelect');
    if (sel.value) fillBikeDetails(sel);
    updateFilerStatus(document.getElementById('customerSel'));
    calcRemainingBalance(); 
};
</script>
<?php
    elseif ($page === 'returns'):
        $sold_bikes = $conn->query("SELECT b.id, b.chassis_number, b.color, b.selling_price, b.purchase_price, m.model_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE b.status='sold' ORDER BY b.selling_date DESC");
        $prefill_ret_id = (int) ($_GET['bike_id'] ?? 0);
?>
<form method="POST" id="returnForm" class="animate__animated animate__fadeIn">
<input type="hidden" name="save_return" value="1">
<fieldset class="fieldset"><legend>↩ Return / Adjustment</legend>
<div class="form-row">
<div class="form-group">
<label>Select Sold Bike <span class="req">*</span></label>
<select name="bike_id" id="returnBikeSelect" required>
<option value="">-- Select Bike --</option>
<?php while ($sb = $sold_bikes->fetch_assoc()): ?>
<option value="<?= $sb['id'] ?>" <?= $prefill_ret_id == $sb['id'] ? 'selected' : '' ?>><?= sanitize($sb['chassis_number']) ?> | <?= sanitize($sb['model_name']) ?> | <?= sanitize($sb['color']) ?> | Sold: <?= fmt_money($sb['selling_price']) ?></option>
<?php endwhile; ?>
</select>
</div>
<div class="form-group"><label>Return Date <span class="req">*</span></label><input type="date" name="return_date" value="<?= date('Y-m-d') ?>" required></div>
<div class="form-group"><label>Return Amount (<?= $currency ?>) <span class="req">*</span></label><input type="number" name="return_amount" step="0.01" min="0" required placeholder="0.00"></div>
</div>
<div class="form-row">
<div class="form-group"><label>Refund Method <span class="req">*</span></label>
<select name="refund_method" id="refundMethod" onchange="toggleRetCheque(this.value)">
<option value="cash">Cash</option>
<option value="bank_transfer">Bank Transfer</option>
<option value="cheque">Cheque</option>
<option value="online">Online</option>
</select>
</div>
</div>
<div id="retChequeFields" style="display:none" class="form-row">
<div class="form-group"><label>Cheque Number</label><input type="text" name="cheque_number" placeholder="CHQ-001"></div>
<div class="form-group"><label>Bank Name</label><input type="text" name="bank_name" placeholder="HBL, MCB..."></div>
<div class="form-group"><label>Cheque Date</label><input type="date" name="cheque_date"></div>
</div>
<div class="form-row">
<div class="form-group"><label>Return Notes</label><textarea name="return_notes" rows="3" placeholder="Reason for return, cheque details, account number, etc."></textarea></div>
</div>
</fieldset>
<button type="submit" name="save_return" class="btn btn-warning">↩ Process Return</button>
<a href="index.php?page=inventory" class="btn btn-default">← Cancel</a>
</form>
<script>
function toggleRetCheque(v) {
    var chequeFields = document.getElementById('retChequeFields');
    if (v === 'cheque') {
        chequeFields.style.display = 'flex';
        $(chequeFields).find('input').attr('required', true);
    } else {
        chequeFields.style.display = 'none';
        $(chequeFields).find('input').removeAttr('required');
    }
}
$(document).ready(function() {
    $('#returnBikeSelect, #refundMethod').select2({
        minimumResultsForSearch: 10,
        placeholder: '-- Select --',
        allowClear: false,
        theme: 'default'
    });
});
</script>
<?php
    elseif ($page === 'payments'):
        $chq_status_f = sanitize($_GET['chq_status'] ?? '');
        $chq_type_f = sanitize($_GET['chq_type'] ?? '');
        $chq_bank_f = sanitize($_GET['chq_bank'] ?? '');
        $chq_from = $_GET['chq_from'] ?? '';
        $chq_to = $_GET['chq_to'] ?? '';
        $chq_where = ['1=1'];
        if ($chq_status_f && in_array($chq_status_f, ['pending', 'cleared', 'bounced', 'cancelled']))
            $chq_where[] = "p.status='$chq_status_f'";
        if ($chq_type_f && in_array($chq_type_f, ['purchase', 'sale', 'installment', 'expense_payment', 'supplier_payment', 'customer_refund']))
            $chq_where[] = "p.transaction_type='$chq_type_f'";
        if ($chq_bank_f)
            $chq_where[] = "p.bank_name LIKE '%" . mysqli_real_escape_string($conn, $chq_bank_f) . "%'";
        if ($chq_from)
            $chq_where[] = "p.payment_date >= '" . mysqli_real_escape_string($conn, $chq_from) . "'";
        if ($chq_to)
            $chq_where[] = "p.payment_date <= '" . mysqli_real_escape_string($conn, $chq_to) . "'";
        $chq_wstr = implode(' AND ', $chq_where);
        $payments_result = $conn->query("SELECT p.*, (CASE WHEN p.payment_type='cheque' THEN IFNULL(p.status, 'pending') ELSE NULL END) as status_display FROM payments p WHERE $chq_wstr ORDER BY p.payment_date DESC, p.id DESC");
        $chq_summary = $conn->query("SELECT (CASE WHEN payment_type='cheque' THEN IFNULL(status, 'pending') ELSE 'N/A' END) as status_group, COUNT(*) as cnt, SUM(amount) as total FROM payments GROUP BY status_group");
        $chq_sum_data = [];
        while ($cs = $chq_summary->fetch_assoc())
            $chq_sum_data[$cs['status_group']] = $cs;
?>
<div class="stats-row no-print animate__animated animate__fadeInUp">
<div class="stat-box"><div class="stat-val" style="color:var(--warning)"><?= number_format($chq_sum_data['pending']['cnt'] ?? 0) ?></div><div class="stat-lbl">Pending</div><div style="font-size:0.75rem;color:var(--text2)"><?= fmt_money($chq_sum_data['pending']['total'] ?? 0) ?></div></div>
<div class="stat-box"><div class="stat-val" style="color:var(--success)"><?= number_format($chq_sum_data['cleared']['cnt'] ?? 0) ?></div><div class="stat-lbl">Cleared</div><div style="font-size:0.75rem;color:var(--text2)"><?= fmt_money($chq_sum_data['cleared']['total'] ?? 0) ?></div></div>
<div class="stat-box"><div class="stat-val" style="color:var(--danger)"><?= number_format($chq_sum_data['bounced']['cnt'] ?? 0) ?></div><div class="stat-lbl">Bounced</div><div style="font-size:0.75rem;color:var(--text2)"><?= fmt_money($chq_sum_data['bounced']['total'] ?? 0) ?></div></div>
<div class="stat-box"><div class="stat-val"><?= number_format($chq_sum_data['cancelled']['cnt'] ?? 0) ?></div><div class="stat-lbl">Cancelled</div></div>
</div>
<div class="filter-bar no-print animate__animated animate__fadeInLeft">
<form method="GET" action="index.php" style="display:contents">
<input type="hidden" name="page" value="payments">
<div class="form-group"><label>Status</label>
<select name="chq_status">
<option value="">All Status</option>
<option value="pending" <?= $chq_status_f === 'pending' ? 'selected' : '' ?>>Pending</option>
<option value="cleared" <?= $chq_status_f === 'cleared' ? 'selected' : '' ?>>Cleared</option>
<option value="bounced" <?= $chq_status_f === 'bounced' ? 'selected' : '' ?>>Bounced</option>
<option value="cancelled" <?= $chq_status_f === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
</select>
</div>
<div class="form-group"><label>Type</label>
<select name="chq_type">
<option value="">All Types</option>
<option value="purchase" <?= $chq_type_f === 'purchase' ? 'selected' : '' ?>>Purchase Payment</option>
<option value="sale" <?= $chq_type_f === 'sale' ? 'selected' : '' ?>>Sale Receipt</option>
<option value="installment" <?= $chq_type_f === 'installment' ? 'selected' : '' ?>>Installment Receipt</option>
<option value="expense_payment" <?= $chq_type_f === 'expense_payment' ? 'selected' : '' ?>>Expense Payment</option>
<option value="supplier_payment" <?= $chq_type_f === 'supplier_payment' ? 'selected' : '' ?>>Supplier Payment</option>
<option value="customer_refund" <?= $chq_type_f === 'customer_refund' ? 'selected' : '' ?>>Customer Refund</option>
</select>
</div>
<div class="form-group"><label>Bank</label><input type="text" name="chq_bank" value="<?= sanitize($chq_bank_f) ?>" placeholder="Bank name"></div>
<div class="form-group"><label>From</label><input type="date" name="chq_from" value="<?= $chq_from ?>"></div>
<div class="form-group"><label>To</label><input type="date" name="chq_to" value="<?= $chq_to ?>"></div>
<button type="submit" class="btn btn-primary btn-sm" style="align-self:flex-end">🔍 Filter</button>
<a href="index.php?page=payments" class="btn btn-default btn-sm" style="align-self:flex-end">Reset</a>
</form>
</div>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Date</th><th>Type</th><th>Method</th><th>Amount</th><th>Cheque #</th><th>Bank</th><th>Chq Date</th><th>Status</th><th>Party</th><th>Ref</th><th class="no-print">Actions</th></tr></thead>
<tbody>
<?php
        $sr = 1;
        $payments_total_amt = 0;
        while ($pay = $payments_result->fetch_assoc()):
            $payments_total_amt += $pay['amount'];
            $st_badge = '';
            if ($pay['payment_type'] === 'cheque') {
                $st_badge = $pay['status_display'] === 'cleared' ? 'badge-success' : ($pay['status_display'] === 'bounced' ? 'badge-danger' : ($pay['status_display'] === 'cancelled' ? 'badge-default' : 'badge-warning'));
            } else {
                $st_badge = 'badge-info';
            }
            $type_badge = '';
            switch ($pay['transaction_type']) {
                case 'sale':
                case 'installment':
                    $type_badge = 'badge-success';
                    break;
                case 'purchase':
                case 'supplier_payment':
                case 'expense_payment':
                    $type_badge = 'badge-danger';
                    break;
                case 'customer_refund':
                    $type_badge = 'badge-warning';
                    break;
                default:
                    $type_badge = 'badge-default';
                    break;
            }
            ?>
<tr>
<td><?= $sr++ ?></td>
<td><?= fmt_date($pay['payment_date']) ?></td>
<td><span class="badge <?= $type_badge ?>"><?= strtoupper(str_replace('_', ' ', $pay['transaction_type'])) ?></span></td>
<td><?= sanitize($pay['payment_type']) ?></td>
<td><?= fmt_money($pay['amount']) ?></td>
<td style="font-family:Consolas,monospace"><?= sanitize($pay['cheque_number'] ?? '-') ?></td>
<td><?= sanitize($pay['bank_name'] ?? '-') ?></td>
<td><?= fmt_date($pay['cheque_date']) ?></td>
<td><span class="badge <?= $st_badge ?>"><?= $pay['payment_type'] === 'cheque' ? strtoupper($pay['status_display']) : 'N/A' ?></span></td>
<td><?= sanitize($pay['party_name']) ?></td>
<td><?= sanitize($pay['reference_id'] ?? '-') ?></td>
<td class="no-print">
<div class="actions-col">
<?php if ($pay['payment_type'] === 'cheque' && $pay['status_display'] === 'pending' && has_permission($conn, 'payments', 'edit')): ?>
<form method="POST" action="index.php?page=payments&action=status" style="display:inline">
<input type="hidden" name="id" value="<?= $pay['id'] ?>">
<input type="hidden" name="status" value="cleared">
<button type="submit" class="btn btn-success btn-sm" title="Mark Cleared">✓ Clear</button>
</form>
<form method="POST" action="index.php?page=payments&action=status" style="display:inline">
<input type="hidden" name="id" value="<?= $pay['id'] ?>">
<input type="hidden" name="status" value="bounced">
<button type="submit" class="btn btn-danger btn-sm" title="Mark Bounced" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Mark as Bounced?', text: 'Are you sure you want to mark this cheque as bounced?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, mark bounced!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">✗ Bounce</button>
</form>
<?php endif; ?>
<?php if (has_permission($conn, 'payments', 'delete')): ?>
<form method="POST" action="index.php?page=payments&action=delete" style="display:inline">
<input type="hidden" name="id" value="<?= $pay['id'] ?>">
<button type="submit" class="btn btn-danger btn-sm" title="Delete" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Delete this payment?', text: 'Are you sure you want to delete this payment entry? This cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🗑</button>
</form>
<?php endif; ?>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td colspan="4"><strong>TOTAL</strong></td><td><strong><?= fmt_money($payments_total_amt) ?></strong></td><td colspan="7"></td></tr></tfoot>
</table>
</div>
<?php
    elseif ($page === 'installments'):
        $status_f = sanitize($_GET['status_f'] ?? '');
        $customer_f = (int) ($_GET['customer_f'] ?? 0);
        $due_from = $_GET['due_from'] ?? '';
        $due_to = $_GET['due_to'] ?? '';
        $where = ['1=1'];
        if ($status_f)
            $where[] = "i.status='$status_f'";
        if ($customer_f)
            $where[] = "i.customer_id=$customer_f";
        if ($due_from)
            $where[] = "i.due_date >= '$due_from'";
        if ($due_to)
            $where[] = "i.due_date <= '$due_to'";
        $where_str = implode(' AND ', $where);
        $installments_r = $conn->query("SELECT i.*, b.chassis_number, b.model_id, m.model_name, c.name as customer_name, c.phone as customer_phone FROM installments i LEFT JOIN bikes b ON i.bike_id=b.id LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON i.customer_id=c.id WHERE $where_str ORDER BY i.due_date ASC");
        $customers_list = $conn->query('SELECT id, name, phone FROM customers ORDER BY name');
?>
<div class="filter-bar no-print animate__animated animate__fadeInLeft">
<form method="GET" action="index.php" style="display:contents">
<input type="hidden" name="page" value="installments">
<div class="form-group"><label>Status</label>
<select name="status_f">
<option value="">All Status</option>
<option value="pending" <?= $status_f === 'pending' ? 'selected' : '' ?>>Pending</option>
<option value="paid" <?= $status_f === 'paid' ? 'selected' : '' ?>>Paid</option>
<option value="overdue" <?= $status_f === 'overdue' ? 'selected' : '' ?>>Overdue</option>
<option value="cancelled" <?= $status_f === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
</select>
</div>
<div class="form-group"><label>Customer</label>
<select name="customer_f">
<option value="0">All Customers</option>
<?php while ($cl = $customers_list->fetch_assoc()): ?>
<option value="<?= $cl['id'] ?>" <?= $customer_f == $cl['id'] ? 'selected' : '' ?>><?= sanitize($cl['name']) ?> - <?= sanitize($cl['phone']) ?></option>
<?php endwhile; ?>
</select>
</div>
<div class="form-group"><label>Due From</label><input type="date" name="due_from" value="<?= $due_from ?>"></div>
<div class="form-group"><label>Due To</label><input type="date" name="due_to" value="<?= $due_to ?>"></div>
<button type="submit" class="btn btn-primary btn-sm" style="align-self:flex-end">🔍 Filter</button>
<a href="index.php?page=installments" class="btn btn-default btn-sm" style="align-self:flex-end">Reset</a>
</form>
</div>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Due Date</th><th>Customer</th><th>Chassis</th><th>Model</th><th>Installment Amount</th><th>Amount Paid</th><th>Penalty</th><th>Status</th><th class="no-sort">Actions</th></tr></thead>
<tbody>
<?php
        $sr = 1;
        $total_installments_amt = 0;
        $total_amount_paid = 0;
        $total_penalty = 0;
        while ($inst = $installments_r->fetch_assoc()):
            $total_installments_amt += $inst['installment_amount'];
            $total_amount_paid += $inst['amount_paid'];
            $total_penalty += $inst['penalty_fee'];
            $status_badge = '';
            $row_class = '';
            switch ($inst['status']) {
                case 'paid':
                    $status_badge = 'badge-success';
                    $row_class = 'row-sold';
                    break;
                case 'overdue':
                    $status_badge = 'badge-danger';
                    $row_class = 'row-returned';
                    break;
                case 'cancelled':
                    $status_badge = 'badge-default';
                    break;
                default:
                    $status_badge = 'badge-warning';
                    $row_class = 'row-reserved';
                    break;
            }
            ?>
<tr class="<?= $row_class ?>">
<td><?= $sr++ ?></td>
<td><?= fmt_date($inst['due_date']) ?></td>
<td><?= sanitize($inst['customer_name'] ?? '-') ?></td>
<td><?= sanitize($inst['chassis_number'] ?? '-') ?></td>
<td><?= sanitize($inst['model_name'] ?? '-') ?></td>
<td><?= fmt_money($inst['installment_amount']) ?></td>
<td><?= fmt_money($inst['amount_paid']) ?></td>
<td><?= fmt_money($inst['penalty_fee']) ?></td>
<td><span class="badge <?= $status_badge ?>"><?= strtoupper($inst['status']) ?></span></td>
<td class="no-print">
<div class="actions-col">
<?php if ($inst['status'] === 'pending' || $inst['status'] === 'overdue' && has_permission($conn, 'installments', 'edit')): ?>
<button type="button" class="btn btn-success btn-sm" onclick="openPayInstallmentModal(<?= $inst['id'] ?>, '<?= fmt_date($inst['due_date']) ?>', <?= $inst['installment_amount'] ?>, <?= $inst['amount_paid'] ?>, <?= $inst['penalty_fee'] ?>)">💵 Pay</button>
<?php endif; ?>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot>
<tr>
<td colspan="5"><strong>TOTAL</strong></td>
<td><strong><?= fmt_money($total_installments_amt) ?></strong></td>
<td><strong><?= fmt_money($total_amount_paid) ?></strong></td>
<td><strong><?= fmt_money($total_penalty) ?></strong></td>
<td colspan="2"></td>
</tr>
</tfoot>
</table>
</div>
<div class="modal-overlay" id="payInstallmentModal">
<div class="modal">
<div class="modal-header"><h3>Pay Installment</h3><button class="modal-close" onclick="closePayInstallmentModal()">✕</button></div>
<form id="payInstallmentForm" method="POST" action="index.php?page=installments&action=pay_installment">
<input type="hidden" name="installment_id" id="modalInstallmentId">
<div class="form-group" style="margin-bottom:8px"><label>Installment Due Date</label><input type="text" id="modalDueDate" readonly style="background:var(--bg3);color:var(--text2)"></div>
<div class="form-group" style="margin-bottom:8px"><label>Installment Amount</label><input type="text" id="modalInstallmentAmount" readonly style="background:var(--bg3);color:var(--text2)"></div>
<div class="form-group" style="margin-bottom:8px"><label>Already Paid</label><input type="text" id="modalAmountPaidPrev" readonly style="background:var(--bg3);color:var(--text2)"></div>
<div class="form-group" style="margin-bottom:8px"><label>Amount to Pay <span class="req">*</span></label><input type="number" name="amount_paid" step="0.01" min="0" required></div>
<div class="form-group" style="margin-bottom:8px"><label>Penalty Fee</label><input type="number" name="penalty_fee" step="0.01" min="0" value="0.00"></div>
<div class="form-group" style="margin-bottom:8px"><label>Payment Date <span class="req">*</span></label><input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required></div>
<div class="form-group" style="margin-bottom:8px"><label>Payment Type <span class="req">*</span></label>
    <select name="payment_type" onchange="togglePayInstCheque(this.value)">
        <option value="cash">Cash</option>
        <option value="bank_transfer">Bank Transfer</option>
        <option value="cheque">Cheque</option>
        <option value="online">Online</option>
    </select>
</div>
<div id="payInstChequeFields" style="display:none">
    <div class="form-group" style="margin-bottom:8px"><label>Cheque Number</label><input type="text" name="cheque_number"></div>
    <div class="form-group" style="margin-bottom:8px"><label>Bank Name</label><input type="text" name="bank_name"></div>
    <div class="form-group" style="margin-bottom:12px"><label>Cheque Date</label><input type="date" name="cheque_date"></div>
</div>
<button type="submit" class="btn btn-primary">Save Payment</button>
</form>
</div>
</div>
<script>
function openPayInstallmentModal(id, dueDate, installmentAmount, amountPaidPrev, penaltyPaidPrev) {
    document.getElementById('modalInstallmentId').value = id;
    document.getElementById('modalDueDate').value = dueDate;
    document.getElementById('modalInstallmentAmount').value = '<?= $currency ?> ' + parseFloat(installmentAmount).toFixed(2);
    document.getElementById('modalAmountPaidPrev').value = '<?= $currency ?> ' + parseFloat(amountPaidPrev).toFixed(2);
    document.querySelector('#payInstallmentModal input[name="amount_paid"]').value = (installmentAmount - amountPaidPrev).toFixed(2);
    document.querySelector('#payInstallmentModal input[name="penalty_fee"]').value = '0.00'; 
    document.getElementById('payInstallmentModal').classList.add('open');
    togglePayInstCheque('cash'); 
}
function closePayInstallmentModal() {
    document.getElementById('payInstallmentModal').classList.remove('open');
}
function togglePayInstCheque(val) {
    var chequeFields = document.getElementById('payInstChequeFields');
    if (val === 'cheque') {
        chequeFields.style.display = 'block';
        $(chequeFields).find('input').attr('required', true);
    } else {
        chequeFields.style.display = 'none';
        $(chequeFields).find('input').removeAttr('required');
    }
}
$(document).ready(function() {
    $('select[name="status_f"], select[name="customer_f"], #payInstallmentModal select[name="payment_type"]').select2({
        minimumResultsForSearch: 10,
        placeholder: '-- Select --',
        allowClear: false,
        theme: 'default'
    });
});
</script>
<?php
    elseif ($page === 'customer_ledger'):
        $sel_cust = (int) ($_GET['cust_id'] ?? 0);
        $customers_for_led = $conn->query('SELECT id, name, phone FROM customers ORDER BY name');
?>
<div class="filter-bar no-print animate__animated animate__fadeInLeft">
<form method="GET" action="index.php" style="display:contents">
<input type="hidden" name="page" value="customer_ledger">
<div class="form-group"><label>Select Customer <span class="req">*</span></label>
<select name="cust_id" required onchange="this.form.submit()">
<option value="0">-- Select Customer --</option>
<?php while ($cl = $customers_for_led->fetch_assoc()): ?>
<option value="<?= $cl['id'] ?>" <?= $sel_cust == $cl['id'] ? 'selected' : '' ?>><?= sanitize($cl['name']) ?> — <?= sanitize($cl['phone']) ?></option>
<?php endwhile; ?>
</select>
</div>
</form>
</div>
<?php
        if ($sel_cust > 0):
            $cust_info = $conn->query("SELECT * FROM customers WHERE id=$sel_cust")->fetch_assoc();
            $ledger_entries = $conn->query("SELECT * FROM ledger WHERE party_type='customer' AND party_id=$sel_cust ORDER BY entry_date ASC, id ASC");
            $running_bal = 0;

            $sums = $conn->query("SELECT 
                SUM(CASE WHEN reference_type='sale' THEN amount ELSE 0 END) - SUM(CASE WHEN reference_type='return_reversal' THEN amount ELSE 0 END) as total_billed, 
                SUM(CASE WHEN reference_type IN ('payment','down_payment','installment') THEN amount ELSE 0 END) - SUM(CASE WHEN reference_type='return_refund' THEN amount ELSE 0 END) as total_paid,
                SUM(CASE WHEN entry_type='debit' THEN amount ELSE 0 END) as total_dr, 
                SUM(CASE WHEN entry_type='credit' THEN amount ELSE 0 END) as total_cr 
                FROM ledger WHERE party_type='customer' AND party_id=$sel_cust")->fetch_assoc();
            $total_dr_summary = $sums['total_billed'] ?? 0;
            $total_cr_summary = $sums['total_paid'] ?? 0;
            $bal_summary = ($sums['total_dr'] ?? 0) - ($sums['total_cr'] ?? 0);
            ?>
<div class="split-grid-3 animate__animated animate__fadeInDown" style="margin-bottom:14px">
    <div class="card danger"><div class="card-icon">🛒</div><div class="card-body"><div class="card-label">Total Billed</div><div class="card-value"><?= fmt_money($total_dr_summary) ?></div></div></div>
    <div class="card success"><div class="card-icon">💵</div><div class="card-body"><div class="card-label">Total Paid</div><div class="card-value"><?= fmt_money($total_cr_summary) ?></div></div></div>
    <div class="card warning"><div class="card-icon">⚖️</div><div class="card-body"><div class="card-label">Remaining Balance</div><div class="card-value" style="color:<?= $bal_summary > 0 ? 'var(--danger)' : 'var(--success)' ?>"><?= fmt_money(abs($bal_summary)) ?> <?= $bal_summary > 0 ? 'Due' : 'Advance' ?></div></div></div>
</div>
<div class="print-btn-wrap no-print animate__animated animate__fadeInRight" style="display:flex;gap:8px;">
<button onclick="document.getElementById('receivePaymentModal').classList.add('open')" class="btn btn-success btn-sm">+ Receive Payment</button>
<button onclick="window.print()" class="btn btn-default btn-sm">🖨 Print Ledger</button>
</div>

<div class="modal-overlay" id="receivePaymentModal">
<div class="modal">
<div class="modal-header"><h3>Receive Payment</h3><button class="modal-close" onclick="document.getElementById('receivePaymentModal').classList.remove('open')">✕</button></div>
<form method="POST">
<input type="hidden" name="add_payment" value="1">
<div class="form-group" style="margin-bottom:8px"><label>Date <span class="req">*</span></label><input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required></div>
<div class="form-group" style="margin-bottom:8px"><label>Amount <span class="req">*</span></label><input type="number" name="amount" step="0.01" min="0.01" required value="<?= $bal_summary > 0 ? $bal_summary : '' ?>"></div>
<div class="form-group" style="margin-bottom:8px"><label>Method</label><select name="payment_method"><option value="cash">Cash</option><option value="bank_transfer">Bank Transfer</option><option value="cheque">Cheque</option><option value="online">Online</option></select></div>
<div class="form-group" style="margin-bottom:12px"><label>Notes</label><textarea name="notes" rows="2" placeholder="Cheque number, bank details, etc..."></textarea></div>
<button type="submit" class="btn btn-primary">Save Payment</button>
</form>
</div>
</div>

<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>👤 Customer Ledger — <?= sanitize($cust_info['name']) ?></legend>
<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:12px;font-size:0.83rem">
<span><strong>Phone:</strong> <?= sanitize($cust_info['phone'] ?? '-') ?></span>
<span><strong>CNIC:</strong> <?= sanitize($cust_info['cnic'] ?? '-') ?></span>
<span><strong>Address:</strong> <?= sanitize($cust_info['address'] ?? '-') ?></span>
<span><strong>Filer Status:</strong> <?= $cust_info['is_filer'] ? 'Filer' : 'Non-Filer' ?></span>
</div>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Date</th><th>Description</th><th>Debit</th><th>Credit</th><th>Balance</th></tr></thead>
<tbody>
<?php
            $sr = 1;
            $total_dr = 0;
            $total_cr = 0;
            while ($le = $ledger_entries->fetch_assoc()):
                if ($le['entry_type'] === 'debit') {
                    $running_bal -= $le['amount'];
                    $total_dr += $le['amount'];
                } else {
                    $running_bal += $le['amount'];
                    $total_cr += $le['amount'];
                }
                ?>
<tr>
<td><?= $sr++ ?></td>
<td><?= fmt_date($le['entry_date']) ?></td>
<td><?= sanitize($le['description']) ?></td>
<td><?= $le['entry_type'] === 'debit' ? fmt_money($le['amount']) : '-' ?></td>
<td><?= $le['entry_type'] === 'credit' ? fmt_money($le['amount']) : '-' ?></td>
<td style="color:<?= $running_bal >= 0 ? 'var(--success)' : 'var(--danger)' ?>;font-weight:700"><?= fmt_money(abs($running_bal)) ?> <?= $running_bal >= 0 ? 'Cr' : 'Dr' ?></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot>
<tr>
<td colspan="3"><strong>TOTAL</strong></td>
<td><strong><?= fmt_money($total_dr) ?></strong></td>
<td><strong><?= fmt_money($total_cr) ?></strong></td>
<td style="color:<?= $running_bal >= 0 ? 'var(--success)' : 'var(--danger)' ?>;font-weight:700"><strong><?= fmt_money(abs($running_bal)) ?> <?= $running_bal >= 0 ? 'Cr' : 'Dr' ?></strong></td>
</tr>
</tfoot>
</table>
</div>
<h4 style="font-size:0.82rem;color:var(--accent);margin:14px 0 8px;text-transform:uppercase">🚲 Purchase History</h4>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Date</th><th>Chassis</th><th>Model</th><th>Color</th><th>Selling Price</th><th>Status</th></tr></thead>
<tbody>
<?php
            $cust_bikes = $conn->query("SELECT b.*, m.model_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE b.customer_id=$sel_cust ORDER BY b.selling_date DESC");
            while ($cb = $cust_bikes->fetch_assoc()):
                ?>
<tr class="row-<?= $cb['status'] ?>">
<td><?= fmt_date($cb['selling_date']) ?></td>
<td><?= sanitize($cb['chassis_number']) ?></td>
<td><?= sanitize($cb['model_name']) ?></td>
<td><?= sanitize($cb['color']) ?></td>
<td><?= fmt_money($cb['selling_price']) ?></td>
<td><span class="badge badge-<?= $cb['status'] === 'sold' ? 'success' : ($cb['status'] === 'returned' ? 'danger' : 'info') ?>"><?= strtoupper($cb['status']) ?></span></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</fieldset>
<script>
$(document).ready(function() {
    $('select[name="cust_id"]').select2({
        minimumResultsForSearch: 10,
        placeholder: '-- Select Customer --',
        allowClear: false,
        theme: 'default'
    });
});
</script>
<?php endif; ?>
<?php
    elseif ($page === 'supplier_ledger'):
        $sel_sup = (int) ($_GET['sup_id'] ?? 0);
        $suppliers_for_led = $conn->query('SELECT id, name FROM suppliers ORDER BY name');
?>
<div class="filter-bar no-print animate__animated animate__fadeInLeft">
<form method="GET" action="index.php" style="display:contents">
<input type="hidden" name="page" value="supplier_ledger">
<div class="form-group"><label>Select Supplier</label>
<select name="sup_id" required onchange="this.form.submit()">
<option value="0">-- Select Supplier --</option>
<?php while ($sl = $suppliers_for_led->fetch_assoc()): ?>
<option value="<?= $sl['id'] ?>" <?= $sel_sup == $sl['id'] ? 'selected' : '' ?>><?= sanitize($sl['name']) ?></option>
<?php endwhile; ?>
</select>
</div>
</form>
</div>
<?php
        if ($sel_sup > 0):
            $sup_info = $conn->query("SELECT * FROM suppliers WHERE id=$sel_sup")->fetch_assoc();
            $sup_orders = $conn->query("SELECT po.*, IFNULL(SUM(b.purchase_price), po.total_amount) as bikes_total, COUNT(b.id) as bike_count FROM purchase_orders po LEFT JOIN bikes b ON po.id=b.purchase_order_id WHERE po.supplier_id=$sel_sup GROUP BY po.id ORDER BY po.order_date ASC");
            $supplier_payments = $conn->query("SELECT * FROM payments WHERE transaction_type='supplier_payment' AND (party_name = '" . mysqli_real_escape_string($conn, $sup_info['name']) . "' OR reference_id IN (SELECT id FROM purchase_orders WHERE supplier_id=$sel_sup)) ORDER BY payment_date ASC");
            $running_bal = 0;

            $purchase_total_sum = 0;
            $payment_total_sum = 0;
            while ($order = $sup_orders->fetch_assoc())
                $purchase_total_sum += $order['bikes_total'];
            while ($payment = $supplier_payments->fetch_assoc())
                $payment_total_sum += $payment['amount'];
            $bal_summary = $purchase_total_sum - $payment_total_sum;
            ?>
<div class="split-grid-3 animate__animated animate__fadeInDown" style="margin-bottom:14px">
    <div class="card danger"><div class="card-icon">📦</div><div class="card-body"><div class="card-label">Total Purchased</div><div class="card-value"><?= fmt_money($purchase_total_sum) ?></div></div></div>
    <div class="card success"><div class="card-icon">💵</div><div class="card-body"><div class="card-label">Total Paid</div><div class="card-value"><?= fmt_money($payment_total_sum) ?></div></div></div>
    <div class="card warning"><div class="card-icon">⚖️</div><div class="card-body"><div class="card-label">Remaining Balance</div><div class="card-value" style="color:<?= $bal_summary > 0 ? 'var(--danger)' : 'var(--success)' ?>"><?= fmt_money(abs($bal_summary)) ?> <?= $bal_summary > 0 ? 'Payable' : 'Advance' ?></div></div></div>
</div>
<div class="print-btn-wrap no-print animate__animated animate__fadeInRight" style="display:flex;gap:8px;">
<button onclick="document.getElementById('makePaymentModal').classList.add('open')" class="btn btn-success btn-sm">+ Make Payment</button>
<button onclick="window.print()" class="btn btn-default btn-sm">🖨 Print Ledger</button>
</div>

<div class="modal-overlay" id="makePaymentModal">
<div class="modal">
<div class="modal-header"><h3>Make Payment to Supplier</h3><button class="modal-close" onclick="document.getElementById('makePaymentModal').classList.remove('open')">✕</button></div>
<form method="POST">
<input type="hidden" name="add_sup_payment" value="1">
<div class="form-group" style="margin-bottom:8px"><label>Date <span class="req">*</span></label><input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required></div>
<div class="form-group" style="margin-bottom:8px"><label>Amount <span class="req">*</span></label><input type="number" name="amount" step="0.01" min="0.01" required value="<?= $bal_summary > 0 ? $bal_summary : '' ?>"></div>
<div class="form-group" style="margin-bottom:8px"><label>Method</label><select name="payment_method"><option value="cash">Cash</option><option value="bank_transfer">Bank Transfer</option><option value="cheque">Cheque</option><option value="online">Online</option></select></div>
<div class="form-group" style="margin-bottom:12px"><label>Notes</label><textarea name="notes" rows="2" placeholder="Cheque number, bank details, etc..."></textarea></div>
<button type="submit" class="btn btn-primary">Save Payment</button>
</form>
</div>
</div>

<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>🏭 Supplier Ledger — <?= sanitize($sup_info['name']) ?></legend>
<div style="margin-bottom:10px;font-size:0.83rem">
<strong>Contact:</strong> <?= sanitize($sup_info['contact'] ?? '-') ?> | <strong>Address:</strong> <?= sanitize($sup_info['address'] ?? '-') ?>
</div>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Date</th><th>Description</th><th>Debit (Purchased Value)</th><th>Credit (Payments)</th><th>Balance</th></tr></thead>
<tbody>
<?php
            $sr = 1;
            $purchase_total = 0;
            $payment_total = 0;
            $transactions = [];
            $sup_orders->data_seek(0);
            while ($order = $sup_orders->fetch_assoc()) {
                $transactions[] = [
                    'date' => $order['order_date'],
                    'type' => 'purchase',
                    'amount' => $order['bikes_total'],
                    'description' => "Purchase Order #{$order['id']} ({$order['bike_count']} bikes)",
                    'id' => $order['id']
                ];
            }
            $supplier_payments->data_seek(0);
            while ($payment = $supplier_payments->fetch_assoc()) {
                $transactions[] = [
                    'date' => $payment['payment_date'],
                    'type' => 'payment',
                    'amount' => $payment['amount'],
                    'description' => "Payment #{$payment['id']} ({$payment['payment_type']} - " . ($payment['cheque_number'] ?? '-') . ')',
                    'id' => $payment['id']
                ];
            }
            usort($transactions, function ($a, $b) {
                if ($a['date'] == $b['date']) {
                    return $a['id'] - $b['id'];
                }
                return strtotime($a['date']) - strtotime($b['date']);
            });
            foreach ($transactions as $trans):
                $debit = 0;
                $credit = 0;
                if ($trans['type'] === 'purchase') {
                    $debit = $trans['amount'];
                    $running_bal -= $debit;
                    $purchase_total += $debit;
                } else {
                    $credit = $trans['amount'];
                    $running_bal += $credit;
                    $payment_total += $credit;
                }
                ?>
<tr>
<td><?= $sr++ ?></td>
<td><?= fmt_date($trans['date']) ?></td>
<td><?= sanitize($trans['description']) ?></td>
<td><?= $debit > 0 ? fmt_money($debit) : '-' ?></td>
<td><?= $credit > 0 ? fmt_money($credit) : '-' ?></td>
<td style="color:<?= $running_bal >= 0 ? 'var(--success)' : 'var(--danger)' ?>;font-weight:700"><?= fmt_money(abs($running_bal)) ?> <?= $running_bal >= 0 ? 'Cr' : 'Dr' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot>
<tr>
<td colspan="3"><strong>TOTAL</strong></td>
<td><strong><?= fmt_money($purchase_total) ?></strong></td>
<td><strong><?= fmt_money($payment_total) ?></strong></td>
<td style="color:<?= $running_bal >= 0 ? 'var(--success)' : 'var(--danger)' ?>;font-weight:700"><strong><?= fmt_money(abs($running_bal)) ?> <?= $running_bal >= 0 ? 'Cr' : 'Dr' ?></strong></td>
</tr>
</tfoot>
</table>
</div>
</fieldset>
<script>
$(document).ready(function() {
    $('select[name="sup_id"]').select2({
        minimumResultsForSearch: 10,
        placeholder: '-- Select Supplier --',
        allowClear: false,
        theme: 'default'
    });
});
</script>
<?php endif; ?>
<?php
    elseif ($page === 'reports'):
        $sub = sanitize($_GET['sub'] ?? 'stock');
        $rep_from = !empty($_GET['rep_from']) ? $_GET['rep_from'] : date('Y-01-01');
        $rep_to = !empty($_GET['rep_to']) ? $_GET['rep_to'] : date('Y-12-31');
        $rep_year = !empty($_GET['rep_year']) ? (int) $_GET['rep_year'] : (int) date('Y');
        $rep_month = !empty($_GET['rep_month']) ? (int) $_GET['rep_month'] : (int) date('n');
?>
<div class="sub-tabs no-print animate__animated animate__fadeInDown">
<?php
        $sub_items = [
            ['stock', '📦 Current Stock'],
            ['sold', '✅ Sold Bikes'],
            ['model_wise', '📊 Model-wise'],
            ['tax', '🧾 Tax Report'],
            ['profit', '📈 Profit/Margin'],
            ['bank', '💳 Bank/Cheque'],
            ['monthly', '📅 Monthly Summary'],
            ['daily', '📆 Daily Ledger'],
            ['purchase_vs_sales', '🔄 Purchase vs Sales'],
            ['accessory_stock', '🛠️ Accessory Stock'],
            ['installments_summary', '🗓️ Installments Summary'],
        ];
        foreach ($sub_items as $si):
            ?>
<a href="index.php?page=reports&sub=<?= $si[0] ?>&rep_from=<?= $rep_from ?>&rep_to=<?= $rep_to ?>&rep_year=<?= $rep_year ?>&rep_month=<?= $rep_month ?>" class="sub-tab <?= $sub === $si[0] ? 'active' : '' ?>"><?= $si[1] ?></a>
<?php endforeach; ?>
</div>
<div class="filter-bar no-print animate__animated animate__fadeInLeft">
<form method="GET" action="index.php" style="display:contents">
<input type="hidden" name="page" value="reports">
<input type="hidden" name="sub" value="<?= $sub ?>">
<div class="form-group"><label>From Date</label><input type="date" name="rep_from" value="<?= $rep_from ?>"></div>
<div class="form-group"><label>To Date</label><input type="date" name="rep_to" value="<?= $rep_to ?>"></div>
<div class="form-group"><label>Year</label><input type="number" name="rep_year" value="<?= $rep_year ?>" min="2000" max="2100" style="width:90px"></div>
<div class="form-group"><label>Month</label>
<select name="rep_month">
<?php for ($m = 1; $m <= 12; $m++): ?>
<option value="<?= $m ?>" <?= $rep_month == $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
<?php endfor; ?>
</select>
</div>
<button type="submit" class="btn btn-primary btn-sm" style="align-self:flex-end">🔍 Filter</button>
</form>
<button onclick="window.print()" class="btn btn-default btn-sm" style="align-self:flex-end">🖨 Print</button>
</div>
<?php
        if ($sub === 'stock'):
            $stock_bikes = $conn->query("SELECT b.*, m.model_name, m.category, m.short_code FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE b.status='in_stock' ORDER BY m.model_name, b.inventory_date");
            $stk_total = 0;
            ?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>📦 Current Stock Report</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Chassis</th><th>Motor#</th><th>Model</th><th>Category</th><th>Color</th><th>Purchase Price</th><th>Inventory Date</th><th>Days in Stock</th></tr></thead>
<tbody>
<?php
            $sr = 1;
            while ($bk = $stock_bikes->fetch_assoc()):
                $days = (int) ((time() - strtotime($bk['inventory_date'])) / 86400);
                $stk_total += $bk['purchase_price'];
                ?>
<tr>
<td><?= $sr++ ?></td>
<td style="font-family:Consolas,monospace"><?= sanitize($bk['chassis_number']) ?></td>
<td style="font-family:Consolas,monospace"><?= sanitize($bk['motor_number'] ?? '-') ?></td>
<td><?= sanitize($bk['model_name']) ?></td>
<td><?= sanitize($bk['category']) ?></td>
<td><?= sanitize($bk['color']) ?></td>
<td><?= fmt_money($bk['purchase_price']) ?></td>
<td><?= fmt_date($bk['inventory_date']) ?></td>
<td><?= $days ?> days</td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td colspan="6"><strong>TOTAL</strong></td><td><strong><?= fmt_money($stk_total) ?></strong></td><td colspan="2"></td></tr></tfoot>
</table>
</div>
</fieldset>
<?php
        elseif ($sub === 'sold'):
            $sold_bikes_r = $conn->query("SELECT b.*, m.model_name, m.short_code, c.name as cust_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON b.customer_id=c.id WHERE b.status='sold' AND b.selling_date BETWEEN '" . mysqli_real_escape_string($conn, $rep_from) . "' AND '" . mysqli_real_escape_string($conn, $rep_to) . "' ORDER BY b.selling_date DESC");
            $sold_total_sp = 0;
            $sold_total_pp = 0;
            $sold_total_mg = 0;
            $sold_total_tax = 0;
?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>✅ Sold Bikes Report (<?= fmt_date($rep_from) ?> - <?= fmt_date($rep_to) ?>)</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Chassis</th><th>Model</th><th>Color</th><th>Customer</th><th>Selling Date</th><th>Purchase Price</th><th>Selling Price</th><th>Tax</th><th>Margin</th></tr></thead>
<tbody>
<?php
            $sr = 1;
            while ($sb = $sold_bikes_r->fetch_assoc()):
                $sold_total_sp += $sb['selling_price'];
                $sold_total_pp += $sb['purchase_price'];
                $sold_total_mg += $sb['margin'];
                $sold_total_tax += $sb['tax_amount'];
                ?>
<tr class="row-sold">
<td><?= $sr++ ?></td>
<td style="font-family:Consolas,monospace"><?= sanitize($sb['chassis_number']) ?></td>
<td><?= sanitize($sb['model_name']) ?></td>
<td><?= sanitize($sb['color']) ?></td>
<td><?= sanitize($sb['cust_name'] ?? 'Walk-in') ?></td>
<td><?= fmt_date($sb['selling_date']) ?></td>
<td><?= fmt_money($sb['purchase_price']) ?></td>
<td><?= fmt_money($sb['selling_price']) ?></td>
<td><?= fmt_money($sb['tax_amount']) ?></td>
<td style="color:<?= $sb['margin'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><?= fmt_money($sb['margin']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot>
<tr>
<td colspan="6"><strong>TOTAL</strong></td>
<td><strong><?= fmt_money($sold_total_pp) ?></strong></td>
<td><strong><?= fmt_money($sold_total_sp) ?></strong></td>
<td><strong><?= fmt_money($sold_total_tax) ?></strong></td>
<td style="color:<?= $sold_total_mg >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><strong><?= fmt_money($sold_total_mg) ?></strong></td>
</tr>
</tfoot>
</table>
</div>
</fieldset>
<?php
        elseif ($sub === 'model_wise'):
            $mw_result = $conn->query("SELECT m.model_name, m.short_code, m.category,
    COUNT(b.id) as total_inv,
    SUM(CASE WHEN b.status='sold' THEN 1 ELSE 0 END) as sold_cnt,
    SUM(CASE WHEN b.status='in_stock' THEN 1 ELSE 0 END) as avail_cnt,
    SUM(CASE WHEN b.status='returned' THEN 1 ELSE 0 END) as ret_cnt,
    SUM(b.purchase_price) as total_pp,
    SUM(CASE WHEN b.status='sold' THEN b.selling_price ELSE 0 END) as total_sp,
    SUM(CASE WHEN b.status='sold' THEN b.margin ELSE 0 END) as total_mg
    FROM models m LEFT JOIN bikes b ON m.id=b.model_id
    GROUP BY m.id ORDER BY m.model_name");
            $mw_t = [0, 0, 0, 0, 0, 0, 0];
?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>📊 Model-wise Sales Report</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Model</th><th>Short Code</th><th>Category</th><th>Inventory</th><th>Sold</th><th>Available</th><th>Returned</th><th>Total Purchase</th><th>Total Sales</th><th>Total Margin</th></tr></thead>
<tbody>
<?php
            while ($mw = $mw_result->fetch_assoc()):
                $mw_t[0] += $mw['total_inv'];
                $mw_t[1] += $mw['sold_cnt'];
                $mw_t[2] += $mw['avail_cnt'];
                $mw_t[3] += $mw['ret_cnt'];
                $mw_t[4] += $mw['total_pp'];
                $mw_t[5] += $mw['total_sp'];
                $mw_t[6] += $mw['total_mg'];
                ?>
<tr>
<td><strong><?= sanitize($mw['model_name']) ?></strong></td>
<td><?= sanitize($mw['short_code']) ?></td>
<td><?= sanitize($mw['category']) ?></td>
<td><?= $mw['total_inv'] ?></td>
<td><span class="badge badge-success"><?= $mw['sold_cnt'] ?></span></td>
<td><span class="badge badge-info"><?= $mw['avail_cnt'] ?></span></td>
<td><span class="badge badge-danger"><?= $mw['ret_cnt'] ?></span></td>
<td><?= fmt_money($mw['total_pp']) ?></td>
<td><?= fmt_money($mw['total_sp']) ?></td>
<td style="color:<?= $mw['total_mg'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><?= fmt_money($mw['total_mg']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td><strong>TOTAL</strong></td><td colspan="3"><strong><?= $mw_t[0] ?></strong></td><td><strong><?= $mw_t[1] ?></strong></td><td><strong><?= $mw_t[2] ?></strong></td><td><strong><?= $mw_t[3] ?></strong></td><td><strong><?= fmt_money($mw_t[4]) ?></strong></td><td><strong><?= fmt_money($mw_t[5]) ?></strong></td><td style="color:var(--success)"><strong><?= fmt_money($mw_t[6]) ?></strong></td></tr></tfoot>
</table>
</div>
</fieldset>
<?php
        elseif ($sub === 'tax'):
            $tax_result = $conn->query("SELECT DATE_FORMAT(selling_date,'%Y-%m') as ym, COUNT(*) as cnt, SUM(tax_amount) as total_tax, SUM(purchase_price) as total_pp FROM bikes WHERE status='sold' AND selling_date BETWEEN '" . mysqli_real_escape_string($conn, $rep_from) . "' AND '" . mysqli_real_escape_string($conn, $rep_to) . "' GROUP BY ym ORDER BY ym DESC");
            $tax_total = 0;
?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>🧾 Tax Report by Month</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Month</th><th>Bikes Sold</th><th>Total Purchase Value</th><th>Tax Amount (<?= get_setting('tax_rate') * 100 ?? 0.1 ?>%)</th></tr></thead>
<tbody>
<?php
            while ($tr = $tax_result->fetch_assoc()):
                $tax_total += $tr['total_tax'];
                ?>
<tr>
<td><?= date('F Y', strtotime($tr['ym'] . '-01')) ?></td>
<td><?= $tr['cnt'] ?></td>
<td><?= fmt_money($tr['total_pp']) ?></td>
<td><?= fmt_money($tr['total_tax']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td colspan="3"><strong>TOTAL TAX</strong></td><td><strong><?= fmt_money($tax_total) ?></strong></td></tr></tfoot>
</table>
</div>
</fieldset>
<?php
        elseif ($sub === 'profit'):
            $profit_monthly = $conn->query("SELECT DATE_FORMAT(selling_date,'%Y-%m') as ym, COUNT(*) as cnt, SUM(selling_price) as total_sp, SUM(purchase_price) as total_pp, SUM(margin) as total_margin, SUM(tax_amount) as total_tax FROM bikes WHERE status='sold' AND selling_date BETWEEN '" . mysqli_real_escape_string($conn, $rep_from) . "' AND '" . mysqli_real_escape_string($conn, $rep_to) . "' GROUP BY ym ORDER BY ym DESC");
            $profit_t = [0, 0, 0, 0, 0];
?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>📈 Profit / Margin Report</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Month</th><th>Bikes Sold</th><th>Total Purchase</th><th>Total Sales</th><th>Total Tax</th><th>Net Profit</th><th>Avg Margin</th></tr></thead>
<tbody>
<?php
            while ($pm = $profit_monthly->fetch_assoc()):
                $profit_t[0] += $pm['cnt'];
                $profit_t[1] += $pm['total_pp'];
                $profit_t[2] += $pm['total_sp'];
                $profit_t[3] += $pm['total_tax'];
                $profit_t[4] += $pm['total_margin'];
                $avg_margin = $pm['cnt'] > 0 ? $pm['total_margin'] / $pm['cnt'] : 0;
                ?>
<tr>
<td><?= date('F Y', strtotime($pm['ym'] . '-01')) ?></td>
<td><?= $pm['cnt'] ?></td>
<td><?= fmt_money($pm['total_pp']) ?></td>
<td><?= fmt_money($pm['total_sp']) ?></td>
<td><?= fmt_money($pm['total_tax']) ?></td>
<td style="color:<?= $pm['total_margin'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>;font-weight:700"><?= fmt_money($pm['total_margin']) ?></td>
<td><?= fmt_money($avg_margin) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot>
<tr>
<td><strong>TOTAL</strong></td>
<td><strong><?= $profit_t[0] ?></strong></td>
<td><strong><?= fmt_money($profit_t[1]) ?></strong></td>
<td><strong><?= fmt_money($profit_t[2]) ?></strong></td>
<td><strong><?= fmt_money($profit_t[3]) ?></strong></td>
<td style="color:var(--success)"><strong><?= fmt_money($profit_t[4]) ?></strong></td>
<td><strong><?= $profit_t[0] > 0 ? fmt_money($profit_t[4] / $profit_t[0]) : fmt_money(0) ?></strong></td>
</tr>
</tfoot>
</table>
</div>
</fieldset>
<?php
        elseif ($sub === 'bank'):
            $bank_result = $conn->query("SELECT bank_name, payment_type, transaction_type, COUNT(*) as cnt, SUM(amount) as total FROM payments WHERE payment_type = 'cheque' GROUP BY bank_name, payment_type, transaction_type ORDER BY bank_name, transaction_type");
            $bank_data = [];
            while ($br2 = $bank_result->fetch_assoc()) {
                $bank_data[$br2['bank_name']][$br2['transaction_type']]['count'] = $br2['cnt'];
                $bank_data[$br2['bank_name']][$br2['transaction_type']]['total'] = $br2['total'];
            }
?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>💳 Bank / Cheque Report</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Bank</th><th>Transaction Type</th><th>Total Cheques</th><th>Total Amount</th></tr></thead>
<tbody>
<?php
            $bank_totals = [0, 0];
            foreach ($bank_data as $bank => $types):
                foreach ($types as $type => $data):
                    $cnt = $data['count'] ?? 0;
                    $ttl = $data['total'] ?? 0;
                    $bank_totals[0] += $cnt;
                    $bank_totals[1] += $ttl;
                    ?>
<tr>
<td><?= sanitize($bank) ?></td>
<td><span class="badge badge-<?= ($type === 'sale' || $type === 'installment') ? 'success' : ($type === 'customer_refund' ? 'warning' : 'info') ?>"><?= strtoupper(str_replace('_', ' ', $type)) ?></span></td>
<td><?= $cnt ?></td>
<td><?= fmt_money($ttl) ?></td>
</tr>
<?php endforeach;
            endforeach; ?>
</tbody>
<tfoot><tr><td colspan="2"><strong>TOTAL</strong></td><td><strong><?= $bank_totals[0] ?></strong></td><td><strong><?= fmt_money($bank_totals[1]) ?></strong></td></tr></tfoot>
</table>
</div>
</fieldset>
<?php
        elseif ($sub === 'monthly'):
            $monthly_r = $conn->query("SELECT DATE_FORMAT(order_date,'%Y-%m') as ym, COUNT(*) as purchased, SUM(purchase_price) as pp_total FROM bikes WHERE YEAR(order_date)=$rep_year GROUP BY ym");
            $sold_monthly_r = $conn->query("SELECT DATE_FORMAT(selling_date,'%Y-%m') as ym, COUNT(*) as sold_cnt, SUM(selling_price) as sp_total, SUM(margin) as mg_total FROM bikes WHERE status='sold' AND YEAR(selling_date)=$rep_year GROUP BY ym");
            $monthly_purch = [];
            $monthly_sales = [];
            while ($mr = $monthly_r->fetch_assoc())
                $monthly_purch[$mr['ym']] = $mr;
            while ($mr2 = $sold_monthly_r->fetch_assoc())
                $monthly_sales[$mr2['ym']] = $mr2;
            $all_months = array_unique(array_merge(array_keys($monthly_purch), array_keys($monthly_sales)));
            sort($all_months);
?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>📅 Monthly Summary — <?= $rep_year ?></legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Month</th><th>Purchased Units</th><th>Purchase Value</th><th>Sold Units</th><th>Sales Value</th><th>Profit</th></tr></thead>
<tbody>
<?php
            $mt = [0, 0, 0, 0, 0];
            foreach ($all_months as $ym):
                $p = $monthly_purch[$ym] ?? null;
                $s = $monthly_sales[$ym] ?? null;
                $mt[0] += $p['purchased'] ?? 0;
                $mt[1] += $p['pp_total'] ?? 0;
                $mt[2] += $s['sold_cnt'] ?? 0;
                $mt[3] += $s['sp_total'] ?? 0;
                $mt[4] += $s['mg_total'] ?? 0;
                ?>
<tr>
<td><?= date('F Y', strtotime($ym . '-01')) ?></td>
<td><?= $p['purchased'] ?? 0 ?></td>
<td><?= fmt_money($p['pp_total'] ?? 0) ?></td>
<td><?= $s['sold_cnt'] ?? 0 ?></td>
<td><?= fmt_money($s['sp_total'] ?? 0) ?></td>
<td style="color:<?= ($s['mg_total'] ?? 0) >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><?= fmt_money($s['mg_total'] ?? 0) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot><tr><td><strong>TOTAL</strong></td><td><strong><?= $mt[0] ?></strong></td><td><strong><?= fmt_money($mt[1]) ?></strong></td><td><strong><?= $mt[2] ?></strong></td><td><strong><?= fmt_money($mt[3]) ?></strong></td><td style="color:var(--success)"><strong><?= fmt_money($mt[4]) ?></strong></td></tr></tfoot>
</table>
</div>
</fieldset>
<?php
        elseif ($sub === 'daily'):
            $daily_date = $_GET['daily_date'] ?? date('Y-m-d');
            $daily_sales = $conn->query("SELECT b.*, m.model_name, c.name as cust_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON b.customer_id=c.id WHERE b.selling_date='" . mysqli_real_escape_string($conn, $daily_date) . "' AND b.status='sold'");
            $daily_purch = $conn->query("SELECT b.*, m.model_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE b.inventory_date='" . mysqli_real_escape_string($conn, $daily_date) . "'");
            $daily_expenses = $conn->query("SELECT * FROM income_expenses WHERE entry_date='" . mysqli_real_escape_string($conn, $daily_date) . "' AND type='expense'");
            $daily_income_other = $conn->query("SELECT * FROM income_expenses WHERE entry_date='" . mysqli_real_escape_string($conn, $daily_date) . "' AND type='income'");
?>
<div class="filter-bar no-print" style="margin-bottom:8px">
<form method="GET" action="index.php" style="display:contents">
<input type="hidden" name="page" value="reports">
<input type="hidden" name="sub" value="daily">
<div class="form-group"><label>Select Date</label><input type="date" name="daily_date" value="<?= $daily_date ?>"></div>
<button type="submit" class="btn btn-primary btn-sm" style="align-self:flex-end">🔍 View</button>
</form>
</div>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>📆 Daily Ledger — <?= fmt_date($daily_date) ?></legend>
<h4 style="font-size:0.82rem;color:var(--success);margin-bottom:6px">Sales</h4>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Chassis</th><th>Model</th><th>Customer</th><th>Selling Price</th><th>Tax</th><th>Margin</th></tr></thead>
<tbody>
<?php $d_sp = 0;
            $d_mg = 0;
            $d_tax = 0;
            while ($ds = $daily_sales->fetch_assoc()):
                $d_sp += $ds['selling_price'];
                $d_mg += $ds['margin'];
                $d_tax += $ds['tax_amount']; ?>
<tr class="row-sold"><td><?= sanitize($ds['chassis_number']) ?></td><td><?= sanitize($ds['model_name']) ?></td><td><?= sanitize($ds['cust_name'] ?? 'Walk-in') ?></td><td><?= fmt_money($ds['selling_price']) ?></td><td><?= fmt_money($ds['tax_amount']) ?></td><td style="color:var(--success)"><?= fmt_money($ds['margin']) ?></td></tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td colspan="3"><strong>TOTAL</strong></td><td><strong><?= fmt_money($d_sp) ?></strong></td><td><strong><?= fmt_money($d_tax) ?></strong></td><td style="color:var(--success)"><strong><?= fmt_money($d_mg) ?></strong></td></tr></tfoot>
</table>
</div>
<h4 style="font-size:0.82rem;color:var(--accent);margin:10px 0 6px">Inventory Added</h4>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Chassis</th><th>Motor#</th><th>Model</th><th>Color</th><th>Purchase Price</th><th>Status</th></tr></thead>
<tbody>
<?php $d_pp = 0;
            while ($dp = $daily_purch->fetch_assoc()):
                $d_pp += $dp['purchase_price']; ?>
<tr><td><?= sanitize($dp['chassis_number']) ?></td><td><?= sanitize($dp['motor_number'] ?? '-') ?></td><td><?= sanitize($dp['model_name']) ?></td><td><?= sanitize($dp['color']) ?></td><td><?= fmt_money($dp['purchase_price']) ?></td><td><span class="badge badge-<?= $dp['status'] === 'in_stock' ? 'info' : ($dp['status'] === 'sold' ? 'success' : 'danger') ?>"><?= strtoupper($dp['status']) ?></span></td></tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td colspan="4"><strong>TOTAL</strong></td><td><strong><?= fmt_money($d_pp) ?></strong></td><td></td></tr></tfoot>
</table>
</div>
<h4 style="font-size:0.82rem;color:var(--danger);margin:10px 0 6px">Expenses</h4>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Category</th><th>Amount</th><th>Method</th><th>Notes</th></tr></thead>
<tbody>
<?php $d_exp_total = 0;
            while ($exp = $daily_expenses->fetch_assoc()):
                $d_exp_total += $exp['amount']; ?>
<tr>
<td><?= sanitize($exp['category']) ?></td>
<td><?= fmt_money($exp['amount']) ?></td>
<td><?= sanitize($exp['payment_method']) ?></td>
<td><?= sanitize($exp['notes']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td><strong>TOTAL</strong></td><td><strong><?= fmt_money($d_exp_total) ?></strong></td><td colspan="2"></td></tr></tfoot>
</table>
</div>
<h4 style="font-size:0.82rem;color:var(--success);margin:10px 0 6px">Other Income</h4>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Category</th><th>Amount</th><th>Method</th><th>Notes</th></tr></thead>
<tbody>
<?php $d_inc_total = 0;
            while ($inc = $daily_income_other->fetch_assoc()):
                $d_inc_total += $inc['amount']; ?>
<tr>
<td><?= sanitize($inc['category']) ?></td>
<td><?= fmt_money($inc['amount']) ?></td>
<td><?= sanitize($inc['payment_method']) ?></td>
<td><?= sanitize($inc['notes']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td><strong>TOTAL</strong></td><td><strong><?= fmt_money($d_inc_total) ?></strong></td><td colspan="2"></td></tr></tfoot>
</table>
</div>
</fieldset>
<?php
        elseif ($sub === 'purchase_vs_sales'):
            $pvs = $conn->query("SELECT DATE_FORMAT(order_date,'%Y-%m') as ym, COUNT(*) as p_cnt, SUM(purchase_price) as p_val FROM bikes WHERE YEAR(order_date)=$rep_year GROUP BY ym");
            $svp = $conn->query("SELECT DATE_FORMAT(selling_date,'%Y-%m') as ym, COUNT(*) as s_cnt, SUM(selling_price) as s_val FROM bikes WHERE status='sold' AND YEAR(selling_date)=$rep_year GROUP BY ym");
            $pvs_data = [];
            $svp_data = [];
            while ($r = $pvs->fetch_assoc())
                $pvs_data[$r['ym']] = $r;
            while ($r = $svp->fetch_assoc())
                $svp_data[$r['ym']] = $r;
            $all_m = array_unique(array_merge(array_keys($pvs_data), array_keys($svp_data)));
            sort($all_m);
?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>🔄 Purchase vs Sales — <?= $rep_year ?></legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Month</th><th>Purchased</th><th>Purchase Value</th><th>Sold</th><th>Sales Value</th><th>Difference</th></tr></thead>
<tbody>
<?php
            $pt = [0, 0, 0, 0];
            foreach ($all_m as $ym):
                $p = $pvs_data[$ym] ?? null;
                $s = $svp_data[$ym] ?? null;
                $diff = ($s['s_val'] ?? 0) - ($p['p_val'] ?? 0);
                $pt[0] += $p['p_cnt'] ?? 0;
                $pt[1] += $p['p_val'] ?? 0;
                $pt[2] += $s['s_cnt'] ?? 0;
                $pt[3] += $s['s_val'] ?? 0;
                ?>
<tr>
<td><?= date('F Y', strtotime($ym . '-01')) ?></td>
<td><?= $p['p_cnt'] ?? 0 ?></td>
<td><?= fmt_money($p['p_val'] ?? 0) ?></td>
<td><?= $s['s_cnt'] ?? 0 ?></td>
<td><?= fmt_money($s['s_val'] ?? 0) ?></td>
<td style="color:<?= $diff >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><?= fmt_money($diff) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot><tr><td><strong>TOTAL</strong></td><td><strong><?= $pt[0] ?></strong></td><td><strong><?= fmt_money($pt[1]) ?></strong></td><td><strong><?= $pt[2] ?></strong></td><td><strong><?= fmt_money($pt[3]) ?></strong></td><td style="color:<?= ($pt[3] - $pt[1]) >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><strong><?= fmt_money($pt[3] - $pt[1]) ?></strong></td></tr></tfoot>
</table>
</div>
</fieldset>
<?php
        elseif ($sub === 'accessory_stock'):
            $acc_stock_r = $conn->query('SELECT * FROM accessories ORDER BY name');
            $acc_total_stock = 0;
            $acc_total_value_pp = 0;
            $acc_total_value_sp = 0;
?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>🛠️ Accessory Stock Report</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Accessory Name</th><th>SKU</th><th>Purchase Price</th><th>Selling Price</th><th>Current Stock</th><th>Total Purchase Value</th><th>Total Selling Value</th></tr></thead>
<tbody>
<?php
            $sr = 1;
            while ($acc = $acc_stock_r->fetch_assoc()):
                $total_pp_val = $acc['purchase_price'] * $acc['current_stock'];
                $total_sp_val = $acc['selling_price'] * $acc['current_stock'];
                $acc_total_stock += $acc['current_stock'];
                $acc_total_value_pp += $total_pp_val;
                $acc_total_value_sp += $total_sp_val;
                ?>
<tr>
<td><?= $sr++ ?></td>
<td><?= sanitize($acc['name']) ?></td>
<td><?= sanitize($acc['sku']) ?></td>
<td><?= fmt_money($acc['purchase_price']) ?></td>
<td><?= fmt_money($acc['selling_price']) ?></td>
<td><?= $acc['current_stock'] ?></td>
<td><?= fmt_money($total_pp_val) ?></td>
<td><?= fmt_money($total_sp_val) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot>
<tr>
<td colspan="5"><strong>TOTAL</strong></td>
<td><strong><?= $acc_total_stock ?></strong></td>
<td><strong><?= fmt_money($acc_total_value_pp) ?></strong></td>
<td><strong><?= fmt_money($acc_total_value_sp) ?></strong></td>
</tr>
</tfoot>
</table>
</div>
</fieldset>
<?php
        elseif ($sub === 'installments_summary'):
            $inst_sum_r = $conn->query("SELECT
                c.name AS customer_name,
                COUNT(i.id) AS total_installments,
                SUM(i.installment_amount) AS total_due_amount,
                SUM(i.amount_paid) AS total_paid_amount,
                SUM(i.penalty_fee) AS total_penalty,
                SUM(CASE WHEN i.status = 'pending' AND i.due_date < CURDATE() THEN (i.installment_amount - i.amount_paid + i.penalty_fee) ELSE 0 END) AS overdue_balance,
                SUM(CASE WHEN i.status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
                SUM(CASE WHEN i.status = 'overdue' THEN 1 ELSE 0 END) AS overdue_count
            FROM installments i
            LEFT JOIN customers c ON i.customer_id = c.id
            GROUP BY c.id
            ORDER BY c.name");
?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>🗓️ Installments Summary Report</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Customer</th><th>Total Installments</th><th>Total Due Amount</th><th>Total Paid</th><th>Total Penalty</th><th>Overdue Balance</th><th>Pending Count</th><th>Overdue Count</th></tr></thead>
<tbody>
<?php
            $sr = 1;
            $overall_totals = ['installments' => 0, 'due' => 0, 'paid' => 0, 'penalty' => 0, 'overdue_bal' => 0, 'pending_cnt' => 0, 'overdue_cnt' => 0];
            while ($sum = $inst_sum_r->fetch_assoc()):
                $overall_totals['installments'] += $sum['total_installments'];
                $overall_totals['due'] += $sum['total_due_amount'];
                $overall_totals['paid'] += $sum['total_paid_amount'];
                $overall_totals['penalty'] += $sum['total_penalty'];
                $overall_totals['overdue_bal'] += $sum['overdue_balance'];
                $overall_totals['pending_cnt'] += $sum['pending_count'];
                $overall_totals['overdue_cnt'] += $sum['overdue_count'];
                ?>
<tr>
<td><?= $sr++ ?></td>
<td><?= sanitize($sum['customer_name']) ?></td>
<td><?= $sum['total_installments'] ?></td>
<td><?= fmt_money($sum['total_due_amount']) ?></td>
<td><?= fmt_money($sum['total_paid_amount']) ?></td>
<td><?= fmt_money($sum['total_penalty']) ?></td>
<td style="color:<?= $sum['overdue_balance'] > 0 ? 'var(--danger)' : 'var(--success)' ?>"><?= fmt_money($sum['overdue_balance']) ?></td>
<td><?= $sum['pending_count'] ?></td>
<td style="color:var(--danger)"><?= $sum['overdue_count'] ?></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot>
<tr>
<td colspan="2"><strong>TOTAL</strong></td>
<td><strong><?= $overall_totals['installments'] ?></strong></td>
<td><strong><?= fmt_money($overall_totals['due']) ?></strong></td>
<td><strong><?= fmt_money($overall_totals['paid']) ?></strong></td>
<td><strong><?= fmt_money($overall_totals['penalty']) ?></strong></td>
<td style="color:<?= $overall_totals['overdue_bal'] > 0 ? 'var(--danger)' : 'var(--success)' ?>"><strong><?= fmt_money($overall_totals['overdue_bal']) ?></strong></td>
<td><strong><?= $overall_totals['pending_cnt'] ?></strong></td>
<td><strong><?= $overall_totals['overdue_cnt'] ?></strong></td>
</tr>
</tfoot>
</table>
</div>
</fieldset>
<?php endif; ?>
<?php
    elseif ($page === 'models'):
        $models_result = $conn->query("SELECT m.*, COUNT(b.id) as bike_count, SUM(CASE WHEN b.status='in_stock' THEN 1 ELSE 0 END) as in_stock, SUM(CASE WHEN b.status='sold' THEN 1 ELSE 0 END) as sold_cnt FROM models m LEFT JOIN bikes b ON m.id=b.model_id GROUP BY m.id ORDER BY m.model_name");
        $edit_model_id = (int) ($_GET['edit_id'] ?? 0);
        $edit_model = null;
        if ($edit_model_id) {
            $em = $conn->query("SELECT * FROM models WHERE id=$edit_model_id");
            $edit_model = $em ? $em->fetch_assoc() : null;
        }
?>
<div style="display:flex;gap:8px;margin-bottom:10px" class="no-print animate__animated animate__fadeInLeft">
<?php if (has_permission($conn, 'models', 'add')): ?>
<button class="btn btn-success" onclick="document.getElementById('addModelFormArea').style.display='block';document.getElementById('addModelFormArea').scrollIntoView()">+ Add Model</button>
<?php endif; ?>
</div>
<div id="addModelFormArea" style="display:<?= $edit_model ? 'block' : 'none' ?>;margin-bottom:14px" class="animate__animated animate__fadeIn">
<fieldset class="fieldset"><legend><?= $edit_model ? '✏ Edit Model' : '+ Add New Model' ?></legend>
<form id="modelForm" method="POST" action="index.php?page=models&action=<?= $edit_model ? 'edit' : 'add' ?>">
<?php if ($edit_model): ?><input type="hidden" name="id" value="<?= $edit_model['id'] ?>"><?php endif; ?>
<div class="form-row">
<div class="form-group"><label>Model Code <span class="req">*</span></label><input type="text" name="model_code" value="<?= sanitize($edit_model['model_code'] ?? '') ?>" required></div>
<div class="form-group"><label>Model Name <span class="req">*</span></label><input type="text" name="model_name" value="<?= sanitize($edit_model['model_name'] ?? '') ?>" required></div>
<div class="form-group"><label>Category</label><input type="text" name="category" value="<?= sanitize($edit_model['category'] ?? 'Electric Bike') ?>"></div>
<div class="form-group"><label>Short Code</label><input type="text" name="short_code" value="<?= sanitize($edit_model['short_code'] ?? '') ?>"></div>
</div>
<button type="submit" class="btn btn-primary">💾 Save</button>
<button type="button" class="btn btn-default" onclick="document.getElementById('addModelFormArea').style.display='none'">Cancel</button>
</form>
</fieldset>
</div>
<div class="data-table-wrap animate__animated animate__fadeInUp">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Model Code</th><th>Model Name</th><th>Category</th><th>Short Code</th><th>Total Inventory</th><th>In Stock</th><th>Sold</th><th class="no-sort">Actions</th></tr></thead>
<tbody>
<?php $sr = 1;
        while ($mdl = $models_result->fetch_assoc()): ?>
<tr>
<td><?= $sr++ ?></td>
<td><strong><?= sanitize($mdl['model_code']) ?></strong></td>
<td><?= sanitize($mdl['model_name']) ?></td>
<td><?= sanitize($mdl['category']) ?></td>
<td><code><?= sanitize($mdl['short_code']) ?></code></td>
<td><?= $mdl['bike_count'] ?></td>
<td><span class="badge badge-info"><?= $mdl['in_stock'] ?></span></td>
<td><span class="badge badge-success"><?= $mdl['sold_cnt'] ?></span></td>
<td class="no-print">
<div class="actions-col">
<?php if (has_permission($conn, 'purchase', 'add')): ?><a href="index.php?page=purchase&model_id=<?= $mdl['id'] ?>" class="btn btn-success btn-sm" title="Purchase">📦</a><?php endif; ?>
<?php if (has_permission($conn, 'sale', 'add')): ?><a href="index.php?page=sale&model_id=<?= $mdl['id'] ?>" class="btn btn-warning btn-sm" title="Sell">🛒</a><?php endif; ?>
<?php if (has_permission($conn, 'models', 'edit')): ?><a href="index.php?page=models&edit_id=<?= $mdl['id'] ?>" class="btn btn-primary btn-sm">✏ Edit</a><?php endif; ?>
<?php if (has_permission($conn, 'models', 'delete')): ?>
<form method="POST" action="index.php?page=models&action=delete" style="display:inline">
<input type="hidden" name="id" value="<?= $mdl['id'] ?>">
<button type="submit" class="btn btn-danger btn-sm" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Delete this model?', text: 'Are you sure you want to delete this model? Only possible if no bikes are linked.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🗑</button>
</form>
<?php endif; ?>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php
    elseif ($page === 'accessories'):
        $accessories_result = $conn->query('SELECT * FROM accessories ORDER BY name');
        $edit_acc_id = (int) ($_GET['edit_id'] ?? 0);
        $edit_acc = null;
        if ($edit_acc_id) {
            $ea = $conn->query("SELECT * FROM accessories WHERE id=$edit_acc_id");
            $edit_acc = $ea ? $ea->fetch_assoc() : null;
        }
?>
<div style="display:flex;gap:8px;margin-bottom:10px" class="no-print animate__animated animate__fadeInLeft">
<?php if (has_permission($conn, 'accessories', 'add')): ?>
<button class="btn btn-success" onclick="document.getElementById('addAccFormArea').style.display='block';document.getElementById('addAccFormArea').scrollIntoView()">+ Add Accessory</button>
<?php endif; ?>
</div>
<div id="addAccFormArea" style="display:<?= $edit_acc ? 'block' : 'none' ?>;margin-bottom:14px" class="animate__animated animate__fadeIn">
<fieldset class="fieldset"><legend><?= $edit_acc ? '✏ Edit Accessory' : '+ Add New Accessory' ?></legend>
<form id="accessoryForm" method="POST" action="index.php?page=accessories&action=<?= $edit_acc ? 'edit' : 'add' ?>">
<?php if ($edit_acc): ?><input type="hidden" name="id" value="<?= $edit_acc['id'] ?>"><?php endif; ?>
<div class="form-row">
<div class="form-group"><label>Accessory Name <span class="req">*</span></label><input type="text" name="name" value="<?= sanitize($edit_acc['name'] ?? '') ?>" required></div>
<div class="form-group"><label>SKU <span class="req">*</span></label><input type="text" name="sku" value="<?= sanitize($edit_acc['sku'] ?? '') ?>" required></div>
</div>
<div class="form-row">
<div class="form-group"><label>Purchase Price (<?= $currency ?>) <span class="req">*</span></label><input type="number" name="purchase_price" step="0.01" min="0" value="<?= $edit_acc['purchase_price'] ?? '0.00' ?>" required></div>
<div class="form-group"><label>Selling Price (<?= $currency ?>) <span class="req">*</span></label><input type="number" name="selling_price" step="0.01" min="0" value="<?= $edit_acc['selling_price'] ?? '0.00' ?>" required></div>
<div class="form-group"><label>Current Stock <span class="req">*</span></label><input type="number" name="current_stock" min="0" value="<?= $edit_acc['current_stock'] ?? '0' ?>" required></div>
</div>
<button type="submit" class="btn btn-primary">💾 Save</button>
<button type="button" class="btn btn-default" onclick="document.getElementById('addAccFormArea').style.display='none'">Cancel</button>
</form>
</fieldset>
</div>
<div class="data-table-wrap animate__animated animate__fadeInUp">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Name</th><th>SKU</th><th>Purchase Price</th><th>Selling Price</th><th>Current Stock</th><th class="no-sort">Actions</th></tr></thead>
<tbody>
<?php $sr = 1;
        while ($acc = $accessories_result->fetch_assoc()): ?>
<tr>
<td><?= $sr++ ?></td>
<td><strong><?= sanitize($acc['name']) ?></strong></td>
<td><code><?= sanitize($acc['sku']) ?></code></td>
<td><?= fmt_money($acc['purchase_price']) ?></td>
<td><?= fmt_money($acc['selling_price']) ?></td>
<td><?= $acc['current_stock'] ?></td>
<td class="no-print">
<div class="actions-col">
<?php if (has_permission($conn, 'accessories', 'edit')): ?><a href="index.php?page=accessories&edit_id=<?= $acc['id'] ?>" class="btn btn-primary btn-sm">✏ Edit</a><?php endif; ?>
<?php if (has_permission($conn, 'accessories', 'delete')): ?>
<form method="POST" action="index.php?page=accessories&action=delete" style="display:inline">
<input type="hidden" name="id" value="<?= $acc['id'] ?>">
<button type="submit" class="btn btn-danger btn-sm" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Delete this accessory?', text: 'Are you sure you want to delete this accessory? Only possible if no sales are linked.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🗑</button>
</form>
<?php endif; ?>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php
    elseif ($page === 'quotations'):
        $quotes_r = $conn->query('SELECT q.*, b.chassis_number, m.model_name, c.name AS customer_name, u.username AS created_by_user FROM quotations q LEFT JOIN bikes b ON q.bike_id=b.id LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON q.customer_id=c.id LEFT JOIN users u ON q.created_by=u.id ORDER BY q.id DESC');
        $edit_quote_id = (int) ($_GET['edit_id'] ?? 0);
        $edit_quote = null;
        if ($edit_quote_id) {
            $eq = $conn->query("SELECT * FROM quotations WHERE id=$edit_quote_id");
            $edit_quote = $eq ? $eq->fetch_assoc() : null;
        }
        $customers_list_q = $conn->query('SELECT id, name, phone, cnic, is_filer, address FROM customers ORDER BY name');
        $bikes_list_q = $conn->query("SELECT b.id, b.chassis_number, m.model_name, b.color, b.purchase_price, m.category FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE b.status='in_stock' ORDER BY b.chassis_number");
        $accessories_list_q = $conn->query('SELECT id, name, selling_price, current_stock FROM accessories WHERE current_stock > 0 ORDER BY name');
?>
<div style="display:flex;gap:8px;margin-bottom:10px" class="no-print animate__animated animate__fadeInLeft">
<?php if (has_permission($conn, 'quotations', 'add')): ?>
<button class="btn btn-success" onclick="document.getElementById('addQuoteFormArea').style.display='block';document.getElementById('addQuoteFormArea').scrollIntoView()">+ Create Quotation</button>
<?php endif; ?>
</div>
<div id="addQuoteFormArea" style="display:<?= $edit_quote ? 'block' : 'none' ?>;margin-bottom:14px" class="animate__animated animate__fadeIn">
<fieldset class="fieldset"><legend><?= $edit_quote ? '✏ Edit Quotation' : '+ Create New Quotation' ?></legend>
<form id="quotationForm" method="POST" action="index.php?page=quotations&action=<?= $edit_quote ? 'edit' : 'add' ?>">
<?php if ($edit_quote): ?><input type="hidden" name="id" value="<?= $edit_quote['id'] ?>"><?php endif; ?>
<input type="hidden" name="save_quote" value="1">
<div class="form-row">
<div class="form-group"><label>Quote Date <span class="req">*</span></label><input type="date" name="quote_date" value="<?= $edit_quote['quote_date'] ?? date('Y-m-d') ?>" required></div>
<div class="form-group"><label>Valid Until <span class="req">*</span></label><input type="date" name="valid_until" value="<?= $edit_quote['valid_until'] ?? date('Y-m-d', strtotime('+7 days')) ?>" required></div>
</div>
<div class="form-row">
<div class="form-group" style="flex:1">
    <label>Customer <span class="req">*</span></label>
    <select name="customer_id" id="quoteCustomerSel" required class="select2-enable" onchange="showQuoteCustomerDetails(this)">
        <option value="">-- Select Customer --</option>
        <?php $customers_list_q->data_seek(0);
        while ($cust = $customers_list_q->fetch_assoc()): ?>
            <option value="<?= $cust['id'] ?>" data-phone="<?= sanitize($cust['phone']) ?>" data-cnic="<?= sanitize($cust['cnic']) ?>" data-filer="<?= $cust['is_filer'] ? 'Filer' : 'Non-Filer' ?>" data-address="<?= sanitize($cust['address']) ?>" <?= (($edit_quote['customer_id'] ?? '') == $cust['id']) ? 'selected' : '' ?>><?= sanitize($cust['name']) ?> (<?= sanitize($cust['phone']) ?>)</option>
        <?php endwhile; ?>
    </select>
    <div id="quoteCustomerDetails" style="margin-top:8px;font-size:0.8rem;color:var(--text);display:none;background:var(--bg2);padding:10px;border-radius:2px;border:1px solid var(--border);line-height:1.4"></div>
</div>
<div class="form-group" style="flex:1">
    <label>Bike <span class="req">*</span></label>
    <select name="bike_id" id="quoteBikeSel" required class="select2-enable" onchange="showQuoteBikeDetails(this)">
        <option value="">-- Select Bike --</option>
        <?php $bikes_list_q->data_seek(0);
        while ($bike = $bikes_list_q->fetch_assoc()): ?>
            <option value="<?= $bike['id'] ?>" data-model="<?= sanitize($bike['model_name']) ?>" data-color="<?= sanitize($bike['color']) ?>" data-pp="<?= fmt_money($bike['purchase_price']) ?>" data-cat="<?= sanitize($bike['category']) ?>" <?= (($edit_quote['bike_id'] ?? '') == $bike['id']) ? 'selected' : '' ?>><?= sanitize($bike['chassis_number']) ?> (<?= sanitize($bike['model_name']) ?>)</option>
        <?php endwhile; ?>
    </select>
    <div id="quoteBikeDetails" style="margin-top:8px;font-size:0.8rem;color:var(--text);display:none;background:var(--bg2);padding:10px;border-radius:2px;border:1px solid var(--border);line-height:1.4"></div>
</div>
</div>
<div class="form-group"><label>Quoted Price (<?= $currency ?>) <span class="req">*</span></label><input type="number" name="quoted_price" step="0.01" min="0" value="<?= $edit_quote['quoted_price'] ?? '0.00' ?>" required></div>
<fieldset class="fieldset" style="margin-top:10px;"><legend>Accessories Included</legend>
    <div id="quoteAccessoriesList">
        <?php
        $q_acc_data = $edit_quote ? json_decode($edit_quote['accessories_json'], true) : [];
        if (!empty($q_acc_data)) {
            $q_acc_count = 0;
            foreach ($q_acc_data as $q_acc_idx => $q_acc_item) {
                $q_acc_count++;
                $selected_acc_id = $q_acc_item['id'] ?? 0;
                $qty = $q_acc_item['quantity'] ?? 1;
                $unit_p = $q_acc_item['unit_price'] ?? 0;
                $disc = $q_acc_item['discount'] ?? 0;
                $final_p = $q_acc_item['final_price'] ?? 0;
                ?>
            <div class="bike-row animate__animated animate__fadeInDown" id="quoteAccessoryRow_<?= $q_acc_count ?>">
                <div class="bike-row-header">
                    <span class="bike-row-num">🛠️ Accessory #<?= $q_acc_count ?></span>
                    <button type="button" class="bike-row-del" onclick="removeQuoteAccessoryRow(<?= $q_acc_count ?>)">✕ Remove</button>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex:2"><label>Accessory <span class="req">*</span></label>
                        <select name="accessories[<?= $q_acc_count ?>][id]" required class="select2-enable" onchange="updateQuoteAccessoryDetails(this, <?= $q_acc_count ?>)">
                            <option value="">-- Select Accessory --</option>
                            <?php $accessories_list_q->data_seek(0);
                            while ($acc_opt = $accessories_list_q->fetch_assoc()): ?>
                                <option value="<?= $acc_opt['id'] ?>" data-price="<?= $acc_opt['selling_price'] ?>" data-stock="<?= $acc_opt['current_stock'] ?>" <?= ($selected_acc_id == $acc_opt['id']) ? 'selected' : '' ?>>
                                    <?= sanitize($acc_opt['name']) ?> (Stock: <?= $acc_opt['current_stock'] ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <span id="quoteAccStock_<?= $q_acc_count ?>" style="font-size:0.75rem;color:var(--text3)"></span>
                    </div>
                    <div class="form-group"><label>Quantity <span class="req">*</span></label><input type="number" name="accessories[<?= $q_acc_count ?>][quantity]" value="<?= $qty ?>" min="1" required oninput="calculateQuoteAccessoryPrice(<?= $q_acc_count ?>)"></div>
                    <div class="form-group"><label>Unit Price</label><input type="number" name="accessories[<?= $q_acc_count ?>][unit_price]" step="0.01" min="0" value="<?= $unit_p ?>" oninput="calculateQuoteAccessoryPrice(<?= $q_acc_count ?>)"></div>
                    <div class="form-group"><label>Discount</label><input type="number" name="accessories[<?= $q_acc_count ?>][discount]" value="<?= $disc ?>" step="0.01" min="0" oninput="calculateQuoteAccessoryPrice(<?= $q_acc_count ?>)"></div>
                    <div class="form-group"><label>Final Price</label><input type="number" name="accessories[<?= $q_acc_count ?>][final_price]" step="0.01" min="0" value="<?= $final_p ?>" readonly style="background:var(--bg3);color:var(--text2)"></div>
                </div>
            </div>
        <?php
            }
        }
        ?>
    </div>
    <button type="button" class="btn btn-default btn-sm" onclick="addQuoteAccessoryRow()" style="margin-top:6px">+ Add Accessory</button>
</fieldset>
<div class="form-group"><label>Notes</label><textarea name="notes" rows="2"><?= sanitize($edit_quote['notes'] ?? '') ?></textarea></div>
<button type="submit" class="btn btn-primary">💾 Save Quotation</button>
<button type="button" class="btn btn-default" onclick="document.getElementById('addQuoteFormArea').style.display='none'">Cancel</button>
</form>
</fieldset>
</div>
<div class="data-table-wrap animate__animated animate__fadeInUp">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Quote #</th><th>Date</th><th>Valid Until</th><th>Customer</th><th>Bike Chassis</th><th>Quoted Price</th><th>Status</th><th>Created By</th><th class="no-sort">Actions</th></tr></thead>
<tbody>
<?php $sr = 1;
        while ($quote = $quotes_r->fetch_assoc()): ?>
<tr>
<td><?= $sr++ ?></td>
<td>QT-<?= $quote['id'] ?></td>
<td><?= fmt_date($quote['quote_date']) ?></td>
<td><?= fmt_date($quote['valid_until']) ?></td>
<td><?= sanitize($quote['customer_name'] ?? '-') ?></td>
<td><?= sanitize($quote['chassis_number'] ?? '-') ?></td>
<td><?= fmt_money($quote['quoted_price']) ?></td>
<td><span class="badge badge-<?= ($quote['status'] == 'converted') ? 'success' : (($quote['status'] == 'rejected') ? 'danger' : 'info') ?>"><?= strtoupper($quote['status']) ?></span></td>
<td><?= sanitize($quote['created_by_user'] ?? '-') ?></td>
<td class="no-print">
<div class="actions-col">
<?php if ($quote['status'] == 'pending' && has_permission($conn, 'quotations', 'edit')): ?>
<form method="POST" action="index.php?page=quotations&action=convert_quote_to_sale" style="display:inline">
<input type="hidden" name="quote_id" value="<?= $quote['id'] ?>">
<input type="hidden" name="convert_quote_to_sale" value="1">
<button type="submit" class="btn btn-success btn-sm" title="Convert to Sale" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Convert to Sale?', text: 'This will create a new sale entry and mark this quotation as converted. Are you sure?', icon: 'info', showCancelButton: true, confirmButtonText: 'Yes, convert it!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🛒</button>
</form>
<a href="index.php?page=quotations&edit_id=<?= $quote['id'] ?>" class="btn btn-primary btn-sm" title="Edit">✏</a>
<?php endif; ?>
<?php if (has_permission($conn, 'quotations', 'delete')): ?>
<form method="POST" action="index.php?page=quotations&action=delete" style="display:inline">
<input type="hidden" name="id" value="<?= $quote['id'] ?>">
<input type="hidden" name="delete_quote" value="1">
<button type="submit" class="btn btn-danger btn-sm" title="Delete" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Delete this quotation?', text: 'Are you sure you want to delete this quotation?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🗑</button>
</form>
<?php endif; ?>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<script>
var quoteAccessoriesCount = <?= $q_acc_count ?? 0 ?>;
var allAvailableAccessories = <?= json_encode($conn->query('SELECT id, name, selling_price, current_stock FROM accessories WHERE current_stock > 0 ORDER BY name')->fetch_all(MYSQLI_ASSOC)) ?>;
function addQuoteAccessoryRow() {
    quoteAccessoriesCount++;
    var d = document.createElement('div');
    d.className = 'bike-row animate__animated animate__fadeInDown';
    d.id = 'quoteAccessoryRow_' + quoteAccessoriesCount;
    var optionsHtml = '<option value="">-- Select Accessory --</option>';
    allAvailableAccessories.forEach(function(acc) {
        optionsHtml += `<option value="${acc.id}" data-price="${acc.selling_price}" data-stock="${acc.current_stock}">${acc.name} (Stock: ${acc.current_stock})</option>`;
    });
    d.innerHTML = `<div class="bike-row-header"><span class="bike-row-num">🛠️ Accessory #${quoteAccessoriesCount}</span><button type="button" class="bike-row-del" onclick="removeQuoteAccessoryRow(${quoteAccessoriesCount})">✕ Remove</button></div>
    <div class="form-row">
        <div class="form-group" style="flex:2"><label>Accessory <span class="req">*</span></label>
            <select name="accessories[${quoteAccessoriesCount}][id]" required class="select2-enable" onchange="updateQuoteAccessoryDetails(this, ${quoteAccessoriesCount})">
                ${optionsHtml}
            </select>
            <span id="quoteAccStock_${quoteAccessoriesCount}" style="font-size:0.75rem;color:var(--text3)"></span>
        </div>
        <div class="form-group"><label>Quantity <span class="req">*</span></label><input type="number" name="accessories[${quoteAccessoriesCount}][quantity]" value="1" min="1" required oninput="calculateQuoteAccessoryPrice(${quoteAccessoriesCount})"></div>
        <div class="form-group"><label>Unit Price</label><input type="number" name="accessories[${quoteAccessoriesCount}][unit_price]" step="0.01" min="0" oninput="calculateQuoteAccessoryPrice(${quoteAccessoriesCount})"></div>
        <div class="form-group"><label>Discount</label><input type="number" name="accessories[${quoteAccessoriesCount}][discount]" value="0.00" step="0.01" min="0" oninput="calculateQuoteAccessoryPrice(${quoteAccessoriesCount})"></div>
        <div class="form-group"><label>Final Price</label><input type="number" name="accessories[${quoteAccessoriesCount}][final_price]" step="0.01" min="0" readonly style="background:var(--bg3);color:var(--text2)"></div>
    </div>`;
    document.getElementById('quoteAccessoriesList').appendChild(d);
    $(d).find('.select2-enable').select2({
        minimumResultsForSearch: 0,
        placeholder: '-- Select Accessory --',
        allowClear: false,
        tags: true,
        theme: 'default'
    });
}
function removeQuoteAccessoryRow(n) {
    document.getElementById('quoteAccessoryRow_' + n).remove();
}
function updateQuoteAccessoryDetails(selectElement, index) {
    var selectedOption = selectElement.options[selectElement.selectedIndex];
    var price = selectedOption && selectedOption.dataset ? (selectedOption.dataset.price || 0) : 0;
    var stock = selectedOption && selectedOption.dataset ? (selectedOption.dataset.stock || 0) : 0;
    document.querySelector(`#quoteAccessoryRow_${index} input[name="accessories[${index}][unit_price]"]`).value = price;
    document.querySelector(`#quoteAccStock_${index}`).innerText = `Available: ${stock}`;
    calculateQuoteAccessoryPrice(index);
}
function calculateQuoteAccessoryPrice(index) {
    var quantity = parseInt(document.querySelector(`#quoteAccessoryRow_${index} input[name="accessories[${index}][quantity]"]`).value) || 0;
    var unitPrice = parseFloat(document.querySelector(`#quoteAccessoryRow_${index} input[name="accessories[${index}][unit_price]"]`).value) || 0;
    var discount = parseFloat(document.querySelector(`#quoteAccessoryRow_${index} input[name="accessories[${index}][discount]"]`).value) || 0;
    var finalPrice = (quantity * unitPrice) - discount;
    document.querySelector(`#quoteAccessoryRow_${index} input[name="accessories[${index}][final_price]"]`).value = finalPrice.toFixed(2);
}
$(document).ready(function() {
    $('#quotationForm select.select2-enable').select2({
        minimumResultsForSearch: 10,
        placeholder: '-- Select --',
        allowClear: false,
        theme: 'default'
    });
    $('#quoteAccessoriesList .select2-enable').select2({
        minimumResultsForSearch: 0,
        placeholder: '-- Select Accessory --',
        allowClear: false,
        tags: true,
        theme: 'default'
    });
    
    $('#quoteCustomerSel').on('change', function() { showQuoteCustomerDetails(this); });
    $('#quoteBikeSel').on('change', function() { showQuoteBikeDetails(this); });
    
    if ($('#quoteCustomerSel').val()) showQuoteCustomerDetails(document.getElementById('quoteCustomerSel'));
    if ($('#quoteBikeSel').val()) showQuoteBikeDetails(document.getElementById('quoteBikeSel'));
});

function showQuoteCustomerDetails(sel) {
    var opt = sel.options[sel.selectedIndex];
    var detailsDiv = document.getElementById('quoteCustomerDetails');
    if (sel.value && opt) {
        var phone = opt.getAttribute('data-phone') || '-';
        var cnic = opt.getAttribute('data-cnic') || '-';
        var filer = opt.getAttribute('data-filer') || '-';
        var addr = opt.getAttribute('data-address') || '-';
        var filerBadge = filer === 'Filer' ? 'success' : 'danger';
        detailsDiv.innerHTML = '<strong>Phone:</strong> ' + phone + '<br><strong>CNIC:</strong> ' + cnic + '<br><strong>Status:</strong> <span class="badge badge-' + filerBadge + '">' + filer + '</span><br><strong>Address:</strong> ' + addr;
        detailsDiv.style.display = 'block';
    } else {
        detailsDiv.style.display = 'none';
        detailsDiv.innerHTML = '';
    }
}

function showQuoteBikeDetails(sel) {
    var opt = sel.options[sel.selectedIndex];
    var detailsDiv = document.getElementById('quoteBikeDetails');
    if (sel.value && opt) {
        var model = opt.getAttribute('data-model') || '-';
        var color = opt.getAttribute('data-color') || '-';
        var cat = opt.getAttribute('data-cat') || '-';
        var pp = opt.getAttribute('data-pp') || '-';
        detailsDiv.innerHTML = '<strong>Model:</strong> ' + model + '<br><strong>Category:</strong> ' + cat + '<br><strong>Color:</strong> ' + color + '<br><strong>Purchase Price:</strong> ' + pp;
        detailsDiv.style.display = 'block';
    } else {
        detailsDiv.style.display = 'none';
        detailsDiv.innerHTML = '';
    }
}
</script>
<?php
    elseif ($page === 'customers'):
        $cust_result = $conn->query("SELECT c.*, COUNT(b.id) as bike_count, SUM(CASE WHEN b.status='sold' THEN b.selling_price ELSE 0 END) as total_purchases FROM customers c LEFT JOIN bikes b ON c.id=b.customer_id GROUP BY c.id ORDER BY c.name");
        $edit_cust_id = (int) ($_GET['edit_id'] ?? 0);
        $edit_cust = null;
        if ($edit_cust_id) {
            $ec = $conn->query("SELECT * FROM customers WHERE id=$edit_cust_id");
            $edit_cust = $ec ? $ec->fetch_assoc() : null;
        }
        $search_cust = sanitize($_GET['search_cust'] ?? '');
        $where_cust = '1=1';
        if ($search_cust)
            $where_cust = "(c.name LIKE '%" . mysqli_real_escape_string($conn, $search_cust) . "%' OR c.phone LIKE '%" . mysqli_real_escape_string($conn, $search_cust) . "%' OR c.cnic LIKE '%" . mysqli_real_escape_string($conn, $search_cust) . "%')";
        $cust_result = $conn->query("SELECT c.*, COUNT(b.id) as bike_count, SUM(CASE WHEN b.status='sold' THEN b.selling_price ELSE 0 END) as total_purchases FROM customers c LEFT JOIN bikes b ON c.id=b.customer_id WHERE $where_cust GROUP BY c.id ORDER BY c.name");
?>
<div style="display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap;align-items:center" class="no-print animate__animated animate__fadeInLeft">
<form method="GET" action="index.php" style="display:flex;gap:6px;align-items:center">
<input type="hidden" name="page" value="customers">
<input type="text" name="search_cust" value="<?= $search_cust ?>" placeholder="Search by name, phone, CNIC..." style="padding:6px 10px;border:1px solid var(--input-border);background:var(--input-bg);color:var(--input-text);border-radius:1px">
<button type="submit" class="btn btn-default btn-sm">🔍</button>
</form>
<?php if (has_permission($conn, 'customers', 'add')): ?>
<button class="btn btn-success" onclick="document.getElementById('addCustFormArea').style.display='block';document.getElementById('addCustFormArea').scrollIntoView()">+ Add Customer</button>
<?php endif; ?>
</div>
<div id="addCustFormArea" style="display:<?= $edit_cust ? 'block' : 'none' ?>;margin-bottom:14px" class="animate__animated animate__fadeIn">
<fieldset class="fieldset"><legend><?= $edit_cust ? '✏ Edit Customer' : '+ Add New Customer' ?></legend>
<form id="customerForm" method="POST" action="index.php?page=customers&action=<?= $edit_cust ? 'edit' : 'add' ?>">
<?php if ($edit_cust): ?><input type="hidden" name="id" value="<?= $edit_cust['id'] ?>"><?php endif; ?>
<div class="form-row">
<div class="form-group"><label>Name <span class="req">*</span></label><input type="text" name="name" value="<?= sanitize($edit_cust['name'] ?? '') ?>" required></div>
<div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?= sanitize($edit_cust['phone'] ?? '') ?>"></div>
<div class="form-group"><label>CNIC</label><input type="text" name="cnic" value="<?= sanitize($edit_cust['cnic'] ?? '') ?>" placeholder="XXXXX-XXXXXXX-X"></div>
</div>
<div class="form-row">
<div class="form-group"><label style="display:flex;align-items:center;gap:6px;"><input type="checkbox" name="is_filer" value="1" <?= ($edit_cust['is_filer'] ?? 1) ? 'checked' : '' ?>> Is Filer?</label></div>
</div>
<div class="form-row">
<div class="form-group"><label>Address</label><textarea name="address" rows="2"><?= sanitize($edit_cust['address'] ?? '') ?></textarea></div>
</div>
<button type="submit" class="btn btn-primary">💾 Save</button>
<button type="button" class="btn btn-default" onclick="document.getElementById('addCustFormArea').style.display='none'">Cancel</button>
</form>
</fieldset>
</div>
<div class="data-table-wrap animate__animated animate__fadeInUp">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Name</th><th>Phone</th><th>CNIC</th><th>Filer Status</th><th>Address</th><th>Bikes Purchased</th><th>Total Amount</th><th class="no-sort">Actions</th></tr></thead>
<tbody>
<?php $sr = 1;
        while ($cu = $cust_result->fetch_assoc()): ?>
<tr>
<td><?= $sr++ ?></td>
<td><strong><?= sanitize($cu['name']) ?></strong></td>
<td><?= sanitize($cu['phone'] ?? '-') ?></td>
<td style="font-family:Consolas,monospace"><?= sanitize($cu['cnic'] ?? '-') ?></td>
<td><span class="badge badge-<?= $cu['is_filer'] ? 'success' : 'danger' ?>"><?= $cu['is_filer'] ? 'FILER' : 'NON-FILER' ?></span></td>
<td><?= sanitize($cu['address'] ?? '-') ?></td>
<td><?= $cu['bike_count'] ?></td>
<td><?= fmt_money($cu['total_purchases'] ?? 0) ?></td>
<td class="no-print">
<div class="actions-col">
<?php if (has_permission($conn, 'customer_ledger', 'view')): ?><a href="index.php?page=customer_ledger&cust_id=<?= $cu['id'] ?>" class="btn btn-default btn-sm" title="Ledger">📒</a><?php endif; ?>
<?php if (has_permission($conn, 'customers', 'edit')): ?><a href="index.php?page=customers&edit_id=<?= $cu['id'] ?>" class="btn btn-primary btn-sm">✏</a><?php endif; ?>
<?php if (has_permission($conn, 'customers', 'delete')): ?>
<form method="POST" action="index.php?page=customers&action=delete" style="display:inline">
<input type="hidden" name="id" value="<?= $cu['id'] ?>">
<button type="submit" class="btn btn-danger btn-sm" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Delete customer?', text: 'Are you sure you want to delete this customer? Only possible if no bikes are linked.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🗑</button>
</form>
<?php endif; ?>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php
    elseif ($page === 'suppliers'):
        $sup_result = $conn->query('SELECT s.*, COUNT(po.id) as order_count, SUM(po.total_amount) as total_purchase_value FROM suppliers s LEFT JOIN purchase_orders po ON s.id=po.supplier_id GROUP BY s.id ORDER BY s.name');
        $edit_sup_id = (int) ($_GET['edit_id'] ?? 0);
        $edit_sup = null;
        if ($edit_sup_id) {
            $es = $conn->query("SELECT * FROM suppliers WHERE id=$edit_sup_id");
            $edit_sup = $es ? $es->fetch_assoc() : null;
        }
?>
<div style="display:flex;gap:8px;margin-bottom:10px" class="no-print animate__animated animate__fadeInLeft">
<?php if (has_permission($conn, 'suppliers', 'add')): ?>
<button class="btn btn-success" onclick="document.getElementById('addSupFormArea').style.display='block';document.getElementById('addSupFormArea').scrollIntoView()">+ Add Supplier</button>
<?php endif; ?>
</div>
<div id="addSupFormArea" style="display:<?= $edit_sup ? 'block' : 'none' ?>;margin-bottom:14px" class="animate__animated animate__fadeIn">
<fieldset class="fieldset"><legend><?= $edit_sup ? '✏ Edit Supplier' : '+ Add New Supplier' ?></legend>
<form id="supplierForm" method="POST" action="index.php?page=suppliers&action=<?= $edit_sup ? 'edit' : 'add' ?>">
<?php if ($edit_sup): ?><input type="hidden" name="id" value="<?= $edit_sup['id'] ?>"><?php endif; ?>
<div class="form-row">
<div class="form-group"><label>Name <span class="req">*</span></label><input type="text" name="name" value="<?= sanitize($edit_sup['name'] ?? '') ?>" required></div>
<div class="form-group"><label>Contact</label><input type="text" name="contact" value="<?= sanitize($edit_sup['contact'] ?? '') ?>"></div>
</div>
<div class="form-row">
<div class="form-group"><label>Address</label><textarea name="address" rows="2"><?= sanitize($edit_sup['address'] ?? '') ?></textarea></div>
</div>
<button type="submit" class="btn btn-primary">💾 Save</button>
<button type="button" class="btn btn-default" onclick="document.getElementById('addSupFormArea').style.display='none'">Cancel</button>
</form>
</fieldset>
</div>
<div class="data-table-wrap animate__animated animate__fadeInUp">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Name</th><th>Contact</th><th>Address</th><th>Orders</th><th>Total Purchase Value</th><th class="no-sort">Actions</th></tr></thead>
<tbody>
<?php $sr = 1;
        while ($sv = $sup_result->fetch_assoc()): ?>
<tr>
<td><?= $sr++ ?></td>
<td><strong><?= sanitize($sv['name']) ?></strong></td>
<td><?= sanitize($sv['contact'] ?? '-') ?></td>
<td><?= sanitize($sv['address'] ?? '-') ?></td>
<td><?= $sv['order_count'] ?></td>
<td><?= fmt_money($sv['total_purchase_value'] ?? 0) ?></td>
<td class="no-print">
<div class="actions-col">
<?php if (has_permission($conn, 'supplier_ledger', 'view')): ?><a href="index.php?page=supplier_ledger&sup_id=<?= $sv['id'] ?>" class="btn btn-default btn-sm" title="Ledger">📒</a><?php endif; ?>
<?php if (has_permission($conn, 'suppliers', 'edit')): ?><a href="index.php?page=suppliers&edit_id=<?= $sv['id'] ?>" class="btn btn-primary btn-sm">✏</a><?php endif; ?>
<?php if (has_permission($conn, 'suppliers', 'delete')): ?>
<form method="POST" action="index.php?page=suppliers&action=delete" style="display:inline">
<input type="hidden" name="id" value="<?= $sv['id'] ?>">
<button type="submit" class="btn btn-danger btn-sm" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Delete supplier?', text: 'Are you sure you want to delete this supplier? Only possible if no purchase orders are linked.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🗑</button>
</form>
<?php endif; ?>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php
    elseif ($page === 'roles'):
        require_permission($conn, 'roles', 'view');
        $roles = $conn->query('SELECT * FROM roles ORDER BY id');
        $edit_id = (int) ($_GET['edit_id'] ?? 0);
        $edit_role = null;
        $perms = [];
        if ($edit_id) {
            $er = $conn->query("SELECT * FROM roles WHERE id=$edit_id");
            $edit_role = $er->fetch_assoc();
            $pr = $conn->query("SELECT * FROM role_permissions WHERE role_id=$edit_id");
            while ($p = $pr->fetch_assoc())
                $perms[$p['page']] = $p;
        }
        $all_pages = [
            'dashboard' => 'Dashboard', 'inventory' => 'Inventory', 'purchase' => 'Purchase Orders', 'sale' => 'Sales', 'customers' => 'Customers', 'suppliers' => 'Suppliers', 'models' => 'Models', 'reports' => 'Reports', 'returns' => 'Returns', 'payments' => 'Payments Register', 'settings' => 'Settings', 'roles' => 'Roles', 'users' => 'Users', 'income_expense' => 'Income/Expense', 'accessories' => 'Accessories', 'quotations' => 'Quotations', 'installments' => 'Installments',
            'customer_ledger' => 'Customer Ledger', 'supplier_ledger' => 'Supplier Ledger'
        ];
?>
<div style="display:flex;gap:8px;margin-bottom:10px" class="no-print animate__animated animate__fadeInLeft">
    <?php if (has_permission($conn, 'roles', 'add')): ?>
    <button class="btn btn-success" onclick="document.getElementById('roleForm').style.display='block';document.getElementById('roleForm').scrollIntoView()">+ Add Role</button>
    <?php endif; ?>
</div>
<div id="roleForm" style="display:<?= $edit_role ? 'block' : 'none' ?>;margin-bottom:14px" class="animate__animated animate__fadeIn">
<fieldset class="fieldset"><legend><?= $edit_role ? '✏ Edit Role' : ' + Add New Role' ?></legend>
<form id="editRoleForm" method="POST">
<input type="hidden" name="id" value="<?= $edit_role['id'] ?? 0 ?>">
<input type="hidden" name="save_role" value="1">
<div class="form-row">
<div class="form-group"><label>Role Name *</label><input type="text" name="name" value="<?= sanitize($edit_role['name'] ?? '') ?>" required></div>
<div class="form-group"><label>Description</label><input type="text" name="description" value="<?= sanitize($edit_role['description'] ?? '') ?>"></div>
</div>
<h4 style="margin:12px 0 6px">Permissions (check to allow)</h4>
<div style="overflow:auto;max-height:400px;border:1px solid var(--border);padding:8px">
<table class="data-table">
<thead><tr><th>Page</th><th>View</th><th>Add</th><th>Edit</th><th>Delete</th></tr></thead>
<tbody>
<?php foreach ($all_pages as $k => $label):
            $p = $perms[$k] ?? []; ?>
<tr>
<td><strong><?= $label ?></strong></td>
<td style="text-align:center"><input type="checkbox" name="perm[<?= $k ?>][view]" <?= ($p['can_view'] ?? 0) ? 'checked' : '' ?>></td>
<td style="text-align:center"><input type="checkbox" name="perm[<?= $k ?>][add]" <?= ($p['can_add'] ?? 0) ? 'checked' : '' ?>></td>
<td style="text-align:center"><input type="checkbox" name="perm[<?= $k ?>][edit]" <?= ($p['can_edit'] ?? 0) ? 'checked' : '' ?>></td>
<td style="text-align:center"><input type="checkbox" name="perm[<?= $k ?>][delete]" <?= ($p['can_delete'] ?? 0) ? 'checked' : '' ?>></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<button type="submit" class="btn btn-primary" style="margin-top:10px">💾 Save Role</button>
<button type="button" class="btn btn-default" onclick="document.getElementById('roleForm').style.display='none'">Cancel</button>
</form>
</fieldset>
</div>
<div class="data-table-wrap animate__animated animate__fadeInUp">
<table class="data-table">
<thead><tr><th>ID</th><th>Role</th><th>Description</th><th>Users</th><th class="no-sort">Actions</th></tr></thead>
<tbody>
<?php while ($r = $roles->fetch_assoc()):
            $uc = $conn->query('SELECT COUNT(*) c FROM users WHERE role_id=' . $r['id'])->fetch_assoc()['c']; ?>
<tr>
<td><?= $r['id'] ?></td>
<td><strong><?= sanitize($r['name']) ?></strong></td>
<td><?= sanitize($r['description']) ?></td>
<td><?= $uc ?></td>
<td class="no-print">
<?php if (has_permission($conn, 'roles', 'edit')): ?>
<a href="index.php?page=roles&edit_id=<?= $r['id'] ?>" class="btn btn-primary btn-sm">✏</a>
<?php endif; ?>
<?php if ($r['id'] != 1 && has_permission($conn, 'roles', 'delete')): ?>
<form method="POST" style="display:inline">
<input type="hidden" name="id" value="<?= $r['id'] ?>">
<button name="delete_role" class="btn btn-danger btn-sm" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Delete role?', text: 'Are you sure you want to delete this role? Only possible if no users are linked.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🗑</button>
</form>
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php
    elseif ($page === 'users'):
        require_permission($conn, 'users', 'view');
        $users = $conn->query('SELECT u.*, r.name role_name FROM users u LEFT JOIN roles r ON u.role_id=r.id ORDER BY u.id');
        $roles = $conn->query('SELECT * FROM roles ORDER BY name');
        $edit_id = (int) ($_GET['edit_id'] ?? 0);
        $edit_user = null;
        if ($edit_id) {
            $eu = $conn->query("SELECT * FROM users WHERE id=$edit_id");
            $edit_user = $eu->fetch_assoc();
        }
?>
<div style="display:flex;gap:8px;margin-bottom:10px" class="no-print animate__animated animate__fadeInLeft">
    <?php if (has_permission($conn, 'users', 'add')): ?>
    <button class="btn btn-success" onclick="document.getElementById('userForm').style.display='block';document.getElementById('userForm').scrollIntoView()">+ Add User</button>
    <?php endif; ?>
</div>
<div id="userForm" style="display:<?= $edit_user ? 'block' : 'none' ?>;margin-bottom:14px" class="animate__animated animate__fadeIn">
<fieldset class="fieldset"><legend><?= $edit_user ? '✏ Edit User' : ' + Add New User' ?></legend>
<form id="editUserForm" method="POST">
<input type="hidden" name="id" value="<?= $edit_user['id'] ?? 0 ?>">
<input type="hidden" name="save_user" value="1">
<div class="form-row">
<div class="form-group"><label>Username *</label><input type="text" name="username" value="<?= sanitize($edit_user['username'] ?? '') ?>" required pattern="[a-zA-Z0-9_]{3,}" title="3+ chars, letters/numbers/_"></div>
<div class="form-group"><label>Full Name</label><input type="text" name="full_name" value="<?= sanitize($edit_user['full_name'] ?? '') ?>"></div>
</div>
<div class="form-row">
<div class="form-group"><label>Role *</label>
<select name="role_id" required>
<?php $roles2 = $conn->query('SELECT * FROM roles ORDER BY name');
        while ($rl = $roles2->fetch_assoc()): ?>
<option value="<?= $rl['id'] ?>" <?= ($edit_user['role_id'] ?? 2) == $rl['id'] ? 'selected' : '' ?>><?= sanitize($rl['name']) ?></option>
<?php endwhile; ?>
</select></div>
<div class="form-group"><label>Password <?= $edit_user ? '(leave blank to keep)' : '(min 8 chars, incl. special, number, letter)' ?></label>
<input type="password" name="password" <?= $edit_user ? '' : 'required' ?> minlength="8" placeholder="Strong password"></div>
<div class="form-group"><label style="display:flex;align-items:center;gap:6px;margin-top:24px"><input type="checkbox" name="is_active" <?= ($edit_user['is_active'] ?? 1) ? 'checked' : '' ?>> Active</label></div>
</div>
<div style="font-size:0.78rem;color:var(--text2)">Strong password: min 8 chars, must include at least one uppercase letter, one lowercase letter, one number, and one special character. Leave empty to keep the current password unchanged.</div>
<button type="submit" class="btn btn-primary" style="margin-top:10px">💾 Save User</button>
<button type="button" class="btn btn-default" onclick="document.getElementById('userForm').style.display='none'">Cancel</button>
</form>
</fieldset>
</div>
<div class="data-table-wrap animate__animated animate__fadeInUp">
<table class="data-table">
<thead><tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Status</th><th>Created</th><th class="no-sort">Actions</th></tr></thead>
<tbody>
<?php while ($u = $users->fetch_assoc()): ?>
<tr>
<td><?= $u['id'] ?></td>
<td><strong><?= sanitize($u['username']) ?></strong></td>
<td><?= sanitize($u['full_name']) ?></td>
<td><?= sanitize($u['role_name']) ?></td>
<td><?= $u['is_active'] ? '<span style="color:var(--success)">Active</span>' : '<span style="color:var(--danger)">Disabled</span>' ?></td>
<td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
<td class="no-print">
<?php if (has_permission($conn, 'users', 'edit')): ?>
<a href="index.php?page=users&edit_id=<?= $u['id'] ?>" class="btn btn-primary btn-sm">✏</a>
<?php endif; ?>
<?php if ($u['id'] != 1 && $u['id'] != $_SESSION['user_id'] && has_permission($conn, 'users', 'delete')): ?>
<form method="POST" style="display:inline">
<input type="hidden" name="id" value="<?= $u['id'] ?>">
<button name="delete_user" class="btn btn-danger btn-sm" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Delete user?', text: 'Are you sure you want to delete this user?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🗑</button>
</form>
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php
    elseif ($page === 'income_expense'):
        require_permission($conn, 'income_expense', 'view');
        $filter_type = $_GET['type'] ?? '';
        $filter_from = $_GET['from'] ?? date('Y-m-01');
        $filter_to = $_GET['to'] ?? date('Y-m-d');
        $filter_cat = $_GET['category'] ?? '';
        $where = "WHERE entry_date BETWEEN '$filter_from' AND '$filter_to'";
        if ($filter_type)
            $where .= " AND type='$filter_type'";
        if ($filter_cat)
            $where .= " AND category='" . mysqli_real_escape_string($conn, $filter_cat) . "'";
        $entries = $conn->query("SELECT ie.*, u.full_name FROM income_expenses ie LEFT JOIN users u ON ie.created_by=u.id $where ORDER BY entry_date DESC, id DESC");
        $cats = $conn->query('SELECT DISTINCT category FROM income_expenses ORDER BY category');
        $totals = $conn->query("SELECT type, SUM(amount) total FROM income_expenses $where GROUP BY type");
        $sum_income = 0;
        $sum_expense = 0;
        while ($t = $totals->fetch_assoc()) {
            if ($t['type'] == 'income')
                $sum_income = $t['total'];
            else
                $sum_expense = $t['total'];
        }
        $edit_id = (int) ($_GET['edit_id'] ?? 0);
        $edit_entry = null;
        if ($edit_id) {
            $ee = $conn->query("SELECT * FROM income_expenses WHERE id=$edit_id");
            $edit_entry = $ee->fetch_assoc();
        }
?>
<div class="no-print animate__animated animate__fadeInLeft" style="display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap;align-items:end">
    <form method="GET" style="display:flex;gap:6px;align-items:end;flex-wrap:wrap">
    <input type="hidden" name="page" value="income_expense">
    <div class="form-group"><label>From</label><input type="date" name="from" value="<?= $filter_from ?>"></div>
    <div class="form-group"><label>To</label><input type="date" name="to" value="<?= $filter_to ?>"></div>
    <div class="form-group"><label>Type</label>
    <select name="type"><option value="">All</option><option value="income" <?= $filter_type == 'income' ? 'selected' : '' ?>>Income</option><option value="expense" <?= $filter_type == 'expense' ? 'selected' : '' ?>>Expense</option></select></div>
    <div class="form-group"><label>Category</label>
    <select name="category"><option value="">All</option><?php $cats2 = $conn->query('SELECT DISTINCT category FROM income_expenses ORDER BY category');
        while ($c = $cats2->fetch_assoc()): ?><option value="<?= sanitize($c['category']) ?>" <?= $filter_cat == $c['category'] ? 'selected' : '' ?>><?= sanitize($c['category']) ?></option><?php endwhile; ?></select></div>
    <button class="btn btn-default">Filter</button>
    <a href="index.php?page=income_expense" class="btn btn-default">Reset</a>
    </form>
    <?php if (has_permission($conn, 'income_expense', 'add')): ?>
    <button class="btn btn-success" onclick="document.getElementById('ieForm').style.display='block';document.getElementById('ieForm').scrollIntoView()">+ Add Entry</button>
    <?php endif; ?>
</div>
<div class="split-grid animate__animated animate__fadeInUp" style="margin-bottom:12px">
<div class="card"><div class="card-body"><div class="card-label">Total Income</div><div class="card-value" style="color:var(--success)"><?= fmt_money($sum_income) ?></div></div></div>
<div class="card"><div class="card-body"><div class="card-label">Total Expense</div><div class="card-value" style="color:var(--danger)"><?= fmt_money($sum_expense) ?></div></div></div>
<div class="card"><div class="card-body"><div class="card-label">Net</div><div class="card-value" style="color:<?= ($sum_income - $sum_expense) >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><?= fmt_money($sum_income - $sum_expense) ?></div></div></div>
</div>
<div id="ieForm" style="display:<?= $edit_entry ? 'block' : 'none' ?>;margin-bottom:14px" class="animate__animated animate__fadeIn">
<fieldset class="fieldset"><legend><?= $edit_entry ? '✏ Edit' : ' + Add' ?> Income/Expense</legend>
<form id="ieEntryForm" method="POST">
<input type="hidden" name="id" value="<?= $edit_entry['id'] ?? 0 ?>">
<input type="hidden" name="save_entry" value="1">
<div class="form-row">
<div class="form-group"><label>Date *</label><input type="date" name="entry_date" value="<?= $edit_entry['entry_date'] ?? date('Y-m-d') ?>" required></div>
<div class="form-group"><label>Type *</label><select name="type" required><option value="income" <?= ($edit_entry['type'] ?? '') == 'income' ? 'selected' : '' ?>>Income</option><option value="expense" <?= ($edit_entry['type'] ?? 'expense') == 'expense' ? 'selected' : '' ?>>Expense</option></select></div>
<div class="form-group"><label>Category *</label><input type="text" name="category" list="catlist" value="<?= sanitize($edit_entry['category'] ?? '') ?>" required placeholder="e.g. Fuel, Salary, Sales"><datalist id="catlist"><?php while ($c = $cats->fetch_assoc()): ?><option value="<?= sanitize($c['category']) ?>"><?php endwhile; ?></datalist></div>
<div class="form-group"><label>Amount *</label><input type="number" step="0.01" name="amount" value="<?= $edit_entry['amount'] ?? '' ?>" required></div>
</div>
<div class="form-row">
<div class="form-group"><label>Payment Method</label><select name="payment_method">
    <option value="cash" <?= ($edit_entry['payment_method'] ?? '') == 'cash' ? 'selected' : '' ?>>cash</option>
    <option value="bank_transfer" <?= ($edit_entry['payment_method'] ?? '') == 'bank_transfer' ? 'selected' : '' ?>>bank_transfer</option>
    <option value="cheque" <?= ($edit_entry['payment_method'] ?? '') == 'cheque' ? 'selected' : '' ?>>cheque</option>
    <option value="online" <?= ($edit_entry['payment_method'] ?? '') == 'online' ? 'selected' : '' ?>>online</option>
    <option value="other" <?= ($edit_entry['payment_method'] ?? '') == 'other' ? 'selected' : '' ?>>other</option>
</select></div>
<div class="form-group"><label>Reference</label><input type="text" name="reference" value="<?= sanitize($edit_entry['reference'] ?? '') ?>"></div>
</div>
<div class="form-row"><div class="form-group"><label>Notes</label><textarea name="notes" rows="2"><?= sanitize($edit_entry['notes'] ?? '') ?></textarea></div></div>
<button type="submit" class="btn btn-primary">💾 Save</button>
<button type="button" class="btn btn-default" onclick="document.getElementById('ieForm').style.display='none'">Cancel</button>
</form>
</fieldset>
</div>
<div class="data-table-wrap animate__animated animate__fadeInUp">
<table class="data-table">
<thead><tr><th>Date</th><th>Type</th><th>Category</th><th>Amount</th><th>Method</th><th>Reference</th><th>By</th><th class="no-sort">Actions</th></tr></thead>
<tbody>
<?php while ($e = $entries->fetch_assoc()): ?>
<tr>
<td><?= date('d/m/Y', strtotime($e['entry_date'])) ?></td>
<td><?= $e['type'] == 'income' ? '<span style="color:var(--success)">Income</span>' : '<span style="color:var(--danger)">Expense</span>' ?></td>
<td><?= sanitize($e['category']) ?></td>
<td><?= fmt_money($e['amount']) ?></td>
<td><?= $e['payment_method'] ?></td>
<td><?= sanitize($e['reference']) ?></td>
<td><?= sanitize($e['full_name'] ?? '-') ?></td>
<td class="no-print">
<?php if (has_permission($conn, 'income_expense', 'edit')): ?><a href="index.php?page=income_expense&edit_id=<?= $e['id'] ?>&from=<?= $filter_from ?>&to=<?= $filter_to ?>" class="btn btn-primary btn-sm">✏</a><?php endif; ?>
<?php if (has_permission($conn, 'income_expense', 'delete')): ?><form method="POST" style="display:inline"><input type="hidden" name="id" value="<?= $e['id'] ?>"><button name="delete_entry" class="btn btn-danger btn-sm" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Delete this entry?', text: 'Are you sure you want to delete this income/expense entry?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🗑</button></form><?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php
    elseif ($page === 'settings'):
        $s_company = get_setting('company_name') ?? 'BNI Enterprises';
        $s_branch = get_setting('branch_name') ?? 'Dera (Ahmed Metro)';
        $s_tax = get_setting('tax_rate') ?? '0.1';
        $s_curr = get_setting('currency') ?? 'Rs.';
        $s_taxon = get_setting('tax_on') ?? 'purchase_price';
        $s_show_pp = get_setting('show_purchase_on_invoice') ?? '0';
        $s_idle_timeout = get_setting('session_timeout_idle') ?? '2400';
        $s_absolute_timeout = get_setting('session_timeout_absolute') ?? '28800';
?>
<form id="settingsForm" method="POST" enctype="multipart/form-data" class="animate__animated animate__fadeIn">
<input type="hidden" name="save_settings" value="1">
<fieldset class="fieldset"><legend>⚙ Company Settings</legend>
<div class="form-row">
<div class="form-group"><label>Company Name</label><input type="text" name="company_name" value="<?= sanitize($s_company) ?>"></div>
<div class="form-group"><label>Branch Name</label><input type="text" name="branch_name" value="<?= sanitize($s_branch) ?>"></div>
</div>
<div class="form-row">
<div class="form-group"><label>Currency Symbol</label><input type="text" name="currency" value="<?= sanitize($s_curr) ?>" style="max-width:80px"></div>
<div class="form-group"><label>Tax Rate (%)</label><input type="number" name="tax_rate" value="<?= $s_tax ?>" step="0.01" min="0" max="100" style="max-width:100px"></div>
<div class="form-group"><label>Tax Calculated On</label>
<select name="tax_on">
<option value="purchase_price" <?= $s_taxon === 'purchase_price' ? 'selected' : '' ?>>Purchase Price</option>
<option value="selling_price" <?= $s_taxon === 'selling_price' ? 'selected' : '' ?>>Selling Price</option>
</select>
</div>
</div>
<div class="form-row">
<div class="form-group"><label>Show Purchase Price on Invoice</label>
<select name="show_purchase_on_invoice">
<option value="0" <?= $s_show_pp === '0' ? 'selected' : '' ?>>No (Hidden)</option>
<option value="1" <?= $s_show_pp === '1' ? 'selected' : '' ?>>Yes (Visible)</option>
</select>
</div>
<div class="form-group"><label>Idle Session Timeout (seconds)</label><input type="number" name="session_timeout_idle" value="<?= $s_idle_timeout ?>" min="60" max="36000" required></div>
<div class="form-group"><label>Absolute Session Timeout (seconds)</label><input type="number" name="session_timeout_absolute" value="<?= $s_absolute_timeout ?>" min="3600" max="86400" required></div>
</div>
</fieldset>
<fieldset class="fieldset"><legend>🔐 Change Admin Password</legend>
<div class="form-row">
<div class="form-group"><label>New Password (min 8 chars, incl. special, number, letter)</label><input type="password" name="new_password" minlength="8" placeholder="Leave blank to keep existing password"></div>
</div>
<div style="font-size:0.78rem;color:var(--text2);margin-top:4px">⚠ Password must be at least 8 characters. Must include at least one uppercase letter, one lowercase letter, one number, and one special character. Leave empty to keep the current password unchanged.</div>
</fieldset>
<fieldset class="fieldset"><legend>💾 Database Backup & Restore</legend>
<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:14px">
<a href="index.php?page=settings&action=backup" class="btn btn-primary animate__animated animate__pulse">⬇ Download SQL Backup</a>
<span style="font-size:0.8rem;color:var(--text2)">Downloads a full SQL dump of the database.</span>
</div>
<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;border-top:1px solid var(--border);padding-top:14px">
<input type="file" name="backup_file" accept=".sql" style="font-size:0.8rem;background:var(--input-bg);color:var(--input-text);border:1px solid var(--input-border);padding:5px;border-radius:2px">
<button type="submit" name="restore_db" class="btn btn-danger" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'WARNING: Restore Database?', text: 'Restoring will OVERWRITE ALL CURRENT DATA! Are you absolutely sure?', icon: 'error', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, Restore!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">⬆ Restore Database</button>
<span style="font-size:0.8rem;color:var(--text2)">Upload a previously downloaded .sql backup file.</span>
</div>
</fieldset>
<fieldset class="fieldset"><legend>ℹ System Info</legend>
<div class="split-grid" style="font-size:0.82rem;color:var(--text2);gap:8px">
<div><strong>App Version:</strong> <?= $app_version ?></div>
<div><strong>Author:</strong> <?= $author ?></div>
<div><strong>PHP Version:</strong> <?= phpversion() ?></div>
<div><strong>MySQL Version:</strong> <?= $conn->server_info ?></div>
<div><strong>Database:</strong> <?= $db_name ?></div>
<div><strong>Server Time:</strong> <?= date('d/m/Y H:i:s') ?></div>
</div>
</fieldset>
<button type="submit" name="save_settings" class="btn btn-primary">💾 Save Settings</button>
</form>
<?php endif; ?>
</div>
</div>
</div>
</div>
<?php endif; ?>
<script>
function toggleSidebar() {
    if (window.innerWidth <= 600) {
        var s = document.getElementById('sidebar');
        var o = document.getElementById('sidebarOverlay');
        s.classList.toggle('open');
        o.classList.toggle('open');
    } else {
        document.body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed') ? '1' : '0');
    }
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('open');
}
var toastWrap = document.getElementById('toastWrap');
if (toastWrap) {
    setTimeout(function() {
        toastWrap.style.opacity = '0';
        toastWrap.style.transition = 'opacity 0.5s';
        setTimeout(function(){ toastWrap.remove(); }, 500);
    }, 3500);
}
setInterval(function() {
}, 60000); 
</script>
</body>
</html>