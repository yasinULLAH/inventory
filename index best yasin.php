<?php
session_start();
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'inventory_ms');
define('APP_NAME', 'InventoryPro');
define('APP_VERSION', '1.0.0');
define('CSRF_TOKEN_NAME', '_csrf_token');
define('SESSION_TIMEOUT', 2400);
define('SESSION_ABSOLUTE', 28800);

function getDB()
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $pdo->exec('USE `' . DB_NAME . '`');
            initializeDatabase($pdo);
        } catch (PDOException $e) {
            die("<div style='font-family:Arial;padding:20px;background:#fee;border:1px solid #f00;margin:20px;'>Database Connection Error: " . htmlspecialchars($e->getMessage()) . '</div>');
        }
    }
    return $pdo;
}

function initializeDatabase($pdo)
{
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('users', $tables))
        return;
    $sql = "
    CREATE TABLE IF NOT EXISTS `settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `setting_key` VARCHAR(100) NOT NULL UNIQUE,
        `setting_value` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;
    CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100) UNIQUE,
        `role` ENUM('Admin','Manager','Staff') NOT NULL DEFAULT 'Staff',
        `status` TINYINT(1) NOT NULL DEFAULT 1,
        `last_login` TIMESTAMP NULL,
        `login_attempts` INT NOT NULL DEFAULT 0,
        `locked_until` TIMESTAMP NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;
    CREATE TABLE IF NOT EXISTS `activity_log` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT,
        `username` VARCHAR(50),
        `action` VARCHAR(100) NOT NULL,
        `module` VARCHAR(50),
        `description` TEXT,
        `ip_address` VARCHAR(45),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB;
    CREATE TABLE IF NOT EXISTS `categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `parent_id` INT NULL,
        `description` TEXT,
        `status` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB;
    CREATE TABLE IF NOT EXISTS `suppliers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `company_name` VARCHAR(150) NOT NULL,
        `contact_person` VARCHAR(100),
        `email` VARCHAR(100),
        `phone` VARCHAR(30),
        `address` TEXT,
        `city` VARCHAR(80),
        `country` VARCHAR(80),
        `tax_id` VARCHAR(50),
        `payment_terms` VARCHAR(100),
        `status` TINYINT(1) NOT NULL DEFAULT 1,
        `notes` TEXT,
        `balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;
    CREATE TABLE IF NOT EXISTS `customers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(150) NOT NULL,
        `email` VARCHAR(100),
        `phone` VARCHAR(30),
        `address` TEXT,
        `city` VARCHAR(80),
        `country` VARCHAR(80),
        `customer_type` ENUM('Walk-in','Regular','Wholesale') NOT NULL DEFAULT 'Walk-in',
        `credit_limit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `current_balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `status` TINYINT(1) NOT NULL DEFAULT 1,
        `notes` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;
    CREATE TABLE IF NOT EXISTS `products` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `sku` VARCHAR(50) NOT NULL UNIQUE,
        `name` VARCHAR(200) NOT NULL,
        `description` TEXT,
        `category_id` INT,
        `brand` VARCHAR(100),
        `unit` VARCHAR(30) NOT NULL DEFAULT 'pcs',
        `purchase_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `selling_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `current_stock` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `min_stock` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `max_stock` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `location` VARCHAR(100),
        `barcode` VARCHAR(100),
        `status` TINYINT(1) NOT NULL DEFAULT 1,
        `image_url` VARCHAR(500),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
        INDEX `idx_sku` (`sku`),
        INDEX `idx_status` (`status`),
        INDEX `idx_category` (`category_id`)
    ) ENGINE=InnoDB;
    CREATE TABLE IF NOT EXISTS `purchase_orders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `po_number` VARCHAR(30) NOT NULL UNIQUE,
        `supplier_id` INT NOT NULL,
        `po_date` DATE NOT NULL,
        `expected_delivery` DATE,
        `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `tax_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        `discount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `payment_status` ENUM('Unpaid','Partial','Paid') NOT NULL DEFAULT 'Unpaid',
        `order_status` ENUM('Pending','Received','Partial','Cancelled') NOT NULL DEFAULT 'Pending',
        `notes` TEXT,
        `created_by` INT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`),
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB;
    CREATE TABLE IF NOT EXISTS `purchase_order_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `po_id` INT NOT NULL,
        `product_id` INT NOT NULL,
        `quantity` DECIMAL(15,2) NOT NULL,
        `received_qty` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `unit_price` DECIMAL(15,2) NOT NULL,
        `total_price` DECIMAL(15,2) NOT NULL,
        FOREIGN KEY (`po_id`) REFERENCES `purchase_orders`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
    ) ENGINE=InnoDB;
    CREATE TABLE IF NOT EXISTS `invoices` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `invoice_number` VARCHAR(30) NOT NULL UNIQUE,
        `customer_id` INT NOT NULL,
        `invoice_date` DATE NOT NULL,
        `due_date` DATE,
        `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `tax_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        `discount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `payment_status` ENUM('Unpaid','Partial','Paid') NOT NULL DEFAULT 'Unpaid',
        `payment_method` ENUM('Cash','Card','Bank Transfer','Credit') NOT NULL DEFAULT 'Cash',
        `notes` TEXT,
        `created_by` INT,
        `is_return` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`),
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB;
    CREATE TABLE IF NOT EXISTS `invoice_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `invoice_id` INT NOT NULL,
        `product_id` INT NOT NULL,
        `quantity` DECIMAL(15,2) NOT NULL,
        `unit_price` DECIMAL(15,2) NOT NULL,
        `discount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `total_price` DECIMAL(15,2) NOT NULL,
        FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
    ) ENGINE=InnoDB;
    CREATE TABLE IF NOT EXISTS `stock_adjustments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `reference_no` VARCHAR(30) NOT NULL UNIQUE,
        `product_id` INT NOT NULL,
        `adjustment_type` ENUM('Addition','Subtraction','Damage','Expired','Correction','Opening Stock') NOT NULL,
        `quantity` DECIMAL(15,2) NOT NULL,
        `previous_stock` DECIMAL(15,2) NOT NULL,
        `new_stock` DECIMAL(15,2) NOT NULL,
        `reason` TEXT,
        `status` ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
        `approved_by` INT NULL,
        `created_by` INT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`),
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
        FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB;
    CREATE TABLE IF NOT EXISTS `stock_transfers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `reference_no` VARCHAR(30) NOT NULL UNIQUE,
        `from_location` VARCHAR(100) NOT NULL,
        `to_location` VARCHAR(100) NOT NULL,
        `transfer_date` DATE NOT NULL,
        `status` ENUM('Pending','In Transit','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
        `notes` TEXT,
        `created_by` INT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB;
    CREATE TABLE IF NOT EXISTS `stock_transfer_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `transfer_id` INT NOT NULL,
        `product_id` INT NOT NULL,
        `quantity` DECIMAL(15,2) NOT NULL,
        FOREIGN KEY (`transfer_id`) REFERENCES `stock_transfers`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
    ) ENGINE=InnoDB;
    CREATE TABLE IF NOT EXISTS `login_attempts` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50),
        `ip_address` VARCHAR(45),
        `status` ENUM('Success','Failed') NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;
    ";
    foreach (explode(';', $sql) as $stmt) {
        $stmt = trim($stmt);
        if (!empty($stmt)) {
            $pdo->exec($stmt);
        }
    }
    $adminPass = password_hash('admin123', PASSWORD_BCRYPT);
    $pdo->exec("INSERT IGNORE INTO `users` (`username`,`password`,`full_name`,`email`,`role`) VALUES ('admin','$adminPass','System Administrator','admin@inventorypro.com','Admin')");
    $pdo->exec("INSERT IGNORE INTO `settings` (`setting_key`,`setting_value`) VALUES
        ('company_name','InventoryPro Ltd'),
        ('company_address','123 Business Avenue, Industrial Zone'),
        ('company_phone','+1 (555) 000-1234'),
        ('company_email','info@inventorypro.com'),
        ('company_website','www.inventorypro.com'),
        ('tax_number','TAX-987654321'),
        ('currency','USD'),
        ('currency_symbol','\$'),
        ('date_format','Y-m-d'),
        ('timezone','UTC'),
        ('low_stock_threshold','10'),
        ('items_per_page','20'),
        ('invoice_prefix','INV'),
        ('invoice_start','1000'),
        ('default_tax','0'),
        ('default_payment_terms','Net 30')
    ");
    $pdo->exec("INSERT IGNORE INTO `categories` (`id`,`name`,`parent_id`,`description`,`status`) VALUES
        (1,'Electronics',NULL,'Electronic devices and components',1),
        (2,'Office Supplies',NULL,'Stationery and office equipment',1),
        (3,'Consumables',NULL,'Daily use consumable products',1),
        (4,'Furniture',NULL,'Office and warehouse furniture',1),
        (5,'Laptops',1,'Notebook computers',1),
        (6,'Printers',1,'Printing devices',1)
    ");
    $pdo->exec("INSERT IGNORE INTO `suppliers` (`id`,`company_name`,`contact_person`,`email`,`phone`,`address`,`city`,`country`,`tax_id`,`payment_terms`,`status`) VALUES
        (1,'TechWorld Distributors','Ahmed Hassan','ahmed@techworld.com','+92-300-1234567','Plot 45, Tech Zone','Karachi','Pakistan','TW-001','Net 30',1),
        (2,'Global Office Mart','Sarah Johnson','sarah@officemaret.com','+1-555-987-6543','100 Commerce Blvd','New York','USA','GO-002','Net 15',1),
        (3,'Alpha Supplies Co.','Raj Patel','raj@alphasupplies.in','+91-98765-43210','22 Industrial Road','Mumbai','India','AS-003','Net 45',1),
        (4,'Prime Furniture House','Li Wei','liwei@primefurniture.cn','+86-10-12345678','88 Factory Street','Shanghai','China','PF-004','Net 60',1)
    ");
    $pdo->exec("INSERT IGNORE INTO `customers` (`id`,`name`,`email`,`phone`,`address`,`city`,`country`,`customer_type`,`credit_limit`,`current_balance`,`status`) VALUES
        (1,'Nexus Technologies Ltd','contact@nexustech.com','+92-21-1234567','Tower 3, Business District','Karachi','Pakistan','Wholesale',500000.00,125000.00,1),
        (2,'City Office Solutions','info@cityoffice.com','+1-555-111-2222','Suite 200, Park Ave','Chicago','USA','Regular',100000.00,15000.00,1),
        (3,'Retail Plus Store','retail@retailplus.com','+44-20-9876-5432','10 High Street','London','UK','Regular',50000.00,8500.00,1),
        (4,'Walk-In Customer','','','','','','Walk-in',0.00,0.00,1)
    ");
    $pdo->exec("INSERT IGNORE INTO `products` (`id`,`sku`,`name`,`description`,`category_id`,`brand`,`unit`,`purchase_price`,`selling_price`,`current_stock`,`min_stock`,`max_stock`,`location`,`barcode`,`status`) VALUES
        (1,'SKU-0001','Dell Latitude 5540 Laptop','14-inch business laptop, Intel Core i5, 16GB RAM, 512GB SSD',5,'Dell','pcs',850.00,1099.00,45,10,100,'Warehouse A','8001234560001',1),
        (2,'SKU-0002','HP LaserJet Pro M404dn','Monochrome laser printer, duplex printing, network ready',6,'HP','pcs',280.00,399.00,18,5,50,'Warehouse A','8001234560002',1),
        (3,'SKU-0003','A4 Copy Paper 500 Sheets','80gsm premium white copy paper, ream of 500 sheets',2,'Navigator','box',3.50,6.00,320,50,1000,'Warehouse B','8001234560003',1),
        (4,'SKU-0004','Executive Office Chair','Ergonomic mesh back office chair with lumbar support, adjustable height',4,'ErgoPlus','pcs',120.00,199.00,12,3,30,'Warehouse C','8001234560004',1)
    ");
    $today = date('Y-m-d');
    $poNum = 'PO-' . date('Ymd') . '-001';
    $invNum = 'INV-' . date('Ymd') . '-001';
    $pdo->exec("INSERT IGNORE INTO `purchase_orders` (`id`,`po_number`,`supplier_id`,`po_date`,`expected_delivery`,`subtotal`,`tax_percent`,`discount`,`total_amount`,`payment_status`,`order_status`,`created_by`) VALUES
        (1,'$poNum',1,'$today','$today',42500.00,0,0,42500.00,'Paid','Received',1)
    ");
    $pdo->exec('INSERT IGNORE INTO `purchase_order_items` (`po_id`,`product_id`,`quantity`,`received_qty`,`unit_price`,`total_price`) VALUES
        (1,1,50,50,850.00,42500.00)
    ');
    $pdo->exec("INSERT IGNORE INTO `invoices` (`id`,`invoice_number`,`customer_id`,`invoice_date`,`due_date`,`subtotal`,`tax_percent`,`discount`,`total_amount`,`payment_status`,`payment_method`,`created_by`) VALUES
        (1,'$invNum',1,'$today','$today',10990.00,0,0,10990.00,'Paid','Bank Transfer',1)
    ");
    $pdo->exec('INSERT IGNORE INTO `invoice_items` (`invoice_id`,`product_id`,`quantity`,`unit_price`,`discount`,`total_price`) VALUES
        (1,1,10,1099.00,0,10990.00)
    ');
    logActivity(1, 'admin', 'System Initialized', 'System', 'Database and seed data created successfully.');
}

function logActivity($userId, $username, $action, $module, $description)
{
    try {
        $pdo = getDB();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare('INSERT INTO `activity_log` (`user_id`,`username`,`action`,`module`,`description`,`ip_address`) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$userId, $username, $action, $module, $description, $ip]);
    } catch (Exception $e) {
    }
}

function generateCSRF()
{
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verifyCSRF($token)
{
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function h($str)
{
    return htmlspecialchars((string) $str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function getSetting($key, $default = '')
{
    static $cache = [];
    if (isset($cache[$key]))
        return $cache[$key];
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT `setting_value` FROM `settings` WHERE `setting_key` = ?');
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        $cache[$key] = ($val !== false) ? $val : $default;
    } catch (Exception $e) {
        $cache[$key] = $default;
    }
    return $cache[$key];
}

function formatCurrency($amount)
{
    $symbol = getSetting('currency_symbol', '$');
    return $symbol . number_format((float) $amount, 2);
}

function formatDate($date)
{
    if (empty($date))
        return '';
    return date(getSetting('date_format', 'Y-m-d'), strtotime($date));
}

function paginate($total, $page, $perPage, $baseUrl)
{
    $totalPages = ceil($total / $perPage);
    if ($totalPages <= 1)
        return '';
    $html = '<div class="pagination">';
    if ($page > 1)
        $html .= '<a href="' . $baseUrl . '&pg=' . ($page - 1) . '" class="page-btn">&laquo; Prev</a>';
    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);
    if ($start > 1) {
        $html .= '<a href="' . $baseUrl . '&pg=1" class="page-btn">1</a>';
        if ($start > 2)
            $html .= '<span class="page-dots">...</span>';
    }
    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $page) ? ' active' : '';
        $html .= '<a href="' . $baseUrl . '&pg=' . $i . '" class="page-btn' . $active . '">' . $i . '</a>';
    }
    if ($end < $totalPages) {
        if ($end < $totalPages - 1)
            $html .= '<span class="page-dots">...</span>';
        $html .= '<a href="' . $baseUrl . '&pg=' . $totalPages . '" class="page-btn">' . $totalPages . '</a>';
    }
    if ($page < $totalPages)
        $html .= '<a href="' . $baseUrl . '&pg=' . ($page + 1) . '" class="page-btn">Next &raquo;</a>';
    $html .= '<span class="page-info">Page ' . $page . ' of ' . $totalPages . ' (' . $total . ' records)</span>';
    $html .= '</div>';
    return $html;
}

function generateSKU()
{
    $pdo = getDB();
    $last = $pdo->query('SELECT `sku` FROM `products` ORDER BY `id` DESC LIMIT 1')->fetchColumn();
    if ($last && preg_match('/SKU-(\d+)/', $last, $m)) {
        return 'SKU-' . str_pad((int) $m[1] + 1, 4, '0', STR_PAD_LEFT);
    }
    return 'SKU-0001';
}

function generatePONumber()
{
    $pdo = getDB();
    $prefix = 'PO-' . date('Ymd') . '-';
    $count = $pdo->query("SELECT COUNT(*) FROM `purchase_orders` WHERE `po_number` LIKE '" . $prefix . "%'")->fetchColumn();
    return $prefix . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
}

function generateInvoiceNumber()
{
    $pdo = getDB();
    $prefix = getSetting('invoice_prefix', 'INV') . '-' . date('Ymd') . '-';
    $count = $pdo->query("SELECT COUNT(*) FROM `invoices` WHERE `invoice_number` LIKE '" . $prefix . "%'")->fetchColumn();
    return $prefix . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
}

function generateAdjRef()
{
    $pdo = getDB();
    $prefix = 'ADJ-' . date('Ymd') . '-';
    $count = $pdo->query("SELECT COUNT(*) FROM `stock_adjustments` WHERE `reference_no` LIKE '" . $prefix . "%'")->fetchColumn();
    return $prefix . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
}

function generateTransferRef()
{
    $pdo = getDB();
    $prefix = 'TRF-' . date('Ymd') . '-';
    $count = $pdo->query("SELECT COUNT(*) FROM `stock_transfers` WHERE `reference_no` LIKE '" . $prefix . "%'")->fetchColumn();
    return $prefix . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
}

function isLoggedIn()
{
    if (empty($_SESSION['user_id']))
        return false;
    $now = time();
    if (!empty($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_destroy();
        return false;
    }
    if (!empty($_SESSION['login_time']) && ($now - $_SESSION['login_time']) > SESSION_ABSOLUTE) {
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = $now;
    return true;
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: ?page=login');
        exit;
    }
}

function hasRole($roles)
{
    if (!isLoggedIn())
        return false;
    $roles = (array) $roles;
    return in_array($_SESSION['user_role'], $roles);
}

function requireRole($roles)
{
    if (!hasRole($roles)) {
        $_SESSION['flash_error'] = 'Access denied. Insufficient permissions.';
        header('Location: ?page=dashboard');
        exit;
    }
}

function setFlash($type, $msg)
{
    $_SESSION['flash_' . $type] = $msg;
}

function getFlash($type)
{
    $msg = $_SESSION['flash_' . $type] ?? '';
    unset($_SESSION['flash_' . $type]);
    return $msg;
}

function mathCaptcha()
{
    $a = rand(2, 9);
    $b = rand(1, 8);
    $ops = ['+', '-', '*'];
    $op = $ops[array_rand($ops)];
    $ans = $op === '+' ? $a + $b : ($op === '-' ? $a - $b : $a * $b);
    $_SESSION['captcha_answer'] = $ans;
    return ['q' => "$a $op $b = ?", 'a' => $ans];
}

function verifyCaptcha($input)
{
    return isset($_SESSION['captcha_answer']) && (int) $input === (int) $_SESSION['captcha_answer'];
}

function generateCaptchaImage($question)
{
    $width = 160;
    $height = 50;
    ob_start();
    ?>
    <svg width="<?= $width ?>" height="<?= $height ?>" xmlns="http://www.w3.org/2000/svg" style="background:#e0e0e0;border:2px inset #999;">
        <?php for ($i = 0; $i < 8; $i++): ?>
        <line x1="<?= rand(0, $width) ?>" y1="<?= rand(0, $height) ?>" x2="<?= rand(0, $width) ?>" y2="<?= rand(0, $height) ?>" stroke="<?= '#' . dechex(rand(0x555555, 0x999999)) ?>" stroke-width="1"/>
        <?php endfor; ?>
        <?php for ($i = 0; $i < 15; $i++): ?>
        <circle cx="<?= rand(0, $width) ?>" cy="<?= rand(0, $height) ?>" r="1.5" fill="<?= '#' . dechex(rand(0x555555, 0x888888)) ?>"/>
        <?php endfor; ?>
        <text x="<?= $width / 2 ?>" y="<?= $height / 2 + 8 ?>" text-anchor="middle" font-family="Courier New,monospace" font-size="22" font-weight="bold" fill="#222" transform="rotate(<?= rand(-5, 5) ?>,<?= $width / 2 ?>,<?= $height / 2 ?>)"><?= h($question) ?></text>
    </svg>
    <?php
    return ob_get_clean();
}

$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? 'list';
if ($page === 'login') {
    handleLogin();
} elseif ($page === 'logout') {
    handleLogout();
} elseif ($page === 'captcha_refresh') {
    $cap = mathCaptcha();
    echo json_encode(['html' => generateCaptchaImage($cap['q'])]);
    exit;
} else {
    requireLogin();
    handlePage($page, $action);
}

function handleLogin()
{
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $captcha = mathCaptcha();
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $captchaInput = $_POST['captcha'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (!verifyCaptcha($captchaInput)) {
            $error = 'Incorrect CAPTCHA answer. Please try again.';
            $captcha = mathCaptcha();
        } else {
            $pdo = getDB();
            $stmt = $pdo->prepare('SELECT * FROM `users` WHERE `username` = ? AND `status` = 1 LIMIT 1');
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            if ($user) {
                $lockedUntil = $user['locked_until'] ? strtotime($user['locked_until']) : 0;
                if ($lockedUntil > time()) {
                    $error = 'Account locked. Try again after ' . date('H:i', $lockedUntil) . '.';
                } elseif (password_verify($password, $user['password'])) {
                    $pdo->prepare('UPDATE `users` SET `login_attempts`=0,`locked_until`=NULL,`last_login`=NOW() WHERE `id`=?')->execute([$user['id']]);
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['login_time'] = time();
                    $_SESSION['last_activity'] = time();
                    $pdo->prepare("INSERT INTO `login_attempts` (`username`,`ip_address`,`status`) VALUES (?,?,'Success')")->execute([$username, $ip]);
                    logActivity($user['id'], $user['username'], 'Login', 'Auth', 'User logged in successfully from ' . $ip);
                    header('Location: ?page=dashboard');
                    exit;
                } else {
                    $attempts = $user['login_attempts'] + 1;
                    $lockUntil = $attempts >= 5 ? date('Y-m-d H:i:s', time() + 900) : null;
                    $pdo->prepare('UPDATE `users` SET `login_attempts`=?,`locked_until`=? WHERE `id`=?')->execute([$attempts, $lockUntil, $user['id']]);
                    $error = 'Invalid username or password.' . ($attempts >= 3 ? ' (' . (5 - $attempts) . ' attempts remaining)' : '');
                    $pdo->prepare("INSERT INTO `login_attempts` (`username`,`ip_address`,`status`) VALUES (?,?,'Failed')")->execute([$username, $ip]);
                    $captcha = mathCaptcha();
                }
            } else {
                $error = 'Invalid username or password.';
                $captcha = mathCaptcha();
            }
        }
    }
    renderLogin($error, $captcha);
}

function handleLogout()
{
    if (isset($_SESSION['user_id'])) {
        logActivity($_SESSION['user_id'], $_SESSION['username'] ?? '', 'Logout', 'Auth', 'User logged out.');
    }
    session_destroy();
    header('Location: ?page=login');
    exit;
}

function handlePage($page, $action)
{
    $pdo = getDB();
    switch ($page) {
        case 'dashboard':
            renderDashboard();
            break;
        case 'products':
            handleProducts($action);
            break;
        case 'categories':
            handleCategories($action);
            break;
        case 'suppliers':
            handleSuppliers($action);
            break;
        case 'customers':
            handleCustomers($action);
            break;
        case 'purchases':
            handlePurchases($action);
            break;
        case 'sales':
            handleSales($action);
            break;
        case 'adjustments':
            handleAdjustments($action);
            break;
        case 'transfers':
            handleTransfers($action);
            break;
        case 'reports':
            handleReports();
            break;
        case 'users':
            handleUsers($action);
            break;
        case 'settings':
            handleSettings();
            break;
        case 'activity':
            handleActivity();
            break;
        case 'profile':
            handleProfile($action);
            break;
        case 'backup':
            handleBackup();
            break;
        default:
            renderDashboard();
    }
}

function handleProducts($action)
{
    requireLogin();
    $pdo = getDB();
    if ($action === 'export_csv') {
        $products = $pdo->query('SELECT p.*,c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY p.name')->fetchAll();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="products_' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['SKU', 'Name', 'Category', 'Brand', 'Unit', 'Purchase Price', 'Selling Price', 'Stock', 'Min Stock', 'Max Stock', 'Location', 'Status']);
        foreach ($products as $p) {
            fputcsv($out, [$p['sku'], $p['name'], $p['category_name'], $p['brand'], $p['unit'], $p['purchase_price'], $p['selling_price'], $p['current_stock'], $p['min_stock'], $p['max_stock'], $p['location'], ($p['status'] ? 'Active' : 'Inactive')]);
        }
        fclose($out);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) {
            setFlash('error', 'Invalid CSRF token.');
            header('Location: ?page=products');
            exit;
        }
        if ($action === 'add' || $action === 'edit') {
            requireRole(['Admin', 'Manager']);
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $cat = (int) ($_POST['category_id'] ?? 0);
            $brand = trim($_POST['brand'] ?? '');
            $unit = trim($_POST['unit'] ?? 'pcs');
            $pprice = (float) ($_POST['purchase_price'] ?? 0);
            $sprice = (float) ($_POST['selling_price'] ?? 0);
            $minStock = (float) ($_POST['min_stock'] ?? 0);
            $maxStock = (float) ($_POST['max_stock'] ?? 0);
            $location = trim($_POST['location'] ?? '');
            $barcode = trim($_POST['barcode'] ?? '');
            $imgUrl = trim($_POST['image_url'] ?? '');
            $status = (int) ($_POST['status'] ?? 1);
            $pass = strlen($name) < 2 ? 'Product name too short.' : '';
            if ($pass) {
                setFlash('error', $pass);
                header('Location: ?page=products&action=' . $action . ($id ? '&id=' . $id : ''));
                exit;
            }
            if ($id) {
                $stmt = $pdo->prepare('UPDATE `products` SET `name`=?,`description`=?,`category_id`=?,`brand`=?,`unit`=?,`purchase_price`=?,`selling_price`=?,`min_stock`=?,`max_stock`=?,`location`=?,`barcode`=?,`image_url`=?,`status`=? WHERE `id`=?');
                $stmt->execute([$name, $desc, $cat ? $cat : null, $brand, $unit, $pprice, $sprice, $minStock, $maxStock, $location, $barcode, $imgUrl, $status, $id]);
                logActivity($_SESSION['user_id'], $_SESSION['username'], 'Update Product', 'Products', "Updated product: $name (ID:$id)");
                setFlash('success', 'Product updated successfully.');
            } else {
                $sku = generateSKU();
                $stmt = $pdo->prepare('INSERT INTO `products` (`sku`,`name`,`description`,`category_id`,`brand`,`unit`,`purchase_price`,`selling_price`,`min_stock`,`max_stock`,`location`,`barcode`,`image_url`,`status`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                $stmt->execute([$sku, $name, $desc, $cat ? $cat : null, $brand, $unit, $pprice, $sprice, $minStock, $maxStock, $location, $barcode, $imgUrl, $status]);
                logActivity($_SESSION['user_id'], $_SESSION['username'], 'Add Product', 'Products', "Added product: $name (SKU:$sku)");
                setFlash('success', 'Product added successfully. SKU: ' . $sku);
            }
            header('Location: ?page=products');
            exit;
        }
        if ($action === 'delete') {
            requireRole(['Admin']);
            $id = (int) ($_POST['id'] ?? 0);
            $prod = $pdo->prepare('SELECT `name` FROM `products` WHERE `id`=?');
            $prod->execute([$id]);
            $prod = $prod->fetchColumn();
            $pdo->prepare('DELETE FROM `products` WHERE `id`=?')->execute([$id]);
            logActivity($_SESSION['user_id'], $_SESSION['username'], 'Delete Product', 'Products', "Deleted product: $prod (ID:$id)");
            setFlash('success', 'Product deleted.');
            header('Location: ?page=products');
            exit;
        }
        if ($action === 'bulk') {
            requireRole(['Admin', 'Manager']);
            $ids = $_POST['selected_ids'] ?? [];
            $bulkAction = $_POST['bulk_action'] ?? '';
            if (!empty($ids) && in_array($bulkAction, ['activate', 'deactivate', 'delete'])) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                if ($bulkAction === 'activate')
                    $pdo->prepare("UPDATE `products` SET `status`=1 WHERE `id` IN ($placeholders)")->execute($ids);
                elseif ($bulkAction === 'deactivate')
                    $pdo->prepare("UPDATE `products` SET `status`=0 WHERE `id` IN ($placeholders)")->execute($ids);
                elseif ($bulkAction === 'delete') {
                    requireRole(['Admin']);
                    $pdo->prepare("DELETE FROM `products` WHERE `id` IN ($placeholders)")->execute($ids);
                }
                logActivity($_SESSION['user_id'], $_SESSION['username'], 'Bulk ' . ucfirst($bulkAction), 'Products', "Bulk $bulkAction on " . count($ids) . ' products.');
                setFlash('success', 'Bulk action completed.');
            }
            header('Location: ?page=products');
            exit;
        }
    }
    $search = trim($_GET['search'] ?? '');
    $catFilter = (int) ($_GET['cat'] ?? 0);
    $statusFilter = $_GET['status'] ?? '';
    $page = max(1, (int) ($_GET['pg'] ?? 1));
    $perPage = (int) getSetting('items_per_page', 20);
    $where = ['1=1'];
    $params = [];
    if ($search) {
        $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.brand LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($catFilter) {
        $where[] = 'p.category_id = ?';
        $params[] = $catFilter;
    }
    if ($statusFilter !== '') {
        $where[] = 'p.status = ?';
        $params[] = (int) $statusFilter;
    }
    $whereStr = implode(' AND ', $where);
    $total = $pdo->prepare("SELECT COUNT(*) FROM `products` p WHERE $whereStr");
    $total->execute($params);
    $total = $total->fetchColumn();
    $offset = ($page - 1) * $perPage;
    $stmt = $pdo->prepare("SELECT p.*,c.name as category_name FROM `products` p LEFT JOIN `categories` c ON p.category_id=c.id WHERE $whereStr ORDER BY p.name LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    $categories = $pdo->query('SELECT `id`,`name` FROM `categories` WHERE `status`=1 ORDER BY `name`')->fetchAll();
    $baseUrl = '?page=products&search=' . urlencode($search) . "&cat=$catFilter&status=$statusFilter";
    if ($action === 'add') {
        requireRole(['Admin', 'Manager']);
        renderProductForm(null, $categories);
    } elseif ($action === 'edit') {
        requireRole(['Admin', 'Manager']);
        $id = (int) ($_GET['id'] ?? 0);
        $prod = $pdo->prepare('SELECT * FROM `products` WHERE `id`=?');
        $prod->execute([$id]);
        $prod = $prod->fetch();
        if (!$prod) {
            setFlash('error', 'Product not found.');
            header('Location: ?page=products');
            exit;
        }
        renderProductForm($prod, $categories);
    } elseif ($action === 'view') {
        $id = (int) ($_GET['id'] ?? 0);
        $prod = $pdo->prepare('SELECT p.*,c.name as category_name FROM `products` p LEFT JOIN `categories` c ON p.category_id=c.id WHERE p.id=?');
        $prod->execute([$id]);
        $prod = $prod->fetch();
        if (!$prod) {
            setFlash('error', 'Product not found.');
            header('Location: ?page=products');
            exit;
        }
        renderProductDetail($prod);
    } else {
        renderProductList($products, $categories, $search, $catFilter, $statusFilter, $total, $page, $perPage, $baseUrl);
    }
}

function handleCategories($action)
{
    $pdo = getDB();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) {
            setFlash('error', 'Invalid CSRF token.');
            header('Location: ?page=categories');
            exit;
        }
        requireRole(['Admin', 'Manager']);
        if ($action === 'add' || $action === 'edit') {
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $parent = (int) ($_POST['parent_id'] ?? 0);
            $desc = trim($_POST['description'] ?? '');
            $status = (int) ($_POST['status'] ?? 1);
            if (strlen($name) < 2) {
                setFlash('error', 'Category name too short.');
                header('Location: ?page=categories');
                exit;
            }
            if ($id) {
                $pdo->prepare('UPDATE `categories` SET `name`=?,`parent_id`=?,`description`=?,`status`=? WHERE `id`=?')->execute([$name, $parent ? $parent : null, $desc, $status, $id]);
                logActivity($_SESSION['user_id'], $_SESSION['username'], 'Update Category', 'Categories', "Updated: $name");
                setFlash('success', 'Category updated.');
            } else {
                $pdo->prepare('INSERT INTO `categories` (`name`,`parent_id`,`description`,`status`) VALUES (?,?,?,?)')->execute([$name, $parent ? $parent : null, $desc, $status]);
                logActivity($_SESSION['user_id'], $_SESSION['username'], 'Add Category', 'Categories', "Added: $name");
                setFlash('success', 'Category added.');
            }
            header('Location: ?page=categories');
            exit;
        }
        if ($action === 'delete') {
            requireRole(['Admin']);
            $id = (int) ($_POST['id'] ?? 0);
            $pdo->prepare('UPDATE `categories` SET `parent_id`=NULL WHERE `parent_id`=?')->execute([$id]);
            $pdo->prepare('UPDATE `products` SET `category_id`=NULL WHERE `category_id`=?')->execute([$id]);
            $pdo->prepare('DELETE FROM `categories` WHERE `id`=?')->execute([$id]);
            setFlash('success', 'Category deleted.');
            header('Location: ?page=categories');
            exit;
        }
        if ($action === 'toggle') {
            $id = (int) ($_POST['id'] ?? 0);
            $pdo->prepare('UPDATE `categories` SET `status`=1-`status` WHERE `id`=?')->execute([$id]);
            setFlash('success', 'Category status toggled.');
            header('Location: ?page=categories');
            exit;
        }
    }
    $cats = $pdo->query('SELECT c.*,p.name as parent_name,(SELECT COUNT(*) FROM products WHERE category_id=c.id) as product_count FROM categories c LEFT JOIN categories p ON c.parent_id=p.id ORDER BY c.name')->fetchAll();
    $parentCats = $pdo->query('SELECT `id`,`name` FROM `categories` WHERE `parent_id` IS NULL AND `status`=1 ORDER BY `name`')->fetchAll();
    renderCategories($cats, $parentCats);
}

function handleSuppliers($action)
{
    $pdo = getDB();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) {
            setFlash('error', 'Invalid CSRF token.');
            header('Location: ?page=suppliers');
            exit;
        }
        requireRole(['Admin', 'Manager']);
        if ($action === 'add' || $action === 'edit') {
            $id = (int) ($_POST['id'] ?? 0);
            $company = trim($_POST['company_name'] ?? '');
            $contact = trim($_POST['contact_person'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $country = trim($_POST['country'] ?? '');
            $taxId = trim($_POST['tax_id'] ?? '');
            $terms = trim($_POST['payment_terms'] ?? '');
            $status = (int) ($_POST['status'] ?? 1);
            $notes = trim($_POST['notes'] ?? '');
            if (strlen($company) < 2) {
                setFlash('error', 'Company name too short.');
                header('Location: ?page=suppliers');
                exit;
            }
            if ($id) {
                $pdo->prepare('UPDATE `suppliers` SET `company_name`=?,`contact_person`=?,`email`=?,`phone`=?,`address`=?,`city`=?,`country`=?,`tax_id`=?,`payment_terms`=?,`status`=?,`notes`=? WHERE `id`=?')->execute([$company, $contact, $email, $phone, $address, $city, $country, $taxId, $terms, $status, $notes, $id]);
                logActivity($_SESSION['user_id'], $_SESSION['username'], 'Update Supplier', 'Suppliers', "Updated: $company");
                setFlash('success', 'Supplier updated.');
            } else {
                $pdo->prepare('INSERT INTO `suppliers` (`company_name`,`contact_person`,`email`,`phone`,`address`,`city`,`country`,`tax_id`,`payment_terms`,`status`,`notes`) VALUES (?,?,?,?,?,?,?,?,?,?,?)')->execute([$company, $contact, $email, $phone, $address, $city, $country, $taxId, $terms, $status, $notes]);
                logActivity($_SESSION['user_id'], $_SESSION['username'], 'Add Supplier', 'Suppliers', "Added: $company");
                setFlash('success', 'Supplier added.');
            }
            header('Location: ?page=suppliers');
            exit;
        }
        if ($action === 'delete') {
            requireRole(['Admin']);
            $id = (int) ($_POST['id'] ?? 0);
            $name = $pdo->prepare('SELECT company_name FROM suppliers WHERE id=?');
            $name->execute([$id]);
            $name = $name->fetchColumn();
            $pdo->prepare('DELETE FROM `suppliers` WHERE `id`=?')->execute([$id]);
            logActivity($_SESSION['user_id'], $_SESSION['username'], 'Delete Supplier', 'Suppliers', "Deleted: $name");
            setFlash('success', 'Supplier deleted.');
            header('Location: ?page=suppliers');
            exit;
        }
    }
    $search = trim($_GET['search'] ?? '');
    $page = max(1, (int) ($_GET['pg'] ?? 1));
    $perPage = (int) getSetting('items_per_page', 20);
    $where = '1=1';
    $params = [];
    if ($search) {
        $where .= ' AND (company_name LIKE ? OR contact_person LIKE ? OR email LIKE ?)';
        $params = ["%$search%", "%$search%", "%$search%"];
    }
    $total = $pdo->prepare("SELECT COUNT(*) FROM suppliers WHERE $where");
    $total->execute($params);
    $total = $total->fetchColumn();
    $offset = ($page - 1) * $perPage;
    $stmt = $pdo->prepare("SELECT * FROM `suppliers` WHERE $where ORDER BY company_name LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $suppliers = $stmt->fetchAll();
    if ($action === 'view') {
        $id = (int) ($_GET['id'] ?? 0);
        $sup = $pdo->prepare('SELECT * FROM suppliers WHERE id=?');
        $sup->execute([$id]);
        $sup = $sup->fetch();
        $pos = $pdo->prepare('SELECT po.*,s.company_name FROM purchase_orders po JOIN suppliers s ON po.supplier_id=s.id WHERE po.supplier_id=? ORDER BY po.created_at DESC LIMIT 10');
        $pos->execute([$id]);
        $pos = $pos->fetchAll();
        renderSupplierDetail($sup, $pos);
        return;
    }
    renderSuppliers($suppliers, $search, $total, $page, $perPage);
}

function handleCustomers($action)
{
    $pdo = getDB();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) {
            setFlash('error', 'Invalid CSRF.');
            header('Location: ?page=customers');
            exit;
        }
        requireRole(['Admin', 'Manager']);
        if ($action === 'add' || $action === 'edit') {
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $country = trim($_POST['country'] ?? '');
            $type = $_POST['customer_type'] ?? 'Walk-in';
            $credit = (float) ($_POST['credit_limit'] ?? 0);
            $status = (int) ($_POST['status'] ?? 1);
            $notes = trim($_POST['notes'] ?? '');
            if (strlen($name) < 2) {
                setFlash('error', 'Name too short.');
                header('Location: ?page=customers');
                exit;
            }
            if ($id) {
                $pdo->prepare('UPDATE `customers` SET `name`=?,`email`=?,`phone`=?,`address`=?,`city`=?,`country`=?,`customer_type`=?,`credit_limit`=?,`status`=?,`notes`=? WHERE `id`=?')->execute([$name, $email, $phone, $address, $city, $country, $type, $credit, $status, $notes, $id]);
                setFlash('success', 'Customer updated.');
            } else {
                $pdo->prepare('INSERT INTO `customers` (`name`,`email`,`phone`,`address`,`city`,`country`,`customer_type`,`credit_limit`,`status`,`notes`) VALUES (?,?,?,?,?,?,?,?,?,?)')->execute([$name, $email, $phone, $address, $city, $country, $type, $credit, $status, $notes]);
                setFlash('success', 'Customer added.');
            }
            logActivity($_SESSION['user_id'], $_SESSION['username'], ($id ? 'Update' : 'Add') . ' Customer', 'Customers', "Name: $name");
            header('Location: ?page=customers');
            exit;
        }
        if ($action === 'delete') {
            requireRole(['Admin']);
            $id = (int) ($_POST['id'] ?? 0);
            $pdo->prepare('DELETE FROM customers WHERE id=?')->execute([$id]);
            setFlash('success', 'Customer deleted.');
            header('Location: ?page=customers');
            exit;
        }
    }
    $search = trim($_GET['search'] ?? '');
    $page = max(1, (int) ($_GET['pg'] ?? 1));
    $perPage = (int) getSetting('items_per_page', 20);
    $where = '1=1';
    $params = [];
    if ($search) {
        $where .= ' AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)';
        $params = ["%$search%", "%$search%", "%$search%"];
    }
    $total = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE $where");
    $total->execute($params);
    $total = $total->fetchColumn();
    $offset = ($page - 1) * $perPage;
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE $where ORDER BY name LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $customers = $stmt->fetchAll();
    if ($action === 'view') {
        $id = (int) ($_GET['id'] ?? 0);
        $cust = $pdo->prepare('SELECT * FROM customers WHERE id=?');
        $cust->execute([$id]);
        $cust = $cust->fetch();
        $invs = $pdo->prepare('SELECT * FROM invoices WHERE customer_id=? ORDER BY created_at DESC LIMIT 10');
        $invs->execute([$id]);
        $invs = $invs->fetchAll();
        renderCustomerDetail($cust, $invs);
        return;
    }
    renderCustomers($customers, $search, $total, $page, $perPage);
}

function handlePurchases($action)
{
    $pdo = getDB();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) {
            setFlash('error', 'Invalid CSRF.');
            header('Location: ?page=purchases');
            exit;
        }
        requireRole(['Admin', 'Manager']);
        if ($action === 'add') {
            $supplierId = (int) ($_POST['supplier_id'] ?? 0);
            $poDate = $_POST['po_date'] ?? date('Y-m-d');
            $expDel = $_POST['expected_delivery'] ?? '';
            $taxPct = (float) ($_POST['tax_percent'] ?? 0);
            $discount = (float) ($_POST['discount'] ?? 0);
            $notes = trim($_POST['notes'] ?? '');
            $payStatus = $_POST['payment_status'] ?? 'Unpaid';
            $prodIds = $_POST['product_id'] ?? [];
            $quantities = $_POST['quantity'] ?? [];
            $unitPrices = $_POST['unit_price'] ?? [];
            if (!$supplierId || empty($prodIds)) {
                setFlash('error', 'Select supplier and add at least one product.');
                header('Location: ?page=purchases&action=add');
                exit;
            }
            $subtotal = 0;
            foreach ($prodIds as $k => $pid) {
                $subtotal += (float) $quantities[$k] * (float) $unitPrices[$k];
            }
            $taxAmount = $subtotal * $taxPct / 100;
            $total = $subtotal + $taxAmount - $discount;
            $poNum = generatePONumber();
            $stmt = $pdo->prepare("INSERT INTO purchase_orders (po_number,supplier_id,po_date,expected_delivery,subtotal,tax_percent,discount,total_amount,payment_status,order_status,notes,created_by) VALUES (?,?,?,?,?,?,?,?,'Pending','Pending',?,?)");
            $stmt->execute([$poNum, $supplierId, $poDate, $expDel ? $expDel : null, $subtotal, $taxPct, $discount, $total, $notes, $_SESSION['user_id']]);
            $poId = $pdo->lastInsertId();
            $payStatus === 'Paid' ? $pdo->prepare("UPDATE purchase_orders SET payment_status='Paid' WHERE id=?")->execute([$poId]) : null;
            foreach ($prodIds as $k => $pid) {
                $pid = (int) $pid;
                $qty = (float) $quantities[$k];
                $up = (float) $unitPrices[$k];
                $tp = $qty * $up;
                $pdo->prepare('INSERT INTO purchase_order_items (po_id,product_id,quantity,unit_price,total_price) VALUES (?,?,?,?,?)')->execute([$poId, $pid, $qty, $up, $tp]);
            }
            logActivity($_SESSION['user_id'], $_SESSION['username'], 'Create PO', 'Purchases', "PO: $poNum, Total: $total");
            setFlash('success', "Purchase Order $poNum created.");
            header('Location: ?page=purchases');
            exit;
        }
        if ($action === 'receive') {
            $poId = (int) ($_POST['po_id'] ?? 0);
            $po = $pdo->prepare('SELECT * FROM purchase_orders WHERE id=?');
            $po->execute([$poId]);
            $po = $po->fetch();
            if (!$po) {
                setFlash('error', 'PO not found.');
                header('Location: ?page=purchases');
                exit;
            }
            $items = $pdo->prepare('SELECT * FROM purchase_order_items WHERE po_id=?');
            $items->execute([$poId]);
            $items = $items->fetchAll();
            $recvQtys = $_POST['recv_qty'] ?? [];
            $allReceived = true;
            foreach ($items as $item) {
                $recvQty = (float) ($recvQtys[$item['id']] ?? 0);
                $newRecv = $item['received_qty'] + $recvQty;
                if ($newRecv < $item['quantity'])
                    $allReceived = false;
                $pdo->prepare('UPDATE purchase_order_items SET received_qty=? WHERE id=?')->execute([$newRecv, $item['id']]);
                if ($recvQty > 0) {
                    $pdo->prepare('UPDATE products SET current_stock=current_stock+? WHERE id=?')->execute([$recvQty, $item['product_id']]);
                    $adjRef = generateAdjRef();
                    $prod = $pdo->prepare('SELECT current_stock FROM products WHERE id=?');
                    $prod->execute([$item['product_id']]);
                    $newStock = $prod->fetchColumn();
                    $pdo->prepare("INSERT INTO stock_adjustments (reference_no,product_id,adjustment_type,quantity,previous_stock,new_stock,reason,status,approved_by,created_by) VALUES (?,?,'Addition',?,?,?,?,?,'Approved',?,?)")->execute([$adjRef, $item['product_id'], $recvQty, $newStock - $recvQty, $newStock, "Received from PO: {$po['po_number']}", $_SESSION['user_id'], $_SESSION['user_id']]);
                }
            }
            $status = $allReceived ? 'Received' : 'Partial';
            $pdo->prepare('UPDATE purchase_orders SET order_status=? WHERE id=?')->execute([$status, $poId]);
            logActivity($_SESSION['user_id'], $_SESSION['username'], 'Receive PO', 'Purchases', "PO ID: $poId, Status: $status");
            setFlash('success', 'Stock received and inventory updated.');
            header('Location: ?page=purchases&action=view&id=' . $poId);
            exit;
        }
        if ($action === 'delete') {
            requireRole(['Admin']);
            $id = (int) ($_POST['id'] ?? 0);
            $pdo->prepare('DELETE FROM purchase_orders WHERE id=?')->execute([$id]);
            setFlash('success', 'Purchase order deleted.');
            header('Location: ?page=purchases');
            exit;
        }
    }
    if ($action === 'add') {
        requireRole(['Admin', 'Manager']);
        $suppliers = $pdo->query('SELECT id,company_name FROM suppliers WHERE status=1 ORDER BY company_name')->fetchAll();
        $products = $pdo->query('SELECT id,sku,name,purchase_price FROM products WHERE status=1 ORDER BY name')->fetchAll();
        renderPurchaseForm($suppliers, $products);
        return;
    }
    if ($action === 'view') {
        $id = (int) ($_GET['id'] ?? 0);
        $po = $pdo->prepare('SELECT po.*,s.company_name,s.contact_person,s.phone,s.email FROM purchase_orders po JOIN suppliers s ON po.supplier_id=s.id WHERE po.id=?');
        $po->execute([$id]);
        $po = $po->fetch();
        $items = $pdo->prepare('SELECT poi.*,p.name as product_name,p.sku,p.unit FROM purchase_order_items poi JOIN products p ON poi.product_id=p.id WHERE poi.po_id=?');
        $items->execute([$id]);
        $items = $items->fetchAll();
        renderPurchaseDetail($po, $items);
        return;
    }
    $search = trim($_GET['search'] ?? '');
    $status = $_GET['status'] ?? '';
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    $pg = max(1, (int) ($_GET['pg'] ?? 1));
    $perPage = (int) getSetting('items_per_page', 20);
    $where = '1=1';
    $params = [];
    if ($search) {
        $where .= ' AND (po.po_number LIKE ? OR s.company_name LIKE ?)';
        $params = ["%$search%", "%$search%"];
    }
    if ($status) {
        $where .= ' AND po.order_status=?';
        $params[] = $status;
    }
    if ($dateFrom) {
        $where .= ' AND po.po_date>=?';
        $params[] = $dateFrom;
    }
    if ($dateTo) {
        $where .= ' AND po.po_date<=?';
        $params[] = $dateTo;
    }
    $total = $pdo->prepare("SELECT COUNT(*) FROM purchase_orders po JOIN suppliers s ON po.supplier_id=s.id WHERE $where");
    $total->execute($params);
    $total = $total->fetchColumn();
    $offset = ($pg - 1) * $perPage;
    $stmt = $pdo->prepare("SELECT po.*,s.company_name FROM purchase_orders po JOIN suppliers s ON po.supplier_id=s.id WHERE $where ORDER BY po.created_at DESC LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
    $baseUrl = '?page=purchases&search=' . urlencode($search) . "&status=$status&date_from=$dateFrom&date_to=$dateTo";
    renderPurchaseList($orders, $search, $status, $dateFrom, $dateTo, $total, $pg, $perPage, $baseUrl);
}

function handleSales($action)
{
    $pdo = getDB();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) {
            setFlash('error', 'Invalid CSRF.');
            header('Location: ?page=sales');
            exit;
        }
        if ($action === 'add') {
            requireRole(['Admin', 'Manager', 'Staff']);
            $customerId = (int) ($_POST['customer_id'] ?? 0);
            $invDate = $_POST['invoice_date'] ?? date('Y-m-d');
            $dueDate = $_POST['due_date'] ?? '';
            $taxPct = (float) ($_POST['tax_percent'] ?? 0);
            $discount = (float) ($_POST['discount'] ?? 0);
            $payStatus = $_POST['payment_status'] ?? 'Unpaid';
            $payMethod = $_POST['payment_method'] ?? 'Cash';
            $notes = trim($_POST['notes'] ?? '');
            $prodIds = $_POST['product_id'] ?? [];
            $quantities = $_POST['quantity'] ?? [];
            $unitPrices = $_POST['unit_price'] ?? [];
            $itemDiscs = $_POST['item_discount'] ?? [];
            if (!$customerId || empty($prodIds)) {
                setFlash('error', 'Select customer and add at least one product.');
                header('Location: ?page=sales&action=add');
                exit;
            }
            foreach ($prodIds as $k => $pid) {
                $pid = (int) $pid;
                $qty = (float) $quantities[$k];
                $stock = $pdo->prepare('SELECT current_stock,name FROM products WHERE id=?');
                $stock->execute([$pid]);
                $stock = $stock->fetch();
                if ($stock && $stock['current_stock'] < $qty) {
                    setFlash('error', 'Insufficient stock for: ' . $stock['name'] . '. Available: ' . $stock['current_stock']);
                    header('Location: ?page=sales&action=add');
                    exit;
                }
            }
            $subtotal = 0;
            foreach ($prodIds as $k => $pid) {
                $qty = (float) ($quantities[$k] ?? 0);
                $up = (float) ($unitPrices[$k] ?? 0);
                $item_disc_pct = (float) ($itemDiscs[$k] ?? 0);
                $subtotal += ($qty * $up) * (1 - $item_disc_pct / 100);
            }
            $taxAmount = $subtotal * $taxPct / 100;
            $total = $subtotal + $taxAmount - $discount;
            $invNum = generateInvoiceNumber();
            $stmt = $pdo->prepare('INSERT INTO invoices (invoice_number,customer_id,invoice_date,due_date,subtotal,tax_percent,discount,total_amount,payment_status,payment_method,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$invNum, $customerId, $invDate, $dueDate ? $dueDate : null, $subtotal, $taxPct, $discount, $total, $payStatus, $payMethod, $notes, $_SESSION['user_id']]);
            $invId = $pdo->lastInsertId();
            foreach ($prodIds as $k => $pid) {
                $pid = (int) $pid;
                $qty = (float) ($quantities[$k]);
                $up = (float) ($unitPrices[$k]);
                $iDisc_pct = (float) ($itemDiscs[$k] ?? 0);
                $line_total = $qty * $up;
                $discount_amount = $line_total * $iDisc_pct / 100;
                $total_price = $line_total - $discount_amount;
                $pdo->prepare('INSERT INTO invoice_items (invoice_id,product_id,quantity,unit_price,discount,total_price) VALUES (?,?,?,?,?,?)')->execute([$invId, $pid, $qty, $up, $discount_amount, $total_price]);
                $pdo->prepare('UPDATE products SET current_stock=current_stock-? WHERE id=?')->execute([$qty, $pid]);
            }
            if ($payStatus !== 'Paid') {
                $pdo->prepare('UPDATE customers SET current_balance=current_balance+? WHERE id=?')->execute([$total, $customerId]);
            }
            logActivity($_SESSION['user_id'], $_SESSION['username'], 'Create Invoice', 'Sales', "Invoice: $invNum, Total: $total");
            setFlash('success', "Invoice $invNum created successfully.");
            header('Location: ?page=sales&action=view&id=' . $invId);
            exit;
        }
        if ($action === 'delete') {
            requireRole(['Admin']);
            $id = (int) ($_POST['id'] ?? 0);
            $pdo->prepare('DELETE FROM invoices WHERE id=?')->execute([$id]);
            setFlash('success', 'Invoice deleted.');
            header('Location: ?page=sales');
            exit;
        }
        if ($action === 'mark_paid') {
            requireRole(['Admin', 'Manager']);
            $id = (int) ($_POST['id'] ?? 0);
            $inv = $pdo->prepare('SELECT * FROM invoices WHERE id = ?');
            $inv->execute([$id]);
            $inv = $inv->fetch();

            if ($inv && $inv['payment_status'] !== 'Paid') {
                $pdo->prepare("UPDATE invoices SET payment_status = 'Paid', payment_method = ? WHERE id = ?")->execute([$_POST['payment_method'] ?? 'Cash', $id]);
                $pdo->prepare('UPDATE customers SET current_balance = current_balance - ? WHERE id = ?')->execute([$inv['total_amount'], $inv['customer_id']]);
                logActivity($_SESSION['user_id'], $_SESSION['username'], 'Mark Invoice Paid', 'Sales', "Invoice {$inv['invoice_number']} marked as paid.");
                setFlash('success', 'Invoice payment status updated to Paid.');
            }
            header('Location: ?page=sales&action=view&id=' . $id);
            exit;
        }
        if ($action === 'return') {
            requireRole(['Admin', 'Manager']);
            $invId = (int) ($_POST['invoice_id'] ?? 0);
            $retQtys = $_POST['ret_qty'] ?? [];
            $reason = trim($_POST['reason'] ?? 'N/A');

            if (empty($reason)) {
                setFlash('error', 'Return reason is required.');
                header('Location: ?page=sales&action=view&id=' . $invId);
                exit;
            }

            $origInv = $pdo->prepare('SELECT * FROM invoices WHERE id=?');
            $origInv->execute([$invId]);
            $origInv = $origInv->fetch();

            if (!$origInv) {
                setFlash('error', 'Invoice not found.');
                header('Location: ?page=sales');
                exit;
            }

            $itemsStmt = $pdo->prepare('SELECT * FROM invoice_items WHERE invoice_id=?');
            $itemsStmt->execute([$invId]);
            $items_rows = $itemsStmt->fetchAll();
            $items = [];
            foreach ($items_rows as $item_row) {
                $items[$item_row['id']] = $item_row;
            }

            $return_subtotal = 0;
            $itemsToReturn = [];

            foreach ($retQtys as $itemId => $retQty) {
                $retQty = (float) $retQty;
                if ($retQty > 0 && isset($items[$itemId])) {
                    $item = $items[$itemId];
                    if ($retQty > $item['quantity']) {
                        setFlash('error', 'Return quantity cannot exceed sold quantity for an item.');
                        header('Location: ?page=sales&action=view&id=' . $invId);
                        exit;
                    }
                    $itemsToReturn[$itemId] = ['item' => $item, 'ret_qty' => $retQty];
                    $price_per_unit = $item['quantity'] > 0 ? $item['total_price'] / $item['quantity'] : 0;
                    $return_subtotal += $price_per_unit * $retQty;
                }
            }

            if (empty($itemsToReturn)) {
                setFlash('error', 'No items selected for return.');
                header('Location: ?page=sales&action=view&id=' . $invId);
                exit;
            }

            $return_ratio = ($origInv['subtotal'] > 0) ? ($return_subtotal / abs($origInv['subtotal'])) : 0;
            $return_discount = $origInv['discount'] * $return_ratio;
            $tax_amount = $origInv['subtotal'] * $origInv['tax_percent'] / 100;
            $return_tax_amount = $tax_amount * $return_ratio;
            $return_total = $return_subtotal + $return_tax_amount - $return_discount;

            $retNum = 'RET-' . $origInv['invoice_number'] . '-' . rand(100, 999);
            $notes = "Return for invoice {$origInv['invoice_number']}. Reason: " . $reason;

            $stmt = $pdo->prepare('INSERT INTO invoices (invoice_number,customer_id,invoice_date,subtotal,tax_percent,discount,total_amount,payment_status,payment_method,notes,created_by,is_return) VALUES (?,?,NOW(),?,?,?,?,?,?,?,?,1)');
            $stmt->execute([$retNum, $origInv['customer_id'], -$return_subtotal, $origInv['tax_percent'], -$return_discount, -$return_total, 'Paid', 'Credit', $notes, $_SESSION['user_id']]);
            $retId = $pdo->lastInsertId();

            foreach ($itemsToReturn as $itemId => $retData) {
                $item = $retData['item'];
                $retQty = $retData['ret_qty'];

                $price_per_unit = $item['quantity'] > 0 ? $item['total_price'] / $item['quantity'] : 0;
                $ret_total_price = $price_per_unit * $retQty;

                $pdo->prepare('INSERT INTO invoice_items (invoice_id,product_id,quantity,unit_price,discount,total_price) VALUES (?,?,?,?,?,?)')->execute([$retId, $item['product_id'], -$retQty, $item['unit_price'], 0, -$ret_total_price]);
                $pdo->prepare('UPDATE products SET current_stock=current_stock+? WHERE id=?')->execute([$retQty, $item['product_id']]);
            }

            $pdo->prepare('UPDATE customers SET current_balance=current_balance-? WHERE id=?')->execute([$return_total, $origInv['customer_id']]);

            logActivity($_SESSION['user_id'], $_SESSION['username'], 'Return Invoice', 'Sales', "Return: $retNum for: " . $origInv['invoice_number'] . ', Amount: ' . formatCurrency($return_total));
            setFlash('success', "Return processed. Reference: $retNum");
            header('Location: ?page=sales&action=view&id=' . $retId);
            exit;
        }
    }
    if ($action === 'add') {
        requireRole(['Admin', 'Manager', 'Staff']);
        $customers = $pdo->query('SELECT id,name,current_balance,credit_limit FROM customers WHERE status=1 ORDER BY name')->fetchAll();
        $products = $pdo->query('SELECT id,sku,name,selling_price,current_stock,unit FROM products WHERE status=1 ORDER BY name')->fetchAll();
        renderSalesForm($customers, $products);
        return;
    }
    if ($action === 'view') {
        $id = (int) ($_GET['id'] ?? 0);
        $inv = $pdo->prepare('SELECT i.*,c.name as customer_name,c.phone,c.email,c.address FROM invoices i JOIN customers c ON i.customer_id=c.id WHERE i.id=?');
        $inv->execute([$id]);
        $inv = $inv->fetch();
        $items = $pdo->prepare('SELECT ii.*,p.name as product_name,p.sku,p.unit FROM invoice_items ii JOIN products p ON ii.product_id=p.id WHERE ii.invoice_id=?');
        $items->execute([$id]);
        $items = $items->fetchAll();
        renderSalesDetail($inv, $items);
        return;
    }
    $search = trim($_GET['search'] ?? '');
    $status = $_GET['status'] ?? '';
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    $pg = max(1, (int) ($_GET['pg'] ?? 1));
    $perPage = (int) getSetting('items_per_page', 20);
    $where = '1=1';
    $params = [];
    if ($search) {
        $where .= ' AND (i.invoice_number LIKE ? OR c.name LIKE ?)';
        $params = ["%$search%", "%$search%"];
    }
    if ($status) {
        $where .= ' AND i.payment_status=?';
        $params[] = $status;
    }
    if ($dateFrom) {
        $where .= ' AND i.invoice_date>=?';
        $params[] = $dateFrom;
    }
    if ($dateTo) {
        $where .= ' AND i.invoice_date<=?';
        $params[] = $dateTo;
    }
    $total = $pdo->prepare("SELECT COUNT(*) FROM invoices i JOIN customers c ON i.customer_id=c.id WHERE $where");
    $total->execute($params);
    $total = $total->fetchColumn();
    $offset = ($pg - 1) * $perPage;
    $stmt = $pdo->prepare("SELECT i.*,c.name as customer_name FROM invoices i JOIN customers c ON i.customer_id=c.id WHERE $where ORDER BY i.created_at DESC LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $invoices = $stmt->fetchAll();
    $baseUrl = '?page=sales&search=' . urlencode($search) . "&status=$status&date_from=$dateFrom&date_to=$dateTo";
    renderSalesList($invoices, $search, $status, $dateFrom, $dateTo, $total, $pg, $perPage, $baseUrl);
}

function handleAdjustments($action)
{
    $pdo = getDB();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) {
            setFlash('error', 'Invalid CSRF.');
            header('Location: ?page=adjustments');
            exit;
        }
        if ($action === 'add') {
            requireRole(['Admin', 'Manager', 'Staff']);
            $prodId = (int) ($_POST['product_id'] ?? 0);
            $type = $_POST['adjustment_type'] ?? '';
            $qty = (float) ($_POST['quantity'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');
            if (!$prodId || !$type || $qty <= 0) {
                setFlash('error', 'All fields required and quantity must be > 0.');
                header('Location: ?page=adjustments&action=add');
                exit;
            }
            $prod = $pdo->prepare('SELECT current_stock FROM products WHERE id=?');
            $prod->execute([$prodId]);
            $prevStock = (float) $prod->fetchColumn();
            $negTypes = ['Subtraction', 'Damage', 'Expired'];
            $newStock = in_array($type, $negTypes) ? $prevStock - $qty : $prevStock + $qty;
            if ($newStock < 0) {
                setFlash('error', 'Resulting stock cannot be negative.');
                header('Location: ?page=adjustments&action=add');
                exit;
            }
            $status = hasRole(['Admin', 'Manager']) ? 'Approved' : 'Pending';
            $approvedBy = hasRole(['Admin', 'Manager']) ? $_SESSION['user_id'] : null;
            $ref = generateAdjRef();
            $pdo->prepare('INSERT INTO stock_adjustments (reference_no,product_id,adjustment_type,quantity,previous_stock,new_stock,reason,status,approved_by,created_by) VALUES (?,?,?,?,?,?,?,?,?,?)')->execute([$ref, $prodId, $type, $qty, $prevStock, $newStock, $reason, $status, $approvedBy, $_SESSION['user_id']]);
            if ($status === 'Approved') {
                $pdo->prepare('UPDATE products SET current_stock=? WHERE id=?')->execute([$newStock, $prodId]);
            }
            logActivity($_SESSION['user_id'], $_SESSION['username'], 'Stock Adjustment', 'Adjustments', "Ref: $ref, Type: $type, Qty: $qty");
            setFlash('success', 'Adjustment recorded' . ($status === 'Pending' ? ' and awaiting approval.' : '.'));
            header('Location: ?page=adjustments');
            exit;
        }
        if ($action === 'approve') {
            requireRole(['Admin', 'Manager']);
            $id = (int) ($_POST['id'] ?? 0);
            $adj = $pdo->prepare('SELECT * FROM stock_adjustments WHERE id=?');
            $adj->execute([$id]);
            $adj = $adj->fetch();
            if ($adj && $adj['status'] === 'Pending') {
                $pdo->prepare("UPDATE stock_adjustments SET status='Approved',approved_by=? WHERE id=?")->execute([$_SESSION['user_id'], $id]);
                $pdo->prepare('UPDATE products SET current_stock=? WHERE id=?')->execute([$adj['new_stock'], $adj['product_id']]);
                setFlash('success', 'Adjustment approved.');
            }
            header('Location: ?page=adjustments');
            exit;
        }
        if ($action === 'reject') {
            requireRole(['Admin', 'Manager']);
            $id = (int) ($_POST['id'] ?? 0);
            $pdo->prepare("UPDATE stock_adjustments SET status='Rejected',approved_by=? WHERE id=?")->execute([$_SESSION['user_id'], $id]);
            setFlash('success', 'Adjustment rejected.');
            header('Location: ?page=adjustments');
            exit;
        }
    }
    if ($action === 'add') {
        requireRole(['Admin', 'Manager', 'Staff']);
        $products = $pdo->query('SELECT id,sku,name,current_stock,unit FROM products WHERE status=1 ORDER BY name')->fetchAll();
        renderAdjustmentForm($products);
        return;
    }
    $pg = max(1, (int) ($_GET['pg'] ?? 1));
    $perPage = (int) getSetting('items_per_page', 20);
    $total = $pdo->query('SELECT COUNT(*) FROM stock_adjustments')->fetchColumn();
    $offset = ($pg - 1) * $perPage;
    $adjs = $pdo->query("SELECT sa.*,p.name as product_name,p.sku,u.full_name as created_by_name,a.full_name as approved_by_name FROM stock_adjustments sa JOIN products p ON sa.product_id=p.id JOIN users u ON sa.created_by=u.id LEFT JOIN users a ON sa.approved_by=a.id ORDER BY sa.created_at DESC LIMIT $perPage OFFSET $offset")->fetchAll();
    renderAdjustmentList($adjs, $total, $pg, $perPage);
}

function handleTransfers($action)
{
    $pdo = getDB();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) {
            setFlash('error', 'Invalid CSRF.');
            header('Location: ?page=transfers');
            exit;
        }
        requireRole(['Admin', 'Manager']);
        if ($action === 'add') {
            $fromLoc = trim($_POST['from_location'] ?? '');
            $toLoc = trim($_POST['to_location'] ?? '');
            $tDate = $_POST['transfer_date'] ?? date('Y-m-d');
            $notes = trim($_POST['notes'] ?? '');
            $prodIds = $_POST['product_id'] ?? [];
            $quantities = $_POST['quantity'] ?? [];
            if (!$fromLoc || !$toLoc || empty($prodIds)) {
                setFlash('error', 'All fields required.');
                header('Location: ?page=transfers&action=add');
                exit;
            }
            $ref = generateTransferRef();
            $pdo->prepare("INSERT INTO stock_transfers (reference_no,from_location,to_location,transfer_date,status,notes,created_by) VALUES (?,?,?,?,'Pending',?,?)")->execute([$ref, $fromLoc, $toLoc, $tDate, $notes, $_SESSION['user_id']]);
            $tId = $pdo->lastInsertId();
            foreach ($prodIds as $k => $pid) {
                $pid = (int) $pid;
                $qty = (float) $quantities[$k];
                $pdo->prepare('INSERT INTO stock_transfer_items (transfer_id,product_id,quantity) VALUES (?,?,?)')->execute([$tId, $pid, $qty]);
            }
            logActivity($_SESSION['user_id'], $_SESSION['username'], 'Create Transfer', 'Transfers', "Ref: $ref, From: $fromLoc To: $toLoc");
            setFlash('success', "Transfer $ref created.");
            header('Location: ?page=transfers');
            exit;
        }
        if ($action === 'complete') {
            $id = (int) ($_POST['id'] ?? 0);
            $transfer = $pdo->prepare('SELECT * FROM stock_transfers WHERE id=?');
            $transfer->execute([$id]);
            $transfer = $transfer->fetch();
            if ($transfer && $transfer['status'] === 'Pending') {
                $items = $pdo->prepare('SELECT * FROM stock_transfer_items WHERE transfer_id=?');
                $items->execute([$id]);
                $items = $items->fetchAll();
                foreach ($items as $item) {
                    $pdo->prepare('UPDATE products SET current_stock=current_stock-? WHERE id=?')->execute([$item['quantity'], $item['product_id']]);
                }
                $pdo->prepare("UPDATE stock_transfers SET status='Completed' WHERE id=?")->execute([$id]);
                setFlash('success', 'Transfer completed and stock updated.');
            }
            header('Location: ?page=transfers');
            exit;
        }
        if ($action === 'cancel') {
            $id = (int) ($_POST['id'] ?? 0);
            $pdo->prepare("UPDATE stock_transfers SET status='Cancelled' WHERE id=?")->execute([$id]);
            setFlash('success', 'Transfer cancelled.');
            header('Location: ?page=transfers');
            exit;
        }
    }
    if ($action === 'add') {
        $products = $pdo->query('SELECT id,sku,name,current_stock,unit,location FROM products WHERE status=1 ORDER BY name')->fetchAll();
        $locations = $pdo->query("SELECT DISTINCT location FROM products WHERE location IS NOT NULL AND location != '' ORDER BY location")->fetchAll(PDO::FETCH_COLUMN);
        renderTransferForm($products, $locations);
        return;
    }
    $pg = max(1, (int) ($_GET['pg'] ?? 1));
    $perPage = (int) getSetting('items_per_page', 20);
    $total = $pdo->query('SELECT COUNT(*) FROM stock_transfers')->fetchColumn();
    $offset = ($pg - 1) * $perPage;
    $transfers = $pdo->query("SELECT st.*,u.full_name as created_by_name FROM stock_transfers st JOIN users u ON st.created_by=u.id ORDER BY st.created_at DESC LIMIT $perPage OFFSET $offset")->fetchAll();
    renderTransferList($transfers, $total, $pg, $perPage, '?page=transfers');
}

function handleReports()
{
    $pdo = getDB();
    $report = $_GET['report'] ?? 'stock';
    $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');
    $export = $_GET['export'] ?? '';
    $data = [];
    $title = '';
    switch ($report) {
        case 'stock':
            $title = 'Stock Report';
            $data = $pdo->query('SELECT p.*,c.name as category_name,(p.current_stock*p.purchase_price) as stock_value FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.status=1 ORDER BY p.name')->fetchAll();
            break;
        case 'sales':
            $title = 'Sales Report';
            $data = $pdo->query("SELECT i.*,c.name as customer_name FROM invoices i JOIN customers c ON i.customer_id=c.id WHERE i.invoice_date BETWEEN '$dateFrom' AND '$dateTo' AND i.is_return=0 ORDER BY i.invoice_date DESC")->fetchAll();
            break;
        case 'purchases':
            $title = 'Purchase Report';
            $data = $pdo->query("SELECT po.*,s.company_name FROM purchase_orders po JOIN suppliers s ON po.supplier_id=s.id WHERE po.po_date BETWEEN '$dateFrom' AND '$dateTo' ORDER BY po.po_date DESC")->fetchAll();
            break;
        case 'low_stock':
            $title = 'Low Stock Report';
            $data = $pdo->query('SELECT p.*,c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.current_stock<=p.min_stock AND p.status=1 ORDER BY (p.current_stock/GREATEST(p.min_stock,1)) ASC')->fetchAll();
            break;
        case 'profit_loss':
            $title = 'Profit & Loss Report';
            $data = $pdo->query("SELECT ii.product_id,p.name,p.sku,SUM(ii.quantity) as total_qty,SUM(ii.total_price) as total_revenue,SUM(ii.quantity*p.purchase_price) as total_cost,SUM(ii.total_price-(ii.quantity*p.purchase_price)) as profit FROM invoice_items ii JOIN products p ON ii.product_id=p.id JOIN invoices i ON ii.invoice_id=i.id WHERE i.invoice_date BETWEEN '$dateFrom' AND '$dateTo' AND i.is_return=0 GROUP BY ii.product_id,p.name,p.sku ORDER BY profit DESC")->fetchAll();
            break;
        case 'valuation':
            $title = 'Inventory Valuation';
            $data = $pdo->query('SELECT p.*,c.name as category_name,(p.current_stock*p.purchase_price) as purchase_value,(p.current_stock*p.selling_price) as selling_value FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.status=1 ORDER BY p.name')->fetchAll();
            break;
        case 'supplier_purchases':
            $title = 'Supplier-wise Purchases';
            $data = $pdo->query("SELECT s.company_name,COUNT(po.id) as order_count,SUM(po.total_amount) as total_amount,SUM(CASE WHEN po.payment_status='Paid' THEN po.total_amount ELSE 0 END) as paid_amount,SUM(CASE WHEN po.payment_status!='Paid' THEN po.total_amount ELSE 0 END) as unpaid_amount FROM purchase_orders po JOIN suppliers s ON po.supplier_id=s.id WHERE po.po_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY s.id,s.company_name ORDER BY total_amount DESC")->fetchAll();
            break;
        case 'customer_sales':
            $title = 'Customer-wise Sales';
            $data = $pdo->query("SELECT c.name,c.customer_type,COUNT(i.id) as invoice_count,SUM(i.total_amount) as total_amount,SUM(CASE WHEN i.payment_status='Paid' THEN i.total_amount ELSE 0 END) as paid_amount,SUM(CASE WHEN i.payment_status!='Paid' THEN i.total_amount ELSE 0 END) as unpaid_amount FROM invoices i JOIN customers c ON i.customer_id=c.id WHERE i.invoice_date BETWEEN '$dateFrom' AND '$dateTo' AND i.is_return=0 GROUP BY c.id,c.name,c.customer_type ORDER BY total_amount DESC")->fetchAll();
            break;
        case 'product_sales':
            $title = 'Product-wise Sales';
            $data = $pdo->query("SELECT p.name,p.sku,p.unit,SUM(ii.quantity) as total_qty,SUM(ii.total_price) as total_revenue FROM invoice_items ii JOIN products p ON ii.product_id=p.id JOIN invoices i ON ii.invoice_id=i.id WHERE i.invoice_date BETWEEN '$dateFrom' AND '$dateTo' AND i.is_return=0 GROUP BY p.id,p.name,p.sku,p.unit ORDER BY total_revenue DESC")->fetchAll();
            break;
        case 'dead_stock':
            $title = 'Dead Stock Report (No movement 60+ days)';
            $data = $pdo->query('SELECT p.*,c.name as category_name,MAX(i.invoice_date) as last_sale FROM products p LEFT JOIN categories c ON p.category_id=c.id LEFT JOIN invoice_items ii ON p.id=ii.product_id LEFT JOIN invoices i ON ii.invoice_id=i.id WHERE p.status=1 GROUP BY p.id HAVING last_sale IS NULL OR last_sale < DATE_SUB(NOW(),INTERVAL 60 DAY) ORDER BY p.current_stock DESC')->fetchAll();
            break;
        case 'stock_movement':
            $title = 'Stock Movement Report';
            $data = $pdo->query("SELECT sa.*,p.name as product_name,p.sku,u.full_name as created_by_name FROM stock_adjustments sa JOIN products p ON sa.product_id=p.id JOIN users u ON sa.created_by=u.id WHERE sa.created_at BETWEEN '$dateFrom 00:00:00' AND '$dateTo 23:59:59' ORDER BY sa.created_at DESC")->fetchAll();
            break;
    }
    if ($export === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . str_replace(' ', '_', $title) . '_' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');
        if (!empty($data)) {
            fputcsv($out, array_keys($data[0]));
            foreach ($data as $row)
                fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }
    renderReports($report, $title, $data, $dateFrom, $dateTo);
}

function handleUsers($action)
{
    requireRole(['Admin']);
    $pdo = getDB();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) {
            setFlash('error', 'Invalid CSRF.');
            header('Location: ?page=users');
            exit;
        }
        if ($action === 'add' || $action === 'edit') {
            $id = (int) ($_POST['id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? 'Staff';
            $status = (int) ($_POST['status'] ?? 1);
            $password = $_POST['password'] ?? '';
            if (strlen($username) < 3 || strlen($fullName) < 2) {
                setFlash('error', 'Username and full name required.');
                header('Location: ?page=users');
                exit;
            }
            if (!$id && strlen($password) < 8) {
                setFlash('error', 'Password min 8 characters required.');
                header('Location: ?page=users');
                exit;
            }
            if ($password && !preg_match('/[^a-zA-Z0-9]/', $password)) {
                setFlash('error', 'Password must contain at least 1 special character.');
                header('Location: ?page=users');
                exit;
            }
            if ($id) {
                $fields = 'username=?,full_name=?,email=?,role=?,status=?';
                $params = [$username, $fullName, $email, $role, $status];
                if ($password) {
                    $fields .= ',password=?';
                    $params[] = password_hash($password, PASSWORD_BCRYPT);
                }
                $params[] = $id;
                $pdo->prepare("UPDATE users SET $fields WHERE id=?")->execute($params);
                setFlash('success', 'User updated.');
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                try {
                    $pdo->prepare('INSERT INTO users (username,password,full_name,email,role,status) VALUES (?,?,?,?,?,?)')->execute([$username, $hash, $fullName, $email, $role, $status]);
                    setFlash('success', 'User created.');
                } catch (PDOException $e) {
                    setFlash('error', 'Username or email already exists.');
                }
            }
            logActivity($_SESSION['user_id'], $_SESSION['username'], ($id ? 'Update' : 'Add') . ' User', 'Users', "Username: $username");
            header('Location: ?page=users');
            exit;
        }
        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id == $_SESSION['user_id']) {
                setFlash('error', 'Cannot delete your own account.');
                header('Location: ?page=users');
                exit;
            }
            $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
            setFlash('success', 'User deleted.');
            header('Location: ?page=users');
            exit;
        }
        if ($action === 'reset_attempts') {
            $id = (int) ($_POST['id'] ?? 0);
            $pdo->prepare('UPDATE users SET login_attempts=0,locked_until=NULL WHERE id=?')->execute([$id]);
            setFlash('success', 'Login attempts reset.');
            header('Location: ?page=users');
            exit;
        }
        if ($action === 'toggle') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id == $_SESSION['user_id']) {
                setFlash('error', 'Cannot change your own status.');
                header('Location: ?page=users');
                exit;
            }
            $pdo->prepare('UPDATE users SET status = 1 - status WHERE id=?')->execute([$id]);
            setFlash('success', 'User status toggled.');
            header('Location: ?page=users');
            exit;
        }
    }
    $users = $pdo->query('SELECT * FROM users ORDER BY full_name')->fetchAll();
    renderUsers($users);
}

function handleSettings()
{
    requireRole(['Admin']);
    $pdo = getDB();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) {
            setFlash('error', 'Invalid CSRF.');
            header('Location: ?page=settings');
            exit;
        }
        $keys = ['company_name', 'company_address', 'company_phone', 'company_email', 'company_website', 'tax_number', 'currency', 'currency_symbol', 'date_format', 'timezone', 'low_stock_threshold', 'items_per_page', 'invoice_prefix', 'invoice_start', 'default_tax', 'default_payment_terms', 'company_logo'];
        foreach ($keys as $k) {
            if (isset($_POST[$k])) {
                $val = trim($_POST[$k]);
                $pdo->prepare('INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?')->execute([$k, $val, $val]);
            }
        }
        logActivity($_SESSION['user_id'], $_SESSION['username'], 'Update Settings', 'Settings', 'System settings updated.');
        setFlash('success', 'Settings saved.');
        header('Location: ?page=settings');
        exit;
    }
    $settings = [];
    $rows = $pdo->query('SELECT setting_key,setting_value FROM settings')->fetchAll();
    foreach ($rows as $r)
        $settings[$r['setting_key']] = $r['setting_value'];
    renderSettings($settings);
}

function handleActivity()
{
    requireRole(['Admin', 'Manager']);
    $pdo = getDB();
    $search = trim($_GET['search'] ?? '');
    $module = $_GET['module'] ?? '';
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    $pg = max(1, (int) ($_GET['pg'] ?? 1));
    $perPage = (int) getSetting('items_per_page', 20);
    $where = '1=1';
    $params = [];
    if ($search) {
        $where .= ' AND (username LIKE ? OR action LIKE ? OR description LIKE ?)';
        $params = ["%$search%", "%$search%", "%$search%"];
    }
    if ($module) {
        $where .= ' AND module=?';
        $params[] = $module;
    }
    if ($dateFrom) {
        $where .= ' AND DATE(created_at)>=?';
        $params[] = $dateFrom;
    }
    if ($dateTo) {
        $where .= ' AND DATE(created_at)<=?';
        $params[] = $dateTo;
    }
    $total = $pdo->prepare("SELECT COUNT(*) FROM activity_log WHERE $where");
    $total->execute($params);
    $total = $total->fetchColumn();
    $offset = ($pg - 1) * $perPage;
    $stmt = $pdo->prepare("SELECT * FROM activity_log WHERE $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    $modules = $pdo->query('SELECT DISTINCT module FROM activity_log ORDER BY module')->fetchAll(PDO::FETCH_COLUMN);
    $baseUrl = '?page=activity&search=' . urlencode($search) . "&module=$module&date_from=$dateFrom&date_to=$dateTo";
    renderActivity($logs, $modules, $search, $module, $dateFrom, $dateTo, $total, $pg, $perPage, $baseUrl);
}

function handleProfile($action)
{
    requireLogin();
    $pdo = getDB();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCSRF($_POST[CSRF_TOKEN_NAME] ?? '')) {
            setFlash('error', 'Invalid CSRF.');
            header('Location: ?page=profile');
            exit;
        }

        if (isset($_POST['full_name'])) {  // Profile update form
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $pdo->prepare('UPDATE users SET full_name=?,email=? WHERE id=?')->execute([$fullName, $email, $_SESSION['user_id']]);
            $_SESSION['full_name'] = $fullName;
            logActivity($_SESSION['user_id'], $_SESSION['username'], 'Update Profile', 'Profile', 'Profile updated.');
            setFlash('success', 'Profile updated.');
        } elseif (isset($_POST['current_password'])) {  // Password change form
            $currentPass = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';

            if (empty($newPass)) {
                setFlash('error', 'New password cannot be empty.');
                header('Location: ?page=profile');
                exit;
            }

            $user = $pdo->prepare('SELECT * FROM users WHERE id=?');
            $user->execute([$_SESSION['user_id']]);
            $user = $user->fetch();

            if (!password_verify($currentPass, $user['password'])) {
                setFlash('error', 'Current password is incorrect.');
                header('Location: ?page=profile');
                exit;
            }
            if (strlen($newPass) < 8) {
                setFlash('error', 'Password min 8 chars.');
                header('Location: ?page=profile');
                exit;
            }
            if (!preg_match('/[^a-zA-Z0-9]/', $newPass)) {
                setFlash('error', 'Password needs 1 special character.');
                header('Location: ?page=profile');
                exit;
            }
            if ($newPass !== $confirmPass) {
                setFlash('error', 'Passwords do not match.');
                header('Location: ?page=profile');
                exit;
            }
            $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($newPass, PASSWORD_BCRYPT), $_SESSION['user_id']]);
            logActivity($_SESSION['user_id'], $_SESSION['username'], 'Update Password', 'Profile', 'Password updated.');
            setFlash('success', 'Password updated.');
        }

        header('Location: ?page=profile');
        exit;
    }
    $user = $pdo->prepare('SELECT * FROM users WHERE id=?');
    $user->execute([$_SESSION['user_id']]);
    $user = $user->fetch();
    renderProfile($user);
}

function handleBackup()
{
    requireRole(['Admin']);
    $pdo = getDB();
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $sql = "-- InventoryPro Database Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n-- Database: " . DB_NAME . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";
    foreach ($tables as $table) {
        $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
        $sql .= "DROP TABLE IF EXISTS `$table`;\n" . $create['Create Table'] . ";\n\n";
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll();
        foreach ($rows as $row) {
            $vals = array_map(function ($v) use ($pdo) {
                return $v === null ? 'NULL' : $pdo->quote($v);
            }, $row);
            $sql .= "INSERT INTO `$table` VALUES (" . implode(',', $vals) . ");\n";
        }
        $sql .= "\n";
    }
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    logActivity($_SESSION['user_id'], $_SESSION['username'], 'Database Backup', 'Settings', 'Database backup downloaded.');
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="inventorypro_backup_' . date('Ymd_His') . '.sql"');
    echo $sql;
    exit;
}

function getDashboardStats()
{
    $pdo = getDB();
    $today = date('Y-m-d');
    $stats = [];
    $stats['total_products'] = $pdo->query('SELECT COUNT(*) FROM products WHERE status=1')->fetchColumn();
    $threshold = (int) getSetting('low_stock_threshold', 10);
    $stats['low_stock'] = $pdo->query('SELECT COUNT(*) FROM products WHERE current_stock<=min_stock AND status=1')->fetchColumn();
    $stats['today_sales'] = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM invoices WHERE DATE(created_at)='$today' AND is_return=0")->fetchColumn();
    $stats['today_purchases'] = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM purchase_orders WHERE DATE(created_at)='$today'")->fetchColumn();
    $stats['total_revenue'] = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM invoices WHERE is_return=0 AND payment_status='Paid'")->fetchColumn();
    $stats['total_customers'] = $pdo->query('SELECT COUNT(*) FROM customers WHERE status=1')->fetchColumn();
    $stats['total_suppliers'] = $pdo->query('SELECT COUNT(*) FROM suppliers WHERE status=1')->fetchColumn();
    $stats['pending_orders'] = $pdo->query("SELECT COUNT(*) FROM purchase_orders WHERE order_status='Pending'")->fetchColumn();
    $stats['inventory_value'] = $pdo->query('SELECT COALESCE(SUM(current_stock*purchase_price),0) FROM products WHERE status=1')->fetchColumn();
    $stats['unpaid_invoices'] = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM invoices WHERE payment_status!='Paid' AND is_return=0")->fetchColumn();
    return $stats;
}

function getRecentTransactions()
{
    $pdo = getDB();
    $sales = $pdo->query("SELECT 'Sale' as type,invoice_number as ref_no,c.name as party,total_amount,payment_status,i.created_at FROM invoices i JOIN customers c ON i.customer_id=c.id WHERE i.is_return=0 ORDER BY i.created_at DESC LIMIT 5")->fetchAll();
    $purchases = $pdo->query("SELECT 'Purchase' as type,po_number as ref_no,s.company_name as party,total_amount,payment_status,po.created_at FROM purchase_orders po JOIN suppliers s ON po.supplier_id=s.id ORDER BY po.created_at DESC LIMIT 5")->fetchAll();
    $combined = array_merge($sales, $purchases);
    usort($combined, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
    return array_slice($combined, 0, 10);
}

function getLowStockProducts()
{
    $pdo = getDB();
    return $pdo->query('SELECT p.*,c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.current_stock<=p.min_stock AND p.status=1 ORDER BY (p.current_stock/GREATEST(p.min_stock,1)) ASC LIMIT 10')->fetchAll();
}

function getSalesChartData()
{
    $pdo = getDB();
    $data = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $label = date('D', strtotime($date));
        $amount = $pdo->prepare('SELECT COALESCE(SUM(total_amount),0) FROM invoices WHERE DATE(created_at)=? AND is_return=0');
        $amount->execute([$date]);
        $amount = $amount->fetchColumn();
        $data[] = ['label' => $label, 'date' => $date, 'amount' => (float) $amount];
    }
    return $data;
}

function getTopProducts()
{
    $pdo = getDB();
    return $pdo->query('SELECT p.name,p.sku,SUM(ii.quantity) as total_qty,SUM(ii.total_price) as total_revenue FROM invoice_items ii JOIN products p ON ii.product_id=p.id JOIN invoices i ON ii.invoice_id=i.id WHERE i.is_return=0 GROUP BY p.id,p.name,p.sku ORDER BY total_revenue DESC LIMIT 5')->fetchAll();
}

function getNotificationCount()
{
    $pdo = getDB();
    $lowStock = $pdo->query('SELECT COUNT(*) FROM products WHERE current_stock<=min_stock AND status=1')->fetchColumn();
    $overdueInv = $pdo->query("SELECT COUNT(*) FROM invoices WHERE payment_status!='Paid' AND due_date<NOW() AND is_return=0")->fetchColumn();
    $overduePO = $pdo->query("SELECT COUNT(*) FROM purchase_orders WHERE payment_status!='Paid' AND expected_delivery<NOW()")->fetchColumn();
    return (int) $lowStock + (int) $overdueInv + (int) $overduePO;
}

function renderHeader($pageTitle = '')
{
    $user = $_SESSION['full_name'] ?? 'User';
    $role = $_SESSION['user_role'] ?? 'Staff';
    $csrf = generateCSRF();
    $notifCount = getNotificationCount();
    $flashSuccess = getFlash('success');
    $flashError = getFlash('error');
    $currentPage = $_GET['page'] ?? 'dashboard';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= h($csrf) ?>">
<title><?= APP_NAME ?><?= $pageTitle ? ' - ' . h($pageTitle) : '' ?></title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
--bg:#f0f0f0;--surface:#e8e8e8;--surface2:#f5f5f5;--border:#999;--border-dark:#666;
--text:#1a1a1a;--text-muted:#555;--text-light:#888;
--blue:#4a90d9;--blue-dark:#2c6fad;--blue-light:#d0e6f7;
--green:#5cb85c;--green-dark:#3d8b3d;--green-light:#d4edda;
--red:#d9534f;--red-dark:#a02020;--red-light:#f8d7da;
--orange:#f0ad4e;--orange-dark:#c07a00;--orange-light:#fff3cd;
--gray-btn:#c0c0c0;--gray-btn-dark:#888;
--sidebar-w:220px;--header-h:42px;--status-h:26px;
--font:'Segoe UI',Arial,sans-serif;
}
html,body{height:100%;overflow:hidden}
body{font-family:var(--font);font-size:13px;background:var(--bg);color:var(--text);display:flex;flex-direction:column}
a{color:var(--blue-dark);text-decoration:none}
a:hover{text-decoration:underline}
button,input,select,textarea{font-family:var(--font);font-size:13px}
input[type=text],input[type=email],input[type=number],input[type=password],input[type=date],input[type=search],textarea,select{
border:2px inset #aaa;background:#fff;padding:4px 6px;color:var(--text);width:100%;outline:none;
}
input[type=text]:focus,input[type=email]:focus,input[type=number]:focus,input[type=password]:focus,input[type=date]:focus,input[type=search]:focus,textarea:focus,select:focus{
border-color:#4a90d9;background:#fffff8;
}
input[type=checkbox],input[type=radio]{width:auto;margin-right:4px;accent-color:var(--blue)}
.btn{
display:inline-block;padding:5px 12px;border:2px outset #bbb;cursor:pointer;
font-size:12px;font-weight:600;letter-spacing:0.2px;
background:var(--gray-btn);color:var(--text);text-align:center;
}
.btn:hover{filter:brightness(0.95)}
.btn:active{border-style:inset}
.btn-primary{background:var(--blue);color:#fff;border-color:#3a7fc9}
.btn-primary:hover{background:var(--blue-dark)}
.btn-success{background:var(--green);color:#fff;border-color:#4a9a4a}
.btn-success:hover{background:var(--green-dark)}
.btn-danger{background:var(--red);color:#fff;border-color:#b03030}
.btn-danger:hover{background:var(--red-dark)}
.btn-warning{background:var(--orange);color:#333;border-color:#c0802e}
.btn-warning:hover{background:var(--orange-dark);color:#fff}
.btn-sm{padding:3px 8px;font-size:11px}
.btn-xs{padding:2px 6px;font-size:11px}
.btn-icon{padding:4px 8px;min-width:28px}
#app-header{
height:var(--header-h);background:#2c2c2c;color:#eee;
display:flex;align-items:center;padding:0 8px;gap:8px;
border-bottom:2px solid #111;flex-shrink:0;
}
#app-header .app-logo{font-size:15px;font-weight:700;color:#fff;letter-spacing:1px;white-space:nowrap}
#app-header .app-logo span{color:var(--orange)}
#app-header .header-spacer{flex:1}
.notif-btn{position:relative;background:none;border:1px solid #555;padding:3px 8px;color:#ddd;cursor:pointer;font-size:12px;border-radius:2px}
.notif-btn:hover{background:#444}
.notif-badge{position:absolute;top:-6px;right:-6px;background:var(--red);color:#fff;font-size:10px;font-weight:700;padding:1px 4px;border-radius:10px;min-width:16px;text-align:center}
.user-menu-wrap{position:relative}
.user-btn{background:none;border:1px solid #555;padding:3px 10px;color:#ddd;cursor:pointer;font-size:12px}
.user-btn:hover{background:#444}
.user-dropdown{
display:none;position:absolute;right:0;top:100%;background:#3a3a3a;border:1px solid #555;
min-width:160px;z-index:9999;
}
.user-dropdown.open{display:block}
.user-dropdown a,.user-dropdown button{
display:block;width:100%;padding:7px 12px;color:#ddd;background:none;
border:none;text-align:left;cursor:pointer;font-size:12px;
}
.user-dropdown a:hover,.user-dropdown button:hover{background:#555;text-decoration:none}
.hamburger{display:block;background:none;border:1px solid #555;padding:4px 8px;color:#ddd;cursor:pointer;font-size:16px}
#sidebar { transition: width 0.25s; overflow-x: hidden; overflow-y: auto !important; padding-bottom: 60px; }
#sidebar::-webkit-scrollbar { width: 4px; }
#sidebar::-webkit-scrollbar-thumb { background: #666; border-radius: 4px; }
#sidebar.collapsed { width: 44px; }
#sidebar.collapsed .sidebar-section-title { opacity: 0; pointer-events: none; }
#sidebar .sidebar-section-title { white-space: nowrap; overflow: hidden; transition: opacity 0.25s; }
#sidebar a.nav-item { white-space: nowrap; overflow: hidden; }
#main-container{display:flex;flex:1;overflow:hidden}
#sidebar{
width:var(--sidebar-w);background:#2a2a2a;color:#ccc;
display:flex;flex-direction:column;flex-shrink:0;overflow-y:auto;
border-right:2px solid #111;
}
#sidebar .sidebar-section-title{
font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;
color:#888;padding:10px 10px 4px;border-top:1px solid #3a3a3a;
}
#sidebar .sidebar-section-title:first-child{border-top:none}
#sidebar a.nav-item{
display:flex;align-items:center;gap:8px;padding:7px 14px;
color:#bbb;font-size:12px;font-weight:500;
border:none;background:none;cursor:pointer;width:100%;text-align:left;
border-left:3px solid transparent;
}
#sidebar a.nav-item:hover{background:#3a3a3a;color:#fff;text-decoration:none}
#sidebar a.nav-item.active{background:#1a3a5a;color:#fff;border-left-color:var(--blue)}
#sidebar a.nav-item .nav-icon{width:16px;text-align:center;font-size:14px;flex-shrink:0}
#main-content{flex:1;overflow-y:auto;display:flex;flex-direction:column}
#content-area{flex:1;padding:10px;overflow-y:auto}
#status-bar{
height:var(--status-h);background:#2a2a2a;color:#aaa;
display:flex;align-items:center;padding:0 10px;gap:16px;
border-top:1px solid #111;flex-shrink:0;font-size:11px;
}
#status-bar .sb-item{display:flex;align-items:center;gap:4px}
.page-title{
background:#ddd;border-bottom:2px solid #999;padding:6px 10px;
font-size:14px;font-weight:700;display:flex;align-items:center;gap:8px;
flex-shrink:0;
}
.page-title .page-title-actions{margin-left:auto;display:flex;gap:6px;align-items:center}
.card{background:var(--surface2);border:1px solid var(--border);padding:10px;margin-bottom:8px}
.card-title{font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);border-bottom:1px solid var(--border);padding-bottom:5px;margin-bottom:8px}
.stat-card{background:var(--surface2);border:2px solid var(--border);padding:10px 14px;display:flex;flex-direction:column;gap:4px}
.stat-card .stat-value{font-size:22px;font-weight:700;font-variant-numeric:tabular-nums}
.stat-card .stat-label{font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px}
.stat-card.blue{border-left:4px solid var(--blue)}
.stat-card.green{border-left:4px solid var(--green)}
.stat-card.red{border-left:4px solid var(--red)}
.stat-card.orange{border-left:4px solid var(--orange)}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px;margin-bottom:10px}
table.data-table{width:100%;border-collapse:collapse;font-size:12px;background:var(--surface2)}
table.data-table thead tr{background:#2c2c2c;color:#eee}
table.data-table th{padding:7px 8px;text-align:left;font-size:11px;font-weight:700;letter-spacing:0.3px;border:1px solid #444;white-space:nowrap}
table.data-table td{padding:5px 8px;border:1px solid #ccc;vertical-align:middle}
table.data-table tbody tr:nth-child(even){background:#ebebeb}
table.data-table tbody tr:hover{background:#d0e6f7}
table.data-table tbody tr.selected{background:#b8d4f0}
.table-wrap{overflow-x:auto;width:100%}
.badge{display:inline-block;padding:2px 7px;font-size:11px;font-weight:600;border:1px solid transparent}
.badge-success{background:var(--green-light);color:var(--green-dark);border-color:var(--green-dark)}
.badge-danger{background:var(--red-light);color:var(--red-dark);border-color:var(--red-dark)}
.badge-warning{background:var(--orange-light);color:var(--orange-dark);border-color:var(--orange-dark)}
.badge-info{background:var(--blue-light);color:var(--blue-dark);border-color:var(--blue-dark)}
.badge-secondary{background:#e0e0e0;color:#555;border-color:#999}
.stock-good{color:var(--green-dark);font-weight:700}
.stock-low{color:var(--orange-dark);font-weight:700}
.stock-critical{color:var(--red-dark);font-weight:700}
.form-group{margin-bottom:10px}
.form-group label{display:block;font-size:12px;font-weight:600;margin-bottom:3px;color:var(--text-muted)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.form-row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
.form-section{border:1px solid var(--border);padding:10px;margin-bottom:10px}
.form-section legend{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);padding:0 4px;background:var(--bg)}
.filter-bar{background:var(--surface);border:1px solid var(--border);padding:8px 10px;margin-bottom:8px;display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end}
.filter-bar .filter-group{display:flex;flex-direction:column;gap:2px;min-width:120px}
.filter-bar .filter-group label{font-size:11px;font-weight:600;color:var(--text-muted)}
.filter-bar .filter-group input,.filter-bar .filter-group select{width:100%}
.pagination{display:flex;gap:3px;align-items:center;padding:6px 0;flex-wrap:wrap}
.page-btn{padding:3px 8px;border:1px solid var(--border);background:var(--surface2);color:var(--text);font-size:12px;cursor:pointer;display:inline-block}
.page-btn:hover{background:var(--blue-light);text-decoration:none}
.page-btn.active{background:var(--blue);color:#fff;border-color:var(--blue-dark)}
.page-dots{padding:3px 4px;font-size:12px}
.page-info{font-size:11px;color:var(--text-muted);margin-left:6px}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:flex-start;justify-content:center;padding:20px;overflow-y:auto}
.modal-overlay.open{display:flex}
.modal{background:var(--bg);border:2px solid #555;width:100%;max-width:600px;margin:auto}
.modal-title-bar{background:#2c2c2c;color:#fff;padding:6px 10px;display:flex;align-items:center;justify-content:space-between;font-weight:700;font-size:13px}
.modal-title-bar .modal-close{background:var(--red);border:none;color:#fff;padding:2px 8px;cursor:pointer;font-size:14px;font-weight:700}
.modal-body{padding:12px}
.modal-footer{padding:8px 12px;background:var(--surface);border-top:1px solid var(--border);display:flex;gap:6px;justify-content:flex-end}
.alert{padding:8px 12px;margin-bottom:8px;border:1px solid transparent;font-size:12px}
.alert-success{background:var(--green-light);border-color:var(--green-dark);color:var(--green-dark)}
.alert-error{background:var(--red-light);border-color:var(--red-dark);color:var(--red-dark)}
.alert-warning{background:var(--orange-light);border-color:var(--orange-dark);color:var(--orange-dark)}
.toast-container{position:fixed;top:50px;right:10px;z-index:9999;display:flex;flex-direction:column;gap:6px;pointer-events:none}
.toast{background:#2c2c2c;color:#fff;padding:8px 14px;border-left:4px solid var(--blue);font-size:12px;min-width:250px;max-width:380px;pointer-events:all;border:1px solid #555;border-left:4px solid var(--blue)}
.toast.toast-success{border-left-color:var(--green)}
.toast.toast-error{border-left-color:var(--red)}
.toast.toast-warning{border-left-color:var(--orange)}
.chart-bar-wrap{display:flex;align-items:flex-end;gap:4px;height:120px;padding:0 4px}
.chart-bar-col{display:flex;flex-direction:column;align-items:center;flex:1;height:100%}
.chart-bar{width:100%;background:var(--blue);min-height:2px;transition:height 0.3s}
.chart-bar-label{font-size:10px;color:var(--text-muted);margin-top:3px;white-space:nowrap}
.chart-bar-val{font-size:9px;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;text-align:center}
.two-col-layout{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.three-col-layout{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px}
.detail-table{width:100%;border-collapse:collapse;font-size:12px}
.detail-table td{padding:5px 8px;border:1px solid #ccc}
.detail-table td:first-child{font-weight:600;background:#eee;width:35%;white-space:nowrap}
.items-table-wrap{overflow-x:auto}
.items-table{width:100%;border-collapse:collapse;font-size:12px}
.items-table th{background:#2c2c2c;color:#eee;padding:5px 8px;text-align:left;border:1px solid #444}
.items-table td{padding:4px 8px;border:1px solid #ccc}
.items-table .remove-row-btn{background:var(--red);color:#fff;border:none;padding:2px 7px;cursor:pointer;font-size:11px}
.items-table tfoot td{background:#eee;font-weight:700}
#add-item-btn{background:var(--green);color:#fff;border:2px outset #3a8a3a;padding:4px 10px;cursor:pointer;font-size:12px;margin-bottom:8px}
#add-item-btn:active{border-style:inset}
.print-header{display:none}
.barcode-svg{font-family:monospace;display:inline-block}
.progress-bar-wrap{background:#ddd;border:1px solid #bbb;height:14px;position:relative;overflow:hidden}
.progress-bar-fill{height:100%;background:var(--blue);transition:width 0.3s}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:899}
@media(max-width:768px){
.hamburger{display:block}
#sidebar{position:fixed;left:-240px;top:0;bottom:0;z-index:900;transition:left 0.25s;width:220px}
#sidebar.collapsed{width:220px}
#sidebar.open{left:0}
.sidebar-overlay.open{display:block}
.form-row,.form-row-3{grid-template-columns:1fr}
.two-col-layout,.three-col-layout{grid-template-columns:1fr}
.stats-grid{grid-template-columns:1fr 1fr}
.modal{max-width:100%;margin:0}
.modal-overlay{padding:0;align-items:flex-start}
.filter-bar{flex-direction:column}
.filter-bar .filter-group{min-width:100%}
.page-title{flex-wrap:wrap}
.page-title .page-title-actions{margin-left:0;width:100%;margin-top:4px}
table.data-table thead{display:none}
table.data-table tbody tr{display:block;border:1px solid #ccc;margin-bottom:6px;background:#fff}
table.data-table tbody td{display:flex;justify-content:space-between;align-items:center;padding:4px 8px;border:none;border-bottom:1px solid #eee;font-size:12px}
table.data-table tbody td::before{content:attr(data-label);font-weight:700;font-size:11px;color:var(--text-muted);flex-shrink:0;margin-right:8px}
table.data-table tbody td:last-child{border-bottom:none}
#status-bar .sb-item:not(:first-child){display:none}
}
@media(max-width:480px){.stats-grid{grid-template-columns:1fr}}
@media print{
#app-header,#sidebar,#status-bar,.btn,.filter-bar,.pagination,.page-title-actions,.no-print{display:none!important}
#main-container{display:block}
#main-content,#content-area{overflow:visible;padding:0}
body{background:#fff;color:#000;font-size:11px}
.print-header{display:block!important;text-align:center;margin-bottom:16px}
.print-header h2{font-size:18px;margin-bottom:4px}
table.data-table thead{display:table-header-group!important}
table.data-table tbody tr{display:table-row!important;border:none}
table.data-table tbody td{display:table-cell!important;border:1px solid #ccc}
table.data-table tbody td::before{display:none}
}
</style>
</head>
<body>
<div id="app-header">
    <button class="hamburger" onclick="toggleSidebar()" title="Menu">&#9776;</button>
    <div class="app-logo"><?= APP_NAME ?><span>&#9632;</span></div>
    <div class="header-spacer"></div>
    <button class="notif-btn" onclick="openNotifications()" title="Notifications">
        &#128276;<?php if ($notifCount > 0): ?><span class="notif-badge"><?= h($notifCount) ?></span><?php endif; ?>
    </button>
    <div class="user-menu-wrap">
        <button class="user-btn" onclick="toggleUserMenu()">&#128100; <?= h($user) ?> [<?= h($role) ?>] &#9660;</button>
        <div class="user-dropdown" id="userDropdown">
            <a href="?page=profile">&#9881; Profile</a>
            <a href="?page=settings">&#9965; Settings</a>
            <?php if (hasRole(['Admin'])): ?>
            <a href="?page=backup">&#128190; Backup DB</a>
            <?php endif; ?>
            <a href="?page=logout" onclick="return confirm('Logout?')">&#10148; Logout</a>
        </div>
    </div>
</div>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
<div id="main-container">
<nav id="sidebar">
    <script>if(window.innerWidth > 768 && localStorage.getItem('sidebarCollapsed') === '1') document.getElementById('sidebar').classList.add('collapsed');</script>
    <div class="sidebar-section-title">Main</div>
    <a href="?page=dashboard" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>"><span class="nav-icon">&#9632;</span> Dashboard</a>
    <div class="sidebar-section-title">Inventory</div>
    <a href="?page=products" class="nav-item <?= $currentPage === 'products' ? 'active' : '' ?>"><span class="nav-icon">&#128230;</span> Products</a>
    <a href="?page=categories" class="nav-item <?= $currentPage === 'categories' ? 'active' : '' ?>"><span class="nav-icon">&#128193;</span> Categories</a>
    <a href="?page=adjustments" class="nav-item <?= $currentPage === 'adjustments' ? 'active' : '' ?>"><span class="nav-icon">&#9881;</span> Adjustments</a>
    <a href="?page=transfers" class="nav-item <?= $currentPage === 'transfers' ? 'active' : '' ?>"><span class="nav-icon">&#8646;</span> Transfers</a>
    <div class="sidebar-section-title">Transactions</div>
    <a href="?page=purchases" class="nav-item <?= $currentPage === 'purchases' ? 'active' : '' ?>"><span class="nav-icon">&#128722;</span> Purchases</a>
    <a href="?page=sales" class="nav-item <?= $currentPage === 'sales' ? 'active' : '' ?>"><span class="nav-icon">&#128176;</span> Sales / Invoices</a>
    <div class="sidebar-section-title">Parties</div>
    <a href="?page=suppliers" class="nav-item <?= $currentPage === 'suppliers' ? 'active' : '' ?>"><span class="nav-icon">&#128663;</span> Suppliers</a>
    <a href="?page=customers" class="nav-item <?= $currentPage === 'customers' ? 'active' : '' ?>"><span class="nav-icon">&#128101;</span> Customers</a>
    <div class="sidebar-section-title">Reports</div>
    <a href="?page=reports" class="nav-item <?= $currentPage === 'reports' ? 'active' : '' ?>"><span class="nav-icon">&#128202;</span> Reports</a>
    <?php if (hasRole(['Admin', 'Manager'])): ?>
    <a href="?page=activity" class="nav-item <?= $currentPage === 'activity' ? 'active' : '' ?>"><span class="nav-icon">&#128203;</span> Activity Log</a>
    <?php endif; ?>
    <?php if (hasRole(['Admin'])): ?>
    <div class="sidebar-section-title">Admin</div>
    <a href="?page=users" class="nav-item <?= $currentPage === 'users' ? 'active' : '' ?>"><span class="nav-icon">&#128100;</span> Users</a>
    <a href="?page=settings" class="nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>"><span class="nav-icon">&#9965;</span> Settings</a>
    <a href="?page=backup" class="nav-item"><span class="nav-icon">&#128190;</span> Backup DB</a>
    <?php endif; ?>
</nav>
<div id="main-content">
    <div id="content-area">
    <?php if ($flashSuccess): ?><div class="alert alert-success" id="flashMsg">&#10003; <?= h($flashSuccess) ?></div><?php endif; ?>
    <?php if ($flashError): ?><div class="alert alert-error" id="flashMsg">&#10007; <?= h($flashError) ?></div><?php endif; ?>
    <?php
}

function renderFooter()
{
    $user = $_SESSION['full_name'] ?? '';
    $role = $_SESSION['user_role'] ?? '';
    $page = $_GET['page'] ?? 'dashboard';
    ?>
    </div>
    <div id="status-bar">
        <span class="sb-item">&#128100; <?= h($user) ?> (<?= h($role) ?>)</span>
        <span class="sb-item">&#128197; <?= date('D, d M Y') ?></span>
        <span class="sb-item">&#128336; <span id="clock"><?= date('H:i:s') ?></span></span>
        <span class="sb-item">&#127759; <?= h(ucfirst($page)) ?></span>
    </div>
</div>
</div>
<div class="toast-container" id="toastContainer"></div>
<div id="notifModal" class="modal-overlay">
    <div class="modal" style="max-width:480px">
        <div class="modal-title-bar">&#128276; Notifications <button class="modal-close" onclick="closeModal('notifModal')">&#10005;</button></div>
        <div class="modal-body" id="notifContent">Loading...</div>
    </div>
</div>
<script>
function toggleSidebar(){
    if(window.innerWidth <= 768){
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('open');
    } else {
        var sb = document.getElementById('sidebar');
        sb.classList.toggle('collapsed');
        localStorage.setItem('sidebarCollapsed', sb.classList.contains('collapsed') ? '1' : '0');
    }
}
function toggleUserMenu(){
    document.getElementById('userDropdown').classList.toggle('open');
}
document.addEventListener('click',function(e){
    var wrap=document.querySelector('.user-menu-wrap');
    if(wrap&&!wrap.contains(e.target)) document.getElementById('userDropdown').classList.remove('open');
});
function openModal(id){document.getElementById(id).classList.add('open')}
function closeModal(id){document.getElementById(id).classList.remove('open')}
document.querySelectorAll('.modal-overlay').forEach(function(m){
    m.addEventListener('click',function(e){if(e.target===m)m.classList.remove('open');});
});
function showToast(msg,type){
    type=type||'info';
    var c=document.getElementById('toastContainer');
    var t=document.createElement('div');
    t.className='toast toast-'+type;
    t.textContent=msg;
    c.appendChild(t);
    setTimeout(function(){t.style.opacity='0';t.style.transition='opacity 0.5s';setTimeout(function(){t.remove()},500)},3500);
}
function confirmDelete(formId){
    if(confirm('Are you sure you want to delete this record? This action cannot be undone.')){
        document.getElementById(formId).submit();
    }
}
function confirmAction(msg,formId){
    if(confirm(msg)){document.getElementById(formId).submit();}
}
setInterval(function(){
    var el=document.getElementById('clock');
    if(el) el.textContent=new Date().toLocaleTimeString('en-GB');
},1000);
setTimeout(function(){
    var el=document.getElementById('flashMsg');
    if(el){el.style.transition='opacity 1s';el.style.opacity='0';setTimeout(function(){el&&el.remove()},1000);}
},4000);
function openNotifications(){
    var m=document.getElementById('notifModal');
    m.classList.add('open');
    fetch('?page=products&action=low_stock_json')
    .then(function(r){return r.json()})
    .catch(function(){return null})
    .then(function(d){
        var c=document.getElementById('notifContent');
        c.innerHTML='<p style="font-size:12px;color:#555;margin-bottom:8px">System notifications and alerts:</p>'
        +'<a href="?page=products&status=1" style="display:block;padding:6px;background:#fff3cd;border:1px solid #c07a00;margin-bottom:4px;font-size:12px">&#9888; Check Low Stock Products — go to Products and filter by low stock.</a>'
        +'<a href="?page=sales&status=Unpaid" style="display:block;padding:6px;background:#f8d7da;border:1px solid #a02020;margin-bottom:4px;font-size:12px">&#9940; Overdue Invoices — review unpaid sales invoices.</a>'
        +'<a href="?page=purchases&status=Pending" style="display:block;padding:6px;background:#d0e6f7;border:1px solid #2c6fad;font-size:12px">&#8505; Pending Purchase Orders — review pending POs.</a>';
    });
}
function toggleSelectAll(source){
    var checkboxes=document.querySelectorAll('input[name="selected_ids[]"]');
    checkboxes.forEach(function(cb){cb.checked=source.checked});
}
function exportCSV(url){window.location.href=url}
function printPage(){window.print()}
var idleTimer;
function resetIdle(){
    clearTimeout(idleTimer);
    idleTimer=setTimeout(function(){
        alert('Session expired due to inactivity. You will be logged out.');
        window.location.href='?page=logout';
    },<?= SESSION_TIMEOUT * 1000 ?>);
}
document.addEventListener('mousemove',resetIdle);
document.addEventListener('keypress',resetIdle);
resetIdle();
</script>
<?php
}

function renderLogin($error, $captcha)
{
    $captchaHtml = generateCaptchaImage($captcha['q']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= APP_NAME ?> - Login</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--blue:#4a90d9;--red:#d9534f;--font:'Segoe UI',Arial,sans-serif}
body{font-family:var(--font);background:#c8c8c8;display:flex;align-items:center;justify-content:center;min-height:100vh;background-image:repeating-linear-gradient(0deg,transparent,transparent 24px,rgba(0,0,0,0.05) 24px,rgba(0,0,0,0.05) 25px),repeating-linear-gradient(90deg,transparent,transparent 24px,rgba(0,0,0,0.05) 24px,rgba(0,0,0,0.05) 25px)}
.login-box{background:#f0f0f0;border:2px solid #999;border-bottom-color:#444;border-right-color:#444;width:100%;max-width:380px;padding:0}
.login-title-bar{background:#2c2c2c;color:#fff;padding:8px 14px;font-size:14px;font-weight:700;letter-spacing:1px;display:flex;align-items:center;gap:8px}
.login-title-bar span{color:#f0ad4e}
.login-body{padding:20px}
.form-group{margin-bottom:12px}
.form-group label{display:block;font-size:12px;font-weight:700;margin-bottom:3px;color:#444}
.form-group input{width:100%;border:2px inset #aaa;padding:6px 8px;font-size:13px;background:#fff}
.form-group input:focus{outline:none;border-color:#4a90d9;background:#fffff8}
.btn-login{width:100%;background:#4a90d9;color:#fff;border:2px outset #3a7fc9;padding:8px;font-size:14px;font-weight:700;cursor:pointer;letter-spacing:0.5px}
.btn-login:hover{background:#2c6fad}
.btn-login:active{border-style:inset}
.error-msg{background:#f8d7da;border:1px solid #a02020;color:#a02020;padding:7px 10px;font-size:12px;margin-bottom:12px}
.captcha-wrap{display:flex;align-items:center;gap:8px;margin-bottom:4px}
.captcha-img-wrap{flex-shrink:0}
.captcha-refresh{background:#c0c0c0;border:2px outset #aaa;padding:3px 8px;cursor:pointer;font-size:11px}
.captcha-refresh:active{border-style:inset}
.login-footer{font-size:11px;color:#666;padding:8px 14px;background:#e0e0e0;border-top:1px solid #bbb;text-align:center}
@media(max-width:420px){.login-box{margin:10px;max-width:100%}}
</style>
</head>
<body>
<div class="login-box">
    <div class="login-title-bar"><span>&#9632;</span> <?= APP_NAME ?> — Sign In</div>
    <div class="login-body">
        <?php if ($error): ?><div class="error-msg">&#10007; <?= h($error) ?></div><?php endif; ?>
        <form method="POST" action="?page=login">
            <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= generateCSRF() ?>">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?= h($_POST['username'] ?? '') ?>" required autofocus autocomplete="username">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required autocomplete="current-password">
            </div>
            <div class="form-group">
                <label>Security Code</label>
                <div class="captcha-wrap">
                    <div class="captcha-img-wrap" id="captchaImg"><?= $captchaHtml ?></div>
                    <button type="button" class="captcha-refresh" onclick="refreshCaptcha()">&#8635; Refresh</button>
                </div>
                <input type="number" name="captcha" placeholder="Enter the answer above" required>
            </div>
            <button type="submit" class="btn-login">LOGIN</button>
        </form>
    </div>
    <div class="login-footer">Default: admin / admin123</div>
</div>
<script>
function refreshCaptcha(){
    fetch('?page=captcha_refresh').then(r=>r.json()).then(d=>{
        document.getElementById('captchaImg').innerHTML=d.html;
    });
}
</script>
</body>
</html>
<?php
    exit;
}

function renderDashboard()
{
    $stats = getDashboardStats();
    $recentTx = getRecentTransactions();
    $lowStock = getLowStockProducts();
    $chartData = getSalesChartData();
    $topProducts = getTopProducts();
    $maxSale = max(array_column($chartData, 'amount') ?: [1]);
    renderHeader('Dashboard');
    ?>
    <div class="page-title">
        &#9632; Dashboard
        <div class="page-title-actions">
            <a href="?page=products&action=add" class="btn btn-primary btn-sm">+ Add Product</a>
            <a href="?page=sales&action=add" class="btn btn-success btn-sm">+ New Invoice</a>
            <a href="?page=purchases&action=add" class="btn btn-warning btn-sm">+ New PO</a>
        </div>
    </div>
    <div class="stats-grid">
        <div class="stat-card blue"><span class="stat-value"><?= h($stats['total_products']) ?></span><span class="stat-label">Total Products</span></div>
        <div class="stat-card red"><span class="stat-value"><?= h($stats['low_stock']) ?></span><span class="stat-label">Low Stock Alerts</span></div>
        <div class="stat-card green"><span class="stat-value"><?= formatCurrency($stats['today_sales']) ?></span><span class="stat-label">Today's Sales</span></div>
        <div class="stat-card orange"><span class="stat-value"><?= formatCurrency($stats['today_purchases']) ?></span><span class="stat-label">Today's Purchases</span></div>
        <div class="stat-card green"><span class="stat-value"><?= formatCurrency($stats['total_revenue']) ?></span><span class="stat-label">Total Revenue (Paid)</span></div>
        <div class="stat-card blue"><span class="stat-value"><?= h($stats['total_customers']) ?></span><span class="stat-label">Total Customers</span></div>
        <div class="stat-card blue"><span class="stat-value"><?= h($stats['total_suppliers']) ?></span><span class="stat-label">Total Suppliers</span></div>
        <div class="stat-card orange"><span class="stat-value"><?= h($stats['pending_orders']) ?></span><span class="stat-label">Pending Orders</span></div>
    </div>
    <div class="two-col-layout">
        <div class="card">
            <div class="card-title">&#128202; Sales — Last 7 Days</div>
            <div class="chart-bar-wrap">
                <?php
                foreach ($chartData as $day):
                    $pct = $maxSale > 0 ? ($day['amount'] / $maxSale) * 100 : 0;
                    ?>
                <div class="chart-bar-col">
                    <div style="flex:1;display:flex;align-items:flex-end;width:100%">
                        <div class="chart-bar" style="height:<?= max(2, round($pct)) ?>%;width:100%" title="<?= $day['date'] ?>: <?= formatCurrency($day['amount']) ?>"></div>
                    </div>
                    <div class="chart-bar-label"><?= h($day['label']) ?></div>
                    <div class="chart-bar-val"><?= formatCurrency($day['amount']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card">
            <div class="card-title">&#127942; Top Selling Products</div>
            <?php if (empty($topProducts)): ?>
            <p style="font-size:12px;color:#888">No sales data yet.</p>
            <?php else: ?>
            <table class="data-table">
                <thead><tr><th>Product</th><th>Qty</th><th>Revenue</th></tr></thead>
                <tbody>
                <?php foreach ($topProducts as $tp): ?>
                <tr>
                    <td data-label="Product"><?= h($tp['name']) ?></td>
                    <td data-label="Qty"><?= h($tp['total_qty']) ?></td>
                    <td data-label="Revenue"><?= formatCurrency($tp['total_revenue']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
    <div class="two-col-layout">
        <div class="card">
            <div class="card-title">&#128260; Recent Transactions</div>
            <?php if (empty($recentTx)): ?><p style="font-size:12px;color:#888">No transactions yet.</p>
            <?php else: ?>
            <table class="data-table">
                <thead><tr><th>Type</th><th>Ref</th><th>Party</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                <?php
                foreach ($recentTx as $tx):
                    $cls = $tx['type'] === 'Sale' ? 'badge-success' : 'badge-info';
                    $pcls = $tx['payment_status'] === 'Paid' ? 'badge-success' : ($tx['payment_status'] === 'Partial' ? 'badge-warning' : 'badge-danger');
                    ?>
                <tr>
                    <td data-label="Type"><span class="badge <?= $cls ?>"><?= h($tx['type']) ?></span></td>
                    <td data-label="Ref" style="font-size:11px"><?= h($tx['ref_no']) ?></td>
                    <td data-label="Party"><?= h($tx['party']) ?></td>
                    <td data-label="Amount"><?= formatCurrency($tx['total_amount']) ?></td>
                    <td data-label="Status"><span class="badge <?= $pcls ?>"><?= h($tx['payment_status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <div class="card">
            <div class="card-title">&#9888; Low Stock Alerts</div>
            <?php if (empty($lowStock)): ?><p style="font-size:12px;color:var(--green-dark)">&#10003; All products are adequately stocked.</p>
            <?php else: ?>
            <table class="data-table">
                <thead><tr><th>Product</th><th>Stock</th><th>Min</th></tr></thead>
                <tbody>
                <?php
                foreach ($lowStock as $lp):
                    $cls = $lp['current_stock'] <= 0 ? 'stock-critical' : ($lp['current_stock'] <= $lp['min_stock'] / 2 ? 'stock-critical' : 'stock-low');
                    ?>
                <tr>
                    <td data-label="Product"><a href="?page=products&action=view&id=<?= h($lp['id']) ?>"><?= h($lp['name']) ?></a></td>
                    <td data-label="Stock"><span class="<?= $cls ?>"><?= h($lp['current_stock']) ?></span></td>
                    <td data-label="Min"><?= h($lp['min_stock']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            <div style="margin-top:6px"><a href="?page=reports&report=low_stock" class="btn btn-warning btn-sm">View Full Report</a></div>
        </div>
    </div>
    <div class="two-col-layout">
        <div class="card">
            <div class="card-title">&#128184; Financial Summary</div>
            <table class="detail-table">
                <tr><td>Inventory Value (Cost)</td><td><strong><?= formatCurrency($stats['inventory_value']) ?></strong></td></tr>
                <tr><td>Total Revenue Collected</td><td><strong style="color:var(--green-dark)"><?= formatCurrency($stats['total_revenue']) ?></strong></td></tr>
                <tr><td>Receivables (Unpaid)</td><td><strong style="color:var(--red-dark)"><?= formatCurrency($stats['unpaid_invoices']) ?></strong></td></tr>
            </table>
        </div>
        <div class="card">
            <div class="card-title">&#9889; Quick Actions</div>
            <div style="display:flex;flex-wrap:wrap;gap:6px">
                <a href="?page=products&action=add" class="btn btn-primary">+ Product</a>
                <a href="?page=sales&action=add" class="btn btn-success">+ Invoice</a>
                <a href="?page=purchases&action=add" class="btn btn-warning">+ Purchase</a>
                <a href="?page=customers&action=add" class="btn">+ Customer</a>
                <a href="?page=suppliers&action=add" class="btn">+ Supplier</a>
                <a href="?page=adjustments&action=add" class="btn">+ Adjustment</a>
                <a href="?page=reports&report=stock" class="btn btn-primary">Stock Report</a>
                <a href="?page=reports&report=low_stock" class="btn btn-danger">Low Stock</a>
            </div>
        </div>
    </div>
    <?php
    renderFooter();
}

function renderProductList($products, $categories, $search, $catFilter, $statusFilter, $total, $page, $perPage, $baseUrl)
{
    renderHeader('Products');
    $csrf = generateCSRF();
    ?>
    <div class="page-title">
        &#128230; Products
        <div class="page-title-actions">
            <?php if (hasRole(['Admin', 'Manager'])): ?>
            <a href="?page=products&action=add" class="btn btn-primary btn-sm">+ Add Product</a>
            <?php endif; ?>
            <a href="?page=products&action=export_csv" class="btn btn-sm">&#8595; CSV</a>
            <button onclick="printPage()" class="btn btn-sm">&#128424; Print</button>
        </div>
    </div>
    <form method="GET" action="">
        <input type="hidden" name="page" value="products">
        <div class="filter-bar">
            <div class="filter-group"><label>Search</label><input type="search" name="search" value="<?= h($search) ?>" placeholder="Name, SKU, Brand..."></div>
            <div class="filter-group"><label>Category</label>
                <select name="cat"><option value="">All Categories</option><?php foreach ($categories as $c): ?><option value="<?= h($c['id']) ?>" <?= $catFilter == $c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option><?php endforeach; ?></select>
            </div>
            <div class="filter-group"><label>Status</label>
                <select name="status"><option value="">All</option><option value="1" <?= $statusFilter === '1' ? 'selected' : '' ?>>Active</option><option value="0" <?= $statusFilter === '0' ? 'selected' : '' ?>>Inactive</option></select>
            </div>
            <div class="filter-group" style="justify-content:flex-end"><label>&nbsp;</label><button type="submit" class="btn btn-primary">Filter</button></div>
            <div class="filter-group" style="justify-content:flex-end"><label>&nbsp;</label><a href="?page=products" class="btn">Clear</a></div>
        </div>
    </form>
    <?php if (hasRole(['Admin', 'Manager'])): ?>
    <form method="POST" action="?page=products&action=bulk" id="bulkForm">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
        <div style="display:flex;gap:6px;align-items:center;margin-bottom:6px;flex-wrap:wrap">
            <select name="bulk_action" style="width:auto;min-width:140px">
                <option value="">-- Bulk Action --</option>
                <option value="activate">Activate</option>
                <option value="deactivate">Deactivate</option>
                <?php if (hasRole(['Admin'])): ?><option value="delete">Delete</option><?php endif; ?>
            </select>
            <button type="button" class="btn btn-sm" onclick="applyBulk()">Apply</button>
            <span style="font-size:11px;color:#888"><?= h($total) ?> records found</span>
        </div>
    <?php endif; ?>
    <div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <?php if (hasRole(['Admin', 'Manager'])): ?><th><input type="checkbox" onclick="toggleSelectAll(this)" title="Select All"></th><?php endif; ?>
                <th>SKU</th><th>Name</th><th>Category</th><th>Brand</th><th>Unit</th>
                <th>Purchase</th><th>Selling</th><th>Stock</th><th>Min</th><th>Status</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($products)): ?>
        <tr><td colspan="12" style="text-align:center;padding:20px;color:#888">No products found.</td></tr>
        <?php
    else:
        foreach ($products as $p):
            $stockClass = $p['current_stock'] <= 0 ? 'stock-critical' : ($p['current_stock'] <= $p['min_stock'] ? 'stock-low' : 'stock-good');
            ?>
        <tr>
            <?php if (hasRole(['Admin', 'Manager'])): ?><td data-label="Sel"><input type="checkbox" name="selected_ids[]" value="<?= h($p['id']) ?>"></td><?php endif; ?>
            <td data-label="SKU" style="font-size:11px;font-family:monospace"><?= h($p['sku']) ?></td>
            <td data-label="Name"><a href="?page=products&action=view&id=<?= h($p['id']) ?>"><?= h($p['name']) ?></a></td>
            <td data-label="Category"><?= h($p['category_name'] ?? '-') ?></td>
            <td data-label="Brand"><?= h($p['brand'] ?? '-') ?></td>
            <td data-label="Unit"><?= h($p['unit']) ?></td>
            <td data-label="Purchase"><?= formatCurrency($p['purchase_price']) ?></td>
            <td data-label="Selling"><?= formatCurrency($p['selling_price']) ?></td>
            <td data-label="Stock"><span class="<?= $stockClass ?>"><?= h($p['current_stock']) ?></span></td>
            <td data-label="Min"><?= h($p['min_stock']) ?></td>
            <td data-label="Status"><span class="badge <?= $p['status'] ? 'badge-success' : 'badge-danger' ?>"><?= $p['status'] ? 'Active' : 'Inactive' ?></span></td>
            <td data-label="Actions" style="white-space:nowrap">
                <a href="?page=products&action=view&id=<?= h($p['id']) ?>" class="btn btn-xs">View</a>
                <?php if (hasRole(['Admin', 'Manager'])): ?>
                <a href="?page=products&action=edit&id=<?= h($p['id']) ?>" class="btn btn-xs btn-warning">Edit</a>
                <form method="POST" action="?page=products&action=delete" style="display:inline" id="del_p_<?= h($p['id']) ?>">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
                    <input type="hidden" name="id" value="<?= h($p['id']) ?>">
                    <button type="button" class="btn btn-xs btn-danger" onclick="confirmDelete('del_p_<?= h($p['id']) ?>')">Del</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach;
    endif; ?>
        </tbody>
    </table>
    </div>
    <?php if (hasRole(['Admin', 'Manager'])): ?></form><?php endif; ?>
    <?= paginate($total, $page, $perPage, $baseUrl) ?>
    <script>
    function applyBulk(){
        var sel=document.querySelectorAll('input[name="selected_ids[]"]:checked');
        if(sel.length===0){alert('Please select at least one item.');return;}
        var act=document.querySelector('select[name="bulk_action"]').value;
        if(!act){alert('Please choose a bulk action.');return;}
        if(act==='delete'&&!confirm('Delete '+sel.length+' selected items? This cannot be undone.')){return;}
        document.getElementById('bulkForm').submit();
    }
    </script>
    <?php
    renderFooter();
}

function renderProductForm($product, $categories)
{
    $title = $product ? 'Edit Product: ' . h($product['name']) : 'Add New Product';
    $action = $product ? 'edit' : 'add';
    renderHeader($title);
    $csrf = generateCSRF();
    $units = ['pcs', 'kg', 'ltr', 'box', 'dozen', 'meter', 'feet', 'gram', 'ml', 'ton'];
    ?>
    <div class="page-title">
        <?= $product ? '&#9998; Edit Product' : '&#43; Add Product' ?>
        <div class="page-title-actions"><a href="?page=products" class="btn btn-sm">&#8592; Back</a></div>
    </div>
    <form method="POST" action="?page=products&action=<?= $action ?>">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
        <?php if ($product): ?><input type="hidden" name="id" value="<?= h($product['id']) ?>"> <?php endif; ?>
        <fieldset class="form-section"><legend>Basic Information</legend>
            <div class="form-row-3">
                <div class="form-group"><label>Product Name *</label><input type="text" name="name" value="<?= h($product['name'] ?? '') ?>" required minlength="2"></div>
                <div class="form-group"><label>Category</label>
                    <select name="category_id">
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $c): ?><option value="<?= h($c['id']) ?>" <?= ($product['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Brand</label><input type="text" name="brand" value="<?= h($product['brand'] ?? '') ?>"></div>
            </div>
            <div class="form-group"><label>Description</label><textarea name="description" rows="2" style="resize:vertical"><?= h($product['description'] ?? '') ?></textarea></div>
        </fieldset>
        <fieldset class="form-section"><legend>Pricing & Unit</legend>
            <div class="form-row-3">
                <div class="form-group"><label>Unit</label>
                    <select name="unit">
                        <?php foreach ($units as $u): ?><option value="<?= h($u) ?>" <?= ($product['unit'] ?? 'pcs') === $u ? 'selected' : '' ?>><?= h($u) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Purchase Price *</label><input type="number" name="purchase_price" value="<?= h($product['purchase_price'] ?? 0) ?>" step="0.01" min="0" required></div>
                <div class="form-group"><label>Selling Price *</label><input type="number" name="selling_price" value="<?= h($product['selling_price'] ?? 0) ?>" step="0.01" min="0" required></div>
            </div>
        </fieldset>
        <fieldset class="form-section"><legend>Stock Levels</legend>
            <div class="form-row-3">
                <div class="form-group"><label>Min Stock Level</label><input type="number" name="min_stock" value="<?= h($product['min_stock'] ?? 0) ?>" step="0.01" min="0"></div>
                <div class="form-group"><label>Max Stock Level</label><input type="number" name="max_stock" value="<?= h($product['max_stock'] ?? 0) ?>" step="0.01" min="0"></div>
                <div class="form-group"><label>Location / Warehouse</label><input type="text" name="location" value="<?= h($product['location'] ?? '') ?>"></div>
            </div>
        </fieldset>
        <fieldset class="form-section"><legend>Additional</legend>
            <div class="form-row">
                <div class="form-group"><label>Barcode</label><input type="text" name="barcode" value="<?= h($product['barcode'] ?? '') ?>"></div>
                <div class="form-group"><label>Image URL</label><input type="text" name="image_url" value="<?= h($product['image_url'] ?? '') ?>"></div>
            </div>
            <div class="form-group"><label>Status</label>
                <select name="status">
                    <option value="1" <?= ($product['status'] ?? 1) == 1 ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= ($product['status'] ?? 1) == 0 ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </fieldset>
        <div style="display:flex;gap:8px;margin-top:10px">
            <button type="submit" class="btn btn-primary">&#10003; Save Product</button>
            <a href="?page=products" class="btn">Cancel</a>
        </div>
    </form>
    <?php
    renderFooter();
}

function renderProductDetail($product)
{
    renderHeader('Product: ' . $product['name']);
    $pdo = getDB();
    $adjHistory = $pdo->prepare('SELECT sa.*,u.full_name as created_by_name FROM stock_adjustments sa JOIN users u ON sa.created_by=u.id WHERE sa.product_id=? ORDER BY sa.created_at DESC LIMIT 20');
    $adjHistory->execute([$product['id']]);
    $adjHistory = $adjHistory->fetchAll();
    $salesHistory = $pdo->prepare('SELECT ii.*,i.invoice_number,i.invoice_date,c.name as customer_name FROM invoice_items ii JOIN invoices i ON ii.invoice_id=i.id JOIN customers c ON i.customer_id=c.id WHERE ii.product_id=? ORDER BY i.invoice_date DESC LIMIT 10');
    $salesHistory->execute([$product['id']]);
    $salesHistory = $salesHistory->fetchAll();
    $stockClass = $product['current_stock'] <= 0 ? 'stock-critical' : ($product['current_stock'] <= $product['min_stock'] ? 'stock-low' : 'stock-good');
    ?>
    <div class="page-title">
        &#128230; <?= h($product['name']) ?>
        <div class="page-title-actions">
            <?php if (hasRole(['Admin', 'Manager'])): ?>
            <a href="?page=products&action=edit&id=<?= h($product['id']) ?>" class="btn btn-warning btn-sm">Edit</a>
            <a href="?page=adjustments&action=add" class="btn btn-sm">Adjust Stock</a>
            <?php endif; ?>
            <a href="?page=products" class="btn btn-sm">&#8592; Back</a>
        </div>
    </div>
    <div class="two-col-layout">
        <div class="card">
            <div class="card-title">Product Details</div>
            <table class="detail-table">
                <tr><td>SKU</td><td><strong style="font-family:monospace"><?= h($product['sku']) ?></strong></td></tr>
                <tr><td>Name</td><td><?= h($product['name']) ?></td></tr>
                <tr><td>Category</td><td><?= h($product['category_name'] ?? '-') ?></td></tr>
                <tr><td>Brand</td><td><?= h($product['brand'] ?? '-') ?></td></tr>
                <tr><td>Unit</td><td><?= h($product['unit']) ?></td></tr>
                <tr><td>Purchase Price</td><td><?= formatCurrency($product['purchase_price']) ?></td></tr>
                <tr><td>Selling Price</td><td><?= formatCurrency($product['selling_price']) ?></td></tr>
                <tr><td>Current Stock</td><td><span class="<?= $stockClass ?>"><?= h($product['current_stock']) ?> <?= h($product['unit']) ?></span></td></tr>
                <tr><td>Min Stock</td><td><?= h($product['min_stock']) ?></td></tr>
                <tr><td>Max Stock</td><td><?= h($product['max_stock']) ?></td></tr>
                <tr><td>Location</td><td><?= h($product['location'] ?? '-') ?></td></tr>
                <tr><td>Barcode</td><td style="font-family:monospace"><?= h($product['barcode'] ?? '-') ?></td></tr>
                <tr><td>Status</td><td><span class="badge <?= $product['status'] ? 'badge-success' : 'badge-danger' ?>"><?= $product['status'] ? 'Active' : 'Inactive' ?></span></td></tr>
                <tr><td>Stock Value</td><td><strong><?= formatCurrency($product['current_stock'] * $product['purchase_price']) ?></strong></td></tr>
                <tr><td>Last Updated</td><td><?= formatDate($product['updated_at']) ?></td></tr>
            </table>
        </div>
        <div>
            <?php if ($product['barcode']): ?>
            <div class="card">
                <div class="card-title">Barcode</div>
                <div style="text-align:center;padding:10px">
                    <?php
                    $bc = $product['barcode'];
                    $bars = '';
                    $pattern = [
                        '0' => '3211', '1' => '2221', '2' => '2122', '3' => '1411', '4' => '1132',
                        '5' => '1231', '6' => '1114', '7' => '1312', '8' => '1213', '9' => '3112'
                    ];
                    $x = 10;
                    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="60" style="background:#fff;border:1px solid #ccc">';
                    foreach (str_split($bc) as $digit) {
                        if (!isset($pattern[$digit]))
                            continue;
                        foreach (str_split($pattern[$digit]) as $i => $w) {
                            $w = (int) $w * 2;
                            if ($i % 2 === 0)
                                $svg .= '<rect x="' . $x . '" y="4" width="' . $w . '" height="40" fill="#000"/>';
                            $x += $w;
                        }
                    }
                    $svg .= '<text x="100" y="57" text-anchor="middle" font-family="monospace" font-size="10">' . h($bc) . '</text></svg>';
                    echo $svg;
                    ?>
                    <br><button onclick="printPage()" class="btn btn-sm" style="margin-top:6px">Print Label</button>
                </div>
            </div>
            <?php endif; ?>
            <div class="card">
                <div class="card-title">Stock Progress</div>
                <?php $pct = ($product['max_stock'] > 0) ? min(100, ($product['current_stock'] / $product['max_stock']) * 100) : 0; ?>
                <div style="margin-bottom:4px;font-size:12px">
                    Stock: <strong><?= h($product['current_stock']) ?></strong> / Max: <strong><?= h($product['max_stock']) ?></strong>
                </div>
                <div class="progress-bar-wrap">
                    <div class="progress-bar-fill" style="width:<?= round($pct) ?>%;background:<?= $pct < 20 ? 'var(--red)' : ($pct < 50 ? 'var(--orange)' : 'var(--green)') ?>"></div>
                </div>
                <div style="font-size:11px;color:#888;margin-top:3px"><?= round($pct, 1) ?>% of max stock</div>
            </div>
        </div>
    </div>
    <div class="two-col-layout">
        <div class="card">
            <div class="card-title">Recent Sales</div>
            <?php if (empty($salesHistory)): ?><p style="font-size:12px;color:#888">No sales recorded.</p>
            <?php else: ?>
            <table class="data-table">
                <thead><tr><th>Invoice</th><th>Date</th><th>Customer</th><th>Qty</th><th>Price</th></tr></thead>
                <tbody>
                <?php foreach ($salesHistory as $s): ?>
                <tr>
                    <td data-label="Invoice"><a href="?page=sales&action=view&id=<?= h($s['invoice_id']) ?>"><?= h($s['invoice_number']) ?></a></td>
                    <td data-label="Date"><?= formatDate($s['invoice_date']) ?></td>
                    <td data-label="Customer"><?= h($s['customer_name']) ?></td>
                    <td data-label="Qty"><?= h($s['quantity']) ?></td>
                    <td data-label="Price"><?= formatCurrency($s['total_price']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <div class="card">
            <div class="card-title">Stock Adjustment History</div>
            <?php if (empty($adjHistory)): ?><p style="font-size:12px;color:#888">No adjustments recorded.</p>
            <?php else: ?>
            <table class="data-table">
                <thead><tr><th>Ref</th><th>Type</th><th>Qty</th><th>New Stock</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php
                foreach ($adjHistory as $a):
                    $negTypes = ['Subtraction', 'Damage', 'Expired'];
                    $color = in_array($a['adjustment_type'], $negTypes) ? 'var(--red-dark)' : 'var(--green-dark)';
                    ?>
                <tr>
                    <td data-label="Ref" style="font-size:11px"><?= h($a['reference_no']) ?></td>
                    <td data-label="Type"><?= h($a['adjustment_type']) ?></td>
                    <td data-label="Qty" style="color:<?= $color ?>;font-weight:700"><?= in_array($a['adjustment_type'], $negTypes) ? '-' : '+' ?><?= h($a['quantity']) ?></td>
                    <td data-label="New Stock"><?= h($a['new_stock']) ?></td>
                    <td data-label="Status"><span class="badge <?= $a['status'] === 'Approved' ? 'badge-success' : ($a['status'] === 'Pending' ? 'badge-warning' : 'badge-danger') ?>"><?= h($a['status']) ?></span></td>
                    <td data-label="Date"><?= formatDate($a['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
    <?php
    renderFooter();
}

function renderCategories($cats, $parentCats)
{
    renderHeader('Categories');
    $csrf = generateCSRF();
    ?>
    <div class="page-title">
        &#128193; Categories
        <div class="page-title-actions">
            <?php if (hasRole(['Admin', 'Manager'])): ?>
            <button onclick="openModal('addCatModal')" class="btn btn-primary btn-sm">+ Add Category</button>
            <?php endif; ?>
        </div>
    </div>
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>ID</th><th>Name</th><th>Parent</th><th>Description</th><th>Products</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if (empty($cats)): ?>
        <tr><td colspan="7" style="text-align:center;padding:20px;color:#888">No categories found.</td></tr>
        <?php else:
        foreach ($cats as $c): ?>
        <tr>
            <td data-label="ID"><?= h($c['id']) ?></td>
            <td data-label="Name"><strong><?= h($c['name']) ?></strong></td>
            <td data-label="Parent"><?= h($c['parent_name'] ?? '-') ?></td>
            <td data-label="Description" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($c['description'] ?? '-') ?></td>
            <td data-label="Products"><span class="badge badge-info"><?= h($c['product_count']) ?></span></td>
            <td data-label="Status"><span class="badge <?= $c['status'] ? 'badge-success' : 'badge-danger' ?>"><?= $c['status'] ? 'Active' : 'Inactive' ?></span></td>
            <td data-label="Actions" style="white-space:nowrap">
                <?php if (hasRole(['Admin', 'Manager'])): ?>
                <button onclick="editCategory(<?= h(json_encode($c)) ?>)" class="btn btn-xs btn-warning">Edit</button>
                <form method="POST" action="?page=categories&action=toggle" style="display:inline" id="tog_c_<?= h($c['id']) ?>">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
                    <input type="hidden" name="id" value="<?= h($c['id']) ?>">
                    <button type="submit" class="btn btn-xs"><?= $c['status'] ? 'Disable' : 'Enable' ?></button>
                </form>
                <?php if (hasRole(['Admin'])): ?>
                <form method="POST" action="?page=categories&action=delete" style="display:inline" id="del_c_<?= h($c['id']) ?>">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
                    <input type="hidden" name="id" value="<?= h($c['id']) ?>">
                    <button type="button" class="btn btn-xs btn-danger" onclick="confirmDelete('del_c_<?= h($c['id']) ?>')">Del</button>
                </form>
                <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach;
    endif; ?>
        </tbody>
    </table>
    </div>
    <?php if (hasRole(['Admin', 'Manager'])): ?>
    <div id="addCatModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-title-bar">Add Category <button class="modal-close" onclick="closeModal('addCatModal')">&#10005;</button></div>
            <form method="POST" action="?page=categories&action=add">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
                <div class="modal-body">
                    <div class="form-group"><label>Category Name *</label><input type="text" name="name" required minlength="2"></div>
                    <div class="form-group"><label>Parent Category</label>
                        <select name="parent_id"><option value="">-- None (Top Level) --</option>
                            <?php foreach ($parentCats as $pc): ?><option value="<?= h($pc['id']) ?>"><?= h($pc['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Description</label><textarea name="description" rows="2"></textarea></div>
                    <div class="form-group"><label>Status</label><select name="status"><option value="1">Active</option><option value="0">Inactive</option></select></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button><button type="button" class="btn" onclick="closeModal('addCatModal')">Cancel</button></div>
            </form>
        </div>
    </div>
    <div id="editCatModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-title-bar">Edit Category <button class="modal-close" onclick="closeModal('editCatModal')">&#10005;</button></div>
            <form method="POST" action="?page=categories&action=edit" id="editCatForm">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
                <input type="hidden" name="id" id="editCatId">
                <div class="modal-body">
                    <div class="form-group"><label>Category Name *</label><input type="text" name="name" id="editCatName" required minlength="2"></div>
                    <div class="form-group"><label>Parent Category</label>
                        <select name="parent_id" id="editCatParent"><option value="">-- None --</option>
                            <?php foreach ($parentCats as $pc): ?><option value="<?= h($pc['id']) ?>"><?= h($pc['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Description</label><textarea name="description" id="editCatDesc" rows="2"></textarea></div>
                    <div class="form-group"><label>Status</label><select name="status" id="editCatStatus"><option value="1">Active</option><option value="0">Inactive</option></select></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Update</button><button type="button" class="btn" onclick="closeModal('editCatModal')">Cancel</button></div>
            </form>
        </div>
    </div>
    <script>
    function editCategory(c){
        document.getElementById('editCatId').value=c.id;
        document.getElementById('editCatName').value=c.name;
        document.getElementById('editCatParent').value=c.parent_id||'';
        document.getElementById('editCatDesc').value=c.description||'';
        document.getElementById('editCatStatus').value=c.status;
        openModal('editCatModal');
    }
    </script>
    <?php endif; ?>
    <?php
    renderFooter();
}

function renderSuppliers($suppliers, $search, $total, $page, $perPage)
{
    renderHeader('Suppliers');
    $csrf = generateCSRF();
    ?>
    <div class="page-title">
        &#128663; Suppliers
        <div class="page-title-actions">
            <?php if (hasRole(['Admin', 'Manager'])): ?>
            <button onclick="openModal('addSupModal')" class="btn btn-primary btn-sm">+ Add Supplier</button>
            <?php endif; ?>
        </div>
    </div>
    <form method="GET" action="">
        <input type="hidden" name="page" value="suppliers">
        <div class="filter-bar">
            <div class="filter-group"><label>Search</label><input type="search" name="search" value="<?= h($search) ?>" placeholder="Company, Contact, Email..."></div>
            <div class="filter-group" style="justify-content:flex-end"><label>&nbsp;</label><button type="submit" class="btn btn-primary">Search</button></div>
            <div class="filter-group" style="justify-content:flex-end"><label>&nbsp;</label><a href="?page=suppliers" class="btn">Clear</a></div>
        </div>
    </form>
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Company</th><th>Contact</th><th>Email</th><th>Phone</th><th>City</th><th>Terms</th><th>Balance</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if (empty($suppliers)): ?>
        <tr><td colspan="9" style="text-align:center;padding:20px;color:#888">No suppliers found.</td></tr>
        <?php else:
        foreach ($suppliers as $s): ?>
        <tr>
            <td data-label="Company"><a href="?page=suppliers&action=view&id=<?= h($s['id']) ?>"><?= h($s['company_name']) ?></a></td>
            <td data-label="Contact"><?= h($s['contact_person'] ?? '-') ?></td>
            <td data-label="Email"><?= h($s['email'] ?? '-') ?></td>
            <td data-label="Phone"><?= h($s['phone'] ?? '-') ?></td>
            <td data-label="City"><?= h($s['city'] ?? '-') ?></td>
            <td data-label="Terms"><?= h($s['payment_terms'] ?? '-') ?></td>
            <td data-label="Balance"><span style="color:<?= $s['balance'] > 0 ? 'var(--red-dark)' : 'var(--green-dark)' ?>"><?= formatCurrency($s['balance']) ?></span></td>
            <td data-label="Status"><span class="badge <?= $s['status'] ? 'badge-success' : 'badge-danger' ?>"><?= $s['status'] ? 'Active' : 'Inactive' ?></span></td>
            <td data-label="Actions" style="white-space:nowrap">
                <a href="?page=suppliers&action=view&id=<?= h($s['id']) ?>" class="btn btn-xs">View</a>
                <?php if (hasRole(['Admin', 'Manager'])): ?>
                <button onclick="editSupplier(<?= h(json_encode($s)) ?>)" class="btn btn-xs btn-warning">Edit</button>
                <form method="POST" action="?page=suppliers&action=delete" style="display:inline" id="del_s_<?= h($s['id']) ?>">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
                    <input type="hidden" name="id" value="<?= h($s['id']) ?>">
                    <button type="button" class="btn btn-xs btn-danger" onclick="confirmDelete('del_s_<?= h($s['id']) ?>')">Del</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach;
    endif; ?>
        </tbody>
    </table>
    </div>
    <?= paginate($total, $page, $perPage, '?page=suppliers&search=' . urlencode($search)) ?>
    <?php if (hasRole(['Admin', 'Manager'])):
        $terms = ['Net 7', 'Net 14', 'Net 30', 'Net 45', 'Net 60', 'COD', 'Prepaid']; ?>
    <?php foreach (['add' => null, 'edit' => 'edit'] as $modalType => $prefix): ?>
    <div id="<?= $modalType ?>SupModal" class="modal-overlay">
        <div class="modal" style="max-width:700px">
            <div class="modal-title-bar"><?= ucfirst($modalType) ?> Supplier <button class="modal-close" onclick="closeModal('<?= $modalType ?>SupModal')">&#10005;</button></div>
            <form method="POST" action="?page=suppliers&action=<?= $modalType ?>">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
                <?php if ($modalType === 'edit'): ?><input type="hidden" name="id" id="editSupId"><?php endif; ?>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group"><label>Company Name *</label><input type="text" name="company_name" id="<?= $modalType ?>SupCompany" required></div>
                        <div class="form-group"><label>Contact Person</label><input type="text" name="contact_person" id="<?= $modalType ?>SupContact"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Email</label><input type="email" name="email" id="<?= $modalType ?>SupEmail"></div>
                        <div class="form-group"><label>Phone</label><input type="text" name="phone" id="<?= $modalType ?>SupPhone"></div>
                    </div>
                    <div class="form-group"><label>Address</label><textarea name="address" id="<?= $modalType ?>SupAddress" rows="2"></textarea></div>
                    <div class="form-row-3">
                        <div class="form-group"><label>City</label><input type="text" name="city" id="<?= $modalType ?>SupCity"></div>
                        <div class="form-group"><label>Country</label><input type="text" name="country" id="<?= $modalType ?>SupCountry"></div>
                        <div class="form-group"><label>Tax ID</label><input type="text" name="tax_id" id="<?= $modalType ?>SupTaxId"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Payment Terms</label>
                            <select name="payment_terms" id="<?= $modalType ?>SupTerms">
                                <?php foreach ($terms as $t): ?><option value="<?= h($t) ?>"><?= h($t) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Status</label><select name="status" id="<?= $modalType ?>SupStatus"><option value="1">Active</option><option value="0">Inactive</option></select></div>
                    </div>
                    <div class="form-group"><label>Notes</label><textarea name="notes" id="<?= $modalType ?>SupNotes" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button><button type="button" class="btn" onclick="closeModal('<?= $modalType ?>SupModal')">Cancel</button></div>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
    <script>
    function editSupplier(s){
        document.getElementById('editSupId').value=s.id;
        document.getElementById('editSupCompany').value=s.company_name||'';
        document.getElementById('editSupContact').value=s.contact_person||'';
        document.getElementById('editSupEmail').value=s.email||'';
        document.getElementById('editSupPhone').value=s.phone||'';
        document.getElementById('editSupAddress').value=s.address||'';
        document.getElementById('editSupCity').value=s.city||'';
        document.getElementById('editSupCountry').value=s.country||'';
        document.getElementById('editSupTaxId').value=s.tax_id||'';
        document.getElementById('editSupTerms').value=s.payment_terms||'';
        document.getElementById('editSupStatus').value=s.status;
        document.getElementById('editSupNotes').value=s.notes||'';
        openModal('editSupModal');
    }
    </script>
    <?php endif; ?>
    <?php
    renderFooter();
}

function renderSupplierDetail($sup, $pos)
{
    renderHeader('Supplier: ' . ($sup['company_name'] ?? ''));
    ?>
    <div class="page-title">&#128663; <?= h($sup['company_name']) ?> <div class="page-title-actions"><a href="?page=suppliers" class="btn btn-sm">&#8592; Back</a></div></div>
    <div class="two-col-layout">
        <div class="card">
            <div class="card-title">Supplier Details</div>
            <table class="detail-table">
                <tr><td>Company</td><td><?= h($sup['company_name']) ?></td></tr>
                <tr><td>Contact</td><td><?= h($sup['contact_person'] ?? '-') ?></td></tr>
                <tr><td>Email</td><td><?= h($sup['email'] ?? '-') ?></td></tr>
                <tr><td>Phone</td><td><?= h($sup['phone'] ?? '-') ?></td></tr>
                <tr><td>Address</td><td><?= h($sup['address'] ?? '-') ?></td></tr>
                <tr><td>City / Country</td><td><?= h($sup['city'] ?? '') ?>, <?= h($sup['country'] ?? '') ?></td></tr>
                <tr><td>Tax ID</td><td><?= h($sup['tax_id'] ?? '-') ?></td></tr>
                <tr><td>Payment Terms</td><td><?= h($sup['payment_terms'] ?? '-') ?></td></tr>
                <tr><td>Outstanding Balance</td><td><strong style="color:var(--red-dark)"><?= formatCurrency($sup['balance']) ?></strong></td></tr>
                <tr><td>Status</td><td><span class="badge <?= $sup['status'] ? 'badge-success' : 'badge-danger' ?>"><?= $sup['status'] ? 'Active' : 'Inactive' ?></span></td></tr>
                <tr><td>Notes</td><td><?= h($sup['notes'] ?? '-') ?></td></tr>
            </table>
        </div>
        <div class="card">
            <div class="card-title">Recent Purchase Orders</div>
            <?php if (empty($pos)): ?><p style="font-size:12px;color:#888">No purchase orders.</p><?php else: ?>
            <table class="data-table">
                <thead><tr><th>PO#</th><th>Date</th><th>Total</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($pos as $po): ?>
                <tr>
                    <td data-label="PO#"><a href="?page=purchases&action=view&id=<?= h($po['id']) ?>"><?= h($po['po_number']) ?></a></td>
                    <td data-label="Date"><?= formatDate($po['po_date']) ?></td>
                    <td data-label="Total"><?= formatCurrency($po['total_amount']) ?></td>
                    <td data-label="Status"><span class="badge <?= $po['order_status'] === 'Received' ? 'badge-success' : ($po['order_status'] === 'Pending' ? 'badge-warning' : 'badge-info') ?>"><?= h($po['order_status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
    <?php
    renderFooter();
}

function renderCustomers($customers, $search, $total, $page, $perPage)
{
    renderHeader('Customers');
    $csrf = generateCSRF();
    ?>
    <div class="page-title">&#128101; Customers
        <div class="page-title-actions">
            <?php if (hasRole(['Admin', 'Manager'])): ?>
            <button onclick="openModal('addCustModal')" class="btn btn-primary btn-sm">+ Add Customer</button>
            <?php endif; ?>
        </div>
    </div>
    <form method="GET" action=""><input type="hidden" name="page" value="customers">
        <div class="filter-bar">
            <div class="filter-group"><label>Search</label><input type="search" name="search" value="<?= h($search) ?>" placeholder="Name, Email, Phone..."></div>
            <div class="filter-group" style="justify-content:flex-end"><label>&nbsp;</label><button type="submit" class="btn btn-primary">Search</button></div>
            <div class="filter-group" style="justify-content:flex-end"><label>&nbsp;</label><a href="?page=customers" class="btn">Clear</a></div>
        </div>
    </form>
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Type</th><th>City</th><th>Credit Limit</th><th>Balance</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if (empty($customers)): ?>
        <tr><td colspan="9" style="text-align:center;padding:20px;color:#888">No customers found.</td></tr>
        <?php else:
        foreach ($customers as $c): ?>
        <tr>
            <td data-label="Name"><a href="?page=customers&action=view&id=<?= h($c['id']) ?>"><?= h($c['name']) ?></a></td>
            <td data-label="Email"><?= h($c['email'] ?? '-') ?></td>
            <td data-label="Phone"><?= h($c['phone'] ?? '-') ?></td>
            <td data-label="Type"><span class="badge badge-info"><?= h($c['customer_type']) ?></span></td>
            <td data-label="City"><?= h($c['city'] ?? '-') ?></td>
            <td data-label="Credit"><?= formatCurrency($c['credit_limit']) ?></td>
            <td data-label="Balance"><span style="color:<?= $c['current_balance'] > 0 ? 'var(--red-dark)' : 'var(--green-dark)' ?>"><?= formatCurrency($c['current_balance']) ?></span></td>
            <td data-label="Status"><span class="badge <?= $c['status'] ? 'badge-success' : 'badge-danger' ?>"><?= $c['status'] ? 'Active' : 'Inactive' ?></span></td>
            <td data-label="Actions" style="white-space:nowrap">
                <a href="?page=customers&action=view&id=<?= h($c['id']) ?>" class="btn btn-xs">View</a>
                <?php if (hasRole(['Admin', 'Manager'])): ?>
                <button onclick="editCustomer(<?= h(json_encode($c)) ?>)" class="btn btn-xs btn-warning">Edit</button>
                <form method="POST" action="?page=customers&action=delete" style="display:inline" id="del_cu_<?= h($c['id']) ?>">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
                    <input type="hidden" name="id" value="<?= h($c['id']) ?>">
                    <button type="button" class="btn btn-xs btn-danger" onclick="confirmDelete('del_cu_<?= h($c['id']) ?>')">Del</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach;
    endif; ?>
        </tbody>
    </table>
    </div>
    <?= paginate($total, $page, $perPage, '?page=customers&search=' . urlencode($search)) ?>
    <?php if (hasRole(['Admin', 'Manager'])):
        foreach (['add', 'edit'] as $mt): ?>
    <div id="<?= $mt ?>CustModal" class="modal-overlay">
        <div class="modal" style="max-width:650px">
            <div class="modal-title-bar"><?= ucfirst($mt) ?> Customer <button class="modal-close" onclick="closeModal('<?= $mt ?>CustModal')">&#10005;</button></div>
            <form method="POST" action="?page=customers&action=<?= $mt ?>">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
                <?php if ($mt === 'edit'): ?><input type="hidden" name="id" id="editCustId"><?php endif; ?>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group"><label>Full Name *</label><input type="text" name="name" id="<?= $mt ?>CustName" required></div>
                        <div class="form-group"><label>Customer Type</label>
                            <select name="customer_type" id="<?= $mt ?>CustType">
                                <?php foreach (['Walk-in', 'Regular', 'Wholesale'] as $ct): ?><option value="<?= h($ct) ?>"><?= h($ct) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Email</label><input type="email" name="email" id="<?= $mt ?>CustEmail"></div>
                        <div class="form-group"><label>Phone</label><input type="text" name="phone" id="<?= $mt ?>CustPhone"></div>
                    </div>
                    <div class="form-group"><label>Address</label><textarea name="address" id="<?= $mt ?>CustAddress" rows="2"></textarea></div>
                    <div class="form-row-3">
                        <div class="form-group"><label>City</label><input type="text" name="city" id="<?= $mt ?>CustCity"></div>
                        <div class="form-group"><label>Country</label><input type="text" name="country" id="<?= $mt ?>CustCountry"></div>
                        <div class="form-group"><label>Credit Limit</label><input type="number" name="credit_limit" id="<?= $mt ?>CustCredit" step="0.01" min="0" value="0"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Status</label><select name="status" id="<?= $mt ?>CustStatus"><option value="1">Active</option><option value="0">Inactive</option></select></div>
                    </div>
                    <div class="form-group"><label>Notes</label><textarea name="notes" id="<?= $mt ?>CustNotes" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button><button type="button" class="btn" onclick="closeModal('<?= $mt ?>CustModal')">Cancel</button></div>
            </form>
        </div>
    </div>
    <?php endforeach;
    endif; ?>
    <script>
    function editCustomer(c){
        document.getElementById('editCustId').value=c.id;
        document.getElementById('editCustName').value=c.name||'';
        document.getElementById('editCustEmail').value=c.email||'';
        document.getElementById('editCustPhone').value=c.phone||'';
        document.getElementById('editCustAddress').value=c.address||'';
        document.getElementById('editCustCity').value=c.city||'';
        document.getElementById('editCustCountry').value=c.country||'';
        document.getElementById('editCustType').value=c.customer_type||'Walk-in';
        document.getElementById('editCustCredit').value=c.credit_limit||0;
        document.getElementById('editCustStatus').value=c.status;
        document.getElementById('editCustNotes').value=c.notes||'';
        openModal('editCustModal');
    }
    </script>
    <?php
    renderFooter();
}

function renderCustomerDetail($cust, $invs)
{
    renderHeader('Customer: ' . ($cust['name'] ?? ''));
    ?>
    <div class="page-title">&#128101; <?= h($cust['name']) ?> <div class="page-title-actions"><a href="?page=customers" class="btn btn-sm">&#8592; Back</a></div></div>
    <div class="two-col-layout">
        <div class="card">
            <div class="card-title">Customer Details</div>
            <table class="detail-table">
                <tr><td>Name</td><td><?= h($cust['name']) ?></td></tr>
                <tr><td>Type</td><td><span class="badge badge-info"><?= h($cust['customer_type']) ?></span></td></tr>
                <tr><td>Email</td><td><?= h($cust['email'] ?? '-') ?></td></tr>
                <tr><td>Phone</td><td><?= h($cust['phone'] ?? '-') ?></td></tr>
                <tr><td>Address</td><td><?= h($cust['address'] ?? '-') ?></td></tr>
                <tr><td>City / Country</td><td><?= h($cust['city'] ?? '') . ' / ' . h($cust['country'] ?? '') ?></td></tr>
                <tr><td>Credit Limit</td><td><?= formatCurrency($cust['credit_limit']) ?></td></tr>
                <tr><td>Current Balance</td><td><strong style="color:<?= $cust['current_balance'] > 0 ? 'var(--red-dark)' : 'var(--green-dark)' ?>"><?= formatCurrency($cust['current_balance']) ?></strong></td></tr>
                <tr><td>Status</td><td><span class="badge <?= $cust['status'] ? 'badge-success' : 'badge-danger' ?>"><?= $cust['status'] ? 'Active' : 'Inactive' ?></span></td></tr>
                <tr><td>Notes</td><td><?= h($cust['notes'] ?? '-') ?></td></tr>
            </table>
        </div>
        <div class="card">
            <div class="card-title">Sales History</div>
            <?php if (empty($invs)): ?><p style="font-size:12px;color:#888">No invoices.</p><?php else: ?>
            <table class="data-table">
                <thead><tr><th>Invoice#</th><th>Date</th><th>Total</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($invs as $inv): ?>
                <tr>
                    <td data-label="Inv#"><a href="?page=sales&action=view&id=<?= h($inv['id']) ?>"><?= h($inv['invoice_number']) ?></a></td>
                    <td data-label="Date"><?= formatDate($inv['invoice_date']) ?></td>
                    <td data-label="Total"><?= formatCurrency($inv['total_amount']) ?></td>
                    <td data-label="Status"><span class="badge <?= $inv['payment_status'] === 'Paid' ? 'badge-success' : ($inv['payment_status'] === 'Partial' ? 'badge-warning' : 'badge-danger') ?>"><?= h($inv['payment_status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
    <?php
    renderFooter();
}

function renderPurchaseList($orders, $search, $status, $dateFrom, $dateTo, $total, $page, $perPage, $baseUrl)
{
    renderHeader('Purchase Orders');
    $csrf = generateCSRF();
    ?>
    <div class="page-title">&#128722; Purchase Orders
        <div class="page-title-actions">
            <?php if (hasRole(['Admin', 'Manager'])): ?>
            <a href="?page=purchases&action=add" class="btn btn-primary btn-sm">+ New PO</a>
            <?php endif; ?>
        </div>
    </div>
    <form method="GET" action=""><input type="hidden" name="page" value="purchases">
        <div class="filter-bar">
            <div class="filter-group"><label>Search</label><input type="search" name="search" value="<?= h($search) ?>" placeholder="PO Number, Supplier..."></div>
            <div class="filter-group"><label>Status</label>
                <select name="status"><option value="">All</option><?php foreach (['Pending', 'Received', 'Partial', 'Cancelled'] as $s): ?><option value="<?= h($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= h($s) ?></option><?php endforeach; ?></select>
            </div>
            <div class="filter-group"><label>From</label><input type="date" name="date_from" value="<?= h($dateFrom) ?>"></div>
            <div class="filter-group"><label>To</label><input type="date" name="date_to" value="<?= h($dateTo) ?>"></div>
            <div class="filter-group" style="justify-content:flex-end"><label>&nbsp;</label><button type="submit" class="btn btn-primary">Filter</button></div>
            <div class="filter-group" style="justify-content:flex-end"><label>&nbsp;</label><a href="?page=purchases" class="btn">Clear</a></div>
        </div>
    </form>
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>PO Number</th><th>Supplier</th><th>Date</th><th>Expected</th><th>Total</th><th>Payment</th><th>Order Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if (empty($orders)): ?>
        <tr><td colspan="8" style="text-align:center;padding:20px;color:#888">No purchase orders found.</td></tr>
        <?php
    else:
        foreach ($orders as $o):
            $pClass = $o['payment_status'] === 'Paid' ? 'badge-success' : ($o['payment_status'] === 'Partial' ? 'badge-warning' : 'badge-danger');
            $oClass = $o['order_status'] === 'Received' ? 'badge-success' : ($o['order_status'] === 'Pending' ? 'badge-warning' : ($o['order_status'] === 'Cancelled' ? 'badge-danger' : 'badge-info'));
            ?>
        <tr>
            <td data-label="PO#"><a href="?page=purchases&action=view&id=<?= h($o['id']) ?>"><?= h($o['po_number']) ?></a></td>
            <td data-label="Supplier"><?= h($o['company_name']) ?></td>
            <td data-label="Date"><?= formatDate($o['po_date']) ?></td>
            <td data-label="Expected"><?= formatDate($o['expected_delivery']) ?></td>
            <td data-label="Total"><?= formatCurrency($o['total_amount']) ?></td>
            <td data-label="Payment"><span class="badge <?= $pClass ?>"><?= h($o['payment_status']) ?></span></td>
            <td data-label="Status"><span class="badge <?= $oClass ?>"><?= h($o['order_status']) ?></span></td>
            <td data-label="Actions" style="white-space:nowrap">
                <a href="?page=purchases&action=view&id=<?= h($o['id']) ?>" class="btn btn-xs">View</a>
                <?php if (hasRole(['Admin']) && in_array($o['order_status'], ['Pending', 'Partial'])): ?>
                <?php endif; ?>
                <?php if (hasRole(['Admin'])): ?>
                <form method="POST" action="?page=purchases&action=delete" style="display:inline" id="del_po_<?= h($o['id']) ?>">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
                    <input type="hidden" name="id" value="<?= h($o['id']) ?>">
                    <button type="button" class="btn btn-xs btn-danger" onclick="confirmDelete('del_po_<?= h($o['id']) ?>')">Del</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach;
    endif; ?>
        </tbody>
    </table>
    </div>
    <?= paginate($total, $page, $perPage, $baseUrl) ?>
    <?php
    renderFooter();
}

function renderPurchaseForm($suppliers, $products)
{
    renderHeader('New Purchase Order');
    $csrf = generateCSRF();
    ?>
    <div class="page-title">&#128722; New Purchase Order <div class="page-title-actions"><a href="?page=purchases" class="btn btn-sm">&#8592; Back</a></div></div>
    <form method="POST" action="?page=purchases&action=add" id="poForm">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
        <fieldset class="form-section"><legend>Order Information</legend>
            <div class="form-row-3">
                <div class="form-group"><label>Supplier *</label>
                    <select name="supplier_id" required>
                        <option value="">-- Select Supplier --</option>
                        <?php foreach ($suppliers as $s): ?><option value="<?= h($s['id']) ?>"><?= h($s['company_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>PO Date *</label><input type="date" name="po_date" value="<?= date('Y-m-d') ?>" required></div>
                <div class="form-group"><label>Expected Delivery</label><input type="date" name="expected_delivery"></div>
            </div>
        </fieldset>
        <fieldset class="form-section"><legend>Order Items</legend>
            <button type="button" id="add-item-btn" onclick="addPOItem()">+ Add Item</button>
            <div class="items-table-wrap">
            <table class="items-table" id="poItemsTable">
                <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th><th>Remove</th></tr></thead>
                <tbody id="poItemsBody"></tbody>
                <tfoot><tr><td colspan="3" style="text-align:right">Subtotal:</td><td id="poSubtotal" colspan="2">0.00</td></tr></tfoot>
            </table>
            </div>
        </fieldset>
        <fieldset class="form-section"><legend>Totals & Payment</legend>
            <div class="form-row-3">
                <div class="form-group"><label>Tax %</label><input type="number" name="tax_percent" id="poTax" value="0" step="0.01" min="0" max="100" onchange="recalcPO()"></div>
                <div class="form-group"><label>Discount</label><input type="number" name="discount" id="poDiscount" value="0" step="0.01" min="0" onchange="recalcPO()"></div>
                <div class="form-group"><label>Payment Status</label>
                    <select name="payment_status">
                        <option value="Unpaid">Unpaid</option><option value="Partial">Partial</option><option value="Paid">Paid</option>
                    </select>
                </div>
            </div>
            <div style="background:#eee;border:1px solid #ccc;padding:8px;margin-top:6px">
                <strong>Grand Total: </strong><span id="poGrandTotal" style="font-size:16px;font-weight:700;color:var(--blue-dark)">0.00</span>
            </div>
        </fieldset>
        <div class="form-group"><label>Notes</label><textarea name="notes" rows="2"></textarea></div>
        <div style="display:flex;gap:8px;margin-top:10px">
            <button type="submit" class="btn btn-primary">&#10003; Create Purchase Order</button>
            <a href="?page=purchases" class="btn">Cancel</a>
        </div>
    </form>
    <script>
    var poProducts=<?= json_encode(array_map(fn($p) => ['id' => $p['id'], 'name' => $p['name'] . ' (' . $p['sku'] . ')', 'price' => $p['purchase_price']], $products)) ?>;
    var poRowCount=0;
    function addPOItem(){
        var opts=poProducts.map(p=>'<option value="'+p.id+'" data-price="'+p.price+'">'+p.name+'</option>').join('');
        var row=document.createElement('tr');
        row.id='poRow'+poRowCount;
        row.innerHTML='<td><select name="product_id[]" onchange="updatePOPrice(this)" required><option value="">-- Select --</option>'+opts+'</select></td>'
            +'<td><input type="number" name="quantity[]" value="1" min="1" step="0.01" style="width:80px" onchange="recalcPO()" required></td>'
            +'<td><input type="number" name="unit_price[]" value="0" min="0" step="0.01" style="width:100px" onchange="recalcPO()" required></td>'
            +'<td class="rowTotal">0.00</td>'
            +'<td><button type="button" class="remove-row-btn" onclick="removePORow(\'poRow'+poRowCount+'\')">&#10005;</button></td>';
        document.getElementById('poItemsBody').appendChild(row);
        poRowCount++;
    }
    function updatePOPrice(sel){
        var opt=sel.options[sel.selectedIndex];
        var row=sel.closest('tr');
        row.querySelector('input[name="unit_price[]"]').value=opt.dataset.price||0;
        recalcPO();
    }
    function removePORow(id){document.getElementById(id).remove();recalcPO();}
    function recalcPO(){
        var subtotal=0;
        document.querySelectorAll('#poItemsBody tr').forEach(function(row){
            var qty=parseFloat(row.querySelector('input[name="quantity[]"]').value)||0;
            var up=parseFloat(row.querySelector('input[name="unit_price[]"]').value)||0;
            var t=qty*up;
            row.querySelector('.rowTotal').textContent=t.toFixed(2);
            subtotal+=t;
        });
        document.getElementById('poSubtotal').textContent=subtotal.toFixed(2);
        var tax=parseFloat(document.getElementById('poTax').value)||0;
        var disc=parseFloat(document.getElementById('poDiscount').value)||0;
        var total=subtotal+(subtotal*tax/100)-disc;
        document.getElementById('poGrandTotal').textContent=total.toFixed(2);
    }
    addPOItem();
    </script>
    <?php
    renderFooter();
}

function renderPurchaseDetail($po, $items)
{
    renderHeader('PO: ' . ($po['po_number'] ?? ''));
    $csrf = generateCSRF();
    $canReceive = in_array($po['order_status'], ['Pending', 'Partial']);
    ?>
    <div class="page-title">&#128722; <?= h($po['po_number']) ?>
        <div class="page-title-actions">
            <?php if ($canReceive && hasRole(['Admin', 'Manager'])): ?>
            <button onclick="openModal('receiveModal')" class="btn btn-success btn-sm">Receive Stock</button>
            <?php endif; ?>
            <button onclick="printPage()" class="btn btn-sm">&#128424; Print</button>
            <a href="?page=purchases" class="btn btn-sm">&#8592; Back</a>
        </div>
    </div>
    <div class="print-header"><h2><?= h(getSetting('company_name')) ?></h2><p><?= h(getSetting('company_address')) ?></p><h3>PURCHASE ORDER</h3></div>
    <div class="two-col-layout">
        <div class="card">
            <div class="card-title">PO Details</div>
            <table class="detail-table">
                <tr><td>PO Number</td><td><strong><?= h($po['po_number']) ?></strong></td></tr>
                <tr><td>Supplier</td><td><?= h($po['company_name']) ?></td></tr>
                <tr><td>Contact</td><td><?= h($po['contact_person'] ?? '-') ?></td></tr>
                <tr><td>Phone</td><td><?= h($po['phone'] ?? '-') ?></td></tr>
                <tr><td>PO Date</td><td><?= formatDate($po['po_date']) ?></td></tr>
                <tr><td>Expected Delivery</td><td><?= formatDate($po['expected_delivery']) ?></td></tr>
                <tr><td>Order Status</td><td><span class="badge <?= $po['order_status'] === 'Received' ? 'badge-success' : ($po['order_status'] === 'Cancelled' ? 'badge-danger' : 'badge-warning') ?>"><?= h($po['order_status']) ?></span></td></tr>
                <tr><td>Payment Status</td><td><span class="badge <?= $po['payment_status'] === 'Paid' ? 'badge-success' : ($po['payment_status'] === 'Partial' ? 'badge-warning' : 'badge-danger') ?>"><?= h($po['payment_status']) ?></span></td></tr>
            </table>
        </div>
        <div class="card">
            <div class="card-title">Financial Summary</div>
            <table class="detail-table">
                <tr><td>Subtotal</td><td><?= formatCurrency($po['subtotal']) ?></td></tr>
                <tr><td>Tax (<?= h($po['tax_percent']) ?>%)</td><td><?= formatCurrency($po['subtotal'] * $po['tax_percent'] / 100) ?></td></tr>
                <tr><td>Discount</td><td><?= formatCurrency($po['discount']) ?></td></tr>
                <tr><td><strong>Total</strong></td><td><strong style="font-size:16px"><?= formatCurrency($po['total_amount']) ?></strong></td></tr>
            </table>
            <?php if ($po['notes']): ?><div style="margin-top:8px;padding:6px;background:#fffff8;border:1px solid #ccc;font-size:12px"><strong>Notes:</strong> <?= h($po['notes']) ?></div><?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-title">Order Items</div>
        <div class="items-table-wrap">
        <table class="items-table">
            <thead><tr><th>#</th><th>SKU</th><th>Product</th><th>Unit</th><th>Qty</th><th>Received</th><th>Pending</th><th>Unit Price</th><th>Total</th></tr></thead>
            <tbody>
            <?php
            $i = 1;
            foreach ($items as $item):
                $pending = max(0, $item['quantity'] - $item['received_qty']);
                ?>
            <tr>
                <td><?= $i++ ?></td>
                <td style="font-family:monospace;font-size:11px"><?= h($item['sku']) ?></td>
                <td><?= h($item['product_name']) ?></td>
                <td><?= h($item['unit']) ?></td>
                <td><?= h($item['quantity']) ?></td>
                <td style="color:var(--green-dark)"><?= h($item['received_qty']) ?></td>
                <td style="color:<?= ($pending > 0 ? 'var(--red-dark)' : 'var(--green-dark)') ?>">
                    <?= h($pending) ?>
                </td>
                <td><?= formatCurrency($item['unit_price']) ?></td>
                <td><?= formatCurrency($item['total_price']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php if ($canReceive && hasRole(['Admin', 'Manager'])): ?>
    <div id="receiveModal" class="modal-overlay">
        <div class="modal" style="max-width:600px">
            <div class="modal-title-bar">Receive Stock <button class="modal-close" onclick="closeModal('receiveModal')">&#10005;</button></div>
            <form method="POST" action="?page=purchases&action=receive">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
                <input type="hidden" name="po_id" value="<?= h($po['id']) ?>">
                <div class="modal-body">
                    <p style="font-size:12px;color:#666;margin-bottom:8px">Enter quantities received for each item:</p>
                    <table class="items-table">
                        <thead><tr><th>Product</th><th>Ordered</th><th>Already Received</th><th>Receive Now</th></tr></thead>
                        <tbody>
                        <?php
                        foreach ($items as $item):
                            $pending = max(0, $item['quantity'] - $item['received_qty']);
                            ?>
                        <tr>
                            <td><?= h($item['product_name']) ?></td>
                            <td><?= h($item['quantity']) ?></td>
                            <td><?= h($item['received_qty']) ?></td>
                            <td><input type="number" name="recv_qty[<?= h($item['id']) ?>]" value="<?= h($pending) ?>" min="0" max="<?= h($pending) ?>" step="0.01" style="width:80px"></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-success">Confirm Receipt</button><button type="button" class="btn" onclick="closeModal('receiveModal')">Cancel</button></div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    <?php
    renderFooter();
}

function renderSalesList($invoices, $search, $status, $dateFrom, $dateTo, $total, $page, $perPage, $baseUrl)
{
    renderHeader('Sales & Invoices');
    $csrf = generateCSRF();
    ?>
    <div class="page-title">&#128176; Sales / Invoices
        <div class="page-title-actions">
            <a href="?page=sales&action=add" class="btn btn-primary btn-sm">+ New Invoice</a>
        </div>
    </div>
    <form method="GET" action=""><input type="hidden" name="page" value="sales">
        <div class="filter-bar">
            <div class="filter-group"><label>Search</label><input type="search" name="search" value="<?= h($search) ?>" placeholder="Invoice#, Customer..."></div>
            <div class="filter-group"><label>Payment</label>
                <select name="status"><option value="">All</option><?php foreach (['Paid', 'Partial', 'Unpaid'] as $s): ?><option value="<?= h($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= h($s) ?></option><?php endforeach; ?></select>
            </div>
            <div class="filter-group"><label>From</label><input type="date" name="date_from" value="<?= h($dateFrom) ?>"></div>
            <div class="filter-group"><label>To</label><input type="date" name="date_to" value="<?= h($dateTo) ?>"></div>
            <div class="filter-group" style="justify-content:flex-end"><label>&nbsp;</label><button type="submit" class="btn btn-primary">Filter</button></div>
            <div class="filter-group" style="justify-content:flex-end"><label>&nbsp;</label><a href="?page=sales" class="btn">Clear</a></div>
        </div>
    </form>
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Invoice#</th><th>Customer</th><th>Date</th><th>Due</th><th>Total</th><th>Payment</th><th>Method</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if (empty($invoices)): ?>
        <tr><td colspan="8" style="text-align:center;padding:20px;color:#888">No invoices found.</td></tr>
        <?php
    else:
        foreach ($invoices as $inv):
            $pClass = $inv['payment_status'] === 'Paid' ? 'badge-success' : ($inv['payment_status'] === 'Partial' ? 'badge-warning' : 'badge-danger');
            ?>
        <tr>
            <td data-label="Inv#"><a href="?page=sales&action=view&id=<?= h($inv['id']) ?>"><?= h($inv['invoice_number']) ?></a></td>
            <td data-label="Customer"><?= h($inv['customer_name']) ?></td>
            <td data-label="Date"><?= formatDate($inv['invoice_date']) ?></td>
            <td data-label="Due"><?= formatDate($inv['due_date']) ?></td>
            <td data-label="Total"><?= formatCurrency($inv['total_amount']) ?></td>
            <td data-label="Payment"><span class="badge <?= $pClass ?>"><?= h($inv['payment_status']) ?></span></td>
            <td data-label="Method"><?= h($inv['payment_method']) ?></td>
            <td data-label="Actions" style="white-space:nowrap">
                <a href="?page=sales&action=view&id=<?= h($inv['id']) ?>" class="btn btn-xs">View</a>
                <?php if (hasRole(['Admin'])): ?>
                <form method="POST" action="?page=sales&action=delete" style="display:inline" id="del_inv_<?= h($inv['id']) ?>">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
                    <input type="hidden" name="id" value="<?= h($inv['id']) ?>">
                    <button type="button" class="btn btn-xs btn-danger" onclick="confirmDelete('del_inv_<?= h($inv['id']) ?>')">Del</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach;
    endif; ?>
        </tbody>
    </table>
    </div>
    <?= paginate($total, $page, $perPage, $baseUrl) ?>
    <?php
    renderFooter();
}

function renderSalesForm($customers, $products)
{
    renderHeader('New Invoice');
    $csrf = generateCSRF();
    ?>
    <div class="page-title">&#128176; New Invoice <div class="page-title-actions"><a href="?page=sales" class="btn btn-sm">&#8592; Back</a></div></div>
    <form method="POST" action="?page=sales&action=add" id="invForm">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
        <fieldset class="form-section"><legend>Invoice Information</legend>
            <div class="form-row-3">
                <div class="form-group"><label>Customer *</label>
                    <select name="customer_id" required>
                        <option value="">-- Select Customer --</option>
                        <?php foreach ($customers as $c): ?><option value="<?= h($c['id']) ?>"><?= h($c['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Invoice Date *</label><input type="date" name="invoice_date" value="<?= date('Y-m-d') ?>" required></div>
                <div class="form-group"><label>Due Date</label><input type="date" name="due_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Payment Method</label>
                    <select name="payment_method">
                        <?php foreach (['Cash', 'Card', 'Bank Transfer', 'Credit'] as $pm): ?><option value="<?= h($pm) ?>"><?= h($pm) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Payment Status</label>
                    <select name="payment_status">
                        <option value="Unpaid">Unpaid</option><option value="Partial">Partial</option><option value="Paid">Paid</option>
                    </select>
                </div>
            </div>
        </fieldset>
        <fieldset class="form-section"><legend>Invoice Items</legend>
            <button type="button" id="add-item-btn" onclick="addInvItem()">+ Add Item</button>
            <div class="items-table-wrap">
            <table class="items-table" id="invItemsTable">
                <thead><tr><th>Product</th><th>Stock</th><th>Qty</th><th>Unit Price</th><th>Disc%</th><th>Total</th><th>Remove</th></tr></thead>
                <tbody id="invItemsBody"></tbody>
                <tfoot><tr><td colspan="5" style="text-align:right">Subtotal:</td><td id="invSubtotal" colspan="2">0.00</td></tr></tfoot>
            </table>
            </div>
        </fieldset>
        <fieldset class="form-section"><legend>Totals</legend>
            <div class="form-row-3">
                <div class="form-group"><label>Tax %</label><input type="number" name="tax_percent" id="invTax" value="0" step="0.01" min="0" max="100" onchange="recalcInv()"></div>
                <div class="form-group"><label>Discount</label><input type="number" name="discount" id="invDiscount" value="0" step="0.01" min="0" onchange="recalcInv()"></div>
                <div class="form-group">&nbsp;</div>
            </div>
            <div style="background:#eee;border:1px solid #ccc;padding:8px;margin-top:6px">
                <strong>Grand Total: </strong><span id="invGrandTotal" style="font-size:16px;font-weight:700;color:var(--blue-dark)">0.00</span>
            </div>
        </fieldset>
        <div class="form-group"><label>Notes</label><textarea name="notes" rows="2"></textarea></div>
        <div style="display:flex;gap:8px;margin-top:10px">
            <button type="submit" class="btn btn-primary">&#10003; Create Invoice</button>
            <a href="?page=sales" class="btn">Cancel</a>
        </div>
    </form>
    <script>
    var invProducts=<?= json_encode(array_map(fn($p) => ['id' => $p['id'], 'name' => $p['name'] . ' (' . $p['sku'] . ')', 'price' => $p['selling_price'], 'stock' => $p['current_stock']], $products)) ?>;
    var invRowCount=0;
    function addInvItem(){
        var opts=invProducts.map(p=>'<option value="'+p.id+'" data-price="'+p.price+'" data-stock="'+p.stock+'">'+p.name+'</option>').join('');
        var row=document.createElement('tr');
        row.id='invRow'+invRowCount;
        row.innerHTML='<td><select name="product_id[]" onchange="updateInvPrice(this)" required><option value="">-- Select --</option>'+opts+'</select></td>'
            +'<td class="rowStock" style="font-size:11px;color:#888">-</td>'
            +'<td><input type="number" name="quantity[]" value="1" min="0.01" step="0.01" style="width:80px" onchange="recalcInv()" required></td>'
            +'<td><input type="number" name="unit_price[]" value="0" min="0" step="0.01" style="width:100px" onchange="recalcInv()" required></td>'
            +'<td><input type="number" name="item_discount[]" value="0" min="0" max="100" step="0.01" style="width:65px" onchange="recalcInv()"></td>'
            +'<td class="rowTotal">0.00</td>'
            +'<td><button type="button" class="remove-row-btn" onclick="removeInvRow(\'invRow'+invRowCount+'\')">&#10005;</button></td>';
        document.getElementById('invItemsBody').appendChild(row);
        invRowCount++;
    }
    function updateInvPrice(sel){
        var opt=sel.options[sel.selectedIndex];
        var row=sel.closest('tr');
        row.querySelector('input[name="unit_price[]"]').value=opt.dataset.price||0;
        row.querySelector('.rowStock').textContent='Stock: '+(opt.dataset.stock||0);
        recalcInv();
    }
    function removeInvRow(id){document.getElementById(id).remove();recalcInv();}
    function recalcInv(){
        var subtotal=0;
        document.querySelectorAll('#invItemsBody tr').forEach(function(row){
            var qty=parseFloat(row.querySelector('input[name="quantity[]"]').value)||0;
            var up=parseFloat(row.querySelector('input[name="unit_price[]"]').value)||0;
            var disc=parseFloat(row.querySelector('input[name="item_discount[]"]').value)||0;
            var t=qty*up*(1-disc/100);
            row.querySelector('.rowTotal').textContent=t.toFixed(2);
            subtotal+=t;
        });
        document.getElementById('invSubtotal').textContent=subtotal.toFixed(2);
        var tax=parseFloat(document.getElementById('invTax').value)||0;
        var disc=parseFloat(document.getElementById('invDiscount').value)||0;
        var total=subtotal+(subtotal*tax/100)-disc;
        document.getElementById('invGrandTotal').textContent=total.toFixed(2);
    }
    addInvItem();
    </script>
    <?php
    renderFooter();
}

function renderSalesDetail($inv, $items)
{
    renderHeader('Invoice: ' . ($inv['invoice_number'] ?? ''));
    $csrf = generateCSRF();
    ?>
    <div class="page-title">&#128176; <?= h($inv['invoice_number']) ?>
        <div class="page-title-actions">
            <?php if ($inv['payment_status'] !== 'Paid' && hasRole(['Admin', 'Manager'])): ?>
            <button onclick="openModal('payModal')" class="btn btn-success btn-sm">Mark Paid</button>
            <?php endif; ?>
            <?php if (hasRole(['Admin', 'Manager'])): ?>
            <button onclick="openModal('returnModal')" class="btn btn-warning btn-sm">Return/Refund</button>
            <?php endif; ?>
            <button onclick="printPage()" class="btn btn-sm">&#128424; Print</button>
            <a href="?page=sales" class="btn btn-sm">&#8592; Back</a>
        </div>
    </div>
    <div class="print-header">
        <h2><?= h(getSetting('company_name')) ?></h2>
        <p><?= h(getSetting('company_address')) ?> | <?= h(getSetting('company_phone')) ?> | <?= h(getSetting('company_email')) ?></p>
        <h3>INVOICE</h3>
    </div>
    <div class="two-col-layout">
        <div class="card">
            <div class="card-title">Invoice Details</div>
            <table class="detail-table">
                <tr><td>Invoice#</td><td><strong><?= h($inv['invoice_number']) ?></strong></td></tr>
                <tr><td>Customer</td><td><?= h($inv['customer_name']) ?></td></tr>
                <tr><td>Email</td><td><?= h($inv['customer_email'] ?? '-') ?></td></tr>
                <tr><td>Phone</td><td><?= h($inv['customer_phone'] ?? '-') ?></td></tr>
                <tr><td>Invoice Date</td><td><?= formatDate($inv['invoice_date']) ?></td></tr>
                <tr><td>Due Date</td><td><?= formatDate($inv['due_date']) ?></td></tr>
                <tr><td>Payment Method</td><td><?= h($inv['payment_method']) ?></td></tr>
                <tr><td>Payment Status</td><td><span class="badge <?= $inv['payment_status'] === 'Paid' ? 'badge-success' : ($inv['payment_status'] === 'Partial' ? 'badge-warning' : 'badge-danger') ?>"><?= h($inv['payment_status']) ?></span></td></tr>
                <tr><td>Created By</td><td><?= h($inv['created_by_name'] ?? '-') ?></td></tr>
            </table>
        </div>
        <div class="card">
            <div class="card-title">Financial Summary</div>
            <table class="detail-table">
                <tr><td>Subtotal</td><td><?= formatCurrency($inv['subtotal']) ?></td></tr>
                <tr><td>Tax (<?= h($inv['tax_percent']) ?>%)</td><td><?= formatCurrency($inv['subtotal'] * $inv['tax_percent'] / 100) ?></td></tr>
                <tr><td>Discount</td><td><?= formatCurrency($inv['discount']) ?></td></tr>
                <tr><td><strong>Total</strong></td><td><strong style="font-size:16px"><?= formatCurrency($inv['total_amount']) ?></strong></td></tr>
            </table>
            <?php if ($inv['notes']): ?><div style="margin-top:8px;padding:6px;background:#fffff8;border:1px solid #ccc;font-size:12px"><strong>Notes:</strong> <?= h($inv['notes']) ?></div><?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-title">Invoice Items</div>
        <div class="items-table-wrap">
        <table class="items-table">
            <thead><tr><th>#</th><th>SKU</th><th>Product</th><th>Unit</th><th>Qty</th><th>Unit Price</th><th>Disc%</th><th>Total</th></tr></thead>
            <tbody>
            <?php $i = 1;
            foreach ($items as $item): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td style="font-family:monospace;font-size:11px"><?= h($item['sku']) ?></td>
                <td><?= h($item['product_name']) ?></td>
                <td><?= h($item['unit']) ?></td>
                <td><?= h($item['quantity']) ?></td>
                <td><?= formatCurrency($item['unit_price']) ?></td>
                <td><?= ($item['quantity'] * $item['unit_price'] > 0) ? round(100 * $item['discount'] / ($item['quantity'] * $item['unit_price']), 2) : '0' ?>%</td>
                <td><?= formatCurrency($item['total_price']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><td colspan="7" style="text-align:right">Subtotal:</td><td><?= formatCurrency($inv['subtotal']) ?></td></tr>
                <tr><td colspan="7" style="text-align:right">Tax (<?= h($inv['tax_percent']) ?>%):</td><td><?= formatCurrency($inv['subtotal'] * $inv['tax_percent'] / 100) ?></td></tr>
                <tr><td colspan="7" style="text-align:right">Discount:</td><td><?= formatCurrency($inv['discount']) ?></td></tr>
                <tr><td colspan="7" style="text-align:right"><strong>TOTAL:</strong></td><td><strong><?= formatCurrency($inv['total_amount']) ?></strong></td></tr>
            </tfoot>
        </table>
        </div>
    </div>
    <?php if ($inv['payment_status'] !== 'Paid' && hasRole(['Admin', 'Manager'])): ?>
    <div id="payModal" class="modal-overlay">
        <div class="modal" style="max-width:400px">
            <div class="modal-title-bar">Mark as Paid <button class="modal-close" onclick="closeModal('payModal')">&#10005;</button></div>
            <form method="POST" action="?page=sales&action=mark_paid">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
                <input type="hidden" name="id" value="<?= h($inv['id']) ?>">
                <div class="modal-body">
                    <div class="form-group"><label>Payment Method</label>
                        <select name="payment_method">
                            <?php foreach (['Cash', 'Card', 'Bank Transfer', 'Credit'] as $pm): ?><option value="<?= h($pm) ?>"><?= h($pm) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <p>This action will mark the invoice as fully paid.</p>
                    <input type="hidden" name="payment_status" value="Paid">
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-success">Confirm</button><button type="button" class="btn" onclick="closeModal('payModal')">Cancel</button></div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    <?php if (hasRole(['Admin', 'Manager'])): ?>
    <div id="returnModal" class="modal-overlay">
        <div class="modal" style="max-width:550px">
            <div class="modal-title-bar">Return / Refund Items <button class="modal-close" onclick="closeModal('returnModal')">&#10005;</button></div>
            <form method="POST" action="?page=sales&action=return">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
                <input type="hidden" name="invoice_id" value="<?= h($inv['id']) ?>">
                <div class="modal-body">
                    <p style="font-size:12px;color:#666;margin-bottom:8px">Enter quantities to return (stock will be added back):</p>
                    <table class="items-table">
                        <thead><tr><th>Product</th><th>Sold Qty</th><th>Return Qty</th></tr></thead>
                        <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= h($item['product_name']) ?></td>
                            <td><?= h($item['quantity']) ?></td>
                            <td><input type="number" name="ret_qty[<?= h($item['id']) ?>]" value="0" min="0" max="<?= h($item['quantity']) ?>" step="0.01" style="width:80px"></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="form-group" style="margin-top:8px"><label>Reason</label><textarea name="reason" rows="2" required></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-warning">Process Return</button><button type="button" class="btn" onclick="closeModal('returnModal')">Cancel</button></div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    <?php
    renderFooter();
}

function renderAdjustmentForm($products)
{
    renderHeader('New Stock Adjustment');
    $csrf = generateCSRF();
    ?>
    <div class="page-title">&#9881; New Stock Adjustment
        <div class="page-title-actions">
            <a href="?page=adjustments" class="btn btn-sm">&#8592; Back to List</a>
        </div>
    </div>
    <form method="POST" action="?page=adjustments&action=add">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
        <div class="modal-body" style="max-width: 600px; margin: auto; background: var(--surface2); border: 1px solid var(--border); padding: 10px;">
            <div class="form-group"><label>Product *</label>
                <select name="product_id" required>
                    <option value="">-- Select Product --</option>
                    <?php foreach ($products as $p): ?><option value="<?= h($p['id']) ?>"><?= h($p['name']) ?> (Stock: <?= h($p['current_stock']) ?>)</option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Adjustment Type *</label>
                    <select name="adjustment_type" required>
                        <?php foreach (['Addition', 'Subtraction', 'Damage', 'Expired', 'Correction', 'Opening Stock'] as $at): ?>
                        <option value="<?= h($at) ?>"><?= h($at) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Quantity *</label><input type="number" name="quantity" required min="0.01" step="0.01"></div>
            </div>
            <div class="form-group"><label>Reason *</label><textarea name="reason" rows="2" required></textarea></div>
            <div style="margin-top:10px">
                <button type="submit" class="btn btn-primary">Submit Adjustment</button>
                <a href="?page=adjustments" class="btn">Cancel</a>
            </div>
        </div>
    </form>
    <?php
    renderFooter();
}
function renderAdjustmentList($adjs, $total, $pg, $perPage)
{
    renderHeader('Stock Adjustments');
    $csrf = generateCSRF();
    ?>
    <div class="page-title">&#9881; Stock Adjustments
        <div class="page-title-actions">
            <?php if (hasRole(['Admin', 'Manager', 'Staff'])): ?>
            <a href="?page=adjustments&action=add" class="btn btn-primary btn-sm">+ New Adjustment</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Ref</th><th>Product</th><th>Type</th><th>Qty</th><th>Status</th><th>Date</th><th>Created By</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if (empty($adjs)): ?>
        <tr><td colspan="8" style="text-align:center;padding:20px;color:#888">No adjustments found.</td></tr>
        <?php else:
        foreach ($adjs as $a):
            $negTypes = ['Subtraction', 'Damage', 'Expired'];
            $color = in_array($a['adjustment_type'], $negTypes) ? 'var(--red-dark)' : 'var(--green-dark)';
            ?>
        <tr>
            <td data-label="Ref" style="font-size:11px;font-family:monospace"><?= h($a['reference_no']) ?></td>
            <td data-label="Product"><?= h($a['product_name']) ?> <small>(<?= h($a['sku']) ?>)</small></td>
            <td data-label="Type"><?= h($a['adjustment_type']) ?></td>
            <td data-label="Qty" style="color:<?= $color ?>;font-weight:700"><?= in_array($a['adjustment_type'], $negTypes) ? '-' : '+' ?><?= h($a['quantity']) ?></td>
            <td data-label="Status"><span class="badge <?= $a['status'] === 'Approved' ? 'badge-success' : ($a['status'] === 'Pending' ? 'badge-warning' : 'badge-danger') ?>"><?= h($a['status']) ?></span></td>
            <td data-label="Date"><?= formatDate($a['created_at']) ?></td>
            <td data-label="Created By"><?= h($a['created_by_name']) ?></td>
            <td data-label="Actions" style="white-space:nowrap">
                <?php if ($a['status'] === 'Pending' && hasRole(['Admin', 'Manager'])): ?>
                <form method="POST" action="?page=adjustments&action=approve" style="display:inline" id="app_adj_<?= h($a['id']) ?>">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
                    <input type="hidden" name="id" value="<?= h($a['id']) ?>">
                    <button type="button" class="btn btn-xs btn-success" onclick="confirmAction('Approve this adjustment?','app_adj_<?= h($a['id']) ?>')">Approve</button>
                </form>
                <form method="POST" action="?page=adjustments&action=reject" style="display:inline" id="rej_adj_<?= h($a['id']) ?>">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
                    <input type="hidden" name="id" value="<?= h($a['id']) ?>">
                    <button type="button" class="btn btn-xs btn-danger" onclick="confirmAction('Reject this adjustment?','rej_adj_<?= h($a['id']) ?>')">Reject</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach;
    endif; ?>
        </tbody>
    </table>
    </div>
    <?= paginate($total, $pg, $perPage, '?page=adjustments') ?>
    <?php
    renderFooter();
}
function renderTransferList($transfers, $total, $page, $perPage, $baseUrl)
{
    renderHeader('Stock Transfers');
    $csrf = generateCSRF();
    ?>
    <div class="page-title">&#8646; Stock Transfers
        <div class="page-title-actions">
            <?php if (hasRole(['Admin', 'Manager'])): ?>
            <a href="?page=transfers&action=add" class="btn btn-primary btn-sm">+ New Transfer</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Reference</th><th>From</th><th>To</th><th>Date</th><th>Status</th><th>By</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if (empty($transfers)): ?>
        <tr><td colspan="7" style="text-align:center;padding:20px;color:#888">No transfers found.</td></tr>
        <?php else:
        foreach ($transfers as $t): ?>
        <tr>
            <td data-label="Ref" style="font-size:11px;font-family:monospace"><?= h($t['reference_no']) ?></td>
            <td data-label="From"><?= h($t['from_location']) ?></td>
            <td data-label="To"><?= h($t['to_location']) ?></td>
            <td data-label="Date"><?= formatDate($t['transfer_date']) ?></td>
            <td data-label="Status"><span class="badge <?= $t['status'] === 'Completed' ? 'badge-success' : ($t['status'] === 'Pending' ? 'badge-warning' : ($t['status'] === 'Cancelled' ? 'badge-danger' : 'badge-info')) ?>"><?= h($t['status']) ?></span></td>
            <td data-label="By"><?= h($t['created_by_name']) ?></td>
            <td data-label="Actions" style="white-space:nowrap">
                <?php if ($t['status'] === 'Pending' && hasRole(['Admin', 'Manager'])): ?>
                <form method="POST" action="?page=transfers&action=complete" style="display:inline" id="comp_tr_<?= h($t['id']) ?>">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
                    <input type="hidden" name="id" value="<?= h($t['id']) ?>">
                    <button type="button" class="btn btn-xs btn-success" onclick="confirmAction('Complete this transfer?','comp_tr_<?= h($t['id']) ?>')">Complete</button>
                </form>
                <form method="POST" action="?page=transfers&action=cancel" style="display:inline" id="canc_tr_<?= h($t['id']) ?>">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
                    <input type="hidden" name="id" value="<?= h($t['id']) ?>">
                    <button type="button" class="btn btn-xs btn-danger" onclick="confirmAction('Cancel this transfer?','canc_tr_<?= h($t['id']) ?>')">Cancel</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach;
    endif; ?>
        </tbody>
    </table>
    </div>
    <?= paginate($total, $page, $perPage, $baseUrl) ?>
    <?php
    renderFooter();
}

function renderReports()
{
    renderHeader('Reports');
    $pdo = getDB();
    $report = $_GET['report'] ?? 'stock';
    $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');
    ?>
    <div class="page-title">&#128202; Reports
        <div class="page-title-actions">
            <button onclick="printPage()" class="btn btn-sm no-print">&#128424; Print</button>
            <a href="?page=reports&report=<?= h($report) ?>&date_from=<?= h($dateFrom) ?>&date_to=<?= h($dateTo) ?>&export=csv" class="btn btn-sm no-print">&#8595; CSV</a>
        </div>
    </div>
    <div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:8px" class="no-print">
        <?php $reports = [
            'stock' => 'Stock Report', 'low_stock' => 'Low Stock', 'sales' => 'Sales', 'purchases' => 'Purchases',
            'profit' => 'Profit & Loss', 'valuation' => 'Valuation', 'supplier' => 'Supplier-wise',
            'customer' => 'Customer-wise', 'product_sales' => 'Product-wise Sales', 'category_sales' => 'Category-wise',
            'payment' => 'Payment Status', 'movement' => 'Stock Movement', 'dead_stock' => 'Dead Stock'
        ];
        foreach ($reports as $key => $label): ?>
        <a href="?page=reports&report=<?= h($key) ?>&date_from=<?= h($dateFrom) ?>&date_to=<?= h($dateTo) ?>" class="btn btn-sm <?= $report === $key ? 'btn-primary' : '' ?>"><?= h($label) ?></a>
        <?php endforeach; ?>
    </div>
    <?php if (!in_array($report, ['stock', 'low_stock', 'valuation', 'dead_stock'])): ?>
    <form method="GET" action="" class="no-print"><input type="hidden" name="page" value="reports"><input type="hidden" name="report" value="<?= h($report) ?>">
        <div class="filter-bar">
            <div class="filter-group"><label>From</label><input type="date" name="date_from" value="<?= h($dateFrom) ?>"></div>
            <div class="filter-group"><label>To</label><input type="date" name="date_to" value="<?= h($dateTo) ?>"></div>
            <div class="filter-group" style="justify-content:flex-end"><label>&nbsp;</label><button type="submit" class="btn btn-primary">Apply</button></div>
        </div>
    </form>
    <?php endif; ?>
    <?php
    if ($report === 'stock') {
        $rows = $pdo->query('SELECT p.*,c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY p.name')->fetchAll();
        echo '<div class="card"><div class="card-title">Current Stock Report</div><div class="table-wrap"><table class="data-table"><thead><tr><th>SKU</th><th>Name</th><th>Category</th><th>Unit</th><th>Stock</th><th>Min</th><th>Purchase Price</th><th>Selling Price</th><th>Stock Value</th><th>Status</th></tr></thead><tbody>';
        $totalVal = 0;
        foreach ($rows as $r) {
            $val = $r['current_stock'] * $r['purchase_price'];
            $totalVal += $val;
            $sc = $r['current_stock'] <= 0 ? 'stock-critical' : ($r['current_stock'] <= $r['min_stock'] ? 'stock-low' : 'stock-good');
            echo '<tr><td data-label="SKU" style="font-family:monospace;font-size:11px">' . h($r['sku']) . '</td><td data-label="Name">' . h($r['name']) . '</td><td data-label="Cat">' . h($r['cat_name'] ?? '-') . '</td><td data-label="Unit">' . h($r['unit']) . '</td><td data-label="Stock"><span class="' . $sc . '">' . h($r['current_stock']) . '</span></td><td data-label="Min">' . h($r['min_stock']) . '</td><td data-label="Purchase">' . formatCurrency($r['purchase_price']) . '</td><td data-label="Selling">' . formatCurrency($r['selling_price']) . '</td><td data-label="Value">' . formatCurrency($val) . '</td><td data-label="Status"><span class="badge ' . ($r['status'] ? 'badge-success' : 'badge-danger') . '">' . ($r['status'] ? 'Active' : 'Inactive') . '</span></td></tr>';
        }
        echo '</tbody><tfoot><tr><td colspan="8" style="text-align:right"><strong>Total Inventory Value:</strong></td><td colspan="2"><strong>' . formatCurrency($totalVal) . '</strong></td></tr></tfoot></table></div></div>';
    } elseif ($report === 'low_stock') {
        $rows = $pdo->query('SELECT p.*,c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.current_stock<=p.min_stock ORDER BY p.current_stock ASC')->fetchAll();
        echo '<div class="card"><div class="card-title">Low Stock Report (' . count($rows) . ' products)</div><div class="table-wrap"><table class="data-table"><thead><tr><th>SKU</th><th>Name</th><th>Category</th><th>Current Stock</th><th>Min Level</th><th>Shortage</th><th>Purchase Price</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $shortage = max(0, $r['min_stock'] - $r['current_stock']);
            $sc = $r['current_stock'] <= 0 ? 'stock-critical' : 'stock-low';
            echo '<tr><td data-label="SKU" style="font-family:monospace;font-size:11px">' . h($r['sku']) . '</td><td data-label="Name">' . h($r['name']) . '</td><td data-label="Cat">' . h($r['cat_name'] ?? '-') . '</td><td data-label="Stock"><span class="' . $sc . '">' . h($r['current_stock']) . '</span></td><td data-label="Min">' . h($r['min_stock']) . '</td><td data-label="Shortage" style="color:var(--red-dark);font-weight:700">' . h($shortage) . '</td><td data-label="Price">' . formatCurrency($r['purchase_price']) . '</td></tr>';
        }
        echo '</tbody></table></div></div>';
    } elseif ($report === 'sales') {
        $rows = $pdo->query("SELECT i.*,c.name as customer_name FROM invoices i JOIN customers c ON i.customer_id=c.id WHERE DATE(i.invoice_date) BETWEEN '" . addslashes($dateFrom) . "' AND '" . addslashes($dateTo) . "' ORDER BY i.invoice_date DESC")->fetchAll();
        $totAmt = array_sum(array_column($rows, 'total_amount'));
        echo '<div class="card"><div class="card-title">Sales Report: ' . h($dateFrom) . ' to ' . h($dateTo) . '</div><div class="table-wrap"><table class="data-table"><thead><tr><th>Invoice#</th><th>Customer</th><th>Date</th><th>Subtotal</th><th>Tax</th><th>Discount</th><th>Total</th><th>Payment</th><th>Method</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $pClass = $r['payment_status'] === 'Paid' ? 'badge-success' : ($r['payment_status'] === 'Partial' ? 'badge-warning' : 'badge-danger');
            echo '<tr><td data-label="Inv#">' . h($r['invoice_number']) . '</td><td data-label="Customer">' . h($r['customer_name']) . '</td><td data-label="Date">' . formatDate($r['invoice_date']) . '</td><td data-label="Sub">' . formatCurrency($r['subtotal']) . '</td><td data-label="Tax">' . formatCurrency($r['subtotal'] * $r['tax_percent'] / 100) . '</td><td data-label="Disc">' . formatCurrency($r['discount']) . '</td><td data-label="Total">' . formatCurrency($r['total_amount']) . '</td><td data-label="Status"><span class="badge ' . $pClass . '">' . h($r['payment_status']) . '</span></td><td data-label="Method">' . h($r['payment_method']) . '</td></tr>';
        }
        echo '</tbody><tfoot><tr><td colspan="6" style="text-align:right"><strong>Total:</strong></td><td colspan="3"><strong>' . formatCurrency($totAmt) . '</strong></td></tr></tfoot></table></div></div>';
    } elseif ($report === 'purchases') {
        $rows = $pdo->query("SELECT po.*,s.company_name FROM purchase_orders po JOIN suppliers s ON po.supplier_id=s.id WHERE DATE(po.po_date) BETWEEN '" . addslashes($dateFrom) . "' AND '" . addslashes($dateTo) . "' ORDER BY po.po_date DESC")->fetchAll();
        $totAmt = array_sum(array_column($rows, 'total_amount'));
        echo '<div class="card"><div class="card-title">Purchase Report: ' . h($dateFrom) . ' to ' . h($dateTo) . '</div><div class="table-wrap"><table class="data-table"><thead><tr><th>PO#</th><th>Supplier</th><th>Date</th><th>Total</th><th>Payment</th><th>Status</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            echo '<tr><td data-label="PO#">' . h($r['po_number']) . '</td><td data-label="Supplier">' . h($r['company_name']) . '</td><td data-label="Date">' . formatDate($r['po_date']) . '</td><td data-label="Total">' . formatCurrency($r['total_amount']) . '</td><td data-label="Payment"><span class="badge ' . ($r['payment_status'] === 'Paid' ? 'badge-success' : ($r['payment_status'] === 'Partial' ? 'badge-warning' : 'badge-danger')) . '">' . h($r['payment_status']) . '</span></td><td data-label="Status"><span class="badge ' . ($r['order_status'] === 'Received' ? 'badge-success' : 'badge-warning') . '">' . h($r['order_status']) . '</span></td></tr>';
        }
        echo '</tbody><tfoot><tr><td colspan="3" style="text-align:right"><strong>Total:</strong></td><td colspan="3"><strong>' . formatCurrency($totAmt) . '</strong></td></tr></tfoot></table></div></div>';
    } elseif ($report === 'profit') {
        $salesRow = $pdo->query("SELECT COALESCE(SUM(total_amount),0) as total_sales, COALESCE(SUM(CASE WHEN payment_status='Paid' THEN total_amount ELSE 0 END),0) as paid_sales FROM invoices WHERE DATE(invoice_date) BETWEEN '" . addslashes($dateFrom) . "' AND '" . addslashes($dateTo) . "'")->fetch();
        $costRow = $pdo->query("SELECT COALESCE(SUM(ii.quantity*p.purchase_price),0) as total_cost FROM invoice_items ii JOIN invoices i ON ii.invoice_id=i.id JOIN products p ON ii.product_id=p.id WHERE DATE(i.invoice_date) BETWEEN '" . addslashes($dateFrom) . "' AND '" . addslashes($dateTo) . "'")->fetch();
        $revenue = $salesRow['total_sales'];
        $cost = $costRow['total_cost'];
        $profit = $revenue - $cost;
        $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;
        echo '<div class="card"><div class="card-title">Profit & Loss: ' . h($dateFrom) . ' to ' . h($dateTo) . '</div>';
        echo '<table class="detail-table" style="max-width:500px">';
        echo '<tr><td>Total Revenue (All Invoices)</td><td style="color:var(--green-dark)"><strong>' . formatCurrency($revenue) . '</strong></td></tr>';
        echo '<tr><td>Paid Revenue</td><td>' . formatCurrency($salesRow['paid_sales']) . '</td></tr>';
        echo '<tr><td>Cost of Goods Sold</td><td style="color:var(--red-dark)">' . formatCurrency($cost) . '</td></tr>';
        echo '<tr><td><strong>Gross Profit</strong></td><td><strong style="color:' . ($profit >= 0 ? 'var(--green-dark)' : 'var(--red-dark)') . '">' . formatCurrency($profit) . '</strong></td></tr>';
        echo '<tr><td>Profit Margin</td><td><strong>' . round($margin, 2) . '%</strong></td></tr>';
        echo '</table></div>';
    } elseif ($report === 'valuation') {
        $rows = $pdo->query('SELECT p.*,c.name as cat_name,(p.current_stock*p.purchase_price) as cost_value,(p.current_stock*p.selling_price) as sell_value FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY cost_value DESC')->fetchAll();
        $totCost = array_sum(array_column($rows, 'cost_value'));
        $totSell = array_sum(array_column($rows, 'sell_value'));
        echo '<div class="card"><div class="card-title">Inventory Valuation Report</div><div class="table-wrap"><table class="data-table"><thead><tr><th>SKU</th><th>Product</th><th>Category</th><th>Stock</th><th>Purchase Price</th><th>Selling Price</th><th>Cost Value</th><th>Sell Value</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            echo '<tr><td data-label="SKU" style="font-family:monospace;font-size:11px">' . h($r['sku']) . '</td><td data-label="Product">' . h($r['name']) . '</td><td data-label="Cat">' . h($r['cat_name'] ?? '-') . '</td><td data-label="Stock">' . h($r['current_stock']) . '</td><td data-label="PPrice">' . formatCurrency($r['purchase_price']) . '</td><td data-label="SPrice">' . formatCurrency($r['selling_price']) . '</td><td data-label="CostVal">' . formatCurrency($r['cost_value']) . '</td><td data-label="SellVal">' . formatCurrency($r['sell_value']) . '</td></tr>';
        }
        echo '</tbody><tfoot><tr><td colspan="6" style="text-align:right"><strong>Totals:</strong></td><td><strong>' . formatCurrency($totCost) . '</strong></td><td><strong>' . formatCurrency($totSell) . '</strong></td></tr></tfoot></table></div></div>';
    } elseif ($report === 'supplier') {
        $rows = $pdo->query("SELECT s.company_name,COUNT(po.id) as total_orders,COALESCE(SUM(po.total_amount),0) as total_purchased,s.balance FROM suppliers s LEFT JOIN purchase_orders po ON s.id=po.supplier_id AND DATE(po.po_date) BETWEEN '" . addslashes($dateFrom) . "' AND '" . addslashes($dateTo) . "' GROUP BY s.id ORDER BY total_purchased DESC")->fetchAll();
        echo '<div class="card"><div class="card-title">Supplier-wise Purchase Report</div><div class="table-wrap"><table class="data-table"><thead><tr><th>Supplier</th><th>Orders</th><th>Total Purchased</th><th>Balance</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            echo '<tr><td data-label="Supplier">' . h($r['company_name']) . '</td><td data-label="Orders">' . h($r['total_orders']) . '</td><td data-label="Total">' . formatCurrency($r['total_purchased']) . '</td><td data-label="Balance" style="color:' . ($r['balance'] > 0 ? 'var(--red-dark)' : 'var(--green-dark)') . '">' . formatCurrency($r['balance']) . '</td></tr>';
        }
        echo '</tbody></table></div></div>';
    } elseif ($report === 'customer') {
        $rows = $pdo->query("SELECT c.name,COUNT(i.id) as total_invoices,COALESCE(SUM(i.total_amount),0) as total_sales,c.current_balance FROM customers c LEFT JOIN invoices i ON c.id=i.customer_id AND DATE(i.invoice_date) BETWEEN '" . addslashes($dateFrom) . "' AND '" . addslashes($dateTo) . "' GROUP BY c.id ORDER BY total_sales DESC")->fetchAll();
        echo '<div class="card"><div class="card-title">Customer-wise Sales Report</div><div class="table-wrap"><table class="data-table"><thead><tr><th>Customer</th><th>Invoices</th><th>Total Sales</th><th>Balance</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            echo '<tr><td data-label="Customer">' . h($r['name']) . '</td><td data-label="Inv">' . h($r['total_invoices']) . '</td><td data-label="Total">' . formatCurrency($r['total_sales']) . '</td><td data-label="Balance" style="color:' . ($r['current_balance'] > 0 ? 'var(--red-dark)' : 'var(--green-dark)') . '">' . formatCurrency($r['current_balance']) . '</td></tr>';
        }
        echo '</tbody></table></div></div>';
    } elseif ($report === 'product_sales') {
        $rows = $pdo->query("SELECT p.name,p.sku,SUM(ii.quantity) as total_qty,SUM(ii.total_price) as total_revenue FROM invoice_items ii JOIN products p ON ii.product_id=p.id JOIN invoices i ON ii.invoice_id=i.id WHERE DATE(i.invoice_date) BETWEEN '" . addslashes($dateFrom) . "' AND '" . addslashes($dateTo) . "' GROUP BY p.id ORDER BY total_revenue DESC")->fetchAll();
        echo '<div class="card"><div class="card-title">Product-wise Sales Report</div><div class="table-wrap"><table class="data-table"><thead><tr><th>SKU</th><th>Product</th><th>Qty Sold</th><th>Revenue</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            echo '<tr><td data-label="SKU" style="font-family:monospace;font-size:11px">' . h($r['sku']) . '</td><td data-label="Product">' . h($r['name']) . '</td><td data-label="Qty">' . h($r['total_qty']) . '</td><td data-label="Revenue">' . formatCurrency($r['total_revenue']) . '</td></tr>';
        }
        echo '</tbody></table></div></div>';
    } elseif ($report === 'category_sales') {
        $rows = $pdo->query("SELECT c.name as category,SUM(ii.quantity) as total_qty,SUM(ii.total_price) as total_revenue FROM invoice_items ii JOIN products p ON ii.product_id=p.id JOIN categories c ON p.category_id=c.id JOIN invoices i ON ii.invoice_id=i.id WHERE DATE(i.invoice_date) BETWEEN '" . addslashes($dateFrom) . "' AND '" . addslashes($dateTo) . "' GROUP BY c.id ORDER BY total_revenue DESC")->fetchAll();
        echo '<div class="card"><div class="card-title">Category-wise Sales Report</div><div class="table-wrap"><table class="data-table"><thead><tr><th>Category</th><th>Qty Sold</th><th>Revenue</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            echo '<tr><td data-label="Category">' . h($r['category']) . '</td><td data-label="Qty">' . h($r['total_qty']) . '</td><td data-label="Revenue">' . formatCurrency($r['total_revenue']) . '</td></tr>';
        }
        echo '</tbody></table></div></div>';
    } elseif ($report === 'payment') {
        $recvRows = $pdo->query("SELECT 'Receivable' as type,invoice_number as ref_no,c.name as party,total_amount,payment_status FROM invoices inv JOIN customers c ON inv.customer_id=c.id WHERE payment_status!='Paid' ORDER BY invoice_date")->fetchAll();
        $payRows = $pdo->query("SELECT 'Payable' as type,po_number as ref_no,s.company_name as party,total_amount,payment_status FROM purchase_orders po JOIN suppliers s ON po.supplier_id=s.id WHERE payment_status!='Paid' ORDER BY po_date")->fetchAll();
        $all = array_merge($recvRows, $payRows);
        echo '<div class="card"><div class="card-title">Payment Status Report (Outstanding)</div><div class="table-wrap"><table class="data-table"><thead><tr><th>Type</th><th>Reference</th><th>Party</th><th>Amount</th><th>Status</th></tr></thead><tbody>';
        foreach ($all as $r) {
            echo '<tr><td data-label="Type"><span class="badge ' . ($r['type'] === 'Receivable' ? 'badge-success' : 'badge-danger') . '">' . h($r['type']) . '</span></td><td data-label="Ref">' . h($r['ref_no']) . '</td><td data-label="Party">' . h($r['party']) . '</td><td data-label="Amount">' . formatCurrency($r['total_amount']) . '</td><td data-label="Status"><span class="badge badge-warning">' . h($r['payment_status']) . '</span></td></tr>';
        }
        echo '</tbody></table></div></div>';
    } elseif ($report === 'movement') {
        $rows = $pdo->query("SELECT sa.*,p.name as product_name,u.full_name as by_name FROM stock_adjustments sa JOIN products p ON sa.product_id=p.id JOIN users u ON sa.created_by=u.id WHERE sa.status='Approved' ORDER BY sa.created_at DESC LIMIT 200")->fetchAll();
        echo '<div class="card"><div class="card-title">Stock Movement Report</div><div class="table-wrap"><table class="data-table"><thead><tr><th>Ref</th><th>Product</th><th>Type</th><th>Qty</th><th>Before</th><th>After</th><th>By</th><th>Date</th></tr></thead><tbody>';
        $negTypes = ['Subtraction', 'Damage', 'Expired'];
        foreach ($rows as $r) {
            $color = in_array($r['adjustment_type'], $negTypes) ? 'var(--red-dark)' : 'var(--green-dark)';
            echo '<tr><td data-label="Ref" style="font-size:11px">' . h($r['reference_no']) . '</td><td data-label="Product">' . h($r['product_name']) . '</td><td data-label="Type">' . h($r['adjustment_type']) . '</td><td data-label="Qty" style="color:' . $color . ';font-weight:700">' . (in_array($r['adjustment_type'], $negTypes) ? '-' : '+') . h($r['quantity']) . '</td><td data-label="Before">' . h($r['previous_stock']) . '</td><td data-label="After">' . h($r['new_stock']) . '</td><td data-label="By">' . h($r['by_name']) . '</td><td data-label="Date">' . formatDate($r['created_at']) . '</td></tr>';
        }
        echo '</tbody></table></div></div>';
    } elseif ($report === 'dead_stock') {
        $days = (int) ($_GET['dead_days'] ?? 90);
        $rows = $pdo->query("SELECT p.*,c.name as cat_name,MAX(i.invoice_date) as last_sale FROM products p LEFT JOIN categories c ON p.category_id=c.id LEFT JOIN invoice_items ii ON p.id=ii.product_id LEFT JOIN invoices i ON ii.invoice_id=i.id GROUP BY p.id HAVING last_sale IS NULL OR last_sale < DATE_SUB(NOW(), INTERVAL {$days} DAY) ORDER BY last_sale ASC")->fetchAll();
        echo '<div class="card"><div class="card-title">Dead Stock Report (No movement in ' . $days . ' days)</div>';
        echo '<form method="GET" action="" style="margin-bottom:8px"><input type="hidden" name="page" value="reports"><input type="hidden" name="report" value="dead_stock"><div class="filter-bar"><div class="filter-group"><label>No movement in (days)</label><input type="number" name="dead_days" value="' . $days . '" min="1" style="width:100px"></div><div class="filter-group" style="justify-content:flex-end"><label>&nbsp;</label><button type="submit" class="btn btn-primary">Apply</button></div></div></form>';
        echo '<div class="table-wrap"><table class="data-table"><thead><tr><th>SKU</th><th>Product</th><th>Category</th><th>Stock</th><th>Last Sale</th><th>Stock Value</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            echo '<tr><td data-label="SKU" style="font-family:monospace;font-size:11px">' . h($r['sku']) . '</td><td data-label="Product">' . h($r['name']) . '</td><td data-label="Cat">' . h($r['cat_name'] ?? '-') . '</td><td data-label="Stock">' . h($r['current_stock']) . '</td><td data-label="Last Sale">' . ($r['last_sale'] ? formatDate($r['last_sale']) : 'Never') . '</td><td data-label="Value">' . formatCurrency($r['current_stock'] * $r['purchase_price']) . '</td></tr>';
        }
        echo '</tbody></table></div></div>';
    }
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        ob_end_clean();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="report_' . h($report) . '_' . date('Ymd') . '.csv"');
    }
    ?>
    <?php
    renderFooter();
}

function renderUsers($users)
{
    renderHeader('User Management');
    $csrf = generateCSRF();
    ?>
    <div class="page-title">&#128100; Users
        <div class="page-title-actions">
            <button onclick="openModal('addUserModal')" class="btn btn-primary btn-sm">+ Add User</button>
        </div>
    </div>
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
            <td data-label="Username"><strong><?= h($u['username']) ?></strong></td>
            <td data-label="Name"><?= h($u['full_name']) ?></td>
            <td data-label="Email"><?= h($u['email'] ?? '-') ?></td>
            <td data-label="Role"><span class="badge <?= $u['role'] === 'Admin' ? 'badge-danger' : ($u['role'] === 'Manager' ? 'badge-warning' : 'badge-info') ?>"><?= h($u['role']) ?></span></td>
            <td data-label="Status"><span class="badge <?= $u['status'] ? 'badge-success' : 'badge-danger' ?>"><?= $u['status'] ? 'Active' : 'Inactive' ?></span></td>
            <td data-label="Last Login" style="font-size:11px"><?= formatDate($u['last_login'] ?? '') ?></td>
            <td data-label="Actions" style="white-space:nowrap">
                <button onclick="editUser(<?= h(json_encode($u)) ?>)" class="btn btn-xs btn-warning">Edit</button>
                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                <form method="POST" action="?page=users&action=toggle" style="display:inline" id="tog_u_<?= h($u['id']) ?>">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
                    <input type="hidden" name="id" value="<?= h($u['id']) ?>">
                    <button type="submit" class="btn btn-xs"><?= $u['status'] ? 'Disable' : 'Enable' ?></button>
                </form>
                <form method="POST" action="?page=users&action=delete" style="display:inline" id="del_u_<?= h($u['id']) ?>">
                    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
                    <input type="hidden" name="id" value="<?= h($u['id']) ?>">
                    <button type="button" class="btn btn-xs btn-danger" onclick="confirmDelete('del_u_<?= h($u['id']) ?>')">Del</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php foreach (['add', 'edit'] as $mt): ?>
    <div id="<?= $mt ?>UserModal" class="modal-overlay">
        <div class="modal" style="max-width:480px">
            <div class="modal-title-bar"><?= ucfirst($mt) ?> User <button class="modal-close" onclick="closeModal('<?= $mt ?>UserModal')">&#10005;</button></div>
            <form method="POST" action="?page=users&action=<?= $mt ?>">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
                <?php if ($mt === 'edit'): ?><input type="hidden" name="id" id="editUserId"><?php endif; ?>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group"><label>Username *</label><input type="text" name="username" id="<?= $mt ?>UserUsername" required minlength="3" autocomplete="off"></div>
                        <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" id="<?= $mt ?>UserFullname" required></div>
                    </div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" id="<?= $mt ?>UserEmail" autocomplete="off"></div>
                    <div class="form-group"><label>Password <?= $mt === 'edit' ? '(leave blank to keep)' : ' *' ?></label>
                        <input type="password" name="password" id="<?= $mt ?>UserPassword" <?= $mt === 'add' ? 'required' : '' ?> minlength="8" autocomplete="new-password" placeholder="Min 8 chars, 1 special char">
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Role *</label>
                            <select name="role" id="<?= $mt ?>UserRole">
                                <?php foreach (['Admin', 'Manager', 'Staff'] as $r): ?><option value="<?= h($r) ?>"><?= h($r) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Status</label>
                            <select name="status" id="<?= $mt ?>UserStatus"><option value="1">Active</option><option value="0">Inactive</option></select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button><button type="button" class="btn" onclick="closeModal('<?= $mt ?>UserModal')">Cancel</button></div>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
    <script>
    function editUser(u){
        document.getElementById('editUserId').value=u.id;
        document.getElementById('editUserUsername').value=u.username;
        document.getElementById('editUserFullname').value=u.full_name;
        document.getElementById('editUserEmail').value=u.email||'';
        document.getElementById('editUserPassword').value='';
        document.getElementById('editUserRole').value=u.role;
        document.getElementById('editUserStatus').value=u.status;
        openModal('editUserModal');
    }
    </script>
    <?php
    renderFooter();
}

function renderProfile($user)
{
    renderHeader('My Profile');
    $csrf = generateCSRF();
    ?>
    <div class="page-title">&#128100; My Profile</div>
    <div class="two-col-layout">
        <div class="card">
            <div class="card-title">Profile Information</div>
            <form method="POST" action="?page=profile&action=update">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
                <div class="form-group"><label>Username</label><input type="text" value="<?= h($user['username']) ?>" disabled style="background:#e0e0e0"></div>
                <div class="form-group"><label>Full Name</label><input type="text" name="full_name" value="<?= h($user['full_name']) ?>" required></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= h($user['email'] ?? '') ?>"></div>
                <div class="form-group"><label>Role</label><input type="text" value="<?= h($user['role']) ?>" disabled style="background:#e0e0e0"></div>
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
        </div>
        <div class="card">
            <div class="card-title">Change Password</div>
            <form method="POST" action="?page=profile&action=change_password">
                <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
                <div class="form-group"><label>Current Password *</label><input type="password" name="current_password" required autocomplete="current-password"></div>
                <div class="form-group"><label>New Password *</label><input type="password" name="new_password" required minlength="8" autocomplete="new-password" placeholder="Min 8 chars, 1 special char"></div>
                <div class="form-group"><label>Confirm New Password *</label><input type="password" name="confirm_password" required autocomplete="new-password"></div>
                <button type="submit" class="btn btn-warning">Change Password</button>
            </form>
        </div>
    </div>
    <?php
    renderFooter();
}

function renderSettings($settings)
{
    renderHeader('System Settings');
    $csrf = generateCSRF();
    ?>
    <div class="page-title">&#9965; Settings</div>
    <form method="POST" action="?page=settings&action=save">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= $csrf ?>">
        <div class="two-col-layout">
            <div>
                <fieldset class="form-section"><legend>Company Settings</legend>
                    <div class="form-group"><label>Company Name</label><input type="text" name="company_name" value="<?= h($settings['company_name'] ?? '') ?>"></div>
                    <div class="form-group"><label>Address</label><textarea name="company_address" rows="2"><?= h($settings['company_address'] ?? '') ?></textarea></div>
                    <div class="form-group"><label>Phone</label><input type="text" name="company_phone" value="<?= h($settings['company_phone'] ?? '') ?>"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="company_email" value="<?= h($settings['company_email'] ?? '') ?>"></div>
                    <div class="form-group"><label>Website</label><input type="text" name="company_website" value="<?= h($settings['company_website'] ?? '') ?>"></div>
                    <div class="form-group"><label>Tax Number</label><input type="text" name="tax_number" value="<?= h($settings['tax_number'] ?? '') ?>"></div>
                    <div class="form-group"><label>Logo URL</label><input type="text" name="company_logo" value="<?= h($settings['company_logo'] ?? '') ?>"></div>
                </fieldset>
                <fieldset class="form-section"><legend>Invoice Settings</legend>
                    <div class="form-group"><label>Invoice Prefix</label><input type="text" name="invoice_prefix" value="<?= h($settings['invoice_prefix'] ?? 'INV') ?>"></div>
                    <div class="form-group"><label>Invoice Start Number</label><input type="number" name="invoice_start" value="<?= h($settings['invoice_start'] ?? 1000) ?>" min="1"></div>
                    <div class="form-group"><label>Default Tax %</label><input type="number" name="default_tax" value="<?= h($settings['default_tax'] ?? 0) ?>" step="0.01" min="0" max="100"></div>
                    <div class="form-group"><label>Default Payment Terms</label><input type="text" name="default_payment_terms" value="<?= h($settings['default_payment_terms'] ?? 'Net 30') ?>"></div>
                </fieldset>
            </div>
            <div>
                <fieldset class="form-section"><legend>System Settings</legend>
                    <div class="form-group"><label>Currency Code</label><input type="text" name="currency" value="<?= h($settings['currency'] ?? 'USD') ?>" maxlength="3"></div>
                    <div class="form-group"><label>Currency Symbol</label><input type="text" name="currency_symbol" value="<?= h($settings['currency_symbol'] ?? '$') ?>" maxlength="5"></div>
                    <div class="form-group"><label>Date Format</label>
                        <select name="date_format">
                            <?php foreach (['Y-m-d' => 'YYYY-MM-DD', 'd-m-Y' => 'DD-MM-YYYY', 'm/d/Y' => 'MM/DD/YYYY'] as $val => $lbl): ?>
                            <option value="<?= h($val) ?>" <?= ($settings['date_format'] ?? 'Y-m-d') === $val ? 'selected' : '' ?>><?= h($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Timezone</label><input type="text" name="timezone" value="<?= h($settings['timezone'] ?? 'UTC') ?>"></div>
                    <div class="form-group"><label>Default Items Per Page</label>
                        <select name="items_per_page">
                            <?php foreach ([10, 20, 50, 100] as $n): ?><option value="<?= h($n) ?>" <?= ($settings['items_per_page'] ?? 20) == $n ? 'selected' : '' ?>><?= h($n) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Low Stock Default Threshold</label><input type="number" name="low_stock_threshold" value="<?= h($settings['low_stock_threshold'] ?? 10) ?>" min="0"></div>
                </fieldset>
                <fieldset class="form-section"><legend>Backup & Restore</legend>
                    <p style="font-size:12px;color:#666;margin-bottom:8px">Export a SQL dump of the entire database or restore from a previous backup.</p>
                    <a href="?page=backup" class="btn btn-primary">&#8595; Download SQL Backup</a>
                    <div style="margin-top:10px">
                        <label>Restore from SQL file</label>
                        <p style="font-size:11px;color:red;margin-bottom:4px">Restore functionality is not implemented.</p>
                    </div>
                </fieldset>
            </div>
        </div>
        <div style="margin-top:12px"><button type="submit" class="btn btn-primary">&#10003; Save Settings</button></div>
    </form>
    <?php
    renderFooter();
}

function renderActivity($logs, $modules, $search, $module, $dateFrom, $dateTo, $total, $pg, $perPage, $baseUrl)
{
    renderHeader('Activity Log');
    ?>
    <div class="page-title">&#128203; Activity Log
        <div class="page-title-actions no-print">
            <button onclick="printPage()" class="btn btn-sm">&#128424; Print</button>
        </div>
    </div>
    <form method="GET" action="" class="no-print">
        <input type="hidden" name="page" value="activity">
        <div class="filter-bar">
            <div class="filter-group"><label>Search</label><input type="search" name="search" value="<?= h($search) ?>" placeholder="User, Action, Details..."></div>
            <div class="filter-group"><label>Module</label>
                <select name="module"><option value="">All</option><?php foreach ($modules as $m): ?><option value="<?= h($m) ?>" <?= $module === $m ? 'selected' : '' ?>><?= h($m) ?></option><?php endforeach; ?></select>
            </div>
            <div class="filter-group"><label>From</label><input type="date" name="date_from" value="<?= h($dateFrom) ?>"></div>
            <div class="filter-group"><label>To</label><input type="date" name="date_to" value="<?= h($dateTo) ?>"></div>
            <div class="filter-group" style="justify-content:flex-end"><label>&nbsp;</label><button type="submit" class="btn btn-primary">Filter</button></div>
            <div class="filter-group" style="justify-content:flex-end"><label>&nbsp;</label><a href="?page=activity" class="btn">Clear</a></div>
        </div>
    </form>
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Timestamp</th><th>User</th><th>Action</th><th>Module</th><th>Details</th><th>IP</th></tr></thead>
        <tbody>
        <?php if (empty($logs)): ?>
        <tr><td colspan="6" style="text-align:center;padding:20px;color:#888">No activity found.</td></tr>
        <?php else:
        foreach ($logs as $log): ?>
        <tr>
            <td data-label="Time" style="font-size:11px"><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></td>
            <td data-label="User"><?= h($log['username'] ?? 'System') ?></td>
            <td data-label="Action"><span class="badge badge-info"><?= h($log['action']) ?></span></td>
            <td data-label="Module"><?= h($log['module'] ?? '-') ?></td>
            <td data-label="Details" style="max-width:300px;font-size:11px;word-break:break-word"><?= h($log['description'] ?? '') ?></td>
            <td data-label="IP" style="font-size:11px;font-family:monospace"><?= h($log['ip_address'] ?? '-') ?></td>
        </tr>
        <?php endforeach;
    endif; ?>
        </tbody>
    </table>
    </div>
    <?= paginate($total, $pg, $perPage, $baseUrl) ?>
    <?php
    renderFooter();
}
?>