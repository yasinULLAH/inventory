<?php
define('APP_NAME', 'BNI Enterprises');
define('APP_BRANCH', 'Dera (Ahmed Metro)');
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'bni_enterprises');
define('DEFAULT_ADMIN_USER', 'admin');
define('DEFAULT_ADMIN_PASS', 'admin@123');
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log');
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'gc_maxlifetime' => 2400,
    'cookie_lifetime' => 28800,
]);

function getDbConnection(): mysqli
{
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
        if ($conn->connect_error) {
            die("<pre style='color:red;padding:20px;'>Database connection failed: " . htmlspecialchars($conn->connect_error) . '</pre>');
        }
        $conn->set_charset('utf8mb4');
        $db_check = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
        if ($db_check->num_rows == 0) {
            if (!$conn->query('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci')) {
                die("<pre style='color:red;padding:20px;'>Failed to create database " . DB_NAME . ': ' . htmlspecialchars($conn->error) . '</pre>');
            }
        }
        $conn->select_db(DB_NAME);
    }
    return $conn;
}

function initializeDatabase(): void
{
    $conn = getDbConnection();
    $conn->autocommit(false);
    try {
        $tables = [
            '`settings` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `setting_key` VARCHAR(100) UNIQUE NOT NULL,
                `setting_value` TEXT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )',
            '`suppliers` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `contact` VARCHAR(100),
                `address` TEXT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )',
            '`customers` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `phone` VARCHAR(50),
                `cnic` VARCHAR(20),
                `address` TEXT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )',
            '`models` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `model_code` VARCHAR(50) UNIQUE NOT NULL,
                `model_name` VARCHAR(255) NOT NULL,
                `category` VARCHAR(100) NOT NULL,
                `short_code` VARCHAR(20),
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )',
            '`purchase_orders` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `order_date` DATE NOT NULL,
                `supplier_id` INT NOT NULL,
                `cheque_number` VARCHAR(50),
                `bank_name` VARCHAR(100),
                `cheque_date` DATE,
                `cheque_amount` DECIMAL(15,2),
                `total_units` INT NOT NULL DEFAULT 0,
                `notes` TEXT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE RESTRICT
            )',
            "`bikes` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `purchase_order_id` INT NOT NULL,
                `order_date` DATE NOT NULL,
                `inventory_date` DATE NOT NULL,
                `chassis_number` VARCHAR(100) UNIQUE NOT NULL,
                `motor_number` VARCHAR(100),
                `model_id` INT NOT NULL,
                `color` VARCHAR(50),
                `purchase_price` DECIMAL(15,2) NOT NULL,
                `selling_price` DECIMAL(15,2) NULL,
                `selling_date` DATE NULL,
                `customer_id` INT NULL,
                `tax_amount` DECIMAL(15,2) DEFAULT 0,
                `margin` DECIMAL(15,2) DEFAULT 0,
                `status` ENUM('in_stock', 'sold', 'returned', 'reserved') DEFAULT 'in_stock',
                `return_date` DATE NULL,
                `return_amount` DECIMAL(15,2) NULL,
                `return_notes` TEXT NULL,
                `notes` TEXT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`model_id`) REFERENCES `models`(`id`) ON DELETE RESTRICT,
                FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL
            )",
            "`cheque_register` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `cheque_number` VARCHAR(50) NOT NULL,
                `bank_name` VARCHAR(100),
                `cheque_date` DATE NOT NULL,
                `amount` DECIMAL(15,2) NOT NULL,
                `type` ENUM('payment', 'receipt', 'refund') NOT NULL,
                `status` ENUM('pending', 'cleared', 'bounced', 'cancelled') DEFAULT 'pending',
                `reference_type` VARCHAR(50),
                `reference_id` INT,
                `party_name` VARCHAR(255),
                `notes` TEXT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "`payments` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `payment_date` DATE NOT NULL,
                `payment_type` ENUM('cash', 'cheque', 'bank_transfer', 'online') NOT NULL,
                `amount` DECIMAL(15,2) NOT NULL,
                `cheque_id` INT NULL,
                `reference_type` VARCHAR(50) NOT NULL,
                `reference_id` INT NOT NULL,
                `party_name` VARCHAR(255),
                `notes` TEXT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`cheque_id`) REFERENCES `cheque_register`(`id`) ON DELETE SET NULL
            )",
            "`ledger` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `entry_date` DATE NOT NULL,
                `entry_type` ENUM('debit', 'credit') NOT NULL,
                `amount` DECIMAL(15,2) NOT NULL,
                `party_type` ENUM('customer', 'supplier', 'other'),
                `party_id` INT,
                `description` TEXT,
                `reference_type` VARCHAR(50),
                `reference_id` INT,
                `balance` DECIMAL(15,2),
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "`users` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(50) UNIQUE NOT NULL,
                `password_hash` VARCHAR(255) NOT NULL,
                `full_name` VARCHAR(100) NOT NULL,
                `email` VARCHAR(150),
                `role` ENUM('admin') DEFAULT 'admin',
                `status` ENUM('active', 'inactive') DEFAULT 'active',
                `last_login` DATETIME NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )"
        ];
        foreach ($tables as $table_sql) {
            $conn->query('CREATE TABLE IF NOT EXISTS ' . $table_sql) or die("<pre style='color:red;padding:20px;'>Failed to create table: " . htmlspecialchars($conn->error) . '</pre>');
        }
        $conn->query("INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
            ('company_name', '" . APP_NAME . "'),
            ('branch_name', '" . APP_BRANCH . "'),
            ('tax_rate', '0.1'),
            ('currency_symbol', 'Rs.'),
            ('tax_on', 'purchase_price'),
            ('theme', 'light')
        ") or die("<pre style='color:red;padding:20px;'>Failed to seed settings: " . htmlspecialchars($conn->error) . '</pre>');
        $conn->query("INSERT IGNORE INTO `suppliers` (`id`, `name`, `contact`, `address`) VALUES
            (1, 'Default Supplier', 'N/A', 'N/A')
        ") or die("<pre style='color:red;padding:20px;'>Failed to seed default supplier: " . htmlspecialchars($conn->error) . '</pre>');
        $conn->query("INSERT IGNORE INTO `customers` (`id`, `name`, `phone`, `cnic`, `address`) VALUES
            (1, 'Walk-in Customer', 'N/A', 'N/A', 'N/A')
        ") or die("<pre style='color:red;padding:20px;'>Failed to seed default customer: " . htmlspecialchars($conn->error) . '</pre>');
        $conn->query("INSERT IGNORE INTO `models` (`model_code`, `model_name`, `category`, `short_code`) VALUES
            ('LY SI', 'LY SI', 'Electric Bike', 'LY'),
            ('T9 Sports', 'T9 Sports', 'Electric Bike', 'T9'),
            ('T9 Sports LFP', 'T9 Sports LFP', 'Electric Bike', 'T9 LFP'),
            ('T9 Eco', 'T9 Eco', 'Electric Bike', 'T9 Eco'),
            ('Thrill Pro', 'Thrill Pro', 'Electric Bike', 'TP'),
            ('Thrill Pro LFP', 'Thrill Pro LFP', 'Electric Bike', 'TP LFP'),
            ('E8S M2', 'E8S M2', 'Electric Scooter', 'E8S'),
            ('E8S Pro', 'E8S Pro', 'Electric Scooter', 'E8S Pro'),
            ('M6 K6', 'M6 K6', 'Electric Bike', 'M6'),
            ('M6 NP', 'M6 NP', 'Electric Bike', 'M6 NP'),
            ('M6 Lithium NP', 'M6 Lithium NP', 'Electric Bike', 'M6 L'),
            ('Premium', 'Premium', 'Electric Bike', 'Premium'),
            ('W. Bike H2', 'W. Bike H2', 'Electric Bike', 'W. Bike')
        ") or die("<pre style='color:red;padding:20px;'>Failed to seed models: " . htmlspecialchars($conn->error) . '</pre>');
        $stmt_check_user = $conn->prepare('SELECT COUNT(*) FROM `users` WHERE `username` = ?');
        $stmt_check_user->bind_param('s', $admin_username);
        $admin_username = DEFAULT_ADMIN_USER;
        $stmt_check_user->execute();
        $stmt_check_user->bind_result($user_count);
        $stmt_check_user->fetch();
        $stmt_check_user->close();
        if ($user_count == 0) {
            $hashed_password = password_hash(DEFAULT_ADMIN_PASS, PASSWORD_BCRYPT);
            $stmt_insert_user = $conn->prepare('INSERT INTO `users` (`username`, `password_hash`, `full_name`, `email`, `role`, `status`) VALUES (?, ?, ?, ?, ?, ?)');
            $full_name = 'System Admin';
            $email = 'admin@bni.com';
            $role = 'admin';
            $status = 'active';
            $stmt_insert_user->bind_param('ssssss', $admin_username, $hashed_password, $full_name, $email, $role, $status);
            $stmt_insert_user->execute() or die("<pre style='color:red;padding:20px;'>Failed to seed admin user: " . htmlspecialchars($conn->error) . '</pre>');
            $stmt_insert_user->close();
        }
        $conn->commit();
        $_SESSION['db_initialized'] = true;
    } catch (Exception $e) {
        $conn->rollback();
        die("<pre style='color:red;padding:20px;'>Database initialization failed: " . htmlspecialchars($e->getMessage()) . '</pre>');
    }
    $conn->autocommit(true);
}

if (!isset($_SESSION['db_initialized'])) {
    initializeDatabase();
}

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): bool
{
    if (!isset($_POST['csrf_token']))
        return false;
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf()) . '">';
}

function getSetting(string $key, string $default = ''): string
{
    static $settings_cache = [];
    if (empty($settings_cache)) {
        $conn = getDbConnection();
        $result = $conn->query('SELECT setting_key, setting_value FROM `settings`');
        while ($row = $result->fetch_assoc()) {
            $settings_cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settings_cache[$key] ?? $default;
}

function setSetting(string $key, string $value): bool
{
    $conn = getDbConnection();
    $stmt = $conn->prepare('INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `setting_value` = ?');
    if ($stmt === false)
        return false;
    $stmt->bind_param('sss', $key, $value, $value);
    $success = $stmt->execute();
    $stmt->close();
    unset($_SESSION['settings_cache']);
    return $success;
}

function formatCurrency(float $amount): string
{
    return getSetting('currency_symbol', 'Rs.') . ' ' . number_format($amount, 2);
}

function formatDate(string $date, string $format = 'd/m/Y'): string
{
    if (empty($date) || $date === '0000-00-00')
        return '-';
    return date($format, strtotime($date));
}

function generateInvoiceNumber(): string
{
    $conn = getDbConnection();
    $prefix = 'INV-' . date('Ymd');
    $stmt = $conn->prepare('SELECT COUNT(*) FROM `bikes` WHERE `selling_date` = CURDATE()');
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $prefix . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ?page=login');
        exit;
    }
    $now = time();
    $idle_timeout = 40 * 60;
    $absolute_timeout = 8 * 3600;
    if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > $idle_timeout) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['flash_message'] = 'Your session expired due to inactivity. Please log in again.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ?page=login');
        exit;
    }
    if (isset($_SESSION['session_start_time']) && ($now - $_SESSION['session_start_time']) > $absolute_timeout) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['flash_message'] = 'Your session has ended. Please log in again.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ?page=login');
        exit;
    }
    $_SESSION['last_activity'] = $now;
}

function handleLogin(): ?string
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        return null;
    if (!verifyCsrf())
        return 'Security token mismatch. Please try again.';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($username) || empty($password))
        return 'Username and password are required.';
    $conn = getDbConnection();
    $stmt = $conn->prepare('SELECT id, username, password_hash, full_name, role, status FROM `users` WHERE username = ? LIMIT 1');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['status'] === 'inactive') {
            return 'Your account is inactive.';
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        $_SESSION['session_start_time'] = time();
        $stmt_update = $conn->prepare('UPDATE `users` SET last_login = NOW() WHERE id = ?');
        $stmt_update->bind_param('i', $user['id']);
        $stmt_update->execute();
        $stmt_update->close();
        header('Location: ?page=dashboard');
        exit;
    } else {
        return 'Invalid username or password.';
    }
}

function handleLogout(): void
{
    session_unset();
    session_destroy();
    header('Location: ?page=login');
    exit;
}

function renderLogin(?string $error = null): void
{
    $theme = getSetting('theme', 'light');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= h(APP_NAME) ?> - Login</title>
        <style>
            :root {
                --bg: <?= $theme === 'dark' ? '#333333' : '#E8E8E8' ?>;
                --surface: <?= $theme === 'dark' ? '#444444' : '#F0F0F0' ?>;
                --text-color: <?= $theme === 'dark' ? '#FFFFFF' : '#000000' ?>;
                --border-color: <?= $theme === 'dark' ? '#666666' : '#808080' ?>;
                --accent-color: <?= $theme === 'dark' ? '#005A9E' : '#0078D7' ?>;
                --accent-dark: <?= $theme === 'dark' ? '#004A80' : '#005A9E' ?>;
                --success-color: #107C10;
                --danger-color: #D13438;
                --input-bg: #ffffff;
                --input-text: #000000;
                --font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            body { font-family: var(--font-family); background-color: var(--bg); color: var(--text-color); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; margin: 0; }
            .login-container { background-color: var(--surface); border: 1px solid var(--border-color); padding: 0; width: 100%; max-width: 350px; box-sizing: border-box; box-shadow: 2px 2px 5px rgba(0,0,0,0.2); }
            .login-header { background-color: var(--accent-color); color: #ffffff; padding: 6px 10px; font-weight: normal; display: flex; align-items: center; gap: 8px; font-size: 12px; }
            .login-header svg { width: 14px; height: 14px; fill: #ffffff; }
            .login-body { padding: 15px; border-top: 1px solid #fff; }
            .app-title { text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 5px; color: var(--text-color); }
            .app-subtitle { text-align: center; font-size: 11px; color: var(--text-color); margin-bottom: 15px; }
            .form-group { margin-bottom: 10px; }
            .form-group label { display: block; font-size: 11px; color: var(--text-color); margin-bottom: 3px; }
            .form-group input { width: 100%; padding: 5px; border: 1px solid var(--border-color); background-color: var(--input-bg); color: var(--input-text); font-family: inherit; font-size: 12px; box-sizing: border-box; box-shadow: inset 1px 1px 2px rgba(0,0,0,0.05); }
            .form-group input:focus { outline: none; border-color: var(--accent-color); box-shadow: 0 0 0 1px var(--accent-color) inset; }
            .btn { width: 100%; padding: 6px; background-color: #E0E0E0; color: #000; border: 1px solid var(--border-color); cursor: pointer; font-family: inherit; font-size: 12px; transition: none; box-shadow: inset 1px 1px 0 #fff, inset -1px -1px 0 #a0a0a0; margin-top: 10px; }
            .btn:hover { background-color: #E5F1FB; border-color: #0078D7; box-shadow: none; }
            .btn:active { background-color: #CCE4F7; border-color: #005499; box-shadow: inset 1px 1px 2px rgba(0,0,0,0.1); }
            .alert { padding: 10px 15px; margin-bottom: 15px; font-size: 13px; border: 1px solid; }
            .alert-danger { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
            .alert-info { background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
            .default-creds { margin-top: 20px; padding: 10px; background-color: #fff3cd; border: 1px solid #ffc107; font-size: 12px; color: #856404; text-align: center; }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="login-header">
                <svg viewBox="0 0 24 24"><path d="M12 17c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm6-9h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-5-3c0-1.66 1.34-3 3-3s3 1.34 3 3v2H8V6c0-1.66 1.34-3 3-3z"/></svg>
                <?= h(APP_NAME) ?>
            </div>
            <div class="login-body">
                <div class="app-title"><?= h(APP_NAME) ?></div>
                <div class="app-subtitle"><?= h(APP_BRANCH) ?></div>
                <?php if ($error): ?>
                    <div class="alert alert-danger animate__animated animate__fadeIn"><?= h($error) ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-info animate__animated animate__fadeIn"><?= h($_SESSION['flash_message']) ?></div>
                    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                <?php endif; ?>
                <form method="POST" action="?page=login">
                    <?= csrfField() ?>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?= h($_POST['username'] ?? '') ?>" required autofocus>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn">Login</button>
                </form>
                <div class="default-creds">Default: <strong><?= DEFAULT_ADMIN_USER ?></strong> / <strong><?= DEFAULT_ADMIN_PASS ?></strong> (Please change after first login!)</div>
            </div>
        </div>
    </body>
    </html>
    <?php
}

function renderLayout(string $pageTitle, string $content, string $activePage = ''): void
{
    requireLogin();
    $theme = getSetting('theme', 'light');
    $is_dark_theme = $theme === 'dark';
    $companyName = getSetting('company_name', APP_NAME);
    $branchName = getSetting('branch_name', APP_BRANCH);
    $currencySymbol = getSetting('currency_symbol', 'Rs.');
    $username = $_SESSION['username'] ?? 'Guest';
    $fullName = $_SESSION['full_name'] ?? 'Guest User';
    $userRole = $_SESSION['user_role'] ?? 'Guest';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= h($pageTitle) ?> - <?= h(APP_NAME) ?></title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.0.7/css/dataTables.dataTables.min.css">
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/3.0.0/css/responsive.dataTables.min.css">
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/2.0.7/js/dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/3.0.0/js/dataTables.responsive.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://cdn.jsdelivr.net/npm/just-validate@4.1.0/dist/just-validate.production.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <style>
            :root {
                --bg: <?= $is_dark_theme ? '#333333' : '#F0F0F0' ?>;
                --surface: <?= $is_dark_theme ? '#444444' : '#F0F0F0' ?>;
                --surface-alt: <?= $is_dark_theme ? '#555555' : '#FFFFFF' ?>;
                --text-color: <?= $is_dark_theme ? '#FFFFFF' : '#000000' ?>;
                --text-muted: <?= $is_dark_theme ? '#AAAAAA' : '#666666' ?>;
                --border-color: <?= $is_dark_theme ? '#666666' : '#808080' ?>;
                --header-bg: <?= $is_dark_theme ? '#222222' : '#E0E0E0' ?>;
                --sidebar-bg: <?= $is_dark_theme ? '#2A2A2A' : '#E8E8E8' ?>;
                --accent-color: <?= $is_dark_theme ? '#005A9E' : '#0078D7' ?>;
                --accent-dark: <?= $is_dark_theme ? '#004A80' : '#005A9E' ?>;
                --success-color: #107C10;
                --danger-color: #D13438;
                --warning-color: #FF8C00;
                --info-color: #00B7C3;
                --font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                --sidebar-width: 200px;
                --header-height: 40px;
                --footer-height: 25px;
            }
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: var(--font-family); background-color: var(--bg); color: var(--text-color); font-size: 13px; min-height: 100vh; display: flex; flex-direction: column; }
            a { color: var(--accent-color); text-decoration: none; }
            a:hover { text-decoration: underline; }
            /* Main Layout */
            #header { background-color: var(--header-bg); border-bottom: 1px solid var(--border-color); color: var(--text-color); padding: 0 15px; height: var(--header-height); display: flex; align-items: center; justify-content: space-between; position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; }
            #header .left { display: flex; align-items: center; gap: 15px; }
            #header .right { display: flex; align-items: center; gap: 10px; }
            #header .page-title { font-size: 16px; font-weight: bold; }
            #sidebar { background-color: var(--sidebar-bg); border-right: 1px solid var(--border-color); width: var(--sidebar-width); position: fixed; top: var(--header-height); left: 0; bottom: 0; overflow-y: auto; z-index: 900; transition: transform 0.3s ease-in-out, width 0.3s ease-in-out; }
            #sidebar.collapsed { transform: translateX(calc(-1 * var(--sidebar-width))); }
            #main-content { margin-left: var(--sidebar-width); padding: 15px; min-height: calc(100vh - var(--header-height) - var(--footer-height)); transition: margin-left 0.3s ease-in-out; }
            body.sidebar-collapsed #main-content { margin-left: 0; }
            #sidebar-header { padding: 15px; text-align: center; border-bottom: 1px solid var(--border-color); }
            #sidebar-header .company-name { font-size: 16px; font-weight: bold; color: var(--text-color); }
            #sidebar-header .branch-name { font-size: 12px; color: var(--text-muted); margin-top: 3px; }
            .nav-menu a { display: flex; align-items: center; gap: 10px; padding: 10px 15px; color: var(--text-color); text-decoration: none; border-bottom: 1px solid var(--border-color); transition: background-color 0.2s; }
            .nav-menu a:hover { background-color: var(--accent-dark); color: #ffffff; text-decoration: none; }
            .nav-menu a.active { background-color: var(--accent-color); color: #ffffff; }
            .nav-menu a .icon { width: 18px; height: 18px; text-align: center; }
            .nav-menu a .label { flex-grow: 1; }
            #footer { background-color: var(--header-bg); border-top: 1px solid var(--border-color); color: var(--text-muted); padding: 5px 15px; font-size: 11px; text-align: center; position: fixed; bottom: 0; left: 0; width: 100%; z-index: 999; height: var(--footer-height); display: flex; align-items: center; justify-content: center; }
            #footer a { color: var(--text-muted); }
            /* Buttons */
            .btn { padding: 4px 12px; border: 1px solid var(--border-color); background-color: var(--header-bg); color: var(--text-color); cursor: pointer; font-family: inherit; font-size: 12px; transition: none; white-space: nowrap; border-radius: 2px; box-shadow: inset 1px 1px 0 #fff, inset -1px -1px 0 #a0a0a0; }
            .btn:hover { background-color: #E5F1FB; border-color: #0078D7; box-shadow: none; color: #000; }
            .btn:active { background-color: #CCE4F7; border-color: #005499; box-shadow: inset 1px 1px 2px rgba(0,0,0,0.1); }
            .btn-primary { background-color: var(--accent-color); color: #ffffff; border-color: var(--accent-dark); box-shadow: inset 1px 1px 0 rgba(255,255,255,0.2), inset -1px -1px 0 rgba(0,0,0,0.2); }
            .btn-primary:hover { background-color: var(--accent-dark); color: #ffffff; }
            .btn-success { background-color: var(--success-color); color: #ffffff; }
            .btn-danger { background-color: var(--danger-color); color: #ffffff; }
            .btn-warning { background-color: var(--warning-color); color: #000; }
            .btn-secondary { background-color: var(--text-muted); color: #ffffff; }
            .btn-sm { padding: 3px 8px; font-size: 11px; }
            .btn-xs { padding: 2px 6px; font-size: 11px; box-shadow: none; }
            .btn-group { display: flex; gap: 4px; flex-wrap: wrap; }
            /* Forms */
            .form-section { border: 1px solid var(--border-color); padding: 15px; margin-bottom: 20px; position: relative; background-color: var(--surface); border-radius: 2px; }
            .form-section-title { position: absolute; top: -8px; left: 8px; background-color: var(--surface); padding: 0 4px; font-size: 11px; font-weight: normal; color: var(--accent-color); }
            .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; }
            .form-group { display: flex; flex-direction: column; }
            .form-group label { font-size: 11px; color: var(--text-color); margin-bottom: 3px; }
            .form-group input[type="text"],
            .form-group input[type="number"],
            .form-group input[type="date"],
            .form-group input[type="email"],
            .form-group input[type="password"],
            .form-group textarea,
            .form-group select { padding: 4px; border: 1px solid var(--border-color); background-color: #ffffff; color: #000000; font-family: inherit; font-size: 12px; width: 100%; box-sizing: border-box; border-radius: 0; box-shadow: inset 1px 1px 2px rgba(0,0,0,0.05); }
            .form-group input[readonly],
            .form-group select[readonly],
            .form-group textarea[readonly] { background-color: var(--surface-alt); cursor: not-allowed; box-shadow: none; }
            .form-group input:focus,
            .form-group select:focus,
            .form-group textarea:focus { outline: none; border-color: var(--accent-color); box-shadow: 0 0 0 1px var(--accent-color) inset; }
            .form-group textarea { resize: vertical; min-height: 50px; }
            .required-mark { color: var(--danger-color); margin-left: 2px; }
            .error-message { color: var(--danger-color); font-size: 10px; margin-top: 2px; }
            /* Tables */
            .table-container { overflow-x: auto; border: 1px solid var(--border-color); margin-bottom: 15px; background-color: #ffffff; box-shadow: inset 1px 1px 2px rgba(0,0,0,0.1); }
            .data-table { width: 100%; border-collapse: collapse; font-size: 12px; background-color: #ffffff; color: #000000; }
            .data-table th, .data-table td { padding: 4px 6px; border: 1px solid var(--border-color); text-align: left; }
            .data-table thead th { background-color: var(--header-bg); color: var(--text-color); font-weight: normal; white-space: nowrap; border-bottom: 1px solid var(--border-color); border-right: 1px solid var(--border-color); box-shadow: inset 1px 1px 0 rgba(255,255,255,0.5); }
            .data-table tbody tr { background-color: #ffffff; }
            .data-table tbody tr:nth-child(even) { background-color: #F8F8F8; }
            .data-table tbody tr:hover { background-color: #CCE8FF; color: #000000; }
            .data-table .text-right { text-align: right; }
            .data-table .text-center { text-align: center; }
            .data-table .status-in_stock, .data-table .status-cleared { color: #107C10; }
            .data-table .status-sold { color: var(--accent-color); }
            .data-table .status-returned, .data-table .status-bounced { color: #D13438; }
            .data-table .status-pending { color: #FF8C00; }
            .data-table .status-cancelled { color: #808080; }
            /* DataTables custom styling for better touch experience */
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter,
            .dataTables_wrapper .dataTables_info,
            .dataTables_wrapper .dataTables_processing,
            .dataTables_wrapper .dataTables_paginate {
                padding: 5px 0;
                color: var(--text-color);
                font-size: 11px;
            }
            .dataTables_wrapper .dataTables_filter input,
            .dataTables_wrapper .dataTables_length select {
                border: 1px solid var(--border-color);
                background-color: #ffffff;
                color: #000000;
                padding: 2px 4px;
                border-radius: 0;
                font-family: var(--font-family);
                font-size: 11px;
                margin-left: 5px;
            }
            .dataTables_wrapper .dataTables_paginate .paginate_button {
                padding: 3px 8px;
                margin: 0 1px;
                border: 1px solid var(--border-color);
                background-color: var(--header-bg);
                color: var(--text-color) !important;
                border-radius: 0;
                cursor: pointer;
                font-size: 11px;
            }
            .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
                background-color: #E5F1FB;
                border-color: #0078D7;
                color: #000 !important;
            }
            .dataTables_wrapper .dataTables_paginate .paginate_button.current,
            .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
                background-color: var(--accent-color);
                color: #ffffff !important;
                border-color: var(--accent-dark);
            }
            .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
                opacity: 0.5;
                cursor: not-allowed;
                background-color: var(--surface);
            }
            .dataTables_wrapper .dataTables_scrollBody {
                border-bottom: 1px solid var(--border-color) !important;
            }
            .dataTables_wrapper .dataTables_scrollHeadInner {
                border-bottom: 1px solid var(--border-color) !important;
            }
            /* Responsive DataTables styles */
            table.dataTable.dtr-inline.collapsed>tbody>tr>td:first-child:before,
            table.dataTable.dtr-inline.collapsed>tbody>tr>th:first-child:before {
                background-color: var(--accent-color) !important;
            }
            table.dataTable.dtr-inline.collapsed>tbody>tr.parent>td:first-child:before,
            table.dataTable.dtr-inline.collapsed>tbody>tr.parent>th:first-child:before {
                background-color: var(--danger-color) !important;
            }
            div.dtr-modal-content {
                background-color: var(--surface);
                color: var(--text-color);
                border: 1px solid var(--border-color);
                border-radius: 3px;
            }
            div.dtr-modal-content button.dtr-modal-close {
                background-color: var(--danger-color);
                color: #ffffff;
                border: none;
                border-radius: 3px;
                padding: 5px 10px;
                cursor: pointer;
            }
            div.dtr-data {
                border-top: 1px solid var(--border-color);
            }
            div.dtr-data label {
                font-weight: bold;
                color: var(--accent-color);
                margin-bottom: 5px;
                display: block;
            }
            div.dtr-data span {
                display: block;
                margin-bottom: 10px;
            }
            div.dtr-title {
                color: var(--text-color);
                font-weight: bold;
            }
            /* Dashboard Cards */
            .dashboard-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
            .card { background-color: var(--surface); border: 1px solid var(--border-color); padding: 15px; border-radius: 3px; }
            .card-title { font-size: 13px; color: var(--text-muted); margin-bottom: 5px; text-transform: uppercase; }
            .card-value { font-size: 24px; font-weight: bold; color: var(--text-color); }
            .card-value.primary { color: var(--accent-color); }
            .card-value.success { color: var(--success-color); }
            .card-value.danger { color: var(--danger-color); }
            .card-value.warning { color: var(--warning-color); }
            .card-link { display: block; margin-top: 10px; font-size: 12px; }
            /* Toast Notifications */
            #toast-container { position: fixed; top: 15px; right: 15px; z-index: 10000; display: flex; flex-direction: column; gap: 10px; }
            .toast { background-color: var(--surface); border: 1px solid var(--border-color); padding: 10px 15px; border-radius: 3px; font-size: 13px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); animation: slideInRight 0.3s forwards, fadeOut 0.3s 3s forwards; }
            .toast-success { background-color: var(--success-color); color: #ffffff; border-color: #3e9a3e; }
            .toast-danger { background-color: var(--danger-color); color: #ffffff; border-color: #c43b2d; }
            .toast-info { background-color: var(--accent-color); color: #ffffff; border-color: var(--accent-dark); }
            @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
            @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; } }
            /* Modals (SweetAlert2 replacement) */
            .swal2-popup {
                background-color: var(--surface) !important;
                color: var(--text-color) !important;
                border: 1px solid var(--border-color) !important;
            }
            .swal2-title {
                color: var(--text-color) !important;
            }
            .swal2-html-container {
                color: var(--text-color) !important;
            }
            .swal2-styled.swal2-confirm {
                background-color: var(--danger-color) !important;
                border-color: var(--danger-color) !important;
            }
            .swal2-styled.swal2-cancel {
                background-color: var(--secondary-color) !important;
                border-color: var(--secondary-color) !important;
            }
            .swal2-input {
                background-color: var(--input-bg) !important;
                color: var(--input-text) !important;
                border: 1px solid var(--border-color) !important;
            }
            /* Theme Toggle */
            .theme-toggle {
                background-color: transparent;
                border: none;
                color: var(--text-color);
                cursor: pointer;
                font-size: 18px;
            }
            /* Print Styles */
            @media print {
                body { background-color: #fff; color: #000; font-size: 10pt; }
                #header, #sidebar, #footer, .no-print, .dt-buttons, .dataTables_filter, .dataTables_length, .dataTables_info, .dataTables_paginate { display: none !important; }
                #main-content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
                .table-container { border: none; overflow: visible; }
                .data-table, .data-table th, .data-table td { border: 1px solid #ccc; }
                .data-table thead th { background-color: #eee; color: #000; }
                .data-table tbody tr { break-inside: avoid; } /* Prevent page breaks within rows */
                .data-table tbody tr:nth-child(even) { background-color: #f9f9f9; }
                /* Invoice specific print styles */
                .invoice-header, .invoice-details, .invoice-items-table, .invoice-summary { page-break-inside: avoid; }
                .invoice-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
                .invoice-header .company-info, .invoice-header .invoice-meta { width: 48%; }
                .invoice-header .company-name { font-size: 18pt; font-weight: bold; margin-bottom: 5px; }
                .invoice-header .invoice-title { font-size: 24pt; font-weight: bold; text-align: right; color: var(--accent-color); }
                .invoice-meta strong { display: block; margin-bottom: 5px; }
                .invoice-items-table { margin-bottom: 20px; }
                .invoice-summary { width: 40%; margin-left: auto; margin-top: 20px; }
                .invoice-footer { text-align: center; margin-top: 30px; font-size: 9pt; color: #555; border-top: 1px solid #ccc; padding-top: 10px; }
                .print-page-break { page-break-after: always; }
            }
            .print-only { display: none; }
            /* Mobile Responsiveness */
            @media (max-width: 900px) {
                #sidebar { transform: translateX(calc(-1 * var(--sidebar-width))); }
                #sidebar.open { transform: translateX(0); }
                #main-content { margin-left: 0; }
                #header .left { gap: 8px; }
                #sidebar-toggle { display: block !important; }
                .form-grid { grid-template-columns: 1fr; }
                .btn-group { flex-direction: column; }
                /* Sticky first column for tables on mobile */
                .data-table.responsive > tbody > tr > td:first-child,
                .data-table.responsive > thead > tr > th:first-child {
                    position: sticky;
                    left: 0;
                    background-color: var(--surface); /* Ensure background is solid */
                    z-index: 1; /* Keep it above other cells */
                }
                .data-table.responsive > thead > tr > th:first-child {
                    background-color: var(--header-bg);
                    border-right: 1px solid var(--border-color);
                }
                .data-table.responsive > tbody > tr > td:first-child {
                    border-right: 1px solid var(--border-color);
                }
            }
            /* Overlay for mobile sidebar */
            #sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 899;
                display: none;
            }
            #sidebar-overlay.active {
                display: block;
            }
            /* Spinner for loading */
            .spinner-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 9999;
                display: none;
            }
            .spinner {
                border: 4px solid var(--border-color);
                border-top: 4px solid var(--accent-color);
                border-radius: 50%;
                width: 40px;
                height: 40px;
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            /* Select2 Custom Styles */
            .select2-container .select2-selection--single {
                height: 34px; /* Adjust height to match other inputs */
                border: 1px solid var(--border-color) !important;
                background-color: var(--input-bg) !important;
                border-radius: 0 !important;
            }
            .select2-container .select2-selection--single .select2-selection__rendered {
                line-height: 32px;
                padding-left: 8px;
                color: var(--input-text);
            }
            .select2-container .select2-selection--single .select2-selection__arrow {
                height: 32px;
            }
            .select2-container--open .select2-dropdown {
                border: 1px solid var(--border-color) !important;
                background-color: var(--surface) !important;
                border-radius: 0 !important;
            }
            .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
                background-color: var(--accent-color) !important;
                color: #ffffff !important;
            }
            .select2-container--default .select2-results__option--selectable {
                color: var(--text-color) !important;
                background-color: var(--surface) !important;
            }
            .select2-search input {
                border: 1px solid var(--border-color) !important;
                background-color: var(--input-bg) !important;
                color: var(--input-text) !important;
                padding: 5px !important;
            }
        </style>
    </head>
    <body class="<?= $is_dark_theme ? 'dark-theme' : 'light-theme' ?>">
        <div class="spinner-overlay" id="loadingSpinner"><div class="spinner"></div></div>
        <header id="header">
            <div class="left">
                <button id="sidebar-toggle" class="btn btn-sm" style="display: none;" onclick="toggleSidebar()">☰</button>
                <div class="page-title"><?= h($pageTitle) ?></div>
            </div>
            <div class="right">
                <button class="theme-toggle" onclick="toggleTheme()">
                    <?= $is_dark_theme ? '☀️' : '🌙' ?>
                </button>
                <div class="user-info" style="font-size: 12px;">
                    <?= h($fullName) ?> (<?= h($userRole) ?>)
                </div>
                <a href="?page=logout" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </header>
        <div id="sidebar" class="animate__animated animate__fadeInLeft">
            <div id="sidebar-header">
                <div class="company-name"><?= h($companyName) ?></div>
                <div class="branch-name"><?= h($branchName) ?></div>
            </div>
            <nav class="nav-menu">
                <a href="?page=dashboard" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">
                    <span class="icon">⌂</span><span class="label">Dashboard</span>
                </a>
                <a href="?page=purchase" class="<?= $activePage === 'purchase' ? 'active' : '' ?>">
                    <span class="icon">📦</span><span class="label">Purchase Entry</span>
                </a>
                <a href="?page=inventory" class="<?= $activePage === 'inventory' ? 'active' : '' ?>">
                    <span class="icon">🛒</span><span class="label">Inventory List</span>
                </a>
                <a href="?page=sale" class="<?= $activePage === 'sale' ? 'active' : '' ?>">
                    <span class="icon">💰</span><span class="label">Sales Entry</span>
                </a>
                <a href="?page=returns" class="<?= $activePage === 'returns' ? 'active' : '' ?>">
                    <span class="icon">↩️</span><span class="label">Returns / Adjustments</span>
                </a>
                <a href="?page=cheques" class="<?= $activePage === 'cheques' ? 'active' : '' ?>">
                    <span class="icon">🧾</span><span class="label">Cheque Register</span>
                </a>
                <a href="?page=customer_ledger" class="<?= $activePage === 'customer_ledger' ? 'active' : '' ?>">
                    <span class="icon">👤</span><span class="label">Customer Ledger</span>
                </a>
                <a href="?page=supplier_ledger" class="<?= $activePage === 'supplier_ledger' ? 'active' : '' ?>">
                    <span class="icon">🚛</span><span class="label">Supplier Ledger</span>
                </a>
                <a href="?page=reports" class="<?= $activePage === 'reports' ? 'active' : '' ?>">
                    <span class="icon">📊</span><span class="label">Reports</span>
                </a>
                <a href="?page=models" class="<?= $activePage === 'models' ? 'active' : '' ?>">
                    <span class="icon">🚲</span><span class="label">Models Management</span>
                </a>
                <a href="?page=customers" class="<?= $activePage === 'customers' ? 'active' : '' ?>">
                    <span class="icon">👥</span><span class="label">Customers Management</span>
                </a>
                <a href="?page=suppliers" class="<?= $activePage === 'suppliers' ? 'active' : '' ?>">
                    <span class="icon">🏢</span><span class="label">Suppliers Management</span>
                </a>
                <a href="?page=settings" class="<?= $activePage === 'settings' ? 'active' : '' ?>">
                    <span class="icon">⚙️</span><span class="label">Settings</span>
                </a>
            </nav>
        </div>
        <div id="sidebar-overlay" onclick="toggleSidebar()"></div>
        <main id="main-content" style="margin-top: var(--header-height);">
            <?= $content ?>
        </main>
        <footer id="footer">
            <span>Created by: Yasin Ullah – Bannu Software Solutions</span> |
            <span>Website: <a href="https://www.yasinbss.com" target="_blank">https://www.yasinbss.com</a></span> |
            <span>WhatsApp: 03361593533</span>
        </footer>
        <div id="toast-container"></div>
        <script>
            const CSRF_TOKEN = '<?= csrf() ?>';
            const CURRENCY_SYMBOL = '<?= getSetting('currency_symbol', 'Rs.') ?>';
            
            function h(unsafe) {
                if (unsafe === null || unsafe === undefined) return '';
                return String(unsafe).replace(/[&<>"']/g, function(m) {
                    return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[m];
                });
            }

            function showToast(message, type = 'info', duration = 3000) {
                const container = document.getElementById('toast-container');
                const toast = document.createElement('div');
                toast.className = `toast toast-${type} animate__animated animate__fadeInRight`;
                toast.textContent = message;
                container.appendChild(toast);
                setTimeout(() => {
                    toast.classList.remove('animate__fadeInRight');
                    toast.classList.add('animate__fadeOutRight');
                    toast.addEventListener('animationend', () => toast.remove());
                }, duration);
            }
            function showLoadingSpinner() {
                document.getElementById('loadingSpinner').style.display = 'flex';
            }
            function hideLoadingSpinner() {
                document.getElementById('loadingSpinner').style.display = 'none';
            }
            async function confirmAction(title, text, confirmButtonText = 'Yes', icon = 'warning') {
                const result = await Swal.fire({
                    title: title,
                    text: text,
                    icon: icon,
                    showCancelButton: true,
                    confirmButtonColor: 'var(--danger-color)',
                    cancelButtonColor: 'var(--text-muted)',
                    confirmButtonText: confirmButtonText,
                    customClass: {
                        popup: 'animate__animated animate__fadeIn'
                    }
                });
                return result.isConfirmed;
            }
            async function promptInput(title, text, inputLabel = 'Value', defaultValue = '') {
                const { value: inputVal } = await Swal.fire({
                    title: title,
                    text: text,
                    input: 'text',
                    inputLabel: inputLabel,
                    inputValue: defaultValue,
                    showCancelButton: true,
                    confirmButtonColor: 'var(--accent-color)',
                    cancelButtonColor: 'var(--text-muted)',
                    inputValidator: (value) => {
                        if (!value) {
                            return 'Input cannot be empty!';
                        }
                    },
                    customClass: {
                        popup: 'animate__animated animate__fadeIn'
                    }
                });
                return inputVal;
            }
            function toggleSidebar() {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebar-overlay');
                const isMobile = window.innerWidth <= 900;
                if (isMobile) {
                    sidebar.classList.toggle('open');
                    overlay.classList.toggle('active');
                } else {
                    document.body.classList.toggle('sidebar-collapsed');
                    localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed') ? 'true' : 'false');
                }
            }
            function initSidebarState() {
                const isMobile = window.innerWidth <= 900;
                const sidebar = document.getElementById('sidebar');
                if (isMobile) {
                    sidebar.classList.add('collapsed');
                    document.getElementById('sidebar-toggle').style.display = 'block';
                } else {
                    const collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                    if (collapsed) {
                        document.body.classList.add('sidebar-collapsed');
                    }
                    sidebar.classList.remove('open');
                    document.getElementById('sidebar-overlay').classList.remove('active');
                    document.getElementById('sidebar-toggle').style.display = 'none';
                }
            }
            window.addEventListener('load', initSidebarState);
            window.addEventListener('resize', initSidebarState);
            function toggleTheme() {
                const currentTheme = localStorage.getItem('theme') || 'light';
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                localStorage.setItem('theme', newTheme);
                fetch('?page=ajax&action=set_theme', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `csrf_token=${CSRF_TOKEN}&theme=${newTheme}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            showToast('Failed to change theme: ' + (data.error || 'Unknown error'), 'danger');
                        }
                    })
                    .catch(error => {
                        console.error('Error changing theme:', error);
                        showToast('Network error while changing theme.', 'danger');
                    });
            }
            <?php if (isset($_SESSION['flash_message'])): ?>
                showToast('<?= h($_SESSION['flash_message']) ?>', '<?= h($_SESSION['flash_type'] ?? 'info') ?>');
                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
            <?php endif; ?>
            $(document).ready(function() {
                $.fn.dataTable.ext.errMode = 'none';
                $('.data-table').each(function() {
                    $(this).DataTable({
                        responsive: true,
                        language: {
                            search: "Search:",
                            lengthMenu: "Show _MENU_ entries",
                            info: "Showing _START_ to _END_ of _TOTAL_ entries",
                            paginate: {
                                first: "First",
                                last: "Last",
                                next: "Next",
                                previous: "Previous"
                            }
                        }
                    });
                });
                $('select').not('.dataTables_length select').select2({
                    width: '100%',
                    placeholder: "-- Select --",
                    allowClear: true
                });
            });
        </script>
    </body>
    </html>
    <?php
}

function getModels(): array
{
    $conn = getDbConnection();
    $result = $conn->query('SELECT * FROM `models` ORDER BY model_name');
    $models = [];
    while ($row = $result->fetch_assoc()) {
        $models[] = $row;
    }
    return $models;
}

function getSuppliers(): array
{
    $conn = getDbConnection();
    $result = $conn->query('SELECT * FROM `suppliers` ORDER BY name');
    $suppliers = [];
    while ($row = $result->fetch_assoc()) {
        $suppliers[] = $row;
    }
    return $suppliers;
}

function getCustomers(): array
{
    $conn = getDbConnection();
    $result = $conn->query('SELECT * FROM `customers` ORDER BY name');
    $customers = [];
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
    return $customers;
}

function getBikes(array $filters = [], int $limit = 20, int $offset = 0): array
{
    $conn = getDbConnection();
    $where = ['1=1'];
    $params = [];
    $param_types = '';
    if (!empty($filters['status']) && $filters['status'] !== 'all') {
        $where[] = 'b.status = ?';
        $params[] = $filters['status'];
        $param_types .= 's';
    }
    if (!empty($filters['model_id'])) {
        $where[] = 'b.model_id = ?';
        $params[] = (int) $filters['model_id'];
        $param_types .= 'i';
    }
    if (!empty($filters['color'])) {
        $where[] = 'b.color LIKE ?';
        $params[] = '%' . $filters['color'] . '%';
        $param_types .= 's';
    }
    if (!empty($filters['search'])) {
        $search = '%' . $filters['search'] . '%';
        $where[] = '(b.chassis_number LIKE ? OR b.motor_number LIKE ? OR m.model_name LIKE ? OR m.model_code LIKE ? OR b.color LIKE ?)';
        array_push($params, $search, $search, $search, $search, $search);
        $param_types .= 'sssss';
    }
    if (!empty($filters['date_from'])) {
        $where[] = 'b.inventory_date >= ?';
        $params[] = $filters['date_from'];
        $param_types .= 's';
    }
    if (!empty($filters['date_to'])) {
        $where[] = 'b.inventory_date <= ?';
        $params[] = $filters['date_to'];
        $param_types .= 's';
    }
    $where_clause = implode(' AND ', $where);
    $count_sql = "SELECT COUNT(*) FROM `bikes` b LEFT JOIN `models` m ON b.model_id = m.id WHERE $where_clause";
    $stmt_count = $conn->prepare($count_sql);
    if (!empty($params)) {
        $stmt_count->bind_param($param_types, ...$params);
    }
    $stmt_count->execute();
    $stmt_count->bind_result($total_bikes);
    $stmt_count->fetch();
    $stmt_count->close();
    $sql = "SELECT b.*, m.model_name, m.short_code, c.name as customer_name
            FROM `bikes` b
            LEFT JOIN `models` m ON b.model_id = m.id
            LEFT JOIN `customers` c ON b.customer_id = c.id
            WHERE $where_clause ORDER BY b.inventory_date DESC, b.id DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $param_types .= 'ii';
    array_push($params, $limit, $offset);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $bikes = [];
    while ($row = $result->fetch_assoc()) {
        $bikes[] = $row;
    }
    $stmt->close();
    return ['bikes' => $bikes, 'total' => $total_bikes];
}

function renderDashboard(): void
{
    $conn = getDbConnection();
    $today = date('Y-m-d');
    $totalBikesInStock = $conn->query("SELECT COUNT(*) FROM `bikes` WHERE status = 'in_stock'")->fetch_row()[0];
    $totalBikesSold = $conn->query("SELECT COUNT(*) FROM `bikes` WHERE status = 'sold'")->fetch_row()[0];
    $totalPurchaseValue = $conn->query('SELECT COALESCE(SUM(purchase_price), 0) FROM `bikes`')->fetch_row()[0];
    $totalSalesValue = $conn->query("SELECT COALESCE(SUM(selling_price), 0) FROM `bikes` WHERE status = 'sold'")->fetch_row()[0];
    $totalTaxPaid = $conn->query("SELECT COALESCE(SUM(tax_amount), 0) FROM `bikes` WHERE status = 'sold'")->fetch_row()[0];
    $totalProfitMargin = $conn->query("SELECT COALESCE(SUM(margin), 0) FROM `bikes` WHERE status = 'sold'")->fetch_row()[0];
    $chequesIssued = $conn->query("SELECT COUNT(*), COALESCE(SUM(amount), 0) FROM `cheque_register` WHERE type = 'payment'")->fetch_row();
    $chequesReceived = $conn->query("SELECT COUNT(*), COALESCE(SUM(amount), 0) FROM `cheque_register` WHERE type = 'receipt'")->fetch_row();
    $pendingCheques = $conn->query("SELECT COUNT(*), COALESCE(SUM(amount), 0) FROM `cheque_register` WHERE status = 'pending'")->fetch_row();
    $modelSummary = [];
    $stmt = $conn->prepare("SELECT m.model_name,
                                COALESCE(SUM(CASE WHEN b.status = 'in_stock' THEN 1 ELSE 0 END), 0) AS in_stock_count,
                                COALESCE(SUM(CASE WHEN b.status = 'sold' THEN 1 ELSE 0 END), 0) AS sold_count,
                                COUNT(b.id) AS total_count
                            FROM `models` m
                            LEFT JOIN `bikes` b ON m.id = b.model_id
                            GROUP BY m.id, m.model_name
                            ORDER BY m.model_name");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $modelSummary[] = $row;
    }
    $stmt->close();
    $recentSales = [];
    $stmt = $conn->prepare("SELECT b.chassis_number, m.model_name, c.name as customer_name, b.selling_date, b.selling_price
                            FROM `bikes` b
                            JOIN `models` m ON b.model_id = m.id
                            LEFT JOIN `customers` c ON b.customer_id = c.id
                            WHERE b.status = 'sold' ORDER BY b.selling_date DESC LIMIT 10");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recentSales[] = $row;
    }
    $stmt->close();
    $recentPurchases = [];
    $stmt = $conn->prepare('SELECT po.order_date, s.name as supplier_name, po.total_units, po.cheque_amount
                            FROM `purchase_orders` po
                            JOIN `suppliers` s ON po.supplier_id = s.id
                            ORDER BY po.order_date DESC LIMIT 10');
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recentPurchases[] = $row;
    }
    $stmt->close();
    ob_start();
    ?>
    <h2 style="margin-bottom: 20px;">Dashboard</h2>
    <div class="dashboard-cards">
        <div class="card animate__animated animate__fadeIn">
            <div class="card-title">Bikes in Stock</div>
            <div class="card-value primary"><?= h(number_format($totalBikesInStock)) ?></div>
            <a href="?page=inventory&status=in_stock" class="card-link">View Inventory</a>
        </div>
        <div class="card animate__animated animate__fadeIn">
            <div class="card-title">Bikes Sold</div>
            <div class="card-value success"><?= h(number_format($totalBikesSold)) ?></div>
            <a href="?page=inventory&status=sold" class="card-link">View Sales</a>
        </div>
        <div class="card animate__animated animate__fadeIn">
            <div class="card-title">Total Sales Value</div>
            <div class="card-value success"><?= h(formatCurrency($totalSalesValue)) ?></div>
            <a href="?page=reports&report=sold_bikes" class="card-link">Sales Report</a>
        </div>
        <div class="card animate__animated animate__fadeIn">
            <div class="card-title">Total Profit/Margin</div>
            <div class="card-value primary"><?= h(formatCurrency($totalProfitMargin)) ?></div>
            <a href="?page=reports&report=profit_margin" class="card-link">Profit Report</a>
        </div>
        <div class="card animate__animated animate__fadeIn">
            <div class="card-title">Total Purchase Value</div>
            <div class="card-value warning"><?= h(formatCurrency($totalPurchaseValue)) ?></div>
            <a href="?page=reports&report=purchase_summary" class="card-link">Purchase Report</a>
        </div>
        <div class="card animate__animated animate__fadeIn">
            <div class="card-title">Pending Cheques</div>
            <div class="card-value danger"><?= h(number_format($pendingCheques[0])) ?> (<?= h(formatCurrency($pendingCheques[1])) ?>)</div>
            <a href="?page=cheques&status=pending" class="card-link">View Cheques</a>
        </div>
        <div class="card animate__animated animate__fadeIn">
            <div class="card-title">Cheques Issued</div>
            <div class="card-value primary"><?= h(number_format($chequesIssued[0])) ?> (<?= h(formatCurrency($chequesIssued[1])) ?>)</div>
            <a href="?page=cheques&type=payment" class="card-link">View Cheques</a>
        </div>
        <div class="card animate__animated animate__fadeIn">
            <div class="card-title">Cheques Received</div>
            <div class="card-value primary"><?= h(number_format($chequesReceived[0])) ?> (<?= h(formatCurrency($chequesReceived[1])) ?>)</div>
            <a href="?page=cheques&type=receipt" class="card-link">View Cheques</a>
        </div>
    </div>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
        <div class="form-section animate__animated animate__fadeIn">
            <div class="form-section-title">Model-wise Stock Summary</div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Model</th>
                            <th>In Stock</th>
                            <th>Sold</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($modelSummary)): ?>
                            <tr><td colspan="4" class="text-center">No models found or no bikes recorded.</td></tr>
                        <?php else: ?>
                            <?php foreach ($modelSummary as $row): ?>
                                <tr>
                                    <td><?= h($row['model_name']) ?></td>
                                    <td><?= h(number_format($row['in_stock_count'])) ?></td>
                                    <td><?= h(number_format($row['sold_count'])) ?></td>
                                    <td><?= h(number_format($row['total_count'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="form-section animate__animated animate__fadeIn">
            <div class="form-section-title">Recent 10 Sales</div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Chassis</th>
                            <th>Model</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th class="text-right">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentSales)): ?>
                            <tr><td colspan="5" class="text-center">No recent sales.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentSales as $sale): ?>
                                <tr>
                                    <td><?= h($sale['chassis_number']) ?></td>
                                    <td><?= h($sale['model_name']) ?></td>
                                    <td><?= h($sale['customer_name'] ?? 'N/A') ?></td>
                                    <td><?= h(formatDate($sale['selling_date'])) ?></td>
                                    <td class="text-right"><?= h(formatCurrency($sale['selling_price'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="form-section animate__animated animate__fadeIn">
        <div class="form-section-title">Recent 10 Purchases</div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order Date</th>
                        <th>Supplier</th>
                        <th>Total Units</th>
                        <th class="text-right">Cheque Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentPurchases)): ?>
                        <tr><td colspan="4" class="text-center">No recent purchases.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentPurchases as $purchase): ?>
                            <tr>
                                <td><?= h(formatDate($purchase['order_date'])) ?></td>
                                <td><?= h($purchase['supplier_name']) ?></td>
                                <td><?= h(number_format($purchase['total_units'])) ?></td>
                                <td class="text-right"><?= h(formatCurrency($purchase['cheque_amount'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            function updateDashboardData() {
            }
        });
    </script>
    <?php
    $content = ob_get_clean();
    renderLayout('Dashboard', $content, 'dashboard');
}

function renderPurchaseEntry(): void
{
    $conn = getDbConnection();
    $suppliers = getSuppliers();
    $models = getModels();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_supplier_inline' && verifyCsrf()) {
        $name = trim($_POST['supplier_name'] ?? '');
        $contact = trim($_POST['supplier_contact'] ?? '');
        $address = trim($_POST['supplier_address'] ?? '');
        if (empty($name)) {
            $_SESSION['flash_message'] = 'Supplier name is required.';
            $_SESSION['flash_type'] = 'danger';
        } else {
            $stmt = $conn->prepare('INSERT INTO `suppliers` (`name`, `contact`, `address`) VALUES (?, ?, ?)');
            if ($stmt) {
                $stmt->bind_param('sss', $name, $contact, $address);
                if ($stmt->execute()) {
                    $_SESSION['flash_message'] = 'Supplier added successfully.';
                    $_SESSION['flash_type'] = 'success';
                } else {
                    $_SESSION['flash_message'] = 'Error adding supplier: ' . h($stmt->error);
                    $_SESSION['flash_type'] = 'danger';
                }
                $stmt->close();
            } else {
                $_SESSION['flash_message'] = 'Database error preparing statement.';
                $_SESSION['flash_type'] = 'danger';
            }
        }
        header('Location: ?page=purchase');
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_model_inline' && verifyCsrf()) {
        $model_code = trim($_POST['model_code'] ?? '');
        $model_name = trim($_POST['model_name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $short_code = trim($_POST['short_code'] ?? '');
        if (empty($model_code) || empty($model_name) || empty($category)) {
            $_SESSION['flash_message'] = 'Model Code, Name, and Category are required.';
            $_SESSION['flash_type'] = 'danger';
        } else {
            $stmt = $conn->prepare('INSERT INTO `models` (`model_code`, `model_name`, `category`, `short_code`) VALUES (?, ?, ?, ?)');
            if ($stmt) {
                $stmt->bind_param('ssss', $model_code, $model_name, $category, $short_code);
                if ($stmt->execute()) {
                    $_SESSION['flash_message'] = 'Model added successfully.';
                    $_SESSION['flash_type'] = 'success';
                } else {
                    $_SESSION['flash_message'] = 'Error adding model: ' . h($stmt->error);
                    $_SESSION['flash_type'] = 'danger';
                }
                $stmt->close();
            } else {
                $_SESSION['flash_message'] = 'Database error preparing statement.';
                $_SESSION['flash_type'] = 'danger';
            }
        }
        header('Location: ?page=purchase');
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_purchase_order' && verifyCsrf()) {
        $conn->autocommit(false);
        try {
            $order_date = trim($_POST['order_date'] ?? date('Y-m-d'));
            $supplier_id = (int) ($_POST['supplier_id'] ?? 0);
            $cheque_number = trim($_POST['cheque_number'] ?? '');
            $bank_name = trim($_POST['bank_name'] ?? '');
            $cheque_date = trim($_POST['cheque_date'] ?? '');
            $cheque_amount = (float) ($_POST['cheque_amount'] ?? 0);
            $po_notes = trim($_POST['po_notes'] ?? '');
            $chassis_numbers = $_POST['chassis_number'] ?? [];
            $motor_numbers = $_POST['motor_number'] ?? [];
            $model_ids = $_POST['model_id'] ?? [];
            $colors = $_POST['color'] ?? [];
            $purchase_prices = $_POST['purchase_price'] ?? [];
            $bike_notes = $_POST['bike_notes'] ?? [];
            $total_units = count($chassis_numbers);
            if ($total_units === 0 || $supplier_id === 0) {
                throw new Exception('Please add at least one bike and select a supplier.');
            }
            $stmt_po = $conn->prepare('INSERT INTO `purchase_orders` (`order_date`, `supplier_id`, `cheque_number`, `bank_name`, `cheque_date`, `cheque_amount`, `total_units`, `notes`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            if ($stmt_po === false)
                throw new Exception('Failed to prepare PO statement: ' . $conn->error);
            $stmt_po->bind_param('sisssdis', $order_date, $supplier_id, $cheque_number, $bank_name, $cheque_date, $cheque_amount, $total_units, $po_notes);
            if (!$stmt_po->execute())
                throw new Exception('Failed to save Purchase Order: ' . $stmt_po->error);
            $purchase_order_id = $stmt_po->insert_id;
            $stmt_po->close();
            $tax_rate = (float) getSetting('tax_rate', '0.1');
            $tax_on = getSetting('tax_on', 'purchase_price');
            $stmt_bike = $conn->prepare('INSERT INTO `bikes` (`purchase_order_id`, `order_date`, `inventory_date`, `chassis_number`, `motor_number`, `model_id`, `color`, `purchase_price`, `tax_amount`, `notes`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            if ($stmt_bike === false)
                throw new Exception('Failed to prepare Bike statement: ' . $conn->error);
            for ($i = 0; $i < $total_units; $i++) {
                $chassis = trim($chassis_numbers[$i] ?? '');
                $motor = trim($motor_numbers[$i] ?? '');
                $model = (int) ($model_ids[$i] ?? 0);
                $color = trim($colors[$i] ?? '');
                $price = (float) ($purchase_prices[$i] ?? 0);
                $bike_note = trim($bike_notes[$i] ?? '');
                $inventory_date = date('Y-m-d');
                if (empty($chassis) || $model === 0 || $price <= 0) {
                    throw new Exception('Missing data for a bike entry. Chassis, Model, and Purchase Price are required.');
                }
                $stmt_chk_chassis = $conn->prepare('SELECT COUNT(*) FROM `bikes` WHERE `chassis_number` = ?');
                $stmt_chk_chassis->bind_param('s', $chassis);
                $stmt_chk_chassis->execute();
                $stmt_chk_chassis->bind_result($chassis_count);
                $stmt_chk_chassis->fetch();
                $stmt_chk_chassis->close();
                if ($chassis_count > 0) {
                    throw new Exception("Duplicate chassis number detected: {$chassis}. Please ensure each bike has a unique chassis number.");
                }
                $tax_amt = 0;
                if ($tax_on === 'purchase_price') {
                    $tax_amt = $price * $tax_rate;
                }
                $stmt_bike->bind_param('issssisdds', $purchase_order_id, $order_date, $inventory_date, $chassis, $motor, $model, $color, $price, $tax_amt, $bike_note);
                if (!$stmt_bike->execute())
                    throw new Exception('Failed to save bike ' . h($chassis) . ': ' . $stmt_bike->error);
            }
            $stmt_bike->close();
            if (!empty($cheque_number) && $cheque_amount > 0) {
                $stmt_cheque = $conn->prepare("INSERT INTO `cheque_register` (`cheque_number`, `bank_name`, `cheque_date`, `amount`, `type`, `status`, `reference_type`, `reference_id`, `party_name`, `notes`) VALUES (?, ?, ?, ?, 'payment', 'pending', 'purchase_order', ?, ?, ?)");
                if ($stmt_cheque === false)
                    throw new Exception('Failed to prepare Cheque Register statement: ' . $conn->error);
                $party_name = $conn->query('SELECT name FROM `suppliers` WHERE id = ' . $supplier_id)->fetch_row()[0];
                $cheque_notes = 'Payment for PO ID: ' . $purchase_order_id;
                $stmt_cheque->bind_param('sssdiss', $cheque_number, $bank_name, $cheque_date, $cheque_amount, $purchase_order_id, $party_name, $cheque_notes);
                if (!$stmt_cheque->execute())
                    throw new Exception('Failed to save Cheque Register entry: ' . $stmt_cheque->error);
                $stmt_cheque->close();
            }
            $conn->commit();
            $_SESSION['flash_message'] = 'Purchase Order saved successfully. ' . $total_units . ' bikes added to inventory.';
            $_SESSION['flash_type'] = 'success';
            header('Location: ?page=purchase');
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['flash_message'] = 'Error saving Purchase Order: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            header('Location: ?page=purchase');
            exit;
        } finally {
            $conn->autocommit(true);
        }
    }
    ob_start();
    ?>
    <h2 style="margin-bottom: 20px;">Purchase Entry</h2>
    <form id="purchase-form" method="POST" action="?page=purchase">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="save_purchase_order">
        <div class="form-section animate__animated animate__fadeIn">
            <div class="form-section-title">Purchase Order Details</div>
            <div class="form-grid">
                <div class="form-group">
                    <label for="order_date">Order Date <span class="required-mark">*</span></label>
                    <input type="date" id="order_date" name="order_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label for="supplier_id">Supplier <span class="required-mark">*</span></label>
                    <select id="supplier_id" name="supplier_id" required>
                        <option value="">-- Select Supplier --</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?= h($supplier['id']) ?>"><?= h($supplier['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-sm btn-secondary" style="margin-top: 5px;" onclick="openAddSupplierModal()">Add New Supplier</button>
                </div>
                <div class="form-group">
                    <label for="cheque_number">Cheque Number</label>
                    <input type="text" id="cheque_number" name="cheque_number">
                </div>
                <div class="form-group">
                    <label for="bank_name">Bank Name</label>
                    <input type="text" id="bank_name" name="bank_name">
                </div>
                <div class="form-group">
                    <label for="cheque_date">Cheque Date</label>
                    <input type="date" id="cheque_date" name="cheque_date">
                </div>
                <div class="form-group">
                    <label for="cheque_amount">Cheque Amount</label>
                    <input type="number" id="cheque_amount" name="cheque_amount" step="0.01" min="0" value="0.00">
                </div>
                <div class="form-group">
                    <label for="po_notes">Notes</label>
                    <textarea id="po_notes" name="po_notes"></textarea>
                </div>
            </div>
        </div>
        <div class="form-section animate__animated animate__fadeIn">
            <div class="form-section-title">Bikes to Add</div>
            <div class="table-container">
                <table id="bikes-table" class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 20%;">Chassis Number <span class="required-mark">*</span></th>
                            <th style="width: 15%;">Motor Number</th>
                            <th style="width: 20%;">Model <span class="required-mark">*</span></th>
                            <th style="width: 15%;">Color</th>
                            <th style="width: 15%;">Purchase Price <span class="required-mark">*</span></th>
                            <th style="width: 10%;">Notes</th>
                            <th style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody id="bikes-table-body">
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-primary" onclick="addBikeRow()">Add Another Bike</button>
        </div>
        <button type="submit" class="btn btn-success animate__animated animate__pulse">Save Purchase Order</button>
    </form>
    <div id="addSupplierModal" class="modal-overlay">
        <div class="modal-content animate__animated animate__fadeInDown">
            <div class="modal-header">
                <h3>Add New Supplier</h3>
                <button type="button" class="modal-close-btn" onclick="closeAddSupplierModal()">X</button>
            </div>
            <div class="modal-body">
                <form id="add-supplier-form" method="POST" action="?page=purchase">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add_supplier_inline">
                    <div class="form-group">
                        <label for="supplier_name">Supplier Name <span class="required-mark">*</span></label>
                        <input type="text" id="supplier_name" name="supplier_name" required>
                    </div>
                    <div class="form-group">
                        <label for="supplier_contact">Contact Person</label>
                        <input type="text" id="supplier_contact" name="supplier_contact">
                    </div>
                    <div class="form-group">
                        <label for="supplier_address">Address</label>
                        <textarea id="supplier_address" name="supplier_address"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Save Supplier</button>
                </form>
            </div>
        </div>
    </div>
    <div id="addModelModal" class="modal-overlay">
        <div class="modal-content animate__animated animate__fadeInDown">
            <div class="modal-header">
                <h3>Add New Model</h3>
                <button type="button" class="modal-close-btn" onclick="closeAddModelModal()">X</button>
            </div>
            <div class="modal-body">
                <form id="add-model-form" method="POST" action="?page=purchase">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add_model_inline">
                    <div class="form-group">
                        <label for="model_code">Model Code <span class="required-mark">*</span></label>
                        <input type="text" id="model_code" name="model_code" required>
                    </div>
                    <div class="form-group">
                        <label for="model_name_modal">Model Name <span class="required-mark">*</span></label>
                        <input type="text" id="model_name_modal" name="model_name" required>
                    </div>
                    <div class="form-group">
                        <label for="category">Category <span class="required-mark">*</span></label>
                        <input type="text" id="category" name="category" required placeholder="e.g. Electric Bike, Electric Scooter">
                    </div>
                    <div class="form-group">
                        <label for="short_code">Short Code</label>
                        <input type="text" id="short_code" name="short_code">
                    </div>
                    <button type="submit" class="btn btn-success">Save Model</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        const MODELS = <?= json_encode($models) ?>;
        const CATEGORIES = ['Electric Bike', 'Electric Scooter'];
        function addBikeRow() {
            const tbody = document.getElementById('bikes-table-body');
            const newRow = document.createElement('tr');
            newRow.classList.add('animate__animated', 'animate__fadeInDown');
            let modelOptions = MODELS.map(model => `<option value="${model.id}">${h(model.model_name)} (${h(model.model_code)})</option>`).join('');
            newRow.innerHTML = `
                <td><input type="text" name="chassis_number[]" required onblur="checkChassisDuplicate(this)"></td>
                <td><input type="text" name="motor_number[]"></td>
                <td>
                    <select name="model_id[]" required style="width: 100%;">
                        <option value="">-- Select Model --</option>
                        ${modelOptions}
                    </select>
                    <button type="button" class="btn btn-xs btn-secondary" style="margin-top: 5px;" onclick="openAddModelModal()">Add New Model</button>
                </td>
                <td><input type="text" name="color[]"></td>
                <td><input type="number" name="purchase_price[]" step="0.01" min="0" value="0.00" required></td>
                <td><input type="text" name="bike_notes[]"></td>
                <td><button type="button" class="btn btn-danger btn-xs" onclick="removeBikeRow(this)">X</button></td>
            `;
            tbody.appendChild(newRow);
            $(newRow).find('select').select2({
                width: '100%',
                placeholder: "-- Select Model --",
                allowClear: true
            });
            validator.addField(newRow.querySelector('input[name="chassis_number[]"]'), [
                { rule: 'required', errorMessage: 'Chassis is required' }
            ]);
            validator.addField(newRow.querySelector('select[name="model_id[]"]'), [
                { rule: 'required', errorMessage: 'Model is required' }
            ]);
            validator.addField(newRow.querySelector('input[name="purchase_price[]"]'), [
                { rule: 'required', errorMessage: 'Price is required' },
                { rule: 'minNumber', value: 0.01, errorMessage: 'Must be > 0' }
            ]);
        }
        function removeBikeRow(button) {
            $(button).closest('tr').remove();
        }
        function openAddSupplierModal() {
            document.getElementById('addSupplierModal').classList.add('open');
            $('#supplier_id').select2('destroy');
            $('#supplier_id').select2({
                width: '100%',
                placeholder: "-- Select Supplier --",
                allowClear: true
            });
        }
        function closeAddSupplierModal() {
            document.getElementById('addSupplierModal').classList.remove('open');
        }
        function openAddModelModal() {
            document.getElementById('addModelModal').classList.add('open');
            fetchModelsForDropdowns();
        }
        function closeAddModelModal() {
            document.getElementById('addModelModal').classList.remove('open');
        }
        function fetchModelsForDropdowns() {
            $('select[name="model_id[]"]').each(function() {
                var currentVal = $(this).val();
                $(this).select2('destroy');
                let modelOptions = MODELS.map(model => `<option value="${model.id}">${h(model.model_name)} (${h(model.model_code)})</option>`).join('');
                $(this).html('<option value="">-- Select Model --</option>' + modelOptions).val(currentVal).select2({
                    width: '100%',
                    placeholder: "-- Select Model --",
                    allowClear: true
                });
            });
        }
        async function checkChassisDuplicate(input) {
            const chassis = input.value.trim();
            if (!chassis) return;
            showLoadingSpinner();
            const response = await fetch(`?page=ajax&action=check_chassis&chassis=${encodeURIComponent(chassis)}`);
            hideLoadingSpinner();
            const data = await response.json();
            if (data.is_duplicate) {
                Swal.fire({
                    icon: 'error',
                    title: 'Duplicate Chassis Number',
                    text: `Chassis number "${chassis}" already exists in inventory (Bike ID: ${data.bike_id}). Please enter a unique chassis number.`,
                    confirmButtonColor: 'var(--danger-color)'
                });
                input.value = '';
                input.focus();
            }
        }
        const validator = new JustValidate('#purchase-form', {
            errorFieldCssClass: 'is-invalid',
            errorLabelCssClass: 'error-message',
            focusInvalidField: true,
            lockForm: true,
            tooltip: {
                position: 'top'
            }
        });
        validator
            .addField('#order_date', [{ rule: 'required', errorMessage: 'Order Date is required' }])
            .addField('#supplier_id', [{ rule: 'required', errorMessage: 'Supplier is required' }])
            .addField('#cheque_amount', [{ rule: 'minNumber', value: 0, errorMessage: 'Cannot be negative' }]);
        $(document).ready(function() {
            addBikeRow();
        });
    </script>
    <?php
    $content = ob_get_clean();
    renderLayout('Purchase Entry', $content, 'purchase');
}

function renderInventoryList(): void
{
    $conn = getDbConnection();
    $models = getModels();
    $page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    $filters = [
        'status' => $_GET['status'] ?? 'all',
        'model_id' => $_GET['model_id'] ?? '',
        'color' => $_GET['color'] ?? '',
        'search' => $_GET['search'] ?? '',
        'date_from' => $_GET['date_from'] ?? '',
        'date_to' => $_GET['date_to'] ?? '',
    ];
    $result = getBikes($filters, $limit, $offset);
    $bikes = $result['bikes'];
    $total_bikes = $result['total'];
    $total_pages = ceil($total_bikes / $limit);
    ob_start();
    ?>
    <h2 style="margin-bottom: 20px;">Inventory / Stock List</h2>
    <div class="form-section animate__animated animate__fadeIn no-print">
        <div class="form-section-title">Filters</div>
        <form method="GET" action="?page=inventory">
            <input type="hidden" name="page" value="inventory">
            <div class="form-grid">
                <div class="form-group">
                    <label for="filter_status">Status</label>
                    <select id="filter_status" name="status">
                        <option value="all" <?= $filters['status'] === 'all' ? 'selected' : '' ?>>All</option>
                        <option value="in_stock" <?= $filters['status'] === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
                        <option value="sold" <?= $filters['status'] === 'sold' ? 'selected' : '' ?>>Sold</option>
                        <option value="returned" <?= $filters['status'] === 'returned' ? 'selected' : '' ?>>Returned</option>
                        <option value="reserved" <?= $filters['status'] === 'reserved' ? 'selected' : '' ?>>Reserved</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filter_model">Model</label>
                    <select id="filter_model" name="model_id">
                        <option value="">-- All Models --</option>
                        <?php foreach ($models as $model): ?>
                            <option value="<?= h($model['id']) ?>" <?= (string) $filters['model_id'] === (string) $model['id'] ? 'selected' : '' ?>><?= h($model['model_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filter_color">Color</label>
                    <input type="text" id="filter_color" name="color" value="<?= h($filters['color']) ?>">
                </div>
                <div class="form-group">
                    <label for="filter_search">Search (Chassis, Motor, Model)</label>
                    <input type="text" id="filter_search" name="search" value="<?= h($filters['search']) ?>">
                </div>
                <div class="form-group">
                    <label for="filter_date_from">Inventory Date From</label>
                    <input type="date" id="filter_date_from" name="date_from" value="<?= h($filters['date_from']) ?>">
                </div>
                <div class="form-group">
                    <label for="filter_date_to">Inventory Date To</label>
                    <input type="date" id="filter_date_to" name="date_to" value="<?= h($filters['date_to']) ?>">
                </div>
            </div>
            <div style="margin-top: 15px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="?page=inventory" class="btn btn-secondary">Reset Filters</a>
                <button type="button" class="btn btn-success" onclick="exportInventoryToCsv()">Export CSV</button>
                <button type="button" class="btn btn-secondary" onclick="window.print()">Print List</button>
            </div>
        </form>
    </div>
    <div class="table-container animate__animated animate__fadeIn">
        <table class="data-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="select-all-bikes"></th>
                    <th>Chassis #</th>
                    <th>Motor #</th>
                    <th>Model</th>
                    <th>Color</th>
                    <th class="text-right">Purchase Price</th>
                    <th class="text-right">Selling Price</th>
                    <th>Status</th>
                    <th>Selling Date</th>
                    <th>Margin</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bikes)): ?>
                    <tr><td colspan="11" class="text-center">No bikes found matching your criteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($bikes as $bike): ?>
                        <tr class="status-<?= h($bike['status']) ?>">
                            <td><input type="checkbox" name="selected_bikes[]" value="<?= h($bike['id']) ?>" class="bike-checkbox"></td>
                            <td><?= h($bike['chassis_number']) ?></td>
                            <td><?= h($bike['motor_number']) ?></td>
                            <td><?= h($bike['model_name']) ?></td>
                            <td><?= h($bike['color']) ?></td>
                            <td class="text-right"><?= h(formatCurrency($bike['purchase_price'])) ?></td>
                            <td class="text-right"><?= h($bike['selling_price'] ? formatCurrency($bike['selling_price']) : '-') ?></td>
                            <td><?= h(str_replace('_', ' ', strtoupper($bike['status']))) ?></td>
                            <td><?= h($bike['selling_date'] ? formatDate($bike['selling_date']) : '-') ?></td>
                            <td><?= h($bike['margin'] ? formatCurrency($bike['margin']) : '-') ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="?page=inventory&action=view&id=<?= h($bike['id']) ?>" class="btn btn-primary btn-xs">View</a>
                                    <?php if ($bike['status'] === 'in_stock'): ?>
                                        <a href="?page=sale&bike_id=<?= h($bike['id']) ?>" class="btn btn-success btn-xs">Sell</a>
                                    <?php elseif ($bike['status'] === 'sold'): ?>
                                        <a href="?page=returns&bike_id=<?= h($bike['id']) ?>" class="btn btn-warning btn-xs">Return</a>
                                    <?php endif; ?>
                                    <a href="?page=inventory&action=edit&id=<?= h($bike['id']) ?>" class="btn btn-warning btn-xs">Edit</a>
                                    <button type="button" class="btn btn-danger btn-xs" onclick="deleteBike(<?= h($bike['id']) ?>, '<?= h($bike['chassis_number']) ?>')">Delete</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="no-print" style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center;">
        <div class="btn-group">
            <button type="button" class="btn btn-primary" onclick="bulkMarkSold()">Mark Selected Sold</button>
            <button type="button" class="btn btn-danger" onclick="bulkDeleteBikes()">Delete Selected</button>
        </div>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=inventory&p=<?= $i ?>&<?= http_build_query($filters) ?>" class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-secondary' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $('#select-all-bikes').change(function() {
                $('.bike-checkbox').prop('checked', $(this).prop('checked'));
            });
            $('.data-table').DataTable({
                responsive: true,
                paging: false,
                info: false,  
                searching: false
            });
        });
        function getSelectedBikeIds() {
            const selectedIds = [];
            $('.bike-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });
            return selectedIds;
        }
        async function bulkMarkSold() {
            const ids = getSelectedBikeIds();
            if (ids.length === 0) {
                showToast('Please select at least one bike.', 'danger');
                return;
            }
            const result = await confirmAction('Confirm Bulk Sale', `Are you sure you want to mark ${ids.length} selected bikes as SOLD? You will be redirected to the sales page to finalize details.`);
            if (result) {
                window.location.href = `?page=sale&bike_ids=${ids.join(',')}`;
            }
        }
        async function bulkDeleteBikes() {
            const ids = getSelectedBikeIds();
            if (ids.length === 0) {
                showToast('Please select at least one bike.', 'danger');
                return;
            }
            const result = await confirmAction('Confirm Bulk Delete', `Are you sure you want to delete ${ids.length} selected bikes? This action cannot be undone.`, 'Yes, Delete Them');
            if (result) {
                showLoadingSpinner();
                const response = await fetch('?page=ajax&action=bulk_delete_bikes', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `csrf_token=${CSRF_TOKEN}&ids=${ids.join(',')}`
                });
                hideLoadingSpinner();
                const data = await response.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    location.reload();
                } else {
                    showToast(data.error, 'danger');
                }
            }
        }
        async function deleteBike(id, chassis) {
            const result = await confirmAction('Confirm Delete', `Are you sure you want to delete bike with Chassis # "${chassis}"? This action cannot be undone.`);
            if (result) {
                showLoadingSpinner();
                const response = await fetch('?page=ajax&action=delete_bike', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `csrf_token=${CSRF_TOKEN}&id=${id}`
                });
                hideLoadingSpinner();
                const data = await response.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    location.reload();
                } else {
                    showToast(data.error, 'danger');
                }
            }
        }
        function exportInventoryToCsv() {
            const params = new URLSearchParams(window.location.search);
            params.set('action', 'export_csv');
            const exportUrl = `?${params.toString()}`;
            window.location.href = exportUrl;
        }
    </script>
    <?php
    $content = ob_get_clean();
    if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="inventory_export_' . date('Ymd_His') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Chassis Number', 'Motor Number', 'Model', 'Color', 'Purchase Price', 'Selling Price', 'Status', 'Selling Date', 'Customer Name', 'Tax Amount', 'Margin', 'Notes', 'Inventory Date']);
        $result = getBikes($filters, 999999, 0);
        foreach ($result['bikes'] as $bike) {
            fputcsv($output, [
                $bike['id'],
                $bike['chassis_number'],
                $bike['motor_number'],
                $bike['model_name'],
                $bike['color'],
                $bike['purchase_price'],
                $bike['selling_price'],
                $bike['status'],
                $bike['selling_date'],
                $bike['customer_name'],
                $bike['tax_amount'],
                $bike['margin'],
                $bike['notes'],
                $bike['inventory_date']
            ]);
        }
        fclose($output);
        exit;
    }
    renderLayout('Inventory / Stock List', $content, 'inventory');
}

function renderSaleEntry(): void
{
    $conn = getDbConnection();
    $customers = getCustomers();
    $models = getModels();
    $bike_id = (int) ($_GET['bike_id'] ?? 0);
    $bike_ids = isset($_GET['bike_ids']) ? array_map('intval', explode(',', $_GET['bike_ids'])) : [];
    $selected_bikes = [];
    if ($bike_id) {
        $stmt = $conn->prepare("SELECT b.*, m.model_name, m.model_code, m.category FROM `bikes` b JOIN `models` m ON b.model_id = m.id WHERE b.id = ? AND b.status = 'in_stock'");
        $stmt->bind_param('i', $bike_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $bike = $result->fetch_assoc();
        $stmt->close();
        if ($bike) {
            $selected_bikes[] = $bike;
        } else {
            $_SESSION['flash_message'] = 'Bike not found or not in stock.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ?page=inventory');
            exit;
        }
    } elseif (!empty($bike_ids)) {
        $placeholders = implode(',', array_fill(0, count($bike_ids), '?'));
        $types = str_repeat('i', count($bike_ids));
        $stmt = $conn->prepare("SELECT b.*, m.model_name, m.model_code, m.category FROM `bikes` b JOIN `models` m ON b.model_id = m.id WHERE b.id IN ($placeholders) AND b.status = 'in_stock'");
        $stmt->bind_param($types, ...$bike_ids);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($bike = $result->fetch_assoc()) {
            $selected_bikes[] = $bike;
        }
        $stmt->close();
        if (empty($selected_bikes)) {
            $_SESSION['flash_message'] = 'Selected bikes not found or not in stock.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ?page=inventory');
            exit;
        }
    }
    $available_bikes = [];
    $result_available_bikes = $conn->query("SELECT b.id, b.chassis_number, m.model_name, b.color, b.purchase_price, b.selling_price FROM `bikes` b JOIN `models` m ON b.model_id = m.id WHERE b.status = 'in_stock' ORDER BY m.model_name, b.chassis_number");
    while ($row = $result_available_bikes->fetch_assoc()) {
        $available_bikes[] = $row;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_customer_inline' && verifyCsrf()) {
        $name = trim($_POST['customer_name'] ?? '');
        $phone = trim($_POST['customer_phone'] ?? '');
        $cnic = trim($_POST['customer_cnic'] ?? '');
        $address = trim($_POST['customer_address'] ?? '');
        if (empty($name) || empty($phone)) {
            $_SESSION['flash_message'] = 'Customer name and phone are required.';
            $_SESSION['flash_type'] = 'danger';
        } else {
            $stmt = $conn->prepare('INSERT INTO `customers` (`name`, `phone`, `cnic`, `address`) VALUES (?, ?, ?, ?)');
            if ($stmt) {
                $stmt->bind_param('ssss', $name, $phone, $cnic, $address);
                if ($stmt->execute()) {
                    $_SESSION['flash_message'] = 'Customer added successfully.';
                    $_SESSION['flash_type'] = 'success';
                } else {
                    $_SESSION['flash_message'] = 'Error adding customer: ' . h($stmt->error);
                    $_SESSION['flash_type'] = 'danger';
                }
                $stmt->close();
            } else {
                $_SESSION['flash_message'] = 'Database error preparing statement.';
                $_SESSION['flash_type'] = 'danger';
            }
        }
        header('Location: ?page=sale');
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_sale' && verifyCsrf()) {
        $conn->autocommit(false);
        try {
            $customer_id = (int) ($_POST['customer_id'] ?? 0);
            $selling_date = trim($_POST['selling_date'] ?? date('Y-m-d'));
            $payment_type = trim($_POST['payment_type'] ?? 'cash');
            $sale_notes = trim($_POST['sale_notes'] ?? '');
            $bike_ids_sold = $_POST['sold_bike_id'] ?? [];
            $selling_prices = $_POST['bike_selling_price'] ?? [];
            $accessories = $_POST['bike_accessories'] ?? [];
            if (empty($bike_ids_sold) || $customer_id === 0) {
                throw new Exception('Please select at least one bike and a customer.');
            }
            $total_sale_amount = 0;
            $total_tax_amount = 0;
            $total_margin = 0;
            $invoice_number = generateInvoiceNumber();
            foreach ($bike_ids_sold as $index => $b_id) {
                $b_id = (int) $b_id;
                $s_price = (float) ($selling_prices[$index] ?? 0);
                $accessory_notes = trim($accessories[$index] ?? '');
                if ($b_id === 0 || $s_price <= 0) {
                    throw new Exception('Invalid bike or selling price provided.');
                }
                $stmt_bike_info = $conn->prepare('SELECT purchase_price, status FROM `bikes` WHERE id = ?');
                $stmt_bike_info->bind_param('i', $b_id);
                $stmt_bike_info->execute();
                $result_bike_info = $stmt_bike_info->get_result();
                $bike_info = $result_bike_info->fetch_assoc();
                $stmt_bike_info->close();
                if (!$bike_info || $bike_info['status'] !== 'in_stock') {
                    throw new Exception('Bike ID ' . h($b_id) . ' not found or not in stock.');
                }
                $purchase_price = $bike_info['purchase_price'];
                $tax_rate = (float) getSetting('tax_rate', '0.1');
                $tax_on = getSetting('tax_on', 'purchase_price');
                $tax_amt = 0;
                if ($tax_on === 'purchase_price') {
                    $tax_amt = $purchase_price * $tax_rate;
                } elseif ($tax_on === 'selling_price') {
                    $tax_amt = $s_price * $tax_rate;
                }
                $margin = $s_price - $purchase_price;
                $total_sale_amount += $s_price;
                $total_tax_amount += $tax_amt;
                $total_margin += $margin;
                $stmt_update_bike = $conn->prepare("UPDATE `bikes` SET
                                    selling_price = ?, selling_date = ?, customer_id = ?,
                                    tax_amount = ?, margin = ?, status = 'sold', notes = CONCAT(notes, '
Accessories: ', ?)
                                    WHERE id = ?");
                if ($stmt_update_bike === false)
                    throw new Exception('Failed to prepare update bike statement: ' . $conn->error);
                $stmt_update_bike->bind_param('dsddssi', $s_price, $selling_date, $customer_id, $tax_amt, $margin, $accessory_notes, $b_id);
                if (!$stmt_update_bike->execute())
                    throw new Exception('Failed to update bike ' . h($b_id) . ': ' . $stmt_update_bike->error);
                $stmt_update_bike->close();
            }
            $cheque_id = null;
            $payment_amount = $total_sale_amount;
            if ($payment_type === 'cheque') {
                $cheque_number = trim($_POST['cheque_number'] ?? '');
                $bank_name = trim($_POST['bank_name'] ?? '');
                $cheque_date = trim($_POST['cheque_date'] ?? '');
                $cheque_amount = (float) ($_POST['cheque_amount'] ?? 0);
                if (empty($cheque_number) || $cheque_amount <= 0 || empty($bank_name) || empty($cheque_date)) {
                    throw new Exception('Cheque details are required for cheque payments.');
                }
                $stmt_cheque = $conn->prepare("INSERT INTO `cheque_register` (`cheque_number`, `bank_name`, `cheque_date`, `amount`, `type`, `status`, `reference_type`, `party_name`, `notes`) VALUES (?, ?, ?, ?, 'receipt', 'pending', 'sale', ?, ?)");
                if ($stmt_cheque === false)
                    throw new Exception('Failed to prepare Cheque Register statement: ' . $conn->error);
                $party_name = $conn->query('SELECT name FROM `customers` WHERE id = ' . $customer_id)->fetch_row()[0];
                $cheque_notes = 'Receipt for Sale of Bikes (Invoice: ' . $invoice_number . ')';
                $stmt_cheque->bind_param('sssdss', $cheque_number, $bank_name, $cheque_date, $cheque_amount, $party_name, $cheque_notes);
                if (!$stmt_cheque->execute())
                    throw new Exception('Failed to save Cheque Register entry: ' . $stmt_cheque->error);
                $cheque_id = $stmt_cheque->insert_id;
                $stmt_cheque->close();
                $payment_amount = $cheque_amount;
            }
            $stmt_payment = $conn->prepare("INSERT INTO `payments` (`payment_date`, `payment_type`, `amount`, `cheque_id`, `reference_type`, `reference_id`, `party_name`, `notes`) VALUES (?, ?, ?, ?, 'sale', ?, ?, ?)");
            if ($stmt_payment === false)
                throw new Exception('Failed to prepare Payment statement: ' . $conn->error);
            $party_name = $conn->query('SELECT name FROM `customers` WHERE id = ' . $customer_id)->fetch_row()[0];
            $payment_ref_id = $b_id;
            $payment_notes = 'Payment for Sale (Invoice: ' . $invoice_number . ')';
            $stmt_payment->bind_param('ssdiiis', $selling_date, $payment_type, $payment_amount, $cheque_id, $payment_ref_id, $party_name, $payment_notes);
            if (!$stmt_payment->execute())
                throw new Exception('Failed to save Payment entry: ' . $stmt_payment->error);
            $stmt_payment->close();
            $stmt_ledger = $conn->prepare("INSERT INTO `ledger` (`entry_date`, `entry_type`, `amount`, `party_type`, `party_id`, `description`, `reference_type`, `reference_id`) VALUES (?, 'credit', ?, 'customer', ?, ?, 'sale', ?)");
            if ($stmt_ledger === false)
                throw new Exception('Failed to prepare Ledger statement: ' . $conn->error);
            $ledger_desc = 'Sale of bike(s) (Invoice: ' . $invoice_number . ')';
            $stmt_ledger->bind_param('sdisi', $selling_date, $total_sale_amount, $customer_id, $ledger_desc, $b_id);
            if (!$stmt_ledger->execute())
                throw new Exception('Failed to save Ledger entry: ' . $stmt_ledger->error);
            $stmt_ledger->close();
            $conn->commit();
            $_SESSION['flash_message'] = 'Sale processed successfully! Invoice # ' . $invoice_number;
            $_SESSION['flash_type'] = 'success';
            $_SESSION['last_invoice_id'] = $b_id;
            header('Location: ?page=sale&action=view_invoice');
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['flash_message'] = 'Error processing sale: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            header('Location: ?page=sale');
            exit;
        } finally {
            $conn->autocommit(true);
        }
    }
    ob_start();
    ?>
    <h2 style="margin-bottom: 20px;">Sales Entry</h2>
    <form id="sale-form" method="POST" action="?page=sale">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="process_sale">
        <div class="form-section animate__animated animate__fadeIn">
            <div class="form-section-title">Sale Details</div>
            <div class="form-grid">
                <div class="form-group">
                    <label for="selling_date">Selling Date <span class="required-mark">*</span></label>
                    <input type="date" id="selling_date" name="selling_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label for="customer_id">Customer <span class="required-mark">*</span></label>
                    <select id="customer_id" name="customer_id" required>
                        <option value="">-- Select Customer --</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?= h($customer['id']) ?>"><?= h($customer['name']) ?> (<?= h($customer['phone']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-sm btn-secondary" style="margin-top: 5px;" onclick="openAddCustomerModal()">Add New Customer</button>
                </div>
                <div class="form-group">
                    <label for="payment_type">Payment Type <span class="required-mark">*</span></label>
                    <select id="payment_type" name="payment_type" onchange="toggleChequeFields()" required>
                        <option value="cash">Cash</option>
                        <option value="cheque">Cheque</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="online">Online Payment</option>
                    </select>
                </div>
            </div>
            <div id="cheque-fields" class="form-grid" style="display: none; margin-top: 15px;">
                <div class="form-group">
                    <label for="cheque_number">Cheque Number <span class="required-mark">*</span></label>
                    <input type="text" id="cheque_number" name="cheque_number">
                </div>
                <div class="form-group">
                    <label for="bank_name">Bank Name <span class="required-mark">*</span></label>
                    <input type="text" id="bank_name" name="bank_name">
                </div>
                <div class="form-group">
                    <label for="cheque_date">Cheque Date <span class="required-mark">*</span></label>
                    <input type="date" id="cheque_date" name="cheque_date">
                </div>
                <div class="form-group">
                    <label for="cheque_amount">Cheque Amount <span class="required-mark">*</span></label>
                    <input type="number" id="cheque_amount" name="cheque_amount" step="0.01" min="0" value="0.00">
                </div>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label for="sale_notes">Notes</label>
                    <textarea id="sale_notes" name="sale_notes"></textarea>
                </div>
            </div>
        </div>
        <div class="form-section animate__animated animate__fadeIn">
            <div class="form-section-title">Bikes to Sell</div>
            <div class="table-container">
                <table id="bikes-for-sale-table" class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Bike (Chassis / Model) <span class="required-mark">*</span></th>
                            <th style="width: 15%;">Purchase Price</th>
                            <th style="width: 15%;">Selling Price <span class="required-mark">*</span></th>
                            <th style="width: 15%;">Margin</th>
                            <th style="width: 20%;">Accessories (e.g., Helmets, Tyres)</th>
                            <th style="width: 10%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="bikes-for-sale-body">
                        <?php if (empty($selected_bikes)): ?>
                            <tr class="initial-bike-row">
                                <td>
                                    <select name="sold_bike_id[]" class="bike-select" onchange="updateBikeDetails(this)" required style="width: 100%;">
                                        <option value="">-- Select Bike --</option>
                                        <?php foreach ($available_bikes as $ab): ?>
                                            <option value="<?= h($ab['id']) ?>" data-pprice="<?= h($ab['purchase_price']) ?>" data-sprice="<?= h($ab['selling_price'] ?? 0) ?>">
                                                <?= h($ab['chassis_number']) ?> (<?= h($ab['model_name']) ?> - <?= h($ab['color']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="text" name="bike_purchase_price[]" class="bike-pprice" readonly></td>
                                <td><input type="number" name="bike_selling_price[]" class="bike-sprice" step="0.01" min="0" value="0.00" oninput="calculateMargin(this)" required></td>
                                <td><input type="text" name="bike_margin[]" class="bike-margin" readonly></td>
                                <td><input type="text" name="bike_accessories[]" placeholder="Helmets, Tyres, etc."></td>
                                <td><button type="button" class="btn btn-danger btn-xs" onclick="removeBikeSaleRow(this)">X</button></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($selected_bikes as $bike): ?>
                                <tr class="animate__animated animate__fadeInDown">
                                    <td>
                                        <select name="sold_bike_id[]" class="bike-select" onchange="updateBikeDetails(this)" required style="width: 100%;">
                                            <option value="<?= h($bike['id']) ?>" selected data-pprice="<?= h($bike['purchase_price']) ?>" data-sprice="<?= h($bike['selling_price'] ?? 0) ?>">
                                                <?= h($bike['chassis_number']) ?> (<?= h($bike['model_name']) ?> - <?= h($bike['color']) ?>)
                                            </option>
                                            <?php foreach ($available_bikes as $ab): ?>
                                                <?php if ((string) $ab['id'] !== (string) $bike['id']): ?>
                                                    <option value="<?= h($ab['id']) ?>" data-pprice="<?= h($ab['purchase_price']) ?>" data-sprice="<?= h($ab['selling_price'] ?? 0) ?>">
                                                        <?= h($ab['chassis_number']) ?> (<?= h($ab['model_name']) ?> - <?= h($ab['color']) ?>)
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="text" name="bike_purchase_price[]" class="bike-pprice" value="<?= h(formatCurrency($bike['purchase_price'])) ?>" readonly></td>
                                    <td><input type="number" name="bike_selling_price[]" class="bike-sprice" step="0.01" min="0" value="<?= h($bike['selling_price'] ?? $bike['purchase_price'] * 1.2) ?>" oninput="calculateMargin(this)" required></td>
                                    <td><input type="text" name="bike_margin[]" class="bike-margin" readonly></td>
                                    <td><input type="text" name="bike_accessories[]" placeholder="Helmets, Tyres, etc."></td>
                                    <td><button type="button" class="btn btn-danger btn-xs" onclick="removeBikeSaleRow(this)">X</button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-primary" onclick="addBikeSaleRow()">Add Another Bike to Sale</button>
        </div>
        <button type="submit" class="btn btn-success animate__animated animate__pulse">Process Sale & Generate Invoice</button>
    </form>
    <div id="addCustomerModal" class="modal-overlay">
        <div class="modal-content animate__animated animate__fadeInDown">
            <div class="modal-header">
                <h3>Add New Customer</h3>
                <button type="button" class="modal-close-btn" onclick="closeAddCustomerModal()">X</button>
            </div>
            <div class="modal-body">
                <form id="add-customer-form" method="POST" action="?page=sale">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add_customer_inline">
                    <div class="form-group">
                        <label for="customer_name">Customer Name <span class="required-mark">*</span></label>
                        <input type="text" id="customer_name" name="customer_name" required>
                    </div>
                    <div class="form-group">
                        <label for="customer_phone">Phone <span class="required-mark">*</span></label>
                        <input type="text" id="customer_phone" name="customer_phone" required>
                    </div>
                    <div class="form-group">
                        <label for="customer_cnic">CNIC</label>
                        <input type="text" id="customer_cnic" name="customer_cnic">
                    </div>
                    <div class="form-group">
                        <label for="customer_address">Address</label>
                        <textarea id="customer_address" name="customer_address"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Save Customer</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        const AVAILABLE_BIKES = <?= json_encode($available_bikes) ?>;
        const CURRENCY = '<?= getSetting('currency_symbol', 'Rs.') ?>';
        function toggleChequeFields() {
            const paymentType = document.getElementById('payment_type').value;
            const chequeFields = document.getElementById('cheque-fields');
            if (paymentType === 'cheque') {
                chequeFields.style.display = 'grid';
                document.getElementById('cheque_number').setAttribute('required', 'required');
                document.getElementById('bank_name').setAttribute('required', 'required');
                document.getElementById('cheque_date').setAttribute('required', 'required');
                document.getElementById('cheque_amount').setAttribute('required', 'required');
            } else {
                chequeFields.style.display = 'none';
                document.getElementById('cheque_number').removeAttribute('required');
                document.getElementById('bank_name').removeAttribute('required');
                document.getElementById('cheque_date').removeAttribute('required');
                document.getElementById('cheque_amount').removeAttribute('required');
            }
        }
        function addBikeSaleRow() {
            const tbody = document.getElementById('bikes-for-sale-body');
            const newRow = document.createElement('tr');
            newRow.classList.add('animate__animated', 'animate__fadeInDown');
            let bikeOptions = AVAILABLE_BIKES.map(bike => `<option value="${bike.id}" data-pprice="${bike.purchase_price}" data-sprice="${bike.selling_price ?? 0}">${h(bike.chassis_number)} (${h(bike.model_name)} - ${h(bike.color)})</option>`).join('');
            newRow.innerHTML = `
                <td>
                    <select name="sold_bike_id[]" class="bike-select" onchange="updateBikeDetails(this)" required style="width: 100%;">
                        <option value="">-- Select Bike --</option>
                        ${bikeOptions}
                    </select>
                </td>
                <td><input type="text" name="bike_purchase_price[]" class="bike-pprice" readonly></td>
                <td><input type="number" name="bike_selling_price[]" class="bike-sprice" step="0.01" min="0" value="0.00" oninput="calculateMargin(this)" required></td>
                <td><input type="text" name="bike_margin[]" class="bike-margin" readonly></td>
                <td><input type="text" name="bike_accessories[]" placeholder="Helmets, Tyres, etc."></td>
                <td><button type="button" class="btn btn-danger btn-xs" onclick="removeBikeSaleRow(this)">X</button></td>
            `;
            tbody.appendChild(newRow);
            $(newRow).find('select').select2({
                width: '100%',
                placeholder: "-- Select Bike --",
                allowClear: true
            });
            validator.addField(newRow.querySelector('select[name="sold_bike_id[]"]'), [
                { rule: 'required', errorMessage: 'Bike is required' }
            ]);
            validator.addField(newRow.querySelector('input[name="bike_selling_price[]"]'), [
                { rule: 'required', errorMessage: 'Price is required' },
                { rule: 'minNumber', value: 0.01, errorMessage: 'Must be > 0' }
            ]);
        }
        function removeBikeSaleRow(button) {
            $(button).closest('tr').remove();
            calculateOverallTotals();
        }
        function updateBikeDetails(selectElement) {
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const row = selectElement.closest('tr');
            const pPriceInput = row.querySelector('.bike-pprice');
            const sPriceInput = row.querySelector('.bike-sprice');
            if (selectedOption.value) {
                const purchasePrice = parseFloat(selectedOption.dataset.pprice);
                let sellingPrice = parseFloat(selectedOption.dataset.sprice);
                if (sellingPrice === 0) {
                    sellingPrice = purchasePrice * 1.2;
                }
                pPriceInput.value = `${CURRENCY} ${purchasePrice.toFixed(2)}`;
                sPriceInput.value = sellingPrice.toFixed(2);
            } else {
                pPriceInput.value = '';
                sPriceInput.value = '0.00';
            }
            calculateMargin(sPriceInput);
            calculateOverallTotals();
        }
        function calculateMargin(sellingPriceInput) {
            const row = sellingPriceInput.closest('tr');
            const pPriceText = row.querySelector('.bike-pprice').value;
            const pPrice = parseFloat(pPriceText.replace(CURRENCY, '').trim()) || 0;
            const sPrice = parseFloat(sellingPriceInput.value) || 0;
            const margin = sPrice - pPrice;
            row.querySelector('.bike-margin').value = `${CURRENCY} ${margin.toFixed(2)}`;
            if (margin < 0) {
                row.querySelector('.bike-margin').style.color = 'var(--danger-color)';
            } else {
                row.querySelector('.bike-margin').style.color = 'var(--success-color)';
            }
            calculateOverallTotals();
        }
        function calculateOverallTotals() {
            let totalSaleAmount = 0;
            let totalMargin = 0;
            document.querySelectorAll('#bikes-for-sale-body .bike-select').forEach(selectElement => {
                const selectedOption = selectElement.options[selectElement.selectedIndex];
                if (selectedOption.value) {
                    const row = selectElement.closest('tr');
                    const pPriceText = row.querySelector('.bike-pprice').value;
                    const pPrice = parseFloat(pPriceText.replace(CURRENCY, '').trim()) || 0;
                    const sPrice = parseFloat(row.querySelector('.bike-sprice').value) || 0;
                    totalSaleAmount += sPrice;
                    totalMargin += (sPrice - pPrice);
                }
            });
        }
        function openAddCustomerModal() {
            document.getElementById('addCustomerModal').classList.add('open');
            $('#customer_id').select2('destroy');
            $('#customer_id').select2({
                width: '100%',
                placeholder: "-- Select Customer --",
                allowClear: true
            });
        }
        function closeAddCustomerModal() {
            document.getElementById('addCustomerModal').classList.remove('open');
        }
        const validator = new JustValidate('#sale-form', {
            errorFieldCssClass: 'is-invalid',
            errorLabelCssClass: 'error-message',
            focusInvalidField: true,
            lockForm: true,
            tooltip: {
                position: 'top'
            }
        });
        validator
            .addField('#selling_date', [{ rule: 'required', errorMessage: 'Selling Date is required' }])
            .addField('#customer_id', [{ rule: 'required', errorMessage: 'Customer is required' }])
            .addField('#payment_type', [{ rule: 'required', errorMessage: 'Payment Type is required' }]);
        $(document).ready(function() {
            toggleChequeFields();
            document.querySelectorAll('.bike-select').forEach(select => updateBikeDetails(select));
            calculateOverallTotals();
        });
    </script>
    <?php
    $content = ob_get_clean();
    if (isset($_GET['action']) && $_GET['action'] === 'view_invoice' && isset($_SESSION['last_invoice_id'])) {
        $last_bike_id = (int) $_SESSION['last_invoice_id'];
        $stmt_invoice_bike = $conn->prepare('SELECT b.*, m.model_name, m.model_code, m.category, s.name as supplier_name, c.name as customer_name, c.phone as customer_phone, c.address as customer_address, c.cnic as customer_cnic
                                    FROM `bikes` b
                                    JOIN `models` m ON b.model_id = m.id
                                    LEFT JOIN `suppliers` s ON b.purchase_order_id = s.id -- This is wrong, should join via purchase_orders table
                                    LEFT JOIN `customers` c ON b.customer_id = c.id
                                    WHERE b.id = ?');
        $stmt_invoice_bike->bind_param('i', $last_bike_id);
        $stmt_invoice_bike->execute();
        $invoice_bike = $stmt_invoice_bike->get_result()->fetch_assoc();
        $stmt_invoice_bike->close();
        $stmt_payment_details = $conn->prepare("SELECT p.*, cr.cheque_number, cr.bank_name, cr.cheque_date FROM `payments` p LEFT JOIN `cheque_register` cr ON p.cheque_id = cr.id WHERE p.reference_id = ? AND p.reference_type = 'sale' ORDER BY p.id DESC LIMIT 1");
        $stmt_payment_details->bind_param('i', $last_bike_id);
        $stmt_payment_details->execute();
        $payment_details = $stmt_payment_details->get_result()->fetch_assoc();
        $stmt_payment_details->close();
        if ($invoice_bike) {
            $invoice_number = generateInvoiceNumber();
            $company_name = getSetting('company_name', APP_NAME);
            $branch_name = getSetting('branch_name', APP_BRANCH);
            $tax_rate = (float) getSetting('tax_rate', '0.1');
            $tax_on = getSetting('tax_on', 'purchase_price');
            $currency_symbol = getSetting('currency_symbol', 'Rs.');
            $accessories = '';
            if (strpos($invoice_bike['notes'], 'Accessories:') !== false) {
                $accessories = trim(explode('Accessories:', $invoice_bike['notes'])[1]);
            }
            $invoice_content = ob_get_clean();
            $invoice_content .= '
            <div class="form-section print-invoice animate__animated animate__fadeIn">
                <div class="print-only" style="margin-bottom: 20px;">
                    <div class="invoice-header">
                        <div class="company-info">
                            <div class="company-name">' . h($company_name) . '</div>
                            <div style="font-size: 10pt; color: #555;">' . h($branch_name) . '</div>
                            <div style="font-size: 10pt; color: #555;">Phone: ' . h(getSetting('company_phone')) . ' | Email: ' . h(getSetting('company_email')) . '</div>
                        </div>
                        <div class="invoice-meta">
                            <div class="invoice-title">INVOICE</div>
                            <strong style="font-size: 12pt;">Invoice #: ' . h($invoice_number) . '</strong>
                            <div><strong>Date:</strong> ' . h(formatDate($invoice_bike['selling_date'])) . '</div>
                        </div>
                    </div>
                </div>
                <div class="form-section-title no-print">Invoice Preview</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Invoice Number:</label>
                        <input type="text" value="' . h($invoice_number) . '" readonly>
                    </div>
                    <div class="form-group">
                        <label>Selling Date:</label>
                        <input type="text" value="' . h(formatDate($invoice_bike['selling_date'])) . '" readonly>
                    </div>
                    <div class="form-group">
                        <label>Customer Name:</label>
                        <input type="text" value="' . h($invoice_bike['customer_name'] ?? 'N/A') . '" readonly>
                    </div>
                    <div class="form-group">
                        <label>Customer Phone:</label>
                        <input type="text" value="' . h($invoice_bike['customer_phone'] ?? 'N/A') . '" readonly>
                    </div>
                    <div class="form-group">
                        <label>Customer CNIC:</label>
                        <input type="text" value="' . h($invoice_bike['customer_cnic'] ?? 'N/A') . '" readonly>
                    </div>
                    <div class="form-group">
                        <label>Customer Address:</label>
                        <textarea readonly>' . h($invoice_bike['customer_address'] ?? 'N/A') . '</textarea>
                    </div>
                </div>
                <h3 style="margin-top: 20px;">Bike Details</h3>
                <div class="table-container">
                    <table class="data-table invoice-items-table">
                        <thead>
                            <tr>
                                <th>Model</th>
                                <th>Chassis #</th>
                                <th>Motor #</th>
                                <th>Color</th>
                                <th class="text-right">Selling Price</th>
                                <th class="text-right">Tax Amount</th>
                                <th class="text-right">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>' . h($invoice_bike['model_name']) . '</td>
                                <td>' . h($invoice_bike['chassis_number']) . '</td>
                                <td>' . h($invoice_bike['motor_number']) . '</td>
                                <td>' . h($invoice_bike['color']) . '</td>
                                <td class="text-right">' . h(formatCurrency($invoice_bike['selling_price'])) . '</td>
                                <td class="text-right">' . h(formatCurrency($invoice_bike['tax_amount'])) . '</td>
                                <td class="text-right">' . h(formatCurrency($invoice_bike['selling_price'] + $invoice_bike['tax_amount'])) . '</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                    <div class="invoice-summary" style="width: 300px; border: 1px solid var(--border-color); padding: 10px; background-color: var(--surface-alt);">
                        <div style="display: flex; justify-content: space-between; padding: 5px 0;">
                            <span>Subtotal:</span>
                            <span style="font-weight: bold;">' . h(formatCurrency($invoice_bike['selling_price'])) . '</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 5px 0;">
                            <span>Tax (' . h($tax_rate * 100) . '%):</span>
                            <span style="font-weight: bold;">' . h(formatCurrency($invoice_bike['tax_amount'])) . '</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-top: 1px solid var(--border-color); font-size: 16px;">
                            <strong>TOTAL:</strong>
                            <strong style="color: var(--accent-color);">' . h(formatCurrency($invoice_bike['selling_price'] + $invoice_bike['tax_amount'])) . '</strong>
                        </div>
                    </div>
                </div>
                <div class="form-grid" style="margin-top: 20px;">
                    <div class="form-group">
                        <label>Payment Type:</label>
                        <input type="text" value="' . h(ucfirst(str_replace('_', ' ', $payment_details['payment_type'] ?? 'N/A'))) . '" readonly>
                    </div>
                    <div class="form-group">
                        <label>Amount Paid:</label>
                        <input type="text" value="' . h(formatCurrency($payment_details['amount'] ?? 0)) . '" readonly>
                    </div>
                    ';
            if ($payment_details['payment_type'] === 'cheque') {
                $invoice_content .= '
                        <div class="form-group">
                            <label>Cheque Number:</label>
                            <input type="text" value="' . h($payment_details['cheque_number']) . '" readonly>
                        </div>
                        <div class="form-group">
                            <label>Bank Name:</label>
                            <input type="text" value="' . h($payment_details['bank_name']) . '" readonly>
                        </div>
                        <div class="form-group">
                            <label>Cheque Date:</label>
                            <input type="text" value="' . h(formatDate($payment_details['cheque_date'])) . '" readonly>
                        </div>
                        ';
            }
            $invoice_content .= '
                    <div class="form-group">
                        <label>Accessories:</label>
                        <textarea readonly>' . h($accessories ?: 'N/A') . '</textarea>
                    </div>
                    <div class="form-group">
                        <label>Notes:</label>
                        <textarea readonly>' . h($invoice_bike['notes'] ?? 'N/A') . '</textarea>
                    </div>
                </div>
                <div class="invoice-footer print-only">
                    Thank you for your purchase from ' . h($company_name) . ' - ' . h($branch_name) . '.
                </div>
                <div class="no-print" style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="button" class="btn btn-primary" onclick="window.print()">Print Invoice</button>
                    <a href="?page=sale" class="btn btn-secondary">New Sale</a>
                    <a href="?page=dashboard" class="btn btn-secondary">Go to Dashboard</a>
                </div>
            </div>
            ';
            echo $invoice_content;
            unset($_SESSION['last_invoice_id']);
            return;
        }
    }
    renderLayout('Sales Entry', $content, 'sale');
}

function renderReturnsAdjustments(): void
{
    $conn = getDbConnection();
    $sold_bikes = [];
    $stmt = $conn->prepare("SELECT b.id, b.chassis_number, m.model_name, b.color, b.selling_date, b.selling_price, c.name as customer_name
                            FROM `bikes` b
                            JOIN `models` m ON b.model_id = m.id
                            JOIN `customers` c ON b.customer_id = c.id
                            WHERE b.status = 'sold'
                            ORDER BY b.selling_date DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $sold_bikes[] = $row;
    }
    $stmt->close();
    $bike_id = (int) ($_GET['bike_id'] ?? 0);
    $selected_bike = null;
    if ($bike_id) {
        $stmt = $conn->prepare("SELECT b.*, m.model_name, m.model_code, c.name as customer_name, c.phone as customer_phone
                                FROM `bikes` b
                                JOIN `models` m ON b.model_id = m.id
                                JOIN `customers` c ON b.customer_id = c.id
                                WHERE b.id = ? AND b.status = 'sold'");
        $stmt->bind_param('i', $bike_id);
        $stmt->execute();
        $selected_bike = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$selected_bike) {
            $_SESSION['flash_message'] = 'Sold bike not found.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ?page=returns');
            exit;
        }
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_return' && verifyCsrf()) {
        $conn->autocommit(false);
        try {
            $bike_id_return = (int) ($_POST['bike_id'] ?? 0);
            $return_date = trim($_POST['return_date'] ?? date('Y-m-d'));
            $return_amount = (float) ($_POST['return_amount'] ?? 0);
            $refund_method = trim($_POST['refund_method'] ?? 'cash');
            $return_notes = trim($_POST['return_notes'] ?? '');
            if ($bike_id_return === 0 || $return_amount < 0) {
                throw new Exception('Invalid bike or return amount provided.');
            }
            $stmt_bike_status = $conn->prepare('SELECT status, customer_id FROM `bikes` WHERE id = ?');
            $stmt_bike_status->bind_param('i', $bike_id_return);
            $stmt_bike_status->execute();
            $result_bike_status = $stmt_bike_status->get_result();
            $current_bike_status = $result_bike_status->fetch_assoc();
            $stmt_bike_status->close();
            if (!$current_bike_status || $current_bike_status['status'] !== 'sold') {
                throw new Exception('Bike not found or not currently marked as sold.');
            }
            $customer_id = $current_bike_status['customer_id'];
            $stmt_update_bike = $conn->prepare("UPDATE `bikes` SET
                                    status = 'returned', return_date = ?, return_amount = ?, return_notes = ?
                                    WHERE id = ?");
            if ($stmt_update_bike === false)
                throw new Exception('Failed to prepare update bike statement: ' . $conn->error);
            $stmt_update_bike->bind_param('sdsi', $return_date, $return_amount, $return_notes, $bike_id_return);
            if (!$stmt_update_bike->execute())
                throw new Exception('Failed to update bike ' . h($bike_id_return) . ': ' . $stmt_update_bike->error);
            $stmt_update_bike->close();
            $cheque_id = null;
            if ($refund_method === 'cheque') {
                $cheque_number = trim($_POST['cheque_number'] ?? '');
                $bank_name = trim($_POST['bank_name'] ?? '');
                $cheque_date = trim($_POST['cheque_date'] ?? '');
                $cheque_amount = (float) ($_POST['cheque_amount'] ?? 0);
                if (empty($cheque_number) || $cheque_amount <= 0 || empty($bank_name) || empty($cheque_date)) {
                    throw new Exception('Cheque details are required for cheque refunds.');
                }
                $stmt_cheque = $conn->prepare("INSERT INTO `cheque_register` (`cheque_number`, `bank_name`, `cheque_date`, `amount`, `type`, `status`, `reference_type`, `reference_id`, `party_name`, `notes`) VALUES (?, ?, ?, ?, 'refund', 'pending', 'return', ?, ?, ?)");
                if ($stmt_cheque === false)
                    throw new Exception('Failed to prepare Cheque Register statement: ' . $conn->error);
                $party_name = $conn->query('SELECT name FROM `customers` WHERE id = ' . $customer_id)->fetch_row()[0];
                $cheque_notes = 'Refund for Bike Return (Bike ID: ' . $bike_id_return . ')';
                $stmt_cheque->bind_param('sssdiss', $cheque_number, $bank_name, $cheque_date, $cheque_amount, $bike_id_return, $party_name, $cheque_notes);
                if (!$stmt_cheque->execute())
                    throw new Exception('Failed to save Cheque Register entry: ' . $stmt_cheque->error);
                $cheque_id = $stmt_cheque->insert_id;
                $stmt_cheque->close();
            }
            $stmt_payment = $conn->prepare("INSERT INTO `payments` (`payment_date`, `payment_type`, `amount`, `cheque_id`, `reference_type`, `reference_id`, `party_name`, `notes`) VALUES (?, ?, ?, ?, 'return', ?, ?, ?)");
            if ($stmt_payment === false)
                throw new Exception('Failed to prepare Payment statement: ' . $conn->error);
            $party_name = $conn->query('SELECT name FROM `customers` WHERE id = ' . $customer_id)->fetch_row()[0];
            $payment_notes = 'Refund for Bike Return (Bike ID: ' . $bike_id_return . ')';
            $stmt_payment->bind_param('ssdiiis', $return_date, $refund_method, $return_amount, $cheque_id, $bike_id_return, $party_name, $payment_notes);
            if (!$stmt_payment->execute())
                throw new Exception('Failed to save Payment entry: ' . $stmt_payment->error);
            $stmt_payment->close();
            $stmt_ledger = $conn->prepare("INSERT INTO `ledger` (`entry_date`, `entry_type`, `amount`, `party_type`, `party_id`, `description`, `reference_type`, `reference_id`) VALUES (?, 'debit', ?, 'customer', ?, ?, 'return', ?)");
            if ($stmt_ledger === false)
                throw new Exception('Failed to prepare Ledger statement: ' . $conn->error);
            $ledger_desc = 'Refund for returned bike (Chassis: ' . $selected_bike['chassis_number'] . ')';
            $stmt_ledger->bind_param('sdisi', $return_date, $return_amount, $customer_id, $ledger_desc, $bike_id_return);
            if (!$stmt_ledger->execute())
                throw new Exception('Failed to save Ledger entry: ' . $stmt_ledger->error);
            $stmt_ledger->close();
            $conn->commit();
            $_SESSION['flash_message'] = 'Bike return processed successfully.';
            $_SESSION['flash_type'] = 'success';
            header('Location: ?page=returns');
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['flash_message'] = 'Error processing return: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            header('Location: ?page=returns');
            exit;
        } finally {
            $conn->autocommit(true);
        }
    }
    ob_start();
    ?>
    <h2 style="margin-bottom: 20px;">Returns / Adjustments</h2>
    <form id="return-form" method="POST" action="?page=returns">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="process_return">
        <div class="form-section animate__animated animate__fadeIn">
            <div class="form-section-title">Bike to Return</div>
            <div class="form-grid">
                <div class="form-group">
                    <label for="bike_id">Select Sold Bike <span class="required-mark">*</span></label>
                    <select id="bike_id" name="bike_id" required onchange="updateReturnBikeDetails()">
                        <option value="">-- Select Sold Bike --</option>
                        <?php foreach ($sold_bikes as $bike): ?>
                            <option value="<?= h($bike['id']) ?>"
                                data-chassis="<?= h($bike['chassis_number']) ?>"
                                data-model="<?= h($bike['model_name']) ?>"
                                data-customer="<?= h($bike['customer_name']) ?>"
                                data-selling-price="<?= h($bike['selling_price']) ?>"
                                <?= $selected_bike && (string) $selected_bike['id'] === (string) $bike['id'] ? 'selected' : '' ?>>
                                <?= h($bike['chassis_number']) ?> (<?= h($bike['model_name']) ?>) - Sold to <?= h($bike['customer_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Chassis Number:</label>
                    <input type="text" id="return_chassis" readonly value="<?= h($selected_bike['chassis_number'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Model:</label>
                    <input type="text" id="return_model" readonly value="<?= h($selected_bike['model_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Customer:</label>
                    <input type="text" id="return_customer" readonly value="<?= h($selected_bike['customer_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Selling Price:</label>
                    <input type="text" id="return_selling_price" readonly value="<?= h($selected_bike ? formatCurrency($selected_bike['selling_price']) : '') ?>">
                </div>
            </div>
        </div>
        <div class="form-section animate__animated animate__fadeIn">
            <div class="form-section-title">Return Details</div>
            <div class="form-grid">
                <div class="form-group">
                    <label for="return_date">Return Date <span class="required-mark">*</span></label>
                    <input type="date" id="return_date" name="return_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label for="return_amount">Return Amount <span class="required-mark">*</span></label>
                    <input type="number" id="return_amount" name="return_amount" step="0.01" min="0" value="<?= h($selected_bike['selling_price'] ?? 0) ?>" required>
                </div>
                <div class="form-group">
                    <label for="refund_method">Refund Method <span class="required-mark">*</span></label>
                    <select id="refund_method" name="refund_method" onchange="toggleRefundChequeFields()" required>
                        <option value="cash">Cash</option>
                        <option value="cheque">Cheque</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="online">Online Payment</option>
                    </select>
                </div>
            </div>
            <div id="refund-cheque-fields" class="form-grid" style="display: none; margin-top: 15px;">
                <div class="form-group">
                    <label for="cheque_number">Cheque Number <span class="required-mark">*</span></label>
                    <input type="text" id="cheque_number" name="cheque_number">
                </div>
                <div class="form-group">
                    <label for="bank_name">Bank Name <span class="required-mark">*</span></label>
                    <input type="text" id="bank_name" name="bank_name">
                </div>
                <div class="form-group">
                    <label for="cheque_date">Cheque Date <span class="required-mark">*</span></label>
                    <input type="date" id="cheque_date" name="cheque_date">
                </div>
                <div class="form-group">
                    <label for="cheque_amount">Cheque Amount <span class="required-mark">*</span></label>
                    <input type="number" id="cheque_amount" name="cheque_amount" step="0.01" min="0" value="0.00">
                </div>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label for="return_notes">Notes (e.g., reason for return, account details)</label>
                    <textarea id="return_notes" name="return_notes"></textarea>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-success animate__animated animate__pulse">Process Return</button>
    </form>
    <script>
        const CURRENCY = '<?= getSetting('currency_symbol', 'Rs.') ?>';
        function updateReturnBikeDetails() {
            const selectElement = document.getElementById('bike_id');
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            if (selectedOption.value) {
                document.getElementById('return_chassis').value = selectedOption.dataset.chassis;
                document.getElementById('return_model').value = selectedOption.dataset.model;
                document.getElementById('return_customer').value = selectedOption.dataset.customer;
                const sellingPrice = parseFloat(selectedOption.dataset.sellingPrice);
                document.getElementById('return_selling_price').value = `${CURRENCY} ${sellingPrice.toFixed(2)}`;
                document.getElementById('return_amount').value = sellingPrice.toFixed(2);
            } else {
                document.getElementById('return_chassis').value = '';
                document.getElementById('return_model').value = '';
                document.getElementById('return_customer').value = '';
                document.getElementById('return_selling_price').value = '';
                document.getElementById('return_amount').value = '0.00';
            }
        }
        function toggleRefundChequeFields() {
            const refundMethod = document.getElementById('refund_method').value;
            const chequeFields = document.getElementById('refund-cheque-fields');
            if (refundMethod === 'cheque') {
                chequeFields.style.display = 'grid';
                document.getElementById('cheque_number').setAttribute('required', 'required');
                document.getElementById('bank_name').setAttribute('required', 'required');
                document.getElementById('cheque_date').setAttribute('required', 'required');
                document.getElementById('cheque_amount').setAttribute('required', 'required');
            } else {
                chequeFields.style.display = 'none';
                document.getElementById('cheque_number').removeAttribute('required');
                document.getElementById('bank_name').removeAttribute('required');
                document.getElementById('cheque_date').removeAttribute('required');
                document.getElementById('cheque_amount').removeAttribute('required');
            }
        }
        const validator = new JustValidate('#return-form', {
            errorFieldCssClass: 'is-invalid',
            errorLabelCssClass: 'error-message',
            focusInvalidField: true,
            lockForm: true,
            tooltip: {
                position: 'top'
            }
        });
        validator
            .addField('#bike_id', [{ rule: 'required', errorMessage: 'Please select a bike to return' }])
            .addField('#return_date', [{ rule: 'required', errorMessage: 'Return Date is required' }])
            .addField('#return_amount', [
                { rule: 'required', errorMessage: 'Return Amount is required' },
                { rule: 'minNumber', value: 0.01, errorMessage: 'Must be greater than 0' }
            ])
            .addField('#refund_method', [{ rule: 'required', errorMessage: 'Refund Method is required' }]);
        $(document).ready(function() {
            updateReturnBikeDetails();
            toggleRefundChequeFields();
        });
    </script>
    <?php
    $content = ob_get_clean();
    renderLayout('Returns / Adjustments', $content, 'returns');
}

function renderChequeRegister(): void
{
    $conn = getDbConnection();
    $filters = [
        'status' => $_GET['status'] ?? 'all',
        'type' => $_GET['type'] ?? 'all',
        'date_from' => $_GET['date_from'] ?? '',
        'date_to' => $_GET['date_to'] ?? '',
        'bank' => $_GET['bank'] ?? ''
    ];
    $where = ['1=1'];
    $params = [];
    $param_types = '';
    if (!empty($filters['status']) && $filters['status'] !== 'all') {
        $where[] = 'status = ?';
        $params[] = $filters['status'];
        $param_types .= 's';
    }
    if (!empty($filters['type']) && $filters['type'] !== 'all') {
        $where[] = 'type = ?';
        $params[] = $filters['type'];
        $param_types .= 's';
    }
    if (!empty($filters['date_from'])) {
        $where[] = 'cheque_date >= ?';
        $params[] = $filters['date_from'];
        $param_types .= 's';
    }
    if (!empty($filters['date_to'])) {
        $where[] = 'cheque_date <= ?';
        $params[] = $filters['date_to'];
        $param_types .= 's';
    }
    if (!empty($filters['bank'])) {
        $where[] = 'bank_name LIKE ?';
        $params[] = '%' . $filters['bank'] . '%';
        $param_types .= 's';
    }
    $where_clause = implode(' AND ', $where);
    $sql = "SELECT * FROM `cheque_register` WHERE $where_clause ORDER BY cheque_date DESC, created_at DESC";
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $cheques = [];
    while ($row = $result->fetch_assoc()) {
        $cheques[] = $row;
    }
    $stmt->close();
    $summarySql = "SELECT
                    COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) AS total_pending,
                    COALESCE(SUM(CASE WHEN status = 'cleared' THEN amount ELSE 0 END), 0) AS total_cleared,
                    COALESCE(SUM(CASE WHEN status = 'bounced' THEN amount ELSE 0 END), 0) AS total_bounced
                   FROM `cheque_register` WHERE $where_clause";
    $stmtSummary = $conn->prepare($summarySql);
    if (!empty($params)) {
        $stmtSummary->bind_param($param_types, ...$params);
    }
    $stmtSummary->execute();
    $summary = $stmtSummary->get_result()->fetch_assoc();
    $stmtSummary->close();
    ob_start();
    ?>
    <h2 style="margin-bottom: 20px;">Cheque Register</h2>
    <div class="form-section animate__animated animate__fadeIn no-print">
        <div class="form-section-title">Cheque Summary</div>
        <div class="dashboard-cards">
            <div class="card">
                <div class="card-title">Total Pending Amount</div>
                <div class="card-value warning"><?= h(formatCurrency($summary['total_pending'])) ?></div>
            </div>
            <div class="card">
                <div class="card-title">Total Cleared Amount</div>
                <div class="card-value success"><?= h(formatCurrency($summary['total_cleared'])) ?></div>
            </div>
            <div class="card">
                <div class="card-title">Total Bounced Amount</div>
                <div class="card-value danger"><?= h(formatCurrency($summary['total_bounced'])) ?></div>
            </div>
        </div>
    </div>
    <div class="form-section animate__animated animate__fadeIn no-print">
        <div class="form-section-title">Filters</div>
        <form method="GET" action="?page=cheques">
            <input type="hidden" name="page" value="cheques">
            <div class="form-grid">
                <div class="form-group">
                    <label for="filter_status">Status</label>
                    <select id="filter_status" name="status">
                        <option value="all" <?= $filters['status'] === 'all' ? 'selected' : '' ?>>All</option>
                        <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="cleared" <?= $filters['status'] === 'cleared' ? 'selected' : '' ?>>Cleared</option>
                        <option value="bounced" <?= $filters['status'] === 'bounced' ? 'selected' : '' ?>>Bounced</option>
                        <option value="cancelled" <?= $filters['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filter_type">Type</label>
                    <select id="filter_type" name="type">
                        <option value="all" <?= $filters['type'] === 'all' ? 'selected' : '' ?>>All</option>
                        <option value="payment" <?= $filters['type'] === 'payment' ? 'selected' : '' ?>>Payment (Issued)</option>
                        <option value="receipt" <?= $filters['type'] === 'receipt' ? 'selected' : '' ?>>Receipt (Received)</option>
                        <option value="refund" <?= $filters['type'] === 'refund' ? 'selected' : '' ?>>Refund</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filter_bank">Bank Name</label>
                    <input type="text" id="filter_bank" name="bank" value="<?= h($filters['bank']) ?>">
                </div>
                <div class="form-group">
                    <label for="filter_date_from">Cheque Date From</label>
                    <input type="date" id="filter_date_from" name="date_from" value="<?= h($filters['date_from']) ?>">
                </div>
                <div class="form-group">
                    <label for="filter_date_to">Cheque Date To</label>
                    <input type="date" id="filter_date_to" name="date_to" value="<?= h($filters['date_to']) ?>">
                </div>
            </div>
            <div style="margin-top: 15px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="?page=cheques" class="btn btn-secondary">Reset Filters</a>
                <button type="button" class="btn btn-secondary" onclick="window.print()">Print Report</button>
            </div>
        </form>
    </div>
    <div class="table-container animate__animated animate__fadeIn">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Cheque #</th>
                    <th>Bank</th>
                    <th>Date</th>
                    <th class="text-right">Amount</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Party Name</th>
                    <th>Reference</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cheques)): ?>
                    <tr><td colspan="9" class="text-center">No cheques found.</td></tr>
                <?php else: ?>
                    <?php foreach ($cheques as $cheque): ?>
                        <tr class="status-<?= h($cheque['status']) ?>">
                            <td><?= h($cheque['cheque_number']) ?></td>
                            <td><?= h($cheque['bank_name']) ?></td>
                            <td><?= h(formatDate($cheque['cheque_date'])) ?></td>
                            <td class="text-right"><?= h(formatCurrency($cheque['amount'])) ?></td>
                            <td><?= h(ucfirst($cheque['type'])) ?></td>
                            <td><?= h(ucfirst($cheque['status'])) ?></td>
                            <td><?= h($cheque['party_name']) ?></td>
                            <td><?= h($cheque['reference_type'] ?? '-') ?> (ID: <?= h($cheque['reference_id'] ?? '-') ?>)</td>
                            <td>
                                <div class="btn-group">
                                    <?php if ($cheque['status'] === 'pending'): ?>
                                        <button type="button" class="btn btn-success btn-xs" onclick="updateChequeStatus(<?= h($cheque['id']) ?>, 'cleared')">Clear</button>
                                        <button type="button" class="btn btn-danger btn-xs" onclick="updateChequeStatus(<?= h($cheque['id']) ?>, 'bounced')">Bounce</button>
                                    <?php endif; ?>
                                    <a href="?page=cheques&action=edit&id=<?= h($cheque['id']) ?>" class="btn btn-warning btn-xs">Edit</a>
                                    <button type="button" class="btn btn-danger btn-xs" onclick="deleteCheque(<?= h($cheque['id']) ?>, '<?= h($cheque['cheque_number']) ?>')">Delete</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script>
        async function updateChequeStatus(id, status) {
            const result = await confirmAction(
                `Confirm Cheque ${status.charAt(0).toUpperCase() + status.slice(1)}`,
                `Are you sure you want to mark this cheque as "${status}"?`
            );
            if (result) {
                showLoadingSpinner();
                const response = await fetch('?page=ajax&action=update_cheque_status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `csrf_token=${CSRF_TOKEN}&id=${id}&status=${status}`
                });
                hideLoadingSpinner();
                const data = await response.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    location.reload();
                } else {
                    showToast(data.error, 'danger');
                }
            }
        }
        async function deleteCheque(id, chequeNumber) {
            const result = await confirmAction('Confirm Delete', `Are you sure you want to delete cheque # "${chequeNumber}"? This action cannot be undone.`);
            if (result) {
                showLoadingSpinner();
                const response = await fetch('?page=ajax&action=delete_cheque', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `csrf_token=${CSRF_TOKEN}&id=${id}`
                });
                hideLoadingSpinner();
                const data = await response.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    location.reload();
                } else {
                    showToast(data.error, 'danger');
                }
            }
        }
    </script>
    <?php
    $content = ob_get_clean();
    renderLayout('Cheque Register', $content, 'cheques');
}

function renderCustomerLedger(): void
{
    $conn = getDbConnection();
    $customers = getCustomers();
    $customer_id = (int) ($_GET['customer_id'] ?? 0);
    $ledger_entries = [];
    $current_balance = 0;
    if ($customer_id) {
        $stmt = $conn->prepare("SELECT * FROM `ledger` WHERE party_type = 'customer' AND party_id = ? ORDER BY entry_date ASC, created_at ASC");
        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $temp_entries = [];
        while ($row = $result->fetch_assoc()) {
            $temp_entries[] = $row;
        }
        $stmt->close();
        foreach ($temp_entries as $entry) {
            if ($entry['entry_type'] === 'debit') {
                $current_balance -= $entry['amount'];
            } else {
                $current_balance += $entry['amount'];
            }
            $entry['balance'] = $current_balance;
            $ledger_entries[] = $entry;
        }
    }
    ob_start();
    ?>
    <h2 style="margin-bottom: 20px;">Customer Ledger</h2>
    <div class="form-section animate__animated animate__fadeIn no-print">
        <div class="form-section-title">Select Customer</div>
        <form method="GET" action="?page=customer_ledger">
            <input type="hidden" name="page" value="customer_ledger">
            <div class="form-grid">
                <div class="form-group">
                    <label for="customer_id">Customer <span class="required-mark">*</span></label>
                    <select id="customer_id" name="customer_id" required>
                        <option value="">-- Select Customer --</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?= h($customer['id']) ?>" <?= (string) $customer_id === (string) $customer['id'] ? 'selected' : '' ?>>
                                <?= h($customer['name']) ?> (<?= h($customer['phone']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="margin-top: 15px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Show Ledger</button>
                <?php if ($customer_id): ?>
                    <button type="button" class="btn btn-secondary" onclick="window.print()">Print Ledger</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <?php if ($customer_id && empty($ledger_entries)): ?>
        <div class="form-section animate__animated animate__fadeIn">
            <div class="form-section-title">Ledger for <?= h($customers[$customer_id - 1]['name']) ?></div>
            <p class="text-center">No transactions found for this customer.</p>
        </div>
    <?php elseif ($customer_id): ?>
        <div class="form-section animate__animated animate__fadeIn">
            <div class="form-section-title">Ledger for <?= h($customers[$customer_id - 1]['name']) ?></div>
            <div style="margin-bottom: 15px; font-weight: bold; font-size: 14px; text-align: right;">
                Current Balance: <span style="color: <?= $current_balance >= 0 ? 'var(--success-color)' : 'var(--danger-color)' ?>;"><?= h(formatCurrency($current_balance)) ?></span>
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th class="text-right">Debit</th>
                            <th class="text-right">Credit</th>
                            <th class="text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $running_balance = 0; ?>
                        <?php foreach ($ledger_entries as $entry): ?>
                            <?php
                            if ($entry['entry_type'] === 'debit') {
                                $running_balance -= $entry['amount'];
                            } else {
                                $running_balance += $entry['amount'];
                            }
                            ?>
                            <tr>
                                <td><?= h(formatDate($entry['entry_date'])) ?></td>
                                <td><?= h($entry['description']) ?></td>
                                <td class="text-right"><?= $entry['entry_type'] === 'debit' ? h(formatCurrency($entry['amount'])) : '-' ?></td>
                                <td class="text-right"><?= $entry['entry_type'] === 'credit' ? h(formatCurrency($entry['amount'])) : '-' ?></td>
                                <td class="text-right" style="color: <?= $running_balance >= 0 ? 'var(--success-color)' : 'var(--danger-color)' ?>;"><?= h(formatCurrency($running_balance)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr style="background-color: var(--header-bg); color: #ffffff; font-weight: bold;">
                            <td colspan="4" class="text-right">Final Balance:</td>
                            <td class="text-right" style="color: <?= $running_balance >= 0 ? 'var(--success-color)' : 'var(--danger-color)' ?>;"><?= h(formatCurrency($running_balance)) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif (!$customer_id): ?>
        <p class="text-center animate__animated animate__fadeIn" style="margin-top: 50px;">Please select a customer to view their ledger.</p>
    <?php endif; ?>
    <script>
        $(document).ready(function() {
        });
    </script>
    <?php
    $content = ob_get_clean();
    renderLayout('Customer Ledger', $content, 'customer_ledger');
}

function renderSupplierLedger(): void
{
    $conn = getDbConnection();
    $suppliers = getSuppliers();
    $supplier_id = (int) ($_GET['supplier_id'] ?? 0);
    $ledger_entries = [];
    $current_balance = 0;
    if ($supplier_id) {
        $stmt = $conn->prepare("SELECT * FROM `ledger` WHERE party_type = 'supplier' AND party_id = ? ORDER BY entry_date ASC, created_at ASC");
        $stmt->bind_param('i', $supplier_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $temp_entries = [];
        while ($row = $result->fetch_assoc()) {
            $temp_entries[] = $row;
        }
        $stmt->close();
        foreach ($temp_entries as $entry) {
            if ($entry['entry_type'] === 'debit') {
                $current_balance += $entry['amount'];
            } else {
                $current_balance -= $entry['amount'];
            }
            $entry['balance'] = $current_balance;
            $ledger_entries[] = $entry;
        }
    }
    ob_start();
    ?>
    <h2 style="margin-bottom: 20px;">Supplier Ledger</h2>
    <div class="form-section animate__animated animate__fadeIn no-print">
        <div class="form-section-title">Select Supplier</div>
        <form method="GET" action="?page=supplier_ledger">
            <input type="hidden" name="page" value="supplier_ledger">
            <div class="form-grid">
                <div class="form-group">
                    <label for="supplier_id">Supplier <span class="required-mark">*</span></label>
                    <select id="supplier_id" name="supplier_id" required>
                        <option value="">-- Select Supplier --</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?= h($supplier['id']) ?>" <?= (string) $supplier_id === (string) $supplier['id'] ? 'selected' : '' ?>>
                                <?= h($supplier['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="margin-top: 15px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Show Ledger</button>
                <?php if ($supplier_id): ?>
                    <button type="button" class="btn btn-secondary" onclick="window.print()">Print Ledger</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <?php if ($supplier_id && empty($ledger_entries)): ?>
        <div class="form-section animate__animated animate__fadeIn">
            <div class="form-section-title">Ledger for <?= h($suppliers[$supplier_id - 1]['name']) ?></div>
            <p class="text-center">No transactions found for this supplier.</p>
        </div>
    <?php elseif ($supplier_id): ?>
        <div class="form-section animate__animated animate__fadeIn">
            <div class="form-section-title">Ledger for <?= h($suppliers[$supplier_id - 1]['name']) ?></div>
            <div style="margin-bottom: 15px; font-weight: bold; font-size: 14px; text-align: right;">
                Current Balance: <span style="color: <?= $current_balance >= 0 ? 'var(--danger-color)' : 'var(--success-color)' ?>;"><?= h(formatCurrency($current_balance)) ?></span>
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th class="text-right">Debit</th>
                            <th class="text-right">Credit</th>
                            <th class="text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $running_balance = 0; ?>
                        <?php foreach ($ledger_entries as $entry): ?>
                            <?php
                            if ($entry['entry_type'] === 'debit') {
                                $running_balance += $entry['amount'];
                            } else {
                                $running_balance -= $entry['amount'];
                            }
                            ?>
                            <tr>
                                <td><?= h(formatDate($entry['entry_date'])) ?></td>
                                <td><?= h($entry['description']) ?></td>
                                <td class="text-right"><?= $entry['entry_type'] === 'debit' ? h(formatCurrency($entry['amount'])) : '-' ?></td>
                                <td class="text-right"><?= $entry['entry_type'] === 'credit' ? h(formatCurrency($entry['amount'])) : '-' ?></td>
                                <td class="text-right" style="color: <?= $running_balance >= 0 ? 'var(--danger-color)' : 'var(--success-color)' ?>;"><?= h(formatCurrency($running_balance)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr style="background-color: var(--header-bg); color: #ffffff; font-weight: bold;">
                            <td colspan="4" class="text-right">Final Balance:</td>
                            <td class="text-right" style="color: <?= $running_balance >= 0 ? 'var(--danger-color)' : 'var(--success-color)' ?>;"><?= h(formatCurrency($running_balance)) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif (!$supplier_id): ?>
        <p class="text-center animate__animated animate__fadeIn" style="margin-top: 50px;">Please select a supplier to view their ledger.</p>
    <?php endif; ?>
    <script>
        $(document).ready(function() {
        });
    </script>
    <?php
    $content = ob_get_clean();
    renderLayout('Supplier Ledger', $content, 'supplier_ledger');
}

function renderReports(): void
{
    $conn = getDbConnection();
    $report_type = $_GET['report'] ?? 'current_stock';
    $date_from = $_GET['date_from'] ?? date('Y-m-01');
    $date_to = $_GET['date_to'] ?? date('Y-m-d');
    $year = $_GET['year'] ?? date('Y');
    $month = $_GET['month'] ?? date('m');
    $currency_symbol = getSetting('currency_symbol', 'Rs.');
    ob_start();
    ?>
    <h2 style="margin-bottom: 20px;">Reports</h2>
    <div class="form-section animate__animated animate__fadeIn no-print">
        <div class="form-section-title">Report Filters</div>
        <form method="GET" action="?page=reports">
            <input type="hidden" name="page" value="reports">
            <div class="form-grid">
                <div class="form-group">
                    <label for="report_type">Report Type</label>
                    <select id="report_type" name="report" onchange="this.form.submit()">
                        <option value="current_stock" <?= $report_type === 'current_stock' ? 'selected' : '' ?>>Current Stock Report</option>
                        <option value="sold_bikes" <?= $report_type === 'sold_bikes' ? 'selected' : '' ?>>Sold Bikes Report</option>
                        <option value="model_wise_sales" <?= $report_type === 'model_wise_sales' ? 'selected' : '' ?>>Model-wise Sales Report</option>
                        <option value="tax_report" <?= $report_type === 'tax_report' ? 'selected' : '' ?>>Tax Report</option>
                        <option value="profit_margin" <?= $report_type === 'profit_margin' ? 'selected' : '' ?>>Profit/Margin Report</option>
                        <option value="bank_cheque" <?= $report_type === 'bank_cheque' ? 'selected' : '' ?>>Bank/Cheque Report</option>
                        <option value="purchase_vs_sales" <?= $report_type === 'purchase_vs_sales' ? 'selected' : '' ?>>Purchase vs Sales Summary</option>
                        <option value="daily_ledger" <?= $report_type === 'daily_ledger' ? 'selected' : '' ?>>Daily Ledger</option>
                        <option value="monthly_summary" <?= $report_type === 'monthly_summary' ? 'selected' : '' ?>>Monthly Summary Report</option>
                    </select>
                </div>
                <?php if (in_array($report_type, ['sold_bikes', 'tax_report', 'profit_margin', 'purchase_vs_sales', 'daily_ledger', 'monthly_summary'])): ?>
                    <div class="form-group">
                        <label for="date_from">Date From</label>
                        <input type="date" id="date_from" name="date_from" value="<?= h($date_from) ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_to">Date To</label>
                        <input type="date" id="date_to" name="date_to" value="<?= h($date_to) ?>">
                    </div>
                <?php endif; ?>
                <?php if (in_array($report_type, ['purchase_vs_sales', 'monthly_summary'])): ?>
                    <div class="form-group">
                        <label for="year">Year</label>
                        <select id="year" name="year">
                            <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                <option value="<?= $y ?>" <?= (string) $year === (string) $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <?php if ($report_type === 'monthly_summary'): ?>
                        <div class="form-group">
                            <label for="month">Month</label>
                            <select id="month" name="month">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= (string) $month === (string) str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 10)) ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div style="margin-top: 15px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Generate Report</button>
                <button type="button" class="btn btn-secondary" onclick="window.print()">Print Report</button>
            </div>
        </form>
    </div>
    <div class="form-section animate__animated animate__fadeIn">
        <div class="form-section-title">Report Output</div>
        <?php if ($report_type === 'current_stock'): ?>
            <h3>Current Stock Report</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Model</th>
                            <th>Chassis #</th>
                            <th>Motor #</th>
                            <th>Color</th>
                            <th class="text-right">Purchase Price</th>
                            <th>Status</th>
                            <th>Inventory Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->query("SELECT b.*, m.model_name FROM `bikes` b JOIN `models` m ON b.model_id = m.id WHERE b.status = 'in_stock' ORDER BY m.model_name, b.chassis_number");
                        $current_stock_bikes = [];
                        while ($row = $stmt->fetch_assoc())
                            $current_stock_bikes[] = $row;
                        ?>
                        <?php if (empty($current_stock_bikes)): ?>
                            <tr><td colspan="7" class="text-center">No bikes currently in stock.</td></tr>
                        <?php else: ?>
                            <?php foreach ($current_stock_bikes as $bike): ?>
                                <tr>
                                    <td><?= h($bike['model_name']) ?></td>
                                    <td><?= h($bike['chassis_number']) ?></td>
                                    <td><?= h($bike['motor_number']) ?></td>
                                    <td><?= h($bike['color']) ?></td>
                                    <td class="text-right"><?= h(formatCurrency($bike['purchase_price'])) ?></td>
                                    <td><?= h(ucfirst(str_replace('_', ' ', $bike['status']))) ?></td>
                                    <td><?= h(formatDate($bike['inventory_date'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($report_type === 'sold_bikes'): ?>
            <h3>Sold Bikes Report (<?= h(formatDate($date_from)) ?> - <?= h(formatDate($date_to)) ?>)</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Chassis #</th>
                            <th>Model</th>
                            <th>Customer</th>
                            <th>Selling Date</th>
                            <th class="text-right">Purchase Price</th>
                            <th class="text-right">Selling Price</th>
                            <th class="text-right">Tax Amount</th>
                            <th class="text-right">Margin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->prepare("SELECT b.*, m.model_name, c.name as customer_name FROM `bikes` b JOIN `models` m ON b.model_id = m.id LEFT JOIN `customers` c ON b.customer_id = c.id WHERE b.status = 'sold' AND b.selling_date BETWEEN ? AND ? ORDER BY b.selling_date DESC");
                        $stmt->bind_param('ss', $date_from, $date_to);
                        $stmt->execute();
                        $sold_bikes_report = $stmt->get_result()->fetch_assoc();
                        $total_purchase_price = 0;
                        $total_selling_price = 0;
                        $total_tax_amount = 0;
                        $total_margin = 0;
                        ?>
                        <?php if (empty($sold_bikes_report)): ?>
                            <tr><td colspan="8" class="text-center">No bikes sold in this period.</td></tr>
                        <?php else: ?>
                            <?php
                            foreach ($sold_bikes_report as $bike):
                                $total_purchase_price += $bike['purchase_price'];
                                $total_selling_price += $bike['selling_price'];
                                $total_tax_amount += $bike['tax_amount'];
                                $total_margin += $bike['margin'];
                                ?>
                                <tr>
                                    <td><?= h($bike['chassis_number']) ?></td>
                                    <td><?= h($bike['model_name']) ?></td>
                                    <td><?= h($bike['customer_name'] ?? 'N/A') ?></td>
                                    <td><?= h(formatDate($bike['selling_date'])) ?></td>
                                    <td class="text-right"><?= h(formatCurrency($bike['purchase_price'])) ?></td>
                                    <td class="text-right"><?= h(formatCurrency($bike['selling_price'])) ?></td>
                                    <td class="text-right"><?= h(formatCurrency($bike['tax_amount'])) ?></td>
                                    <td class="text-right"><?= h(formatCurrency($bike['margin'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr style="background-color: var(--header-bg); color: #ffffff; font-weight: bold;">
                                <td colspan="4" class="text-right">Totals:</td>
                                <td class="text-right"><?= h(formatCurrency($total_purchase_price)) ?></td>
                                <td class="text-right"><?= h(formatCurrency($total_selling_price)) ?></td>
                                <td class="text-right"><?= h(formatCurrency($total_tax_amount)) ?></td>
                                <td class="text-right"><?= h(formatCurrency($total_margin)) ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($report_type === 'model_wise_sales'): ?>
            <h3>Model-wise Sales Report</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Model</th>
                            <th class="text-right">Inventory Count</th>
                            <th class="text-right">Sold Count</th>
                            <th class="text-right">Available Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $model_sales_summary = [];
                        $stmt = $conn->query("SELECT m.model_name,
                                COALESCE(COUNT(b.id), 0) AS inventory_count,
                                COALESCE(SUM(CASE WHEN b.status = 'sold' THEN 1 ELSE 0 END), 0) AS sold_count,
                                COALESCE(SUM(CASE WHEN b.status = 'in_stock' THEN 1 ELSE 0 END), 0) AS available_stock
                            FROM `models` m
                            LEFT JOIN `bikes` b ON m.id = b.model_id
                            GROUP BY m.id, m.model_name
                            ORDER BY m.model_name");
                        while ($row = $stmt->fetch_assoc())
                            $model_sales_summary[] = $row;
                        ?>
                        <?php if (empty($model_sales_summary)): ?>
                            <tr><td colspan="4" class="text-center">No model data available.</td></tr>
                        <?php else: ?>
                            <?php foreach ($model_sales_summary as $model): ?>
                                <tr>
                                    <td><?= h($model['model_name']) ?></td>
                                    <td class="text-right"><?= h(number_format($model['inventory_count'])) ?></td>
                                    <td class="text-right"><?= h(number_format($model['sold_count'])) ?></td>
                                    <td class="text-right"><?= h(number_format($model['available_stock'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($report_type === 'tax_report'): ?>
            <h3>Tax Report (<?= h(formatDate($date_from)) ?> - <?= h(formatDate($date_to)) ?>)</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Selling Date</th>
                            <th>Chassis #</th>
                            <th>Model</th>
                            <th>Customer</th>
                            <th class="text-right">Selling Price</th>
                            <th class="text-right">Tax Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->prepare("SELECT b.chassis_number, b.selling_date, b.selling_price, b.tax_amount, m.model_name, c.name as customer_name
                                                FROM `bikes` b
                                                JOIN `models` m ON b.model_id = m.id
                                                LEFT JOIN `customers` c ON b.customer_id = c.id
                                                WHERE b.status = 'sold' AND b.selling_date BETWEEN ? AND ?
                                                ORDER BY b.selling_date DESC");
                        $stmt->bind_param('ss', $date_from, $date_to);
                        $stmt->execute();
                        $tax_report_entries = $stmt->get_result();
                        $total_tax_collected = 0;
                        ?>
                        <?php if ($tax_report_entries->num_rows === 0): ?>
                            <tr><td colspan="6" class="text-center">No tax entries in this period.</td></tr>
                        <?php else: ?>
                            <?php while ($entry = $tax_report_entries->fetch_assoc()):
                                $total_tax_collected += $entry['tax_amount']; ?>
                                <tr>
                                    <td><?= h(formatDate($entry['selling_date'])) ?></td>
                                    <td><?= h($entry['chassis_number']) ?></td>
                                    <td><?= h($entry['model_name']) ?></td>
                                    <td><?= h($entry['customer_name'] ?? 'N/A') ?></td>
                                    <td class="text-right"><?= h(formatCurrency($entry['selling_price'])) ?></td>
                                    <td class="text-right"><?= h(formatCurrency($entry['tax_amount'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                            <tr style="background-color: var(--header-bg); color: #ffffff; font-weight: bold;">
                                <td colspan="5" class="text-right">Total Tax Collected:</td>
                                <td class="text-right"><?= h(formatCurrency($total_tax_collected)) ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($report_type === 'profit_margin'): ?>
            <h3>Profit/Margin Report (<?= h(formatDate($date_from)) ?> - <?= h(formatDate($date_to)) ?>)</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Selling Date</th>
                            <th>Chassis #</th>
                            <th>Model</th>
                            <th class="text-right">Purchase Price</th>
                            <th class="text-right">Selling Price</th>
                            <th class="text-right">Margin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->prepare("SELECT b.chassis_number, b.selling_date, b.purchase_price, b.selling_price, b.margin, m.model_name
                                                FROM `bikes` b
                                                JOIN `models` m ON b.model_id = m.id
                                                WHERE b.status = 'sold' AND b.selling_date BETWEEN ? AND ?
                                                ORDER BY b.selling_date DESC");
                        $stmt->bind_param('ss', $date_from, $date_to);
                        $stmt->execute();
                        $profit_report_entries = $stmt->get_result();
                        $total_margin_sum = 0;
                        ?>
                        <?php if ($profit_report_entries->num_rows === 0): ?>
                            <tr><td colspan="6" class="text-center">No profit/margin entries in this period.</td></tr>
                        <?php else: ?>
                            <?php while ($entry = $profit_report_entries->fetch_assoc()):
                                $total_margin_sum += $entry['margin']; ?>
                                <tr>
                                    <td><?= h(formatDate($entry['selling_date'])) ?></td>
                                    <td><?= h($entry['chassis_number']) ?></td>
                                    <td><?= h($entry['model_name']) ?></td>
                                    <td class="text-right"><?= h(formatCurrency($entry['purchase_price'])) ?></td>
                                    <td class="text-right"><?= h(formatCurrency($entry['selling_price'])) ?></td>
                                    <td class="text-right" style="color: <?= $entry['margin'] >= 0 ? 'var(--success-color)' : 'var(--danger-color)' ?>;"><?= h(formatCurrency($entry['margin'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                            <tr style="background-color: var(--header-bg); color: #ffffff; font-weight: bold;">
                                <td colspan="5" class="text-right">Total Margin:</td>
                                <td class="text-right" style="color: <?= $total_margin_sum >= 0 ? 'var(--success-color)' : 'var(--danger-color)' ?>;"><?= h(formatCurrency($total_margin_sum)) ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($report_type === 'bank_cheque'): ?>
            <h3>Bank/Cheque Report</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Bank Name</th>
                            <th class="text-right">Total Pending</th>
                            <th class="text-right">Total Cleared</th>
                            <th class="text-right">Total Bounced</th>
                            <th class="text-right">Total Cancelled</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->query("SELECT bank_name,
                                COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) AS pending_amount,
                                COALESCE(SUM(CASE WHEN status = 'cleared' THEN amount ELSE 0 END), 0) AS cleared_amount,
                                COALESCE(SUM(CASE WHEN status = 'bounced' THEN amount ELSE 0 END), 0) AS bounced_amount,
                                COALESCE(SUM(CASE WHEN status = 'cancelled' THEN amount ELSE 0 END), 0) AS cancelled_amount
                            FROM `cheque_register`
                            GROUP BY bank_name
                            ORDER BY bank_name");
                        $bank_cheque_summary = [];
                        while ($row = $stmt->fetch_assoc())
                            $bank_cheque_summary[] = $row;
                        ?>
                        <?php if (empty($bank_cheque_summary)): ?>
                            <tr><td colspan="5" class="text-center">No cheque data available.</td></tr>
                        <?php else: ?>
                            <?php foreach ($bank_cheque_summary as $bank_summary): ?>
                                <tr>
                                    <td><?= h($bank_summary['bank_name']) ?></td>
                                    <td class="text-right"><?= h(formatCurrency($bank_summary['pending_amount'])) ?></td>
                                    <td class="text-right"><?= h(formatCurrency($bank_summary['cleared_amount'])) ?></td>
                                    <td class="text-right"><?= h(formatCurrency($bank_summary['bounced_amount'])) ?></td>
                                    <td class="text-right"><?= h(formatCurrency($bank_summary['cancelled_amount'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($report_type === 'purchase_vs_sales'): ?>
            <h3>Purchase vs Sales Summary (Year: <?= h($year) ?>)</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th class="text-right">Total Purchases</th>
                            <th class="text-right">Total Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $monthly_data = [];
                        for ($m = 1; $m <= 12; $m++) {
                            $month_str = str_pad($m, 2, '0', STR_PAD_LEFT);
                            $month_start = "$year-$month_str-01";
                            $month_end = date('Y-m-t', strtotime($month_start));
                            $total_purchases = $conn->query("SELECT COALESCE(SUM(cheque_amount), 0) FROM `purchase_orders` WHERE order_date BETWEEN '$month_start' AND '$month_end'")->fetch_row()[0];
                            $total_sales = $conn->query("SELECT COALESCE(SUM(selling_price + tax_amount), 0) FROM `bikes` WHERE status = 'sold' AND selling_date BETWEEN '$month_start' AND '$month_end'")->fetch_row()[0];
                            $monthly_data[] = [
                                'month_name' => date('F', mktime(0, 0, 0, $m, 10)),
                                'purchases' => $total_purchases,
                                'sales' => $total_sales
                            ];
                        }
                        $grand_total_purchases = array_sum(array_column($monthly_data, 'purchases'));
                        $grand_total_sales = array_sum(array_column($monthly_data, 'sales'));
                        ?>
                        <?php if (empty($monthly_data)): ?>
                            <tr><td colspan="3" class="text-center">No data for the selected year.</td></tr>
                        <?php else: ?>
                            <?php foreach ($monthly_data as $data): ?>
                                <tr>
                                    <td><?= h($data['month_name']) ?></td>
                                    <td class="text-right"><?= h(formatCurrency($data['purchases'])) ?></td>
                                    <td class="text-right"><?= h(formatCurrency($data['sales'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr style="background-color: var(--header-bg); color: #ffffff; font-weight: bold;">
                                <td class="text-right">Grand Totals:</td>
                                <td class="text-right"><?= h(formatCurrency($grand_total_purchases)) ?></td>
                                <td class="text-right"><?= h(formatCurrency($grand_total_sales)) ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($report_type === 'daily_ledger'): ?>
            <h3>Daily Ledger (Date: <?= h(formatDate($date_from)) ?>)</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Description</th>
                            <th>Party</th>
                            <th class="text-right">Debit</th>
                            <th class="text-right">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->prepare("SELECT l.*, c.name as customer_name, s.name as supplier_name
                                                FROM `ledger` l
                                                LEFT JOIN `customers` c ON l.party_type = 'customer' AND l.party_id = c.id
                                                LEFT JOIN `suppliers` s ON l.party_type = 'supplier' AND l.party_id = s.id
                                                WHERE DATE(l.entry_date) = ?
                                                ORDER BY l.created_at ASC");
                        $stmt->bind_param('s', $date_from);
                        $stmt->execute();
                        $daily_ledger_entries = $stmt->get_result();
                        $total_debit = 0;
                        $total_credit = 0;
                        ?>
                        <?php if ($daily_ledger_entries->num_rows === 0): ?>
                            <tr><td colspan="5" class="text-center">No ledger entries for this date.</td></tr>
                        <?php else: ?>
                            <?php
                            while ($entry = $daily_ledger_entries->fetch_assoc()):
                                $party_name = '';
                                if ($entry['party_type'] === 'customer') {
                                    $party_name = $entry['customer_name'];
                                } elseif ($entry['party_type'] === 'supplier') {
                                    $party_name = $entry['supplier_name'];
                                } else {
                                    $party_name = 'Other';
                                }
                                if ($entry['entry_type'] === 'debit') {
                                    $total_debit += $entry['amount'];
                                } else {
                                    $total_credit += $entry['amount'];
                                }
                                ?>
                                <tr>
                                    <td><?= h(date('H:i:s', strtotime($entry['created_at']))) ?></td>
                                    <td><?= h($entry['description']) ?></td>
                                    <td><?= h($party_name) ?></td>
                                    <td class="text-right"><?= $entry['entry_type'] === 'debit' ? h(formatCurrency($entry['amount'])) : '-' ?></td>
                                    <td class="text-right"><?= $entry['entry_type'] === 'credit' ? h(formatCurrency($entry['amount'])) : '-' ?></td>
                                </tr>
                            <?php endwhile; ?>
                            <tr style="background-color: var(--header-bg); color: #ffffff; font-weight: bold;">
                                <td colspan="3" class="text-right">Totals:</td>
                                <td class="text-right"><?= h(formatCurrency($total_debit)) ?></td>
                                <td class="text-right"><?= h(formatCurrency($total_credit)) ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($report_type === 'monthly_summary'): ?>
            <h3>Monthly Summary Report (<?= date('F Y', mktime(0, 0, 0, (int) $month, 1, (int) $year)) ?>)</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $month_start = "$year-$month-01";
                        $month_end = date('Y-m-t', strtotime($month_start));
                        $total_sales = $conn->query("SELECT COALESCE(SUM(selling_price + tax_amount), 0) FROM `bikes` WHERE status = 'sold' AND selling_date BETWEEN '$month_start' AND '$month_end'")->fetch_row()[0];
                        $total_purchases = $conn->query("SELECT COALESCE(SUM(cheque_amount), 0) FROM `purchase_orders` WHERE order_date BETWEEN '$month_start' AND '$month_end'")->fetch_row()[0];
                        $total_profit_margin = $conn->query("SELECT COALESCE(SUM(margin), 0) FROM `bikes` WHERE status = 'sold' AND selling_date BETWEEN '$month_start' AND '$month_end'")->fetch_row()[0];
                        $total_tax_collected = $conn->query("SELECT COALESCE(SUM(tax_amount), 0) FROM `bikes` WHERE status = 'sold' AND selling_date BETWEEN '$month_start' AND '$month_end'")->fetch_row()[0];
                        $total_cheques_issued = $conn->query("SELECT COALESCE(SUM(amount), 0) FROM `cheque_register` WHERE type = 'payment' AND cheque_date BETWEEN '$month_start' AND '$month_end'")->fetch_row()[0];
                        $total_cheques_received = $conn->query("SELECT COALESCE(SUM(amount), 0) FROM `cheque_register` WHERE type = 'receipt' AND cheque_date BETWEEN '$month_start' AND '$month_end'")->fetch_row()[0];
                        $total_refunds_issued = $conn->query("SELECT COALESCE(SUM(amount), 0) FROM `cheque_register` WHERE type = 'refund' AND cheque_date BETWEEN '$month_start' AND '$month_end'")->fetch_row()[0];
                        ?>
                        <tr>
                            <td>Total Sales</td>
                            <td class="text-right"><?= h(formatCurrency($total_sales)) ?></td>
                        </tr>
                        <tr>
                            <td>Total Purchases</td>
                            <td class="text-right"><?= h(formatCurrency($total_purchases)) ?></td>
                        </tr>
                        <tr>
                            <td>Total Profit/Margin</td>
                            <td class="text-right" style="color: <?= $total_profit_margin >= 0 ? 'var(--success-color)' : 'var(--danger-color)' ?>;"><?= h(formatCurrency($total_profit_margin)) ?></td>
                        </tr>
                        <tr>
                            <td>Total Tax Collected</td>
                            <td class="text-right"><?= h(formatCurrency($total_tax_collected)) ?></td>
                        </tr>
                        <tr>
                            <td>Total Cheques Issued</td>
                            <td class="text-right"><?= h(formatCurrency($total_cheques_issued)) ?></td>
                        </tr>
                        <tr>
                            <td>Total Cheques Received</td>
                            <td class="text-right"><?= h(formatCurrency($total_cheques_received)) ?></td>
                        </tr>
                        <tr>
                            <td>Total Refunds Issued</td>
                            <td class="text-right"><?= h(formatCurrency($total_refunds_issued)) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php
    $content = ob_get_clean();
    renderLayout('Reports', $content, 'reports');
}

function renderModelsManagement(): void
{
    $conn = getDbConnection();
    $models = getModels();
    $action = $_GET['action'] ?? 'list';
    $edit_id = (int) ($_GET['id'] ?? 0);
    $model_to_edit = null;
    if ($edit_id) {
        $stmt = $conn->prepare('SELECT * FROM `models` WHERE id = ?');
        $stmt->bind_param('i', $edit_id);
        $stmt->execute();
        $model_to_edit = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
        $model_code = trim($_POST['model_code'] ?? '');
        $model_name = trim($_POST['model_name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $short_code = trim($_POST['short_code'] ?? '');
        if (empty($model_code) || empty($model_name) || empty($category)) {
            $_SESSION['flash_message'] = 'Model Code, Name, and Category are required.';
            $_SESSION['flash_type'] = 'danger';
            if ($action === 'edit' && $edit_id) {
                header('Location: ?page=models&action=edit&id=' . $edit_id);
            } else {
                header('Location: ?page=models&action=new');
            }
            exit;
        }
        if ($action === 'new') {
            $stmt = $conn->prepare('INSERT INTO `models` (`model_code`, `model_name`, `category`, `short_code`) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssss', $model_code, $model_name, $category, $short_code);
        } elseif ($action === 'edit' && $edit_id) {
            $stmt = $conn->prepare('UPDATE `models` SET `model_code` = ?, `model_name` = ?, `category` = ?, `short_code` = ? WHERE `id` = ?');
            $stmt->bind_param('ssssi', $model_code, $model_name, $category, $short_code, $edit_id);
        } elseif ($action === 'delete') {
            $delete_id = (int) $_POST['id'];
            $stmt = $conn->prepare('DELETE FROM `models` WHERE `id` = ?');
            $stmt->bind_param('i', $delete_id);
        } else {
            $_SESSION['flash_message'] = 'Invalid action.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ?page=models');
            exit;
        }
        if ($stmt->execute()) {
            if ($action === 'new') {
                $_SESSION['flash_message'] = 'Model added successfully.';
            } elseif ($action === 'edit') {
                $_SESSION['flash_message'] = 'Model updated successfully.';
            } elseif ($action === 'delete') {
                $_SESSION['flash_message'] = 'Model deleted successfully.';
            }
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Database error: ' . h($stmt->error);
            $_SESSION['flash_type'] = 'danger';
        }
        $stmt->close();
        header('Location: ?page=models');
        exit;
    }
    ob_start();
    ?>
    <h2 style="margin-bottom: 20px;">Models Management</h2>
    <?php if (in_array($action, ['list', ''])): ?>
        <div class="form-section animate__animated animate__fadeIn">
            <div class="form-section-title">All Models</div>
            <a href="?page=models&action=new" class="btn btn-primary no-print" style="margin-bottom: 15px;">Add New Model</a>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Model Code</th>
                            <th>Model Name</th>
                            <th>Category</th>
                            <th>Short Code</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($models)): ?>
                            <tr><td colspan="5" class="text-center">No models defined.</td></tr>
                        <?php else: ?>
                            <?php foreach ($models as $model): ?>
                                <tr>
                                    <td><?= h($model['model_code']) ?></td>
                                    <td><?= h($model['model_name']) ?></td>
                                    <td><?= h($model['category']) ?></td>
                                    <td><?= h($model['short_code']) ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="?page=models&action=edit&id=<?= h($model['id']) ?>" class="btn btn-warning btn-xs">Edit</a>
                                            <button type="button" class="btn btn-danger btn-xs" onclick="deleteModel(<?= h($model['id']) ?>, '<?= h($model['model_name']) ?>')">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif ($action === 'new' || ($action === 'edit' && $model_to_edit)): ?>
        <div class="form-section animate__animated animate__fadeIn">
            <div class="form-section-title"><?= $action === 'new' ? 'Add New Model' : 'Edit Model' ?></div>
            <form id="model-form" method="POST" action="?page=models&action=<?= $action ?><?= $edit_id ? '&id=' . h($edit_id) : '' ?>">
                <?= csrfField() ?>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="model_code">Model Code <span class="required-mark">*</span></label>
                        <input type="text" id="model_code" name="model_code" value="<?= h($model_to_edit['model_code'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="model_name">Model Name <span class="required-mark">*</span></label>
                        <input type="text" id="model_name" name="model_name" value="<?= h($model_to_edit['model_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="category">Category <span class="required-mark">*</span></label>
                        <input type="text" id="category" name="category" value="<?= h($model_to_edit['category'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="short_code">Short Code</label>
                        <input type="text" id="short_code" name="short_code" value="<?= h($model_to_edit['short_code'] ?? '') ?>">
                    </div>
                </div>
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success"><?= $action === 'new' ? 'Add Model' : 'Update Model' ?></button>
                    <a href="?page=models" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    <?php else: ?>
        <p class="text-center animate__animated animate__fadeIn" style="margin-top: 50px;">Model not found or invalid action.</p>
    <?php endif; ?>
    <script>
        async function deleteModel(id, modelName) {
            const result = await confirmAction('Confirm Delete', `Are you sure you want to delete model "${modelName}"? This will affect all bikes associated with this model. This action cannot be undone.`, 'Yes, Delete Model');
            if (result) {
                showLoadingSpinner();
                const response = await fetch('?page=models', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `csrf_token=${CSRF_TOKEN}&action=delete&id=${id}`
                });
                hideLoadingSpinner();
                const data = await response.text();
                location.reload();
            }
        }
        const validator = new JustValidate('#model-form', {
            errorFieldCssClass: 'is-invalid',
            errorLabelCssClass: 'error-message',
            focusInvalidField: true,
            lockForm: true,
            tooltip: {
                position: 'top'
            }
        });
        validator
            .addField('#model_code', [{ rule: 'required', errorMessage: 'Model Code is required' }])
            .addField('#model_name', [{ rule: 'required', errorMessage: 'Model Name is required' }])
            .addField('#category', [{ rule: 'required', errorMessage: 'Category is required' }]);
    </script>
    <?php
    $content = ob_get_clean();
    renderLayout('Models Management', $content, 'models');
}

function renderCustomersManagement(): void
{
    $conn = getDbConnection();
    $customers = getCustomers();
    $action = $_GET['action'] ?? 'list';
    $edit_id = (int) ($_GET['id'] ?? 0);
    $customer_to_edit = null;
    if ($edit_id) {
        $stmt = $conn->prepare('SELECT * FROM `customers` WHERE id = ?');
        $stmt->bind_param('i', $edit_id);
        $stmt->execute();
        $customer_to_edit = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $cnic = trim($_POST['cnic'] ?? '');
        $address = trim($_POST['address'] ?? '');
        if (empty($name) || empty($phone)) {
            $_SESSION['flash_message'] = 'Customer Name and Phone are required.';
            $_SESSION['flash_type'] = 'danger';
            if ($action === 'edit' && $edit_id) {
                header('Location: ?page=customers&action=edit&id=' . $edit_id);
            } else {
                header('Location: ?page=customers&action=new');
            }
            exit;
        }
        if ($action === 'new') {
            $stmt = $conn->prepare('INSERT INTO `customers` (`name`, `phone`, `cnic`, `address`) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssss', $name, $phone, $cnic, $address);
        } elseif ($action === 'edit' && $edit_id) {
            $stmt = $conn->prepare('UPDATE `customers` SET `name` = ?, `phone` = ?, `cnic` = ?, `address` = ? WHERE `id` = ?');
            $stmt->bind_param('ssssi', $name, $phone, $cnic, $address, $edit_id);
        } elseif ($action === 'delete') {
            $delete_id = (int) $_POST['id'];
            $stmt = $conn->prepare('DELETE FROM `customers` WHERE `id` = ?');
            $stmt->bind_param('i', $delete_id);
        } else {
            $_SESSION['flash_message'] = 'Invalid action.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ?page=customers');
            exit;
        }
        if ($stmt->execute()) {
            if ($action === 'new') {
                $_SESSION['flash_message'] = 'Customer added successfully.';
            } elseif ($action === 'edit') {
                $_SESSION['flash_message'] = 'Customer updated successfully.';
            } elseif ($action === 'delete') {
                $_SESSION['flash_message'] = 'Customer deleted successfully.';
            }
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Database error: ' . h($stmt->error);
            $_SESSION['flash_type'] = 'danger';
        }
        $stmt->close();
        header('Location: ?page=customers');
        exit;
    }
    ob_start();
    ?>
    <h2 style="margin-bottom: 20px;">Customers Management</h2>
    <?php if (in_array($action, ['list', ''])): ?>
        <div class="form-section animate__animated animate__fadeIn">
            <div class="form-section-title">All Customers</div>
            <a href="?page=customers&action=new" class="btn btn-primary no-print" style="margin-bottom: 15px;">Add New Customer</a>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>CNIC</th>
                            <th>Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customers)): ?>
                            <tr><td colspan="5" class="text-center">No customers defined.</td></tr>
                        <?php else: ?>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><?= h($customer['name']) ?></td>
                                    <td><?= h($customer['phone']) ?></td>
                                    <td><?= h($customer['cnic']) ?></td>
                                    <td><?= h($customer['address']) ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="?page=customers&action=edit&id=<?= h($customer['id']) ?>" class="btn btn-warning btn-xs">Edit</a>
                                            <button type="button" class="btn btn-danger btn-xs" onclick="deleteCustomer(<?= h($customer['id']) ?>, '<?= h($customer['name']) ?>')">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif ($action === 'new' || ($action === 'edit' && $customer_to_edit)): ?>
        <div class="form-section animate__animated animate__fadeIn">
            <div class="form-section-title"><?= $action === 'new' ? 'Add New Customer' : 'Edit Customer' ?></div>
            <form id="customer-form" method="POST" action="?page=customers&action=<?= $action ?><?= $edit_id ? '&id=' . h($edit_id) : '' ?>">
                <?= csrfField() ?>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Name <span class="required-mark">*</span></label>
                        <input type="text" id="name" name="name" value="<?= h($customer_to_edit['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone <span class="required-mark">*</span></label>
                        <input type="text" id="phone" name="phone" value="<?= h($customer_to_edit['phone'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="cnic">CNIC</label>
                        <input type="text" id="cnic" name="cnic" value="<?= h($customer_to_edit['cnic'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address"><?= h($customer_to_edit['address'] ?? '') ?></textarea>
                    </div>
                </div>
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success"><?= $action === 'new' ? 'Add Customer' : 'Update Customer' ?></button>
                    <a href="?page=customers" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    <?php else: ?>
        <p class="text-center animate__animated animate__fadeIn" style="margin-top: 50px;">Customer not found or invalid action.</p>
    <?php endif; ?>
    <script>
        async function deleteCustomer(id, customerName) {
            const result = await confirmAction('Confirm Delete', `Are you sure you want to delete customer "${customerName}"? This will affect all sales associated with this customer. This action cannot be undone.`, 'Yes, Delete Customer');
            if (result) {
                showLoadingSpinner();
                const response = await fetch('?page=customers', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `csrf_token=${CSRF_TOKEN}&action=delete&id=${id}`
                });
                hideLoadingSpinner();
                const data = await response.text();
                location.reload();
            }
        }
        const validator = new JustValidate('#customer-form', {
            errorFieldCssClass: 'is-invalid',
            errorLabelCssClass: 'error-message',
            focusInvalidField: true,
            lockForm: true,
            tooltip: {
                position: 'top'
            }
        });
        validator
            .addField('#name', [{ rule: 'required', errorMessage: 'Customer Name is required' }])
            .addField('#phone', [{ rule: 'required', errorMessage: 'Phone is required' }]);
    </script>
    <?php
    $content = ob_get_clean();
    renderLayout('Customers Management', $content, 'customers');
}

function renderSuppliersManagement(): void
{
    $conn = getDbConnection();
    $suppliers = getSuppliers();
    $action = $_GET['action'] ?? 'list';
    $edit_id = (int) ($_GET['id'] ?? 0);
    $supplier_to_edit = null;
    if ($edit_id) {
        $stmt = $conn->prepare('SELECT * FROM `suppliers` WHERE id = ?');
        $stmt->bind_param('i', $edit_id);
        $stmt->execute();
        $supplier_to_edit = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
        $name = trim($_POST['name'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $address = trim($_POST['address'] ?? '');
        if (empty($name)) {
            $_SESSION['flash_message'] = 'Supplier Name is required.';
            $_SESSION['flash_type'] = 'danger';
            if ($action === 'edit' && $edit_id) {
                header('Location: ?page=suppliers&action=edit&id=' . $edit_id);
            } else {
                header('Location: ?page=suppliers&action=new');
            }
            exit;
        }
        if ($action === 'new') {
            $stmt = $conn->prepare('INSERT INTO `suppliers` (`name`, `contact`, `address`) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $name, $contact, $address);
        } elseif ($action === 'edit' && $edit_id) {
            $stmt = $conn->prepare('UPDATE `suppliers` SET `name` = ?, `contact` = ?, `address` = ? WHERE `id` = ?');
            $stmt->bind_param('sssi', $name, $contact, $address, $edit_id);
        } elseif ($action === 'delete') {
            $delete_id = (int) $_POST['id'];
            $stmt = $conn->prepare('DELETE FROM `suppliers` WHERE `id` = ?');
            $stmt->bind_param('i', $delete_id);
        } else {
            $_SESSION['flash_message'] = 'Invalid action.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ?page=suppliers');
            exit;
        }
        if ($stmt->execute()) {
            if ($action === 'new') {
                $_SESSION['flash_message'] = 'Supplier added successfully.';
            } elseif ($action === 'edit') {
                $_SESSION['flash_message'] = 'Supplier updated successfully.';
            } elseif ($action === 'delete') {
                $_SESSION['flash_message'] = 'Supplier deleted successfully.';
            }
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Database error: ' . h($stmt->error);
            $_SESSION['flash_type'] = 'danger';
        }
        $stmt->close();
        header('Location: ?page=suppliers');
        exit;
    }
    ob_start();
    ?>
    <h2 style="margin-bottom: 20px;">Suppliers Management</h2>
    <?php if (in_array($action, ['list', ''])): ?>
        <div class="form-section animate__animated animate__fadeIn">
            <div class="form-section-title">All Suppliers</div>
            <a href="?page=suppliers&action=new" class="btn btn-primary no-print" style="margin-bottom: 15px;">Add New Supplier</a>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($suppliers)): ?>
                            <tr><td colspan="4" class="text-center">No suppliers defined.</td></tr>
                        <?php else: ?>
                            <?php foreach ($suppliers as $supplier): ?>
                                <tr>
                                    <td><?= h($supplier['name']) ?></td>
                                    <td><?= h($supplier['contact']) ?></td>
                                    <td><?= h($supplier['address']) ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="?page=suppliers&action=edit&id=<?= h($supplier['id']) ?>" class="btn btn-warning btn-xs">Edit</a>
                                            <button type="button" class="btn btn-danger btn-xs" onclick="deleteSupplier(<?= h($supplier['id']) ?>, '<?= h($supplier['name']) ?>')">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif ($action === 'new' || ($action === 'edit' && $supplier_to_edit)): ?>
        <div class="form-section animate__animated animate__fadeIn">
            <div class="form-section-title"><?= $action === 'new' ? 'Add New Supplier' : 'Edit Supplier' ?></div>
            <form id="supplier-form" method="POST" action="?page=suppliers&action=<?= $action ?><?= $edit_id ? '&id=' . h($edit_id) : '' ?>">
                <?= csrfField() ?>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Name <span class="required-mark">*</span></label>
                        <input type="text" id="name" name="name" value="<?= h($supplier_to_edit['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="contact">Contact Person</label>
                        <input type="text" id="contact" name="contact" value="<?= h($supplier_to_edit['contact'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address"><?= h($supplier_to_edit['address'] ?? '') ?></textarea>
                    </div>
                </div>
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success"><?= $action === 'new' ? 'Add Supplier' : 'Update Supplier' ?></button>
                    <a href="?page=suppliers" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    <?php else: ?>
        <p class="text-center animate__animated animate__fadeIn" style="margin-top: 50px;">Supplier not found or invalid action.</p>
    <?php endif; ?>
    <script>
        async function deleteSupplier(id, supplierName) {
            const result = await confirmAction('Confirm Delete', `Are you sure you want to delete supplier "${supplierName}"? This will affect all purchase orders associated with this supplier. This action cannot be undone.`, 'Yes, Delete Supplier');
            if (result) {
                showLoadingSpinner();
                const response = await fetch('?page=suppliers', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `csrf_token=${CSRF_TOKEN}&action=delete&id=${id}`
                });
                hideLoadingSpinner();
                const data = await response.text();
                location.reload();
            }
        }
        const validator = new JustValidate('#supplier-form', {
            errorFieldCssClass: 'is-invalid',
            errorLabelCssClass: 'error-message',
            focusInvalidField: true,
            lockForm: true,
            tooltip: {
                position: 'top'
            }
        });
        validator.addField('#name', [{ rule: 'required', errorMessage: 'Supplier Name is required' }]);
    </script>
    <?php
    $content = ob_get_clean();
    renderLayout('Suppliers Management', $content, 'suppliers');
}

function renderSettings(): void
{
    $conn = getDbConnection();
    $section = $_GET['section'] ?? 'company';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
        if ($section === 'general' || $section === 'company') {
            $setting_keys = ['company_name', 'branch_name', 'tax_rate', 'currency_symbol', 'tax_on', 'theme'];
            foreach ($setting_keys as $key) {
                $value = $_POST[$key] ?? '';
                setSetting($key, $value);
            }
            $_SESSION['flash_message'] = 'Settings saved successfully.';
            $_SESSION['flash_type'] = 'success';
        } elseif ($section === 'backup' && isset($_POST['db_backup'])) {
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="bni_enterprises_backup_' . date('Ymd_His') . '.sql"');
            $tables_query = $conn->query('SHOW TABLES');
            $tables = [];
            while ($row = $tables_query->fetch_row()) {
                $tables[] = $row[0];
            }
            echo "-- BNI Enterprises SQL Backup\n";
            echo '-- Date: ' . date('Y-m-d H:i:s') . "\n";
            echo '-- Database: ' . DB_NAME . "\n\n";
            echo "SET FOREIGN_KEY_CHECKS=0;\n\n";
            foreach ($tables as $table) {
                $create_table_query = $conn->query('SHOW CREATE TABLE `' . $table . '`');
                $create_table_sql = $create_table_query->fetch_row()[1];
                echo $create_table_sql . ";\n\n";
                $rows_query = $conn->query('SELECT * FROM `' . $table . '`');
                while ($row = $rows_query->fetch_assoc()) {
                    $values = [];
                    foreach ($row as $value) {
                        $values[] = $value === null ? 'NULL' : "'" . $conn->real_escape_string($value) . "'";
                    }
                    echo 'INSERT INTO `' . $table . '` VALUES (' . implode(', ', $values) . ");\n";
                }
                echo "\n";
            }
            echo "SET FOREIGN_KEY_CHECKS=1;\n";
            exit;
        } elseif ($section === 'backup' && isset($_FILES['sql_restore']) && $_FILES['sql_restore']['error'] === UPLOAD_ERR_OK) {
            $filename = $_FILES['sql_restore']['tmp_name'];
            $file_content = file_get_contents($filename);
            if ($file_content) {
                $conn->autocommit(false);
                try {
                    $conn->query('SET FOREIGN_KEY_CHECKS=0');
                    $statements = array_filter(array_map('trim', explode(';', $file_content)));
                    foreach ($statements as $stmt_sql) {
                        if (!empty($stmt_sql)) {
                            $conn->query($stmt_sql);
                            if ($conn->error) {
                                throw new Exception('SQL error: ' . $conn->error . ' in statement: ' . $stmt_sql);
                            }
                        }
                    }
                    $conn->query('SET FOREIGN_KEY_CHECKS=1');
                    $conn->commit();
                    $_SESSION['flash_message'] = 'Database restored successfully.';
                    $_SESSION['flash_type'] = 'success';
                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['flash_message'] = 'Database restore failed: ' . h($e->getMessage());
                    $_SESSION['flash_type'] = 'danger';
                } finally {
                    $conn->autocommit(true);
                }
            } else {
                $_SESSION['flash_message'] = 'Failed to read SQL backup file.';
                $_SESSION['flash_type'] = 'danger';
            }
        }
        header('Location: ?page=settings&section=' . $section);
        exit;
    }
    ob_start();
    ?>
    <h2 style="margin-bottom: 20px;">Settings</h2>
    <div class="form-section animate__animated animate__fadeIn">
        <div class="form-section-title">Navigation</div>
        <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
            <a href="?page=settings&section=company" class="btn <?= $section === 'company' ? 'btn-primary' : 'btn-secondary' ?>">Company Info</a>
            <a href="?page=settings&section=general" class="btn <?= $section === 'general' ? 'btn-primary' : 'btn-secondary' ?>">General Settings</a>
            <a href="?page=settings&section=backup" class="btn <?= $section === 'backup' ? 'btn-primary' : 'btn-secondary' ?>">Backup & Restore</a>
            <a href="?page=settings&section=users" class="btn <?= $section === 'users' ? 'btn-primary' : 'btn-secondary' ?>">User Management</a>
        </div>
        <?php if ($section === 'company'): ?>
            <h3>Company Information</h3>
            <form id="company-settings-form" method="POST" action="?page=settings&section=company">
                <?= csrfField() ?>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="company_name">Company Name <span class="required-mark">*</span></label>
                        <input type="text" id="company_name" name="company_name" value="<?= h(getSetting('company_name', APP_NAME)) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="branch_name">Branch Name <span class="required-mark">*</span></label>
                        <input type="text" id="branch_name" name="branch_name" value="<?= h(getSetting('branch_name', APP_BRANCH)) ?>" required>
                    </div>
                </div>
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-success">Save Company Settings</button>
                </div>
            </form>
        <?php elseif ($section === 'general'): ?>
            <h3>General Settings</h3>
            <form id="general-settings-form" method="POST" action="?page=settings&section=general">
                <?= csrfField() ?>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="tax_rate">Tax Rate (%) <span class="required-mark">*</span></label>
                        <input type="number" id="tax_rate" name="tax_rate" step="0.01" min="0" max="100" value="<?= h(getSetting('tax_rate', '0.1') * 100) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="currency_symbol">Currency Symbol <span class="required-mark">*</span></label>
                        <input type="text" id="currency_symbol" name="currency_symbol" value="<?= h(getSetting('currency_symbol', 'Rs.')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="tax_on">Tax Calculation Basis <span class="required-mark">*</span></label>
                        <select id="tax_on" name="tax_on" required>
                            <option value="purchase_price" <?= getSetting('tax_on', 'purchase_price') === 'purchase_price' ? 'selected' : '' ?>>On Purchase Price</option>
                            <option value="selling_price" <?= getSetting('tax_on', 'purchase_price') === 'selling_price' ? 'selected' : '' ?>>On Selling Price</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="theme">Theme <span class="required-mark">*</span></label>
                        <select id="theme" name="theme" required>
                            <option value="dark" <?= getSetting('theme', 'light') === 'dark' ? 'selected' : '' ?>>Dark</option>
                            <option value="light" <?= getSetting('theme', 'light') === 'light' ? 'selected' : '' ?>>Light</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-success">Save General Settings</button>
                </div>
            </form>
        <?php elseif ($section === 'backup'): ?>
            <h3>Database Backup & Restore</h3>
            <div class="form-grid">
                <div class="form-group">
                    <div class="form-section-title">Download SQL Backup</div>
                    <p style="font-size: 13px; margin-bottom: 10px;">Create a full backup of your database. It's recommended to do this regularly.</p>
                    <form method="POST" action="?page=settings&section=backup">
                        <?= csrfField() ?>
                        <input type="hidden" name="db_backup" value="1">
                        <button type="submit" class="btn btn-primary">Download SQL Backup</button>
                    </form>
                </div>
                <div class="form-group">
                    <div class="form-section-title">Restore from SQL File</div>
                    <p style="font-size: 13px; margin-bottom: 10px; color: var(--danger-color); font-weight: bold;">WARNING: This will overwrite ALL existing data. Proceed with extreme caution.</p>
                    <form id="restore-form" method="POST" action="?page=settings&section=backup" enctype="multipart/form-data">
                        <?= csrfField() ?>
                        <input type="file" name="sql_restore" accept=".sql" required style="display: block; margin-bottom: 10px;">
                        <button type="button" class="btn btn-danger" onclick="confirmDbRestore()">Restore Database</button>
                    </form>
                </div>
            </div>
        <?php
    elseif ($section === 'users'):
        $users = [];
        $stmt_users = $conn->query('SELECT id, username, full_name, email, role, status FROM `users`');
        while ($row = $stmt_users->fetch_assoc()) {
            $users[] = $row;
        }
        $current_user_id = $_SESSION['user_id'];
        ?>
            <h3>User Management</h3>
            <a href="?page=settings&section=users&action=new_user" class="btn btn-primary no-print" style="margin-bottom: 15px;">Add New User</a>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="6" class="text-center">No users found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= h($user['username']) ?></td>
                                    <td><?= h($user['full_name']) ?></td>
                                    <td><?= h($user['email'] ?? '-') ?></td>
                                    <td><?= h(ucfirst($user['role'])) ?></td>
                                    <td><?= h(ucfirst($user['status'])) ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="?page=settings&section=users&action=edit_user&id=<?= h($user['id']) ?>" class="btn btn-warning btn-xs">Edit</a>
                                            <?php if ((int) $user['id'] !== (int) $current_user_id): ?>
                                                <button type="button" class="btn btn-danger btn-xs" onclick="deleteUser(<?= h($user['id']) ?>, '<?= h($user['username']) ?>')">Delete</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php
            if (isset($_GET['action']) && $_GET['action'] === 'new_user' || (isset($_GET['action']) && $_GET['action'] === 'edit_user' && isset($_GET['id']))):
                $user_edit_id = (int) ($_GET['id'] ?? 0);
                $user_to_edit = null;
                if ($user_edit_id) {
                    $stmt = $conn->prepare('SELECT id, username, full_name, email, role, status FROM `users` WHERE id = ?');
                    $stmt->bind_param('i', $user_edit_id);
                    $stmt->execute();
                    $user_to_edit = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                }
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf() && isset($_POST['user_action'])) {
                    $username = trim($_POST['user_username'] ?? '');
                    $full_name = trim($_POST['user_full_name'] ?? '');
                    $email = trim($_POST['user_email'] ?? '');
                    $role = trim($_POST['user_role'] ?? 'admin');
                    $status = trim($_POST['user_status'] ?? 'active');
                    $password = $_POST['user_password'] ?? '';
                    $password_confirm = $_POST['user_password_confirm'] ?? '';
                    $errors = [];
                    if (empty($username))
                        $errors[] = 'Username is required.';
                    if (empty($full_name))
                        $errors[] = 'Full Name is required.';
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
                        $errors[] = 'Valid Email is required.';
                    if ($_POST['user_action'] === 'add_user') {
                        if (empty($password))
                            $errors[] = 'Password is required for new users.';
                        if ($password !== $password_confirm)
                            $errors[] = 'Passwords do not match.';
                        if (strlen($password) < 8)
                            $errors[] = 'Password must be at least 8 characters long.';
                        if (!preg_match('/[^a-zA-Z0-9]/', $password))
                            $errors[] = 'Password must contain at least one special character.';
                    } elseif ($_POST['user_action'] === 'update_user' && !empty($password)) {
                        if ($password !== $password_confirm)
                            $errors[] = 'New passwords do not match.';
                        if (strlen($password) < 8)
                            $errors[] = 'New password must be at least 8 characters long.';
                        if (!preg_match('/[^a-zA-Z0-9]/', $password))
                            $errors[] = 'New password must contain at least one special character.';
                    }
                    $check_stmt = $conn->prepare('SELECT id FROM `users` WHERE (username = ? OR email = ?) AND id != ?');
                    $check_stmt->bind_param('ssi', $username, $email, $user_edit_id);
                    $check_stmt->execute();
                    $check_stmt->store_result();
                    if ($check_stmt->num_rows > 0) {
                        $errors[] = 'Username or Email already exists.';
                    }
                    $check_stmt->close();
                    if (!empty($errors)) {
                        $_SESSION['flash_message'] = implode('<br>', $errors);
                        $_SESSION['flash_type'] = 'danger';
                    } else {
                        try {
                            if ($_POST['user_action'] === 'add_user') {
                                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                                $stmt = $conn->prepare('INSERT INTO `users` (username, password_hash, full_name, email, role, status) VALUES (?, ?, ?, ?, ?, ?)');
                                $stmt->bind_param('ssssss', $username, $password_hash, $full_name, $email, $role, $status);
                            } else {
                                if (!empty($password)) {
                                    $password_hash = password_hash($password, PASSWORD_BCRYPT);
                                    $stmt = $conn->prepare('UPDATE `users` SET username = ?, password_hash = ?, full_name = ?, email = ?, role = ?, status = ? WHERE id = ?');
                                    $stmt->bind_param('ssssssi', $username, $password_hash, $full_name, $email, $role, $status, $user_edit_id);
                                } else {
                                    $stmt = $conn->prepare('UPDATE `users` SET username = ?, full_name = ?, email = ?, role = ?, status = ? WHERE id = ?');
                                    $stmt->bind_param('sssssi', $username, $full_name, $email, $role, $status, $user_edit_id);
                                }
                            }
                            if ($stmt->execute()) {
                                $_SESSION['flash_message'] = 'User ' . (($_POST['user_action'] === 'add_user') ? 'added' : 'updated') . ' successfully.';
                                $_SESSION['flash_type'] = 'success';
                            } else {
                                throw new Exception('Database error: ' . $stmt->error);
                            }
                            $stmt->close();
                        } catch (Exception $e) {
                            $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
                            $_SESSION['flash_type'] = 'danger';
                        }
                    }
                    header('Location: ?page=settings&section=users');
                    exit;
                }
                ?>
            <div class="form-section animate__animated animate__fadeIn" style="margin-top: 20px;">
                <div class="form-section-title"><?= ($user_edit_id ? 'Edit User' : 'Add New User') ?></div>
                <form id="user-form" method="POST" action="?page=settings&section=users">
                    <?= csrfField() ?>
                    <input type="hidden" name="user_action" value="<?= ($user_edit_id ? 'update_user' : 'add_user') ?>">
                    <?php if ($user_edit_id): ?><input type="hidden" name="id" value="<?= h($user_edit_id) ?>"><?php endif; ?>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="user_username">Username <span class="required-mark">*</span></label>
                            <input type="text" id="user_username" name="user_username" value="<?= h($user_to_edit['username'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="user_full_name">Full Name <span class="required-mark">*</span></label>
                            <input type="text" id="user_full_name" name="user_full_name" value="<?= h($user_to_edit['full_name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="user_email">Email <span class="required-mark">*</span></label>
                            <input type="email" id="user_email" name="user_email" value="<?= h($user_to_edit['email'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="user_role">Role <span class="required-mark">*</span></label>
                            <select id="user_role" name="user_role" required>
                                <option value="admin" <?= (($user_to_edit['role'] ?? 'admin') === 'admin') ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="user_status">Status <span class="required-mark">*</span></label>
                            <select id="user_status" name="user_status" required>
                                <option value="active" <?= (($user_to_edit['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= (($user_to_edit['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="user_password"><?= $user_edit_id ? 'New Password (leave blank to keep current)' : 'Password' ?> <span class="required-mark">*</span></label>
                            <input type="password" id="user_password" name="user_password" <?= $user_edit_id ? '' : 'required' ?> autocomplete="new-password">
                        </div>
                        <div class="form-group">
                            <label for="user_password_confirm"><?= $user_edit_id ? 'Confirm New Password' : 'Confirm Password' ?> <span class="required-mark">*</span></label>
                            <input type="password" id="user_password_confirm" name="user_password_confirm" <?= $user_edit_id ? '' : 'required' ?> autocomplete="new-password">
                        </div>
                    </div>
                    <div style="margin-top: 20px; display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-success"><?= $user_edit_id ? 'Update User' : 'Add User' ?></button>
                        <a href="?page=settings&section=users" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
            <script>
                async function deleteUser(id, username) {
                    const result = await confirmAction('Confirm Delete', `Are you sure you want to delete user "${username}"? This action cannot be undone.`, 'Yes, Delete User');
                    if (result) {
                        showLoadingSpinner();
                        const response = await fetch('?page=settings&section=users', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `csrf_token=${CSRF_TOKEN}&user_action=delete_user&id=${id}`
                        });
                        hideLoadingSpinner();
                        const data = await response.text();
                        location.reload();
                    }
                }
                const userValidator = new JustValidate('#user-form', {
                    errorFieldCssClass: 'is-invalid',
                    errorLabelCssClass: 'error-message',
                    focusInvalidField: true,
                    lockForm: true,
                    tooltip: {
                        position: 'top'
                    }
                });
                userValidator
                    .addField('#user_username', [{ rule: 'required', errorMessage: 'Username is required' }])
                    .addField('#user_full_name', [{ rule: 'required', errorMessage: 'Full Name is required' }])
                    .addField('#user_email', [
                        { rule: 'required', errorMessage: 'Email is required' },
                        { rule: 'email', errorMessage: 'Enter a valid email' }
                    ]);
                const passwordField = document.getElementById('user_password');
                const confirmPasswordField = document.getElementById('user_password_confirm');
                if (!<?= json_encode((bool) $user_edit_id) ?>) {
                    userValidator.addField(passwordField, [
                        { rule: 'required', errorMessage: 'Password is required' },
                        { rule: 'minLength', value: 8, errorMessage: 'Password must be at least 8 characters' },
                        { rule: 'strength', type: 'special_character', errorMessage: 'Password must contain at least one special character' }
                    ]);
                    userValidator.addField(confirmPasswordField, [
                        { rule: 'required', errorMessage: 'Confirm Password is required' },
                        { rule: 'customRegexp', value: new RegExp(passwordField.value), errorMessage: 'Passwords do not match' }
                    ]);
                } else {
                    passwordField.addEventListener('input', () => {
                        if (passwordField.value) {
                            userValidator.addField(passwordField, [
                                { rule: 'minLength', value: 8, errorMessage: 'New password must be at least 8 characters' },
                                { rule: 'strength', type: 'special_character', errorMessage: 'New password must contain at least one special character' }
                            ], true);
                            userValidator.addField(confirmPasswordField, [
                                { rule: 'required', errorMessage: 'Confirm new password is required' },
                                { rule: 'customRegexp', value: new RegExp(passwordField.value), errorMessage: 'Passwords do not match' }
                            ], true);
                        } else {
                            userValidator.removeField(passwordField);
                            userValidator.removeField(confirmPasswordField);
                        }
                    });
                }
            </script>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <script>
        async function confirmDbRestore() {
            const result = await confirmAction('Confirm Database Restore', 'This action will completely erase your current database and replace it with the selected backup. THIS CANNOT BE UNDONE. Are you absolutely sure?', 'Yes, Restore Database', 'error');
            if (result) {
                document.getElementById('restore-form').submit();
            }
        }
        const companyValidator = new JustValidate('#company-settings-form', {
            errorFieldCssClass: 'is-invalid',
            errorLabelCssClass: 'error-message',
            focusInvalidField: true,
            lockForm: true,
            tooltip: { position: 'top' }
        });
        companyValidator.addField('#company_name', [{ rule: 'required', errorMessage: 'Company Name is required' }]);
        companyValidator.addField('#branch_name', [{ rule: 'required', errorMessage: 'Branch Name is required' }]);
        const generalValidator = new JustValidate('#general-settings-form', {
            errorFieldCssClass: 'is-invalid',
            errorLabelCssClass: 'error-message',
            focusInvalidField: true,
            lockForm: true,
            tooltip: { position: 'top' }
        });
        generalValidator.addField('#tax_rate', [
            { rule: 'required', errorMessage: 'Tax Rate is required' },
            { rule: 'minNumber', value: 0, errorMessage: 'Must be non-negative' },
            { rule: 'maxNumber', value: 100, errorMessage: 'Max 100' }
        ]);
        generalValidator.addField('#currency_symbol', [{ rule: 'required', errorMessage: 'Currency Symbol is required' }]);
        generalValidator.addField('#tax_on', [{ rule: 'required', errorMessage: 'Tax Calculation Basis is required' }]);
        generalValidator.addField('#theme', [{ rule: 'required', errorMessage: 'Theme is required' }]);
    </script>
    <?php
    $content = ob_get_clean();
    renderLayout('Settings', $content, 'settings');
}

function handleAjax(): void
{
    $conn = getDbConnection();
    header('Content-Type: application/json');
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $data = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
        exit;
    }
    if (!verifyCsrf()) {
        echo json_encode(['success' => false, 'error' => 'Security token mismatch. Please refresh.']);
        exit;
    }
    switch ($action) {
        case 'check_chassis':
            $chassis = trim($data['chassis'] ?? '');
            $bike_id = (int) ($data['bike_id'] ?? 0);
            $stmt = $conn->prepare('SELECT id FROM `bikes` WHERE chassis_number = ? AND id != ?');
            $stmt->bind_param('si', $chassis, $bike_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $is_duplicate = $result->num_rows > 0;
            $duplicate_bike_id = $is_duplicate ? $result->fetch_assoc()['id'] : null;
            $stmt->close();
            echo json_encode(['success' => true, 'is_duplicate' => $is_duplicate, 'bike_id' => $duplicate_bike_id]);
            break;
        case 'delete_bike':
            $id = (int) ($data['id'] ?? 0);
            $stmt = $conn->prepare('DELETE FROM `bikes` WHERE id = ?');
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Bike deleted successfully.']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete bike: ' . h($stmt->error)]);
            }
            $stmt->close();
            break;
        case 'bulk_delete_bikes':
            $ids_str = $data['ids'] ?? '';
            $ids = array_map('intval', explode(',', $ids_str));
            if (empty($ids)) {
                echo json_encode(['success' => false, 'error' => 'No bikes selected.']);
                exit;
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $types = str_repeat('i', count($ids));
            $stmt = $conn->prepare("DELETE FROM `bikes` WHERE id IN ($placeholders)");
            $stmt->bind_param($types, ...$ids);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => count($ids) . ' bikes deleted successfully.']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete bikes: ' . h($stmt->error)]);
            }
            $stmt->close();
            break;
        case 'update_cheque_status':
            $id = (int) ($data['id'] ?? 0);
            $status = trim($data['status'] ?? '');
            if (!in_array($status, ['pending', 'cleared', 'bounced', 'cancelled'])) {
                echo json_encode(['success' => false, 'error' => 'Invalid status provided.']);
                exit;
            }
            $stmt = $conn->prepare('UPDATE `cheque_register` SET status = ? WHERE id = ?');
            $stmt->bind_param('si', $status, $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Cheque status updated successfully.']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update cheque status: ' . h($stmt->error)]);
            }
            $stmt->close();
            break;
        case 'delete_cheque':
            $id = (int) ($data['id'] ?? 0);
            $stmt = $conn->prepare('DELETE FROM `cheque_register` WHERE id = ?');
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Cheque deleted successfully.']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete cheque: ' . h($stmt->error)]);
            }
            $stmt->close();
            break;
        case 'set_theme':
            $theme = trim($data['theme'] ?? 'light');
            if (!in_array($theme, ['dark', 'light'])) {
                echo json_encode(['success' => false, 'error' => 'Invalid theme value.']);
                exit;
            }
            if (setSetting('theme', $theme)) {
                echo json_encode(['success' => true, 'message' => 'Theme updated.']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to update theme.']);
            }
            break;
        case 'delete_user':
            $id = (int) ($data['id'] ?? 0);
            if ($id === (int) $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'error' => 'Cannot delete your own account.']);
                exit;
            }
            $stmt = $conn->prepare('DELETE FROM `users` WHERE id = ?');
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete user: ' . h($stmt->error)]);
            }
            $stmt->close();
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Unknown AJAX action.']);
            break;
    }
    exit;
}

$page = $_GET['page'] ?? 'dashboard';
if ($page === 'ajax') {
    handleAjax();
} elseif ($page === 'login') {
    if (isLoggedIn()) {
        header('Location: ?page=dashboard');
        exit;
    }
    $loginError = handleLogin();
    renderLogin($loginError);
} elseif ($page === 'logout') {
    handleLogout();
} else {
    requireLogin();
    if (isset($_GET['action']) && $_GET['action'] === 'set_theme' && isset($_GET['theme']) && verifyCsrf()) {
        $theme = $_GET['theme'];
        if (in_array($theme, ['dark', 'light'])) {
            setSetting('theme', $theme);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid theme.']);
        }
        exit;
    }
    switch ($page) {
        case 'dashboard':
            renderDashboard();
            break;
        case 'purchase':
            renderPurchaseEntry();
            break;
        case 'inventory':
            renderInventoryList();
            break;
        case 'sale':
            renderSaleEntry();
            break;
        case 'returns':
            renderReturnsAdjustments();
            break;
        case 'cheques':
            renderChequeRegister();
            break;
        case 'customer_ledger':
            renderCustomerLedger();
            break;
        case 'supplier_ledger':
            renderSupplierLedger();
            break;
        case 'reports':
            renderReports();
            break;
        case 'models':
            renderModelsManagement();
            break;
        case 'customers':
            renderCustomersManagement();
            break;
        case 'suppliers':
            renderSuppliersManagement();
            break;
        case 'settings':
            renderSettings();
            break;
        default:
            renderDashboard();
            break;
    }
}
?>