<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'inventory_ms2');
define('APP_NAME', 'InventoryPro');
define('APP_VERSION', '2.0.0');
define('SESSION_TIMEOUT', 2400);
define('SESSION_ABSOLUTE', 28800);
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'gc_maxlifetime' => SESSION_TIMEOUT,
]);

function validateStrongPassword(string $pwd): bool
{
    return strlen($pwd) >= 12 && preg_match('/[A-Z]/', $pwd) && preg_match('/[a-z]/', $pwd) && preg_match('/[0-9]/', $pwd) && preg_match('/[^A-Za-z0-9]/', $pwd);
}

function getPDO(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn_root = 'mysql:host=' . DB_HOST . ';charset=utf8mb4';
            $root = new PDO($dsn_root, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $root->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("<pre style='color:red;padding:20px;'>Database connection failed: " . htmlspecialchars($e->getMessage()) . '</pre>');
        }
    }
    return $pdo;
}

function upgradeDatabase(): void
{
    $pdo = getPDO();
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->exec('CREATE TABLE IF NOT EXISTS `roles` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(50) NOT NULL UNIQUE,
        `description` VARCHAR(255),
        `is_system` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE IF NOT EXISTS `permissions` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `module` VARCHAR(50) NOT NULL,
        `action` VARCHAR(50) NOT NULL,
        `display_name` VARCHAR(100) NOT NULL,
        UNIQUE KEY `uniq_module_action` (`module`,`action`)
    ) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE IF NOT EXISTS `role_permissions` (
        `role_id` INT UNSIGNED NOT NULL,
        `permission_id` INT UNSIGNED NOT NULL,
        PRIMARY KEY (`role_id`,`permission_id`),
        FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE IF NOT EXISTS `user_roles` (
        `user_id` INT UNSIGNED NOT NULL,
        `role_id` INT UNSIGNED NOT NULL,
        PRIMARY KEY (`user_id`,`role_id`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB');
    try {
        $pdo->exec('ALTER TABLE `users` ADD COLUMN `role_id` INT UNSIGNED NULL AFTER `role`');
    } catch (PDOException $e) {
    }
    try {
        $pdo->exec('ALTER TABLE `users` ADD COLUMN `phone` VARCHAR(30) NULL AFTER `email`');
    } catch (PDOException $e) {
    }
    try {
        $pdo->exec('ALTER TABLE `users` ADD COLUMN `avatar` VARCHAR(500) NULL AFTER `phone`');
    } catch (PDOException $e) {
    }
    try {
        $pdo->exec('ALTER TABLE `users` ADD COLUMN `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `avatar`');
    } catch (PDOException $e) {
    }
    try {
        $pdo->exec('ALTER TABLE `products` ADD COLUMN `parent_id` INT UNSIGNED NULL AFTER `id`');
    } catch (PDOException $e) {
    }
    try {
        $pdo->exec('ALTER TABLE `products` ADD COLUMN `is_variant` TINYINT(1) NOT NULL DEFAULT 0 AFTER `parent_id`');
    } catch (PDOException $e) {
    }
    try {
        $pdo->exec('ALTER TABLE `products` ADD COLUMN `is_composite` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_variant`');
    } catch (PDOException $e) {
    }
    try {
        $pdo->exec('ALTER TABLE `products` ADD COLUMN `tax_id` INT UNSIGNED NULL AFTER `selling_price`');
    } catch (PDOException $e) {
    }
    try {
        $pdo->exec('ALTER TABLE `products` ADD COLUMN `image_path` VARCHAR(500) NULL AFTER `image_url`');
    } catch (PDOException $e) {
    }
    try {
        $pdo->exec('ALTER TABLE `products` ADD COLUMN `track_serial` TINYINT(1) NOT NULL DEFAULT 0 AFTER `image_path`');
    } catch (PDOException $e) {
    }
    try {
        $pdo->exec('ALTER TABLE `products` ADD COLUMN `track_batch` TINYINT(1) NOT NULL DEFAULT 0 AFTER `track_serial`');
    } catch (PDOException $e) {
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS `product_attributes` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL UNIQUE,
        `type` ENUM('text','number','select','date') NOT NULL DEFAULT 'text'
    ) ENGINE=InnoDB");
    $pdo->exec('CREATE TABLE IF NOT EXISTS `product_attribute_values` (
        `product_id` INT UNSIGNED NOT NULL,
        `attribute_id` INT UNSIGNED NOT NULL,
        `value` TEXT,
        PRIMARY KEY (`product_id`,`attribute_id`),
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`attribute_id`) REFERENCES `product_attributes`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE IF NOT EXISTS `product_images` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `product_id` INT UNSIGNED NOT NULL,
        `image_path` VARCHAR(500) NOT NULL,
        `is_url` TINYINT(1) NOT NULL DEFAULT 0,
        `sort_order` INT NOT NULL DEFAULT 0,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE IF NOT EXISTS `batches` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `product_id` INT UNSIGNED NOT NULL,
        `batch_number` VARCHAR(100) NOT NULL,
        `expiry_date` DATE NULL,
        `quantity` INT NOT NULL DEFAULT 0,
        `purchase_price` DECIMAL(15,2) NOT NULL DEFAULT 0,
        UNIQUE KEY `uniq_product_batch` (`product_id`,`batch_number`),
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB');
    $pdo->exec("CREATE TABLE IF NOT EXISTS `serial_numbers` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `product_id` INT UNSIGNED NOT NULL,
        `serial` VARCHAR(150) NOT NULL UNIQUE,
        `status` ENUM('in_stock','sold','returned','damaged') NOT NULL DEFAULT 'in_stock',
        `invoice_id` INT UNSIGNED NULL,
        `purchase_id` INT UNSIGNED NULL,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    $pdo->exec('CREATE TABLE IF NOT EXISTS `composite_items` (
        `parent_id` INT UNSIGNED NOT NULL,
        `child_id` INT UNSIGNED NOT NULL,
        `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1,
        PRIMARY KEY (`parent_id`,`child_id`),
        FOREIGN KEY (`parent_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`child_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
    ) ENGINE=InnoDB');
    $pdo->exec("CREATE TABLE IF NOT EXISTS `quotations` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `quote_number` VARCHAR(30) NOT NULL UNIQUE,
        `customer_id` INT UNSIGNED NOT NULL,
        `quote_date` DATE NOT NULL,
        `valid_until` DATE NULL,
        `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0,
        `tax_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
        `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
        `status` ENUM('draft','sent','accepted','rejected','converted') NOT NULL DEFAULT 'draft',
        `converted_invoice_id` INT UNSIGNED NULL,
        `created_by` INT UNSIGNED NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`)
    ) ENGINE=InnoDB");
    $pdo->exec('CREATE TABLE IF NOT EXISTS `quotation_items` LIKE `invoice_items`');
    try {
        $pdo->exec('ALTER TABLE `quotation_items` DROP FOREIGN KEY quotation_items_ibfk_1');
    } catch (PDOException $e) {
    }
    try {
        $pdo->exec('ALTER TABLE `quotation_items` CHANGE `invoice_id` `quote_id` INT UNSIGNED NOT NULL');
    } catch (PDOException $e) {
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS `sales_returns` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `return_number` VARCHAR(30) NOT NULL UNIQUE,
        `invoice_id` INT UNSIGNED NOT NULL,
        `customer_id` INT UNSIGNED NOT NULL,
        `return_date` DATE NOT NULL,
        `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
        `refund_method` ENUM('cash','card','bank_transfer','credit_note') NOT NULL DEFAULT 'cash',
        `reason` TEXT,
        `created_by` INT UNSIGNED NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`)
    ) ENGINE=InnoDB");
    $pdo->exec('CREATE TABLE IF NOT EXISTS `sales_return_items` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `return_id` INT UNSIGNED NOT NULL,
        `product_id` INT UNSIGNED NOT NULL,
        `quantity` INT NOT NULL,
        `unit_price` DECIMAL(15,2) NOT NULL,
        `batch_id` INT UNSIGNED NULL,
        `serial_id` INT UNSIGNED NULL,
        FOREIGN KEY (`return_id`) REFERENCES `sales_returns`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE IF NOT EXISTS `purchase_returns` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `return_number` VARCHAR(30) NOT NULL UNIQUE,
        `po_id` INT UNSIGNED NOT NULL,
        `supplier_id` INT UNSIGNED NOT NULL,
        `return_date` DATE NOT NULL,
        `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
        `created_by` INT UNSIGNED NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`po_id`) REFERENCES `purchase_orders`(`id`)
    ) ENGINE=InnoDB');
    $pdo->exec("CREATE TABLE IF NOT EXISTS `requisitions` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `req_number` VARCHAR(30) NOT NULL UNIQUE,
        `requested_by` INT UNSIGNED NOT NULL,
        `status` ENUM('pending','approved','rejected','ordered') NOT NULL DEFAULT 'pending',
        `notes` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    $pdo->exec('CREATE TABLE IF NOT EXISTS `requisition_items` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `req_id` INT UNSIGNED NOT NULL,
        `product_id` INT UNSIGNED NOT NULL,
        `quantity` INT NOT NULL,
        FOREIGN KEY (`req_id`) REFERENCES `requisitions`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE IF NOT EXISTS `expenses` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `expense_date` DATE NOT NULL,
        `category` VARCHAR(100) NOT NULL,
        `amount` DECIMAL(15,2) NOT NULL,
        `payment_method` VARCHAR(50),
        `reference` VARCHAR(100),
        `notes` TEXT,
        `created_by` INT UNSIGNED NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE IF NOT EXISTS `income` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `income_date` DATE NOT NULL,
        `source` VARCHAR(100) NOT NULL,
        `amount` DECIMAL(15,2) NOT NULL,
        `payment_method` VARCHAR(50),
        `reference` VARCHAR(100),
        `notes` TEXT,
        `created_by` INT UNSIGNED NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE IF NOT EXISTS `payment_receipts` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `receipt_number` VARCHAR(30) NOT NULL UNIQUE,
        `customer_id` INT UNSIGNED NULL,
        `supplier_id` INT UNSIGNED NULL,
        `invoice_id` INT UNSIGNED NULL,
        `po_id` INT UNSIGNED NULL,
        `amount` DECIMAL(15,2) NOT NULL,
        `payment_date` DATE NOT NULL,
        `method` VARCHAR(50) NOT NULL,
        `reference` VARCHAR(100),
        `created_by` INT UNSIGNED NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE IF NOT EXISTS `taxes` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(50) NOT NULL,
        `rate` DECIMAL(5,2) NOT NULL,
        `is_default` TINYINT(1) NOT NULL DEFAULT 0
    ) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE IF NOT EXISTS `currencies` (
        `code` CHAR(3) PRIMARY KEY,
        `name` VARCHAR(50) NOT NULL,
        `symbol` VARCHAR(5) NOT NULL,
        `rate_to_base` DECIMAL(15,6) NOT NULL DEFAULT 1.000000,
        `is_base` TINYINT(1) NOT NULL DEFAULT 0
    ) ENGINE=InnoDB');
    $pdo->exec("CREATE TABLE IF NOT EXISTS `stock_audits` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `audit_number` VARCHAR(30) NOT NULL UNIQUE,
        `warehouse_id` INT UNSIGNED NOT NULL,
        `audit_date` DATE NOT NULL,
        `status` ENUM('open','closed') NOT NULL DEFAULT 'open',
        `created_by` INT UNSIGNED NOT NULL
    ) ENGINE=InnoDB");
    $pdo->exec('CREATE TABLE IF NOT EXISTS `stock_audit_items` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `audit_id` INT UNSIGNED NOT NULL,
        `product_id` INT UNSIGNED NOT NULL,
        `system_qty` INT NOT NULL,
        `counted_qty` INT NOT NULL,
        `difference` INT NOT NULL,
        FOREIGN KEY (`audit_id`) REFERENCES `stock_audits`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB');
    $pdo->exec("CREATE TABLE IF NOT EXISTS `shifts` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `open_time` DATETIME NOT NULL,
        `close_time` DATETIME NULL,
        `opening_float` DECIMAL(15,2) NOT NULL DEFAULT 0,
        `closing_cash` DECIMAL(15,2) NULL,
        `total_sales` DECIMAL(15,2) NOT NULL DEFAULT 0,
        `status` ENUM('open','closed') NOT NULL DEFAULT 'open'
    ) ENGINE=InnoDB");
    $pdo->exec('CREATE TABLE IF NOT EXISTS `smtp_settings` (
        `id` TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
        `host` VARCHAR(255),
        `port` INT,
        `username` VARCHAR(255),
        `password` VARCHAR(255),
        `encryption` VARCHAR(10),
        `from_email` VARCHAR(255),
        `from_name` VARCHAR(100)
    ) ENGINE=InnoDB');
    $roles = $pdo->query('SELECT COUNT(*) FROM roles')->fetchColumn();
    if ($roles == 0) {
        $pdo->exec("INSERT INTO roles (name,description,is_system) VALUES 
            ('admin','Full access',1),
            ('manager','Manage operations',1),
            ('cashier','POS only',1),
            ('warehouse','Inventory only',1)");
    }
    $perms = $pdo->query('SELECT COUNT(*) FROM permissions')->fetchColumn();
    if ($perms == 0) {
        $modules = [
            'dashboard' => ['view'],
            'pos' => ['view', 'sell'],
            'products' => ['view', 'create', 'edit', 'delete'],
            'categories' => ['view', 'manage'],
            'purchases' => ['view', 'create', 'edit'],
            'sales' => ['view', 'create', 'edit', 'return'],
            'quotations' => ['view', 'create', 'convert'],
            'customers' => ['view', 'manage'],
            'suppliers' => ['view', 'manage'],
            'reports' => ['view', 'export'],
            'expenses' => ['view', 'manage'],
            'income' => ['view', 'manage'],
            'users' => ['view', 'manage'],
            'roles' => ['view', 'manage'],
            'settings' => ['view', 'manage'],
            'stock_audit' => ['view', 'manage']
        ];
        $stmt = $pdo->prepare('INSERT INTO permissions (module,action,display_name) VALUES (?,?,?)');
        foreach ($modules as $mod => $acts) {
            foreach ($acts as $act) {
                $stmt->execute([$mod, $act, ucfirst($mod) . ' ' . ucfirst($act)]);
            }
        }
        $pdo->exec('INSERT INTO role_permissions (role_id,permission_id) SELECT 1,id FROM permissions');
    }
    $pdo->exec('UPDATE users u JOIN roles r ON u.role=r.name SET u.role_id=r.id WHERE u.role_id IS NULL');
    $pdo->exec("CREATE TABLE IF NOT EXISTS `customer_ledgers` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `customer_id` INT UNSIGNED NOT NULL,
        `transaction_date` DATE NOT NULL,
        `reference_type` ENUM('opening','invoice','payment','return','credit_note') NOT NULL,
        `reference_id` INT UNSIGNED NULL,
        `debit` DECIMAL(15,2) NOT NULL DEFAULT 0,
        `credit` DECIMAL(15,2) NOT NULL DEFAULT 0,
        `balance` DECIMAL(15,2) NOT NULL DEFAULT 0,
        `notes` VARCHAR(255),
        FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
        INDEX (`customer_id`,`transaction_date`)
    ) ENGINE=InnoDB");
    try {
        $pdo->exec('ALTER TABLE `purchase_orders` ADD COLUMN `shipping_cost` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `total_amount`');
    } catch (PDOException $e) {
    }
    try {
        $pdo->exec('ALTER TABLE `purchase_orders` ADD COLUMN `customs_duty` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `shipping_cost`');
    } catch (PDOException $e) {
    }
    try {
        $pdo->exec('ALTER TABLE `purchase_orders` ADD COLUMN `freight_cost` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `customs_duty`');
    } catch (PDOException $e) {
    }
    try {
        $pdo->exec('ALTER TABLE `purchase_orders` ADD COLUMN `landed_cost_total` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `freight_cost`');
    } catch (PDOException $e) {
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS `api_keys` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `platform` ENUM('shopify','woocommerce','amazon') NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `api_key` VARCHAR(255) NOT NULL,
        `api_secret` VARCHAR(255),
        `webhook_url` VARCHAR(500),
        `last_sync` DATETIME NULL,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB");
    $pdo->exec('CREATE TABLE IF NOT EXISTS `sms_settings` (
        `id` TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
        `provider` VARCHAR(50),
        `api_key` VARCHAR(255),
        `sender_id` VARCHAR(20),
        `is_active` TINYINT(1) NOT NULL DEFAULT 0
    ) ENGINE=InnoDB');
    try {
        $pdo->exec('ALTER TABLE `shifts` ADD COLUMN `cash_sales` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `opening_float`');
    } catch (PDOException $e) {
    }
    try {
        $pdo->exec('ALTER TABLE `shifts` ADD COLUMN `card_sales` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `cash_sales`');
    } catch (PDOException $e) {
    }
    $pdo->exec('CREATE TABLE IF NOT EXISTS `purchase_return_items` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `return_id` INT UNSIGNED NOT NULL,
        `product_id` INT UNSIGNED NOT NULL,
        `quantity` INT NOT NULL,
        `unit_price` DECIMAL(15,2) NOT NULL,
        FOREIGN KEY (`return_id`) REFERENCES `purchase_returns`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB');
    $pdo->exec("CREATE TABLE IF NOT EXISTS `finance_categories` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL UNIQUE,
        `type` ENUM('income','expense') NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `supplier_ledgers` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `supplier_id` INT UNSIGNED NOT NULL,
        `transaction_date` DATE NOT NULL,
        `reference_type` ENUM('opening','po','payment','return','credit_note') NOT NULL,
        `reference_id` INT UNSIGNED NULL,
        `debit` DECIMAL(15,2) NOT NULL DEFAULT 0,
        `credit` DECIMAL(15,2) NOT NULL DEFAULT 0,
        `balance` DECIMAL(15,2) NOT NULL DEFAULT 0,
        `notes` VARCHAR(255),
        FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE CASCADE,
        INDEX (`supplier_id`,`transaction_date`)
    ) ENGINE=InnoDB");
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}

function initializeDatabase(): void
{
    $pdo = getPDO();
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->exec('CREATE TABLE IF NOT EXISTS `settings` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `setting_key` VARCHAR(100) NOT NULL UNIQUE,
        `setting_value` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB');
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password_hash` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(150) NOT NULL UNIQUE,
        `role` ENUM('admin','manager','staff') NOT NULL DEFAULT 'staff',
        `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
        `last_login` DATETIME NULL,
        `login_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
        `locked_until` DATETIME NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `login_log` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL,
        `ip_address` VARCHAR(45) NOT NULL,
        `status` ENUM('success','failed','locked') NOT NULL,
        `user_agent` VARCHAR(255),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_username` (`username`),
        INDEX `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB");
    $pdo->exec('CREATE TABLE IF NOT EXISTS `activity_log` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `action` VARCHAR(100) NOT NULL,
        `module` VARCHAR(50) NOT NULL,
        `description` TEXT,
        `ip_address` VARCHAR(45),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_module` (`module`),
        INDEX `idx_created_at` (`created_at`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB');
    $pdo->exec("CREATE TABLE IF NOT EXISTS `categories` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `parent_id` INT UNSIGNED NULL DEFAULT NULL,
        `description` TEXT,
        `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_parent_id` (`parent_id`),
        FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `suppliers` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `company_name` VARCHAR(150) NOT NULL,
        `contact_person` VARCHAR(100),
        `email` VARCHAR(150),
        `phone` VARCHAR(30),
        `address` TEXT,
        `city` VARCHAR(80),
        `country` VARCHAR(80),
        `tax_id` VARCHAR(50),
        `payment_terms` VARCHAR(100),
        `current_balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
        `notes` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_status` (`status`)
    ) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `customers` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(150) NOT NULL,
        `email` VARCHAR(150),
        `phone` VARCHAR(30),
        `address` TEXT,
        `city` VARCHAR(80),
        `country` VARCHAR(80),
        `customer_type` ENUM('walk-in','regular','wholesale') NOT NULL DEFAULT 'walk-in',
        `credit_limit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `current_balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
        `notes` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_status` (`status`),
        INDEX `idx_customer_type` (`customer_type`)
    ) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `warehouses` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `location` VARCHAR(200),
        `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `products` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `sku` VARCHAR(50) NOT NULL UNIQUE,
        `name` VARCHAR(200) NOT NULL,
        `description` TEXT,
        `category_id` INT UNSIGNED NULL,
        `brand` VARCHAR(100),
        `unit` VARCHAR(20) NOT NULL DEFAULT 'pcs',
        `purchase_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `selling_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `current_stock` INT NOT NULL DEFAULT 0,
        `min_stock_level` INT NOT NULL DEFAULT 5,
        `max_stock_level` INT NOT NULL DEFAULT 1000,
        `warehouse_id` INT UNSIGNED NULL,
        `barcode` VARCHAR(100),
        `image_url` VARCHAR(500),
        `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_sku` (`sku`),
        INDEX `idx_category_id` (`category_id`),
        INDEX `idx_status` (`status`),
        INDEX `idx_barcode` (`barcode`),
        FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
        FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `purchase_orders` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `po_number` VARCHAR(30) NOT NULL UNIQUE,
        `supplier_id` INT UNSIGNED NOT NULL,
        `po_date` DATE NOT NULL,
        `expected_delivery` DATE NULL,
        `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `tax_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        `discount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `payment_status` ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
        `order_status` ENUM('pending','partial','received','cancelled') NOT NULL DEFAULT 'pending',
        `notes` TEXT,
        `created_by` INT UNSIGNED NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_supplier_id` (`supplier_id`),
        INDEX `idx_po_date` (`po_date`),
        INDEX `idx_order_status` (`order_status`),
        FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE RESTRICT,
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
    ) ENGINE=InnoDB");
    $pdo->exec('CREATE TABLE IF NOT EXISTS `purchase_order_items` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `po_id` INT UNSIGNED NOT NULL,
        `product_id` INT UNSIGNED NOT NULL,
        `quantity` INT NOT NULL DEFAULT 1,
        `received_qty` INT NOT NULL DEFAULT 0,
        `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `total_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        INDEX `idx_po_id` (`po_id`),
        INDEX `idx_product_id` (`product_id`),
        FOREIGN KEY (`po_id`) REFERENCES `purchase_orders`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
    ) ENGINE=InnoDB');
    $pdo->exec("CREATE TABLE IF NOT EXISTS `invoices` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `invoice_number` VARCHAR(30) NOT NULL UNIQUE,
        `customer_id` INT UNSIGNED NOT NULL,
        `invoice_date` DATE NOT NULL,
        `due_date` DATE NULL,
        `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `tax_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        `discount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `payment_status` ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
        `payment_method` ENUM('cash','card','bank_transfer','credit') NOT NULL DEFAULT 'cash',
        `notes` TEXT,
        `created_by` INT UNSIGNED NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_customer_id` (`customer_id`),
        INDEX `idx_invoice_date` (`invoice_date`),
        INDEX `idx_payment_status` (`payment_status`),
        FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE RESTRICT,
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
    ) ENGINE=InnoDB");
    $pdo->exec('CREATE TABLE IF NOT EXISTS `invoice_items` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `invoice_id` INT UNSIGNED NOT NULL,
        `product_id` INT UNSIGNED NOT NULL,
        `quantity` INT NOT NULL DEFAULT 1,
        `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `discount_pct` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        `total_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        INDEX `idx_invoice_id` (`invoice_id`),
        INDEX `idx_product_id` (`product_id`),
        FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
    ) ENGINE=InnoDB');
    $pdo->exec("CREATE TABLE IF NOT EXISTS `stock_adjustments` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `reference_no` VARCHAR(30) NOT NULL UNIQUE,
        `product_id` INT UNSIGNED NOT NULL,
        `adjustment_type` ENUM('addition','subtraction','damage','expired','correction','opening_stock') NOT NULL,
        `quantity` INT NOT NULL,
        `before_stock` INT NOT NULL,
        `after_stock` INT NOT NULL,
        `reason` TEXT,
        `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        `requested_by` INT UNSIGNED NOT NULL,
        `approved_by` INT UNSIGNED NULL,
        `approved_at` DATETIME NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_product_id` (`product_id`),
        INDEX `idx_status` (`status`),
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT,
        FOREIGN KEY (`requested_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
        FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `stock_transfers` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `reference_no` VARCHAR(30) NOT NULL UNIQUE,
        `from_warehouse_id` INT UNSIGNED NOT NULL,
        `to_warehouse_id` INT UNSIGNED NOT NULL,
        `transfer_date` DATE NOT NULL,
        `status` ENUM('pending','in_transit','completed','cancelled') NOT NULL DEFAULT 'pending',
        `notes` TEXT,
        `created_by` INT UNSIGNED NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_status` (`status`),
        FOREIGN KEY (`from_warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE RESTRICT,
        FOREIGN KEY (`to_warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE RESTRICT,
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
    ) ENGINE=InnoDB");
    $pdo->exec('CREATE TABLE IF NOT EXISTS `stock_transfer_items` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `transfer_id` INT UNSIGNED NOT NULL,
        `product_id` INT UNSIGNED NOT NULL,
        `quantity` INT NOT NULL DEFAULT 1,
        INDEX `idx_transfer_id` (`transfer_id`),
        FOREIGN KEY (`transfer_id`) REFERENCES `stock_transfers`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
    ) ENGINE=InnoDB');
    $pdo->exec("CREATE TABLE IF NOT EXISTS `notifications` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `type` ENUM('low_stock','overdue_payment','overdue_purchase','system') NOT NULL,
        `title` VARCHAR(200) NOT NULL,
        `message` TEXT NOT NULL,
        `is_read` TINYINT(1) NOT NULL DEFAULT 0,
        `related_id` INT UNSIGNED NULL,
        `related_module` VARCHAR(50) NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_is_read` (`is_read`),
        INDEX `idx_type` (`type`)
    ) ENGINE=InnoDB");
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    $chk = $pdo->query('SELECT COUNT(*) as cnt FROM `users`')->fetch();
    if ($chk['cnt'] == 0) {
        seedDatabase($pdo);
    }
}

function seedDatabase(PDO $pdo): void
{
    $adminHash = password_hash('admin123!', PASSWORD_BCRYPT, ['cost' => 12]);
    $managerHash = password_hash('Manager@2024', PASSWORD_BCRYPT, ['cost' => 12]);
    $staffHash = password_hash('Staff@2024!', PASSWORD_BCRYPT, ['cost' => 12]);
    $pdo->exec("INSERT INTO `users` (`username`,`password_hash`,`full_name`,`email`,`role`,`status`) VALUES
        ('admin','" . $adminHash . "','System Administrator','admin@inventorypro.com','admin','active'),
        ('manager1','" . $managerHash . "','Sarah Johnson','sarah.johnson@inventorypro.com','manager','active'),
        ('staff1','" . $staffHash . "','Michael Chen','michael.chen@inventorypro.com','staff','active'),
        ('staff2','" . $staffHash . "','Aisha Malik','aisha.malik@inventorypro.com','staff','active')
    ");
    $settingsData = [
        ['company_name', 'InventoryPro Solutions'],
        ['company_address', '123 Business Park, Main Boulevard'],
        ['company_phone', '+92-51-1234567'],
        ['company_email', 'info@inventorypro.com'],
        ['company_website', 'https://inventorypro.com'],
        ['company_tax_number', 'TAX-2024-001'],
        ['company_logo_url', ''],
        ['invoice_prefix', 'INV'],
        ['invoice_start_number', '1001'],
        ['po_prefix', 'PO'],
        ['po_start_number', '1001'],
        ['default_tax_percent', '17'],
        ['default_payment_terms', 'Net 30'],
        ['currency_symbol', 'Rs.'],
        ['currency_code', 'PKR'],
        ['date_format', 'Y-m-d'],
        ['timezone', 'Asia/Karachi'],
        ['low_stock_threshold', '10'],
        ['items_per_page', '20'],
    ];
    $stmtS = $pdo->prepare('INSERT IGNORE INTO `settings` (`setting_key`,`setting_value`) VALUES (?,?)');
    foreach ($settingsData as $s) {
        $stmtS->execute($s);
    }
    $pdo->exec("INSERT INTO `warehouses` (`name`,`location`,`status`) VALUES
        ('Main Warehouse','Ground Floor, Building A','active'),
        ('Secondary Warehouse','Second Floor, Building B','active')
    ");
    $pdo->exec("INSERT INTO `categories` (`name`,`parent_id`,`description`,`status`) VALUES
        ('Electronics',NULL,'Electronic devices and components','active'),
        ('Furniture',NULL,'Office and home furniture','active'),
        ('Stationery',NULL,'Office supplies and stationery','active'),
        ('Food & Beverage',NULL,'Food items and beverages','active'),
        ('Laptops & Computers',1,'Laptops, desktops, accessories','active'),
        ('Mobile Phones',1,'Smartphones and accessories','active'),
        ('Office Chairs',2,'Ergonomic and standard office chairs','active'),
        ('Pens & Pencils',3,'Writing instruments','active')
    ");
    $pdo->exec("INSERT INTO `suppliers` (`company_name`,`contact_person`,`email`,`phone`,`address`,`city`,`country`,`tax_id`,`payment_terms`,`current_balance`,`status`) VALUES
        ('TechSource Pakistan','Bilal Ahmed','bilal@techsource.pk','+92-321-5551234','Plot 45, Industrial Area','Lahore','Pakistan','PKR-TAX-001','Net 30',0.00,'active'),
        ('Global Office Supplies','Fatima Khan','fatima@globaloffice.com','+92-300-6667890','Office Tower, Floor 3','Karachi','Pakistan','PKR-TAX-002','Net 15',0.00,'active'),
        ('Fresh Foods Distributors','Usman Ali','usman@freshfoods.pk','+92-333-9990123','Market Road, Sector G','Islamabad','Pakistan','PKR-TAX-003','Cash on Delivery',0.00,'active'),
        ('ProFurniture Ltd','Zara Siddiqui','zara@profurniture.pk','+92-42-7774567','Furniture Market, GT Road','Rawalpindi','Pakistan','PKR-TAX-004','Net 45',0.00,'active')
    ");
    $pdo->exec("INSERT INTO `customers` (`name`,`email`,`phone`,`address`,`city`,`country`,`customer_type`,`credit_limit`,`current_balance`,`status`) VALUES
        ('Hassan Enterprises','contact@hassanent.com','+92-51-4441234','Blue Area, Office 12','Islamabad','Pakistan','wholesale',500000.00,0.00,'active'),
        ('Raza & Sons Trading','raza@razasons.com','+92-300-2223456','Saddar Bazaar, Shop 45','Rawalpindi','Pakistan','regular',100000.00,0.00,'active'),
        ('Nadia Boutique','nadia@boutique.pk','+92-321-8889012','F-7 Markaz, Shop 8','Islamabad','Pakistan','regular',50000.00,0.00,'active'),
        ('Walk-in Customer','walkin@inventorypro.com','+92-000-0000000','N/A','N/A','Pakistan','walk-in',0.00,0.00,'active')
    ");
    $pdo->exec("INSERT INTO `products` (`sku`,`name`,`description`,`category_id`,`brand`,`unit`,`purchase_price`,`selling_price`,`current_stock`,`min_stock_level`,`max_stock_level`,`warehouse_id`,`barcode`,`status`) VALUES
        ('SKU-00001','Dell Inspiron 15 Laptop','15.6 inch FHD, Intel Core i5, 8GB RAM, 512GB SSD',5,'Dell','pcs',85000.00,99999.00,15,3,50,1,'8001234567890','active'),
        ('SKU-00002','Samsung Galaxy A54','6.4 inch Super AMOLED, 128GB, 8GB RAM',6,'Samsung','pcs',45000.00,54999.00,8,5,100,1,'8009876543210','active'),
        ('SKU-00003','Ergonomic Office Chair','Mesh back, adjustable height, lumbar support',7,'Herman Miller','pcs',18000.00,24999.00,25,5,80,2,'8005555555555','active'),
        ('SKU-00004','Ballpoint Pens Box (50 pcs)','Blue ink, medium point, smooth writing',8,'Pilot','box',350.00,550.00,120,20,500,2,'8007777777777','active')
    ");
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
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf()) . '">';
}

function formatCurrency(float $amount): string
{
    $sym = getSetting('currency_symbol') ?: 'Rs.';
    return $sym . ' ' . number_format($amount, 2);
}

function formatDate(string $date): string
{
    if (empty($date) || $date === '0000-00-00')
        return '-';
    $fmt = getSetting('date_format') ?: 'Y-m-d';
    return date($fmt, strtotime($date));
}

function getSetting(string $key): string
{
    static $cache = [];
    if (isset($cache[$key]))
        return $cache[$key];
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT `setting_value` FROM `settings` WHERE `setting_key` = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        $cache[$key] = $row ? (string) $row['setting_value'] : '';
        return $cache[$key];
    } catch (PDOException $e) {
        return '';
    }
}

function setSetting(string $key, string $value): void
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO `settings` (`setting_key`,`setting_value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `setting_value`=?');
    $stmt->execute([$key, $value, $value]);
}

function generateSku(): string
{
    $pdo = getPDO();
    $row = $pdo->query("SELECT MAX(CAST(SUBSTRING(`sku`,5) AS UNSIGNED)) as mx FROM `products` WHERE `sku` LIKE 'SKU-%'")->fetch();
    $next = ($row && $row['mx']) ? (int) $row['mx'] + 1 : 1;
    return 'SKU-' . str_pad($next, 5, '0', STR_PAD_LEFT);
}

function generatePoNumber(): string
{
    $pdo = getPDO();
    $prefix = getSetting('po_prefix') ?: 'PO';
    $date = date('Ymd');
    $row = $pdo->query("SELECT COUNT(*) as cnt FROM `purchase_orders` WHERE `po_number` LIKE '" . $prefix . '-' . $date . "-%'")->fetch();
    $seq = str_pad((int) $row['cnt'] + 1, 3, '0', STR_PAD_LEFT);
    return $prefix . '-' . $date . '-' . $seq;
}

function generateInvoiceNumber(): string
{
    $pdo = getPDO();
    $prefix = getSetting('invoice_prefix') ?: 'INV';
    $date = date('Ymd');
    $row = $pdo->query("SELECT COUNT(*) as cnt FROM `invoices` WHERE `invoice_number` LIKE '" . $prefix . '-' . $date . "-%'")->fetch();
    $seq = str_pad((int) $row['cnt'] + 1, 3, '0', STR_PAD_LEFT);
    return $prefix . '-' . $date . '-' . $seq;
}

function generateRefNumber(string $prefix): string
{
    return $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}

function updateCustomerLedger(int $customerId, string $type, ?int $refId, float $debit, float $credit, string $notes = ''): void
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT balance FROM customer_ledgers WHERE customer_id=? ORDER BY transaction_date DESC, id DESC LIMIT 1');
    $stmt->execute([$customerId]);
    $lastBalance = (float) ($stmt->fetchColumn() ?: 0);
    $newBalance = $lastBalance + $debit - $credit;
    $stmt = $pdo->prepare('INSERT INTO customer_ledgers (customer_id, transaction_date, reference_type, reference_id, debit, credit, balance, notes) VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$customerId, $type, $refId, $debit, $credit, $newBalance, $notes]);
    $pdo->prepare('UPDATE customers SET current_balance=? WHERE id=?')->execute([$newBalance, $customerId]);
}

function updateSupplierLedger(int $supplierId, string $type, ?int $refId, float $debit, float $credit, string $notes = ''): void
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT balance FROM supplier_ledgers WHERE supplier_id=? ORDER BY transaction_date DESC, id DESC LIMIT 1');
    $stmt->execute([$supplierId]);
    $lastBalance = (float) ($stmt->fetchColumn() ?: 0);
    $newBalance = $lastBalance + $debit - $credit;
    $stmt = $pdo->prepare('INSERT INTO supplier_ledgers (supplier_id, transaction_date, reference_type, reference_id, debit, credit, balance, notes) VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$supplierId, $type, $refId, $debit, $credit, $newBalance, $notes]);
    $pdo->prepare('UPDATE suppliers SET current_balance=? WHERE id=?')->execute([$newBalance, $supplierId]);
}

function logActivity(int $userId, string $action, string $module, string $description): void
{
    try {
        $pdo = getPDO();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt = $pdo->prepare('INSERT INTO `activity_log` (`user_id`,`action`,`module`,`description`,`ip_address`) VALUES (?,?,?,?,?)');
        $stmt->execute([$userId, $action, $module, $description, $ip]);
    } catch (PDOException $e) {
    }
}

function paginate(int $total, int $perPage, int $currentPage): array
{
    $totalPages = max(1, (int) ceil($total / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    return ['total' => $total, 'per_page' => $perPage, 'current_page' => $currentPage, 'total_pages' => $totalPages, 'offset' => $offset];
}

function paginationHtml(array $p, string $baseUrl): string
{
    if ($p['total_pages'] <= 1)
        return '';
    $html = '<div class="pagination">';
    $q = parse_url($baseUrl, PHP_URL_QUERY);
    parse_str($q ?: '', $params);
    unset($params['pg']);
    $base = '?' . http_build_query($params);
    if ($p['current_page'] > 1) {
        $html .= '<a href="' . $base . '&pg=' . ($p['current_page'] - 1) . '" class="page-btn">&#8592; Prev</a>';
    }
    $start = max(1, $p['current_page'] - 2);
    $end = min($p['total_pages'], $p['current_page'] + 2);
    for ($i = $start; $i <= $end; $i++) {
        $active = ($i === $p['current_page']) ? ' active' : '';
        $html .= '<a href="' . $base . '&pg=' . $i . '" class="page-btn' . $active . '">' . $i . '</a>';
    }
    if ($p['current_page'] < $p['total_pages']) {
        $html .= '<a href="' . $base . '&pg=' . ($p['current_page'] + 1) . '" class="page-btn">Next &#8594;</a>';
    }
    $html .= '<span class="page-info">Page ' . $p['current_page'] . ' of ' . $p['total_pages'] . ' (' . $p['total'] . ' records)</span>';
    $html .= '</div>';
    return $html;
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ?page=login');
        exit;
    }
    $now = time();
    if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        session_start();
        header('Location: ?page=login&reason=timeout');
        exit;
    }
    if (isset($_SESSION['session_start']) && ($now - $_SESSION['session_start']) > SESSION_ABSOLUTE) {
        session_unset();
        session_destroy();
        session_start();
        header('Location: ?page=login&reason=expired');
        exit;
    }
    $_SESSION['last_activity'] = $now;
}

function requireRole(array $roles): void
{
    requireLogin();
    if (!in_array($_SESSION['user_role'], $roles, true)) {
        renderError403();
        exit;
    }
}

function canAccess(array $roles): bool
{
    return isLoggedIn() && in_array($_SESSION['user_role'], $roles, true);
}

function generateMathCaptcha(): array
{
    $a = rand(2, 12);
    $b = rand(1, 9);
    $ops = ['+', '-', '*'];
    $op = $ops[array_rand($ops)];
    if ($op === '-' && $b > $a) {
        [$a, $b] = [$b, $a];
    }
    $answer = match ($op) {
        '+' => $a + $b,
        '-' => $a - $b,
        '*' => $a * $b
    };
    return ['question' => "$a $op $b", 'answer' => $answer];
}

function handleLogin(): ?string
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST')
        return null;
    if (!verifyCsrf())
        return 'Security token mismatch. Please try again.';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $captchaInput = trim($_POST['captcha'] ?? '');
    $captchaAnswer = (int) ($_SESSION['captcha_answer'] ?? -1);
    if ((int) $captchaInput !== $captchaAnswer) {
        unset($_SESSION['captcha_answer']);
        return 'Incorrect CAPTCHA. Please try again.';
    }
    unset($_SESSION['captcha_answer']);
    if (empty($username) || empty($password))
        return 'Username and password are required.';
    $pdo = getPDO();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $stmt = $pdo->prepare('SELECT * FROM `users` WHERE `username` = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if (!$user || $user['status'] !== 'active') {
        $logStmt = $pdo->prepare("INSERT INTO `login_log` (`username`,`ip_address`,`status`,`user_agent`) VALUES (?,?,'failed',?)");
        $logStmt->execute([$username, $ip, $ua]);
        return 'Invalid username or password.';
    }
    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
        $logStmt = $pdo->prepare("INSERT INTO `login_log` (`username`,`ip_address`,`status`,`user_agent`) VALUES (?,?,'locked',?)");
        $logStmt->execute([$username, $ip, $ua]);
        return 'Account is temporarily locked. Try again after ' . date('H:i', strtotime($user['locked_until'])) . '.';
    }
    if (!password_verify($password, $user['password_hash'])) {
        $attempts = $user['login_attempts'] + 1;
        $lockUntil = null;
        if ($attempts >= 5) {
            $lockUntil = date('Y-m-d H:i:s', time() + 900);
        }
        $pdo->prepare('UPDATE `users` SET `login_attempts`=?, `locked_until`=? WHERE `id`=?')->execute([$attempts, $lockUntil, $user['id']]);
        $logStmt = $pdo->prepare("INSERT INTO `login_log` (`username`,`ip_address`,`status`,`user_agent`) VALUES (?,?,'failed',?)");
        $logStmt->execute([$username, $ip, $ua]);
        $rem = 5 - $attempts;
        if ($rem <= 0)
            return 'Account locked for 15 minutes due to too many failed attempts.';
        return 'Invalid credentials. ' . $rem . ' attempt(s) remaining.';
    }
    $pdo->prepare('UPDATE `users` SET `login_attempts`=0, `locked_until`=NULL, `last_login`=NOW() WHERE `id`=?')->execute([$user['id']]);
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['last_activity'] = time();
    $_SESSION['session_start'] = time();
    $logStmt = $pdo->prepare("INSERT INTO `login_log` (`username`,`ip_address`,`status`,`user_agent`) VALUES (?,?,'success',?)");
    $logStmt->execute([$username, $ip, $ua]);
    logActivity($user['id'], 'LOGIN', 'auth', 'User logged in from ' . $ip);
    header('Location: ?page=dashboard');
    exit;
}

function handleLogout(): void
{
    if (isLoggedIn()) {
        logActivity($_SESSION['user_id'], 'LOGOUT', 'auth', 'User logged out');
    }
    session_unset();
    session_destroy();
    header('Location: ?page=login&reason=logout');
    exit;
}

function getDashboardStats(): array
{
    $pdo = getPDO();
    $today = date('Y-m-d');
    $thisMonthStart = date('Y-m-01');
    $stats = [];
    $stats['total_products'] = (int) $pdo->query("SELECT COUNT(*) FROM `products` WHERE `status`='active'")->fetchColumn();
    $stats['low_stock'] = (int) $pdo->query("SELECT COUNT(*) FROM `products` WHERE `current_stock` > 0 AND `current_stock` <= `min_stock_level` AND `status`='active'")->fetchColumn();
    $stats['out_of_stock'] = (int) $pdo->query("SELECT COUNT(*) FROM `products` WHERE `current_stock` <= 0 AND `status`='active'")->fetchColumn();
    $stats['categories_count'] = (int) $pdo->query("SELECT COUNT(*) FROM `categories` WHERE `status`='active'")->fetchColumn();
    $stats['inventory_value'] = (float) $pdo->query("SELECT COALESCE(SUM(`current_stock` * `purchase_price`),0) FROM `products` WHERE `status`='active'")->fetchColumn();
    $stats['today_sales'] = (float) $pdo->query("SELECT COALESCE(SUM(`total_amount`),0) FROM `invoices` WHERE DATE(`invoice_date`)='$today'")->fetchColumn();
    $stats['this_month_sales'] = (float) $pdo->query("SELECT COALESCE(SUM(`total_amount`),0) FROM `invoices` WHERE `invoice_date` >= '$thisMonthStart'")->fetchColumn();
    $stats['total_revenue'] = (float) $pdo->query('SELECT COALESCE(SUM(`total_amount`),0) FROM `invoices`')->fetchColumn();
    $stats['unpaid_invoices_amt'] = (float) $pdo->query("SELECT COALESCE(SUM(`total_amount`),0) FROM `invoices` WHERE `payment_status` != 'paid'")->fetchColumn();
    $stats['unpaid_invoices'] = (int) $pdo->query("SELECT COUNT(*) FROM `invoices` WHERE `payment_status`='unpaid'")->fetchColumn();
    $stats['today_purchases'] = (float) $pdo->query("SELECT COALESCE(SUM(`total_amount`),0) FROM `purchase_orders` WHERE DATE(`po_date`)='$today'")->fetchColumn();
    $stats['this_month_purchases'] = (float) $pdo->query("SELECT COALESCE(SUM(`total_amount`),0) FROM `purchase_orders` WHERE `po_date` >= '$thisMonthStart'")->fetchColumn();
    $stats['unpaid_pos_amt'] = (float) $pdo->query("SELECT COALESCE(SUM(`total_amount`),0) FROM `purchase_orders` WHERE `payment_status` != 'paid'")->fetchColumn();
    $stats['pending_orders'] = (int) $pdo->query("SELECT COUNT(*) FROM `purchase_orders` WHERE `order_status`='pending'")->fetchColumn();
    $stats['total_customers'] = (int) $pdo->query("SELECT COUNT(*) FROM `customers` WHERE `status`='active'")->fetchColumn();
    $stats['total_suppliers'] = (int) $pdo->query("SELECT COUNT(*) FROM `suppliers` WHERE `status`='active'")->fetchColumn();
    $stats['total_users'] = (int) $pdo->query("SELECT COUNT(*) FROM `users` WHERE `status`='active'")->fetchColumn();
    $stats['total_warehouses'] = (int) $pdo->query("SELECT COUNT(*) FROM `warehouses` WHERE `status`='active'")->fetchColumn();
    $stats['total_sales_returns'] = (float) $pdo->query('SELECT COALESCE(SUM(`total_amount`),0) FROM `sales_returns`')->fetchColumn();
    $stats['total_purchase_returns'] = (float) $pdo->query('SELECT COALESCE(SUM(`total_amount`),0) FROM `purchase_returns`')->fetchColumn();
    $salesLast7 = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $amt = (float) $pdo->query("SELECT COALESCE(SUM(`total_amount`),0) FROM `invoices` WHERE DATE(`invoice_date`)='$d'")->fetchColumn();
        $cost = (float) $pdo->query("SELECT COALESCE(SUM(ii.quantity * p.purchase_price),0) FROM invoice_items ii JOIN invoices i ON i.id=ii.invoice_id JOIN products p ON p.id=ii.product_id WHERE DATE(i.invoice_date)='$d'")->fetchColumn();
        $salesLast7[] = ['date' => date('D', strtotime($d)), 'amount' => $amt, 'cost' => $cost];
    }
    $stats['sales_last7'] = $salesLast7;
    $stats['customers_by_type'] = $pdo->query('SELECT customer_type, COUNT(*) as cnt FROM customers GROUP BY customer_type')->fetchAll();
    $stats['po_status_dist'] = $pdo->query('SELECT order_status, COUNT(*) as cnt FROM purchase_orders GROUP BY order_status')->fetchAll();
    $stats['stock_by_category'] = $pdo->query('SELECT c.name, COALESCE(SUM(p.current_stock * p.purchase_price),0) as val FROM products p JOIN categories c ON c.id=p.category_id GROUP BY c.id, c.name ORDER BY val DESC LIMIT 6')->fetchAll();
    $stats['recent_invoices'] = $pdo->query('SELECT i.`id`, i.`invoice_number`, c.`name` as customer_name, i.`total_amount`, i.`payment_status`, i.`invoice_date` FROM `invoices` i JOIN `customers` c ON c.`id`=i.`customer_id` ORDER BY i.`created_at` DESC LIMIT 5')->fetchAll();
    $stats['recent_purchases'] = $pdo->query('SELECT po.`id`, po.`po_number`, s.`company_name` as supplier_name, po.`total_amount`, po.`order_status`, po.`po_date` FROM `purchase_orders` po JOIN `suppliers` s ON s.`id`=po.`supplier_id` ORDER BY po.`created_at` DESC LIMIT 5')->fetchAll();
    $stats['low_stock_products'] = $pdo->query("SELECT `sku`,`name`,`current_stock`,`min_stock_level` FROM `products` WHERE `current_stock` <= `min_stock_level` AND `status`='active' ORDER BY `current_stock` ASC LIMIT 8")->fetchAll();
    $stats['top_products'] = $pdo->query('SELECT p.`name`, COALESCE(SUM(ii.`quantity`),0) as qty_sold FROM `products` p LEFT JOIN `invoice_items` ii ON ii.`product_id`=p.`id` GROUP BY p.`id`,p.`name` ORDER BY qty_sold DESC LIMIT 5')->fetchAll();
    return $stats;
}

function getProducts(array $filters = [], int $page = 1): array
{
    $pdo = getPDO();
    $perPage = (int) (getSetting('items_per_page') ?: 20);
    $where = ['1=1'];
    $params = [];
    if (!empty($filters['search'])) {
        $where[] = '(p.`name` LIKE ? OR p.`sku` LIKE ? OR p.`brand` LIKE ? OR p.`barcode` LIKE ?)';
        $s = '%' . $filters['search'] . '%';
        array_push($params, $s, $s, $s, $s);
    }
    if (!empty($filters['category_id'])) {
        $where[] = 'p.`category_id` = ?';
        $params[] = (int) $filters['category_id'];
    }
    if (!empty($filters['status'])) {
        $where[] = 'p.`status` = ?';
        $params[] = $filters['status'];
    }
    if (!empty($filters['stock_level'])) {
        if ($filters['stock_level'] === 'low')
            $where[] = 'p.`current_stock` <= p.`min_stock_level`';
        elseif ($filters['stock_level'] === 'out')
            $where[] = 'p.`current_stock` = 0';
        elseif ($filters['stock_level'] === 'ok')
            $where[] = 'p.`current_stock` > p.`min_stock_level`';
    }
    $whereStr = implode(' AND ', $where);
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM `products` p WHERE $whereStr");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    $pag = paginate($total, $perPage, $page);
    $orderBy = 'p.`name` ASC';
    if (!empty($filters['sort'])) {
        $allowed = ['name', 'sku', 'current_stock', 'selling_price', 'purchase_price', 'status'];
        $dir = (!empty($filters['dir']) && strtoupper($filters['dir']) === 'DESC') ? 'DESC' : 'ASC';
        if (in_array($filters['sort'], $allowed, true))
            $orderBy = 'p.`' . $filters['sort'] . "` $dir";
    }
    $stmt = $pdo->prepare("SELECT p.*, c.`name` as category_name, w.`name` as warehouse_name FROM `products` p LEFT JOIN `categories` c ON c.`id`=p.`category_id` LEFT JOIN `warehouses` w ON w.`id`=p.`warehouse_id` WHERE $whereStr ORDER BY $orderBy LIMIT ? OFFSET ?");
    $execParams = array_merge($params, [$pag['per_page'], $pag['offset']]);
    $stmt->execute($execParams);
    return ['products' => $stmt->fetchAll(), 'pagination' => $pag];
}

function getProduct(int $id): ?array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT p.*, c.`name` as category_name, w.`name` as warehouse_name FROM `products` p LEFT JOIN `categories` c ON c.`id`=p.`category_id` LEFT JOIN `warehouses` w ON w.`id`=p.`warehouse_id` WHERE p.`id` = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function saveProduct(array $data, ?int $id = null): array
{
    $pdo = getPDO();
    $errors = [];
    $name = trim($data['name'] ?? '');
    $sku = trim($data['sku'] ?? '');
    $purchasePrice = (float) ($data['purchase_price'] ?? 0);
    $sellingPrice = (float) ($data['selling_price'] ?? 0);
    $currentStock = (int) ($data['current_stock'] ?? 0);
    $minStock = (int) ($data['min_stock_level'] ?? 5);
    $maxStock = (int) ($data['max_stock_level'] ?? 1000);
    $categoryId = !empty($data['category_id']) ? (int) $data['category_id'] : null;
    $warehouseId = !empty($data['warehouse_id']) ? (int) $data['warehouse_id'] : null;
    if (empty($name))
        $errors[] = 'Product name is required.';
    if (empty($sku))
        $errors[] = 'SKU is required.';
    if ($purchasePrice < 0)
        $errors[] = 'Purchase price cannot be negative.';
    if ($sellingPrice < 0)
        $errors[] = 'Selling price cannot be negative.';
    if (!empty($errors))
        return ['success' => false, 'errors' => $errors];
    $skuChk = $pdo->prepare('SELECT `id` FROM `products` WHERE `sku`=? AND `id`!=?');
    $skuChk->execute([$sku, $id ?? 0]);
    if ($skuChk->fetch())
        $errors[] = 'SKU already exists.';
    if (!empty($errors))
        return ['success' => false, 'errors' => $errors];
    $params = [
        'name' => $name,
        'sku' => $sku,
        'description' => trim($data['description'] ?? ''),
        'category_id' => $categoryId,
        'brand' => trim($data['brand'] ?? ''),
        'unit' => trim($data['unit'] ?? 'pcs'),
        'purchase_price' => $purchasePrice,
        'selling_price' => $sellingPrice,
        'current_stock' => $currentStock,
        'min_stock_level' => $minStock,
        'max_stock_level' => $maxStock,
        'warehouse_id' => $warehouseId,
        'barcode' => trim($data['barcode'] ?? ''),
        'image_url' => trim($data['image_url'] ?? ''),
        'status' => $data['status'] ?? 'active',
    ];
    if ($id) {
        $sets = implode(', ', array_map(fn($k) => "`$k`=:$k", array_keys($params)));
        $params['id'] = $id;
        $pdo->prepare("UPDATE `products` SET $sets WHERE `id`=:id")->execute($params);
        logActivity($_SESSION['user_id'], 'UPDATE', 'products', "Updated product: $name (SKU: $sku)");
        return ['success' => true, 'id' => $id];
    } else {
        $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($params)));
        $vals = implode(', ', array_map(fn($k) => ":$k", array_keys($params)));
        $pdo->prepare("INSERT INTO `products` ($cols) VALUES ($vals)")->execute($params);
        $newId = (int) $pdo->lastInsertId();
        logActivity($_SESSION['user_id'], 'CREATE', 'products', "Created product: $name (SKU: $sku)");
        return ['success' => true, 'id' => $newId];
    }
}

function deleteProduct(int $id): array
{
    $pdo = getPDO();
    $chk = $pdo->prepare('SELECT COUNT(*) FROM `invoice_items` WHERE `product_id`=?');
    $chk->execute([$id]);
    if ((int) $chk->fetchColumn() > 0)
        return ['success' => false, 'error' => 'Cannot delete: product has sales history.'];
    $chk2 = $pdo->prepare('SELECT COUNT(*) FROM `purchase_order_items` WHERE `product_id`=?');
    $chk2->execute([$id]);
    if ((int) $chk2->fetchColumn() > 0)
        return ['success' => false, 'error' => 'Cannot delete: product has purchase history.'];
    $prod = getProduct($id);
    $pdo->prepare('DELETE FROM `products` WHERE `id`=?')->execute([$id]);
    if ($prod)
        logActivity($_SESSION['user_id'], 'DELETE', 'products', "Deleted product: {$prod['name']} (SKU: {$prod['sku']})");
    return ['success' => true];
}

function getCategories(bool $activeOnly = false): array
{
    $pdo = getPDO();
    $sql = 'SELECT c.*, p.`name` as parent_name, (SELECT COUNT(*) FROM `products` pr WHERE pr.`category_id`=c.`id`) as product_count FROM `categories` c LEFT JOIN `categories` p ON p.`id`=c.`parent_id`';
    if ($activeOnly)
        $sql .= " WHERE c.`status`='active'";
    $sql .= ' ORDER BY COALESCE(c.`parent_id`,c.`id`), c.`parent_id` IS NULL DESC, c.`name`';
    return $pdo->query($sql)->fetchAll();
}

function saveCategory(array $data, ?int $id = null): array
{
    $pdo = getPDO();
    $name = trim($data['name'] ?? '');
    if (empty($name))
        return ['success' => false, 'error' => 'Category name is required.'];
    $parentId = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;
    if ($parentId && $parentId === $id)
        return ['success' => false, 'error' => 'Category cannot be its own parent.'];
    $params = ['name' => $name, 'parent_id' => $parentId, 'description' => trim($data['description'] ?? ''), 'status' => $data['status'] ?? 'active'];
    if ($id) {
        $pdo->prepare('UPDATE `categories` SET `name`=:name, `parent_id`=:parent_id, `description`=:description, `status`=:status WHERE `id`=:id')->execute(array_merge($params, ['id' => $id]));
        logActivity($_SESSION['user_id'], 'UPDATE', 'categories', "Updated category: $name");
    } else {
        $pdo->prepare('INSERT INTO `categories` (`name`,`parent_id`,`description`,`status`) VALUES (:name,:parent_id,:description,:status)')->execute($params);
        logActivity($_SESSION['user_id'], 'CREATE', 'categories', "Created category: $name");
    }
    return ['success' => true];
}

function getSuppliers(array $filters = [], int $page = 1): array
{
    $pdo = getPDO();
    $perPage = (int) (getSetting('items_per_page') ?: 20);
    $where = ['1=1'];
    $params = [];
    if (!empty($filters['search'])) {
        $where[] = '(`company_name` LIKE ? OR `contact_person` LIKE ? OR `email` LIKE ? OR `phone` LIKE ?)';
        $s = '%' . $filters['search'] . '%';
        array_push($params, $s, $s, $s, $s);
    }
    if (!empty($filters['status'])) {
        $where[] = '`status`=?';
        $params[] = $filters['status'];
    }
    $whereStr = implode(' AND ', $where);
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM `suppliers` WHERE $whereStr");
    $cnt->execute($params);
    $total = (int) $cnt->fetchColumn();
    $pag = paginate($total, $perPage, $page);
    $stmt = $pdo->prepare("SELECT * FROM `suppliers` WHERE $whereStr ORDER BY `company_name` ASC LIMIT ? OFFSET ?");
    $stmt->execute(array_merge($params, [$pag['per_page'], $pag['offset']]));
    return ['suppliers' => $stmt->fetchAll(), 'pagination' => $pag];
}

function getSupplier(int $id): ?array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM `suppliers` WHERE `id`=?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function saveSupplier(array $data, ?int $id = null): array
{
    $pdo = getPDO();
    $name = trim($data['company_name'] ?? '');
    if (empty($name))
        return ['success' => false, 'error' => 'Company name is required.'];
    $params = [
        'company_name' => $name,
        'contact_person' => trim($data['contact_person'] ?? ''),
        'email' => trim($data['email'] ?? ''),
        'phone' => trim($data['phone'] ?? ''),
        'address' => trim($data['address'] ?? ''),
        'city' => trim($data['city'] ?? ''),
        'country' => trim($data['country'] ?? ''),
        'tax_id' => trim($data['tax_id'] ?? ''),
        'payment_terms' => trim($data['payment_terms'] ?? ''),
        'status' => $data['status'] ?? 'active',
        'notes' => trim($data['notes'] ?? ''),
    ];
    if ($id) {
        $sets = implode(', ', array_map(fn($k) => "`$k`=:$k", array_keys($params)));
        $params['id'] = $id;
        $pdo->prepare("UPDATE `suppliers` SET $sets WHERE `id`=:id")->execute($params);
        logActivity($_SESSION['user_id'], 'UPDATE', 'suppliers', "Updated supplier: $name");
    } else {
        $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($params)));
        $vals = implode(', ', array_map(fn($k) => ":$k", array_keys($params)));
        $pdo->prepare("INSERT INTO `suppliers` ($cols) VALUES ($vals)")->execute($params);
        logActivity($_SESSION['user_id'], 'CREATE', 'suppliers', "Created supplier: $name");
    }
    return ['success' => true];
}

function deleteSupplier(int $id): array
{
    $pdo = getPDO();
    $chk = $pdo->prepare('SELECT COUNT(*) FROM `purchase_orders` WHERE `supplier_id`=?');
    $chk->execute([$id]);
    if ((int) $chk->fetchColumn() > 0)
        return ['success' => false, 'error' => 'Cannot delete: supplier has purchase orders.'];
    $sup = getSupplier($id);
    $pdo->prepare('DELETE FROM `suppliers` WHERE `id`=?')->execute([$id]);
    if ($sup)
        logActivity($_SESSION['user_id'], 'DELETE', 'suppliers', "Deleted supplier: {$sup['company_name']}");
    return ['success' => true];
}

function getCustomers(array $filters = [], int $page = 1): array
{
    $pdo = getPDO();
    $perPage = (int) (getSetting('items_per_page') ?: 20);
    $where = ['1=1'];
    $params = [];
    if (!empty($filters['search'])) {
        $where[] = '(`name` LIKE ? OR `email` LIKE ? OR `phone` LIKE ?)';
        $s = '%' . $filters['search'] . '%';
        array_push($params, $s, $s, $s);
    }
    if (!empty($filters['status'])) {
        $where[] = '`status`=?';
        $params[] = $filters['status'];
    }
    if (!empty($filters['type'])) {
        $where[] = '`customer_type`=?';
        $params[] = $filters['type'];
    }
    $whereStr = implode(' AND ', $where);
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM `customers` WHERE $whereStr");
    $cnt->execute($params);
    $total = (int) $cnt->fetchColumn();
    $pag = paginate($total, $perPage, $page);
    $stmt = $pdo->prepare("SELECT * FROM `customers` WHERE $whereStr ORDER BY `name` ASC LIMIT ? OFFSET ?");
    $stmt->execute(array_merge($params, [$pag['per_page'], $pag['offset']]));
    return ['customers' => $stmt->fetchAll(), 'pagination' => $pag];
}

function getCustomer(int $id): ?array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM `customers` WHERE `id`=?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function saveCustomer(array $data, ?int $id = null): array
{
    $pdo = getPDO();
    $name = trim($data['name'] ?? '');
    if (empty($name))
        return ['success' => false, 'error' => 'Customer name is required.'];
    $params = [
        'name' => $name,
        'email' => trim($data['email'] ?? ''),
        'phone' => trim($data['phone'] ?? ''),
        'address' => trim($data['address'] ?? ''),
        'city' => trim($data['city'] ?? ''),
        'country' => trim($data['country'] ?? ''),
        'customer_type' => $data['customer_type'] ?? 'walk-in',
        'credit_limit' => (float) ($data['credit_limit'] ?? 0),
        'status' => $data['status'] ?? 'active',
        'notes' => trim($data['notes'] ?? ''),
    ];
    if ($id) {
        $sets = implode(', ', array_map(fn($k) => "`$k`=:$k", array_keys($params)));
        $params['id'] = $id;
        $pdo->prepare("UPDATE `customers` SET $sets WHERE `id`=:id")->execute($params);
        logActivity($_SESSION['user_id'], 'UPDATE', 'customers', "Updated customer: $name");
    } else {
        $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($params)));
        $vals = implode(', ', array_map(fn($k) => ":$k", array_keys($params)));
        $pdo->prepare("INSERT INTO `customers` ($cols) VALUES ($vals)")->execute($params);
        logActivity($_SESSION['user_id'], 'CREATE', 'customers', "Created customer: $name");
    }
    return ['success' => true];
}

function deleteCustomer(int $id): array
{
    $pdo = getPDO();
    $chk = $pdo->prepare('SELECT COUNT(*) FROM `invoices` WHERE `customer_id`=?');
    $chk->execute([$id]);
    if ((int) $chk->fetchColumn() > 0)
        return ['success' => false, 'error' => 'Cannot delete: customer has invoices.'];
    $cus = getCustomer($id);
    $pdo->prepare('DELETE FROM `customers` WHERE `id`=?')->execute([$id]);
    if ($cus)
        logActivity($_SESSION['user_id'], 'DELETE', 'customers', "Deleted customer: {$cus['name']}");
    return ['success' => true];
}

function getUsers(int $page = 1): array
{
    $pdo = getPDO();
    $perPage = (int) (getSetting('items_per_page') ?: 20);
    $total = (int) $pdo->query('SELECT COUNT(*) FROM `users`')->fetchColumn();
    $pag = paginate($total, $perPage, $page);
    $stmt = $pdo->prepare('SELECT `id`,`username`,`full_name`,`email`,`role`,`status`,`last_login`,`login_attempts`,`created_at` FROM `users` ORDER BY `created_at` ASC LIMIT ? OFFSET ?');
    $stmt->execute([$pag['per_page'], $pag['offset']]);
    return ['users' => $stmt->fetchAll(), 'pagination' => $pag];
}

function getUser(int $id): ?array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT `id`,`username`,`full_name`,`email`,`role`,`status` FROM `users` WHERE `id`=?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function saveUser(array $data, ?int $id = null): array
{
    $pdo = getPDO();
    $errors = [];
    $username = trim($data['username'] ?? '');
    $fullName = trim($data['full_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $role = $data['role'] ?? 'staff';
    $status = $data['status'] ?? 'active';
    $password = $data['password'] ?? '';
    if (empty($username))
        $errors[] = 'Username is required.';
    if (empty($fullName))
        $errors[] = 'Full name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'Valid email is required.';
    if (!$id && empty($password))
        $errors[] = 'Password is required for new users.';
    if (!empty($password)) {
        if (!validateStrongPassword($password))
            $errors[] = 'Password must be at least 12 characters with uppercase, lowercase, number, and special character.';
    }
    if (!in_array($role, ['admin', 'manager', 'staff']))
        $errors[] = 'Invalid role.';
    if (!empty($errors))
        return ['success' => false, 'errors' => $errors];
    $uniqChk = $pdo->prepare('SELECT `id` FROM `users` WHERE (`username`=? OR `email`=?) AND `id`!=?');
    $uniqChk->execute([$username, $email, $id ?? 0]);
    if ($uniqChk->fetch())
        $errors[] = 'Username or email already exists.';
    if (!empty($errors))
        return ['success' => false, 'errors' => $errors];
    if ($id) {
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare('UPDATE `users` SET `username`=?, `full_name`=?, `email`=?, `role`=?, `status`=?, `password_hash`=? WHERE `id`=?')->execute([$username, $fullName, $email, $role, $status, $hash, $id]);
        } else {
            $pdo->prepare('UPDATE `users` SET `username`=?, `full_name`=?, `email`=?, `role`=?, `status`=? WHERE `id`=?')->execute([$username, $fullName, $email, $role, $status, $id]);
        }
        logActivity($_SESSION['user_id'], 'UPDATE', 'users', "Updated user: $username");
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare('INSERT INTO `users` (`username`,`password_hash`,`full_name`,`email`,`role`,`status`) VALUES (?,?,?,?,?,?)')->execute([$username, $hash, $fullName, $email, $role, $status]);
        logActivity($_SESSION['user_id'], 'CREATE', 'users', "Created user: $username");
    }
    return ['success' => true];
}

function changePassword(int $userId, string $current, string $newPass): array
{
    $pdo = getPDO();
    if (strlen($newPass) < 8)
        return ['success' => false, 'error' => 'New password must be at least 8 characters.'];
    if (!preg_match('/[^a-zA-Z0-9]/', $newPass))
        return ['success' => false, 'error' => 'Password must contain at least one special character.'];
    $stmt = $pdo->prepare('SELECT `password_hash` FROM `users` WHERE `id`=?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($current, $user['password_hash']))
        return ['success' => false, 'error' => 'Current password is incorrect.'];
    $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
    $pdo->prepare('UPDATE `users` SET `password_hash`=? WHERE `id`=?')->execute([$hash, $userId]);
    logActivity($userId, 'CHANGE_PASSWORD', 'users', 'User changed their password');
    return ['success' => true];
}

function getNotifications(): array
{
    $pdo = getPDO();
    $pdo->exec('DELETE FROM `notifications` WHERE `created_at` < DATE_SUB(NOW(), INTERVAL 30 DAY)');
    $lowStock = $pdo->query("SELECT `sku`,`name`,`current_stock`,`min_stock_level` FROM `products` WHERE `current_stock` <= `min_stock_level` AND `status`='active' LIMIT 20")->fetchAll();
    foreach ($lowStock as $p) {
        $chk = $pdo->prepare("SELECT `id` FROM `notifications` WHERE `type`='low_stock' AND `related_id`=(SELECT `id` FROM `products` WHERE `sku`=?) AND `is_read`=0");
        $chk->execute([$p['sku']]);
        if (!$chk->fetch()) {
            $prodId = (int) $pdo->query("SELECT `id` FROM `products` WHERE `sku`='" . $p['sku'] . "'")->fetchColumn();
            $pdo->prepare("INSERT IGNORE INTO `notifications` (`type`,`title`,`message`,`related_id`,`related_module`) VALUES ('low_stock',?,?,?,'products')")->execute(["Low Stock: {$p['name']}", "Product '{$p['name']}' has only {$p['current_stock']} units left (min: {$p['min_stock_level']})", $prodId]);
        }
    }
    return $pdo->query('SELECT * FROM `notifications` ORDER BY `is_read` ASC, `created_at` DESC LIMIT 20')->fetchAll();
}

function getUnreadNotificationCount(): int
{
    return (int) getPDO()->query('SELECT COUNT(*) FROM `notifications` WHERE `is_read`=0')->fetchColumn();
}

function renderError403(): void
{
    echo '<div class="alert alert-danger" style="margin:40px auto;max-width:500px;text-align:center;"><h3>403 - Access Denied</h3><p>You do not have permission to access this page.</p><a href="?page=dashboard" class="btn btn-primary">Go to Dashboard</a></div>';
}

function renderLogin(?string $error = null): void
{
    $captcha = generateMathCaptcha();
    $_SESSION['captcha_answer'] = $captcha['answer'];
    $reason = $_GET['reason'] ?? '';
    $reasonMsg = match ($reason) {
        'timeout' => 'Session timed out. Please log in again.',
        'expired' => 'Session expired. Please log in again.',
        'logout' => 'You have been logged out successfully.',
        default => ''
    };
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= h(APP_NAME) ?> - Login</title>
        <style>
            *, *::before, *::after {
                box-sizing: border-box;
                margin: 0;
                padding: 0
            }
            body {
                font-family: 'Segoe UI', Arial, sans-serif;
                background: #e0e0e0;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                padding: 16px
            }
            .login-wrap {
                background: #f0f0f0;
                border: 2px solid #a0a0a0;
                border-style: ridge;
                width: 100%;
                max-width: 380px;
                padding: 0
            }
            .login-title-bar {
                background: #4a90d9;
                color: #fff;
                padding: 8px 12px;
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 14px;
                font-weight: 700;
                letter-spacing: 0.5px
            }
            .login-title-bar svg {
                width: 18px;
                height: 18px;
                fill: #fff
            }
            .login-body {
                padding: 24px
            }
            .app-title {
                text-align: center;
                font-size: 22px;
                font-weight: 700;
                color: #2c3e50;
                margin-bottom: 4px
            }
            .app-sub {
                text-align: center;
                font-size: 12px;
                color: #666;
                margin-bottom: 20px
            }
            .field-group {
                margin-bottom: 14px
            }
            .field-group label {
                display: block;
                font-size: 12px;
                font-weight: 600;
                color: #333;
                margin-bottom: 4px
            }
            .field-group input {
                width: 100%;
                padding: 6px 8px;
                border: 2px inset #a0a0a0;
                background: #fff;
                font-size: 13px;
                font-family: inherit;
                color: #222;
                outline: none
            }
            .field-group input:focus {
                border-color: #4a90d9
            }
            .captcha-box {
                background: #fff;
                border: 2px inset #a0a0a0;
                padding: 10px;
                text-align: center;
                margin-bottom: 8px;
                font-size: 18px;
                font-weight: 700;
                color: #2c3e50;
                letter-spacing: 4px;
                position: relative;
                overflow: hidden
            }
            .captcha-noise {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                pointer-events: none
            }
            .btn-login {
                width: 100%;
                padding: 8px;
                background: #4a90d9;
                color: #fff;
                border: 2px outset #6aaae9;
                font-size: 14px;
                font-weight: 700;
                cursor: pointer;
                font-family: inherit;
                letter-spacing: 0.5px
            }
            .btn-login:hover {
                background: #357abd
            }
            .btn-login:active {
                border-style: inset;
                background: #2a6099
            }
            .alert {
                padding: 8px 12px;
                margin-bottom: 14px;
                font-size: 12px;
                border: 1px solid
            }
            .alert-danger {
                background: #f8d7da;
                border-color: #f5c6cb;
                color: #721c24
            }
            .alert-success {
                background: #d4edda;
                border-color: #c3e6cb;
                color: #155724
            }
            .alert-info {
                background: #d1ecf1;
                border-color: #bee5eb;
                color: #0c5460
            }
            .default-creds {
                margin-top: 12px;
                padding: 8px;
                background: #fff3cd;
                border: 1px solid #ffc107;
                font-size: 11px;
                color: #856404;
                text-align: center
            }
            @media (max-width: 400px) {
                .login-wrap {
                    max-width: 100%
                }
            }
        </style>
    </head>
    <body>
    <div class="login-wrap">
        <div class="login-title-bar">
            <svg viewBox="0 0 24 24">
                <path d="M20 7H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zm-9 8H9v2H7v-2H5v-2h2v-2h2v2h2v2zm4.5 1c-.83 0-1.5-.67-1.5-1.5S14.67 13 15.5 13s1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm3-3c-.83 0-1.5-.67-1.5-1.5S17.67 10 18.5 10s1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM7 3h2v3H7zm8 0h2v3h-2z"/>
            </svg>
            <?= h(APP_NAME) ?> - Sign In
        </div>
        <div class="login-body">
            <div class="app-title"><?= h(APP_NAME) ?></div>
            <div class="app-sub">Inventory Management System v<?= APP_VERSION ?></div>
            <?php if ($reasonMsg): ?>
                <div class="alert alert-info"><?= h($reasonMsg) ?></div><?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
            <form method="POST" action="?page=login">
                <?= csrfField() ?>
                <div class="field-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?= h($_POST['username'] ?? '') ?>"
                           autocomplete="username" required>
                </div>
                <div class="field-group">
                    <label>Password</label>
                    <input type="password" name="password" autocomplete="current-password" required>
                </div>
                <div class="field-group">
                    <label>Security Check: </label>
                    <div class="captcha-box">
                        <svg class="captcha-noise" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%">
                            <?php for ($ci = 0; $ci < 8; $ci++):
                                $x1 = rand(0, 340);
                                $y1 = rand(0, 50);
                                $x2 = rand(0, 340);
                                $y2 = rand(0, 50); ?>
                                <line x1="<?= $x1 ?>" y1="<?= $y1 ?>" x2="<?= $x2 ?>" y2="<?= $y2 ?>" stroke="#ccc"
                                      stroke-width="1" opacity="0.6"/>
                            <?php endfor; ?>
                            <?php for ($ci = 0; $ci < 30; $ci++):
                                $cx = rand(0, 340);
                                $cy = rand(0, 50); ?>
                                <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="1.5" fill="#bbb" opacity="0.5"/>
                            <?php endfor; ?>
                        </svg>
                        <?= h($captcha['question']) ?> = ?
                    </div>
                    <input type="number" name="captcha" placeholder="Enter answer" required>
                </div>
                <button type="submit" class="btn-login">LOG IN</button>
            </form>
            <div class="default-creds">Default: <strong>admin</strong> / <strong>admin123!</strong></div>
        </div>
    </div>
    </body>
    </html>
    <?php
}

function renderLayout(string $pageTitle, string $pageContent, string $activePage = ''): void
{
    $user = ['name' => $_SESSION['user_name'] ?? 'User', 'role' => $_SESSION['user_role'] ?? 'staff', 'username' => $_SESSION['username'] ?? ''];
    $notifications = getNotifications();
    $unreadCount = getUnreadNotificationCount();
    $companyName = getSetting('company_name') ?: APP_NAME;
    $currencySymbol = getSetting('currency_symbol') ?: 'Rs.';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= h($pageTitle) ?> - <?= h(APP_NAME) ?></title>
        <style>
            *, *::before, *::after {
                box-sizing: border-box;
                margin: 0;
                padding: 0
            }
            :root {
                --bg: #e0e0e0;
                --surface: #f0f0f0;
                --surface2: #f8f8f8;
                --border: #a0a0a0;
                --border-dark: #707070;
                --text: #1a1a1a;
                --text-muted: #555;
                --text-faint: #888;
                --primary: #4a90d9;
                --primary-dark: #357abd;
                --primary-darker: #2a6099;
                --danger: #d9534f;
                --danger-dark: #c9302c;
                --success: #5cb85c;
                --success-dark: #449d44;
                --warning: #f0ad4e;
                --warning-dark: #ec971f;
                --sidebar-w: 220px;
                --sidebar-collapsed-w: 42px;
                --header-h: 42px;
                --status-h: 28px;
                --font: 'Segoe UI', Arial, sans-serif
            }
            body {
                font-family: var(--font);
                background: var(--bg);
                color: var(--text);
                font-size: 13px;
                min-height: 100vh;
                overflow-x: hidden
            }
            a {
                color: var(--primary);
                text-decoration: none
            }
            a:hover {
                color: var(--primary-dark);
                text-decoration: underline
            }
            button, input, select, textarea {
                font-family: var(--font);
                font-size: 13px
            }
            table {
                border-collapse: collapse;
                width: 100%
            }
            svg {
                display: inline-block;
                vertical-align: middle
            }
            /* HEADER */
            #header {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: var(--header-h);
                background: #4a90d9;
                border-bottom: 2px solid #357abd;
                z-index: 1000;
                display: flex;
                align-items: center;
                padding: 0 8px;
                gap: 8px
            }
            #hamburger {
                display: none;
                background: #357abd;
                border: 1px outset #6aaae9;
                color: #fff;
                padding: 4px 8px;
                cursor: pointer;
                font-size: 16px;
                line-height: 1
            }
            #header-title {
                color: #fff;
                font-size: 15px;
                font-weight: 700;
                letter-spacing: 0.5px;
                flex: 1;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis
            }
            #header-title span {
                font-size: 11px;
                font-weight: 400;
                opacity: 0.85;
                margin-left: 6px
            }
            .header-actions {
                display: flex;
                align-items: center;
                gap: 6px
            }
            .notif-btn {
                position: relative;
                background: #357abd;
                border: 1px outset #6aaae9;
                color: #fff;
                padding: 4px 8px;
                cursor: pointer;
                font-size: 13px;
                line-height: 1
            }
            .notif-btn:hover {
                background: #2a6099
            }
            .notif-badge {
                position: absolute;
                top: -4px;
                right: -4px;
                background: #d9534f;
                color: #fff;
                border-radius: 50%;
                width: 16px;
                height: 16px;
                font-size: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 700
            }
            .user-menu-wrap {
                position: relative
            }
            .user-btn {
                background: #357abd;
                border: 1px outset #6aaae9;
                color: #fff;
                padding: 4px 10px;
                cursor: pointer;
                font-size: 12px;
                display: flex;
                align-items: center;
                gap: 4px
            }
            .user-btn:hover {
                background: #2a6099
            }
            .user-dropdown {
                display: none;
                position: absolute;
                right: 0;
                top: 100%;
                background: #f0f0f0;
                border: 2px solid #a0a0a0;
                border-style: ridge;
                min-width: 160px;
                z-index: 2000
            }
            .user-dropdown.open {
                display: block
            }
            .user-dropdown-header {
                padding: 8px 12px;
                background: #e0e0e0;
                border-bottom: 1px solid #a0a0a0;
                font-size: 11px
            }
            .user-dropdown-header strong {
                display: block;
                font-size: 13px;
                color: #1a1a1a
            }
            .user-dropdown a, .user-dropdown button {
                display: block;
                width: 100%;
                text-align: left;
                padding: 7px 12px;
                color: #1a1a1a;
                text-decoration: none;
                background: none;
                border: none;
                cursor: pointer;
                font-size: 12px;
                border-bottom: 1px solid #e0e0e0
            }
            .user-dropdown a:hover, .user-dropdown button:hover {
                background: #4a90d9;
                color: #fff
            }
            /* SIDEBAR */
            #sidebar {
                position: fixed;
                top: var(--header-h);
                left: 0;
                bottom: var(--status-h);
                width: var(--sidebar-w);
                background: #f0f0f0;
                border-right: 2px solid #a0a0a0;
                overflow-y: auto;
                overflow-x: hidden;
                z-index: 900;
                transition: width 0.2s ease, transform 0.2s ease
            }
            #sidebar.collapsed {
                width: var(--sidebar-collapsed-w);
                overflow: visible
            }
            #sidebar.collapsed .nav-section-title {
                display: none
            }
            #sidebar.collapsed .nav-item {
                justify-content: center;
                padding: 7px 0;
                position: relative
            }
            #sidebar.collapsed .nav-item span.nav-icon {
                margin: 0
            }
            #sidebar.collapsed .nav-item .nav-label {
                display: none
            }
            #sidebar.collapsed .nav-item:hover::after {
                content: attr(data-label);
                position: absolute;
                left: calc(var(--sidebar-collapsed-w) + 4px);
                top: 50%;
                transform: translateY(-50%);
                background: #333;
                color: #fff;
                padding: 3px 8px;
                font-size: 11px;
                white-space: nowrap;
                border-radius: 3px;
                z-index: 9999;
                pointer-events: none
            }
            #sidebar-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.4);
                z-index: 890
            }
            .nav-section {
                padding: 4px 0;
                border-bottom: 1px solid #c8c8c8
            }
            .nav-section-title {
                padding: 6px 10px;
                font-size: 10px;
                font-weight: 700;
                color: #888;
                text-transform: uppercase;
                letter-spacing: 0.8px;
                background: #e8e8e8;
                border-bottom: 1px solid #d0d0d0
            }
            .nav-item {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 7px 12px;
                color: #1a1a1a;
                text-decoration: none;
                font-size: 12px;
                font-weight: 500;
                border-bottom: 1px solid #e0e0e0;
                cursor: pointer;
                transition: none
            }
            .nav-label {
                flex: 1;
                white-space: nowrap;
                overflow: hidden
            }
            .nav-item:hover {
                background: #d0d0d0;
                color: #1a1a1a;
                text-decoration: none
            }
            .nav-item.active {
                background: #4a90d9;
                color: #fff
            }
            .nav-item.active:hover {
                background: #357abd
            }
            .nav-icon {
                width: 16px;
                height: 16px;
                flex-shrink: 0
            }
            /* MAIN CONTENT */
            #main {
                margin-left: var(--sidebar-w);
                margin-top: var(--header-h);
                margin-bottom: var(--status-h);
                padding: 12px;
                min-height: calc(100vh - var(--header-h) - var(--status-h));
                background: var(--bg)
            }
            /* STATUS BAR */
            #statusbar {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                height: var(--status-h);
                background: #e8e8e8;
                border-top: 1px solid #a0a0a0;
                display: flex;
                align-items: center;
                padding: 0 10px;
                gap: 16px;
                font-size: 11px;
                color: #555;
                z-index: 1000
            }
            .sb-item {
                display: flex;
                align-items: center;
                gap: 4px
            }
            .sb-sep {
                color: #a0a0a0
            }
            #clock {
                margin-left: auto;
                font-weight: 600;
                color: #333
            }
            /* PAGE HEADER */
            .page-header {
                background: #f0f0f0;
                border: 2px solid #a0a0a0;
                border-style: ridge;
                padding: 8px 14px;
                margin-bottom: 12px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 8px
            }
            .page-header h1 {
                font-size: 16px;
                font-weight: 700;
                color: #1a1a1a;
                display: flex;
                align-items: center;
                gap: 8px
            }
            .page-header-actions {
                display: flex;
                gap: 6px;
                flex-wrap: wrap
            }
            /* LABELFRAME */
            .lf {
                border: 2px solid #a0a0a0;
                border-style: groove;
                padding: 12px 14px;
                margin-bottom: 12px;
                position: relative
            }
            .lf-title {
                position: absolute;
                top: -10px;
                left: 10px;
                background: #f0f0f0;
                padding: 0 6px;
                font-size: 11px;
                font-weight: 700;
                color: #555;
                text-transform: uppercase;
                letter-spacing: 0.5px
            }
            /* BUTTONS */
            .btn {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 5px 12px;
                font-size: 12px;
                font-weight: 600;
                border: 2px outset;
                cursor: pointer;
                text-decoration: none;
                font-family: var(--font);
                line-height: 1.4;
                white-space: nowrap;
                min-height: 28px
            }
            .btn:hover {
                text-decoration: none
            }
            .btn:active {
                border-style: inset
            }
            .btn-primary {
                background: var(--primary);
                color: #fff;
                border-color: #6aaae9
            }
            .btn-primary:hover {
                background: var(--primary-dark);
                color: #fff
            }
            .btn-primary:active {
                background: var(--primary-darker)
            }
            .btn-danger {
                background: var(--danger);
                color: #fff;
                border-color: #e87773
            }
            .btn-danger:hover {
                background: var(--danger-dark);
                color: #fff
            }
            .btn-success {
                background: var(--success);
                color: #fff;
                border-color: #7dcc7d
            }
            .btn-success:hover {
                background: var(--success-dark);
                color: #fff
            }
            .btn-warning {
                background: var(--warning);
                color: #1a1a1a;
                border-color: #f5c97e
            }
            .btn-warning:hover {
                background: var(--warning-dark)
            }
            .btn-secondary {
                background: #c0c0c0;
                color: #1a1a1a;
                border-color: #d8d8d8
            }
            .btn-secondary:hover {
                background: #a8a8a8
            }
            .btn-sm {
                padding: 3px 8px;
                font-size: 11px;
                min-height: 24px
            }
            .btn-xs {
                padding: 2px 6px;
                font-size: 10px;
                min-height: 20px
            }
            /* FORMS */
            .form-grid {
                display: grid;
                grid-template-columns:repeat(auto-fill, minmax(200px, 1fr));
                gap: 10px
            }
            .form-grid-2 {
                grid-template-columns:repeat(2, 1fr)
            }
            .form-grid-3 {
                grid-template-columns:repeat(3, 1fr)
            }
            .form-group {
                display: flex;
                flex-direction: column;
                gap: 3px
            }
            .form-group label {
                font-size: 11px;
                font-weight: 700;
                color: #333;
                text-transform: uppercase;
                letter-spacing: 0.3px
            }
            .form-group input, .form-group select, .form-group textarea {
                padding: 5px 7px;
                border: 2px inset #a0a0a0;
                background: #fff;
                color: #222;
                font-size: 12px;
                font-family: var(--font);
                outline: none
            }
            .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
                border-color: #4a90d9;
                border-style: solid
            }
            .form-group textarea {
                resize: vertical;
                min-height: 70px
            }
            .form-group select {
                appearance: auto;
                background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%23555'/%3E%3C/svg%3E") no-repeat right 6px center
            }
            .form-group.full {
                grid-column: 1/-1
            }
            .form-error {
                font-size: 11px;
                color: var(--danger)
            }
            .required-mark {
                color: var(--danger)
            }
            /* ALERTS */
            .alert {
                padding: 8px 12px;
                margin-bottom: 10px;
                font-size: 12px;
                border: 1px solid;
                display: flex;
                align-items: flex-start;
                gap: 6px
            }
            .alert-danger {
                background: #f8d7da;
                border-color: #f5c6cb;
                color: #721c24
            }
            .alert-success {
                background: #d4edda;
                border-color: #c3e6cb;
                color: #155724
            }
            .alert-warning {
                background: #fff3cd;
                border-color: #ffeeba;
                color: #856404
            }
            .alert-info {
                background: #d1ecf1;
                border-color: #bee5eb;
                color: #0c5460
            }
            .alert ul {
                margin: 4px 0 0 16px
            }
            .alert li {
                margin-bottom: 2px
            }
            /* TABLES */
            .tbl-wrap {
                overflow-x: auto;
                border: 2px solid #a0a0a0
            }
            .tbl {
                width: 100%;
                border-collapse: collapse;
                font-size: 12px
            }
            .tbl thead th {
                background: #d0d0d0;
                color: #1a1a1a;
                padding: 7px 10px;
                text-align: left;
                font-weight: 700;
                border-right: 1px solid #a0a0a0;
                border-bottom: 2px solid #a0a0a0;
                white-space: nowrap
            }
            .tbl thead th:last-child {
                border-right: none
            }
            .tbl tbody tr:nth-child(odd) {
                background: #f8f8f8
            }
            .tbl tbody tr:nth-child(even) {
                background: #f0f0f0
            }
            .tbl tbody tr:hover {
                background: #dde8f5
            }
            .tbl tbody td {
                padding: 6px 10px;
                border-right: 1px solid #d8d8d8;
                border-bottom: 1px solid #e0e0e0;
                vertical-align: middle
            }
            .tbl tbody td:last-child {
                border-right: none
            }
            .tbl-actions {
                display: flex;
                gap: 4px;
                flex-wrap: wrap
            }
            .sort-link {
                color: #1a1a1a;
                text-decoration: none;
                display: flex;
                align-items: center;
                gap: 3px
            }
            .sort-link:hover {
                color: #4a90d9
            }
            /* BADGES */
            .badge {
                display: inline-block;
                padding: 2px 7px;
                font-size: 10px;
                font-weight: 700;
                border: 1px solid;
                text-transform: uppercase;
                letter-spacing: 0.3px
            }
            .badge-success {
                background: #d4edda;
                border-color: #c3e6cb;
                color: #155724
            }
            .badge-danger {
                background: #f8d7da;
                border-color: #f5c6cb;
                color: #721c24
            }
            .badge-warning {
                background: #fff3cd;
                border-color: #ffeeba;
                color: #856404
            }
            .badge-info {
                background: #d1ecf1;
                border-color: #bee5eb;
                color: #0c5460
            }
            .badge-secondary {
                background: #e2e3e5;
                border-color: #d6d8db;
                color: #383d41
            }
            .badge-primary {
                background: #cce5ff;
                border-color: #b8daff;
                color: #004085
            }
            /* STATS CARDS */
            .stats-grid {
                display: grid;
                grid-template-columns:repeat(auto-fill, minmax(160px, 1fr));
                gap: 10px;
                margin-bottom: 12px
            }
            .stat-card {
                background: #f0f0f0;
                border: 2px solid #a0a0a0;
                border-style: ridge;
                padding: 12px;
                cursor: pointer
            }
            .stat-card:hover {
                background: #e8e8e8
            }
            .stat-card-title {
                font-size: 10px;
                font-weight: 700;
                color: #666;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                margin-bottom: 6px
            }
            .stat-card-value {
                font-size: 22px;
                font-weight: 700;
                color: #1a1a1a;
                line-height: 1;
                margin-bottom: 4px
            }
            .stat-card-sub {
                font-size: 10px;
                color: #888
            }
            .stat-card.primary .stat-card-value {
                color: #4a90d9
            }
            .stat-card.danger .stat-card-value {
                color: #d9534f
            }
            .stat-card.success .stat-card-value {
                color: #5cb85c
            }
            .stat-card.warning .stat-card-value {
                color: #f0ad4e
            }
            /* DASHBOARD CHARTS */
            .chart-bars {
                display: flex;
                align-items: flex-end;
                gap: 6px;
                height: 100px;
                padding: 4px 0
            }
            .chart-bar-wrap {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 3px;
                flex: 1
            }
            .chart-bar {
                background: #4a90d9;
                min-height: 4px;
                width: 100%;
                border: 1px solid #357abd;
                transition: height 0.3s
            }
            .chart-bar:hover {
                background: #357abd
            }
            .chart-bar-label {
                font-size: 9px;
                color: #666;
                text-align: center
            }
            .chart-bar-val {
                font-size: 9px;
                color: #333;
                font-weight: 600
            }
            /* FILTERS */
            .filter-bar {
                background: #f0f0f0;
                border: 2px solid #a0a0a0;
                border-style: ridge;
                padding: 10px 14px;
                margin-bottom: 10px;
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                align-items: flex-end
            }
            .filter-bar .form-group {
                min-width: 140px
            }
            .filter-bar .form-group label {
                text-transform: none;
                letter-spacing: 0
            }
            /* MODAL */
            .modal-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 5000;
                align-items: center;
                justify-content: center;
                padding: 10px
            }
            .modal-overlay.open {
                display: flex
            }
            .modal {
                background: #f0f0f0;
                border: 2px solid #a0a0a0;
                border-style: ridge;
                width: 100%;
                max-width: 560px;
                max-height: 90vh;
                display: flex;
                flex-direction: column
            }
            .modal-title-bar {
                background: #4a90d9;
                color: #fff;
                padding: 7px 12px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                font-weight: 700;
                font-size: 13px;
                flex-shrink: 0
            }
            .modal-close-btn {
                background: none;
                border: none;
                color: #fff;
                font-size: 18px;
                cursor: pointer;
                line-height: 1;
                padding: 0 4px
            }
            .modal-close-btn:hover {
                color: #ffd
            }
            .modal-body {
                padding: 16px;
                overflow-y: auto;
                flex: 1
            }
            .modal-footer {
                padding: 10px 16px;
                border-top: 1px solid #c0c0c0;
                display: flex;
                gap: 8px;
                justify-content: flex-end;
                flex-shrink: 0;
                background: #e8e8e8
            }
            .modal.wide {
                max-width: 800px
            }
            .modal.xl {
                max-width: 1000px
            }
            /* PAGINATION */
            .pagination {
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
                padding: 10px 0;
                align-items: center
            }
            .page-btn {
                padding: 4px 10px;
                background: #f0f0f0;
                border: 1px solid #a0a0a0;
                color: #1a1a1a;
                font-size: 12px;
                text-decoration: none;
                display: inline-block
            }
            .page-btn:hover {
                background: #d0d0d0;
                text-decoration: none;
                color: #1a1a1a
            }
            .page-btn.active {
                background: #4a90d9;
                color: #fff;
                border-color: #357abd
            }
            .page-info {
                margin-left: 8px;
                font-size: 11px;
                color: #666
            }
            /* TOAST */
            #toast-container {
                position: fixed;
                bottom: 36px;
                right: 12px;
                z-index: 9999;
                display: flex;
                flex-direction: column;
                gap: 6px
            }
            .toast {
                padding: 10px 14px;
                font-size: 12px;
                border: 1px solid;
                min-width: 200px;
                max-width: 320px;
                animation: toastIn 0.2s ease
            }
            .toast-success {
                background: #d4edda;
                border-color: #c3e6cb;
                color: #155724
            }
            .toast-error {
                background: #f8d7da;
                border-color: #f5c6cb;
                color: #721c24
            }
            .toast-info {
                background: #d1ecf1;
                border-color: #bee5eb;
                color: #0c5460
            }
            @keyframes toastIn {
                from {
                    opacity: 0;
                    transform: translateX(20px)
                }
                to {
                    opacity: 1;
                    transform: translateX(0)
                }
            }
            /* MISC */
            .stock-ok {
                color: var(--success);
                font-weight: 700
            }
            .stock-low {
                color: var(--warning);
                font-weight: 700
            }
            .stock-critical {
                color: var(--danger);
                font-weight: 700
            }
            .text-right {
                text-align: right
            }
            .text-center {
                text-align: center
            }
            .mt-8 {
                margin-top: 8px
            }
            .mb-8 {
                margin-bottom: 8px
            }
            .flex {
                display: flex
            }
            .flex-wrap {
                flex-wrap: wrap
            }
            .gap-6 {
                gap: 6px
            }
            .gap-10 {
                gap: 10px
            }
            .align-center {
                align-items: center
            }
            .justify-between {
                justify-content: space-between
            }
            .w-100 {
                width: 100%
            }
            .cols-2 {
                display: grid;
                grid-template-columns:1fr 1fr;
                gap: 10px
            }
            .cols-3 {
                display: grid;
                grid-template-columns:1fr 1fr 1fr;
                gap: 10px
            }
            .notif-panel {
                position: fixed;
                top: var(--header-h);
                right: 0;
                width: 320px;
                max-height: 400px;
                overflow-y: auto;
                background: #f0f0f0;
                border: 2px solid #a0a0a0;
                border-style: ridge;
                z-index: 3000;
                display: none
            }
            .notif-panel.open {
                display: block
            }
            .notif-item {
                padding: 8px 12px;
                border-bottom: 1px solid #e0e0e0;
                font-size: 12px;
                cursor: pointer
            }
            .notif-item:hover {
                background: #e8e8e8
            }
            .notif-item.unread {
                background: #e8f0fb;
                font-weight: 600
            }
            .notif-item-title {
                color: #1a1a1a;
                font-weight: 700;
                font-size: 11px
            }
            .notif-item-msg {
                color: #555;
                font-size: 11px;
                margin-top: 2px
            }
            .notif-item-time {
                color: #888;
                font-size: 10px;
                margin-top: 2px
            }
            .notif-panel-header {
                background: #4a90d9;
                color: #fff;
                padding: 7px 12px;
                font-weight: 700;
                font-size: 12px;
                display: flex;
                justify-content: space-between;
                align-items: center
            }
            .notif-mark-all {
                font-size: 11px;
                background: none;
                border: none;
                color: #cce;
                cursor: pointer;
                text-decoration: underline
            }
            .confirm-dialog {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 8000;
                align-items: center;
                justify-content: center
            }
            .confirm-dialog.open {
                display: flex
            }
            .confirm-box {
                background: #f0f0f0;
                border: 2px solid #a0a0a0;
                border-style: ridge;
                padding: 0;
                min-width: 300px;
                max-width: 400px
            }
            .confirm-title {
                background: #d9534f;
                color: #fff;
                padding: 7px 12px;
                font-weight: 700;
                font-size: 13px
            }
            .confirm-body {
                padding: 16px;
                font-size: 13px
            }
            .confirm-footer {
                padding: 10px 16px;
                background: #e8e8e8;
                border-top: 1px solid #c0c0c0;
                display: flex;
                gap: 8px;
                justify-content: flex-end
            }
            .loading-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(240, 240, 240, 0.7);
                z-index: 9000;
                align-items: center;
                justify-content: center
            }
            .loading-overlay.open {
                display: flex
            }
            .loading-spinner {
                background: #f0f0f0;
                border: 2px ridge #a0a0a0;
                padding: 20px 30px;
                font-weight: 700;
                font-size: 14px;
                color: #4a90d9
            }
            .barcode-svg-wrap {
                font-family: 'Courier New', monospace;
                text-align: center;
                border: 1px solid #a0a0a0;
                padding: 8px;
                background: #fff;
                display: inline-block
            }
            .barcode-bars {
                display: flex;
                align-items: flex-end;
                justify-content: center;
                gap: 0;
                height: 50px
            }
            .barcode-bar {
                background: #000;
                height: 100%
            }
            .barcode-bar.t {
                height: 70%
            }
            .barcode-text {
                font-size: 10px;
                letter-spacing: 2px;
                margin-top: 4px
            }
            .quick-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin-bottom: 12px
            }
            .recent-grid {
                display: grid;
                grid-template-columns:1fr 1fr;
                gap: 10px;
                margin-bottom: 12px
            }
            .dashboard-row {
                display: grid;
                grid-template-columns:2fr 1fr;
                gap: 10px;
                margin-bottom: 12px
            }
            .print-only {
                display: none
            }
            @media print {
                * {
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                #header, #sidebar, #statusbar, #toast-container, .no-print, button.no-print, .page-header-actions {
                    display: none !important
                }
                #main {
                    margin: 0;
                    padding: 0
                }
                .print-only {
                    display: block
                }
                body {
                    font-size: 11px
                }
                .tbl {
                    font-size: 10px
                }
            }
            @media (max-width: 900px) {
                :root {
                    --sidebar-w: 220px
                }
                #sidebar {
                    transform: translateX(-100%)
                }
                body.sidebar-collapsed #main-content {
                    margin-left: 0
                }
                #sidebar.collapsed {
                    width: var(--sidebar-w)
                }
                #sidebar.collapsed .nav-label {
                    display: inline
                }
                #sidebar.collapsed .nav-item {
                    justify-content: flex-start;
                    padding: 7px 12px
                }
                #sidebar.collapsed .nav-section-title {
                    display: block
                }
                #sidebar.collapsed .nav-item:hover::after {
                    display: none
                }
                #sidebar {
                    transform: translateX(-220px);
                    width: 220px
                }
                #sidebar.open {
                    transform: translateX(0)
                }
                body.sidebar-collapsed #main-content {
                    margin-left: var(--sidebar-collapsed-w)
                }
                #sidebar-overlay.open {
                    display: block
                }
                #hamburger {
                    display: block
                }
                #main {
                    margin-left: 0
                }
                .stats-grid {
                    grid-template-columns:repeat(2, 1fr)
                }
                .recent-grid, .dashboard-row {
                    grid-template-columns:1fr
                }
                .form-grid-2, .form-grid-3 {
                    grid-template-columns:1fr
                }
                .cols-2, .cols-3 {
                    grid-template-columns:1fr
                }
                .modal {
                    max-width: 100%;
                    max-height: 100vh
                }
                .notif-panel {
                    width: 100%;
                    right: 0
                }
            }
            @media (max-width: 500px) {
                .stats-grid {
                    grid-template-columns:repeat(2, 1fr)
                }
                .page-header {
                    flex-direction: column;
                    align-items: flex-start
                }
                .filter-bar .form-group {
                    min-width: 100%;
                    width: 100%
                }
                .filter-bar {
                    flex-direction: column
                }
                .tbl-wrap {
                    font-size: 11px
                }
                #header-title span {
                    display: none
                }
            }
        </style>
    </head>
    <body>
    <div id="loading-overlay" class="loading-overlay">
        <div class="loading-spinner">⏳ Processing...</div>
    </div>
    <div id="confirm-dialog" class="confirm-dialog">
        <div class="confirm-box">
            <div class="confirm-title" id="confirm-title">Confirm Action</div>
            <div class="confirm-body" id="confirm-msg">Are you sure?</div>
            <div class="confirm-footer">
                <button id="confirm-yes" class="btn btn-danger btn-sm">Yes, Proceed</button>
                <button id="confirm-no" class="btn btn-secondary btn-sm" onclick="closeConfirm()">Cancel</button>
            </div>
        </div>
    </div>
    <div id="toast-container"></div>
    <header id="header">
        <button id="hamburger" onclick="toggleSidebar()" aria-label="Menu">&#9776;</button>
        <div id="header-title"><?= h(APP_NAME) ?> <span><?= h($companyName) ?></span></div>
        <div class="header-actions">
            <div style="position:relative">
                <button class="notif-btn" onclick="toggleNotifPanel()" title="Notifications">
                    &#128276;
                    <?php if ($unreadCount > 0): ?><span
                            class="notif-badge"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span><?php endif; ?>
                </button>
                <div id="notif-panel" class="notif-panel">
                    <div class="notif-panel-header">
                        Notifications (<?= $unreadCount ?> unread)
                        <button class="notif-mark-all" onclick="markAllNotifRead()">Mark all read</button>
                    </div>
                    <?php if (empty($notifications)): ?>
                        <div style="padding:16px;text-align:center;color:#888;font-size:12px;">No notifications</div>
                    <?php else:
        foreach ($notifications as $n): ?>
                            <div class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>"
                                 onclick="markNotifRead(<?= $n['id'] ?>)">
                                <div class="notif-item-title"><?= h($n['title']) ?></div>
                                <div class="notif-item-msg"><?= h(substr($n['message'], 0, 80)) ?>...</div>
                                <div class="notif-item-time"><?= h($n['created_at']) ?></div>
                            </div>
                        <?php endforeach;
    endif; ?>
                </div>
            </div>
            <div class="user-menu-wrap">
                <button class="user-btn" onclick="toggleUserMenu()">
                    &#128100; <?= h($user['name']) ?> <small>[<?= h($user['role']) ?>]</small> &#9660;
                </button>
                <div id="user-dropdown" class="user-dropdown">
                    <div class="user-dropdown-header">
                        <strong><?= h($user['name']) ?></strong>
                        <?= h($user['username']) ?> &bull; <?= h($user['role']) ?>
                    </div>
                    <a href="?page=profile">&#128100; My Profile</a>
                    <a href="?page=profile&action=change_password">&#128274; Change Password</a>
                    <button onclick="window.location='?action=logout'">&#128275; Sign Out</button>
                </div>
            </div>
        </div>
    </header>
    <div id="sidebar-overlay" onclick="toggleSidebar()"></div>
    <nav id="sidebar">
        <?php
        $navItems = [
            ['section' => 'MAIN'],
            ['page' => 'dashboard', 'label' => 'Dashboard', 'icon' => '&#9632;', 'roles' => ['admin', 'manager', 'staff']],
            ['page' => 'pos', 'label' => 'POS Checkout', 'icon' => '&#128722;', 'roles' => ['admin', 'manager', 'staff']],
            ['section' => 'INVENTORY'],
            ['page' => 'products', 'label' => 'Products', 'icon' => '&#128230;', 'roles' => ['admin', 'manager', 'staff']],
            ['page' => 'categories', 'label' => 'Categories', 'icon' => '&#128193;', 'roles' => ['admin', 'manager', 'staff']],
            ['page' => 'adjustments', 'label' => 'Stock Adjustments', 'icon' => '&#9881;', 'roles' => ['admin', 'manager', 'staff']],
            ['page' => 'transfers', 'label' => 'Stock Transfers', 'icon' => '&#8644;', 'roles' => ['admin', 'manager']],
            ['page' => 'stock_audit', 'label' => 'Stock Audit', 'icon' => '&#128269;', 'roles' => ['admin', 'manager']],
            ['section' => 'SALES'],
            ['page' => 'sales', 'label' => 'Sales / Invoices', 'icon' => '&#128203;', 'roles' => ['admin', 'manager', 'staff']],
            ['page' => 'quotations', 'label' => 'Quotations', 'icon' => '&#128221;', 'roles' => ['admin', 'manager', 'staff']],
            ['page' => 'sales_returns', 'label' => 'Sales Returns', 'icon' => '&#8630;', 'roles' => ['admin', 'manager']],
            ['page' => 'customers', 'label' => 'Customers', 'icon' => '&#128101;', 'roles' => ['admin', 'manager', 'staff']],
            ['page' => 'customer_ledgers', 'label' => 'Customer Ledgers', 'icon' => '&#128200;', 'roles' => ['admin', 'manager']],
            ['section' => 'PURCHASING'],
            ['page' => 'purchases', 'label' => 'Purchase Orders', 'icon' => '&#128722;', 'roles' => ['admin', 'manager', 'staff']],
            ['page' => 'purchase_returns', 'label' => 'Purchase Returns', 'icon' => '&#8630;', 'roles' => ['admin', 'manager']],
            ['page' => 'requisitions', 'label' => 'Requisitions', 'icon' => '&#128227;', 'roles' => ['admin', 'manager', 'staff']],
            ['page' => 'suppliers', 'label' => 'Suppliers', 'icon' => '&#128667;', 'roles' => ['admin', 'manager', 'staff']],
            ['section' => 'FINANCE'],
            ['page' => 'income_expenses', 'label' => 'Income & Expenses', 'icon' => '&#128181;', 'roles' => ['admin', 'manager']],
            ['page' => 'taxes', 'label' => 'Taxes', 'icon' => '&#37;', 'roles' => ['admin']],
            ['page' => 'currencies', 'label' => 'Currencies', 'icon' => '&#164;', 'roles' => ['admin']],
            ['section' => 'HR'],
            ['page' => 'shifts', 'label' => 'Shifts', 'icon' => '&#128336;', 'roles' => ['admin', 'manager']],
            ['page' => 'roles', 'label' => 'Roles & Permissions', 'icon' => '&#128272;', 'roles' => ['admin']],
            ['page' => 'users', 'label' => 'User Management', 'icon' => '&#128101;', 'roles' => ['admin']],
            ['section' => 'INTEGRATIONS'],
            ['page' => 'api_sync', 'label' => 'E-Commerce Sync', 'icon' => '&#128279;', 'roles' => ['admin']],
            ['page' => 'sms', 'label' => 'SMS Alerts', 'icon' => '&#128241;', 'roles' => ['admin']],
            ['section' => 'REPORTS'],
            ['page' => 'reports', 'label' => 'Reports', 'icon' => '&#128202;', 'roles' => ['admin', 'manager']],
            ['page' => 'barcodes', 'label' => 'Barcodes', 'icon' => '&#9644;', 'roles' => ['admin', 'manager', 'staff']],
            ['page' => 'activity_log', 'label' => 'Activity Log', 'icon' => '&#128196;', 'roles' => ['admin', 'manager']],
            ['section' => 'SYSTEM'],
            ['page' => 'settings', 'label' => 'Settings', 'icon' => '&#9881;', 'roles' => ['admin']],
        ];
        foreach ($navItems as $item):
            if (isset($item['section'])):
                ?>
                <div class="nav-section-title"><?= h($item['section']) ?></div>
            <?php elseif (canAccess($item['roles'])):
                $isActive = ($activePage === $item['page']); ?>
                <a href="?page=<?= h($item['page']) ?>" class="nav-item <?= $isActive ? 'active' : '' ?>"
                   data-label="<?= h($item['label']) ?>">
                    <span class="nav-icon"><?= $item['icon'] ?></span>
                    <span class="nav-label"><?= h($item['label']) ?></span>
                </a>
            <?php endif;
        endforeach; ?>
    </nav>
    <main id="main">
        <?= $pageContent ?>
    </main>
    <footer id="statusbar">
        <span class="sb-item">&#128100; <?= h($user['name']) ?></span>
        <span class="sb-sep">|</span>
        <span class="sb-item">Role: <?= h(ucfirst($user['role'])) ?></span>
        <span class="sb-sep">|</span>
        <span class="sb-item">Page: <?= h(ucfirst($activePage)) ?></span>
        <span id="clock" class="sb-item"></span>
    </footer>
    <script>
        var CSRF_TOKEN = <?= json_encode(csrf()) ?>;
        var CURRENCY = <?= json_encode(getSetting('currency_symbol') ?: 'Rs.') ?>;
        var TAX_PCT = <?= json_encode((float) getSetting('default_tax_percent') / 100) ?>;
        function toggleSidebar() {
            const isMobile = window.innerWidth <= 900;
            if (isMobile) {
                document.getElementById('sidebar').classList.toggle('open');
                document.getElementById('sidebar-overlay').classList.toggle('open');
            } else {
                const sidebar = document.getElementById('sidebar');
                const collapsed = sidebar.classList.toggle('collapsed');
                document.body.classList.toggle('sidebar-collapsed', collapsed);
                localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0');
            }
        }
        function initSidebar() {
            const isMobile = window.innerWidth <= 900;
            if (isMobile) {
                document.getElementById('sidebar').classList.remove('collapsed');
                document.body.classList.remove('sidebar-collapsed');
            } else {
                const saved = localStorage.getItem('sidebarCollapsed');
                if (saved === '1') {
                    document.getElementById('sidebar').classList.add('collapsed');
                    document.body.classList.add('sidebar-collapsed');
                }
            }
        }
        initSidebar();
        function toggleUserMenu() {
            document.getElementById('user-dropdown').classList.toggle('open');
        }
        function toggleNotifPanel() {
            document.getElementById('notif-panel').classList.toggle('open');
            document.getElementById('user-dropdown').classList.remove('open');
        }
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.user-menu-wrap')) document.getElementById('user-dropdown').classList.remove('open');
            if (!e.target.closest('.notif-btn') && !e.target.closest('#notif-panel')) document.getElementById('notif-panel').classList.remove('open');
        });
        function showToast(msg, type) {
            type = type || 'info';
            var c = document.getElementById('toast-container');
            var t = document.createElement('div');
            t.className = 'toast toast-' + type;
            t.textContent = msg;
            c.appendChild(t);
            setTimeout(function () {
                t.style.opacity = '0';
                t.style.transition = 'opacity 0.5s';
                setTimeout(function () {
                    if (t.parentNode) t.parentNode.removeChild(t);
                }, 500);
            }, 3500);
        }
        function showLoading() {
            document.getElementById('loading-overlay').classList.add('open');
        }
        function hideLoading() {
            document.getElementById('loading-overlay').classList.remove('open');
        }
        var _confirmCb = null;
        function confirmAction(msg, cb, title) {
            title = title || 'Confirm Delete';
            document.getElementById('confirm-msg').textContent = msg || 'Are you sure you want to proceed? This action cannot be undone.';
            document.getElementById('confirm-title').textContent = title;
            document.getElementById('confirm-dialog').classList.add('open');
            _confirmCb = cb;
        }
        function closeConfirm() {
            document.getElementById('confirm-dialog').classList.remove('open');
            _confirmCb = null;
        }
        document.getElementById('confirm-yes').onclick = function () {
            if (_confirmCb) {
                _confirmCb();
            }
            closeConfirm();
        };
        function openModal(id) {
            document.getElementById(id).classList.add('open');
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('open');
        }
        document.querySelectorAll('.modal-overlay').forEach(function (m) {
            m.addEventListener('click', function (e) {
                if (e.target === m) m.classList.remove('open');
            });
        });
        function apiPost(url, data, onSuccess, onError) {
            showLoading();
            data['csrf_token'] = CSRF_TOKEN;
            fetch(url, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
                body: new URLSearchParams(data)
            })
                .then(function (r) {
                    return r.json();
                })
                .then(function (d) {
                    hideLoading();
                    if (d.success) {
                        if (onSuccess) onSuccess(d);
                    } else {
                        if (onError) onError(d); else showToast(d.error || 'An error occurred.', 'error');
                    }
                })
                .catch(function (e) {
                    hideLoading();
                    showToast('Network error. Please try again.', 'error');
                });
        }
        function markNotifRead(id) {
            fetch('?ajax=mark_notif_read&id=' + id, {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': CSRF_TOKEN}
            });
        }
        function markAllNotifRead() {
            fetch('?ajax=mark_all_notif_read', {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': CSRF_TOKEN}
            });
            document.querySelectorAll('.notif-item.unread').forEach(function (n) {
                n.classList.remove('unread');
            });
            var badge = document.querySelector('.notif-badge');
            if (badge) badge.remove();
            document.getElementById('notif-panel').querySelector('.notif-panel-header').textContent = 'Notifications (0 unread)';
        }
        (function clock() {
            var el = document.getElementById('clock');
            function tick() {
                var now = new Date();
                el.textContent = now.toLocaleDateString() + ' ' + now.toLocaleTimeString();
            }
            tick();
            setInterval(tick, 1000);
        })();
        <?php if (isset($_SESSION['toast_msg'])): ?>
        showToast(<?= json_encode($_SESSION['toast_msg']) ?>, <?= json_encode($_SESSION['toast_type'] ?? 'info') ?>);
        <?php unset($_SESSION['toast_msg'], $_SESSION['toast_type']);
    endif; ?>
    </script>
    <?php
}

function renderDashboard(): void
{
    ob_start();
    $stats = getDashboardStats();
    $dates7 = array_reverse(array_column($stats['sales_last7'], 'date'));
    $sales7 = array_reverse(array_column($stats['sales_last7'], 'amount'));
    $cost7 = array_reverse(array_column($stats['sales_last7'], 'cost'));
    $topProdLabels = array_column($stats['top_products'], 'name');
    $topProdData = array_column($stats['top_products'], 'qty_sold');
    $custTypeLabels = array_column($stats['customers_by_type'], 'customer_type');
    $custTypeData = array_column($stats['customers_by_type'], 'cnt');
    $poStatusLabels = array_column($stats['po_status_dist'], 'order_status');
    $poStatusData = array_column($stats['po_status_dist'], 'cnt');
    $catStockLabels = array_column($stats['stock_by_category'], 'name');
    $catStockData = array_column($stats['stock_by_category'], 'val');
    ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <div class="page-header">
        <h1>&#9632; Dashboard Overview</h1>
    </div>
    <div class="quick-actions">
        <a href="?page=sales&action=new" class="btn btn-success btn-sm">+ New Invoice</a>
        <a href="?page=purchases&action=new" class="btn btn-primary btn-sm">+ New PO</a>
        <a href="?page=products&action=new" class="btn btn-secondary btn-sm">+ New Product</a>
        <a href="?page=customers&action=new" class="btn btn-sm"
           style="background:#17a2b8;color:#fff;border-color:#117a8b">+ New Customer</a>
        <a href="?page=suppliers&action=new" class="btn btn-sm"
           style="background:#343a40;color:#fff;border-color:#23272b">+ New Supplier</a>
        <a href="?page=adjustments&action=new" class="btn btn-warning btn-sm">+ Stock Adjustment</a>
        <a href="?page=transfers&action=new" class="btn btn-warning btn-sm">+ Stock Transfer</a>
        <a href="?page=quotations&action=new" class="btn btn-primary btn-sm">+ New Quotation</a>
        <a href="?page=sales_returns&action=new" class="btn btn-danger btn-sm">+ Sales Return</a>
        <a href="?page=purchase_returns&action=new" class="btn btn-danger btn-sm">+ Purchase Return</a>
        <a href="?page=income_expenses" class="btn btn-success btn-sm">+ Add Income</a>
        <a href="?page=income_expenses" class="btn btn-danger btn-sm">+ Add Expense</a>
        <a href="?page=pos" class="btn btn-primary btn-sm" style="background:#6f42c1;border-color:#6f42c1">&#128722; POS
            Checkout</a>
        <a href="?page=stock_audit" class="btn btn-secondary btn-sm">&#128269; Stock Audit</a>
        <a href="?page=reports" class="btn btn-sm" style="background:#e83e8c;color:#fff;border-color:#e83e8c">&#128202;
            Reports</a>
    </div>
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fill,minmax(200px,1fr)); margin-bottom:16px;">
        <div class="stat-card primary" onclick="window.location='?page=products'">
            <div class="stat-card-title">Total Products</div>
            <div class="stat-card-value"><?= number_format($stats['total_products']) ?></div>
            <div class="stat-card-sub">Active items in catalog</div>
        </div>
        <div class="stat-card danger" onclick="window.location='?page=products&stock_level=low'">
            <div class="stat-card-title">Low Stock Alerts</div>
            <div class="stat-card-value"><?= number_format($stats['low_stock']) ?></div>
            <div class="stat-card-sub">Below minimum level</div>
        </div>
        <div class="stat-card danger" onclick="window.location='?page=products&stock_level=out'">
            <div class="stat-card-title">Out of Stock</div>
            <div class="stat-card-value"><?= number_format($stats['out_of_stock']) ?></div>
            <div class="stat-card-sub">Zero inventory</div>
        </div>
        <div class="stat-card primary" onclick="window.location='?page=categories'">
            <div class="stat-card-title">Categories</div>
            <div class="stat-card-value"><?= number_format($stats['categories_count']) ?></div>
            <div class="stat-card-sub">Active categories</div>
        </div>
        <div class="stat-card success" onclick="window.location='?page=sales'">
            <div class="stat-card-title">Today's Sales</div>
            <div class="stat-card-value" style="font-size:18px"><?= formatCurrency($stats['today_sales']) ?></div>
            <div class="stat-card-sub">Revenue today</div>
        </div>
        <div class="stat-card success" onclick="window.location='?page=sales'">
            <div class="stat-card-title">This Month Sales</div>
            <div class="stat-card-value" style="font-size:18px"><?= formatCurrency($stats['this_month_sales']) ?></div>
            <div class="stat-card-sub">Revenue this month</div>
        </div>
        <div class="stat-card success">
            <div class="stat-card-title">Total Revenue</div>
            <div class="stat-card-value" style="font-size:18px"><?= formatCurrency($stats['total_revenue']) ?></div>
            <div class="stat-card-sub">All time</div>
        </div>
        <div class="stat-card warning" onclick="window.location='?page=sales&payment_status=unpaid'">
            <div class="stat-card-title">Unpaid Invoices</div>
            <div class="stat-card-value"
                 style="font-size:18px"><?= formatCurrency($stats['unpaid_invoices_amt']) ?></div>
            <div class="stat-card-sub"><?= $stats['unpaid_invoices'] ?> invoices</div>
        </div>
        <div class="stat-card primary" onclick="window.location='?page=purchases'">
            <div class="stat-card-title">Today's Purchases</div>
            <div class="stat-card-value" style="font-size:18px"><?= formatCurrency($stats['today_purchases']) ?></div>
            <div class="stat-card-sub">Procurement today</div>
        </div>
        <div class="stat-card primary" onclick="window.location='?page=purchases'">
            <div class="stat-card-title">This Month Purchases</div>
            <div class="stat-card-value"
                 style="font-size:18px"><?= formatCurrency($stats['this_month_purchases']) ?></div>
            <div class="stat-card-sub">Procurement this month</div>
        </div>
        <div class="stat-card warning" onclick="window.location='?page=purchases&order_status=pending'">
            <div class="stat-card-title">Pending POs</div>
            <div class="stat-card-value"><?= number_format($stats['pending_orders']) ?></div>
            <div class="stat-card-sub">Awaiting delivery</div>
        </div>
        <div class="stat-card danger" onclick="window.location='?page=purchases&payment_status=unpaid'">
            <div class="stat-card-title">Unpaid POs Amount</div>
            <div class="stat-card-value" style="font-size:18px"><?= formatCurrency($stats['unpaid_pos_amt']) ?></div>
            <div class="stat-card-sub">Payables outstanding</div>
        </div>
        <div class="stat-card info" onclick="window.location='?page=customers'">
            <div class="stat-card-title">Total Customers</div>
            <div class="stat-card-value"><?= number_format($stats['total_customers']) ?></div>
            <div class="stat-card-sub">Active customers</div>
        </div>
        <div class="stat-card info" onclick="window.location='?page=suppliers'">
            <div class="stat-card-title">Total Suppliers</div>
            <div class="stat-card-value"><?= number_format($stats['total_suppliers']) ?></div>
            <div class="stat-card-sub">Active suppliers</div>
        </div>
        <div class="stat-card secondary" onclick="window.location='?page=sales_returns'">
            <div class="stat-card-title">Total Sales Returns</div>
            <div class="stat-card-value"
                 style="font-size:18px"><?= formatCurrency($stats['total_sales_returns']) ?></div>
            <div class="stat-card-sub">Amount refunded</div>
        </div>
        <div class="stat-card secondary" onclick="window.location='?page=purchase_returns'">
            <div class="stat-card-title">Purchase Returns</div>
            <div class="stat-card-value"
                 style="font-size:18px"><?= formatCurrency($stats['total_purchase_returns']) ?></div>
            <div class="stat-card-sub">Returned to suppliers</div>
        </div>
    </div>
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 16px; margin-bottom: 16px;">
        <div class="lf"><span class="lf-title">Sales & Cost (Last 7 Days)</span>
            <div style="height:250px;">
                <canvas id="chartSalesCost"></canvas>
            </div>
        </div>
        <div class="lf"><span class="lf-title">Inventory Value by Category</span>
            <div style="height:250px;">
                <canvas id="chartStockCat"></canvas>
            </div>
        </div>
        <div class="lf"><span class="lf-title">Top 5 Selling Products</span>
            <div style="height:250px; display:flex; justify-content:center;">
                <canvas id="chartTopProducts"></canvas>
            </div>
        </div>
        <div class="lf"><span class="lf-title">Customers by Type</span>
            <div style="height:250px; display:flex; justify-content:center;">
                <canvas id="chartCustType"></canvas>
            </div>
        </div>
        <div class="lf"><span class="lf-title">PO Status Distribution</span>
            <div style="height:250px; display:flex; justify-content:center;">
                <canvas id="chartPoStatus"></canvas>
            </div>
        </div>
        <div class="lf"><span class="lf-title">Sales Revenue (Last 7 Days)</span>
            <div style="height:250px;">
                <canvas id="chartSalesBar"></canvas>
            </div>
        </div>
    </div>
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 16px;">
        <div class="lf"><span class="lf-title">Recent Invoices</span>
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                    <tr>
                        <th>Invoice#</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($stats['recent_invoices'])): ?>
                        <tr>
                            <td colspan="5" class="text-center" style="color:#888;padding:16px">No invoices yet</td>
                        </tr>
                    <?php else:
        foreach ($stats['recent_invoices'] as $inv): ?>
                            <tr>
                                <td>
                                    <a href="?page=sales&action=view&id=<?= $inv['id'] ?>"><?= h($inv['invoice_number']) ?></a>
                                </td>
                                <td><?= h($inv['customer_name']) ?></td>
                                <td class="text-right"><?= formatCurrency((float) $inv['total_amount']) ?></td>
                                <td><?php $ps = $inv['payment_status'];
            echo '<span class="badge badge-' . ($ps === 'paid' ? 'success' : ($ps === 'partial' ? 'warning' : 'danger')) . '">' . h(strtoupper($ps)) . '</span>'; ?></td>
                                <td><?= h($inv['invoice_date']) ?></td>
                            </tr>
                        <?php endforeach;
    endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-8 text-right"><a href="?page=sales" class="btn btn-secondary btn-xs">View All Invoices</a>
            </div>
        </div>
        <div class="lf"><span class="lf-title">Recent Purchase Orders</span>
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                    <tr>
                        <th>PO#</th>
                        <th>Supplier</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($stats['recent_purchases'])): ?>
                        <tr>
                            <td colspan="5" class="text-center" style="color:#888;padding:16px">No purchase orders yet
                            </td>
                        </tr>
                    <?php else:
        foreach ($stats['recent_purchases'] as $po): ?>
                            <tr>
                                <td>
                                    <a href="?page=purchases&action=view&id=<?= $po['id'] ?>"><?= h($po['po_number']) ?></a>
                                </td>
                                <td><?= h($po['supplier_name']) ?></td>
                                <td class="text-right"><?= formatCurrency((float) $po['total_amount']) ?></td>
                                <td><?php $os = $po['order_status'];
            echo '<span class="badge badge-' . ($os === 'received' ? 'success' : ($os === 'cancelled' ? 'danger' : ($os === 'partial' ? 'warning' : 'info'))) . '">' . h(strtoupper($os)) . '</span>'; ?></td>
                                <td><?= h($po['po_date']) ?></td>
                            </tr>
                        <?php endforeach;
    endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-8 text-right"><a href="?page=purchases" class="btn btn-secondary btn-xs">View All POs</a>
            </div>
        </div>
        <div class="lf"><span class="lf-title">Low Stock Products</span>
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th class="text-right">Stock</th>
                        <th class="text-right">Min</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($stats['low_stock_products'])): ?>
                        <tr>
                            <td colspan="4" class="text-center" style="color:#5cb85c;padding:16px">&#10003; All stock
                                levels OK
                            </td>
                        </tr>
                    <?php else:
        foreach ($stats['low_stock_products'] as $p):
            $cls = $p['current_stock'] == 0 ? 'critical' : 'low'; ?>
                            <tr>
                                <td><code style="font-size:10px"><?= h($p['sku']) ?></code></td>
                                <td><?= h($p['name']) ?></td>
                                <td class="text-right stock-<?= $cls ?>"><?= number_format((int) $p['current_stock']) ?></td>
                                <td class="text-right"><?= number_format((int) $p['min_stock_level']) ?></td>
                            </tr>
                        <?php endforeach;
    endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-8 text-right"><a href="?page=products&stock_level=low" class="btn btn-warning btn-xs">View
                    All Low Stock</a></div>
        </div>
        <div class="lf"><span class="lf-title">Inventory Snapshot</span>
            <div style="font-size:13px;display:flex;flex-direction:column;gap:8px">
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e8e8e8">
                    <span>Total Inventory Value:</span><span
                            style="font-weight:700;color:#5cb85c"><?= formatCurrency($stats['inventory_value']) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e8e8e8">
                    <span>Unpaid Invoices (Receivable):</span><span
                            style="font-weight:700;color:#4a90d9"><?= formatCurrency($stats['unpaid_invoices_amt']) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e8e8e8">
                    <span>Unpaid POs (Payable):</span><span
                            style="font-weight:700;color:#d9534f"><?= formatCurrency($stats['unpaid_pos_amt']) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e8e8e8">
                    <span>System Users:</span><span
                            style="font-weight:700;color:#555"><?= number_format($stats['total_users']) ?></span></div>
                <div style="display:flex;justify-content:space-between;padding:8px 0">
                    <span>Registered Warehouses:</span><span
                            style="font-weight:700;color:#555"><?= number_format($stats['total_warehouses']) ?></span>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            Chart.defaults.font.family = "'Segoe UI', Arial, sans-serif";
            const dates7 = <?= json_encode($dates7) ?>;
            const sales7 = <?= json_encode($sales7) ?>;
            const cost7 = <?= json_encode($cost7) ?>;
            const colors = ['#4a90d9', '#5cb85c', '#f0ad4e', '#d9534f', '#6f42c1', '#17a2b8', '#fd7e14', '#e83e8c'];
            new Chart(document.getElementById('chartSalesCost'), {
                type: 'line',
                data: {
                    labels: dates7,
                    datasets: [
                        {
                            label: 'Revenue',
                            data: sales7,
                            borderColor: '#5cb85c',
                            backgroundColor: 'rgba(92,184,92,0.1)',
                            fill: true,
                            tension: 0.3
                        },
                        {
                            label: 'Cost',
                            data: cost7,
                            borderColor: '#d9534f',
                            backgroundColor: 'transparent',
                            borderDash: [5, 5],
                            tension: 0.3
                        }
                    ]
                },
                options: {responsive: true, maintainAspectRatio: false, plugins: {legend: {position: 'bottom'}}}
            });
            new Chart(document.getElementById('chartSalesBar'), {
                type: 'bar',
                data: {
                    labels: dates7,
                    datasets: [{label: 'Sales Revenue', data: sales7, backgroundColor: '#4a90d9'}]
                },
                options: {responsive: true, maintainAspectRatio: false, plugins: {legend: {display: false}}}
            });
            new Chart(document.getElementById('chartTopProducts'), {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode($topProdLabels) ?>,
                    datasets: [{data: <?= json_encode($topProdData) ?>, backgroundColor: colors}]
                },
                options: {responsive: true, maintainAspectRatio: false, plugins: {legend: {position: 'right'}}}
            });
            new Chart(document.getElementById('chartCustType'), {
                type: 'pie',
                data: {
                    labels: <?= json_encode($custTypeLabels) ?>,
                    datasets: [{
                        data: <?= json_encode($custTypeData) ?>,
                        backgroundColor: ['#17a2b8', '#ffc107', '#28a745', '#e83e8c']
                    }]
                },
                options: {responsive: true, maintainAspectRatio: false, plugins: {legend: {position: 'right'}}}
            });
            new Chart(document.getElementById('chartPoStatus'), {
                type: 'polarArea',
                data: {
                    labels: <?= json_encode($poStatusLabels) ?>,
                    datasets: [{
                        data: <?= json_encode($poStatusData) ?>,
                        backgroundColor: ['#f0ad4e', '#5bc0de', '#5cb85c', '#d9534f']
                    }]
                },
                options: {responsive: true, maintainAspectRatio: false, plugins: {legend: {position: 'right'}}}
            });
            new Chart(document.getElementById('chartStockCat'), {
                type: 'bar',
                data: {
                    labels: <?= json_encode($catStockLabels) ?>,
                    datasets: [{
                        label: 'Stock Value',
                        data: <?= json_encode($catStockData) ?>,
                        backgroundColor: '#6f42c1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {legend: {display: false}}
                }
            });
        });
    </script>
    <?php
    $content = ob_get_clean();
    renderLayout('Dashboard Overview', $content, 'dashboard');
}

function renderProducts(): void
{
    $action = $_GET['action'] ?? 'list';
    $pdo = getPDO();
    if ($action === 'list' || $action === '') {
        $filters = ['search' => $_GET['search'] ?? '', 'category_id' => $_GET['category_id'] ?? '', 'status' => $_GET['status'] ?? '', 'stock_level' => $_GET['stock_level'] ?? '', 'sort' => $_GET['sort'] ?? '', 'dir' => $_GET['dir'] ?? ''];
        $page = max(1, (int) ($_GET['pg'] ?? 1));
        $result = getProducts($filters, $page);
        $products = $result['products'];
        $pag = $result['pagination'];
        $categories = getCategories(true);
        ob_start();
        ?>
        <div class="page-header">
            <h1>&#128230; Products / Inventory</h1>
            <div class="page-header-actions">
                <a href="?page=products&action=new" class="btn btn-success btn-sm">+ Add Product</a>
                <a href="?page=products&action=import" class="btn btn-primary btn-sm">&#8593; Import CSV</a>
                <a href="?page=products&action=export" class="btn btn-secondary btn-sm">&#8595; Export CSV</a>
            </div>
        </div>
        <form method="GET" action="">
            <input type="hidden" name="page" value="products">
            <div class="filter-bar">
                <div class="form-group"><label>Search</label><input type="text" name="search"
                                                                    value="<?= h($filters['search']) ?>"
                                                                    placeholder="Name, SKU, brand, barcode..."></div>
                <div class="form-group"><label>Category</label>
                    <select name="category_id">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option
                            value="<?= $cat['id'] ?>" <?= $filters['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= h(($cat['parent_name'] ? '-- ' : '') . $cat['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Status</label>
                    <select name="status">
                        <option value="">All</option>
                        <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive
                        </option>
                    </select>
                </div>
                <div class="form-group"><label>Stock Level</label>
                    <select name="stock_level">
                        <option value="">All</option>
                        <option value="ok" <?= $filters['stock_level'] === 'ok' ? 'selected' : '' ?>>OK</option>
                        <option value="low" <?= $filters['stock_level'] === 'low' ? 'selected' : '' ?>>Low</option>
                        <option value="out" <?= $filters['stock_level'] === 'out' ? 'selected' : '' ?>>Out of Stock
                        </option>
                    </select>
                </div>
                <div class="form-group"
                     style="justify-content:flex-end;flex-direction:row;align-items:flex-end;gap:6px">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="?page=products" class="btn btn-secondary btn-sm">Reset</a>
                </div>
            </div>
        </form>
    <?php if (isset($_SESSION['flash_msg'])): ?>
        <div class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?>"><?= h($_SESSION['flash_msg']) ?></div>
    <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
        endif; ?>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                <tr>
                    <th><input type="checkbox" id="chk-all" onchange="toggleAllCheckboxes(this)"></th>
                    <th><a class="sort-link"
                           href="?page=products&sort=sku&dir=<?= $filters['sort'] === 'sku' && $filters['dir'] === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= h($filters['search']) ?>&category_id=<?= h($filters['category_id']) ?>&status=<?= h($filters['status']) ?>">SKU <?= $filters['sort'] === 'sku' ? ($filters['dir'] === 'ASC' ? '&#9650;' : '&#9660;') : '' ?></a>
                    </th>
                    <th><a class="sort-link"
                           href="?page=products&sort=name&dir=<?= $filters['sort'] === 'name' && $filters['dir'] === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= h($filters['search']) ?>">Product
                            Name <?= $filters['sort'] === 'name' ? ($filters['dir'] === 'ASC' ? '&#9650;' : '&#9660;') : '' ?></a>
                    </th>
                    <th>Category</th>
                    <th>Brand</th>
                    <th>Unit</th>
                    <th class="text-right"><a class="sort-link"
                                              href="?page=products&sort=purchase_price&dir=<?= $filters['sort'] === 'purchase_price' && $filters['dir'] === 'ASC' ? 'DESC' : 'ASC' ?>">Buy
                            Price</a></th>
                    <th class="text-right"><a class="sort-link"
                                              href="?page=products&sort=selling_price&dir=<?= $filters['sort'] === 'selling_price' && $filters['dir'] === 'ASC' ? 'DESC' : 'ASC' ?>">Sell
                            Price</a></th>
                    <th class="text-right"><a class="sort-link"
                                              href="?page=products&sort=current_stock&dir=<?= $filters['sort'] === 'current_stock' && $filters['dir'] === 'ASC' ? 'DESC' : 'ASC' ?>">Stock</a>
                    </th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="11" class="text-center" style="padding:20px;color:#888">No products found. <a
                                    href="?page=products&action=new">Add your first product</a></td>
                    </tr>
                <?php
        else:
            foreach ($products as $p):
                $stockClass = $p['current_stock'] == 0 ? 'stock-critical' : ($p['current_stock'] <= $p['min_stock_level'] ? 'stock-low' : 'stock-ok');
                ?>
                        <tr>
                            <td><input type="checkbox" class="row-chk" value="<?= $p['id'] ?>"></td>
                            <td><code style="font-size:11px"><?= h($p['sku']) ?></code></td>
                            <td><strong><?= h($p['name']) ?></strong><?php if ($p['brand']): ?><br><small
                                        style="color:#888"><?= h($p['brand']) ?></small><?php endif; ?></td>
                            <td><?= h($p['category_name'] ?? '-') ?></td>
                            <td><?= h($p['brand'] ?: '-') ?></td>
                            <td><?= h($p['unit']) ?></td>
                            <td class="text-right"><?= formatCurrency((float) $p['purchase_price']) ?></td>
                            <td class="text-right"><?= formatCurrency((float) $p['selling_price']) ?></td>
                            <td class="text-right <?= $stockClass ?>"><?= number_format((int) $p['current_stock']) ?>
                                <small><?= h($p['unit']) ?></small></td>
                            <td>
                                <span class="badge badge-<?= $p['status'] === 'active' ? 'success' : 'secondary' ?>"><?= h(strtoupper($p['status'])) ?></span>
                            </td>
                            <td>
                                <div class="tbl-actions">
                                    <a href="?page=products&action=view&id=<?= $p['id'] ?>"
                                       class="btn btn-primary btn-xs" title="View">&#128065;</a>
                                    <a href="?page=products&action=edit&id=<?= $p['id'] ?>"
                                       class="btn btn-warning btn-xs" title="Edit">&#9998;</a>
                                    <a href="?page=adjustments&action=new&product_id=<?= $p['id'] ?>"
                                       class="btn btn-secondary btn-xs" title="Adjust Stock">&#9881;</a>
                                    <?php if (canAccess(['admin', 'manager'])): ?>
                                        <button onclick="deleteProduct(<?= $p['id'] ?>, '<?= h(addslashes($p['name'])) ?>')"
                                                class="btn btn-danger btn-xs" title="Delete">&#128465;
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach;
        endif; ?>
                </tbody>
            </table>
        </div>
    <?php if (!empty($products)): ?>
        <div style="display:flex;align-items:center;gap:10px;margin-top:8px;flex-wrap:wrap">
            <div style="font-size:12px;color:#666">Bulk action on selected:</div>
            <button onclick="bulkAction('activate')" class="btn btn-success btn-sm">Activate</button>
            <button onclick="bulkAction('deactivate')" class="btn btn-warning btn-sm">Deactivate</button>
            <?php if (canAccess(['admin'])): ?>
                <button onclick="bulkAction('delete')" class="btn btn-danger btn-sm">Delete</button><?php endif; ?>
        </div>
    <?php endif; ?>
    <?= paginationHtml($pag, '?page=products&search=' . urlencode($filters['search']) . '&category_id=' . urlencode($filters['category_id']) . '&status=' . urlencode($filters['status']) . '&stock_level=' . urlencode($filters['stock_level'])) ?>
        <script>
            function toggleAllCheckboxes(src) {
                document.querySelectorAll('.row-chk').forEach(function (c) {
                    c.checked = src.checked;
                });
            }
            function getCheckedIds() {
                var ids = [];
                document.querySelectorAll('.row-chk:checked').forEach(function (c) {
                    ids.push(c.value);
                });
                return ids;
            }
            function bulkAction(act) {
                var ids = getCheckedIds();
                if (!ids.length) {
                    showToast('Please select at least one product.', 'error');
                    return;
                }
                var msg = act === 'delete' ? 'Delete ' + ids.length + ' selected product(s)? This cannot be undone.' : (act === 'activate' ? 'Activate' : 'Deactivate') + ' ' + ids.length + ' selected product(s)?';
                confirmAction(msg, function () {
                    apiPost('?ajax=bulk_products', {action: act, ids: ids.join(',')}, function (d) {
                        showToast(d.message || 'Done', 'success');
                        setTimeout(function () {
                            location.reload();
                        }, 800);
                    });
                }, act === 'delete' ? 'Confirm Delete' : 'Confirm Action');
            }
            function deleteProduct(id, name) {
                confirmAction('Delete product "' + name + '"? This cannot be undone.', function () {
                    apiPost('?ajax=delete_product', {id: id}, function (d) {
                        showToast('Product deleted.', 'success');
                        setTimeout(function () {
                            location.reload();
                        }, 800);
                    });
                });
            }
        </script>
    <?php
        $content = ob_get_clean();
        renderLayout('Products', $content, 'products');
    } elseif ($action === 'new' || $action === 'edit') {
        $editId = !empty($_GET['id']) ? (int) $_GET['id'] : null;
        $prod = $editId ? getProduct($editId) : null;
        $categories = getCategories(true);
        $warehouses = $pdo->query("SELECT * FROM `warehouses` WHERE `status`='active' ORDER BY `name`")->fetchAll();
        $errors = [];
        $success = false;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
            $result = saveProduct($_POST, $editId);
            if ($result['success']) {
                $_SESSION['flash_msg'] = $editId ? 'Product updated successfully.' : 'Product created successfully.';
                $_SESSION['flash_type'] = 'success';
                header('Location: ?page=products');
                exit;
            } else {
                $errors = $result['errors'] ?? [$result['error'] ?? 'Unknown error'];
            }
        }
        $d = $prod ?? $_POST;
        ob_start();
        ?>
        <div class="page-header">
            <h1><?= $editId ? '&#9998; Edit Product' : '&#43; New Product' ?></h1>
            <div class="page-header-actions"><a href="?page=products" class="btn btn-secondary btn-sm">&#8592; Back to
                    Products</a></div>
        </div>
        <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul><?php foreach ($errors as $e): ?>
                <li><?= h($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form method="POST" action="?page=products&action=<?= $editId ? 'edit&id=' . $editId : 'new' ?>">
            <?= csrfField() ?>
            <div class="lf"><span class="lf-title">Basic Information</span>
                <div class="form-grid form-grid-3">
                    <div class="form-group">
                        <label>SKU <span class="required-mark">*</span></label>
                        <input type="text" name="sku" value="<?= h($d['sku'] ?? generateSku()) ?>" required>
                    </div>
                    <div class="form-group full" style="grid-column:span 2">
                        <label>Product Name <span class="required-mark">*</span></label>
                        <input type="text" name="name" value="<?= h($d['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option
                                value="<?= $cat['id'] ?>" <?= ($d['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= h(($cat['parent_name'] ? '-- ' : '') . $cat['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Brand</label>
                        <input type="text" name="brand" value="<?= h($d['brand'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Unit</label>
                        <select name="unit">
                            <?php foreach (['pcs', 'kg', 'ltr', 'box', 'bag', 'carton', 'dozen', 'meter', 'set', 'pair'] as $u): ?>
                                <option value="<?= $u ?>" <?= ($d['unit'] ?? 'pcs') === $u ? 'selected' : '' ?>><?= $u ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Description</label>
                        <textarea name="description"><?= h($d['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            <div class="lf"><span class="lf-title">Pricing</span>
                <div class="form-grid form-grid-3">
                    <div class="form-group">
                        <label>Purchase Price <span class="required-mark">*</span></label>
                        <input type="number" name="purchase_price" step="0.01" min="0"
                               value="<?= h($d['purchase_price'] ?? '0.00') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Selling Price <span class="required-mark">*</span></label>
                        <input type="number" name="selling_price" step="0.01" min="0"
                               value="<?= h($d['selling_price'] ?? '0.00') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Margin</label>
                        <input type="text" id="margin-display" readonly
                               style="background:#f0f0f0;color:#5cb85c;font-weight:700">
                    </div>
                </div>
            </div>
            <div class="lf"><span class="lf-title">Stock & Location</span>
                <div class="form-grid form-grid-3">
                    <div class="form-group">
                        <label>Current Stock</label>
                        <input type="number" name="current_stock" min="0" value="<?= h($d['current_stock'] ?? '0') ?>">
                    </div>
                    <div class="form-group">
                        <label>Minimum Stock Level</label>
                        <input type="number" name="min_stock_level" min="0"
                               value="<?= h($d['min_stock_level'] ?? '5') ?>">
                    </div>
                    <div class="form-group">
                        <label>Maximum Stock Level</label>
                        <input type="number" name="max_stock_level" min="0"
                               value="<?= h($d['max_stock_level'] ?? '1000') ?>">
                    </div>
                    <div class="form-group">
                        <label>Warehouse / Location</label>
                        <select name="warehouse_id">
                            <option value="">-- No Specific Location --</option>
                            <?php foreach ($warehouses as $w): ?>
                                <option
                                value="<?= $w['id'] ?>" <?= ($d['warehouse_id'] ?? '') == $w['id'] ? 'selected' : '' ?>><?= h($w['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Barcode</label>
                        <input type="text" name="barcode" value="<?= h($d['barcode'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="active" <?= ($d['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>
                                Active
                            </option>
                            <option value="inactive" <?= ($d['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>
                                Inactive
                            </option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Image URL</label>
                        <input type="url" name="image_url" value="<?= h($d['image_url'] ?? '') ?>"
                               placeholder="https://...">
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <button type="submit"
                        class="btn btn-success"><?= $editId ? '&#10003; Update Product' : '&#43; Create Product' ?></button>
                <a href="?page=products" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
        <script>
            (function () {
                var bp = document.querySelector('[name=purchase_price]');
                var sp = document.querySelector('[name=selling_price]');
                var md = document.getElementById('margin-display');
                function calcMargin() {
                    var b = parseFloat(bp.value) || 0, s = parseFloat(sp.value) || 0;
                    if (b > 0) {
                        var m = ((s - b) / b * 100).toFixed(1);
                        md.value = m + '% (' + CURRENCY + ' ' + (s - b).toFixed(2) + ')';
                        md.style.color = m >= 0 ? '#5cb85c' : '#d9534f';
                    } else {
                        md.value = 'N/A';
                    }
                }
                bp.addEventListener('input', calcMargin);
                sp.addEventListener('input', calcMargin);
                calcMargin();
            })();
        </script>
    <?php
        $content = ob_get_clean();
        renderLayout(($editId ? 'Edit' : 'New') . ' Product', $content, 'products');
    } elseif ($action === 'view') {
        $id = (int) ($_GET['id'] ?? 0);
        $prod = getProduct($id);
        if (!$prod) {
            $_SESSION['flash_msg'] = 'Product not found.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ?page=products');
            exit;
        }
        $pdo2 = getPDO();
        $salesHistory = $pdo2->prepare('SELECT ii.quantity, ii.unit_price, ii.total_price, i.invoice_number, i.invoice_date, c.name as customer_name FROM invoice_items ii JOIN invoices i ON i.id=ii.invoice_id JOIN customers c ON c.id=i.customer_id WHERE ii.product_id=? ORDER BY i.invoice_date DESC LIMIT 10');
        $salesHistory->execute([$id]);
        $salesHistory = $salesHistory->fetchAll();
        $adjustHistory = $pdo2->prepare('SELECT sa.*, u.full_name as requested_by_name FROM stock_adjustments sa JOIN users u ON u.id=sa.requested_by WHERE sa.product_id=? ORDER BY sa.created_at DESC LIMIT 10');
        $adjustHistory->execute([$id]);
        $adjustHistory = $adjustHistory->fetchAll();
        ob_start();
        ?>
        <div class="page-header">
            <h1>&#128065; Product Detail</h1>
            <div class="page-header-actions">
                <a href="?page=products&action=edit&id=<?= $id ?>" class="btn btn-warning btn-sm">&#9998; Edit</a>
                <a href="?page=adjustments&action=new&product_id=<?= $id ?>" class="btn btn-primary btn-sm">&#9881;
                    Adjust Stock</a>
                <a href="?page=barcodes&product_id=<?= $id ?>" class="btn btn-secondary btn-sm">&#9644; Print
                    Barcode</a>
                <a href="?page=products" class="btn btn-secondary btn-sm">&#8592; Back</a>
            </div>
        </div>
        <div class="cols-2">
            <div class="lf"><span class="lf-title">Product Information</span>
                <table style="width:100%;font-size:12px">
                    <tbody>
                    <tr>
                        <td style="padding:5px 0;color:#666;width:40%">SKU:</td>
                        <td><code><?= h($prod['sku']) ?></code></td>
                    </tr>
                    <tr>
                        <td style="padding:5px 0;color:#666">Name:</td>
                        <td><strong><?= h($prod['name']) ?></strong></td>
                    </tr>
                    <tr>
                        <td style="padding:5px 0;color:#666">Category:</td>
                        <td><?= h($prod['category_name'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td style="padding:5px 0;color:#666">Brand:</td>
                        <td><?= h($prod['brand'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td style="padding:5px 0;color:#666">Unit:</td>
                        <td><?= h($prod['unit']) ?></td>
                    </tr>
                    <tr>
                        <td style="padding:5px 0;color:#666">Barcode:</td>
                        <td><?= h($prod['barcode'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td style="padding:5px 0;color:#666">Warehouse:</td>
                        <td><?= h($prod['warehouse_name'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td style="padding:5px 0;color:#666">Status:</td>
                        <td>
                            <span class="badge badge-<?= $prod['status'] === 'active' ? 'success' : 'secondary' ?>"><?= h(strtoupper($prod['status'])) ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:5px 0;color:#666">Description:</td>
                        <td><?= h($prod['description'] ?? '-') ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div>
                <div class="lf"><span class="lf-title">Pricing & Stock</span>
                    <table style="width:100%;font-size:12px">
                        <tbody>
                        <tr>
                            <td style="padding:5px 0;color:#666;width:50%">Purchase Price:</td>
                            <td><?= formatCurrency((float) $prod['purchase_price']) ?></td>
                        </tr>
                        <tr>
                            <td style="padding:5px 0;color:#666">Selling Price:</td>
                            <td><?= formatCurrency((float) $prod['selling_price']) ?></td>
                        </tr>
                        <tr>
                            <td style="padding:5px 0;color:#666">Profit Margin:</td>
                            <td><?php $pp = (float) $prod['purchase_price'];
        $sp = (float) $prod['selling_price'];
        echo $pp > 0 ? number_format(($sp - $pp) / $pp * 100, 1) . '%' : '-'; ?></td>
                        </tr>
                        <tr>
                            <td style="padding:5px 0;color:#666">Current Stock:</td>
                            <td><?php $sc = $prod['current_stock'] <= 0 ? 'critical' : ($prod['current_stock'] <= $prod['min_stock_level'] ? 'low' : 'ok'); ?>
                                <span class="stock-<?= $sc ?>"
                                      style="font-size:18px;font-weight:700"><?= number_format((int) $prod['current_stock']) ?></span> <?= h($prod['unit']) ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:5px 0;color:#666">Min Stock Level:</td>
                            <td><?= number_format((int) $prod['min_stock_level']) ?></td>
                        </tr>
                        <tr>
                            <td style="padding:5px 0;color:#666">Max Stock Level:</td>
                            <td><?= number_format((int) $prod['max_stock_level']) ?></td>
                        </tr>
                        <tr>
                            <td style="padding:5px 0;color:#666">Stock Value:</td>
                            <td><?= formatCurrency((float) $prod['current_stock'] * (float) $prod['purchase_price']) ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="lf"><span class="lf-title">Recent Sales History</span>
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                    <tr>
                        <th>Invoice#</th>
                        <th>Customer</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                        <th>Date</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($salesHistory)): ?>
                        <tr>
                            <td colspan="6" class="text-center" style="padding:12px;color:#888">No sales history</td>
                        </tr>
                    <?php else:
            foreach ($salesHistory as $sh): ?>
                            <tr>
                                <td><?= h($sh['invoice_number']) ?></td>
                                <td><?= h($sh['customer_name']) ?></td>
                                <td><?= number_format((int) $sh['quantity']) ?></td>
                                <td><?= formatCurrency((float) $sh['unit_price']) ?></td>
                                <td><?= formatCurrency((float) $sh['total_price']) ?></td>
                                <td><?= h($sh['invoice_date']) ?></td>
                            </tr>
                        <?php endforeach;
        endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="lf"><span class="lf-title">Stock Adjustment History</span>
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                    <tr>
                        <th>Ref#</th>
                        <th>Type</th>
                        <th>Qty</th>
                        <th>Before</th>
                        <th>After</th>
                        <th>Reason</th>
                        <th>By</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($adjustHistory)): ?>
                        <tr>
                            <td colspan="9" class="text-center" style="padding:12px;color:#888">No adjustments</td>
                        </tr>
                    <?php else:
            foreach ($adjustHistory as $ah): ?>
                            <tr>
                                <td><code style="font-size:10px"><?= h($ah['reference_no']) ?></code></td>
                                <td><?= h(str_replace('_', ' ', strtoupper($ah['adjustment_type']))) ?></td>
                                <td><?= number_format((int) $ah['quantity']) ?></td>
                                <td><?= number_format((int) $ah['before_stock']) ?></td>
                                <td><?= number_format((int) $ah['after_stock']) ?></td>
                                <td><?= h(substr($ah['reason'] ?? '-', 0, 30)) ?></td>
                                <td><?= h($ah['requested_by_name']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $ah['status'] === 'approved' ? 'success' : ($ah['status'] === 'rejected' ? 'danger' : 'warning') ?>"><?= h(strtoupper($ah['status'])) ?></span>
                                </td>
                                <td><?= h(substr($ah['created_at'], 0, 10)) ?></td>
                            </tr>
                        <?php endforeach;
        endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php
        $content = ob_get_clean();
        renderLayout('Product Detail', $content, 'products');
    } elseif ($action === 'export') {
        requireRole(['admin', 'manager', 'staff']);
        $result = getProducts([], 1);
        $all = getPDO()->query('SELECT p.*, c.name as category_name, w.name as warehouse_name FROM products p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN warehouses w ON w.id=p.warehouse_id ORDER BY p.sku')->fetchAll();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="products_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['SKU', 'Name', 'Description', 'Category', 'Brand', 'Unit', 'Purchase Price', 'Selling Price', 'Current Stock', 'Min Stock', 'Max Stock', 'Warehouse', 'Barcode', 'Status']);
        foreach ($all as $p) {
            fputcsv($out, [$p['sku'], $p['name'], $p['description'], $p['category_name'] ?? '', $p['brand'], $p['unit'], $p['purchase_price'], $p['selling_price'], $p['current_stock'], $p['min_stock_level'], $p['max_stock_level'], $p['warehouse_name'] ?? '', $p['barcode'], $p['status']]);
        }
        fclose($out);
        exit;
    } elseif ($action === 'import') {
        $importErrors = [];
        $importSuccess = 0;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
            if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === 0) {
                $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
                if ($ext !== 'csv') {
                    $importErrors[] = 'File must be a CSV.';
                } else {
                    $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
                    $header = fgetcsv($handle);
                    while (($row = fgetcsv($handle)) !== false) {
                        if (count($row) < 8)
                            continue;
                        $data = ['sku' => trim($row[0] ?? generateSku()), 'name' => trim($row[1] ?? ''), 'description' => trim($row[2] ?? ''), 'category_id' => '', 'brand' => trim($row[4] ?? ''), 'unit' => trim($row[5] ?? 'pcs'), 'purchase_price' => (float) ($row[6] ?? 0), 'selling_price' => (float) ($row[7] ?? 0), 'current_stock' => (int) ($row[8] ?? 0), 'min_stock_level' => (int) ($row[9] ?? 5), 'max_stock_level' => (int) ($row[10] ?? 1000), 'barcode' => trim($row[12] ?? ''), 'status' => trim($row[13] ?? 'active')];
                        if (empty($data['name']))
                            continue;
                        if (empty($data['sku']))
                            $data['sku'] = generateSku();
                        $res = saveProduct($data);
                        if ($res['success'])
                            $importSuccess++;
                        else
                            $importErrors = array_merge($importErrors, $res['errors'] ?? [$res['error'] ?? 'Row error']);
                    }
                    fclose($handle);
                }
            } else {
                $importErrors[] = 'Please select a CSV file.';
            }
        }
        ob_start();
        ?>
        <div class="page-header"><h1>&#8593; Import Products CSV</h1>
            <div class="page-header-actions"><a href="?page=products" class="btn btn-secondary btn-sm">&#8592; Back</a>
            </div>
        </div>
        <?php if ($importSuccess): ?>
    <div class="alert alert-success"><?= $importSuccess ?> product(s) imported successfully.</div><?php endif; ?>
        <?php if (!empty($importErrors)): ?>
    <div class="alert alert-danger">
        <ul><?php foreach (array_slice($importErrors, 0, 10) as $e): ?>
                <li><?= h($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <div class="lf"><span class="lf-title">Import CSV File</span>
            <form method="POST" enctype="multipart/form-data" action="?page=products&action=import">
                <?= csrfField() ?>
                <div class="form-group" style="max-width:400px">
                    <label>CSV File <span class="required-mark">*</span></label>
                    <input type="file" name="csv_file" accept=".csv" required>
                </div>
                <div style="margin:12px 0;padding:10px;background:#fff3cd;border:1px solid #ffc107;font-size:12px">
                    <strong>CSV Format (columns in order):</strong><br>
                    SKU, Name, Description, Category, Brand, Unit, Purchase Price, Selling Price, Current Stock, Min
                    Stock, Max Stock, Warehouse, Barcode, Status
                </div>
                <button type="submit" class="btn btn-primary">&#8593; Import Now</button>
                <a href="?page=products&action=export" class="btn btn-secondary" style="margin-left:8px">&#8595;
                    Download Sample CSV</a>
            </form>
        </div>
        <?php
        $content = ob_get_clean();
        renderLayout('Import Products', $content, 'products');
    }
}

function renderCategories(): void
{
    $action = $_GET['action'] ?? 'list';
    $pdo = getPDO();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf() && in_array($action, ['new', 'edit'])) {
        $editId = !empty($_GET['id']) ? (int) $_GET['id'] : null;
        $res = saveCategory($_POST, $editId);
        if ($res['success']) {
            $_SESSION['flash_msg'] = $editId ? 'Category updated.' : 'Category created.';
            $_SESSION['flash_type'] = 'success';
            header('Location: ?page=categories');
            exit;
        } else {
            $_SESSION['flash_msg'] = $res['error'] ?? 'Error saving category.';
            $_SESSION['flash_type'] = 'danger';
        }
    }
    if ($action === 'delete' && !empty($_GET['id']) && canAccess(['admin', 'manager'])) {
        if ($_GET['confirm'] ?? '' === 'yes') {
            $id = (int) $_GET['id'];
            $chkProd = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id=?');
            $chkProd->execute([$id]);
            $chkChild = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE parent_id=?');
            $chkChild->execute([$id]);
            if ((int) $chkProd->fetchColumn() > 0) {
                $_SESSION['flash_msg'] = 'Cannot delete: category has products.';
                $_SESSION['flash_type'] = 'danger';
            } elseif ((int) $chkChild->fetchColumn() > 0) {
                $_SESSION['flash_msg'] = 'Cannot delete: category has sub-categories.';
                $_SESSION['flash_type'] = 'danger';
            } else {
                $pdo->prepare('DELETE FROM categories WHERE id=?')->execute([$id]);
                logActivity($_SESSION['user_id'], 'DELETE', 'categories', 'Deleted category id ' . $id);
                $_SESSION['flash_msg'] = 'Category deleted.';
                $_SESSION['flash_type'] = 'success';
            }
            header('Location: ?page=categories');
            exit;
        }
    }
    $categories = getCategories();
    $editCat = ($action === 'edit' && !empty($_GET['id'])) ? ($pdo->prepare('SELECT * FROM categories WHERE id=?') ?: null) : null;
    if ($editCat) {
        $editCat->execute([(int) $_GET['id']]);
        $editCat = $editCat->fetch() ?: null;
    }
    ob_start();
    ?>
    <div class="page-header">
        <h1>&#128193; Categories</h1>
        <div class="page-header-actions"><a href="?page=categories&action=new" class="btn btn-success btn-sm">+ Add
                Category</a></div>
    </div>
    <?php if (isset($_SESSION['flash_msg'])): ?>
    <div
    class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?>"><?= h($_SESSION['flash_msg']) ?></div><?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
    endif; ?>
    <div class="cols-2">
        <div class="lf"><span
                    class="lf-title"><?= ($action === 'edit' && $editCat) ? 'Edit Category' : 'Add New Category' ?></span>
            <form method="POST"
                  action="?page=categories&action=<?= ($action === 'edit' && $editCat) ? 'edit&id=' . (int) $_GET['id'] : 'new' ?>">
                <?= csrfField() ?>
                <div class="form-grid" style="grid-template-columns:1fr">
                    <div class="form-group"><label>Category Name <span class="required-mark">*</span></label><input
                                type="text" name="name" value="<?= h($editCat['name'] ?? $_POST['name'] ?? '') ?>"
                                required></div>
                    <div class="form-group"><label>Parent Category</label>
                        <select name="parent_id">
                            <option value="">-- Top Level --</option>
                            <?php foreach ($categories as $c):
                                if ($editCat && $c['id'] == (int) $_GET['id'])
                                    continue; ?>
                                <option
                                value="<?= $c['id'] ?>" <?= ($editCat['parent_id'] ?? $_POST['parent_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= h(($c['parent_name'] ? '-- ' : '') . $c['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Description</label><textarea
                                name="description"><?= h($editCat['description'] ?? $_POST['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group"><label>Status</label>
                        <select name="status">
                            <option value="active" <?= ($editCat['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>
                                Active
                            </option>
                            <option value="inactive" <?= ($editCat['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>
                                Inactive
                            </option>
                        </select>
                    </div>
                </div>
                <div style="display:flex;gap:8px;margin-top:10px">
                    <button type="submit"
                            class="btn btn-success btn-sm"><?= ($action === 'edit' && $editCat) ? '&#10003; Update' : '+ Create' ?></button>
                    <?php if ($action === 'edit'): ?><a href="?page=categories"
                                                        class="btn btn-secondary btn-sm">Cancel</a><?php endif; ?>
                </div>
            </form>
        </div>
        <div class="lf"><span class="lf-title">All Categories (<?= count($categories) ?>)</span>
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Parent</th>
                        <th>Products</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="5" class="text-center" style="padding:12px;color:#888">No categories yet</td>
                        </tr>
                    <?php else:
        foreach ($categories as $c): ?>
                            <tr>
                                <td><?= $c['parent_id'] ? '&nbsp;&nbsp;&nbsp;↳ ' : '' ?>
                                    <strong><?= h($c['name']) ?></strong></td>
                                <td><?= h($c['parent_name'] ?? '-') ?></td>
                                <td class="text-right"><?= number_format((int) $c['product_count']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $c['status'] === 'active' ? 'success' : 'secondary' ?>"><?= h(strtoupper($c['status'])) ?></span>
                                </td>
                                <td>
                                    <div class="tbl-actions">
                                        <a href="?page=categories&action=edit&id=<?= $c['id'] ?>"
                                           class="btn btn-warning btn-xs">&#9998;</a>
                                        <?php if (canAccess(['admin', 'manager'])): ?>
                                            <button onclick="confirmDeleteCat(<?= $c['id'] ?>,'<?= h(addslashes($c['name'])) ?>')"
                                                    class="btn btn-danger btn-xs">&#128465;
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach;
    endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        function confirmDeleteCat(id, name) {
            confirmAction('Delete category "' + name + '"?', function () {
                window.location = '?page=categories&action=delete&id=' + id + '&confirm=yes';
            });
        }
    </script>
    <?php
    $content = ob_get_clean();
    renderLayout('Categories', $content, 'categories');
}

function renderSuppliers(): void
{
    $action = $_GET['action'] ?? 'list';
    $pdo = getPDO();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
        if ($action === 'new' || $action === 'edit') {
            $editId = !empty($_GET['id']) ? (int) $_GET['id'] : null;
            $res = saveSupplier($_POST, $editId);
            if ($res['success']) {
                $_SESSION['flash_msg'] = $editId ? 'Supplier updated.' : 'Supplier created.';
                $_SESSION['flash_type'] = 'success';
                header('Location: ?page=suppliers');
                exit;
            }
        }
    }
    if ($action === 'delete' && !empty($_GET['id']) && canAccess(['admin', 'manager'])) {
        $res = deleteSupplier((int) $_GET['id']);
        $_SESSION['flash_msg'] = $res['success'] ? 'Supplier deleted.' : ($res['error'] ?? 'Error');
        $_SESSION['flash_type'] = $res['success'] ? 'success' : 'danger';
        header('Location: ?page=suppliers');
        exit;
    }
    if ($action === 'list') {
        $filters = ['search' => $_GET['search'] ?? '', 'status' => $_GET['status'] ?? ''];
        $page = max(1, (int) ($_GET['pg'] ?? 1));
        $result = getSuppliers($filters, $page);
        ob_start();
        ?>
        <div class="page-header"><h1>&#128667; Suppliers</h1>
            <div class="page-header-actions"><a href="?page=suppliers&action=new" class="btn btn-success btn-sm">+ Add
                    Supplier</a></div>
        </div>
        <?php if (isset($_SESSION['flash_msg'])): ?>
        <div
        class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?>"><?= h($_SESSION['flash_msg']) ?></div><?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
        endif; ?>
        <form method="GET"><input type="hidden" name="page" value="suppliers">
            <div class="filter-bar">
                <div class="form-group"><label>Search</label><input type="text" name="search"
                                                                    value="<?= h($filters['search']) ?>"
                                                                    placeholder="Name, contact, email..."></div>
                <div class="form-group"><label>Status</label><select name="status">
                        <option value="">All</option>
                        <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive
                        </option>
                    </select></div>
                <div class="form-group"
                     style="justify-content:flex-end;flex-direction:row;align-items:flex-end;gap:6px">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="?page=suppliers" class="btn btn-secondary btn-sm">Reset</a></div>
            </div>
        </form>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                <tr>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>City</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($result['suppliers'])): ?>
                    <tr>
                        <td colspan="8" class="text-center" style="padding:16px;color:#888">No suppliers found</td>
                    </tr>
                <?php else:
            foreach ($result['suppliers'] as $s): ?>
                        <tr>
                            <td><strong><?= h($s['company_name']) ?></strong></td>
                            <td><?= h($s['contact_person'] ?? '-') ?></td>
                            <td><?= h($s['email'] ?? '-') ?></td>
                            <td><?= h($s['phone'] ?? '-') ?></td>
                            <td><?= h($s['city'] ?? '-') ?></td>
                            <td class="text-right <?= $s['current_balance'] > 0 ? 'stock-critical' : '' ?>"><?= formatCurrency((float) $s['current_balance']) ?></td>
                            <td>
                                <span class="badge badge-<?= $s['status'] === 'active' ? 'success' : 'secondary' ?>"><?= h(strtoupper($s['status'])) ?></span>
                            </td>
                            <td>
                                <div class="tbl-actions">
                                    <a href="?page=suppliers&action=view&id=<?= $s['id'] ?>"
                                       class="btn btn-primary btn-xs">&#128065;</a>
                                    <a href="?page=suppliers&action=edit&id=<?= $s['id'] ?>"
                                       class="btn btn-warning btn-xs">&#9998;</a>
                                    <a href="?page=purchases&action=new&supplier_id=<?= $s['id'] ?>"
                                       class="btn btn-success btn-xs" title="New PO">+PO</a>
                                    <?php if (canAccess(['admin'])): ?>
                                        <button
                                        onclick="confirmDelSupplier(<?= $s['id'] ?>,'<?= h(addslashes($s['company_name'])) ?>')"
                                        class="btn btn-danger btn-xs">&#128465;</button><?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach;
        endif; ?>
                </tbody>
            </table>
        </div>
    <?= paginationHtml($result['pagination'], '?page=suppliers&search=' . urlencode($filters['search']) . '&status=' . urlencode($filters['status'])) ?>
        <script>function confirmDelSupplier(id, n) {
                confirmAction('Delete supplier "' + n + '"?', function () {
                    window.location = '?page=suppliers&action=delete&id=' + id;
                });
            }</script>
    <?php
        $content = ob_get_clean();
        renderLayout('Suppliers', $content, 'suppliers');
    } elseif ($action === 'new' || $action === 'edit') {
        $editId = !empty($_GET['id']) ? (int) $_GET['id'] : null;
        $sup = $editId ? getSupplier($editId) : null;
        $d = $sup ?? [];
        ob_start();
        ?>
        <div class="page-header"><h1><?= $editId ? '&#9998; Edit Supplier' : '+ New Supplier' ?></h1>
            <div class="page-header-actions"><a href="?page=suppliers" class="btn btn-secondary btn-sm">&#8592; Back</a>
            </div>
        </div>
        <form method="POST" action="?page=suppliers&action=<?= $editId ? 'edit&id=' . $editId : 'new' ?>">
            <?= csrfField() ?>
            <div class="lf"><span class="lf-title">Supplier Details</span>
                <div class="form-grid form-grid-3">
                    <div class="form-group" style="grid-column:span 2"><label>Company Name <span
                                    class="required-mark">*</span></label><input type="text" name="company_name"
                                                                                 value="<?= h($d['company_name'] ?? '') ?>"
                                                                                 required></div>
                    <div class="form-group"><label>Contact Person</label><input type="text" name="contact_person"
                                                                                value="<?= h($d['contact_person'] ?? '') ?>">
                    </div>
                    <div class="form-group"><label>Email</label><input type="email" name="email"
                                                                       value="<?= h($d['email'] ?? '') ?>"></div>
                    <div class="form-group"><label>Phone</label><input type="text" name="phone"
                                                                       value="<?= h($d['phone'] ?? '') ?>"></div>
                    <div class="form-group"><label>Tax ID</label><input type="text" name="tax_id"
                                                                        value="<?= h($d['tax_id'] ?? '') ?>"></div>
                    <div class="form-group"><label>Payment Terms</label><input type="text" name="payment_terms"
                                                                               value="<?= h($d['payment_terms'] ?? 'Net 30') ?>"
                                                                               placeholder="e.g. Net 30"></div>
                    <div class="form-group"><label>City</label><input type="text" name="city"
                                                                      value="<?= h($d['city'] ?? '') ?>"></div>
                    <div class="form-group"><label>Country</label><input type="text" name="country"
                                                                         value="<?= h($d['country'] ?? 'Pakistan') ?>">
                    </div>
                    <div class="form-group"><label>Status</label><select name="status">
                            <option value="active" <?= ($d['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>
                                Active
                            </option>
                            <option value="inactive" <?= ($d['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>
                                Inactive
                            </option>
                        </select></div>
                    <div class="form-group full"><label>Address</label><textarea
                                name="address"><?= h($d['address'] ?? '') ?></textarea></div>
                    <div class="form-group full"><label>Notes</label><textarea
                                name="notes"><?= h($d['notes'] ?? '') ?></textarea></div>
                </div>
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-success"><?= $editId ? '&#10003; Update' : '+ Create' ?></button>
                <a href="?page=suppliers" class="btn btn-secondary">Cancel</a></div>
        </form>
    <?php
        $content = ob_get_clean();
        renderLayout(($editId ? 'Edit' : 'New') . ' Supplier', $content, 'suppliers');
    } elseif ($action === 'view') {
        $id = (int) ($_GET['id'] ?? 0);
        $sup = getSupplier($id);
        if (!$sup) {
            header('Location: ?page=suppliers');
            exit;
        }
        $pdo2 = getPDO();
        $poHistory = $pdo2->prepare('SELECT po_number,po_date,total_amount,order_status,payment_status FROM purchase_orders WHERE supplier_id=? ORDER BY po_date DESC LIMIT 10');
        $poHistory->execute([$id]);
        $poHistory = $poHistory->fetchAll();
        ob_start();
        ?>
        <div class="page-header"><h1>&#128065; Supplier: <?= h($sup['company_name']) ?></h1>
            <div class="page-header-actions"><a href="?page=suppliers&action=edit&id=<?= $id ?>"
                                                class="btn btn-warning btn-sm">&#9998; Edit</a><a
                        href="?page=purchases&action=new&supplier_id=<?= $id ?>" class="btn btn-success btn-sm">+ New
                    PO</a><a href="?page=suppliers" class="btn btn-secondary btn-sm">&#8592; Back</a></div>
        </div>
        <div class="cols-2">
            <div class="lf"><span class="lf-title">Supplier Information</span>
                <table style="width:100%;font-size:12px">
                    <tbody>
                    <tr>
                        <td style="padding:4px 0;color:#666;width:40%">Company:</td>
                        <td><strong><?= h($sup['company_name']) ?></strong></td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:#666">Contact:</td>
                        <td><?= h($sup['contact_person'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:#666">Email:</td>
                        <td><?= h($sup['email'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:#666">Phone:</td>
                        <td><?= h($sup['phone'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:#666">Address:</td>
                        <td><?= h($sup['address'] ?? '-') ?>, <?= h($sup['city'] ?? '') ?>
                            , <?= h($sup['country'] ?? '') ?></td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:#666">Tax ID:</td>
                        <td><?= h($sup['tax_id'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:#666">Payment Terms:</td>
                        <td><?= h($sup['payment_terms'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:#666">Outstanding Balance:</td>
                        <td>
                            <strong style="color:<?= $sup['current_balance'] > 0 ? '#d9534f' : '#5cb85c' ?>"><?= formatCurrency((float) $sup['current_balance']) ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:#666">Status:</td>
                        <td>
                            <span class="badge badge-<?= $sup['status'] === 'active' ? 'success' : 'secondary' ?>"><?= h(strtoupper($sup['status'])) ?></span>
                        </td>
                    </tr>
                    <?php if ($sup['notes']): ?>
                        <tr>
                        <td style="padding:4px 0;color:#666">Notes:</td>
                        <td><?= h($sup['notes']) ?></td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="lf"><span class="lf-title">Purchase History</span>
                <div class="tbl-wrap">
                    <table class="tbl">
                        <thead>
                        <tr>
                            <th>PO#</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($poHistory)): ?>
                            <tr>
                                <td colspan="5" class="text-center" style="padding:12px;color:#888">No POs yet</td>
                            </tr>
                        <?php else:
            foreach ($poHistory as $po): ?>
                                <tr>
                                    <td>
                                        <a href="?page=purchases&action=view&id=<?php $pid = $pdo->query("SELECT id FROM purchase_orders WHERE po_number='" . $po['po_number'] . "'")->fetchColumn();
                echo $pid; ?>"><?= h($po['po_number']) ?></a></td>
                                    <td><?= h($po['po_date']) ?></td>
                                    <td class="text-right"><?= formatCurrency((float) $po['total_amount']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $po['order_status'] === 'received' ? 'success' : ($po['order_status'] === 'cancelled' ? 'danger' : 'warning') ?>"><?= h(strtoupper($po['order_status'])) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $po['payment_status'] === 'paid' ? 'success' : ($po['payment_status'] === 'partial' ? 'warning' : 'danger') ?>"><?= h(strtoupper($po['payment_status'])) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach;
        endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
        $content = ob_get_clean();
        renderLayout('Supplier Detail', $content, 'suppliers');
    }
}

function renderCustomers(): void
{
    $action = $_GET['action'] ?? 'list';
    $pdo = getPDO();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
        if ($action === 'new' || $action === 'edit') {
            $editId = !empty($_GET['id']) ? (int) $_GET['id'] : null;
            $res = saveCustomer($_POST, $editId);
            if ($res['success']) {
                $_SESSION['flash_msg'] = $editId ? 'Customer updated.' : 'Customer created.';
                $_SESSION['flash_type'] = 'success';
                header('Location: ?page=customers');
                exit;
            }
        }
    }
    if ($action === 'delete' && !empty($_GET['id']) && canAccess(['admin', 'manager'])) {
        $res = deleteCustomer((int) $_GET['id']);
        $_SESSION['flash_msg'] = $res['success'] ? 'Customer deleted.' : ($res['error'] ?? 'Error');
        $_SESSION['flash_type'] = $res['success'] ? 'success' : 'danger';
        header('Location: ?page=customers');
        exit;
    }
    if ($action === 'list') {
        $filters = ['search' => $_GET['search'] ?? '', 'status' => $_GET['status'] ?? '', 'type' => $_GET['type'] ?? ''];
        $page = max(1, (int) ($_GET['pg'] ?? 1));
        $result = getCustomers($filters, $page);
        ob_start();
        ?>
        <div class="page-header"><h1>&#128101; Customers</h1>
            <div class="page-header-actions"><a href="?page=customers&action=new" class="btn btn-success btn-sm">+ Add
                    Customer</a></div>
        </div>
        <?php if (isset($_SESSION['flash_msg'])): ?>
        <div
        class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?>"><?= h($_SESSION['flash_msg']) ?></div><?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
        endif; ?>
        <form method="GET"><input type="hidden" name="page" value="customers">
            <div class="filter-bar">
                <div class="form-group"><label>Search</label><input type="text" name="search"
                                                                    value="<?= h($filters['search']) ?>"
                                                                    placeholder="Name, email, phone..."></div>
                <div class="form-group"><label>Type</label><select name="type">
                        <option value="">All</option>
                        <option value="walk-in" <?= $filters['type'] === 'walk-in' ? 'selected' : '' ?>>Walk-in</option>
                        <option value="regular" <?= $filters['type'] === 'regular' ? 'selected' : '' ?>>Regular</option>
                        <option value="wholesale" <?= $filters['type'] === 'wholesale' ? 'selected' : '' ?>>Wholesale
                        </option>
                    </select></div>
                <div class="form-group"><label>Status</label><select name="status">
                        <option value="">All</option>
                        <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive
                        </option>
                    </select></div>
                <div class="form-group"
                     style="justify-content:flex-end;flex-direction:row;align-items:flex-end;gap:6px">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="?page=customers" class="btn btn-secondary btn-sm">Reset</a></div>
            </div>
        </form>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Type</th>
                    <th>Credit Limit</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($result['customers'])): ?>
                    <tr>
                        <td colspan="8" class="text-center" style="padding:16px;color:#888">No customers found</td>
                    </tr>
                <?php else:
            foreach ($result['customers'] as $c):
                $typeBadge = ['walk-in' => 'secondary', 'regular' => 'info', 'wholesale' => 'primary'][$c['customer_type']] ?? 'secondary'; ?>
                        <tr>
                            <td><strong><?= h($c['name']) ?></strong></td>
                            <td><?= h($c['email'] ?? '-') ?></td>
                            <td><?= h($c['phone'] ?? '-') ?></td>
                            <td>
                                <span class="badge badge-<?= $typeBadge ?>"><?= h(strtoupper($c['customer_type'])) ?></span>
                            </td>
                            <td class="text-right"><?= formatCurrency((float) $c['credit_limit']) ?></td>
                            <td class="text-right <?= $c['current_balance'] > 0 ? 'stock-critical' : '' ?>"><?= formatCurrency((float) $c['current_balance']) ?></td>
                            <td>
                                <span class="badge badge-<?= $c['status'] === 'active' ? 'success' : 'secondary' ?>"><?= h(strtoupper($c['status'])) ?></span>
                            </td>
                            <td>
                                <div class="tbl-actions">
                                    <a href="?page=customers&action=view&id=<?= $c['id'] ?>"
                                       class="btn btn-primary btn-xs">&#128065;</a>
                                    <a href="?page=customers&action=edit&id=<?= $c['id'] ?>"
                                       class="btn btn-warning btn-xs">&#9998;</a>
                                    <a href="?page=sales&action=new&customer_id=<?= $c['id'] ?>"
                                       class="btn btn-success btn-xs" title="New Invoice">+INV</a>
                                    <?php if (canAccess(['admin'])): ?>
                                        <button
                                        onclick="confirmDelCust(<?= $c['id'] ?>,'<?= h(addslashes($c['name'])) ?>')"
                                        class="btn btn-danger btn-xs">&#128465;</button><?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach;
        endif; ?>
                </tbody>
            </table>
        </div>
    <?= paginationHtml($result['pagination'], '?page=customers&search=' . urlencode($filters['search']) . '&status=' . urlencode($filters['status']) . '&type=' . urlencode($filters['type'])) ?>
        <script>function confirmDelCust(id, n) {
                confirmAction('Delete customer "' + n + '"?', function () {
                    window.location = '?page=customers&action=delete&id=' + id;
                });
            }</script>
    <?php
        $content = ob_get_clean();
        renderLayout('Customers', $content, 'customers');
    } elseif ($action === 'new' || $action === 'edit') {
        $editId = !empty($_GET['id']) ? (int) $_GET['id'] : null;
        $cust = $editId ? getCustomer($editId) : null;
        $d = $cust ?? [];
        ob_start();
        ?>
        <div class="page-header"><h1><?= $editId ? '&#9998; Edit Customer' : '+ New Customer' ?></h1>
            <div class="page-header-actions"><a href="?page=customers" class="btn btn-secondary btn-sm">&#8592; Back</a>
            </div>
        </div>
        <form method="POST" action="?page=customers&action=<?= $editId ? 'edit&id=' . $editId : 'new' ?>">
            <?= csrfField() ?>
            <div class="lf"><span class="lf-title">Customer Details</span>
                <div class="form-grid form-grid-3">
                    <div class="form-group" style="grid-column:span 2"><label>Full Name <span
                                    class="required-mark">*</span></label><input type="text" name="name"
                                                                                 value="<?= h($d['name'] ?? '') ?>"
                                                                                 required></div>
                    <div class="form-group"><label>Customer Type</label><select name="customer_type">
                            <option value="walk-in" <?= ($d['customer_type'] ?? 'walk-in') === 'walk-in' ? 'selected' : '' ?>>
                                Walk-in
                            </option>
                            <option value="regular" <?= ($d['customer_type'] ?? '') === 'regular' ? 'selected' : '' ?>>
                                Regular
                            </option>
                            <option value="wholesale" <?= ($d['customer_type'] ?? '') === 'wholesale' ? 'selected' : '' ?>>
                                Wholesale
                            </option>
                        </select></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email"
                                                                       value="<?= h($d['email'] ?? '') ?>"></div>
                    <div class="form-group"><label>Phone</label><input type="text" name="phone"
                                                                       value="<?= h($d['phone'] ?? '') ?>"></div>
                    <div class="form-group"><label>Credit Limit</label><input type="number" name="credit_limit"
                                                                              step="0.01" min="0"
                                                                              value="<?= h($d['credit_limit'] ?? '0') ?>">
                    </div>
                    <div class="form-group"><label>City</label><input type="text" name="city"
                                                                      value="<?= h($d['city'] ?? '') ?>"></div>
                    <div class="form-group"><label>Country</label><input type="text" name="country"
                                                                         value="<?= h($d['country'] ?? 'Pakistan') ?>">
                    </div>
                    <div class="form-group"><label>Status</label><select name="status">
                            <option value="active" <?= ($d['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>
                                Active
                            </option>
                            <option value="inactive" <?= ($d['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>
                                Inactive
                            </option>
                        </select></div>
                    <div class="form-group full"><label>Address</label><textarea
                                name="address"><?= h($d['address'] ?? '') ?></textarea></div>
                    <div class="form-group full"><label>Notes</label><textarea
                                name="notes"><?= h($d['notes'] ?? '') ?></textarea></div>
                </div>
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-success"><?= $editId ? '&#10003; Update' : '+ Create' ?></button>
                <a href="?page=customers" class="btn btn-secondary">Cancel</a></div>
        </form>
    <?php
        $content = ob_get_clean();
        renderLayout(($editId ? 'Edit' : 'New') . ' Customer', $content, 'customers');
    } elseif ($action === 'view') {
        $id = (int) ($_GET['id'] ?? 0);
        $cust = getCustomer($id);
        if (!$cust) {
            header('Location: ?page=customers');
            exit;
        }
        $invHistory = $pdo->prepare('SELECT invoice_number,invoice_date,total_amount,payment_status,payment_method FROM invoices WHERE customer_id=? ORDER BY invoice_date DESC LIMIT 10');
        $invHistory->execute([$id]);
        $invHistory = $invHistory->fetchAll();
        $totalSpent = (float) $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM invoices WHERE customer_id=$id")->fetchColumn();
        ob_start();
        ?>
        <div class="page-header"><h1>&#128065; Customer: <?= h($cust['name']) ?></h1>
            <div class="page-header-actions"><a href="?page=customers&action=edit&id=<?= $id ?>"
                                                class="btn btn-warning btn-sm">&#9998; Edit</a><a
                        href="?page=sales&action=new&customer_id=<?= $id ?>" class="btn btn-success btn-sm">+ New
                    Invoice</a><a href="?page=customers" class="btn btn-secondary btn-sm">&#8592; Back</a></div>
        </div>
        <div class="cols-2">
            <div class="lf"><span class="lf-title">Customer Information</span>
                <table style="width:100%;font-size:12px">
                    <tbody>
                    <tr>
                        <td style="padding:4px 0;color:#666;width:40%">Name:</td>
                        <td><strong><?= h($cust['name']) ?></strong></td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:#666">Email:</td>
                        <td><?= h($cust['email'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:#666">Phone:</td>
                        <td><?= h($cust['phone'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:#666">Type:</td>
                        <td><?= h(strtoupper($cust['customer_type'])) ?></td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:#666">Address:</td>
                        <td><?= h($cust['address'] ?? '-') ?>, <?= h($cust['city'] ?? '') ?></td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:#666">Credit Limit:</td>
                        <td><?= formatCurrency((float) $cust['credit_limit']) ?></td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:#666">Outstanding Balance:</td>
                        <td>
                            <strong style="color:<?= $cust['current_balance'] > 0 ? '#d9534f' : '#5cb85c' ?>"><?= formatCurrency((float) $cust['current_balance']) ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:#666">Total Spent:</td>
                        <td><strong style="color:#5cb85c"><?= formatCurrency($totalSpent) ?></strong></td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:#666">Status:</td>
                        <td>
                            <span class="badge badge-<?= $cust['status'] === 'active' ? 'success' : 'secondary' ?>"><?= h(strtoupper($cust['status'])) ?></span>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="lf"><span class="lf-title">Purchase History</span>
                <div class="tbl-wrap">
                    <table class="tbl">
                        <thead>
                        <tr>
                            <th>Invoice#</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Payment</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($invHistory)): ?>
                            <tr>
                                <td colspan="4" class="text-center" style="padding:12px;color:#888">No invoices yet</td>
                            </tr>
                        <?php else:
            foreach ($invHistory as $inv): ?>
                                <tr>
                                    <td><?= h($inv['invoice_number']) ?></td>
                                    <td><?= h($inv['invoice_date']) ?></td>
                                    <td class="text-right"><?= formatCurrency((float) $inv['total_amount']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $inv['payment_status'] === 'paid' ? 'success' : ($inv['payment_status'] === 'partial' ? 'warning' : 'danger') ?>"><?= h(strtoupper($inv['payment_status'])) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach;
        endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
        $content = ob_get_clean();
        renderLayout('Customer Detail', $content, 'customers');
    }
}

function renderUsers(): void
{
    requireRole(['admin']);
    $action = $_GET['action'] ?? 'list';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
        if ($action === 'new' || $action === 'edit') {
            $editId = !empty($_GET['id']) ? (int) $_GET['id'] : null;
            $res = saveUser($_POST, $editId);
            if ($res['success']) {
                $_SESSION['flash_msg'] = $editId ? 'User updated.' : 'User created.';
                $_SESSION['flash_type'] = 'success';
                header('Location: ?page=users');
                exit;
            } else {
                $_SESSION['flash_msg'] = implode(' ', $res['errors'] ?? [$res['error'] ?? 'Error']);
                $_SESSION['flash_type'] = 'danger';
            }
        }
        if ($action === 'delete' && !empty($_POST['id'])) {
            $delId = (int) $_POST['id'];
            if ($delId === $_SESSION['user_id']) {
                $_SESSION['flash_msg'] = 'Cannot delete your own account.';
                $_SESSION['flash_type'] = 'danger';
            } else {
                getPDO()->prepare('DELETE FROM users WHERE id=?')->execute([$delId]);
                logActivity($_SESSION['user_id'], 'DELETE', 'users', 'Deleted user id ' . $delId);
                $_SESSION['flash_msg'] = 'User deleted.';
                $_SESSION['flash_type'] = 'success';
            }
            header('Location: ?page=users');
            exit;
        }
    }
    if ($action === 'list') {
        $page = max(1, (int) ($_GET['pg'] ?? 1));
        $result = getUsers($page);
        ob_start();
        ?>
        <div class="page-header"><h1>&#128101; User Management</h1>
            <div class="page-header-actions"><a href="?page=users&action=new" class="btn btn-success btn-sm">+ Add
                    User</a></div>
        </div>
        <?php if (isset($_SESSION['flash_msg'])): ?>
        <div
        class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?>"><?= h($_SESSION['flash_msg']) ?></div><?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
        endif; ?>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                <tr>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($result['users'])): ?>
                    <tr>
                        <td colspan="7" class="text-center" style="padding:16px;color:#888">No users</td>
                    </tr>
                <?php else:
            foreach ($result['users'] as $u):
                $roleBadge = ['admin' => 'danger', 'manager' => 'warning', 'staff' => 'info'][$u['role']] ?? 'secondary'; ?>
                        <tr>
                            <td><strong><?= h($u['username']) ?></strong></td>
                            <td><?= h($u['full_name']) ?></td>
                            <td><?= h($u['email']) ?></td>
                            <td><span class="badge badge-<?= $roleBadge ?>"><?= h(strtoupper($u['role'])) ?></span></td>
                            <td>
                                <span class="badge badge-<?= $u['status'] === 'active' ? 'success' : 'secondary' ?>"><?= h(strtoupper($u['status'])) ?></span>
                            </td>
                            <td><?= h($u['last_login'] ?? 'Never') ?></td>
                            <td>
                                <div class="tbl-actions">
                                    <a href="?page=users&action=edit&id=<?= $u['id'] ?>" class="btn btn-warning btn-xs">&#9998;</a>
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <button onclick="confirmDelUser(<?= $u['id'] ?>,'<?= h(addslashes($u['username'])) ?>')"
                                                class="btn btn-danger btn-xs">&#128465;
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach;
        endif; ?>
                </tbody>
            </table>
        </div>
    <?= paginationHtml($result['pagination'], '?page=users') ?>
        <form id="del-user-form" method="POST" action="?page=users&action=delete"
              style="display:none"><?= csrfField() ?><input type="hidden" name="id" id="del-user-id"></form>
        <script>function confirmDelUser(id, n) {
                confirmAction('Delete user "' + n + '"? All their activity logs will be removed.', function () {
                    document.getElementById('del-user-id').value = id;
                    document.getElementById('del-user-form').submit();
                });
            }</script>
    <?php
        $content = ob_get_clean();
        renderLayout('Users', $content, 'users');
    } elseif ($action === 'new' || $action === 'edit') {
        $editId = !empty($_GET['id']) ? (int) $_GET['id'] : null;
        $usr = $editId ? getUser($editId) : null;
        $d = $usr ?? [];
        ob_start();
        ?>
        <div class="page-header"><h1><?= $editId ? '&#9998; Edit User' : '+ New User' ?></h1>
            <div class="page-header-actions"><a href="?page=users" class="btn btn-secondary btn-sm">&#8592; Back</a>
            </div>
        </div>
        <?php if (isset($_SESSION['flash_msg'])): ?>
        <div
        class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?>"><?= h($_SESSION['flash_msg']) ?></div><?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
        endif; ?>
        <form method="POST" action="?page=users&action=<?= $editId ? 'edit&id=' . $editId : 'new' ?>">
            <?= csrfField() ?>
            <div class="lf"><span class="lf-title">User Account</span>
                <div class="form-grid form-grid-3">
                    <div class="form-group"><label>Username <span class="required-mark">*</span></label><input
                                type="text" name="username" value="<?= h($d['username'] ?? '') ?>" required></div>
                    <div class="form-group" style="grid-column:span 2"><label>Full Name <span
                                    class="required-mark">*</span></label><input type="text" name="full_name"
                                                                                 value="<?= h($d['full_name'] ?? '') ?>"
                                                                                 required></div>
                    <div class="form-group" style="grid-column:span 2"><label>Email <span class="required-mark">*</span></label><input
                                type="email" name="email" value="<?= h($d['email'] ?? '') ?>" required></div>
                    <div class="form-group"><label>Role</label><select name="role">
                            <option value="staff" <?= ($d['role'] ?? 'staff') === 'staff' ? 'selected' : '' ?>>Staff
                            </option>
                            <option value="manager" <?= ($d['role'] ?? '') === 'manager' ? 'selected' : '' ?>>Manager
                            </option>
                            <option value="admin" <?= ($d['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select></div>
                    <div class="form-group">
                        <label><?= $editId ? 'New Password (leave blank to keep)' : 'Password' ?> <?php if (!$editId): ?>
                                <span class="required-mark">*</span><?php endif; ?></label><input type="password"
                                                                                                  name="password" <?= !$editId ? 'required' : '' ?>
                                                                                                  autocomplete="new-password"
                                                                                                  placeholder="Min 8 chars, 1 special char">
                    </div>
                    <div class="form-group"><label>Confirm Password</label><input type="password"
                                                                                  name="password_confirm"
                                                                                  autocomplete="new-password"></div>
                    <div class="form-group"><label>Status</label><select name="status">
                            <option value="active" <?= ($d['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>
                                Active
                            </option>
                            <option value="inactive" <?= ($d['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>
                                Inactive
                            </option>
                        </select></div>
                </div>
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-success"><?= $editId ? '&#10003; Update' : '+ Create' ?></button>
                <a href="?page=users" class="btn btn-secondary">Cancel</a></div>
        </form>
        <?php
        $content = ob_get_clean();
        renderLayout(($editId ? 'Edit' : 'New') . ' User', $content, 'users');
    }
}

function renderProfile(): void
{
    $action = $_GET['action'] ?? 'view';
    $userId = (int) $_SESSION['user_id'];
    $pdo = getPDO();
    $user = $pdo->prepare('SELECT * FROM users WHERE id=?');
    $user->execute([$userId]);
    $user = $user->fetch();
    $msg = '';
    $msgType = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
        if ($action === 'update') {
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            if (empty($fullName) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $msg = 'Valid name and email required.';
                $msgType = 'danger';
            } else {
                $chk = $pdo->prepare('SELECT id FROM users WHERE email=? AND id!=?');
                $chk->execute([$email, $userId]);
                if ($chk->fetch()) {
                    $msg = 'Email already in use.';
                    $msgType = 'danger';
                } else {
                    $pdo->prepare('UPDATE users SET full_name=?,email=? WHERE id=?')->execute([$fullName, $email, $userId]);
                    $_SESSION['user_name'] = $fullName;
                    $_SESSION['user_email'] = $email;
                    $msg = 'Profile updated.';
                    $msgType = 'success';
                }
            }
        } elseif ($action === 'change_password') {
            $res = changePassword($userId, $_POST['current_password'] ?? '', $_POST['new_password'] ?? '');
            $msg = $res['success'] ? 'Password changed successfully.' : ($res['error'] ?? 'Error');
            $msgType = $res['success'] ? 'success' : 'danger';
        }
    }
    ob_start();
    ?>
    <div class="page-header"><h1>&#128100; My Profile</h1></div>
    <?php if ($msg): ?>
    <div class="alert alert-<?= h($msgType) ?>"><?= h($msg) ?></div><?php endif; ?>
    <div class="cols-2">
        <div class="lf"><span class="lf-title">Profile Information</span>
            <form method="POST" action="?page=profile&action=update">
                <?= csrfField() ?>
                <div class="form-grid" style="grid-template-columns:1fr">
                    <div class="form-group"><label>Username</label><input type="text"
                                                                          value="<?= h($user['username']) ?>" readonly
                                                                          style="background:#e8e8e8;color:#666"></div>
                    <div class="form-group"><label>Full Name <span class="required-mark">*</span></label><input
                                type="text" name="full_name" value="<?= h($user['full_name']) ?>" required></div>
                    <div class="form-group"><label>Email <span class="required-mark">*</span></label><input type="email"
                                                                                                            name="email"
                                                                                                            value="<?= h($user['email']) ?>"
                                                                                                            required>
                    </div>
                    <div class="form-group"><label>Role</label><input type="text"
                                                                      value="<?= h(ucfirst($user['role'])) ?>" readonly
                                                                      style="background:#e8e8e8;color:#666"></div>
                    <div class="form-group"><label>Last Login</label><input type="text"
                                                                            value="<?= h($user['last_login'] ?? 'Never') ?>"
                                                                            readonly
                                                                            style="background:#e8e8e8;color:#666"></div>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top:10px">&#10003; Update Profile</button>
            </form>
        </div>
        <div class="lf"><span class="lf-title">Change Password</span>
            <form method="POST" action="?page=profile&action=change_password">
                <?= csrfField() ?>
                <div class="form-grid" style="grid-template-columns:1fr">
                    <div class="form-group"><label>Current Password <span class="required-mark">*</span></label><input
                                type="password" name="current_password" required autocomplete="current-password"></div>
                    <div class="form-group"><label>New Password <span class="required-mark">*</span></label><input
                                type="password" name="new_password" required autocomplete="new-password"
                                placeholder="Min 8 chars, 1 special char"></div>
                    <div class="form-group"><label>Confirm New Password <span
                                    class="required-mark">*</span></label><input type="password"
                                                                                 name="new_password_confirm" required
                                                                                 autocomplete="new-password"></div>
                </div>
                <div style="font-size:11px;color:#666;margin:6px 0">Requirements: min 8 characters, at least 1 special
                    character (!@#$%^&*)
                </div>
                <button type="submit" class="btn btn-warning" style="margin-top:6px">&#128274; Change Password</button>
            </form>
        </div>
    </div>
    <?php
    $content = ob_get_clean();
    renderLayout('My Profile', $content, 'profile');
}

function renderPurchases(): void
{
    $action = $_GET['action'] ?? 'list';
    $pdo = getPDO();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment']) && verifyCsrf()) {
        $poId = (int) $_POST['po_id'];
        $amt = (float) $_POST['amount'];
        $method = $_POST['method'];
        $ref = $_POST['reference'];
        $po = $pdo->prepare('SELECT * FROM purchase_orders WHERE id=?');
        $po->execute([$poId]);
        $po = $po->fetch();
        if ($po && $amt > 0) {
            $pdo->beginTransaction();
            try {
                $rn = generateRefNumber('REC');
                $pdo
                    ->prepare('INSERT INTO payment_receipts (receipt_number, supplier_id, po_id, amount, payment_date, method, reference, created_by) VALUES (?,?,?,?,CURDATE(),?,?,?)')
                    ->execute([$rn, $po['supplier_id'], $poId, $amt, $method, $ref, $_SESSION['user_id']]);
                $recId = $pdo->lastInsertId();
                updateSupplierLedger($po['supplier_id'], 'payment', $recId, $amt, 0, "Payment for PO #{$po['po_number']}");
                $initialPayment = (float) $pdo->query("SELECT debit FROM supplier_ledgers WHERE reference_type='po' AND reference_id=$poId")->fetchColumn();
                $subsequentPayments = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payment_receipts WHERE po_id=$poId")->fetchColumn();
                $totalPaid = $initialPayment + $subsequentPayments;
                $newStatus = ($totalPaid >= $po['total_amount']) ? 'paid' : 'partial';
                $pdo->prepare('UPDATE purchase_orders SET payment_status=? WHERE id=?')->execute([$newStatus, $poId]);
                $pdo->commit();
                $_SESSION['flash_msg'] = 'Payment of ' . formatCurrency($amt) . ' recorded.';
                $_SESSION['flash_type'] = 'success';
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['flash_msg'] = 'Error recording payment: ' . $e->getMessage();
                $_SESSION['flash_type'] = 'danger';
            }
        }
        header("Location: ?page=purchases&action=view&id=$poId");
        exit;
    }
    if ($action === 'list') {
        $filters = ['search' => $_GET['search'] ?? '', 'order_status' => $_GET['order_status'] ?? '', 'payment_status' => $_GET['payment_status'] ?? '', 'supplier_id' => $_GET['supplier_id'] ?? '', 'date_from' => $_GET['date_from'] ?? '', 'date_to' => $_GET['date_to'] ?? ''];
        $page = max(1, (int) ($_GET['pg'] ?? 1));
        $perPage = (int) (getSetting('items_per_page') ?? 20);
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['search'])) {
            $where[] = '(po.po_number LIKE ? OR s.company_name LIKE ?)';
            $s = '%' . $filters['search'] . '%';
            $params[] = $s;
            $params[] = $s;
        }
        if (!empty($filters['order_status'])) {
            $where[] = 'po.order_status=?';
            $params[] = $filters['order_status'];
        }
        if (!empty($filters['payment_status'])) {
            $where[] = 'po.payment_status=?';
            $params[] = $filters['payment_status'];
        }
        if (!empty($filters['supplier_id'])) {
            $where[] = 'po.supplier_id=?';
            $params[] = (int) $filters['supplier_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'po.po_date>=?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'po.po_date<=?';
            $params[] = $filters['date_to'];
        }
        $whereStr = implode(' AND ', $where);
        $cnt = $pdo->prepare("SELECT COUNT(*) FROM purchase_orders po JOIN suppliers s ON s.id=po.supplier_id WHERE $whereStr");
        $cnt->execute($params);
        $total = (int) $cnt->fetchColumn();
        $pag = paginate($total, $perPage, $page);
        $stmt = $pdo->prepare("SELECT po.*,s.company_name as supplier_name,u.full_name as created_by_name FROM purchase_orders po JOIN suppliers s ON s.id=po.supplier_id JOIN users u ON u.id=po.created_by WHERE $whereStr ORDER BY po.created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute(array_merge($params, [$pag['per_page'], $pag['offset']]));
        $pos = $stmt->fetchAll();
        $suppliers = $pdo->query("SELECT id,company_name FROM suppliers WHERE status='active' ORDER BY company_name")->fetchAll();
        ob_start();
        ?>
        <div class="page-header"><h1>&#128722; Purchase Orders</h1>
            <div class="page-header-actions"><?php if (canAccess(['admin', 'manager', 'staff'])): ?><a
                        href="?page=purchases&action=new" class="btn btn-success btn-sm">+ New PO</a><?php endif; ?>
            </div>
        </div>
        <?php if (isset($_SESSION['flash_msg'])): ?>
        <div
        class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?>"><?= h($_SESSION['flash_msg']) ?></div><?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
        endif; ?>
        <form method="GET"><input type="hidden" name="page" value="purchases">
            <div class="filter-bar">
                <div class="form-group"><label>Search</label><input type="text" name="search"
                                                                    value="<?= h($filters['search']) ?>"
                                                                    placeholder="PO#, supplier..."></div>
                <div class="form-group"><label>Supplier</label><select name="supplier_id">
                        <option value="">All Suppliers</option><?php foreach ($suppliers as $s): ?>
                            <option
                            value="<?= $s['id'] ?>" <?= $filters['supplier_id'] == $s['id'] ? 'selected' : '' ?>><?= h($s['company_name']) ?></option><?php endforeach; ?>
                    </select></div>
                <div class="form-group"><label>Order Status</label><select name="order_status">
                        <option value="">All</option>
                        <option value="pending" <?= $filters['order_status'] === 'pending' ? 'selected' : '' ?>>
                            Pending
                        </option>
                        <option value="partial" <?= $filters['order_status'] === 'partial' ? 'selected' : '' ?>>
                            Partial
                        </option>
                        <option value="received" <?= $filters['order_status'] === 'received' ? 'selected' : '' ?>>
                            Received
                        </option>
                        <option value="cancelled" <?= $filters['order_status'] === 'cancelled' ? 'selected' : '' ?>>
                            Cancelled
                        </option>
                    </select></div>
                <div class="form-group"><label>Payment</label><select name="payment_status">
                        <option value="">All</option>
                        <option value="unpaid" <?= $filters['payment_status'] === 'unpaid' ? 'selected' : '' ?>>Unpaid
                        </option>
                        <option value="partial" <?= $filters['payment_status'] === 'partial' ? 'selected' : '' ?>>
                            Partial
                        </option>
                        <option value="paid" <?= $filters['payment_status'] === 'paid' ? 'selected' : '' ?>>Paid
                        </option>
                    </select></div>
                <div class="form-group"><label>From</label><input type="date" name="date_from"
                                                                  value="<?= h($filters['date_from']) ?>"></div>
                <div class="form-group"><label>To</label><input type="date" name="date_to"
                                                                value="<?= h($filters['date_to']) ?>"></div>
                <div class="form-group"
                     style="justify-content:flex-end;flex-direction:row;align-items:flex-end;gap:6px">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="?page=purchases" class="btn btn-secondary btn-sm">Reset</a></div>
            </div>
        </form>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                <tr>
                    <th>PO#</th>
                    <th>Supplier</th>
                    <th>PO Date</th>
                    <th>Expected</th>
                    <th>Total</th>
                    <th>Order Status</th>
                    <th>Payment</th>
                    <th>Created By</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($pos)): ?>
                    <tr>
                        <td colspan="9" class="text-center" style="padding:16px;color:#888">No purchase orders found
                        </td>
                    </tr>
                <?php else:
            foreach ($pos as $po):
                $osBadge = ['pending' => 'warning', 'partial' => 'info', 'received' => 'success', 'cancelled' => 'danger'][$po['order_status']] ?? 'secondary';
                $psBadge = ['unpaid' => 'danger', 'partial' => 'warning', 'paid' => 'success'][$po['payment_status']] ?? 'secondary'; ?>
                        <tr>
                            <td><a href="?page=purchases&action=view&id=<?= $po['id'] ?>"><?= h($po['po_number']) ?></a>
                            </td>
                            <td><?= h($po['supplier_name']) ?></td>
                            <td><?= h($po['po_date']) ?></td>
                            <td><?= h($po['expected_delivery'] ?? '-') ?></td>
                            <td class="text-right"><?= formatCurrency((float) $po['total_amount']) ?></td>
                            <td>
                                <span class="badge badge-<?= $osBadge ?>"><?= h(strtoupper($po['order_status'])) ?></span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $psBadge ?>"><?= h(strtoupper($po['payment_status'])) ?></span>
                            </td>
                            <td><?= h($po['created_by_name']) ?></td>
                            <td>
                                <div class="tbl-actions">
                                    <a href="?page=purchases&action=view&id=<?= $po['id'] ?>"
                                       class="btn btn-primary btn-xs">&#128065;</a>
                                    <?php if ($po['order_status'] !== 'received' && $po['order_status'] !== 'cancelled'): ?>
                                        <a href="?page=purchases&action=edit&id=<?= $po['id'] ?>"
                                           class="btn btn-warning btn-xs">&#9998;</a>
                                        <a href="?page=purchases&action=receive&id=<?= $po['id'] ?>"
                                           class="btn btn-success btn-xs" title="Receive Stock">&#8595;Rcv</a>
                                    <?php endif; ?>
                                    <?php if ($po['order_status'] === 'pending' && canAccess(['admin', 'manager'])): ?>
                                        <button onclick="cancelPO(<?= $po['id'] ?>)" class="btn btn-danger btn-xs">
                                            &#215;Cancel
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach;
        endif; ?>
                </tbody>
            </table>
        </div>
    <?= paginationHtml($pag, '?page=purchases&search=' . urlencode($filters['search']) . '&order_status=' . urlencode($filters['order_status']) . '&payment_status=' . urlencode($filters['payment_status'])) ?>
        <script>
            function cancelPO(id) {
                confirmAction('Cancel this purchase order?', function () {
                    apiPost('?ajax=cancel_po', {id: id}, function () {
                        showToast('PO cancelled.', 'success');
                        setTimeout(function () {
                            location.reload();
                        }, 800);
                    });
                }, 'Confirm Cancel');
            }
        </script>
    <?php
        $content = ob_get_clean();
        renderLayout('Purchase Orders', $content, 'purchases');
    } elseif ($action === 'new' || $action === 'edit') {
        $editId = !empty($_GET['id']) ? (int) $_GET['id'] : null;
        $po = null;
        $poItems = [];
        if ($editId) {
            $poQ = $pdo->prepare('SELECT po.*,s.company_name FROM purchase_orders po JOIN suppliers s ON s.id=po.supplier_id WHERE po.id=?');
            $poQ->execute([$editId]);
            $po = $poQ->fetch();
            $poItems = $pdo->prepare('SELECT poi.*,p.name as product_name,p.sku FROM purchase_order_items poi JOIN products p ON p.id=poi.product_id WHERE poi.po_id=?');
            $poItems->execute([$editId]);
            $poItems = $poItems->fetchAll();
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
            $pdo->beginTransaction();
            try {
                $supplierId = (int) ($_POST['supplier_id'] ?? 0);
                $poDate = trim($_POST['po_date'] ?? date('Y-m-d'));
                $expectedDelivery = trim($_POST['expected_delivery'] ?? '') ?: null;
                $taxPct = (float) ($_POST['tax_percent'] ?? 0);
                $discount = (float) ($_POST['discount'] ?? 0);
                $payStatus = $_POST['payment_status'] ?? 'unpaid';
                $notes = trim($_POST['notes'] ?? '');
                $productIds = $_POST['product_id'] ?? [];
                $quantities = $_POST['quantity'] ?? [];
                $unitPrices = $_POST['unit_price'] ?? [];
                if (empty($productIds) || !$supplierId)
                    throw new Exception('Supplier and at least one product required.');
                $subtotal = 0;
                foreach ($productIds as $i => $pid) {
                    $qty = (int) ($quantities[$i] ?? 0);
                    $up = (float) ($unitPrices[$i] ?? 0);
                    if ($qty > 0 && $up >= 0)
                        $subtotal += ($qty * $up);
                }
                $taxAmt = $subtotal * ($taxPct / 100);
                $total = $subtotal + $taxAmt - $discount;
                if ($editId) {
                    $pdo->prepare('UPDATE purchase_orders SET supplier_id=?,po_date=?,expected_delivery=?,subtotal=?,tax_percent=?,discount=?,total_amount=?,payment_status=?,notes=? WHERE id=?')->execute([$supplierId, $poDate, $expectedDelivery, $subtotal, $taxPct, $discount, $total, $payStatus, $notes, $editId]);
                    $pdo->prepare('DELETE FROM purchase_order_items WHERE po_id=?')->execute([$editId]);
                } else {
                    $poNum = generatePoNumber();
                    $pdo->prepare("INSERT INTO purchase_orders (po_number,supplier_id,po_date,expected_delivery,subtotal,tax_percent,discount,total_amount,payment_status,order_status,notes,created_by) VALUES (?,?,?,?,?,?,?,?,'unpaid','pending',?,?)")->execute([$poNum, $supplierId, $poDate, $expectedDelivery, $subtotal, $taxPct, $discount, $total, $notes, $_SESSION['user_id']]);
                    $editId = (int) $pdo->lastInsertId();
                }
                foreach ($productIds as $i => $pid) {
                    $qty = (int) ($quantities[$i] ?? 0);
                    $up = (float) ($unitPrices[$i] ?? 0);
                    if ($qty > 0 && $pid)
                        $pdo->prepare('INSERT INTO purchase_order_items (po_id,product_id,quantity,unit_price,total_price) VALUES (?,?,?,?,?)')->execute([$editId, (int) $pid, $qty, $up, $qty * $up]);
                }
                if ($payStatus === 'paid') {
                    updateSupplierLedger($supplierId, 'po', $editId, $total, $total, "Paid PO #$poNum");
                } else {
                    updateSupplierLedger($supplierId, 'po', $editId, 0, $total, "PO #$poNum");
                }
                $pdo->commit();
                logActivity($_SESSION['user_id'], $po ? 'UPDATE' : 'CREATE', 'purchases', ($po ? 'Updated' : 'Created') . ' PO id ' . $editId);
                $_SESSION['flash_msg'] = 'Purchase order ' . ($po ? 'updated' : 'created') . '.';
                $_SESSION['flash_type'] = 'success';
                header('Location: ?page=purchases&action=view&id=' . $editId);
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['flash_msg'] = 'Error: ' . $e->getMessage();
                $_SESSION['flash_type'] = 'danger';
            }
        }
        $suppliers = $pdo->query("SELECT id,company_name FROM suppliers WHERE status='active' ORDER BY company_name")->fetchAll();
        $products = $pdo->query("SELECT id,name,sku,purchase_price,current_stock FROM products WHERE status='active' ORDER BY name")->fetchAll();
        $preSupplier = (int) ($_GET['supplier_id'] ?? $po['supplier_id'] ?? 0);
        ob_start();
        ?>
        <div class="page-header">
            <h1><?= $po ? '&#9998; Edit PO: ' . h($po['po_number']) : '+ New Purchase Order' ?></h1>
            <div class="page-header-actions"><a href="?page=purchases" class="btn btn-secondary btn-sm">&#8592; Back</a>
            </div>
        </div>
        <?php if (isset($_SESSION['flash_msg'])): ?>
        <div
        class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?>"><?= h($_SESSION['flash_msg']) ?></div><?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
        endif; ?>
        <form method="POST" action="?page=purchases&action=<?= $po ? 'edit&id=' . $po['id'] : 'new' ?>" id="po-form">
            <?= csrfField() ?>
            <div class="lf"><span class="lf-title">Order Details</span>
                <div class="form-grid form-grid-3">
                    <div class="form-group" style="grid-column:span 2"><label>Supplier <span
                                    class="required-mark">*</span></label><select name="supplier_id" required>
                            <option value="">-- Select Supplier --</option><?php foreach ($suppliers as $s): ?>
                                <option
                                value="<?= $s['id'] ?>" <?= ($po['supplier_id'] ?? $preSupplier) == $s['id'] ? 'selected' : '' ?>><?= h($s['company_name']) ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="form-group"><label>PO Date <span class="required-mark">*</span></label><input
                                type="date" name="po_date" value="<?= h($po['po_date'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    <div class="form-group"><label>Expected Delivery</label><input type="date" name="expected_delivery"
                                                                                   value="<?= h($po['expected_delivery'] ?? '') ?>">
                    </div>
                    <div class="form-group"><label>Tax %</label><input type="number" name="tax_percent" id="tax_percent"
                                                                       step="0.01" min="0" max="100"
                                                                       value="<?= h($po['tax_percent'] ?? getSetting('default_tax_percent')) ?>"
                                                                       oninput="recalcPO()"></div>
                    <div class="form-group"><label>Discount (<?= h(getSetting('currency_symbol')) ?>)</label><input
                                type="number" name="discount" id="po_discount" step="0.01" min="0"
                                value="<?= h($po['discount'] ?? '0') ?>" oninput="recalcPO()"></div>
                    <div class="form-group"><label>Payment Status</label><select name="payment_status">
                            <option value="unpaid" <?= ($po['payment_status'] ?? 'unpaid') === 'unpaid' ? 'selected' : '' ?>>
                                Unpaid
                            </option>
                            <option value="partial" <?= ($po['payment_status'] ?? '') === 'partial' ? 'selected' : '' ?>>
                                Partial
                            </option>
                            <option value="paid" <?= ($po['payment_status'] ?? '') === 'paid' ? 'selected' : '' ?>>
                                Paid
                            </option>
                        </select></div>
                    <div class="form-group full"><label>Notes</label><textarea
                                name="notes"><?= h($po['notes'] ?? '') ?></textarea></div>
                </div>
            </div>
            <div class="lf"><span class="lf-title">Order Items</span>
                <div style="margin-bottom:10px">
                    <button type="button" onclick="addPORow()" class="btn btn-primary btn-sm">+ Add Product</button>
                </div>
                <div class="tbl-wrap">
                    <table class="tbl" id="po-items-table">
                        <thead>
                        <tr>
                            <th style="width:35%">Product</th>
                            <th style="width:15%">Current Stock</th>
                            <th style="width:15%">Quantity</th>
                            <th style="width:20%">Unit Price</th>
                            <th style="width:12%">Total</th>
                            <th style="width:3%"></th>
                        </tr>
                        </thead>
                        <tbody id="po-items-body">
                        <?php if (!empty($poItems)):
                            foreach ($poItems as $item): ?>
                                <tr class="po-row">
                                    <td><select name="product_id[]" class="po-product" onchange="updatePOStock(this)"
                                                required
                                                style="width:100%;padding:4px;border:2px inset #a0a0a0;background:#fff">
                                            <option value="">-- Select Product --</option>
                                            <?php foreach ($products as $pr): ?>
                                                <option value="<?= $pr['id'] ?>"
                                                        data-price="<?= $pr['purchase_price'] ?>"
                                                        data-stock="<?= $pr['current_stock'] ?>" <?= $item['product_id'] == $pr['id'] ? 'selected' : '' ?>><?= h($pr['name'] . ' (' . $pr['sku'] . ')') ?></option><?php endforeach; ?>
                                        </select></td>
                                    <td class="po-stock text-right"><?= number_format((int) $item['received_qty']) ?></td>
                                    <td><input type="number" name="quantity[]" class="po-qty" min="1"
                                               value="<?= (int) $item['quantity'] ?>" oninput="recalcPO()" required
                                               style="width:80px;padding:4px;border:2px inset #a0a0a0;background:#fff">
                                    </td>
                                    <td><input type="number" name="unit_price[]" class="po-price" step="0.01" min="0"
                                               value="<?= number_format((float) $item['unit_price'], 2, '.', '') ?>"
                                               oninput="recalcPO()" required
                                               style="width:110px;padding:4px;border:2px inset #a0a0a0;background:#fff">
                                    </td>
                                    <td class="po-row-total text-right"><?= formatCurrency((float) $item['total_price']) ?></td>
                                    <td>
                                        <button type="button" onclick="this.closest('tr').remove();recalcPO()"
                                                class="btn btn-danger btn-xs">&#215;
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach;
                        else: ?>
                            <tr class="po-row">
                                <td><select name="product_id[]" class="po-product" onchange="updatePOStock(this)"
                                            required
                                            style="width:100%;padding:4px;border:2px inset #a0a0a0;background:#fff">
                                        <option value="">-- Select Product --</option>
                                        <?php foreach ($products as $pr): ?>
                                            <option value="<?= $pr['id'] ?>" data-price="<?= $pr['purchase_price'] ?>"
                                                    data-stock="<?= $pr['current_stock'] ?>"><?= h($pr['name'] . ' (' . $pr['sku'] . ')') ?></option><?php endforeach; ?>
                                    </select></td>
                                <td class="po-stock text-right">-</td>
                                <td><input type="number" name="quantity[]" class="po-qty" min="1" value="1"
                                           oninput="recalcPO()" required
                                           style="width:80px;padding:4px;border:2px inset #a0a0a0;background:#fff"></td>
                                <td><input type="number" name="unit_price[]" class="po-price" step="0.01" min="0"
                                           value="0.00" oninput="recalcPO()" required
                                           style="width:110px;padding:4px;border:2px inset #a0a0a0;background:#fff">
                                </td>
                                <td class="po-row-total text-right">-</td>
                                <td>
                                    <button type="button" onclick="this.closest('tr').remove();recalcPO()"
                                            class="btn btn-danger btn-xs">&#215;
                                    </button>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                        <tfoot>
                        <tr style="background:#e8e8e8;font-weight:700">
                            <td colspan="4" class="text-right" style="padding:6px 10px">Subtotal:</td>
                            <td id="po-subtotal" class="text-right" style="padding:6px 10px">-</td>
                            <td></td>
                        </tr>
                        <tr style="background:#e8e8e8">
                            <td colspan="4" class="text-right" style="padding:4px 10px;font-size:11px">Tax:</td>
                            <td id="po-tax" class="text-right" style="padding:4px 10px;font-size:11px">-</td>
                            <td></td>
                        </tr>
                        <tr style="background:#e8e8e8">
                            <td colspan="4" class="text-right" style="padding:4px 10px;font-size:11px">Discount:</td>
                            <td id="po-disc" class="text-right" style="padding:4px 10px;font-size:11px">-</td>
                            <td></td>
                        </tr>
                        <tr style="background:#d0d0d0;font-weight:700;font-size:14px">
                            <td colspan="4" class="text-right" style="padding:8px 10px">TOTAL:</td>
                            <td id="po-total" class="text-right" style="padding:8px 10px"></td>
                            <td></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-success">&#10003; <?= $po ? 'Update PO' : 'Create PO' ?></button>
                <a href="?page=purchases" class="btn btn-secondary">Cancel</a></div>
        </form>
        <script>
            var PO_PRODUCTS = <?= json_encode($products) ?>;
            function addPORow() {
                var opts = '<option value="">-- Select Product --</option>';
                PO_PRODUCTS.forEach(function (p) {
                    opts += '<option value="' + p.id + '" data-price="' + p.purchase_price + '" data-stock="' + p.current_stock + '">' + p.name + ' (' + p.sku + ')</option>';
                });
                var tr = document.createElement('tr');
                tr.className = 'po-row';
                tr.innerHTML = '<td><select name="product_id[]" class="po-product" onchange="updatePOStock(this)" required style="width:100%;padding:4px;border:2px inset #a0a0a0;background:#fff">' + opts + '</select></td><td class="po-stock text-right">-</td><td><input type="number" name="quantity[]" class="po-qty" min="1" value="1" oninput="recalcPO()" required style="width:80px;padding:4px;border:2px inset #a0a0a0;background:#fff"></td><td><input type="number" name="unit_price[]" class="po-price" step="0.01" min="0" value="0.00" oninput="recalcPO()" required style="width:110px;padding:4px;border:2px inset #a0a0a0;background:#fff"></td><td class="po-row-total text-right">-</td><td><button type="button" onclick="this.closest(\'tr\').remove();recalcPO()" class="btn btn-danger btn-xs">&#215;</button></td>';
                document.getElementById('po-items-body').appendChild(tr);
            }
            function updatePOStock(sel) {
                var opt = sel.options[sel.selectedIndex];
                var row = sel.closest('tr');
                row.querySelector('.po-stock').textContent = opt.dataset.stock || '-';
                var priceInput = row.querySelector('.po-price');
                if (opt.dataset.price) priceInput.value = parseFloat(opt.dataset.price).toFixed(2);
                recalcPO();
            }
            function recalcPO() {
                var subtotal = 0;
                document.querySelectorAll('.po-row').forEach(function (row) {
                    var qty = parseFloat(row.querySelector('.po-qty').value) || 0;
                    var price = parseFloat(row.querySelector('.po-price').value) || 0;
                    var rowTotal = qty * price;
                    row.querySelector('.po-row-total').textContent = CURRENCY + ' ' + rowTotal.toFixed(2);
                    subtotal += rowTotal;
                });
                var taxPct = parseFloat(document.getElementById('tax_percent').value) || 0;
                var disc = parseFloat(document.getElementById('po_discount').value) || 0;
                var tax = subtotal * (taxPct / 100);
                var total = subtotal + tax - disc;
                document.getElementById('po-subtotal').textContent = CURRENCY + ' ' + subtotal.toFixed(2);
                document.getElementById('po-tax').textContent = CURRENCY + ' ' + tax.toFixed(2);
                document.getElementById('po-disc').textContent = CURRENCY + ' ' + disc.toFixed(2);
                document.getElementById('po-total').textContent = CURRENCY + ' ' + total.toFixed(2);
            }
            recalcPO();
        </script>
    <?php
        $content = ob_get_clean();
        renderLayout(($po ? 'Edit' : 'New') . ' Purchase Order', $content, 'purchases');
    } elseif ($action === 'view') {
        $id = (int) ($_GET['id'] ?? 0);
        $poQ = $pdo->prepare('SELECT po.*,s.company_name,s.contact_person,s.email as supplier_email,s.phone as supplier_phone,s.address as supplier_address,u.full_name as created_by_name FROM purchase_orders po JOIN suppliers s ON s.id=po.supplier_id JOIN users u ON u.id=po.created_by WHERE po.id=?');
        $poQ->execute([$id]);
        $po = $poQ->fetch();
        if (!$po) {
            header('Location: ?page=purchases');
            exit;
        }
        $items = $pdo->prepare('SELECT poi.*,p.name as product_name,p.sku,p.unit FROM purchase_order_items poi JOIN products p ON p.id=poi.product_id WHERE poi.po_id=?');
        $items->execute([$id]);
        $items = $items->fetchAll();
        $companyName = getSetting('company_name');
        $companyAddress = getSetting('company_address');
        $companyPhone = getSetting('company_phone');
        $initialPayment = (float) $pdo->query("SELECT debit FROM supplier_ledgers WHERE reference_type='po' AND reference_id=$id")->fetchColumn();
        $subsequentPayments = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payment_receipts WHERE po_id=$id")->fetchColumn();
        $totalPaid = $initialPayment + $subsequentPayments;
        $balanceDue = max(0, $po['total_amount'] - $totalPaid);
        $receipts = $pdo->query("SELECT * FROM payment_receipts WHERE po_id=$id ORDER BY created_at ASC")->fetchAll();
        ob_start();
        ?>
        <div class="page-header no-print"><h1>&#128065; PO: <?= h($po['po_number']) ?></h1>
            <div class="page-header-actions">
                <?php if ($po['payment_status'] !== 'paid'): ?>
                    <button onclick="openModal('paymentModal')" class="btn btn-success btn-sm">&#128176; Add Payment
                    </button>
                <?php endif; ?>
                <?php if ($po['order_status'] !== 'received' && $po['order_status'] !== 'cancelled'): ?>
                    <a href="?page=purchases&action=edit&id=<?= $id ?>" class="btn btn-warning btn-sm">&#9998; Edit</a>
                    <a href="?page=purchases&action=receive&id=<?= $id ?>" class="btn btn-success btn-sm">&#8595;
                        Receive Stock</a>
                <?php endif; ?>
                <button onclick="window.print()" class="btn btn-primary btn-sm">&#128438; Print</button>
                <a href="?page=purchases" class="btn btn-secondary btn-sm">&#8592; Back</a>
            </div>
        </div>
        <?php if (isset($_SESSION['flash_msg'])): ?>
        <div
        class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?> no-print"><?= h($_SESSION['flash_msg']) ?></div><?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
        endif; ?>
        <div class="lf print-po">
            <div class="print-only" style="margin-bottom:16px">
                <h2 style="font-size:18px"><?= h($companyName) ?></h2>
                <div style="font-size:12px"><?= h($companyAddress) ?> | <?= h($companyPhone) ?></div>
                <hr style="margin:8px 0">
            </div>
            <div class="cols-2" style="margin-bottom:12px">
                <div>
                    <div style="font-size:11px;color:#666;text-transform:uppercase;font-weight:700;margin-bottom:4px">
                        Purchase Order
                    </div>
                    <div style="font-size:20px;font-weight:700;color:#4a90d9"><?= h($po['po_number']) ?></div>
                    <table style="font-size:12px;margin-top:8px">
                        <tbody>
                        <tr>
                            <td style="padding:3px 8px 3px 0;color:#666">PO Date:</td>
                            <td><?= h($po['po_date']) ?></td>
                        </tr>
                        <tr>
                            <td style="padding:3px 8px 3px 0;color:#666">Expected Delivery:</td>
                            <td><?= h($po['expected_delivery'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td style="padding:3px 8px 3px 0;color:#666">Order Status:</td>
                            <td>
                                <span class="badge badge-<?= ['pending' => 'warning', 'partial' => 'info', 'received' => 'success', 'cancelled' => 'danger'][$po['order_status']] ?? 'secondary' ?>"><?= h(strtoupper($po['order_status'])) ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:3px 8px 3px 0;color:#666">Payment Status:</td>
                            <td>
                                <span class="badge badge-<?= ['unpaid' => 'danger', 'partial' => 'warning', 'paid' => 'success'][$po['payment_status']] ?? 'secondary' ?>"><?= h(strtoupper($po['payment_status'])) ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:3px 8px 3px 0;color:#666">Created By:</td>
                            <td><?= h($po['created_by_name']) ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div>
                    <div style="font-size:11px;color:#666;text-transform:uppercase;font-weight:700;margin-bottom:4px">
                        Supplier
                    </div>
                    <div style="font-size:14px;font-weight:700"><?= h($po['company_name']) ?></div>
                    <div style="font-size:12px;margin-top:4px;color:#555"><?= h($po['contact_person'] ?? '') ?></div>
                    <div style="font-size:12px;color:#555"><?= h($po['supplier_email'] ?? '') ?></div>
                    <div style="font-size:12px;color:#555"><?= h($po['supplier_phone'] ?? '') ?></div>
                    <div style="font-size:12px;color:#555"><?= h($po['supplier_address'] ?? '') ?></div>
                </div>
            </div>
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>Unit</th>
                        <th class="text-right">Ordered</th>
                        <th class="text-right">Received</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Total</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $rowNum = 0;
                    foreach ($items as $item):
                        $rowNum++; ?>
                        <tr>
                            <td><?= $rowNum ?></td>
                            <td><code style="font-size:10px"><?= h($item['sku']) ?></code></td>
                            <td><?= h($item['product_name']) ?></td>
                            <td><?= h($item['unit']) ?></td>
                            <td class="text-right"><?= number_format((int) $item['quantity']) ?></td>
                            <td class="text-right <?= $item['received_qty'] >= $item['quantity'] ? 'stock-ok' : ($item['received_qty'] > 0 ? 'stock-low' : 'stock-critical') ?>"><?= number_format((int) $item['received_qty']) ?></td>
                            <td class="text-right"><?= formatCurrency((float) $item['unit_price']) ?></td>
                            <td class="text-right"><?= formatCurrency((float) $item['total_price']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr style="background:#e8e8e8">
                        <td colspan="7" class="text-right" style="padding:6px 10px;font-weight:600">Subtotal:</td>
                        <td class="text-right"
                            style="padding:6px 10px"><?= formatCurrency((float) $po['subtotal']) ?></td>
                    </tr>
                    <tr style="background:#e8e8e8">
                        <td colspan="7" class="text-right" style="padding:4px 10px;font-size:11px">Tax
                            (<?= h($po['tax_percent']) ?>%):
                        </td>
                        <td class="text-right"
                            style="padding:4px 10px;font-size:11px"><?= formatCurrency((float) $po['subtotal'] * (float) $po['tax_percent'] / 100) ?></td>
                    </tr>
                    <tr style="background:#e8e8e8">
                        <td colspan="7" class="text-right" style="padding:4px 10px;font-size:11px">Discount:</td>
                        <td class="text-right" style="padding:4px 10px;font-size:11px">
                            (<?= formatCurrency((float) $po['discount']) ?>)
                        </td>
                    </tr>
                    <tr style="background:#d0d0d0;font-weight:700;font-size:14px">
                        <td colspan="7" class="text-right" style="padding:8px 10px">TOTAL:</td>
                        <td class="text-right"
                            style="padding:8px 10px"><?= formatCurrency((float) $po['total_amount']) ?></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
            <div style="display:flex; justify-content:flex-end; margin-top:16px;">
                <table style="width:300px; font-size:13px;">
                    <tr>
                        <td style="padding:4px; color:#666;">Total Amount:</td>
                        <td class="text-right"
                            style="padding:4px; font-weight:700;"><?= formatCurrency((float) $po['total_amount']) ?></td>
                    </tr>
                    <tr>
                        <td style="padding:4px; color:#666;">Total Paid:</td>
                        <td class="text-right"
                            style="padding:4px; font-weight:700; color:#5cb85c;"><?= formatCurrency($totalPaid) ?></td>
                    </tr>
                    <tr style="border-top:2px solid #ccc;">
                        <td style="padding:6px 4px; font-weight:700;">Balance Due:</td>
                        <td class="text-right"
                            style="padding:6px 4px; font-weight:700; color:#d9534f; font-size:16px;"><?= formatCurrency($balanceDue) ?></td>
                    </tr>
                </table>
            </div>
            <?php if ($po['notes']): ?>
                <div style="margin-top:12px;font-size:12px"><strong>Notes:</strong> <?= h($po['notes']) ?>
                </div><?php endif; ?>
            <?php if (!empty($receipts)): ?>
                <div class="no-print mt-8">
                    <h4 style="font-size:14px; margin-bottom:8px; border-bottom:1px solid #ccc; padding-bottom:4px;">
                        Payment Receipts</h4>
                    <table class="tbl">
                        <thead>
                        <tr>
                            <th>Receipt#</th>
                            <th>Date</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th class="text-right">Amount</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($receipts as $r): ?>
                            <tr>
                                <td><?= h($r['receipt_number']) ?></td>
                                <td><?= h($r['payment_date']) ?></td>
                                <td><?= h(ucfirst($r['method'])) ?></td>
                                <td><?= h($r['reference'] ?? '-') ?></td>
                                <td class="text-right"
                                    style="color:#5cb85c;font-weight:700"><?= formatCurrency((float) $r['amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($po['payment_status'] !== 'paid'): ?>
        <div id="paymentModal" class="modal-overlay no-print">
            <div class="modal" style="max-width:400px;">
                <div class="modal-title-bar"><span>Record Payment</span>
                    <button class="modal-close-btn" onclick="closeModal('paymentModal')">&times;</button>
                </div>
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="add_payment" value="1">
                    <input type="hidden" name="po_id" value="<?= $id ?>">
                    <div class="modal-body">
                        <div class="form-group mb-8">
                            <label>Amount to Pay (Due: <?= formatCurrency($balanceDue) ?>)</label>
                            <input type="number" name="amount" step="0.01" min="0.01" max="<?= $balanceDue ?>"
                                   value="<?= $balanceDue ?>" class="form-control" required>
                        </div>
                        <div class="form-group mb-8">
                            <label>Payment Method</label>
                            <select name="method" class="form-control" required>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="card">Card</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Reference (Optional)</label>
                            <input type="text" name="reference" class="form-control"
                                   placeholder="Transaction ID, Check #...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('paymentModal')">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-success btn-sm">Save Payment</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
    <?php
        $content = ob_get_clean();
        renderLayout('PO: ' . $po['po_number'], $content, 'purchases');
    } elseif ($action === 'receive') {
        $id = (int) ($_GET['id'] ?? 0);
        $poQ = $pdo->prepare("SELECT po.*,s.company_name FROM purchase_orders po JOIN suppliers s ON s.id=po.supplier_id WHERE po.id=? AND po.order_status NOT IN ('received','cancelled')");
        $poQ->execute([$id]);
        $po = $poQ->fetch();
        if (!$po) {
            $_SESSION['flash_msg'] = 'PO not found or already completed.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ?page=purchases');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
            $pdo->beginTransaction();
            try {
                $receiveQtys = $_POST['receive_qty'] ?? [];
                $itemIds = $_POST['item_id'] ?? [];
                $allReceived = true;
                $anyReceived = false;
                foreach ($itemIds as $i => $itemId) {
                    $itemId = (int) $itemId;
                    $rcvQty = (int) ($receiveQtys[$i] ?? 0);
                    if ($rcvQty <= 0)
                        continue;
                    $anyReceived = true;
                    $itemQ = $pdo->prepare('SELECT * FROM purchase_order_items WHERE id=? AND po_id=?');
                    $itemQ->execute([$itemId, $id]);
                    $item = $itemQ->fetch();
                    if (!$item)
                        continue;
                    $newRcv = min($item['quantity'], (int) $item['received_qty'] + $rcvQty);
                    $pdo->prepare('UPDATE purchase_order_items SET received_qty=? WHERE id=?')->execute([$newRcv, $itemId]);
                    $pdo->prepare('UPDATE products SET current_stock=current_stock+?, updated_at=NOW() WHERE id=?')->execute([$rcvQty, (int) $item['product_id']]);
                    $ref = generateRefNumber('ADJ');
                    $prodStmt = $pdo->prepare('SELECT current_stock FROM products WHERE id=?');
                    $prodStmt->execute([(int) $item['product_id']]);
                    $prodRow = $prodStmt->fetch();
                    $beforeStock = (int) $prodRow['current_stock'] - $rcvQty;
                    $pdo->prepare("INSERT INTO stock_adjustments (reference_no,product_id,adjustment_type,quantity,before_stock,after_stock,reason,status,requested_by,approved_by,approved_at) VALUES (?,?,'addition',?,?,?,?,?,?,?,NOW())")->execute([$ref, (int) $item['product_id'], $rcvQty, $beforeStock, (int) $prodRow['current_stock'], 'Stock received from PO: ' . $po['po_number'], 'approved', (int) $_SESSION['user_id'], (int) $_SESSION['user_id']]);
                    if ($newRcv < $item['quantity'])
                        $allReceived = false;
                }
                if (!$anyReceived)
                    throw new Exception('Please enter at least one quantity to receive.');
                $pendingChk = $pdo->prepare('SELECT SUM(quantity-received_qty) as remaining FROM purchase_order_items WHERE po_id=?');
                $pendingChk->execute([$id]);
                $remaining = (int) $pendingChk->fetch()['remaining'];
                $newStatus = $remaining <= 0 ? 'received' : 'partial';
                $pdo->prepare('UPDATE purchase_orders SET order_status=?,updated_at=NOW() WHERE id=?')->execute([$newStatus, $id]);
                $pdo->commit();
                logActivity($_SESSION['user_id'], 'RECEIVE', 'purchases', 'Received stock for PO: ' . $po['po_number']);
                $_SESSION['flash_msg'] = 'Stock received successfully. PO status: ' . strtoupper($newStatus) . '.';
                $_SESSION['flash_type'] = 'success';
                header('Location: ?page=purchases&action=view&id=' . $id);
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['flash_msg'] = 'Error: ' . $e->getMessage();
                $_SESSION['flash_type'] = 'danger';
            }
        }
        $items = $pdo->prepare('SELECT poi.*,p.name as product_name,p.sku,p.unit,p.current_stock FROM purchase_order_items poi JOIN products p ON p.id=poi.product_id WHERE poi.po_id=?');
        $items->execute([$id]);
        $items = $items->fetchAll();
        ob_start();
        ?>
        <div class="page-header"><h1>&#8595; Receive Stock: <?= h($po['po_number']) ?></h1>
            <div class="page-header-actions"><a href="?page=purchases&action=view&id=<?= $id ?>"
                                                class="btn btn-secondary btn-sm">&#8592; Back to PO</a></div>
        </div>
        <?php if (isset($_SESSION['flash_msg'])): ?>
        <div
        class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?>"><?= h($_SESSION['flash_msg']) ?></div><?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
        endif; ?>
        <div class="lf"><span class="lf-title">Supplier: <?= h($po['company_name']) ?></span>
            <form method="POST" action="?page=purchases&action=receive&id=<?= $id ?>">
                <?= csrfField() ?>
                <div class="tbl-wrap">
                    <table class="tbl">
                        <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th class="text-right">Ordered</th>
                            <th class="text-right">Already Received</th>
                            <th class="text-right">Pending</th>
                            <th class="text-right">Current Stock</th>
                            <th style="width:130px">Receive Now</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $item):
                            $pending = $item['quantity'] - $item['received_qty']; ?>
                            <tr <?= $pending <= 0 ? 'style="opacity:0.5"' : '' ?>>
                                <td><?= h($item['product_name']) ?></td>
                                <td><code style="font-size:10px"><?= h($item['sku']) ?></code></td>
                                <td class="text-right"><?= number_format((int) $item['quantity']) ?></td>
                                <td class="text-right <?= $item['received_qty'] > 0 ? 'stock-ok' : '' ?>"><?= number_format((int) $item['received_qty']) ?></td>
                                <td class="text-right <?= $pending > 0 ? 'stock-low' : 'stock-ok' ?>"><?= number_format($pending) ?></td>
                                <td class="text-right"><?= number_format((int) $item['current_stock']) ?> <?= h($item['unit']) ?></td>
                                <td>
                                    <input type="hidden" name="item_id[]" value="<?= $item['id'] ?>">
                                    <input type="number" name="receive_qty[]" min="0" max="<?= $pending ?>"
                                           value="<?= $pending > 0 ? $pending : 0 ?>" <?= $pending <= 0 ? 'disabled' : '' ?>
                                           style="width:90px;padding:4px;border:2px inset #a0a0a0;background:<?= $pending <= 0 ? '#e8e8e8' : '#fff' ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top:12px;display:flex;gap:8px">
                    <button type="submit" class="btn btn-success">&#10003; Confirm Receipt & Update Stock</button>
                    <a href="?page=purchases&action=view&id=<?= $id ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        <?php
        $content = ob_get_clean();
        renderLayout('Receive Stock', $content, 'purchases');
    }
}

function renderSales(): void
{
    $action = $_GET['action'] ?? 'list';
    $pdo = getPDO();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment']) && verifyCsrf()) {
        $invId = (int) $_POST['invoice_id'];
        $amt = (float) $_POST['amount'];
        $method = $_POST['method'];
        $ref = $_POST['reference'];
        $inv = $pdo->prepare('SELECT * FROM invoices WHERE id=?');
        $inv->execute([$invId]);
        $inv = $inv->fetch();
        if ($inv && $amt > 0) {
            $pdo->beginTransaction();
            try {
                $rn = generateRefNumber('REC');
                $pdo
                    ->prepare('INSERT INTO payment_receipts (receipt_number, customer_id, invoice_id, amount, payment_date, method, reference, created_by) VALUES (?,?,?,?,CURDATE(),?,?,?)')
                    ->execute([$rn, $inv['customer_id'], $invId, $amt, $method, $ref, $_SESSION['user_id']]);
                $recId = $pdo->lastInsertId();
                updateCustomerLedger($inv['customer_id'], 'payment', $recId, 0, $amt, "Payment for Invoice #{$inv['invoice_number']}");
                $initialPayment = (float) $pdo->query("SELECT credit FROM customer_ledgers WHERE reference_type='invoice' AND reference_id=$invId")->fetchColumn();
                $subsequentPayments = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payment_receipts WHERE invoice_id=$invId")->fetchColumn();
                $totalPaid = $initialPayment + $subsequentPayments;
                $newStatus = ($totalPaid >= $inv['total_amount']) ? 'paid' : 'partial';
                $pdo->prepare('UPDATE invoices SET payment_status=? WHERE id=?')->execute([$newStatus, $invId]);
                $pdo->commit();
                $_SESSION['flash_msg'] = 'Payment of ' . formatCurrency($amt) . ' recorded.';
                $_SESSION['flash_type'] = 'success';
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['flash_msg'] = 'Error recording payment: ' . $e->getMessage();
                $_SESSION['flash_type'] = 'danger';
            }
        }
        header("Location: ?page=sales&action=view&id=$invId");
        exit;
    }
    if ($action === 'list') {
        $filters = ['search' => $_GET['search'] ?? '', 'payment_status' => $_GET['payment_status'] ?? '', 'customer_id' => $_GET['customer_id'] ?? '', 'date_from' => $_GET['date_from'] ?? '', 'date_to' => $_GET['date_to'] ?? ''];
        $page = max(1, (int) ($_GET['pg'] ?? 1));
        $perPage = (int) (getSetting('items_per_page') ?? 20);
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['search'])) {
            $where[] = '(i.invoice_number LIKE ? OR c.name LIKE ?)';
            $s = '%' . $filters['search'] . '%';
            $params[] = $s;
            $params[] = $s;
        }
        if (!empty($filters['payment_status'])) {
            $where[] = 'i.payment_status=?';
            $params[] = $filters['payment_status'];
        }
        if (!empty($filters['customer_id'])) {
            $where[] = 'i.customer_id=?';
            $params[] = (int) $filters['customer_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'i.invoice_date>=?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'i.invoice_date<=?';
            $params[] = $filters['date_to'];
        }
        $whereStr = implode(' AND ', $where);
        $cnt = $pdo->prepare("SELECT COUNT(*) FROM invoices i JOIN customers c ON c.id=i.customer_id WHERE $whereStr");
        $cnt->execute($params);
        $total = (int) $cnt->fetchColumn();
        $pag = paginate($total, $perPage, $page);
        $stmt = $pdo->prepare("SELECT i.*,c.name as customer_name,u.full_name as created_by_name FROM invoices i JOIN customers c ON c.id=i.customer_id JOIN users u ON u.id=i.created_by WHERE $whereStr ORDER BY i.created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute(array_merge($params, [$pag['per_page'], $pag['offset']]));
        $invoices = $stmt->fetchAll();
        $customers = $pdo->query("SELECT id,name FROM customers WHERE status='active' ORDER BY name")->fetchAll();
        ob_start();
        ?>
        <div class="page-header"><h1>&#128203; Sales / Invoices</h1>
            <div class="page-header-actions"><a href="?page=sales&action=new" class="btn btn-success btn-sm">+ New
                    Invoice</a></div>
        </div>
        <?php if (isset($_SESSION['flash_msg'])): ?>
        <div
        class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?>"><?= h($_SESSION['flash_msg']) ?></div><?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
        endif; ?>
        <form method="GET"><input type="hidden" name="page" value="sales">
            <div class="filter-bar">
                <div class="form-group"><label>Search</label><input type="text" name="search"
                                                                    value="<?= h($filters['search']) ?>"
                                                                    placeholder="Invoice#, customer..."></div>
                <div class="form-group"><label>Customer</label><select name="customer_id">
                        <option value="">All Customers</option><?php foreach ($customers as $c): ?>
                            <option
                            value="<?= $c['id'] ?>" <?= $filters['customer_id'] == $c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option><?php endforeach; ?>
                    </select></div>
                <div class="form-group"><label>Payment</label><select name="payment_status">
                        <option value="">All</option>
                        <option value="unpaid" <?= $filters['payment_status'] === 'unpaid' ? 'selected' : '' ?>>Unpaid
                        </option>
                        <option value="partial" <?= $filters['payment_status'] === 'partial' ? 'selected' : '' ?>>
                            Partial
                        </option>
                        <option value="paid" <?= $filters['payment_status'] === 'paid' ? 'selected' : '' ?>>Paid
                        </option>
                    </select></div>
                <div class="form-group"><label>From</label><input type="date" name="date_from"
                                                                  value="<?= h($filters['date_from']) ?>"></div>
                <div class="form-group"><label>To</label><input type="date" name="date_to"
                                                                value="<?= h($filters['date_to']) ?>"></div>
                <div class="form-group"
                     style="justify-content:flex-end;flex-direction:row;align-items:flex-end;gap:6px">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="?page=sales" class="btn btn-secondary btn-sm">Reset</a></div>
            </div>
        </form>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                <tr>
                    <th>Invoice#</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Due Date</th>
                    <th class="text-right">Total</th>
                    <th>Payment</th>
                    <th>Method</th>
                    <th>Created By</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($invoices)): ?>
                    <tr>
                        <td colspan="9" class="text-center" style="padding:16px;color:#888">No invoices found</td>
                    </tr>
                <?php else:
            foreach ($invoices as $inv):
                $psBadge = ['unpaid' => 'danger', 'partial' => 'warning', 'paid' => 'success'][$inv['payment_status']] ?? 'secondary'; ?>
                        <tr>
                            <td>
                                <a href="?page=sales&action=view&id=<?= $inv['id'] ?>"><?= h($inv['invoice_number']) ?></a>
                            </td>
                            <td><?= h($inv['customer_name']) ?></td>
                            <td><?= h($inv['invoice_date']) ?></td>
                            <td><?= h($inv['due_date'] ?? '-') ?></td>
                            <td class="text-right"><?= formatCurrency((float) $inv['total_amount']) ?></td>
                            <td>
                                <span class="badge badge-<?= $psBadge ?>"><?= h(strtoupper($inv['payment_status'])) ?></span>
                            </td>
                            <td><?= h(str_replace('_', ' ', strtoupper($inv['payment_method']))) ?></td>
                            <td><?= h($inv['created_by_name']) ?></td>
                            <td>
                                <div class="tbl-actions">
                                    <a href="?page=sales&action=view&id=<?= $inv['id'] ?>"
                                       class="btn btn-primary btn-xs">&#128065;</a>
                                    <?php if ($inv['payment_status'] !== 'paid'): ?><a
                                        href="?page=sales&action=edit&id=<?= $inv['id'] ?>"
                                        class="btn btn-warning btn-xs">&#9998;</a><?php endif; ?>
                                    <?php if (canAccess(['admin', 'manager'])): ?>
                                        <button
                                        onclick="voidInvoice(<?= $inv['id'] ?>,'<?= h(addslashes($inv['invoice_number'])) ?>')"
                                        class="btn btn-danger btn-xs">&#215;Void</button><?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach;
        endif; ?>
                </tbody>
            </table>
        </div>
    <?= paginationHtml($pag, '?page=sales&search=' . urlencode($filters['search']) . '&payment_status=' . urlencode($filters['payment_status'])) ?>
        <script>function voidInvoice(id, n) {
                confirmAction('Void invoice "' + n + '"? Stock will NOT be automatically reversed. Use Stock Adjustment if needed.', function () {
                    apiPost('?ajax=void_invoice', {id: id}, function () {
                        showToast('Invoice voided.', 'success');
                        setTimeout(function () {
                            location.reload();
                        }, 800);
                    });
                }, 'Confirm Void');
            }</script>
    <?php
        $content = ob_get_clean();
        renderLayout('Sales / Invoices', $content, 'sales');
    } elseif ($action === 'new' || $action === 'edit') {
        $editId = !empty($_GET['id']) ? (int) $_GET['id'] : null;
        $inv = null;
        $invItems = [];
        if ($editId) {
            $invQ = $pdo->prepare('SELECT * FROM invoices WHERE id=?');
            $invQ->execute([$editId]);
            $inv = $invQ->fetch();
            $invItems = $pdo->prepare('SELECT ii.*,p.name as product_name,p.sku,p.current_stock FROM invoice_items ii JOIN products p ON p.id=ii.product_id WHERE ii.invoice_id=?');
            $invItems->execute([$editId]);
            $invItems = $invItems->fetchAll();
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
            $pdo->beginTransaction();
            try {
                $customerId = (int) ($_POST['customer_id'] ?? 0);
                $invoiceDate = trim($_POST['invoice_date'] ?? date('Y-m-d'));
                $dueDate = trim($_POST['due_date'] ?? '') ?: null;
                $taxPct = (float) ($_POST['tax_percent'] ?? 0);
                $discount = (float) ($_POST['discount'] ?? 0);
                $payStatus = $_POST['payment_status'] ?? 'unpaid';
                $payMethod = $_POST['payment_method'] ?? 'cash';
                $notes = trim($_POST['notes'] ?? '');
                $productIds = $_POST['product_id'] ?? [];
                $quantities = $_POST['quantity'] ?? [];
                $unitPrices = $_POST['unit_price'] ?? [];
                $discPcts = $_POST['discount_pct'] ?? [];
                if (empty($productIds) || !$customerId)
                    throw new Exception('Customer and at least one product required.');
                if ($editId) {
                    $oldItems = $pdo->prepare('SELECT ii.*,p.current_stock FROM invoice_items ii JOIN products p ON p.id=ii.product_id WHERE ii.invoice_id=?');
                    $oldItems->execute([$editId]);
                    $oldItems = $oldItems->fetchAll();
                    foreach ($oldItems as $oi) {
                        $pdo->prepare('UPDATE products SET current_stock=current_stock+? WHERE id=?')->execute([(int) $oi['quantity'], (int) $oi['product_id']]);
                    }
                    $pdo->prepare('DELETE FROM invoice_items WHERE invoice_id=?')->execute([$editId]);
                }
                $subtotal = 0;
                foreach ($productIds as $i => $pid) {
                    $pid = (int) $pid;
                    if (!$pid)
                        continue;
                    $qty = (int) ($quantities[$i] ?? 0);
                    $up = (float) ($unitPrices[$i] ?? 0);
                    $dp = (float) ($discPcts[$i] ?? 0);
                    if ($qty <= 0)
                        continue;
                    $chkStock = $pdo->prepare('SELECT current_stock,name FROM products WHERE id=?');
                    $chkStock->execute([$pid]);
                    $chkRow = $chkStock->fetch();
                    if (!$chkRow)
                        continue;
                    if ($chkRow['current_stock'] < $qty)
                        throw new Exception("Insufficient stock for '{$chkRow['name']}'. Available: {$chkRow['current_stock']}, requested: $qty");
                    $lineTotal = $qty * $up * (1 - $dp / 100);
                    $subtotal += $lineTotal;
                }
                $taxAmt = $subtotal * ($taxPct / 100);
                $total = $subtotal + $taxAmt - $discount;
                $custCheck = $pdo->prepare('SELECT customer_type FROM customers WHERE id=?');
                $custCheck->execute([$customerId]);
                if ($custCheck->fetchColumn() === 'walk-in' && $payStatus !== 'paid') {
                    throw new Exception('Walk-in customers must pay in full.');
                }
                if ($editId) {
                    $pdo->prepare('UPDATE invoices SET customer_id=?,invoice_date=?,due_date=?,subtotal=?,tax_percent=?,discount=?,total_amount=?,payment_status=?,payment_method=?,notes=?,updated_at=NOW() WHERE id=?')->execute([$customerId, $invoiceDate, $dueDate, $subtotal, $taxPct, $discount, $total, $payStatus, $payMethod, $notes, $editId]);
                } else {
                    $invNum = generateInvoiceNumber();
                    $pdo->prepare('INSERT INTO invoices (invoice_number,customer_id,invoice_date,due_date,subtotal,tax_percent,discount,total_amount,payment_status,payment_method,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$invNum, $customerId, $invoiceDate, $dueDate, $subtotal, $taxPct, $discount, $total, $payStatus, $payMethod, $notes, (int) $_SESSION['user_id']]);
                    $editId = (int) $pdo->lastInsertId();
                }
                foreach ($productIds as $i => $pid) {
                    $pid = (int) $pid;
                    if (!$pid)
                        continue;
                    $qty = (int) ($quantities[$i] ?? 0);
                    $up = (float) ($unitPrices[$i] ?? 0);
                    $dp = (float) ($discPcts[$i] ?? 0);
                    if ($qty <= 0)
                        continue;
                    $lineTotal = $qty * $up * (1 - $dp / 100);
                    $pdo->prepare('INSERT INTO invoice_items (invoice_id,product_id,quantity,unit_price,discount_pct,total_price) VALUES (?,?,?,?,?,?)')->execute([$editId, $pid, $qty, $up, $dp, $lineTotal]);
                    $pdo->prepare('UPDATE products SET current_stock=current_stock-?,updated_at=NOW() WHERE id=?')->execute([$qty, $pid]);
                }
                if ($payStatus === 'paid') {
                    updateCustomerLedger($customerId, 'invoice', $editId, $total, $total, "Paid Invoice #$invNum");
                } else {
                    updateCustomerLedger($customerId, 'invoice', $editId, $total, 0, "Invoice #$invNum");
                }
                $pdo->commit();
                logActivity($_SESSION['user_id'], $inv ? 'UPDATE' : 'CREATE', 'sales', ($inv ? 'Updated' : 'Created') . ' invoice id ' . $editId);
                $_SESSION['flash_msg'] = 'Invoice ' . ($inv ? 'updated' : 'created') . '.';
                $_SESSION['flash_type'] = 'success';
                header('Location: ?page=sales&action=view&id=' . $editId);
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['flash_msg'] = 'Error: ' . $e->getMessage();
                $_SESSION['flash_type'] = 'danger';
            }
        }
        $customers = $pdo->query("SELECT id,name,customer_type,credit_limit FROM customers WHERE status='active' ORDER BY name")->fetchAll();
        $products = $pdo->query("SELECT id,name,sku,selling_price,current_stock,unit FROM products WHERE status='active' ORDER BY name")->fetchAll();
        $preCustomer = (int) ($_GET['customer_id'] ?? $inv['customer_id'] ?? 0);
        ob_start();
        ?>
        <div class="page-header">
            <h1><?= $inv ? '&#9998; Edit Invoice: ' . h($inv['invoice_number']) : '+ New Invoice' ?></h1>
            <div class="page-header-actions"><a href="?page=sales" class="btn btn-secondary btn-sm">&#8592; Back</a>
            </div>
        </div>
        <?php if (isset($_SESSION['flash_msg'])): ?>
        <div
        class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?>"><?= h($_SESSION['flash_msg']) ?></div><?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
        endif; ?>
        <form method="POST" action="?page=sales&action=<?= $inv ? 'edit&id=' . $inv['id'] : 'new' ?>" id="inv-form">
            <?= csrfField() ?>
            <div class="lf"><span class="lf-title">Invoice Details</span>
                <div class="form-grid form-grid-3">
                    <div class="form-group" style="grid-column:span 2"><label>Customer <span
                                    class="required-mark">*</span></label><select name="customer_id" required>
                            <option value="">-- Select Customer --</option><?php foreach ($customers as $c): ?>
                                <option value="<?= $c['id'] ?>"
                                        data-credit="<?= $c['credit_limit'] ?>" <?= ($inv['customer_id'] ?? $preCustomer) == $c['id'] ? 'selected' : '' ?>><?= h($c['name'] . ' [' . strtoupper($c['customer_type']) . ']') ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="form-group"><label>Invoice Date <span class="required-mark">*</span></label><input
                                type="date" name="invoice_date" value="<?= h($inv['invoice_date'] ?? date('Y-m-d')) ?>"
                                required></div>
                    <div class="form-group"><label>Due Date</label><input type="date" name="due_date"
                                                                          value="<?= h($inv['due_date'] ?? '') ?>">
                    </div>
                    <div class="form-group"><label>Tax %</label><input type="number" name="tax_percent"
                                                                       id="inv_tax_percent" step="0.01" min="0"
                                                                       max="100"
                                                                       value="<?= h($inv['tax_percent'] ?? getSetting('default_tax_percent')) ?>"
                                                                       oninput="recalcInv()"></div>
                    <div class="form-group"><label>Discount (<?= h(getSetting('currency_symbol')) ?>)</label><input
                                type="number" name="discount" id="inv_discount" step="0.01" min="0"
                                value="<?= h($inv['discount'] ?? '0') ?>" oninput="recalcInv()"></div>
                    <div class="form-group"><label>Payment Status</label><select name="payment_status">
                            <option value="unpaid" <?= ($inv['payment_status'] ?? 'unpaid') === 'unpaid' ? 'selected' : '' ?>>
                                Unpaid
                            </option>
                            <option value="partial" <?= ($inv['payment_status'] ?? '') === 'partial' ? 'selected' : '' ?>>
                                Partial
                            </option>
                            <option value="paid" <?= ($inv['payment_status'] ?? '') === 'paid' ? 'selected' : '' ?>>
                                Paid
                            </option>
                        </select></div>
                    <div class="form-group"><label>Payment Method</label><select name="payment_method">
                            <option value="cash" <?= ($inv['payment_method'] ?? 'cash') === 'cash' ? 'selected' : '' ?>>
                                Cash
                            </option>
                            <option value="card" <?= ($inv['payment_method'] ?? '') === 'card' ? 'selected' : '' ?>>
                                Card
                            </option>
                            <option value="bank_transfer" <?= ($inv['payment_method'] ?? '') === 'bank_transfer' ? 'selected' : '' ?>>
                                Bank Transfer
                            </option>
                            <option value="credit" <?= ($inv['payment_method'] ?? '') === 'credit' ? 'selected' : '' ?>>
                                Credit
                            </option>
                        </select></div>
                    <div class="form-group full"><label>Notes</label><textarea
                                name="notes"><?= h($inv['notes'] ?? '') ?></textarea></div>
                </div>
            </div>
            <div class="lf"><span class="lf-title">Invoice Items</span>
                <div style="margin-bottom:10px">
                    <button type="button" onclick="addInvRow()" class="btn btn-primary btn-sm">+ Add Product</button>
                </div>
                <div class="tbl-wrap">
                    <table class="tbl" id="inv-table">
                        <thead>
                        <tr>
                            <th style="width:35%">Product</th>
                            <th style="width:12%">In Stock</th>
                            <th style="width:12%">Qty</th>
                            <th style="width:16%">Unit Price</th>
                            <th style="width:10%">Disc %</th>
                            <th style="width:12%">Total</th>
                            <th style="width:3%"></th>
                        </tr>
                        </thead>
                        <tbody id="inv-items-body">
                        <?php if (!empty($invItems)):
                            foreach ($invItems as $item): ?>
                                <tr class="inv-row">
                                    <td><select name="product_id[]" class="inv-product" onchange="updateInvStock(this)"
                                                required
                                                style="width:100%;padding:4px;border:2px inset #a0a0a0;background:#fff">
                                            <option value="">-- Select Product --</option>
                                            <?php foreach ($products as $pr): ?>
                                                <option value="<?= $pr['id'] ?>"data-price="<?= $pr['selling_price'] ?>"
                                                        data-stock="<?= $pr['current_stock'] ?>"
                                                        data-unit="<?= h($pr['unit']) ?>" <?= $item['product_id'] == $pr['id'] ? 'selected' : '' ?>><?= h($pr['name'] . ' (' . $pr['sku'] . ')') ?></option><?php endforeach; ?>
                                        </select></td>
                                    <td class="inv-stock text-right"><?= number_format((int) $item['current_stock']) ?></td>
                                    <td><input type="number" name="quantity[]" class="inv-qty" min="1"
                                               value="<?= (int) $item['quantity'] ?>" oninput="recalcInv()" required
                                               style="width:70px;padding:4px;border:2px inset #a0a0a0;background:#fff">
                                    </td>
                                    <td><input type="number" name="unit_price[]" class="inv-price" step="0.01" min="0"
                                               value="<?= number_format((float) $item['unit_price'], 2, '.', '') ?>"
                                               oninput="recalcInv()" required
                                               style="width:100px;padding:4px;border:2px inset #a0a0a0;background:#fff">
                                    </td>
                                    <td><input type="number" name="discount_pct[]" class="inv-disc" step="0.01" min="0"
                                               max="100"
                                               value="<?= number_format((float) $item['discount_pct'], 2, '.', '') ?>"
                                               oninput="recalcInv()"
                                               style="width:60px;padding:4px;border:2px inset #a0a0a0;background:#fff">
                                    </td>
                                    <td class="inv-row-total text-right"><?= formatCurrency((float) $item['total_price']) ?></td>
                                    <td>
                                        <button type="button" onclick="this.closest('tr').remove();recalcInv()"
                                                class="btn btn-danger btn-xs">&#215;
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach;
                        else: ?>
                            <tr class="inv-row">
                                <td><select name="product_id[]" class="inv-product" onchange="updateInvStock(this)"
                                            required
                                            style="width:100%;padding:4px;border:2px inset #a0a0a0;background:#fff">
                                        <option value="">-- Select Product --</option>
                                        <?php foreach ($products as $pr): ?>
                                            <option value="<?= $pr['id'] ?>" data-price="<?= $pr['selling_price'] ?>"
                                                    data-stock="<?= $pr['current_stock'] ?>"
                                                    data-unit="<?= h($pr['unit']) ?>"><?= h($pr['name'] . ' (' . $pr['sku'] . ')') ?></option><?php endforeach; ?>
                                    </select></td>
                                <td class="inv-stock text-right">-</td>
                                <td><input type="number" name="quantity[]" class="inv-qty" min="1" value="1"
                                           oninput="recalcInv()" required
                                           style="width:70px;padding:4px;border:2px inset #a0a0a0;background:#fff"></td>
                                <td><input type="number" name="unit_price[]" class="inv-price" step="0.01" min="0"
                                           value="0.00" oninput="recalcInv()" required
                                           style="width:100px;padding:4px;border:2px inset #a0a0a0;background:#fff">
                                </td>
                                <td><input type="number" name="discount_pct[]" class="inv-disc" step="0.01" min="0"
                                           max="100" value="0.00" oninput="recalcInv()"
                                           style="width:60px;padding:4px;border:2px inset #a0a0a0;background:#fff"></td>
                                <td class="inv-row-total text-right">-</td>
                                <td>
                                    <button type="button" onclick="this.closest('tr').remove();recalcInv()"
                                            class="btn btn-danger btn-xs">&#215;
                                    </button>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                        <tfoot>
                        <tr style="background:#e8e8e8">
                            <td colspan="5" class="text-right" style="padding:6px 10px;font-weight:600">Subtotal:</td>
                            <td id="inv-subtotal" class="text-right" style="padding:6px 10px">-</td>
                            <td></td>
                        </tr>
                        <tr style="background:#e8e8e8">
                            <td colspan="5" class="text-right" style="padding:4px 10px;font-size:11px">Tax:</td>
                            <td id="inv-tax" class="text-right" style="padding:4px 10px;font-size:11px">-</td>
                            <td></td>
                        </tr>
                        <tr style="background:#e8e8e8">
                            <td colspan="5" class="text-right" style="padding:4px 10px;font-size:11px">Discount:</td>
                            <td id="inv-disc-total" class="text-right" style="padding:4px 10px;font-size:11px">-</td>
                            <td></td>
                        </tr>
                        <tr style="background:#d0d0d0;font-weight:700;font-size:14px">
                            <td colspan="5" class="text-right" style="padding:8px 10px">TOTAL:</td>
                            <td id="inv-total" class="text-right" style="padding:8px 10px"></td>
                            <td></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-success">
                    &#10003; <?= $inv ? 'Update Invoice' : 'Create Invoice' ?></button>
                <a href="?page=sales" class="btn btn-secondary">Cancel</a></div>
        </form>
        <script>
            var INV_PRODUCTS = <?= json_encode($products) ?>;
            function addInvRow() {
                var opts = '<option value="">-- Select Product --</option>';
                INV_PRODUCTS.forEach(function (p) {
                    opts += '<option value="' + p.id + '" data-price="' + p.selling_price + '" data-stock="' + p.current_stock + '" data-unit="' + p.unit + '">' + p.name + ' (' + p.sku + ')</option>';
                });
                var tr = document.createElement('tr');
                tr.className = 'inv-row';
                tr.innerHTML = '<td><select name="product_id[]" class="inv-product" onchange="updateInvStock(this)" required style="width:100%;padding:4px;border:2px inset #a0a0a0;background:#fff">' + opts + '</select></td><td class="inv-stock text-right">-</td><td><input type="number" name="quantity[]" class="inv-qty" min="1" value="1" oninput="recalcInv()" required style="width:70px;padding:4px;border:2px inset #a0a0a0;background:#fff"></td><td><input type="number" name="unit_price[]" class="inv-price" step="0.01" min="0" value="0.00" oninput="recalcInv()" required style="width:100px;padding:4px;border:2px inset #a0a0a0;background:#fff"></td><td><input type="number" name="discount_pct[]" class="inv-disc" step="0.01" min="0" max="100" value="0.00" oninput="recalcInv()" style="width:60px;padding:4px;border:2px inset #a0a0a0;background:#fff"></td><td class="inv-row-total text-right">-</td><td><button type="button" onclick="this.closest(\'tr\').remove();recalcInv()" class="btn btn-danger btn-xs">&#215;</button></td>';
                document.getElementById('inv-items-body').appendChild(tr);
            }
            function updateInvStock(sel) {
                var opt = sel.options[sel.selectedIndex];
                var row = sel.closest('tr');
                var stk = opt.dataset.stock || 0;
                row.querySelector('.inv-stock').textContent = stk + ' ' + (opt.dataset.unit || '');
                row.querySelector('.inv-stock').style.color = stk <= 0 ? '#d9534f' : (stk <= 5 ? '#f0ad4e' : '#5cb85c');
                if (opt.dataset.price) row.querySelector('.inv-price').value = parseFloat(opt.dataset.price).toFixed(2);
                recalcInv();
            }
            function recalcInv() {
                var subtotal = 0;
                document.querySelectorAll('.inv-row').forEach(function (row) {
                    var qty = parseFloat(row.querySelector('.inv-qty').value) || 0;
                    var price = parseFloat(row.querySelector('.inv-price').value) || 0;
                    var disc = parseFloat(row.querySelector('.inv-disc').value) || 0;
                    var rowTotal = qty * price * (1 - disc / 100);
                    row.querySelector('.inv-row-total').textContent = CURRENCY + ' ' + rowTotal.toFixed(2);
                    subtotal += rowTotal;
                });
                var taxPct = parseFloat(document.getElementById('inv_tax_percent').value) || 0;
                var discAmt = parseFloat(document.getElementById('inv_discount').value) || 0;
                var tax = subtotal * (taxPct / 100);
                var total = subtotal + tax - discAmt;
                document.getElementById('inv-subtotal').textContent = CURRENCY + ' ' + subtotal.toFixed(2);
                document.getElementById('inv-tax').textContent = CURRENCY + ' ' + tax.toFixed(2);
                document.getElementById('inv-disc-total').textContent = '(' + CURRENCY + ' ' + discAmt.toFixed(2) + ')';
                document.getElementById('inv-total').textContent = CURRENCY + ' ' + total.toFixed(2);
            }
            recalcInv();
        </script>
    <?php
        $content = ob_get_clean();
        renderLayout(($inv ? 'Edit' : 'New') . ' Invoice', $content, 'sales');
    } elseif ($action === 'view') {
        $id = (int) ($_GET['id'] ?? 0);
        $invQ = $pdo->prepare('SELECT i.*,c.name as customer_name,c.email as customer_email,c.phone as customer_phone,c.address as customer_address,c.city as customer_city,u.full_name as created_by_name FROM invoices i JOIN customers c ON c.id=i.customer_id JOIN users u ON u.id=i.created_by WHERE i.id=?');
        $invQ->execute([$id]);
        $inv = $invQ->fetch();
        if (!$inv) {
            header('Location: ?page=sales');
            exit;
        }
        $items = $pdo->prepare('SELECT ii.*,p.name as product_name,p.sku,p.unit FROM invoice_items ii JOIN products p ON p.id=ii.product_id WHERE ii.invoice_id=?');
        $items->execute([$id]);
        $items = $items->fetchAll();
        $companyName = getSetting('company_name');
        $companyAddress = getSetting('company_address');
        $companyPhone = getSetting('company_phone');
        $companyEmail = getSetting('company_email');
        $initialPayment = (float) $pdo->query("SELECT credit FROM customer_ledgers WHERE reference_type='invoice' AND reference_id=$id")->fetchColumn();
        $subsequentPayments = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payment_receipts WHERE invoice_id=$id")->fetchColumn();
        $totalPaid = $initialPayment + $subsequentPayments;
        $balanceDue = max(0, $inv['total_amount'] - $totalPaid);
        $receipts = $pdo->query("SELECT * FROM payment_receipts WHERE invoice_id=$id ORDER BY created_at ASC")->fetchAll();
        ob_start();
        ?>
        <div class="page-header no-print"><h1>&#128065; Invoice: <?= h($inv['invoice_number']) ?></h1>
            <div class="page-header-actions">
                <?php if ($inv['payment_status'] !== 'paid'): ?>
                    <button onclick="openModal('paymentModal')" class="btn btn-success btn-sm">&#128176; Add Payment
                    </button>
                    <a href="?page=sales&action=edit&id=<?= $id ?>" class="btn btn-warning btn-sm">&#9998; Edit</a>
                <?php endif; ?>
                <button onclick="window.print()" class="btn btn-primary btn-sm">&#128438; Print</button>
                <a href="?page=sales" class="btn btn-secondary btn-sm">&#8592; Back</a>
            </div>
        </div>
        <?php if (isset($_SESSION['flash_msg'])): ?>
        <div
        class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?> no-print"><?= h($_SESSION['flash_msg']) ?></div><?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
        endif; ?>
        <div class="lf">
            <div class="print-only" style="margin-bottom:16px;border-bottom:2px solid #333;padding-bottom:12px">
                <table style="width:100%">
                    <tr>
                        <td><h2 style="font-size:20px;font-weight:700"><?= h($companyName) ?></h2>
                            <div style="font-size:11px;color:#555"><?= h($companyAddress) ?><br><?= h($companyPhone) ?>
                                | <?= h($companyEmail) ?></div>
                        </td>
                        <td style="text-align:right">
                            <div style="font-size:24px;font-weight:700;color:#4a90d9">INVOICE</div>
                            <div style="font-size:14px;font-weight:700"><?= h($inv['invoice_number']) ?></div>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="cols-2" style="margin-bottom:12px">
                <div>
                    <div style="font-size:11px;color:#666;text-transform:uppercase;font-weight:700;margin-bottom:4px">
                        Bill To
                    </div>
                    <div style="font-size:14px;font-weight:700"><?= h($inv['customer_name']) ?></div>
                    <div style="font-size:12px;color:#555;margin-top:4px"><?= h($inv['customer_email'] ?? '') ?></div>
                    <div style="font-size:12px;color:#555"><?= h($inv['customer_phone'] ?? '') ?></div>
                    <div style="font-size:12px;color:#555"><?= h($inv['customer_address'] ?? '') ?><?= $inv['customer_city'] ? ', ' . h($inv['customer_city']) : '' ?></div>
                </div>
                <div>
                    <table style="font-size:12px;width:100%">
                        <tbody>
                        <tr>
                            <td style="padding:3px 8px 3px 0;color:#666;width:45%">Invoice#:</td>
                            <td><strong><?= h($inv['invoice_number']) ?></strong></td>
                        </tr>
                        <tr>
                            <td style="padding:3px 8px 3px 0;color:#666">Invoice Date:</td>
                            <td><?= h($inv['invoice_date']) ?></td>
                        </tr>
                        <tr>
                            <td style="padding:3px 8px 3px 0;color:#666">Due Date:</td>
                            <td><?= h($inv['due_date'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td style="padding:3px 8px 3px 0;color:#666">Payment Status:</td>
                            <td>
                                <span class="badge badge-<?= ['unpaid' => 'danger', 'partial' => 'warning', 'paid' => 'success'][$inv['payment_status']] ?? 'secondary' ?>"><?= h(strtoupper($inv['payment_status'])) ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:3px 8px 3px 0;color:#666">Payment Method:</td>
                            <td><?= h(str_replace('_', ' ', strtoupper($inv['payment_method']))) ?></td>
                        </tr>
                        <tr>
                            <td style="padding:3px 8px 3px 0;color:#666">Created By:</td>
                            <td><?= h($inv['created_by_name']) ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>Unit</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Disc %</th>
                        <th class="text-right">Total</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $rn = 0;
                    foreach ($items as $item):
                        $rn++; ?>
                        <tr>
                            <td><?= $rn ?></td>
                            <td><code style="font-size:10px"><?= h($item['sku']) ?></code></td>
                            <td><?= h($item['product_name']) ?></td>
                            <td><?= h($item['unit']) ?></td>
                            <td class="text-right"><?= number_format((int) $item['quantity']) ?></td>
                            <td class="text-right"><?= formatCurrency((float) $item['unit_price']) ?></td>
                            <td class="text-right"><?= number_format((float) $item['discount_pct'], 1) ?>%</td>
                            <td class="text-right"><?= formatCurrency((float) $item['total_price']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr style="background:#e8e8e8">
                        <td colspan="7" class="text-right" style="padding:6px 10px;font-weight:600">Subtotal:</td>
                        <td class="text-right"
                            style="padding:6px 10px"><?= formatCurrency((float) $inv['subtotal']) ?></td>
                    </tr>
                    <tr style="background:#e8e8e8">
                        <td colspan="7" class="text-right" style="padding:4px 10px;font-size:11px">Tax
                            (<?= h($inv['tax_percent']) ?>%):
                        </td>
                        <td class="text-right"
                            style="padding:4px 10px;font-size:11px"><?= formatCurrency((float) $inv['subtotal'] * (float) $inv['tax_percent'] / 100) ?></td>
                    </tr>
                    <tr style="background:#e8e8e8">
                        <td colspan="7" class="text-right" style="padding:4px 10px;font-size:11px">Discount:</td>
                        <td class="text-right" style="padding:4px 10px;font-size:11px">
                            (<?= formatCurrency((float) $inv['discount']) ?>)
                        </td>
                    </tr>
                    <tr style="background:#d0d0d0;font-weight:700;font-size:16px">
                        <td colspan="7" class="text-right" style="padding:8px 10px">TOTAL:</td>
                        <td class="text-right"
                            style="padding:8px 10px"><?= formatCurrency((float) $inv['total_amount']) ?></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
            <div style="display:flex; justify-content:flex-end; margin-top:16px;">
                <table style="width:300px; font-size:13px;">
                    <tr>
                        <td style="padding:4px; color:#666;">Total Amount:</td>
                        <td class="text-right"
                            style="padding:4px; font-weight:700;"><?= formatCurrency((float) $inv['total_amount']) ?></td>
                    </tr>
                    <tr>
                        <td style="padding:4px; color:#666;">Total Paid:</td>
                        <td class="text-right"
                            style="padding:4px; font-weight:700; color:#5cb85c;"><?= formatCurrency($totalPaid) ?></td>
                    </tr>
                    <tr style="border-top:2px solid #ccc;">
                        <td style="padding:6px 4px; font-weight:700;">Balance Due:</td>
                        <td class="text-right"
                            style="padding:6px 4px; font-weight:700; color:#d9534f; font-size:16px;"><?= formatCurrency($balanceDue) ?></td>
                    </tr>
                </table>
            </div>
            <?php if ($inv['notes']): ?>
                <div style="margin-top:12px;font-size:12px"><strong>Notes:</strong> <?= h($inv['notes']) ?>
                </div><?php endif; ?>
            <?php if (!empty($receipts)): ?>
                <div class="no-print mt-8">
                    <h4 style="font-size:14px; margin-bottom:8px; border-bottom:1px solid #ccc; padding-bottom:4px;">
                        Payment Receipts</h4>
                    <table class="tbl">
                        <thead>
                        <tr>
                            <th>Receipt#</th>
                            <th>Date</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th class="text-right">Amount</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($receipts as $r): ?>
                            <tr>
                                <td><?= h($r['receipt_number']) ?></td>
                                <td><?= h($r['payment_date']) ?></td>
                                <td><?= h(ucfirst($r['method'])) ?></td>
                                <td><?= h($r['reference'] ?? '-') ?></td>
                                <td class="text-right"
                                    style="color:#5cb85c;font-weight:700"><?= formatCurrency((float) $r['amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <div class="print-only"
                 style="margin-top:30px;font-size:11px;text-align:center;color:#888;border-top:1px solid #ccc;padding-top:8px">
                Thank you for your business! &bull; <?= h($companyName) ?></div>
        </div>
        <?php if ($inv['payment_status'] !== 'paid'): ?>
        <div id="paymentModal" class="modal-overlay no-print">
            <div class="modal" style="max-width:400px;">
                <div class="modal-title-bar"><span>Record Payment</span>
                    <button class="modal-close-btn" onclick="closeModal('paymentModal')">&times;</button>
                </div>
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="add_payment" value="1">
                    <input type="hidden" name="invoice_id" value="<?= $id ?>">
                    <div class="modal-body">
                        <div class="form-group mb-8">
                            <label>Amount to Pay (Due: <?= formatCurrency($balanceDue) ?>)</label>
                            <input type="number" name="amount" step="0.01" min="0.01" max="<?= $balanceDue ?>"
                                   value="<?= $balanceDue ?>" class="form-control" required>
                        </div>
                        <div class="form-group mb-8">
                            <label>Payment Method</label>
                            <select name="method" class="form-control" required>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="credit">Credit Note</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Reference (Optional)</label>
                            <input type="text" name="reference" class="form-control"
                                   placeholder="Transaction ID, Check #...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('paymentModal')">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-success btn-sm">Save Payment</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
        <?php
        $content = ob_get_clean();
        renderLayout('Invoice: ' . $inv['invoice_number'], $content, 'sales');
    }
}

function renderAdjustments(): void
{
    $action = $_GET['action'] ?? 'list';
    $pdo = getPDO();
    if ($action === 'list') {
        $page = max(1, (int) ($_GET['pg'] ?? 1));
        $perPage = (int) (getSetting('items_per_page') ?? 20);
        $total = (int) $pdo->query('SELECT COUNT(*) FROM stock_adjustments')->fetchColumn();
        $pag = paginate($total, $perPage, $page);
        $stmt = $pdo->prepare('SELECT sa.*,p.name as product_name,p.sku,u.full_name as req_name,a.full_name as app_name FROM stock_adjustments sa JOIN products p ON p.id=sa.product_id JOIN users u ON u.id=sa.requested_by LEFT JOIN users a ON a.id=sa.approved_by ORDER BY sa.created_at DESC LIMIT ? OFFSET ?');
        $stmt->execute([$pag['per_page'], $pag['offset']]);
        $adjs = $stmt->fetchAll();
        ob_start();
        ?>
        <div class="page-header"><h1>&#9881; Stock Adjustments</h1>
            <div class="page-header-actions"><a href="?page=adjustments&action=new" class="btn btn-success btn-sm">+ New
                    Adjustment</a></div>
        </div>
        <?php if (isset($_SESSION['flash_msg'])): ?>
        <div
        class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?>"><?= h($_SESSION['flash_msg']) ?></div><?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
        endif; ?>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                <tr>
                    <th>Ref#</th>
                    <th>Product</th>
                    <th>Type</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Before</th>
                    <th class="text-right">After</th>
                    <th>Reason</th>
                    <th>Requested By</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($adjs)): ?>
                    <tr>
                        <td colspan="10" class="text-center" style="padding:16px;color:#888">No adjustments found</td>
                    </tr>
                <?php else:
            foreach ($adjs as $a):
                $sBadge = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'][$a['status']] ?? 'secondary'; ?>
                        <tr>
                            <td><code style="font-size:10px"><?= h($a['reference_no']) ?></code></td>
                            <td><?= h($a['product_name']) ?><br><small style="color:#888"><?= h($a['sku']) ?></small>
                            </td>
                            <td><?= h(str_replace('_', ' ', strtoupper($a['adjustment_type']))) ?></td>
                            <td class="text-right <?= in_array($a['adjustment_type'], ['addition', 'opening_stock']) ? 'stock-ok' : 'stock-critical' ?>"><?= number_format((int) $a['quantity']) ?></td>
                            <td class="text-right"><?= number_format((int) $a['before_stock']) ?></td>
                            <td class="text-right"><?= number_format((int) $a['after_stock']) ?></td>
                            <td><?= h(substr($a['reason'] ?? '-', 0, 40)) ?></td>
                            <td><?= h($a['req_name']) ?></td>
                            <td><span class="badge badge-<?= $sBadge ?>"><?= h(strtoupper($a['status'])) ?></span></td>
                            <td><?php if ($a['status'] === 'pending' && canAccess(['admin', 'manager'])): ?>
                                    <div class="tbl-actions">
                                        <button onclick="approveAdj(<?= $a['id'] ?>)" class="btn btn-success btn-xs">
                                            &#10003;
                                        </button>
                                        <button onclick="rejectAdj(<?= $a['id'] ?>)" class="btn btn-danger btn-xs">
                                            &#215;
                                        </button>
                                    </div>
                                <?php endif; ?></td>
                        </tr>
                    <?php endforeach;
        endif; ?>
                </tbody>
            </table>
        </div>
    <?= paginationHtml($pag, '?page=adjustments') ?>
        <script>
            function approveAdj(id) {
                confirmAction('Approve this stock adjustment? Stock will be updated.', function () {
                    apiPost('?ajax=approve_adj', {id: id}, function (d) {
                        showToast('Adjustment approved.', 'success');
                        setTimeout(function () {
                            location.reload();
                        }, 800);
                    });
                }, 'Approve Adjustment');
            }
            function rejectAdj(id) {
                confirmAction('Reject this adjustment?', function () {
                    apiPost('?ajax=reject_adj', {id: id}, function (d) {
                        showToast('Adjustment rejected.', 'success');
                        setTimeout(function () {
                            location.reload();
                        }, 800);
                    });
                }, 'Reject Adjustment');
            }
        </script>
    <?php
        $content = ob_get_clean();
        renderLayout('Stock Adjustments', $content, 'adjustments');
    } elseif ($action === 'new') {
        $preProductId = (int) ($_GET['product_id'] ?? 0);
        $products = $pdo->query("SELECT id,name,sku,current_stock,unit FROM products WHERE status='active' ORDER BY name")->fetchAll();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
            $productId = (int) ($_POST['product_id'] ?? 0);
            $adjType = $_POST['adjustment_type'] ?? 'addition';
            $qty = (int) ($_POST['quantity'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');
            if (!$productId || $qty <= 0) {
                $_SESSION['flash_msg'] = 'Product and quantity required.';
                $_SESSION['flash_type'] = 'danger';
            } else {
                $prodQ = $pdo->prepare('SELECT current_stock FROM products WHERE id=?');
                $prodQ->execute([$productId]);
                $prod = $prodQ->fetch();
                if (!$prod) {
                    $_SESSION['flash_msg'] = 'Product not found.';
                    $_SESSION['flash_type'] = 'danger';
                } else {
                    $beforeStock = (int) $prod['current_stock'];
                    $addTypes = ['addition', 'opening_stock'];
                    $afterStock = in_array($adjType, $addTypes) ? $beforeStock + $qty : max(0, $beforeStock - $qty);
                    $ref = generateRefNumber('ADJ');
                    $autoApprove = canAccess(['admin', 'manager']);
                    $status = $autoApprove ? 'approved' : 'pending';
                    $approvedBy = $autoApprove ? (int) $_SESSION['user_id'] : null;
                    $approvedAt = $autoApprove ? date('Y-m-d H:i:s') : null;
                    $pdo->prepare('INSERT INTO stock_adjustments (reference_no,product_id,adjustment_type,quantity,before_stock,after_stock,reason,status,requested_by,approved_by,approved_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)')->execute([$ref, $productId, $adjType, $qty, $beforeStock, $afterStock, $reason, $status, (int) $_SESSION['user_id'], $approvedBy, $approvedAt]);
                    if ($autoApprove)
                        $pdo->prepare('UPDATE products SET current_stock=?,updated_at=NOW() WHERE id=?')->execute([$afterStock, $productId]);
                    logActivity($_SESSION['user_id'], 'CREATE', 'adjustments', 'Created stock adjustment: ' . $ref);
                    $_SESSION['flash_msg'] = $autoApprove ? 'Adjustment applied. Stock updated.' : 'Adjustment submitted for approval.';
                    $_SESSION['flash_type'] = 'success';
                    header('Location: ?page=adjustments');
                    exit;
                }
            }
        }
        ob_start();
        ?>
        <div class="page-header"><h1>+ New Stock Adjustment</h1>
            <div class="page-header-actions"><a href="?page=adjustments" class="btn btn-secondary btn-sm">&#8592;
                    Back</a></div>
        </div>
        <?php if (isset($_SESSION['flash_msg'])): ?>
        <div
        class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?>"><?= h($_SESSION['flash_msg']) ?></div><?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
        endif; ?>
        <?php if (!canAccess(['admin', 'manager'])): ?>
        <div class="alert alert-info">&#128274; As Staff, adjustments require Manager or Admin approval before stock is
            updated.
        </div><?php endif; ?>
        <form method="POST" action="?page=adjustments&action=new">
            <?= csrfField() ?>
            <div class="lf"><span class="lf-title">Adjustment Details</span>
                <div class="form-grid form-grid-2">
                    <div class="form-group"><label>Product <span class="required-mark">*</span></label>
                        <select name="product_id" id="adj-product" onchange="updateAdjStock()" required>
                            <option value="">-- Select Product --</option>
                            <?php foreach ($products as $pr): ?>
                                <option value="<?= $pr['id'] ?>" data-stock="<?= $pr['current_stock'] ?>"
                                        data-unit="<?= h($pr['unit']) ?>" <?= $preProductId == $pr['id'] ? 'selected' : '' ?>><?= h($pr['name'] . ' (' . $pr['sku'] . ')') ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Current Stock</label><input type="text" id="adj-current-stock"
                                                                               readonly
                                                                               style="background:#e8e8e8;font-weight:700;font-size:14px"
                                                                               value="-"></div>
                    <div class="form-group"><label>Adjustment Type <span class="required-mark">*</span></label>
                        <select name="adjustment_type" id="adj-type" onchange="updateAdjPreview()">
                            <option value="addition">Addition (+ Stock)</option>
                            <option value="subtraction">Subtraction (- Stock)</option>
                            <option value="damage">Damage (- Stock)</option>
                            <option value="expired">Expired (- Stock)</option>
                            <option value="correction">Correction (Set exact)</option>
                            <option value="opening_stock">Opening Stock (+ Stock)</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Quantity <span class="required-mark">*</span></label><input
                                type="number" name="quantity" id="adj-qty" min="1" value="1"
                                oninput="updateAdjPreview()" required></div>
                    <div class="form-group"><label>New Stock Preview</label><input type="text" id="adj-new-stock"
                                                                                   readonly
                                                                                   style="background:#e8e8e8;font-weight:700;font-size:14px;color:#4a90d9"
                                                                                   value="-"></div>
                    <div class="form-group full"><label>Reason / Notes</label><textarea name="reason" rows="2"
                                                                                        placeholder="Reason for adjustment..."></textarea>
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-success">&#10003; Submit Adjustment</button>
                <a href="?page=adjustments" class="btn btn-secondary">Cancel</a></div>
        </form>
        <script>
            function updateAdjStock() {
                var sel = document.getElementById('adj-product');
                var opt = sel.options[sel.selectedIndex];
                var stk = opt.dataset.stock || 0;
                document.getElementById('adj-current-stock').value = stk + ' ' + (opt.dataset.unit || '');
                updateAdjPreview();
            }
            function updateAdjPreview() {
                var sel = document.getElementById('adj-product');
                var opt = sel.options[sel.selectedIndex];
                var stk = parseInt(opt.dataset.stock || 0);
                var qty = parseInt(document.getElementById('adj-qty').value) || 0;
                var type = document.getElementById('adj-type').value;
                var addTypes = ['addition', 'opening_stock'];
                var newStk = addTypes.includes(type) ? stk + qty : Math.max(0, stk - qty);
                var el = document.getElementById('adj-new-stock');
                el.value = newStk + ' ' + (opt.dataset.unit || '');
                el.style.color = newStk <= 0 ? '#d9534f' : (newStk < 5 ? '#f0ad4e' : '#5cb85c');
            }
            if (document.getElementById('adj-product').value) updateAdjStock();
        </script>
        <?php
        $content = ob_get_clean();
        renderLayout('New Stock Adjustment', $content, 'adjustments');
    }
}

function renderTransfers(): void
{
    requireRole(['admin', 'manager']);
    $action = $_GET['action'] ?? 'list';
    $pdo = getPDO();
    $warehouses = $pdo->query("SELECT * FROM warehouses WHERE status='active' ORDER BY name")->fetchAll();
    if ($action === 'list') {
        $page = max(1, (int) ($_GET['pg'] ?? 1));
        $perPage = (int) (getSetting('items_per_page') ?? 20);
        $total = (int) $pdo->query('SELECT COUNT(*) FROM stock_transfers')->fetchColumn();
        $pag = paginate($total, $perPage, $page);
        $stmt = $pdo->prepare('SELECT st.*,fw.name as from_name,tw.name as to_name,u.full_name as created_by_name FROM stock_transfers st JOIN warehouses fw ON fw.id=st.from_warehouse_id JOIN warehouses tw ON tw.id=st.to_warehouse_id JOIN users u ON u.id=st.created_by ORDER BY st.created_at DESC LIMIT ? OFFSET ?');
        $stmt->execute([$pag['per_page'], $pag['offset']]);
        $transfers = $stmt->fetchAll();
        ob_start();
        ?>
        <div class="page-header"><h1>&#8644; Stock Transfers</h1>
            <div class="page-header-actions"><a href="?page=transfers&action=new" class="btn btn-success btn-sm">+ New
                    Transfer</a></div>
        </div>
        <?php if (isset($_SESSION['flash_msg'])): ?>
        <div
        class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?>"><?= h($_SESSION['flash_msg']) ?></div><?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
        endif; ?>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                <tr>
                    <th>Ref#</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($transfers)): ?>
                    <tr>
                        <td colspan="7" class="text-center" style="padding:16px;color:#888">No transfers found</td>
                    </tr>
                <?php else:
            foreach ($transfers as $t):
                $sBadge = ['pending' => 'warning', 'in_transit' => 'info', 'completed' => 'success', 'cancelled' => 'danger'][$t['status']] ?? 'secondary'; ?>
                        <tr>
                            <td><code style="font-size:10px"><?= h($t['reference_no']) ?></code></td>
                            <td><?= h($t['from_name']) ?></td>
                            <td><?= h($t['to_name']) ?></td>
                            <td><?= h($t['transfer_date']) ?></td>
                            <td>
                                <span class="badge badge-<?= $sBadge ?>"><?= h(str_replace('_', ' ', strtoupper($t['status']))) ?></span>
                            </td>
                            <td><?= h($t['created_by_name']) ?></td>
                            <td>
                                <div class="tbl-actions">
                                    <?php if ($t['status'] === 'pending'): ?>
                                        <button onclick="completeTransfer(<?= $t['id'] ?>)"
                                                class="btn btn-success btn-xs">&#10003; Complete
                                        </button>
                                        <button onclick="cancelTransfer(<?= $t['id'] ?>)" class="btn btn-danger btn-xs">
                                            &#215; Cancel
                                        </button>
                                    <?php else: ?>
                                        <span style="font-size:11px;color:#888"><?= h(strtoupper($t['status'])) ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach;
        endif; ?>
                </tbody>
            </table>
        </div>
    <?= paginationHtml($pag, '?page=transfers') ?>
        <script>
            function completeTransfer(id) {
                confirmAction('Mark transfer as completed? Stock will be moved.', function () {
                    apiPost('?ajax=complete_transfer', {id: id}, function () {
                        showToast('Transfer completed.', 'success');
                        setTimeout(function () {
                            location.reload();
                        }, 800);
                    });
                }, 'Complete Transfer');
            }
            function cancelTransfer(id) {
                confirmAction('Cancel this transfer?', function () {
                    apiPost('?ajax=cancel_transfer', {id: id}, function () {
                        showToast('Transfer cancelled.', 'success');
                        setTimeout(function () {
                            location.reload();
                        }, 800);
                    });
                }, 'Cancel Transfer');
            }
        </script>
    <?php
        $content = ob_get_clean();
        renderLayout('Stock Transfers', $content, 'transfers');
    } elseif ($action === 'new') {
        $products = $pdo->query("SELECT id,name,sku,current_stock,unit FROM products WHERE status='active' ORDER BY name")->fetchAll();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
            $fromId = (int) ($_POST['from_warehouse_id'] ?? 0);
            $toId = (int) ($_POST['to_warehouse_id'] ?? 0);
            $transferDate = trim($_POST['transfer_date'] ?? date('Y-m-d'));
            $notes = trim($_POST['notes'] ?? '');
            $productIds = $_POST['product_id'] ?? [];
            $quantities = $_POST['quantity'] ?? [];
            if ($fromId === $toId || !$fromId || !$toId) {
                $_SESSION['flash_msg'] = 'Please select two different warehouses.';
                $_SESSION['flash_type'] = 'danger';
            } elseif (empty($productIds)) {
                $_SESSION['flash_msg'] = 'Add at least one product.';
                $_SESSION['flash_type'] = 'danger';
            } else {
                $ref = generateRefNumber('TRF');
                $pdo->prepare("INSERT INTO stock_transfers (reference_no,from_warehouse_id,to_warehouse_id,transfer_date,status,notes,created_by) VALUES (?,?,?,?,'pending',?,?)")->execute([$ref, $fromId, $toId, $transferDate, $notes, (int) $_SESSION['user_id']]);
                $tId = (int) $pdo->lastInsertId();
                foreach ($productIds as $i => $pid) {
                    $qty = (int) ($quantities[$i] ?? 0);
                    if ($pid && $qty > 0)
                        $pdo->prepare('INSERT INTO stock_transfer_items (transfer_id,product_id,quantity) VALUES (?,?,?)')->execute([$tId, (int) $pid, $qty]);
                }
                logActivity($_SESSION['user_id'], 'CREATE', 'transfers', 'Created transfer: ' . $ref);
                $_SESSION['flash_msg'] = 'Transfer created (Pending).';
                $_SESSION['flash_type'] = 'success';
                header('Location: ?page=transfers');
                exit;
            }
        }
        ob_start();
        ?>
        <div class="page-header"><h1>+ New Stock Transfer</h1>
            <div class="page-header-actions"><a href="?page=transfers" class="btn btn-secondary btn-sm">&#8592; Back</a>
            </div>
        </div>
        <?php if (isset($_SESSION['flash_msg'])): ?>
        <div
        class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?>"><?= h($_SESSION['flash_msg']) ?></div><?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
        endif; ?>
        <form method="POST" action="?page=transfers&action=new">
            <?= csrfField() ?>
            <div class="lf"><span class="lf-title">Transfer Details</span>
                <div class="form-grid form-grid-3">
                    <div class="form-group"><label>From Warehouse <span class="required-mark">*</span></label><select
                                name="from_warehouse_id" required>
                            <option value="">-- Select --</option><?php foreach ($warehouses as $w): ?>
                                <option value="<?= $w['id'] ?>"><?= h($w['name']) ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="form-group"><label>To Warehouse <span class="required-mark">*</span></label><select
                                name="to_warehouse_id" required>
                            <option value="">-- Select --</option><?php foreach ($warehouses as $w): ?>
                                <option value="<?= $w['id'] ?>"><?= h($w['name']) ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="form-group"><label>Transfer Date <span class="required-mark">*</span></label><input
                                type="date" name="transfer_date" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="form-group full"><label>Notes</label><textarea name="notes" rows="2"></textarea></div>
                </div>
            </div>
            <div class="lf"><span class="lf-title">Items to Transfer</span>
                <div style="margin-bottom:10px">
                    <button type="button" onclick="addTrfRow()" class="btn btn-primary btn-sm">+ Add Product</button>
                </div>
                <div class="tbl-wrap">
                    <table class="tbl">
                        <thead>
                        <tr>
                            <th>Product</th>
                            <th>Available Stock</th>
                            <th style="width:130px">Transfer Qty</th>
                            <th style="width:40px"></th>
                        </tr>
                        </thead>
                        <tbody id="trf-body">
                        <tr class="trf-row">
                            <td><select name="product_id[]" class="trf-product" onchange="updateTrfStock(this)" required
                                        style="width:100%;padding:4px;border:2px inset #a0a0a0;background:#fff">
                                    <option value="">-- Select Product --</option><?php foreach ($products as $pr): ?>
                                        <option value="<?= $pr['id'] ?>" data-stock="<?= $pr['current_stock'] ?>"
                                                data-unit="<?= h($pr['unit']) ?>"><?= h($pr['name'] . ' (' . $pr['sku'] . ')') ?></option><?php endforeach; ?>
                                </select></td>
                            <td class="trf-stock text-right">-</td>
                            <td><input type="number" name="quantity[]" min="1" value="1" required
                                       style="width:90px;padding:4px;border:2px inset #a0a0a0;background:#fff"></td>
                            <td>
                                <button type="button" onclick="this.closest('tr').remove()"
                                        class="btn btn-danger btn-xs">&#215;
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-success">&#10003; Create Transfer</button>
                <a href="?page=transfers" class="btn btn-secondary">Cancel</a></div>
        </form>
        <script>
            var TRF_PRODUCTS = <?= json_encode($products) ?>;
            function addTrfRow() {
                var opts = '<option value="">-- Select Product --</option>';
                TRF_PRODUCTS.forEach(function (p) {
                    opts += '<option value="' + p.id + '" data-stock="' + p.current_stock + '" data-unit="' + p.unit + '">' + p.name + ' (' + p.sku + ')</option>';
                });
                var tr = document.createElement('tr');
                tr.className = 'trf-row';
                tr.innerHTML = '<td><select name="product_id[]" class="trf-product" onchange="updateTrfStock(this)" required style="width:100%;padding:4px;border:2px inset #a0a0a0;background:#fff">' + opts + '</select></td><td class="trf-stock text-right">-</td><td><input type="number" name="quantity[]" min="1" value="1" required style="width:90px;padding:4px;border:2px inset #a0a0a0;background:#fff"></td><td><button type="button" onclick="this.closest(\'tr\').remove()" class="btn btn-danger btn-xs">&#215;</button></td>';
                document.getElementById('trf-body').appendChild(tr);
            }
            function updateTrfStock(sel) {
                var opt = sel.options[sel.selectedIndex];
                sel.closest('tr').querySelector('.trf-stock').textContent = (opt.dataset.stock || '-') + ' ' + (opt.dataset.unit || '');
            }
        </script>
        <?php
        $content = ob_get_clean();
        renderLayout('New Stock Transfer', $content, 'transfers');
    }
}

function renderReports(): void
{
    requireRole(['admin', 'manager']);
    $pdo = getPDO();
    $reportType = $_GET['report'] ?? 'stock';
    $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');
    $exportCsv = !empty($_GET['export']) && $_GET['export'] === 'csv';
    ob_start();
    ?>
    <div class="page-header"><h1>&#128202; Reports</h1></div>
    <div class="filter-bar">
        <div class="form-group"><label>Report Type</label>
            <select onchange="window.location='?page=reports&report='+this.value+'&date_from=<?= h($dateFrom) ?>&date_to=<?= h($dateTo) ?>'">
                <?php $rTypes = ['stock' => 'Stock Report', 'sales' => 'Sales Report', 'purchases' => 'Purchase Report', 'profit_loss' => 'Profit & Loss', 'low_stock' => 'Low Stock Report', 'valuation' => 'Inventory Valuation', 'supplier_wise' => 'Supplier-wise Purchases', 'customer_wise' => 'Customer-wise Sales', 'product_wise' => 'Product-wise Sales', 'payment_status' => 'Payment Status', 'stock_movement' => 'Stock Movement', 'dead_stock' => 'Dead Stock Report'];
                foreach ($rTypes as $k => $v): ?>
                    <option
                    value="<?= $k ?>" <?= $reportType === $k ? 'selected' : '' ?>><?= h($v) ?></option><?php endforeach; ?>
            </select>
        </div>
        <?php if (!in_array($reportType, ['stock', 'low_stock', 'valuation', 'dead_stock'])): ?>
            <div class="form-group"><label>Date From</label><input type="date" id="df" value="<?= h($dateFrom) ?>">
            </div>
            <div class="form-group"><label>Date To</label><input type="date" id="dt" value="<?= h($dateTo) ?>"></div>
            <div class="form-group" style="justify-content:flex-end;flex-direction:row;align-items:flex-end;gap:6px">
                <button class="btn btn-primary btn-sm" onclick="applyFilter()">&#128202; Run Report</button>
            </div>
        <?php endif; ?>
        <div class="form-group" style="justify-content:flex-end;flex-direction:row;align-items:flex-end;gap:6px">
            <a href="?page=reports&report=<?= h($reportType) ?>&date_from=<?= h($dateFrom) ?>&date_to=<?= h($dateTo) ?>&export=csv"
               class="btn btn-secondary btn-sm">&#8595; Export CSV</a>
        </div>
    </div>
<?php
    if ($reportType === 'stock') {
        $rows = $pdo->query('SELECT p.*,c.name as category_name,w.name as warehouse_name FROM products p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN warehouses w ON w.id=p.warehouse_id ORDER BY p.name')->fetchAll();
        if ($exportCsv) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="stock_report_' . date('Ymd') . '.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['SKU', 'Name', 'Category', 'Brand', 'Unit', 'Purchase Price', 'Selling Price', 'Current Stock', 'Min Stock', 'Status', 'Stock Value']);
            foreach ($rows as $r)
                fputcsv($out, [$r['sku'], $r['name'], $r['category_name'] ?? '', $r['brand'], $r['unit'], $r['purchase_price'], $r['selling_price'], $r['current_stock'], $r['min_stock_level'], $r['status'], number_format($r['current_stock'] * $r['purchase_price'], 2)]);
            fclose($out);
            exit;
        }
        echo '<div class="tbl-wrap"><table class="tbl"><thead><tr><th>SKU</th><th>Name</th><th>Category</th><th>Unit</th><th class="text-right">Sell Price</th><th class="text-right">Stock</th><th>Min</th><th>Status</th><th class="text-right">Stock Value</th></tr></thead><tbody>';
        $totalVal = 0;
        foreach ($rows as $r) {
            $sc = $r['current_stock'] <= 0 ? 'critical' : ($r['current_stock'] <= $r['min_stock_level'] ? 'low' : 'ok');
            $val = $r['current_stock'] * $r['purchase_price'];
            $totalVal += $val;
            echo '<tr><td><code style="font-size:10px">' . h($r['sku']) . '</code></td><td>' . h($r['name']) . '</td><td>' . h($r['category_name'] ?? '-') . '</td><td>' . h($r['unit']) . '</td><td class="text-right">' . formatCurrency((float) $r['selling_price']) . '</td><td class="text-right stock-' . $sc . '">' . number_format((int) $r['current_stock']) . '</td><td class="text-right">' . number_format((int) $r['min_stock_level']) . '</td><td><span class="badge badge-' . ($r['status'] === 'active' ? 'success' : 'secondary') . '">' . h(strtoupper($r['status'])) . '</span></td><td class="text-right">' . formatCurrency($val) . '</td></tr>';
        }
        echo '<tr style="background:#d0d0d0;font-weight:700"><td colspan="8" class="text-right" style="padding:8px 10px">Total Inventory Value:</td><td class="text-right" style="padding:8px 10px">' . formatCurrency($totalVal) . '</td></tr>';
        echo '</tbody></table></div>';
    } elseif ($reportType === 'sales') {
        $rows = $pdo->query("SELECT i.*,c.name as customer_name FROM invoices i JOIN customers c ON c.id=i.customer_id WHERE DATE(i.invoice_date) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY i.invoice_date DESC")->fetchAll();
        if ($exportCsv) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="sales_report_' . date('Ymd') . '.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Invoice#', 'Customer', 'Date', 'Subtotal', 'Tax%', 'Discount', 'Total', 'Payment Status', 'Method']);
            foreach ($rows as $r)
                fputcsv($out, [$r['invoice_number'], $r['customer_name'], $r['invoice_date'], $r['subtotal'], $r['tax_percent'], $r['discount'], $r['total_amount'], $r['payment_status'], $r['payment_method']]);
            fclose($out);
            exit;
        }
        $total = array_sum(array_column($rows, 'total_amount'));
        echo '<div style="padding:8px;background:#d4edda;border:1px solid #c3e6cb;margin-bottom:10px;font-size:12px"><strong>Total Sales (' . date('d M Y', strtotime($dateFrom)) . ' - ' . date('d M Y', strtotime($dateTo)) . '):</strong> ' . formatCurrency($total) . ' (' . count($rows) . ' invoices)</div>';
        echo '<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Invoice#</th><th>Customer</th><th>Date</th><th class="text-right">Subtotal</th><th class="text-right">Tax</th><th class="text-right">Discount</th><th class="text-right">Total</th><th>Payment</th><th>Method</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $psBadge = ['unpaid' => 'danger', 'partial' => 'warning', 'paid' => 'success'][$r['payment_status']] ?? 'secondary';
            echo '<tr><td><a href="?page=sales&action=view&id=' . $r['id'] . '">' . h($r['invoice_number']) . '</a></td><td>' . h($r['customer_name']) . '</td><td>' . h($r['invoice_date']) . '</td><td class="text-right">' . formatCurrency((float) $r['subtotal']) . '</td><td class="text-right">' . formatCurrency((float) $r['subtotal'] * (float) $r['tax_percent'] / 100) . '</td><td class="text-right">' . formatCurrency((float) $r['discount']) . '</td><td class="text-right">' . formatCurrency((float) $r['total_amount']) . '</td><td><span class="badge badge-' . $psBadge . '">' . h(strtoupper($r['payment_status'])) . '</span></td><td>' . h(str_replace('_', ' ', strtoupper($r['payment_method']))) . '</td></tr>';
        }
        if (empty($rows))
            echo '<tr><td colspan="9" class="text-center" style="padding:16px;color:#888">No sales in this period</td></tr>';
        echo '</tbody></table></div>';
    } elseif ($reportType === 'purchases') {
        $rows = $pdo->query("SELECT po.*,s.company_name FROM purchase_orders po JOIN suppliers s ON s.id=po.supplier_id WHERE DATE(po.po_date) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY po.po_date DESC")->fetchAll();
        if ($exportCsv) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="purchase_report_' . date('Ymd') . '.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['PO#', 'Supplier', 'Date', 'Total', 'Order Status', 'Payment Status']);
            foreach ($rows as $r)
                fputcsv($out, [$r['po_number'], $r['company_name'], $r['po_date'], $r['total_amount'], $r['order_status'], $r['payment_status']]);
            fclose($out);
            exit;
        }
        $total = array_sum(array_column($rows, 'total_amount'));
        echo '<div style="padding:8px;background:#d1ecf1;border:1px solid #bee5eb;margin-bottom:10px;font-size:12px"><strong>Total Purchases:</strong> ' . formatCurrency($total) . ' (' . count($rows) . ' orders)</div>';
        echo '<div class="tbl-wrap"><table class="tbl"><thead><tr><th>PO#</th><th>Supplier</th><th>Date</th><th class="text-right">Total</th><th>Order Status</th><th>Payment</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $osBadge = ['pending' => 'warning', 'partial' => 'info', 'received' => 'success', 'cancelled' => 'danger'][$r['order_status']] ?? 'secondary';
            $psBadge = ['unpaid' => 'danger', 'partial' => 'warning', 'paid' => 'success'][$r['payment_status']] ?? 'secondary';
            echo '<tr><td><a href="?page=purchases&action=view&id=' . $r['id'] . '">' . h($r['po_number']) . '</a></td><td>' . h($r['company_name']) . '</td><td>' . h($r['po_date']) . '</td><td class="text-right">' . formatCurrency((float) $r['total_amount']) . '</td><td><span class="badge badge-' . $osBadge . '">' . h(strtoupper($r['order_status'])) . '</span></td><td><span class="badge badge-' . $psBadge . '">' . h(strtoupper($r['payment_status'])) . '</span></td></tr>';
        }
        if (empty($rows))
            echo '<tr><td colspan="6" class="text-center" style="padding:16px;color:#888">No purchases in this period</td></tr>';
        echo '</tbody></table></div>';
    } elseif ($reportType === 'profit_loss') {
        $revenue = (float) $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM invoices WHERE DATE(invoice_date) BETWEEN '$dateFrom' AND '$dateTo'")->fetchColumn();
        $cogsSql = "SELECT COALESCE(SUM(ii.quantity * p.purchase_price),0) FROM invoice_items ii JOIN invoices i ON i.id=ii.invoice_id JOIN products p ON p.id=ii.product_id WHERE DATE(i.invoice_date) BETWEEN '$dateFrom' AND '$dateTo'";
        $cogs = (float) $pdo->query($cogsSql)->fetchColumn();
        $grossProfit = $revenue - $cogs;
        $grossMargin = $revenue > 0 ? ($grossProfit / $revenue * 100) : 0;
        echo '<div class="lf"><span class="lf-title">Profit & Loss: ' . h(date('d M Y', strtotime($dateFrom))) . ' to ' . h(date('d M Y', strtotime($dateTo))) . '</span>';
        echo '<table style="width:100%;max-width:500px;font-size:13px"><tbody>';
        echo '<tr style="background:#d4edda"><td style="padding:8px;font-weight:700">Total Revenue (Sales)</td><td style="padding:8px;text-align:right;font-weight:700;color:#155724">' . formatCurrency($revenue) . '</td></tr>';
        echo '<tr style="background:#f8d7da"><td style="padding:8px;font-weight:700">Cost of Goods Sold (COGS)</td><td style="padding:8px;text-align:right;font-weight:700;color:#721c24">(' . formatCurrency($cogs) . ')</td></tr>';
        echo '<tr style="background:' . ($grossProfit >= 0 ? '#cce5ff' : '#f8d7da') . ';font-size:15px"><td style="padding:10px;font-weight:700">Gross Profit</td><td style="padding:10px;text-align:right;font-weight:700;color:' . ($grossProfit >= 0 ? '#004085' : '#721c24') . '">' . formatCurrency($grossProfit) . '</td></tr>';
        echo '<tr style="background:#f0f0f0"><td style="padding:8px;color:#555">Gross Margin</td><td style="padding:8px;text-align:right;color:' . ($grossMargin >= 0 ? '#5cb85c' : '#d9534f') . ';font-weight:700">' . number_format($grossMargin, 2) . '%</td></tr>';
        echo '</tbody></table></div>';
    } elseif ($reportType === 'low_stock') {
        $rows = $pdo->query("SELECT p.*,c.name as category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.current_stock<=p.min_stock_level AND p.status='active' ORDER BY p.current_stock ASC")->fetchAll();
        if ($exportCsv) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="low_stock_' . date('Ymd') . '.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['SKU', 'Name', 'Category', 'Current Stock', 'Min Stock', 'Shortage']);
            foreach ($rows as $r)
                fputcsv($out, [$r['sku'], $r['name'], $r['category_name'] ?? '', $r['current_stock'], $r['min_stock_level'], $r['min_stock_level'] - $r['current_stock']]);
            fclose($out);
            exit;
        }
        echo '<div class="tbl-wrap"><table class="tbl"><thead><tr><th>SKU</th><th>Name</th><th>Category</th><th class="text-right">Current Stock</th><th class="text-right">Min Level</th><th class="text-right">Shortage</th><th>Action</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $sc = $r['current_stock'] <= 0 ? 'critical' : 'low';
            echo '<tr><td><code style="font-size:10px">' . h($r['sku']) . '</code></td><td>' . h($r['name']) . '</td><td>' . h($r['category_name'] ?? '-') . '</td><td class="text-right stock-' . $sc . '">' . number_format((int) $r['current_stock']) . '</td><td class="text-right">' . number_format((int) $r['min_stock_level']) . '</td><td class="text-right stock-critical">' . number_format(max(0, $r['min_stock_level'] - $r['current_stock'])) . '</td><td><a href="?page=purchases&action=new" class="btn btn-primary btn-xs">Order Now</a></td></tr>';
        }
        if (empty($rows))
            echo '<tr><td colspan="7" class="text-center" style="padding:16px;color:#5cb85c;font-weight:700">&#10003; All products are adequately stocked!</td></tr>';
        echo '</tbody></table></div>';
    } elseif ($reportType === 'valuation') {
        $totalPurchaseVal = (float) $pdo->query("SELECT COALESCE(SUM(current_stock*purchase_price),0) FROM products WHERE status='active'")->fetchColumn();
        $totalSellingVal = (float) $pdo->query("SELECT COALESCE(SUM(current_stock*selling_price),0) FROM products WHERE status='active'")->fetchColumn();
        $potentialProfit = $totalSellingVal - $totalPurchaseVal;
        echo '<div class="stats-grid" style="max-width:600px;margin-bottom:16px">';
        echo '<div class="stat-card primary"><div class="stat-card-title">Total at Cost Price</div><div class="stat-card-value" style="font-size:16px">' . formatCurrency($totalPurchaseVal) . '</div></div>';
        echo '<div class="stat-card success"><div class="stat-card-title">Total at Selling Price</div><div class="stat-card-value" style="font-size:16px">' . formatCurrency($totalSellingVal) . '</div></div>';
        echo '<div class="stat-card warning"><div class="stat-card-title">Potential Gross Profit</div><div class="stat-card-value" style="font-size:16px">' . formatCurrency($potentialProfit) . '</div></div>';
        echo '</div>';
        $rows = $pdo->query("SELECT p.*,c.name as category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.status='active' AND p.current_stock>0 ORDER BY (p.current_stock*p.purchase_price) DESC")->fetchAll();
        echo '<div class="tbl-wrap"><table class="tbl"><thead><tr><th>SKU</th><th>Name</th><th>Category</th><th class="text-right">Stock</th><th class="text-right">Cost Price</th><th class="text-right">Sell Price</th><th class="text-right">Stock Value (Cost)</th><th class="text-right">Stock Value (Sell)</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            echo '<tr><td><code style="font-size:10px">' . h($r['sku']) . '</code></td><td>' . h($r['name']) . '</td><td>' . h($r['category_name'] ?? '-') . '</td><td class="text-right">' . number_format((int) $r['current_stock']) . '</td><td class="text-right">' . formatCurrency((float) $r['purchase_price']) . '</td><td class="text-right">' . formatCurrency((float) $r['selling_price']) . '</td><td class="text-right">' . formatCurrency($r['current_stock'] * $r['purchase_price']) . '</td><td class="text-right">' . formatCurrency($r['current_stock'] * $r['selling_price']) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    } elseif ($reportType === 'supplier_wise') {
        $rows = $pdo->query("SELECT s.company_name,COUNT(po.id) as po_count,COALESCE(SUM(po.total_amount),0) as total FROM suppliers s LEFT JOIN purchase_orders po ON po.supplier_id=s.id WHERE po.po_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY s.id,s.company_name ORDER BY total DESC")->fetchAll();
        echo '<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Supplier</th><th class="text-right">PO Count</th><th class="text-right">Total Purchased</th></tr></thead><tbody>';
        foreach ($rows as $r)
            echo '<tr><td>' . h($r['company_name']) . '</td><td class="text-right">' . number_format((int) $r['po_count']) . '</td><td class="text-right">' . formatCurrency((float) $r['total']) . '</td></tr>';
        if (empty($rows))
            echo '<tr><td colspan="3" class="text-center" style="padding:16px;color:#888">No data in this period</td></tr>';
        echo '</tbody></table></div>';
    } elseif ($reportType === 'customer_wise') {
        $rows = $pdo->query("SELECT c.name,COUNT(i.id) as inv_count,COALESCE(SUM(i.total_amount),0) as total FROM customers c LEFT JOIN invoices i ON i.customer_id=c.id WHERE i.invoice_date BETWEEN '$dateFrom' AND '$dateTo' GROUP BY c.id,c.name ORDER BY total DESC")->fetchAll();
        echo '<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Customer</th><th class="text-right">Invoice Count</th><th class="text-right">Total Sales</th></tr></thead><tbody>';
        foreach ($rows as $r)
            echo '<tr><td>' . h($r['name']) . '</td><td class="text-right">' . number_format((int) $r['inv_count']) . '</td><td class="text-right">' . formatCurrency((float) $r['total']) . '</td></tr>';
        if (empty($rows))
            echo '<tr><td colspan="3" class="text-center" style="padding:16px;color:#888">No data in this period</td></tr>';
        echo '</tbody></table></div>';
    } elseif ($reportType === 'product_wise') {
        $rows = $pdo->query("SELECT p.name,p.sku,COALESCE(SUM(ii.quantity),0) as qty_sold,COALESCE(SUM(ii.total_price),0) as revenue FROM products p LEFT JOIN invoice_items ii ON ii.product_id=p.id LEFT JOIN invoices inv ON inv.id=ii.invoice_id WHERE (inv.invoice_date IS NULL OR inv.invoice_date BETWEEN '$dateFrom' AND '$dateTo') GROUP BY p.id,p.name,p.sku ORDER BY qty_sold DESC")->fetchAll();
        echo '<div class="tbl-wrap"><table class="tbl"><thead><tr><th>SKU</th><th>Product</th><th class="text-right">Qty Sold</th><th class="text-right">Revenue</th></tr></thead><tbody>';
        foreach ($rows as $r)
            echo '<tr><td><code style="font-size:10px">' . h($r['sku']) . '</code></td><td>' . h($r['name']) . '</td><td class="text-right">' . number_format((int) $r['qty_sold']) . '</td><td class="text-right">' . formatCurrency((float) $r['revenue']) . '</td></tr>';
        echo '</tbody></table></div>';
    } elseif ($reportType === 'payment_status') {
        $unpaidInv = (float) $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM invoices WHERE payment_status='unpaid'")->fetchColumn();
        $partialInv = (float) $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM invoices WHERE payment_status='partial'")->fetchColumn();
        $unpaidPO = (float) $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM purchase_orders WHERE payment_status='unpaid'")->fetchColumn();
        $partialPO = (float) $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM purchase_orders WHERE payment_status='partial'")->fetchColumn();
        echo '<div class="cols-2">';
        echo '<div class="lf"><span class="lf-title">Receivables (Customer Payments)</span><table style="width:100%;font-size:13px"><tbody>';
        echo '<tr><td style="padding:6px 0;color:#666">Unpaid Invoices:</td><td style="text-align:right;font-weight:700;color:#d9534f">' . formatCurrency($unpaidInv) . '</td></tr>';
        echo '<tr><td style="padding:6px 0;color:#666">Partial Invoices:</td><td style="text-align:right;font-weight:700;color:#f0ad4e">' . formatCurrency($partialInv) . '</td></tr>';
        echo '<tr style="border-top:2px solid #a0a0a0"><td style="padding:8px 0;font-weight:700">Total Receivable:</td><td style="text-align:right;font-weight:700;color:#4a90d9">' . formatCurrency($unpaidInv + $partialInv) . '</td></tr>';
        echo '</tbody></table></div>';
        echo '<div class="lf"><span class="lf-title">Payables (Supplier Payments)</span><table style="width:100%;font-size:13px"><tbody>';
        echo '<tr><td style="padding:6px 0;color:#666">Unpaid POs:</td><td style="text-align:right;font-weight:700;color:#d9534f">' . formatCurrency($unpaidPO) . '</td></tr>';
        echo '<tr><td style="padding:6px 0;color:#666">Partial POs:</td><td style="text-align:right;font-weight:700;color:#f0ad4e">' . formatCurrency($partialPO) . '</td></tr>';
        echo '<tr style="border-top:2px solid #a0a0a0"><td style="padding:8px 0;font-weight:700">Total Payable:</td><td style="text-align:right;font-weight:700;color:#4a90d9">' . formatCurrency($unpaidPO + $partialPO) . '</td></tr>';
        echo '</tbody></table></div>';
        echo '</div>';
    } elseif ($reportType === 'stock_movement') {
        $rows = $pdo->query("SELECT sa.*,p.name as product_name,p.sku,u.full_name as req_name FROM stock_adjustments sa JOIN products p ON p.id=sa.product_id JOIN users u ON u.id=sa.requested_by WHERE DATE(sa.created_at) BETWEEN '$dateFrom' AND '$dateTo' ORDER BY sa.created_at DESC")->fetchAll();
        echo '<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Ref#</th><th>Product</th><th>Type</th><th class="text-right">Qty</th><th class="text-right">Before</th><th class="text-right">After</th><th>By</th><th>Status</th><th>Date</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $isAdd = in_array($r['adjustment_type'], ['addition', 'opening_stock']);
            echo '<tr><td><code style="font-size:10px">' . h($r['reference_no']) . '</code></td><td>' . h($r['product_name']) . '<br><small style="color:#888">' . h($r['sku']) . '</small></td><td>' . h(str_replace('_', ' ', strtoupper($r['adjustment_type']))) . '</td><td class="text-right ' . ($isAdd ? 'stock-ok' : 'stock-critical') . '">' . ($isAdd ? '+' : '−') . number_format((int) $r['quantity']) . '</td><td class="text-right">' . number_format((int) $r['before_stock']) . '</td><td class="text-right">' . number_format((int) $r['after_stock']) . '</td><td>' . h($r['req_name']) . '</td><td><span class="badge badge-' . (['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'][$r['status']] ?? 'secondary') . '">' . h(strtoupper($r['status'])) . '</span></td><td>' . h(substr($r['created_at'], 0, 10)) . '</td></tr>';
        }
        if (empty($rows))
            echo '<tr><td colspan="9" class="text-center" style="padding:16px;color:#888">No stock movements in this period</td></tr>';
        echo '</tbody></table></div>';
    } elseif ($reportType === 'dead_stock') {
        $days = (int) ($_GET['days'] ?? 90);
        $rows = $pdo->query("SELECT p.*,c.name as category_name,MAX(ii.created_at) as last_sale FROM products p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN invoice_items ii ON ii.product_id=p.id WHERE p.status='active' AND p.current_stock>0 GROUP BY p.id HAVING last_sale IS NULL OR last_sale < DATE_SUB(NOW(),INTERVAL $days DAY) ORDER BY last_sale ASC")->fetchAll();
        echo '<div style="margin-bottom:10px;font-size:12px;color:#666">Showing products with no sales in the last <strong>' . $days . '</strong> days. <a href="?page=reports&report=dead_stock&days=30">30 days</a> | <a href="?page=reports&report=dead_stock&days=60">60 days</a> | <a href="?page=reports&report=dead_stock&days=90">90 days</a> | <a href="?page=reports&report=dead_stock&days=180">180 days</a></div>';
        echo '<div class="tbl-wrap"><table class="tbl"><thead><tr><th>SKU</th><th>Name</th><th>Category</th><th class="text-right">Current Stock</th><th class="text-right">Stock Value</th><th>Last Sale</th></tr></thead><tbody>';
        foreach ($rows as $r)
            echo '<tr><td><code style="font-size:10px">' . h($r['sku']) . '</code></td><td>' . h($r['name']) . '</td><td>' . h($r['category_name'] ?? '-') . '</td><td class="text-right stock-low">' . number_format((int) $r['current_stock']) . '</td><td class="text-right">' . formatCurrency($r['current_stock'] * $r['purchase_price']) . '</td><td>' . ($r['last_sale'] ? h(substr($r['last_sale'], 0, 10)) : '<span class="badge badge-danger">NEVER SOLD</span>') . '</td></tr>';
        if (empty($rows))
            echo '<tr><td colspan="6" class="text-center" style="padding:16px;color:#5cb85c;font-weight:700">&#10003; No dead stock found!</td></tr>';
        echo '</tbody></table></div>';
    }
    ?>
    <script>function applyFilter() {
            window.location = '?page=reports&report=<?= h($reportType) ?>&date_from=' + document.getElementById('df').value + '&date_to=' + document.getElementById('dt').value;
        }</script>
    <?php
    $content = ob_get_clean();
    renderLayout('Reports', $content, 'reports');
}

function renderBarcodes(): void
{
    $pdo = getPDO();
    $productId = (int) ($_GET['product_id'] ?? 0);
    $products = $pdo->query("SELECT id,name,sku,barcode FROM products WHERE status='active' ORDER BY name")->fetchAll();
    $selectedProduct = null;
    if ($productId) {
        foreach ($products as $p) {
            if ($p['id'] === $productId) {
                $selectedProduct = $p;
                break;
            }
        }
    }
    ob_start();
    ?>
    <div class="page-header"><h1>&#9644; Barcode Labels</h1>
        <div class="page-header-actions">
            <button onclick="window.print()" class="btn btn-primary btn-sm no-print">&#128438; Print Labels</button>
        </div>
    </div>
    <div class="no-print lf"><span class="lf-title">Select Product</span>
        <form method="GET" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
            <input type="hidden" name="page" value="barcodes">
            <div class="form-group" style="min-width:300px"><label>Product</label>
                <select name="product_id" required>
                    <option value="">-- Select Product --</option><?php foreach ($products as $p): ?>
                        <option
                        value="<?= $p['id'] ?>" <?= $productId == $p['id'] ? 'selected' : '' ?>><?= h($p['name'] . ' (' . $p['sku'] . ')') ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Labels per Row</label><select name="cols" id="barcode-cols">
                    <option value="2" <?= ($_GET['cols'] ?? '2') === '2' ? 'selected' : '' ?>>2</option>
                    <option value="3" <?= ($_GET['cols'] ?? '') === '3' ? 'selected' : '' ?>>3</option>
                    <option value="4" <?= ($_GET['cols'] ?? '') === '4' ? 'selected' : '' ?>>4</option>
                </select></div>
            <button type="submit" class="btn btn-primary btn-sm">Generate</button>
        </form>
    </div>
<?php
    if ($selectedProduct):
        $cols = (int) ($_GET['cols'] ?? 2);
        $barcodeVal = $selectedProduct['barcode'] ?: $selectedProduct['sku'];
        $numLabels = 12;
        ?>
    <div style="display:grid;grid-template-columns:repeat(<?= $cols ?>,1fr);gap:8px;max-width:800px">
        <?php for ($i = 0; $i < $numLabels; $i++): ?>
            <div class="barcode-svg-wrap">
                <div style="font-size:11px;font-weight:700;margin-bottom:4px;color:#000"><?= h(substr($selectedProduct['name'], 0, 25)) ?></div>
                <svg class="js-barcode" jsbarcode-value="<?= h($barcodeVal) ?>" jsbarcode-height="40"
                     jsbarcode-width="1.5" jsbarcode-fontsize="12" jsbarcode-textmargin="2"
                     style="max-width:100%;height:auto;"></svg>
                <div style="font-size:9px;color:#555;margin-top:2px"><?= h($selectedProduct['sku']) ?></div>
            </div>
        <?php endfor; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            if (typeof JsBarcode !== 'undefined') {
                JsBarcode(".js-barcode").init();
            }
        });
    </script>
<?php elseif ($productId): ?>
    <div class="alert alert-warning">Product not found.</div>
<?php else: ?>
    <div style="padding:20px;text-align:center;color:#888;font-size:13px">Select a product above to generate barcode
        labels.
    </div>
<?php endif; ?>
    <?php
    $content = ob_get_clean();
    renderLayout('Barcodes', $content, 'barcodes');
}

function renderSettings(): void
{
    requireRole(['admin']);
    $pdo = getPDO();
    $section = $_GET['section'] ?? 'company';
    $msg = '';
    $msgType = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
        foreach ($_POST as $k => $v) {
            if ($k === 'csrf_token')
                continue;
            if (substr($k, 0, 8) === 'setting_') {
                $key = substr($k, 8);
                setSetting($key, (string) $v);
            }
        }
        if (!empty($_POST['db_backup'])) {
            $host = DB_HOST;
            $user = DB_USER;
            $pass = DB_PASS;
            $db = DB_NAME;
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="backup_' . DB_NAME . '_' . date('Ymd_His') . '.sql"');
            $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
            echo "-- InventoryPro SQL Backup\n-- Date: " . date('Y-m-d H:i:s') . "\n-- Database: " . DB_NAME . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";
            foreach ($tables as $table) {
                $createQ = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
                echo $createQ['Create Table'] . ";\n\n";
                $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll();
                foreach ($rows as $row) {
                    $vals = array_map(function ($v) use ($pdo) {
                        return $v === null ? 'NULL' : $pdo->quote((string) $v);
                    }, $row);
                    echo "INSERT INTO `$table` VALUES (" . implode(',', $vals) . ");\n";
                }
                echo "\n";
            }
            echo "SET FOREIGN_KEY_CHECKS=1;\n";
            exit;
        }
        $msg = 'Settings saved.';
        $msgType = 'success';
        logActivity($_SESSION['user_id'], 'UPDATE', 'settings', 'Updated system settings');
    }
    $settingKeys = ['company' => ['company_name' => 'Company Name', 'company_address' => 'Address', 'company_phone' => 'Phone', 'company_email' => 'Email', 'company_website' => 'Website', 'company_tax_number' => 'Tax Number', 'company_logo_url' => 'Logo URL'], 'invoice' => ['invoice_prefix' => 'Invoice Prefix', 'invoice_start_number' => 'Starting Number', 'po_prefix' => 'PO Prefix', 'po_start_number' => 'PO Starting Number', 'default_tax_percent' => 'Default Tax %', 'default_payment_terms' => 'Default Payment Terms'], 'system' => ['currency_symbol' => 'Currency Symbol', 'currency_code' => 'Currency Code', 'date_format' => 'Date Format', 'timezone' => 'Timezone', 'low_stock_threshold' => 'Low Stock Threshold', 'items_per_page' => 'Items Per Page']];
    ob_start();
    ?>
    <div class="page-header"><h1>&#9881; Settings</h1></div>
    <?php if ($msg): ?>
    <div class="alert alert-<?= h($msgType) ?>"><?= h($msg) ?></div><?php endif; ?>
    <div style="display:flex;gap:0;margin-bottom:12px;border-bottom:2px solid #a0a0a0">
        <?php $sections = ['company' => '&#127970; Company', 'invoice' => '&#128203; Invoice', 'system' => '&#9881; System', 'backup' => '&#128190; Backup'];
        foreach ($sections as $k => $v): ?>
            <a href="?page=settings&section=<?= $k ?>"
               style="padding:8px 16px;text-decoration:none;font-size:12px;font-weight:600;border:1px solid #a0a0a0;border-bottom:none;background:<?= $section === $k ? '#f0f0f0' : '#d0d0d0' ?>;color:<?= $section === $k ? '#000' : '#555' ?>;margin-right:2px"><?= $v ?></a>
        <?php endforeach; ?>
    </div>
<?php if ($section !== 'backup' && isset($settingKeys[$section])): ?>
    <form method="POST" action="?page=settings&section=<?= h($section) ?>">
        <?= csrfField() ?>
        <div class="lf"><span
                    class="lf-title"><?= ['company' => 'Company Information', 'invoice' => 'Invoice & PO Settings', 'system' => 'System Preferences'][$section] ?></span>
            <div class="form-grid form-grid-2">
                <?php foreach ($settingKeys[$section] as $key => $label): ?>
                    <div class="form-group"><label><?= h($label) ?></label><input type="text"
                                                                                  name="setting_<?= h($key) ?>"
                                                                                  value="<?= h(getSetting($key)) ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <button type="submit" class="btn btn-success">&#10003; Save Settings</button>
    </form>
<?php elseif ($section === 'backup'): ?>
    <div class="lf"><span class="lf-title">&#128190; Database Backup &amp; Restore</span>
        <div class="cols-2">
            <div>
                <div style="font-size:13px;font-weight:700;margin-bottom:8px">&#8595; Download SQL Backup</div>
                <p style="font-size:12px;color:#555;margin-bottom:10px">Downloads a full SQL dump of all tables and
                    data. Store securely.</p>
                <form method="POST" action="?page=settings&section=backup">
                    <?= csrfField() ?>
                    <input type="hidden" name="db_backup" value="1">
                    <button type="submit" class="btn btn-primary">&#128190; Download SQL Backup</button>
                </form>
            </div>
            <div>
                <div style="font-size:13px;font-weight:700;margin-bottom:8px">&#8593; Restore from SQL File</div>
                <p style="font-size:12px;color:#555;margin-bottom:10px">Upload a previously downloaded SQL backup file.
                    <strong style="color:#d9534f">WARNING: This will overwrite existing data.</strong></p>
                <form method="POST" action="?page=settings&section=backup" enctype="multipart/form-data"
                      id="restore-form">
                    <?= csrfField() ?>
                    <input type="file" name="sql_restore" accept=".sql"
                           style="display:block;margin-bottom:8px;padding:4px;border:2px inset #a0a0a0;background:#fff;width:100%">
                    <button type="button" onclick="confirmRestoreDB()" class="btn btn-danger">&#9888; Restore Database
                    </button>
                </form>
                <?php
                if (!empty($_FILES['sql_restore']['tmp_name']) && verifyCsrf()) {
                    $sqlContent = file_get_contents($_FILES['sql_restore']['tmp_name']);
                    if ($sqlContent) {
                        try {
                            $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
                            $stmts = array_filter(array_map('trim', explode(";\n", $sqlContent)));
                            $ok = 0;
                            $fail = 0;
                            foreach ($stmts as $stmt) {
                                if ($stmt && stripos($stmt, '--') !== 0) {
                                    try {
                                        $pdo->exec($stmt);
                                        $ok++;
                                    } catch (Exception $e) {
                                        $fail++;
                                    }
                                }
                            }
                            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
                            echo '<div class="alert alert-success">Restore completed: ' . $ok . ' statements executed, ' . $fail . ' failed.</div>';
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">Restore failed: ' . h($e->getMessage()) . '</div>';
                        }
                    }
                }
                ?>
            </div>
        </div>
    </div>
    <script>function confirmRestoreDB() {
            confirmAction('DANGER! Restoring will overwrite ALL current data. This cannot be undone. Continue?', function () {
                document.getElementById(\'restore-form\').submit(); },'
                Confirm
                Database
                Restore
                '); }</script>
<?php endif; ?>
    <?php
    $content = ob_get_clean();
    renderLayout('Settings', $content, 'settings');
}

function renderAuditLog(): void
{
    requireRole(['admin', 'manager']);
    $pdo = getPDO();
    $page = max(1, (int) ($_GET['pg'] ?? 1));
    $perPage = (int) (getSetting('items_per_page') ?? 20);
    $userId = (int) ($_GET['user_id'] ?? 0);
    $module = $_GET['module'] ?? '';
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    $where = ['1=1'];
    $params = [];
    if ($userId) {
        $where[] = 'al.user_id=?';
        $params[] = $userId;
    }
    if ($module) {
        $where[] = 'al.module=?';
        $params[] = $module;
    }
    if ($dateFrom) {
        $where[] = 'DATE(al.created_at)>=?';
        $params[] = $dateFrom;
    }
    if ($dateTo) {
        $where[] = 'DATE(al.created_at)<=?';
        $params[] = $dateTo;
    }
    $whereStr = implode(' AND ', $where);
    $cntQ = $pdo->prepare("SELECT COUNT(*) FROM activity_log al WHERE $whereStr");
    $cntQ->execute($params);
    $total = (int) $cntQ->fetchColumn();
    $pag = paginate($total, $perPage, $page);
    $stmt = $pdo->prepare("SELECT al.*,u.full_name,u.username FROM activity_log al LEFT JOIN users u ON u.id=al.user_id WHERE $whereStr ORDER BY al.created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute(array_merge($params, [$pag['per_page'], $pag['offset']]));
    $logs = $stmt->fetchAll();
    $users = $pdo->query('SELECT id,full_name,username FROM users ORDER BY full_name')->fetchAll();
    $modules = array_column($pdo->query('SELECT DISTINCT module FROM activity_log ORDER BY module')->fetchAll(), 'module');
    ob_start();
    ?>
    <div class="page-header"><h1>&#128196; Activity Log / Audit Trail</h1></div>
    <form method="GET"><input type="hidden" name="page" value="audit_log">
        <div class="filter-bar">
            <div class="form-group"><label>User</label><select name="user_id">
                    <option value="">All Users</option><?php foreach ($users as $u): ?>
                        <option
                        value="<?= $u['id'] ?>" <?= $userId == $u['id'] ? 'selected' : '' ?>><?= h($u['full_name'] . ' (@' . $u['username'] . ')') ?></option><?php endforeach; ?>
                </select></div>
            <div class="form-group"><label>Module</label><select name="module">
                    <option value="">All Modules</option><?php foreach ($modules as $m): ?>
                        <option
                        value="<?= h($m) ?>" <?= $module === $m ? 'selected' : '' ?>><?= h(strtoupper($m)) ?></option><?php endforeach; ?>
                </select></div>
            <div class="form-group"><label>From</label><input type="date" name="date_from" value="<?= h($dateFrom) ?>">
            </div>
            <div class="form-group"><label>To</label><input type="date" name="date_to" value="<?= h($dateTo) ?>"></div>
            <div class="form-group" style="justify-content:flex-end;flex-direction:row;align-items:flex-end;gap:6px">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="?page=audit_log" class="btn btn-secondary btn-sm">Reset</a></div>
        </div>
    </form>
    <div class="tbl-wrap">
        <table class="tbl">
            <thead>
            <tr>
                <th>User</th>
                <th>Action</th>
                <th>Module</th>
                <th>Description</th>
                <th>IP Address</th>
                <th>Date &amp; Time</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="6" class="text-center" style="padding:16px;color:#888">No activity records found</td>
                </tr>
            <?php else:
        foreach ($logs as $log):
            $actionColor = ['CREATE' => '#5cb85c', 'UPDATE' => '#f0ad4e', 'DELETE' => '#d9534f', 'LOGIN' => '#4a90d9', 'LOGOUT' => '#888', 'RECEIVE' => '#5bc0de'][$log['action']] ?? '#555'; ?>
                    <tr>
                        <td><strong><?= h($log['full_name'] ?? 'System') ?></strong><br><small
                                    style="color:#888">@<?= h($log['username'] ?? '-') ?></small></td>
                        <td>
                            <span style="display:inline-block;padding:2px 6px;background:<?= $actionColor ?>;color:#fff;font-size:10px;font-weight:700"><?= h($log['action']) ?></span>
                        </td>
                        <td>
                            <span style="font-size:11px;text-transform:uppercase;color:#555"><?= h($log['module']) ?></span>
                        </td>
                        <td style="font-size:12px;max-width:300px"><?= h($log['description']) ?></td>
                        <td style="font-size:11px;color:#888"><?= h($log['ip_address'] ?? '-') ?></td>
                        <td style="font-size:11px;white-space:nowrap"><?= h($log['created_at']) ?></td>
                    </tr>
                <?php endforeach;
    endif; ?>
            </tbody>
        </table>
    </div>
    <?= paginationHtml($pag, '?page=audit_log&user_id=' . urlencode($userId) . '&module=' . urlencode($module) . '&date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo)) ?>
    <?php
    $content = ob_get_clean();
    renderLayout('Audit Log', $content, 'audit_log');
}

function renderNotifications(): void
{
    $pdo = getPDO();
    $lowStockItems = $pdo->query("SELECT p.name,p.sku,p.current_stock,p.min_stock_level FROM products p WHERE p.current_stock<=p.min_stock_level AND p.status='active' ORDER BY p.current_stock ASC LIMIT 50")->fetchAll();
    $overdueInvoices = $pdo->query("SELECT i.id,i.invoice_number,c.name as customer_name,i.due_date,i.total_amount FROM invoices i JOIN customers c ON c.id=i.customer_id WHERE i.payment_status IN ('unpaid','partial') AND i.due_date < CURDATE() ORDER BY i.due_date ASC LIMIT 50")->fetchAll();
    $overduePOs = $pdo->query("SELECT po.id,po.po_number,s.company_name,po.expected_delivery,po.total_amount FROM purchase_orders po JOIN suppliers s ON s.id=po.supplier_id WHERE po.payment_status IN ('unpaid','partial') AND po.expected_delivery < CURDATE() ORDER BY po.expected_delivery ASC LIMIT 50")->fetchAll();
    ob_start();
    ?>
    <div class="page-header"><h1>&#128276; Notifications &amp; Alerts</h1></div>
    <?php if (count($lowStockItems) > 0): ?>
    <div class="lf"><span class="lf-title"
                          style="color:#d9534f">&#9888; Low Stock Alerts (<?= count($lowStockItems) ?>)</span>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                <tr>
                    <th>SKU</th>
                    <th>Product</th>
                    <th class="text-right">Current</th>
                    <th class="text-right">Minimum</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($lowStockItems as $p):
                    $sc = $p['current_stock'] <= 0 ? 'critical' : 'low'; ?>
                    <tr>
                        <td><code style="font-size:10px"><?= h($p['sku']) ?></code></td>
                        <td><?= h($p['name']) ?></td>
                        <td class="text-right stock-<?= $sc ?>"><?= number_format((int) $p['current_stock']) ?></td>
                        <td class="text-right"><?= number_format((int) $p['min_stock_level']) ?></td>
                        <td><a href="?page=purchases&action=new" class="btn btn-warning btn-xs">Order</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-success">&#10003; No low stock alerts. All products adequately stocked.</div><?php endif; ?>
    <?php if (count($overdueInvoices) > 0): ?>
    <div class="lf"><span class="lf-title"
                          style="color:#d9534f">&#128178; Overdue Receivables (<?= count($overdueInvoices) ?>)</span>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                <tr>
                    <th>Invoice#</th>
                    <th>Customer</th>
                    <th>Due Date</th>
                    <th class="text-right">Amount</th>
                    <th>Days Overdue</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($overdueInvoices as $inv):
                    $days = max(0, (int) floor((time() - strtotime($inv['due_date'])) / 86400)); ?>
                    <tr>
                        <td><a href="?page=sales&action=view&id=<?= $inv['id'] ?>"><?= h($inv['invoice_number']) ?></a>
                        </td>
                        <td><?= h($inv['customer_name']) ?></td>
                        <td style="color:#d9534f;font-weight:700"><?= h($inv['due_date']) ?></td>
                        <td class="text-right"><?= formatCurrency((float) $inv['total_amount']) ?></td>
                        <td class="text-right stock-critical"><?= $days ?> days</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-success">&#10003; No overdue customer invoices.</div><?php endif; ?>
    <?php if (count($overduePOs) > 0): ?>
    <div class="lf"><span class="lf-title"
                          style="color:#f0ad4e">&#128667; Overdue Supplier Payments (<?= count($overduePOs) ?>)</span>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                <tr>
                    <th>PO#</th>
                    <th>Supplier</th>
                    <th>Expected By</th>
                    <th class="text-right">Amount</th>
                    <th>Days Overdue</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($overduePOs as $po):
                    $days = max(0, (int) floor((time() - strtotime($po['expected_delivery'])) / 86400)); ?>
                    <tr>
                        <td><a href="?page=purchases&action=view&id=<?= $po['id'] ?>"><?= h($po['po_number']) ?></a>
                        </td>
                        <td><?= h($po['company_name']) ?></td>
                        <td style="color:#f0ad4e;font-weight:700"><?= h($po['expected_delivery']) ?></td>
                        <td class="text-right"><?= formatCurrency((float) $po['total_amount']) ?></td>
                        <td class="text-right stock-low"><?= $days ?> days</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-success">&#10003; No overdue supplier payment alerts.</div><?php endif; ?>
    <?php
    $content = ob_get_clean();
    renderLayout('Notifications', $content, 'notifications');
}

function handleAjax(): void
{
    $pdo = getPDO();
    header('Content-Type: application/json');
    $endpoint = $_GET['ajax'] ?? '';
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'msg' => 'Not authenticated']);
        exit;
    }
    if ($endpoint === 'get_product') {
        $id = (int) ($data['id'] ?? 0);
        $p = $pdo->prepare('SELECT * FROM products WHERE id=?');
        $p->execute([$id]);
        $p = $p->fetch();
        echo json_encode(['success' => (bool) $p, 'product' => $p]);
    } elseif ($endpoint === 'void_invoice') {
        requireRole(['admin', 'manager']);
        $id = (int) ($data['id'] ?? 0);
        $pdo->prepare("UPDATE invoices SET payment_status='unpaid',updated_at=NOW() WHERE id=?")->execute([$id]);
        logActivity($_SESSION['user_id'], 'UPDATE', 'sales', 'Voided invoice id: ' . $id);
        echo json_encode(['success' => true]);
    } elseif ($endpoint === 'approve_adj') {
        requireRole(['admin', 'manager']);
        $id = (int) ($data['id'] ?? 0);
        $adj = $pdo->prepare("SELECT * FROM stock_adjustments WHERE id=? AND status='pending'");
        $adj->execute([$id]);
        $adj = $adj->fetch();
        if ($adj) {
            $pdo->prepare("UPDATE stock_adjustments SET status='approved',approved_by=?,approved_at=NOW() WHERE id=?")->execute([(int) $_SESSION['user_id'], $id]);
            $pdo->prepare('UPDATE products SET current_stock=?,updated_at=NOW() WHERE id=?')->execute([(int) $adj['after_stock'], (int) $adj['product_id']]);
            logActivity($_SESSION['user_id'], 'UPDATE', 'adjustments', 'Approved adjustment id: ' . $id);
            echo json_encode(['success' => true]);
        } else
            echo json_encode(['success' => false, 'msg' => 'Not found or already processed']);
    } elseif ($endpoint === 'reject_adj') {
        requireRole(['admin', 'manager']);
        $id = (int) ($data['id'] ?? 0);
        $pdo->prepare("UPDATE stock_adjustments SET status='rejected',approved_by=?,approved_at=NOW() WHERE id=?")->execute([(int) $_SESSION['user_id'], $id]);
        logActivity($_SESSION['user_id'], 'UPDATE', 'adjustments', 'Rejected adjustment id: ' . $id);
        echo json_encode(['success' => true]);
    } elseif ($endpoint === 'complete_transfer') {
        requireRole(['admin', 'manager']);
        $id = (int) ($data['id'] ?? 0);
        $pdo->beginTransaction();
        try {
            $items = $pdo->prepare('SELECT * FROM stock_transfer_items WHERE transfer_id=?');
            $items->execute([$id]);
            $items = $items->fetchAll();
            foreach ($items as $item) {
                $pdo->prepare('UPDATE products SET current_stock=current_stock-?,updated_at=NOW() WHERE id=?')->execute([(int) $item['quantity'], (int) $item['product_id']]);
            }
            $pdo->prepare("UPDATE stock_transfers SET status='completed',updated_at=NOW() WHERE id=?")->execute([$id]);
            $pdo->commit();
            logActivity($_SESSION['user_id'], 'UPDATE', 'transfers', 'Completed transfer id: ' . $id);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
        }
    } elseif ($endpoint === 'cancel_transfer') {
        requireRole(['admin', 'manager']);
        $id = (int) ($data['id'] ?? 0);
        $pdo->prepare("UPDATE stock_transfers SET status='cancelled',updated_at=NOW() WHERE id=?")->execute([$id]);
        logActivity($_SESSION['user_id'], 'UPDATE', 'transfers', 'Cancelled transfer id: ' . $id);
        echo json_encode(['success' => true]);
    } elseif ($endpoint === 'delete_user') {
        requireRole(['admin']);
        $id = (int) ($data['id'] ?? 0);
        if ($id === (int) $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'msg' => 'Cannot delete yourself']);
            exit;
        }
        $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
        echo json_encode(['success' => true]);
    } elseif ($endpoint === 'toggle_product_status') {
        $id = (int) ($data['id'] ?? 0);
        $pdo->prepare("UPDATE products SET status=IF(status='active','inactive','active'),updated_at=NOW() WHERE id=?")->execute([$id]);
        echo json_encode(['success' => true]);
    } elseif ($endpoint === 'bulk_product_action') {
        requireRole(['admin', 'manager']);
        $action = $data['bulk_action'] ?? '';
        $ids = array_map('intval', $data['ids'] ?? []);
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            if ($action === 'activate')
                $pdo->prepare("UPDATE products SET status='active' WHERE id IN ($placeholders)")->execute($ids);
            elseif ($action === 'deactivate')
                $pdo->prepare("UPDATE products SET status='inactive' WHERE id IN ($placeholders)")->execute($ids);
            elseif ($action === 'delete' && canAccess(['admin']))
                $pdo->prepare("DELETE FROM products WHERE id IN ($placeholders)")->execute($ids);
        }
        echo json_encode(['success' => true, 'count' => count($ids)]);
    } elseif ($endpoint === 'check_stock') {
        $id = (int) ($data['id'] ?? 0);
        $row = $pdo->prepare('SELECT current_stock,name FROM products WHERE id=?');
        $row->execute([$id]);
        $row = $row->fetch();
        echo json_encode(['success' => (bool) $row, 'stock' => $row ? $row['current_stock'] : 0, 'name' => $row ? $row['name'] : '']);
    } elseif ($endpoint === 'pos_checkout') {
        requireRole(['admin', 'manager', 'staff']);
        $items = $data['items'] ?? [];
        $customerId = (int) ($data['customer_id'] ?? 1);
        $payMethod = $data['payment_method'] ?? 'cash';
        $amountPaidInput = isset($data['amount_paid']) ? (float) $data['amount_paid'] : null;
        if (empty($items)) {
            echo json_encode(['success' => false, 'msg' => 'Cart is empty']);
            exit;
        }
        $pdo->beginTransaction();
        try {
            $subtotal = 0;
            foreach ($items as $it) {
                $subtotal += ($it['price'] * $it['qty']);
            }
            $taxPct = (float) getSetting('default_tax_percent');
            $taxAmt = $subtotal * ($taxPct / 100);
            $total = $subtotal + $taxAmt;
            $custCheck = $pdo->prepare('SELECT customer_type FROM customers WHERE id=?');
            $custCheck->execute([$customerId]);
            $isWalkIn = ($custCheck->fetchColumn() === 'walk-in');
            $amountPaid = $amountPaidInput;
            if ($amountPaid === null || $amountPaid >= $total) {
                $amountPaid = $total;
                $paymentStatus = 'paid';
            } elseif ($amountPaid > 0) {
                $paymentStatus = 'partial';
            } else {
                $amountPaid = 0;
                $paymentStatus = 'unpaid';
            }
            if ($isWalkIn && $paymentStatus !== 'paid') {
                echo json_encode(['success' => false, 'msg' => 'Walk-in customers must pay in full.']);
                exit;
            }
            $invNum = generateInvoiceNumber();
            $pdo->prepare('INSERT INTO invoices (invoice_number,customer_id,invoice_date,subtotal,tax_percent,total_amount,payment_status,payment_method,created_by) VALUES (?,?,CURDATE(),?,?,?,?,?,?)')->execute([$invNum, $customerId, $subtotal, $taxPct, $total, $paymentStatus, $payMethod, (int) $_SESSION['user_id']]);
            $invId = (int) $pdo->lastInsertId();
            $openShift = $pdo->prepare("SELECT id FROM shifts WHERE user_id=? AND status='open'");
            $openShift->execute([$_SESSION['user_id']]);
            $shiftId = $openShift->fetchColumn();
            if ($shiftId && $amountPaid > 0) {
                if ($payMethod === 'cash') {
                    $pdo->prepare('UPDATE shifts SET total_sales=total_sales+?, cash_sales=cash_sales+? WHERE id=?')->execute([$amountPaid, $amountPaid, $shiftId]);
                } else {
                    $pdo->prepare('UPDATE shifts SET total_sales=total_sales+?, card_sales=card_sales+? WHERE id=?')->execute([$amountPaid, $amountPaid, $shiftId]);
                }
            }
            foreach ($items as $it) {
                $qty = (int) $it['qty'];
                $price = (float) $it['price'];
                $pdo->prepare('INSERT INTO invoice_items (invoice_id,product_id,quantity,unit_price,total_price) VALUES (?,?,?,?,?)')->execute([$invId, (int) $it['id'], $qty, $price, $qty * $price]);
                $pdo->prepare('UPDATE products SET current_stock=current_stock-?,updated_at=NOW() WHERE id=?')->execute([$qty, (int) $it['id']]);
            }
            updateCustomerLedger($customerId, 'invoice', $invId, $total, $amountPaid, "POS Sale #$invNum");
            $pdo->commit();
            logActivity($_SESSION['user_id'], 'CREATE', 'pos', 'Created POS invoice ' . $invNum);
            echo json_encode(['success' => true, 'id' => $invId, 'invoice' => $invNum]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
        }
    } elseif ($endpoint === 'get_invoice_details') {
        $id = (int) ($data['id'] ?? 0);
        $items = $pdo->prepare('SELECT ii.*, p.name FROM invoice_items ii JOIN products p ON p.id=ii.product_id WHERE ii.invoice_id=?');
        $items->execute([$id]);
        echo json_encode(['success' => true, 'items' => $items->fetchAll()]);
    } elseif ($endpoint === 'get_po_details') {
        $id = (int) ($data['id'] ?? 0);
        $items = $pdo->prepare('SELECT poi.*, p.name FROM purchase_order_items poi JOIN products p ON p.id=poi.product_id WHERE poi.po_id=?');
        $items->execute([$id]);
        echo json_encode(['success' => true, 'items' => $items->fetchAll()]);
    } elseif ($endpoint === 'save_requisition_items') {
        $id = (int) ($data['id'] ?? 0);
        $items = $data['items'] ?? [];
        $pdo->prepare('DELETE FROM requisition_items WHERE req_id=?')->execute([$id]);
        $stmt = $pdo->prepare('INSERT INTO requisition_items (req_id, product_id, quantity) VALUES (?,?,?)');
        foreach ($items as $it) {
            $stmt->execute([$id, $it['id'], $it['qty']]);
        }
        echo json_encode(['success' => true]);
    } elseif ($endpoint === 'finalize_audit') {
        requireRole(['admin', 'manager']);
        $id = (int) ($data['id'] ?? 0);
        $counts = $data['counts'] ?? [];
        $pdo->beginTransaction();
        try {
            foreach ($counts as $pid => $qty) {
                $prod = getProduct((int) $pid);
                $diff = $qty - $prod['current_stock'];
                if ($diff != 0) {
                    $ref = 'AUDIT-' . $id . '-' . $pid;
                    $type = $diff > 0 ? 'addition' : 'subtraction';
                    $pdo
                        ->prepare('INSERT INTO stock_adjustments (reference_no, product_id, adjustment_type, quantity, before_stock, after_stock, reason, status, requested_by, approved_by, approved_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())')
                        ->execute([$ref, $pid, $type, abs($diff), $prod['current_stock'], $qty, 'Stock Audit Variance', 'approved', $_SESSION['user_id'], $_SESSION['user_id']]);
                    $pdo->prepare('UPDATE products SET current_stock=?, updated_at=NOW() WHERE id=?')->execute([$qty, $pid]);
                }
                $pdo
                    ->prepare('UPDATE stock_audit_items SET counted_qty=?, difference=? WHERE audit_id=? AND product_id=?')
                    ->execute([$qty, $diff, $id, $pid]);
            }
            $pdo->prepare("UPDATE stock_audits SET status='closed' WHERE id=?")->execute([$id]);
            $pdo->commit();
            logActivity($_SESSION['user_id'], 'UPDATE', 'stock_audit', 'Finalized audit id: ' . $id);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
        }
    } elseif ($endpoint === 'get_finance_categories') {
        $type = $data['type'] ?? 'expense';
        $stmt = $pdo->prepare('SELECT * FROM finance_categories WHERE type=?');
        $stmt->execute([$type]);
        echo json_encode(['success' => true, 'categories' => $stmt->fetchAll()]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Unknown endpoint']);
    }
    exit;
}

initializeDatabase();
upgradeDatabase();
$currentPage = $_GET['page'] ?? 'dashboard';
if (!empty($_GET['ajax'])) {
    handleAjax();
} elseif ($currentPage === 'login') {
    if (isset($_SESSION['user_id'])) {
        header('Location: ?page=dashboard');
        exit;
    }
    $loginError = handleLogin();
    renderLogin($loginError);
} elseif ($currentPage === 'logout') {
    $pdo = getPDO();
    if (isset($_SESSION['user_id']))
        logActivity($_SESSION['user_id'], 'LOGOUT', 'auth', 'User logged out');
    session_destroy();
    header('Location: ?page=login');
    exit;
} elseif (!isset($_SESSION['user_id'])) {
    header('Location: ?page=login');
    exit;
} else {
    requireLogin();

    function hasPermission(string $module, string $action): bool
    {
        if (!isset($_SESSION['user_id']))
            return false;
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT r.name FROM users u JOIN roles r ON u.role_id=r.id WHERE u.id=?');
        $stmt->execute([$_SESSION['user_id']]);
        if ($stmt->fetchColumn() === 'admin')
            return true;
        $sql = 'SELECT 1 FROM role_permissions rp 
            JOIN permissions p ON rp.permission_id=p.id
            WHERE rp.role_id=(SELECT role_id FROM users WHERE id=?) AND p.module=? AND p.action=?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['user_id'], $module, $action]);
        return (bool) $stmt->fetchColumn();
    }

    function requirePermission(string $module, string $action): void
    {
        if (!hasPermission($module, $action)) {
            renderError403();
            exit;
        }
    }

    function renderRoles(): void
    {
        requirePermission('roles', 'view');
        $pdo = getPDO();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $perms = $_POST['perms'] ?? [];
            if ($name) {
                if ($id) {
                    $pdo->prepare('UPDATE roles SET name=?, description=? WHERE id=?')->execute([$name, $desc, $id]);
                    $pdo->prepare('DELETE FROM role_permissions WHERE role_id=?')->execute([$id]);
                } else {
                    $pdo->prepare('INSERT INTO roles (name, description) VALUES (?, ?)')->execute([$name, $desc]);
                    $id = (int) $pdo->lastInsertId();
                }
                foreach ($perms as $p) {
                    $pdo->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)')->execute([$id, (int) $p]);
                }
                $_SESSION['flash_msg'] = 'Role saved successfully.';
                $_SESSION['flash_type'] = 'success';
                header('Location: ?page=roles');
                exit;
            }
        }
        $roles = $pdo->query('SELECT * FROM roles ORDER BY id')->fetchAll();
        $perms = $pdo->query('SELECT * FROM permissions ORDER BY module,action')->fetchAll();
        $modules = [];
        foreach ($perms as $p)
            $modules[$p['module']][] = $p;
        ob_start();
        ?>
    <div class="page-header"><h1>&#128272; Roles & Permissions</h1>
        <div class="page-header-actions">
            <button class="btn btn-primary btn-sm" onclick="newRole()">+ New Role</button>
        </div>
    </div>
    <?php if (isset($_SESSION['flash_msg'])): ?>
    <div
    class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?>"><?= h($_SESSION['flash_msg']) ?></div><?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
        endif; ?>
    <div class="tbl-wrap">
        <table class="tbl">
            <thead>
            <tr>
                <th>Role</th>
                <th>Description</th>
                <th>Permissions</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($roles as $r) {
                $cnt = $pdo->prepare('SELECT COUNT(*) FROM role_permissions WHERE role_id=?');
                $cnt->execute([$r['id']]);
                $rolePerms = $pdo->prepare('SELECT permission_id FROM role_permissions WHERE role_id=?');
                $rolePerms->execute([$r['id']]);
                $rpJson = htmlspecialchars(json_encode($rolePerms->fetchAll(PDO::FETCH_COLUMN)), ENT_QUOTES, 'UTF-8');
                echo '<tr><td>' . h($r['name']) . '</td><td>' . h($r['description']) . '</td><td>' . $cnt->fetchColumn() . ' permissions</td>';
                echo '<td><button class="btn btn-warning btn-xs" onclick="editRole(' . $r['id'] . ", '" . h(addslashes($r['name'])) . "', '" . h(addslashes($r['description'])) . "', " . $rpJson . ')">&#9998; Edit</button></td></tr>';
            } ?>
            </tbody>
        </table>
    </div>
    <div id="roleModal" class="modal-overlay">
        <div class="modal wide">
            <div class="modal-title-bar"><span id="roleTitle">New Role</span>
                <button class="modal-close-btn" onclick="closeModal('roleModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="roleForm" method="POST" action="?page=roles"><?= csrfField() ?><input type="hidden" name="id"
                                                                                                id="roleId">
                    <div class="form-group"><label>Name</label><input type="text" name="name" required></div>
                    <div class="form-group"><label>Description</label><input type="text" name="description"></div>
                    <hr>
                    <h5>Permissions</h5>
                    <div class="cols-3">
                        <?php foreach ($modules as $mod => $list) {
                            echo '<div style="margin-bottom:10px"><strong>' . ucfirst($mod) . '</strong><br>';
                            foreach ($list as $p) {
                                echo '<label style="display:block;font-weight:normal;font-size:12px"><input type="checkbox" name="perms[]" value="' . $p['id'] . '"> ' . h($p['display_name']) . '</label>';
                            }
                            echo '</div>';
                        } ?>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('roleModal')">Cancel</button>
                <button type="submit" class="btn btn-success btn-sm">Save</button>
                </form></div>
        </div>
    </div>
    <script>
        function editRole(id, name, desc, perms) {
            document.getElementById('roleTitle').textContent = 'Edit Role';
            document.getElementById('roleId').value = id;
            document.querySelector('input[name="name"]').value = name;
            document.querySelector('input[name="description"]').value = desc;
            document.querySelectorAll('input[name="perms[]"]').forEach(function (cb) {
                cb.checked = perms.includes(parseInt(cb.value));
            });
            openModal('roleModal');
        }
        function newRole() {
            document.getElementById('roleTitle').textContent = 'New Role';
            document.getElementById('roleId').value = '';
            document.getElementById('roleForm').reset();
            openModal('roleModal');
        }
    </script>
<?php
        $content = ob_get_clean();
        renderLayout('Roles & Permissions', $content, 'roles');
    }

    function renderPOS(): void
    {
        requirePermission('pos', 'view');
        $pdo = getPDO();
        $products = $pdo->query("SELECT id,sku,name,selling_price,current_stock,barcode FROM products WHERE status='active' LIMIT 200")->fetchAll();
        $customers = $pdo->query("SELECT id,name,customer_type FROM customers WHERE status='active' ORDER BY name")->fetchAll();
        ob_start();
        echo '<div class="page-header"><h1>&#128722; POS Checkout</h1></div>';
        echo '<div style="display:grid;grid-template-columns:1fr 350px;gap:12px;height:calc(100vh - 160px)">';
        echo '<div class="lf"><span class="lf-title">Products</span><div style="margin-bottom:10px"><input id="posBarcode" class="form-control" style="width:100%;padding:10px;font-size:16px;border:2px inset #a0a0a0" placeholder="Scan barcode or search product (F2)" autofocus></div>';
        echo '<div id="posProducts" style="display:flex;flex-wrap:wrap;gap:8px;overflow-y:auto;max-height:calc(100vh - 220px)">';
        foreach ($products as $p) {
            echo '<div style="background:#fff;border:2px solid #a0a0a0;padding:8px;width:130px;cursor:pointer;display:flex;flex-direction:column;justify-content:space-between" onclick="posAdd(' . $p['id'] . ",'" . addslashes($p['name']) . "'," . $p['selling_price'] . ',' . $p['current_stock'] . ')">
                <div style="font-size:10px;color:#888">' . h($p['sku']) . '</div><div style="font-weight:600;margin-bottom:4px;font-size:11px">' . h(substr($p['name'], 0, 30)) . '</div><div style="color:#4a90d9;font-weight:700">Rs ' . number_format($p['selling_price'], 2) . '</div></div>';
        }
        echo '</div></div>';
        echo '<div class="lf" style="display:flex;flex-direction:column"><span class="lf-title">Cart</span><div id="posCart" style="flex:1;overflow-y:auto;margin-bottom:10px;border-bottom:1px solid #a0a0a0;padding-bottom:10px"></div>';
        echo '<div style="font-size:14px;font-weight:600;display:flex;justify-content:space-between;margin-bottom:4px"><span>Subtotal</span><span id="posSubtotal">0.00</span></div>';
        echo '<div style="font-size:12px;color:#666;display:flex;justify-content:space-between;margin-bottom:8px"><span>Tax</span><span id="posTax">0.00</span></div>';
        echo '<div style="font-size:20px;font-weight:700;display:flex;justify-content:space-between;margin-bottom:12px;color:#1a1a1a"><span>Total</span><span id="posTotal">0.00</span></div>';
        echo '<div><select id="posCustomer" style="width:100%;padding:8px;margin-bottom:8px;border:2px inset #a0a0a0">';
        foreach ($customers as $c) {
            $sel = ($c['customer_type'] === 'walk-in') ? ' selected' : '';
            echo '<option value="' . $c['id'] . '"' . $sel . '>' . h($c['name']) . '</option>';
        }
        echo '</select>';
        echo '<div style="margin-bottom:8px;"><label style="font-size:11px;font-weight:600;display:block;margin-bottom:2px">Amount Paid (Leave empty for full payment)</label><input type="number" id="posAmountPaid" class="form-control" style="width:100%;padding:8px;border:2px inset #a0a0a0" step="0.01" min="0" placeholder="0.00"></div>';
        echo '<div style="display:flex;gap:8px"><button class="btn btn-success" style="flex:1;padding:12px;font-size:16px;justify-content:center" onclick="posCheckout(\'cash\')">Cash</button>';
        echo '<button class="btn btn-primary" style="flex:1;padding:12px;font-size:16px;justify-content:center" onclick="posCheckout(\'card\')">Card</button></div></div></div></div>';
        echo '<script>let posCart=[];function posAdd(id,name,price,stock){let f=posCart.find(i=>i.id===id);if(f){if(f.qty<stock)f.qty++}else posCart.push({id,name,price,qty:1});posRender()}function posRender(){let h="";let sub=0;posCart.forEach((i,idx)=>{let t=i.price*i.qty;sub+=t;h+=`<div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px dashed #ccc"><div><div style="font-weight:600;font-size:12px">${i.name}</div><div style="font-size:11px;color:#666">${CURRENCY} ${i.price} x ${i.qty}</div></div><div style="font-weight:700;font-size:12px">${t.toFixed(2)} <button onclick="posCart.splice(${idx},1);posRender()" class="btn btn-danger btn-xs" style="margin-left:8px;padding:2px 5px">×</button></div></div>`});document.getElementById("posCart").innerHTML=h||"<div style=\'color:#888;text-align:center;padding:20px\'>Cart is empty</div>";document.getElementById("posSubtotal").textContent=sub.toFixed(2);let tax=sub*TAX_PCT;document.getElementById("posTax").textContent=tax.toFixed(2);document.getElementById("posTotal").textContent=(sub+tax).toFixed(2)}async function posCheckout(method){if(!posCart.length){showToast("Cart empty","error");return;}let amtPaid=document.getElementById("posAmountPaid").value;const res=await fetch("?ajax=pos_checkout",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({items:posCart,customer_id:document.getElementById("posCustomer").value,payment_method:method,amount_paid:amtPaid?parseFloat(amtPaid):null,csrf_token:CSRF_TOKEN})});const d=await res.json();if(d.success){showToast("Sale #"+d.invoice,"success");window.open("?page=sales&action=view&id="+d.id,"_blank");posCart=[];posRender();document.getElementById("posAmountPaid").value="";}else showToast(d.msg||"Checkout error","error")}</script>';
        $content = ob_get_clean();
        renderLayout('POS Checkout', $content, 'pos');
    }

    function renderIncomeExpenses(): void
    {
        requirePermission('expenses', 'view');
        $pdo = getPDO();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
            if (isset($_POST['save_expense'])) {
                $stmt = $pdo->prepare('INSERT INTO expenses (expense_date, category, amount, payment_method, reference, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$_POST['expense_date'], $_POST['category'], $_POST['amount'], $_POST['payment_method'], $_POST['reference'], $_POST['notes'], $_SESSION['user_id']]);
                $_SESSION['flash_msg'] = 'Expense recorded successfully.';
                $_SESSION['flash_type'] = 'success';
            } elseif (isset($_POST['save_income'])) {
                $stmt = $pdo->prepare('INSERT INTO income (income_date, source, amount, payment_method, reference, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$_POST['income_date'], $_POST['source'], $_POST['amount'], $_POST['payment_method'], $_POST['reference'], $_POST['notes'], $_SESSION['user_id']]);
                $_SESSION['flash_msg'] = 'Income recorded successfully.';
                $_SESSION['flash_type'] = 'success';
            } elseif (isset($_POST['save_finance_category'])) {
                $stmt = $pdo->prepare('INSERT INTO finance_categories (name, type) VALUES (?, ?)');
                $stmt->execute([$_POST['name'], $_POST['type']]);
                $_SESSION['flash_msg'] = 'Finance category saved.';
                $_SESSION['flash_type'] = 'success';
            }
            header('Location: ?page=income_expenses');
            exit;
        }
        $summary = $pdo->query("
            SELECT 
                DATE_FORMAT(d, '%Y-%m') as month,
                SUM(income_amt) as total_income,
                SUM(expense_amt) as total_expense
            FROM (
                SELECT income_date as d, amount as income_amt, 0 as expense_amt FROM income
                UNION ALL
                SELECT expense_date as d, 0 as income_amt, amount as expense_amt FROM expenses
            ) t
            GROUP BY month
            ORDER BY month DESC
            LIMIT 12
        ")->fetchAll();
        $expenses = $pdo->query('SELECT * FROM expenses ORDER BY expense_date DESC LIMIT 50')->fetchAll();
        $income = $pdo->query('SELECT * FROM income ORDER BY income_date DESC LIMIT 50')->fetchAll();
        $categories = $pdo->query('SELECT * FROM finance_categories ORDER BY type, name')->fetchAll();
        ob_start();
?>
    <div class="page-header">
        <h1>&#128181; Income & Expenses</h1>
        <div class="page-header-actions">
            <button class="btn btn-success btn-sm" onclick="openModal('incomeModal')">+ Add Income</button>
            <button class="btn btn-danger btn-sm" onclick="openModal('expenseModal')">+ Add Expense</button>
            <button class="btn btn-primary btn-sm" onclick="openModal('categoryModal')">Manage Categories</button>
        </div>
    </div>
    <?php if (isset($_SESSION['flash_msg'])): ?>
    <div class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?>"><?= h($_SESSION['flash_msg']) ?></div>
    <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
<?php endif; ?>
    <div class="lf">
        <span class="lf-title">Monthly Summary</span>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                <tr>
                    <th>Month</th>
                    <th class="text-right">Total Income</th>
                    <th class="text-right">Total Expenses</th>
                    <th class="text-right">Net Profit/Loss</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($summary as $s):
                    $net = $s['total_income'] - $s['total_expense'];
                    ?>
                    <tr>
                        <td><?= h($s['month']) ?></td>
                        <td class="text-right" style="color:green"><?= formatCurrency((float) $s['total_income']) ?></td>
                        <td class="text-right" style="color:red"><?= formatCurrency((float) $s['total_expense']) ?></td>
                        <td class="text-right font-weight-bold" style="color:<?= $net >= 0 ? 'green' : 'red' ?>">
                            <?= formatCurrency((float) $net) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($summary)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">No data available</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="cols-2">
        <div class="lf">
            <span class="lf-title">Recent Expenses</span>
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th class="text-right">Amount</th>
                        <th>Method</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($expenses as $e): ?>
                        <tr>
                            <td><?= h($e['expense_date']) ?></td>
                            <td><?= h($e['category']) ?></td>
                            <td class="text-right"><?= formatCurrency((float) $e['amount']) ?></td>
                            <td><?= h($e['payment_method']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($expenses)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">No expenses found</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="lf">
            <span class="lf-title">Recent Income</span>
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Source</th>
                        <th class="text-right">Amount</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($income as $i): ?>
                        <tr>
                            <td><?= h($i['income_date']) ?></td>
                            <td><?= h($i['source']) ?></td>
                            <td class="text-right"><?= formatCurrency((float) $i['amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($income)): ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted">No income found</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div id="expenseModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-title-bar"><span>Add Expense</span>
                <button class="modal-close-btn" onclick="closeModal('expenseModal')">&times;</button>
            </div>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="save_expense" value="1">
                <div class="modal-body">
                    <div class="form-group mb-8"><label>Date</label><input type="date" name="expense_date"
                                                                           value="<?= date('Y-m-d') ?>" required></div>
                    <div class="form-group mb-8"><label>Category</label>
                        <select name="category" required>
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $cat) if ($cat['type'] === 'expense') echo "<option value='" . h($cat['name']) . "'>" . h($cat['name']) . '</option>'; ?>
                        </select>
                    </div>
                    <div class="form-group mb-8"><label>Amount</label><input type="number" name="amount" step="0.01"
                                                                             required></div>
                    <div class="form-group mb-8"><label>Payment Method</label>
                        <select name="payment_method">
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Card">Card</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="form-group mb-8"><label>Reference</label><input type="text" name="reference"></div>
                    <div class="form-group"><label>Notes</label><textarea name="notes"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('expenseModal')">Cancel
                    </button>
                    <button type="submit" class="btn btn-danger btn-sm">Save Expense</button>
                </div>
            </form>
        </div>
    </div>
    <div id="incomeModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-title-bar"><span>Add Income</span>
                <button class="modal-close-btn" onclick="closeModal('incomeModal')">&times;</button>
            </div>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="save_income" value="1">
                <div class="modal-body">
                    <div class="form-group mb-8"><label>Date</label><input type="date" name="income_date"
                                                                           value="<?= date('Y-m-d') ?>" required></div>
                    <div class="form-group mb-8"><label>Source / Category</label>
                        <select name="source" required>
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $cat) if ($cat['type'] === 'income') echo "<option value='" . h($cat['name']) . "'>" . h($cat['name']) . '</option>'; ?>
                        </select>
                    </div>
                    <div class="form-group mb-8"><label>Amount</label><input type="number" name="amount" step="0.01"
                                                                             required></div>
                    <div class="form-group mb-8"><label>Payment Method</label>
                        <select name="payment_method">
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Card">Card</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="form-group mb-8"><label>Reference</label><input type="text" name="reference"></div>
                    <div class="form-group"><label>Notes</label><textarea name="notes"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('incomeModal')">Cancel
                    </button>
                    <button type="submit" class="btn btn-success btn-sm">Save Income</button>
                </div>
            </form>
        </div>
    </div>
    <div id="categoryModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-title-bar"><span>Finance Categories</span>
                <button class="modal-close-btn" onclick="closeModal('categoryModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" class="mb-8" style="display:flex;gap:4px;align-items:flex-end">
                    <?= csrfField() ?>
                    <input type="hidden" name="save_finance_category" value="1">
                    <div class="form-group"><label>Name</label><input type="text" name="name" required></div>
                    <div class="form-group"><label>Type</label><select name="type">
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select></div>
                    <button type="submit" class="btn btn-primary btn-sm">Add</button>
                </form>
                <div class="tbl-wrap" style="max-height:200px;overflow-y:auto">
                    <table class="tbl">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><?= h($cat['name']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $cat['type'] === 'income' ? 'success' : 'danger' ?>"><?= strtoupper($cat['type']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php
        $content = ob_get_clean();
        renderLayout('Income & Expenses', $content, 'income_expenses');
    }

    function renderQuotations(): void
    {
        requirePermission('quotations', 'view');
        $pdo = getPDO();
        $action = $_GET['action'] ?? 'list';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
            if (isset($_POST['save_quotation'])) {
                $customerId = (int) $_POST['customer_id'];
                $validUntil = $_POST['valid_until'];
                $status = $_POST['status'] ?? 'sent';
                $items = json_decode($_POST['items_json'], true) ?? [];
                if (empty($items)) {
                    $_SESSION['flash_msg'] = 'Error: No items selected.';
                    $_SESSION['flash_type'] = 'danger';
                    header('Location: ?page=quotations&action=new');
                    exit;
                }
                $subtotal = 0;
                foreach ($items as $it) {
                    $subtotal += $it['price'] * $it['qty'];
                }
                $total = $subtotal;
                $qn = 'QT-' . date('Ymd') . '-' . rand(1000, 9999);
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare('INSERT INTO quotations (quote_number, customer_id, quote_date, valid_until, subtotal, tax_amount, total_amount, status, created_by) VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$qn, $customerId, $validUntil, $subtotal, 0, $total, $status, $_SESSION['user_id']]);
                    $qid = $pdo->lastInsertId();
                    $stmtItem = $pdo->prepare('INSERT INTO quotation_items (quote_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)');
                    foreach ($items as $it) {
                        $stmtItem->execute([$qid, $it['id'], $it['qty'], $it['price'], $it['qty'] * $it['price']]);
                    }
                    $pdo->commit();
                    $_SESSION['flash_msg'] = "Quotation $qn created successfully.";
                    $_SESSION['flash_type'] = 'success';
                    header('Location: ?page=quotations');
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $_SESSION['flash_msg'] = 'Error: ' . $e->getMessage();
                    $_SESSION['flash_type'] = 'danger';
                }
            } elseif (isset($_POST['convert_quotation'])) {
                $id = (int) $_POST['quote_id'];
                $q = $pdo->prepare('SELECT * FROM quotations WHERE id=? AND status != "converted"');
                $q->execute([$id]);
                $quote = $q->fetch();
                if ($quote) {
                    $pdo->beginTransaction();
                    try {
                        $invNum = generateInvoiceNumber();
                        $stmtInv = $pdo->prepare('INSERT INTO invoices (invoice_number, customer_id, invoice_date, due_date, subtotal, tax_percent, total_amount, payment_status, created_by) VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), ?, 0, ?, "unpaid", ?)');
                        $stmtInv->execute([$invNum, $quote['customer_id'], $quote['subtotal'], $quote['total_amount'], $_SESSION['user_id']]);
                        $invId = $pdo->lastInsertId();
                        $items = $pdo->prepare('SELECT * FROM quotation_items WHERE quote_id=?');
                        $items->execute([$id]);
                        $qItems = $items->fetchAll();
                        $stmtInvItem = $pdo->prepare('INSERT INTO invoice_items (invoice_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)');
                        $stmtUpdateStock = $pdo->prepare('UPDATE products SET current_stock = current_stock - ? WHERE id = ?');
                        foreach ($qItems as $it) {
                            $stmtInvItem->execute([$invId, $it['product_id'], $it['quantity'], $it['unit_price'], $it['total_price']]);
                            $stmtUpdateStock->execute([$it['quantity'], $it['product_id']]);
                        }
                        $pdo->prepare('UPDATE quotations SET status="converted", converted_invoice_id=? WHERE id=?')->execute([$invId, $id]);
                        $pdo->commit();
                        $_SESSION['flash_msg'] = "Quotation converted to Invoice $invNum. Stock levels updated.";
                        $_SESSION['flash_type'] = 'success';
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $_SESSION['flash_msg'] = 'Error: ' . $e->getMessage();
                        $_SESSION['flash_type'] = 'danger';
                    }
                }
                header('Location: ?page=quotations');
                exit;
            }
        }
        if ($action === 'new') {
            $customers = $pdo->query('SELECT id,name FROM customers WHERE status="active" ORDER BY name')->fetchAll();
            $products = $pdo->query("SELECT id,name,sku,selling_price,current_stock FROM products WHERE status='active' ORDER BY name")->fetchAll();
            ob_start();
            ?>
    <div class="page-header">
        <h1>&#128221; New Quotation</h1>
        <div class="page-header-actions"><a href="?page=quotations" class="btn btn-secondary btn-sm">Back to List</a>
        </div>
    </div>
    <div class="lf">
        <form method="POST" id="qForm">
            <?= csrfField() ?>
            <input type="hidden" name="save_quotation" value="1">
            <input type="hidden" name="items_json" id="qJ">
            <div class="form-grid form-grid-3">
                <div class="form-group" style="grid-column:span 2">
                    <label>Customer</label>
                    <select name="customer_id" required>
                        <option value="">-- Select Customer --</option>
                        <?php foreach ($customers as $c) echo "<option value='{$c['id']}'>" . h($c['name']) . '</option>'; ?>
                    </select>
                </div>
                <div class="form-group"><label>Valid Until</label><input type="date" name="valid_until"
                                                                         value="<?= date('Y-m-d', strtotime('+15 days')) ?>"
                                                                         required></div>
                <div class="form-group"><label>Status</label><select name="status">
                        <option value="sent">Sent</option>
                        <option value="draft">Draft</option>
                    </select></div>
            </div>
            <div class="lf mt-8">
                <span class="lf-title">Select Items</span>
                <div class="form-group" style="flex-direction:row;gap:8px;align-items:flex-end">
                    <div style="flex:1">
                        <label>Product</label>
                        <select id="qP">
                            <option value="">-- Choose Product --</option>
                            <?php foreach ($products as $p) echo "<option value='{$p['id']}' data-price='{$p['selling_price']}' data-sku='{$p['sku']}'>" . h($p['name']) . ' (' . h($p['sku']) . ')</option>'; ?>
                        </select>
                    </div>
                    <div style="width:100px"><label>Qty</label><input type="number" id="qQ" value="1" min="1"></div>
                    <button type="button" class="btn btn-primary" onclick="qA()">Add Item</button>
                </div>
                <table class="tbl mt-8" id="qT">
                    <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th class="text-right">Price</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Total</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                    <tr>
                        <th colspan="4" class="text-right">Subtotal:</th>
                        <th class="text-right" id="qSub">0.00</th>
                        <th></th>
                    </tr>
                    </tfoot>
                </table>
            </div>
            <div class="mt-8">
                <button type="submit" class="btn btn-success" onclick="qS(event)">Create Quotation</button>
            </div>
        </form>
    </div>
    <script>
        let qc = [];
        function qA() {
            let s = document.getElementById('qP');
            let q = document.getElementById('qQ');
            if (!s.value) return;
            let opt = s.options[s.selectedIndex];
            let id = +s.value;
            let f = qc.find(i => i.id === id);
            if (f) {
                f.qty += +q.value;
            } else {
                qc.push({
                    id: id,
                    sku: opt.dataset.sku,
                    name: opt.text,
                    price: +opt.dataset.price,
                    qty: +q.value
                });
            }
            qr();
        }
        function qr() {
            let b = document.querySelector('#qT tbody');
            b.innerHTML = '';
            let sub = 0;
            qc.forEach((i, k) => {
                let total = i.price * i.qty;
                sub += total;
                b.innerHTML += `<tr>
                            <td>${h(i.sku)}</td>
                            <td>${h(i.name)}</td>
                            <td class="text-right">${i.price.toFixed(2)}</td>
                            <td class="text-right"><input type="number" value="${i.qty}" min="1" style="width:60px" onchange="qc[${k}].qty=+this.value;qr()"></td>
                            <td class="text-right">${total.toFixed(2)}</td>
                            <td class="text-center"><button type="button" class="btn btn-danger btn-xs" onclick="qc.splice(${k},1);qr()">×</button></td>
                        </tr>`;
            });
            document.getElementById('qSub').textContent = sub.toFixed(2);
        }
        function qS(e) {
            if (qc.length === 0) {
                alert('Please add at least one item.');
                e.preventDefault();
                return;
            }
            document.getElementById('qJ').value = JSON.stringify(qc);
        }
    </script>
<?php
        } else {
            $quotes = $pdo->query('SELECT q.*,c.name as customer FROM quotations q LEFT JOIN customers c ON c.id=q.customer_id ORDER BY q.id DESC')->fetchAll();
            ob_start();
?>
    <div class="page-header">
        <h1>&#128221; Quotations</h1>
        <div class="page-header-actions"><a href="?page=quotations&action=new" class="btn btn-primary btn-sm">+ New
                Quotation</a></div>
    </div>
    <?php if (isset($_SESSION['flash_msg'])): ?>
    <div class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?>"><?= h($_SESSION['flash_msg']) ?></div>
    <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
<?php endif; ?>
    <div class="lf">
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Valid Until</th>
                    <th class="text-right">Total</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($quotes as $q): ?>
                    <tr>
                        <td><?= h($q['quote_number']) ?></td>
                        <td><?= h($q['customer']) ?></td>
                        <td><?= h($q['quote_date']) ?></td>
                        <td><?= h($q['valid_until']) ?></td>
                        <td class="text-right"><?= formatCurrency((float) $q['total_amount']) ?></td>
                        <td>
                            <span class="badge badge-<?= ['draft' => 'secondary', 'sent' => 'info', 'accepted' => 'success', 'rejected' => 'danger', 'converted' => 'primary'][$q['status']] ?? 'secondary' ?>"><?= strtoupper($q['status']) ?></span>
                        </td>
                        <td>
                            <div class="tbl-actions">
                                <?php if ($q['status'] === 'sent' || $q['status'] === 'accepted'): ?>
                                    <form method="POST" style="display:inline"
                                          onsubmit="return confirm('Convert this quotation to an invoice?')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="convert_quotation" value="1">
                                        <input type="hidden" name="quote_id" value="<?= $q['id'] ?>">
                                        <button type="submit" class="btn btn-success btn-xs">Convert to Invoice</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($q['status'] === 'converted' && $q['converted_invoice_id']): ?>
                                    <a href="?page=sales&action=view&id=<?= $q['converted_invoice_id'] ?>"
                                       class="btn btn-info btn-xs">View Invoice</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($quotes)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">No quotations found</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php
        }
        $content = ob_get_clean();
        renderLayout('Quotations', $content, 'quotations');
    }

    function renderSalesReturns(): void
    {
        requirePermission('sales', 'return');
        $pdo = getPDO();
        $action = $_GET['action'] ?? 'list';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_return']) && verifyCsrf()) {
            $pdo->beginTransaction();
            try {
                $invoiceId = (int) $_POST['invoice_id'];
                $customerId = (int) $_POST['customer_id'];
                $items = json_decode($_POST['items_json'], true) ?? [];
                $refundMethod = $_POST['refund_method'] ?? 'cash';
                $reason = $_POST['reason'] ?? '';
                if (empty($items))
                    throw new Exception('No items to return.');
                $total = 0;
                foreach ($items as $it) {
                    $total += $it['price'] * $it['qty'];
                }
                $rn = 'SR-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
                $stmt = $pdo->prepare('INSERT INTO sales_returns (return_number, invoice_id, customer_id, return_date, total_amount, refund_method, reason, created_by) VALUES (?, ?, ?, CURDATE(), ?, ?, ?, ?)');
                $stmt->execute([$rn, $invoiceId, $customerId, $total, $refundMethod, $reason, $_SESSION['user_id']]);
                $returnId = $pdo->lastInsertId();
                $stmtItem = $pdo->prepare('INSERT INTO sales_return_items (return_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)');
                $stmtUpdateStock = $pdo->prepare('UPDATE products SET current_stock = current_stock + ? WHERE id = ?');
                foreach ($items as $it) {
                    $stmtItem->execute([$returnId, (int) $it['id'], (int) $it['qty'], (float) $it['price']]);
                    $stmtUpdateStock->execute([(int) $it['qty'], (int) $it['id']]);
                }
                updateCustomerLedger($customerId, 'return', $returnId, 0, $total, "Return for Invoice #$invoiceId");
                logActivity($_SESSION['user_id'], 'CREATE', 'sales', "Processed sales return $rn for invoice ID $invoiceId");
                $pdo->commit();
                $_SESSION['flash_msg'] = "Sales return $rn processed successfully.";
                header('Location: ?page=sales_returns');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = $e->getMessage();
            }
        }
        if ($action === 'new') {
            $invoices = $pdo->query("SELECT i.id, i.invoice_number, i.customer_id, c.name as customer_name FROM invoices i JOIN customers c ON i.customer_id = c.id WHERE i.payment_status IN ('paid', 'partial') ORDER BY i.id DESC")->fetchAll();
            ob_start();
?>
    <div class="page-header">
        <h1>&#8630; Process Sales Return</h1>
        <div class="page-header-actions"><a href="?page=sales_returns" class="btn btn-secondary btn-sm">Back to List</a>
        </div>
    </div>
    <div class="lf">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
        <form method="POST" id="srForm">
            <?= csrfField() ?>
            <input type="hidden" name="save_return" value="1">
            <input type="hidden" name="items_json" id="items_json">
            <div class="grid grid-2">
                <div class="form-group">
                    <label>Select Invoice</label>
                    <select name="invoice_id" id="invoice_id" required onchange="loadInvoiceItems(this.value)">
                        <option value="">-- Select Invoice --</option>
                        <?php foreach ($invoices as $inv): ?>
                            <option value="<?= $inv['id'] ?>"
                                    data-customer-id="<?= $inv['customer_id'] ?>"><?= h($inv['invoice_number']) ?>
                                - <?= h($inv['customer_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="customer_id" id="customer_id">
                </div>
                <div class="form-group">
                    <label>Refund Method</label>
                    <select name="refund_method" required>
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="credit_note">Credit Note</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Reason for Return</label>
                <textarea name="reason" rows="2" class="form-control" placeholder="Optional reason..."></textarea>
            </div>
            <div id="items-container" style="display:none; margin-top:20px;">
                <h3>Invoice Items</h3>
                <table class="tbl" id="items-table">
                    <thead>
                    <tr>
                        <th>Product</th>
                        <th>Bought Qty</th>
                        <th>Unit Price</th>
                        <th>Return Qty</th>
                        <th>Total</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                    <tr>
                        <th colspan="4" class="text-right">Return Total:</th>
                        <th id="return-total"><?= formatCurrency(0) ?></th>
                    </tr>
                    </tfoot>
                </table>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary" onclick="prepareSubmit(event)">Submit Return</button>
                </div>
            </div>
        </form>
    </div>
    <script>
        let invoiceItems = [];
        function loadInvoiceItems(invId) {
            if (!invId) {
                document.getElementById('items-container').style.display = 'none';
                return;
            }
            const sel = document.getElementById('invoice_id');
            document.getElementById('customer_id').value = sel.options[sel.selectedIndex].dataset.customerId;
            fetch('?ajax=get_invoice_details', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: invId})
            })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        invoiceItems = res.items;
                        renderItems();
                        document.getElementById('items-container').style.display = 'block';
                    } else {
                        alert('Error loading items: ' + (res.msg || 'Unknown error'));
                    }
                });
        }
        function renderItems() {
            const tbody = document.querySelector('#items-table tbody');
            tbody.innerHTML = '';
            invoiceItems.forEach((it, idx) => {
                tbody.innerHTML += `
                            <tr>
                                <td>${h(it.name)}</td>
                                <td>${it.quantity}</td>
                                <td>${it.unit_price}</td>
                                <td><input type="number" class="form-control" style="width:80px" min="0" max="${it.quantity}" value="0" onchange="updateTotal(${idx}, this.value)"></td>
                                <td id="total-${idx}">${(0).toFixed(2)}</td>
                            </tr>
                        `;
            });
            calculateGrandTotal();
        }
        function updateTotal(idx, qty) {
            invoiceItems[idx].return_qty = parseFloat(qty) || 0;
            document.getElementById('total-' + idx).textContent = (invoiceItems[idx].return_qty * invoiceItems[idx].unit_price).toFixed(2);
            calculateGrandTotal();
        }
        function calculateGrandTotal() {
            let total = 0;
            invoiceItems.forEach(it => {
                total += (it.return_qty || 0) * it.unit_price;
            });
            document.getElementById('return-total').textContent = total.toFixed(2);
        }
        function prepareSubmit(e) {
            const returns = invoiceItems.filter(it => (it.return_qty || 0) > 0).map(it => ({
                id: it.product_id,
                qty: it.return_qty,
                price: it.unit_price
            }));
            if (returns.length === 0) {
                alert('Please select at least one item to return.');
                e.preventDefault();
                return;
            }
            document.getElementById('items_json').value = JSON.stringify(returns);
        }
    </script>
<?php
            $c = ob_get_clean();
            renderLayout('Process Sales Return', $c, 'sales_returns');
        } else {
            $returns = $pdo->query('SELECT sr.*, c.name as customer FROM sales_returns sr JOIN customers c ON sr.customer_id = c.id ORDER BY sr.id DESC')->fetchAll();
            ob_start();
?>
    <div class="page-header">
        <h1>&#8630; Sales Returns</h1>
        <div class="page-header-actions"><a href="?page=sales_returns&action=new" class="btn btn-primary btn-sm">+
                Process Return</a></div>
    </div>
    <div class="lf">
        <table class="tbl">
            <thead>
            <tr>
                <th>#</th>
                <th>Return No.</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Total Amount</th>
                <th>Method</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($returns)): ?>
                <tr>
                    <td colspan="6" class="text-center">No sales returns found.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($returns as $idx => $r): ?>
                <tr>
                    <td><?= $idx + 1 ?></td>
                    <td><?= h($r['return_number']) ?></td>
                    <td><?= h($r['customer']) ?></td>
                    <td><?= h($r['return_date']) ?></td>
                    <td><?= formatCurrency((float) $r['total_amount']) ?></td>
                    <td><span class="badge badge-info"><?= h(ucfirst($r['refund_method'])) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php
            $c = ob_get_clean();
            renderLayout('Sales Returns', $c, 'sales_returns');
        }
    }

    function renderCustomerLedgers(): void
    {
        requirePermission('customers', 'view');
        $pdo = getPDO();
        $customers = $pdo->query('SELECT id,name FROM customers ORDER BY name')->fetchAll();
        $cid = (int) ($_GET['customer_id'] ?? ($customers[0]['id'] ?? 0));
        ob_start();
        ?>
    <div class="page-header"><h1>&#128200; Customer Ledgers</h1></div>
    <div class="lf"><span class="lf-title">Statement of Account</span>
        <form method="GET" class="mb-8" style="display:flex;gap:8px;align-items:flex-end">
            <input type="hidden" name="page" value="customer_ledgers">
            <div class="form-group" style="width:300px"><label>Select Customer</label>
                <select name="customer_id" onchange="this.form.submit()"><?php foreach ($customers as $c) {
            $s = $c['id'] == $cid ? 'selected' : '';
            echo "<option value='{$c['id']}' $s>" . h($c['name']) . '</option>';
        } ?></select>
            </div>
        </form>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Credit</th>
                    <th class="text-right">Balance</th>
                </tr>
                </thead>
                <tbody>
                <?php $stmt = $pdo->prepare('SELECT * FROM customer_ledgers WHERE customer_id=? ORDER BY transaction_date');
                $stmt->execute([$cid]);
                $ledgers = $stmt->fetchAll();
                if (empty($ledgers)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">No ledger entries found</td>
                    </tr><?php endif;
                foreach ($ledgers as $l): ?>
                    <tr>
                        <td><?= $l['transaction_date'] ?></td>
                        <td><?= h(ucfirst($l['reference_type'])) ?></td>
                        <td class="text-right" style="color:#d9534f"><?= formatCurrency($l['debit']) ?></td>
                        <td class="text-right" style="color:#5cb85c"><?= formatCurrency($l['credit']) ?></td>
                        <td class="text-right font-weight-bold"><?= formatCurrency($l['balance']) ?></td>
                    </tr>
                <?php endforeach; ?></tbody>
            </table>
        </div>
    </div>
<?php
        $content = ob_get_clean();
        renderLayout('Customer Ledgers', $content, 'customer_ledgers');
    }

    function renderPurchaseReturns(): void
    {
        requirePermission('purchases', 'view');
        $pdo = getPDO();
        $action = $_GET['action'] ?? 'list';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_return']) && verifyCsrf()) {
            $pdo->beginTransaction();
            try {
                $poId = (int) $_POST['po_id'];
                $supplierId = (int) $_POST['supplier_id'];
                $items = json_decode($_POST['items_json'], true) ?? [];
                if (empty($items))
                    throw new Exception('No items to return.');
                $total = 0;
                foreach ($items as $it) {
                    $total += $it['price'] * $it['qty'];
                }
                $rn = 'PR-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
                $stmt = $pdo->prepare('INSERT INTO purchase_returns (return_number, po_id, supplier_id, return_date, total_amount, created_by) VALUES (?, ?, ?, CURDATE(), ?, ?)');
                $stmt->execute([$rn, $poId, $supplierId, $total, $_SESSION['user_id']]);
                $returnId = $pdo->lastInsertId();
                $stmtItem = $pdo->prepare('INSERT INTO purchase_return_items (return_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)');
                $stmtUpdateStock = $pdo->prepare('UPDATE products SET current_stock = current_stock - ? WHERE id = ?');
                foreach ($items as $it) {
                    $stmtItem->execute([$returnId, (int) $it['id'], (int) $it['qty'], (float) $it['price']]);
                    $stmtUpdateStock->execute([(int) $it['qty'], (int) $it['id']]);
                }
                updateSupplierLedger($supplierId, 'return', $returnId, $total, 0, "Return for PO #$poId");
                logActivity($_SESSION['user_id'], 'CREATE', 'purchases', "Processed purchase return $rn for PO ID $poId");
                $pdo->commit();
                $_SESSION['flash_msg'] = "Purchase return $rn processed successfully.";
                header('Location: ?page=purchase_returns');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = $e->getMessage();
            }
        }
        if ($action === 'new') {
            $pos = $pdo->query("SELECT po.id, po.po_number, po.supplier_id, s.name as supplier_name FROM purchase_orders po JOIN suppliers s ON po.supplier_id = s.id WHERE po.order_status = 'Received' ORDER BY po.id DESC")->fetchAll();
            ob_start();
            ?>
    <div class="page-header">
        <h1>&#8630; New Purchase Return</h1>
        <div class="page-header-actions"><a href="?page=purchase_returns" class="btn btn-secondary btn-sm">Back to
                List</a></div>
    </div>
    <div class="lf">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
        <form method="POST" id="prForm">
            <?= csrfField() ?>
            <input type="hidden" name="save_return" value="1">
            <input type="hidden" name="items_json" id="items_json">
            <div class="form-group">
                <label>Select Purchase Order</label>
                <select name="po_id" id="po_id" required onchange="loadPoItems(this.value)">
                    <option value="">-- Select PO --</option>
                    <?php foreach ($pos as $po): ?>
                        <option value="<?= $po['id'] ?>"
                                data-supplier-id="<?= $po['supplier_id'] ?>"><?= h($po['po_number']) ?>
                            - <?= h($po['supplier_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="supplier_id" id="supplier_id">
            </div>
            <div id="items-container" style="display:none; margin-top:20px;">
                <h3>PO Items</h3>
                <table class="tbl" id="items-table">
                    <thead>
                    <tr>
                        <th>Product</th>
                        <th>Received Qty</th>
                        <th>Unit Price</th>
                        <th>Return Qty</th>
                        <th>Total</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                    <tr>
                        <th colspan="4" class="text-right">Return Total:</th>
                        <th id="return-total"><?= formatCurrency(0) ?></th>
                    </tr>
                    </tfoot>
                </table>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary" onclick="prepareSubmit(event)">Submit Return</button>
                </div>
            </div>
        </form>
    </div>
    <script>
        let poItems = [];
        function loadPoItems(poId) {
            if (!poId) {
                document.getElementById('items-container').style.display = 'none';
                return;
            }
            const sel = document.getElementById('po_id');
            document.getElementById('supplier_id').value = sel.options[sel.selectedIndex].dataset.supplierId;
            fetch('?ajax=get_po_details', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: poId})
            })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        poItems = res.items;
                        renderItems();
                        document.getElementById('items-container').style.display = 'block';
                    } else {
                        alert('Error loading items: ' + (res.msg || 'Unknown error'));
                    }
                });
        }
        function renderItems() {
            const tbody = document.querySelector('#items-table tbody');
            tbody.innerHTML = '';
            poItems.forEach((it, idx) => {
                tbody.innerHTML += `
                            <tr>
                                <td>${h(it.name)}</td>
                                <td>${it.received_qty}</td>
                                <td>${it.unit_price}</td>
                                <td><input type="number" class="form-control" style="width:80px" min="0" max="${it.received_qty}" value="0" onchange="updateTotal(${idx}, this.value)"></td>
                                <td id="total-${idx}">${(0).toFixed(2)}</td>
                            </tr>
                        `;
            });
            calculateGrandTotal();
        }
        function updateTotal(idx, qty) {
            poItems[idx].return_qty = parseFloat(qty) || 0;
            document.getElementById('total-' + idx).textContent = (poItems[idx].return_qty * poItems[idx].unit_price).toFixed(2);
            calculateGrandTotal();
        }
        function calculateGrandTotal() {
            let total = 0;
            poItems.forEach(it => {
                total += (it.return_qty || 0) * it.unit_price;
            });
            document.getElementById('return-total').textContent = total.toFixed(2);
        }
        function prepareSubmit(e) {
            const returns = poItems.filter(it => (it.return_qty || 0) > 0).map(it => ({
                id: it.product_id,
                qty: it.return_qty,
                price: it.unit_price
            }));
            if (returns.length === 0) {
                alert('Please select at least one item to return.');
                e.preventDefault();
                return;
            }
            document.getElementById('items_json').value = JSON.stringify(returns);
        }
    </script>
<?php
            renderLayout('Purchase Returns', ob_get_clean(), 'purchase_returns');
        } else {
            $returns = $pdo->query('SELECT pr.*, s.company_name as supplier FROM purchase_returns pr JOIN suppliers s ON pr.supplier_id = s.id ORDER BY pr.id DESC')->fetchAll();
            ob_start();
?>
    <div class="page-header">
        <h1>&#8630; Purchase Returns</h1>
        <div class="page-header-actions"><a href="?page=purchase_returns&action=new" class="btn btn-primary btn-sm">+
                New Return</a></div>
    </div>
    <div class="lf">
        <table class="tbl">
            <thead>
            <tr>
                <th>#</th>
                <th>Return No.</th>
                <th>Supplier</th>
                <th>Date</th>
                <th>Total Amount</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($returns)): ?>
                <tr>
                    <td colspan="5" class="text-center">No purchase returns found.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($returns as $idx => $r): ?>
                <tr>
                    <td><?= $idx + 1 ?></td>
                    <td><?= h($r['return_number']) ?></td>
                    <td><?= h($r['supplier']) ?></td>
                    <td><?= h($r['return_date']) ?></td>
                    <td><?= formatCurrency((float) $r['total_amount']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php renderLayout('Purchase Returns', ob_get_clean(), 'purchase_returns');
        }
    }

    function renderRequisitions(): void
    {
        requirePermission('purchases', 'view');
        $pdo = getPDO();
        $action = $_GET['action'] ?? 'list';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
            if (isset($_POST['save_requisition'])) {
                $notes = trim($_POST['notes'] ?? '');
                $items = json_decode($_POST['items_json'], true) ?? [];
                if (empty($items)) {
                    $_SESSION['flash_msg'] = 'Error: No items selected.';
                    $_SESSION['flash_type'] = 'danger';
                    header('Location: ?page=requisitions&action=new');
                    exit;
                }
                $rn = 'RQ-' . date('Ymd') . '-' . rand(1000, 9999);
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare('INSERT INTO requisitions (req_number, requested_by, status, notes) VALUES (?, ?, "pending", ?)');
                    $stmt->execute([$rn, $_SESSION['user_id'], $notes]);
                    $reqId = $pdo->lastInsertId();
                    $stmtItem = $pdo->prepare('INSERT INTO requisition_items (req_id, product_id, quantity) VALUES (?, ?, ?)');
                    foreach ($items as $it) {
                        $stmtItem->execute([$reqId, $it['id'], $it['qty']]);
                    }
                    $pdo->commit();
                    $_SESSION['flash_msg'] = "Requisition $rn submitted successfully.";
                    $_SESSION['flash_type'] = 'success';
                    header('Location: ?page=requisitions');
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $_SESSION['flash_msg'] = 'Error: ' . $e->getMessage();
                    $_SESSION['flash_type'] = 'danger';
                }
            } elseif (isset($_POST['update_status'])) {
                $id = (int) $_POST['req_id'];
                $status = $_POST['status'];
                if (in_array($status, ['approved', 'rejected'])) {
                    $pdo->prepare('UPDATE requisitions SET status=? WHERE id=?')->execute([$status, $id]);
                    $_SESSION['flash_msg'] = 'Requisition status updated to ' . ucfirst($status) . '.';
                    $_SESSION['flash_type'] = 'success';
                }
                header('Location: ?page=requisitions');
                exit;
            } elseif (isset($_POST['create_po'])) {
                $id = (int) $_POST['req_id'];
                $supplierId = (int) $_POST['supplier_id'];
                $req = $pdo->prepare('SELECT * FROM requisitions WHERE id=? AND status="approved"');
                $req->execute([$id]);
                $requisition = $req->fetch();
                if ($requisition && $supplierId) {
                    $pdo->beginTransaction();
                    try {
                        $poNum = generatePoNumber();
                        $items = $pdo->prepare('SELECT ri.*, p.purchase_price FROM requisition_items ri JOIN products p ON p.id=ri.product_id WHERE ri.req_id=?');
                        $items->execute([$id]);
                        $reqItems = $items->fetchAll();
                        $subtotal = 0;
                        foreach ($reqItems as $it) {
                            $subtotal += $it['purchase_price'] * $it['quantity'];
                        }
                        $taxPct = (float) getSetting('default_tax_percent');
                        $taxAmt = $subtotal * ($taxPct / 100);
                        $total = $subtotal + $taxAmt;
                        $stmtPO = $pdo->prepare('INSERT INTO purchase_orders (po_number, supplier_id, po_date, subtotal, tax_percent, total_amount, payment_status, order_status, notes, created_by) VALUES (?, ?, CURDATE(), ?, ?, ?, "unpaid", "pending", ?, ?)');
                        $stmtPO->execute([$poNum, $supplierId, $subtotal, $taxPct, $total, 'Created from Requisition ' . $requisition['req_number'], $_SESSION['user_id']]);
                        $poId = $pdo->lastInsertId();
                        $stmtPOItem = $pdo->prepare('INSERT INTO purchase_order_items (po_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)');
                        foreach ($reqItems as $it) {
                            $stmtPOItem->execute([$poId, $it['product_id'], $it['quantity'], $it['purchase_price'], $it['quantity'] * $it['purchase_price']]);
                        }
                        $pdo->prepare('UPDATE requisitions SET status="ordered" WHERE id=?')->execute([$id]);
                        $pdo->commit();
                        $_SESSION['flash_msg'] = "Draft Purchase Order $poNum created successfully.";
                        $_SESSION['flash_type'] = 'success';
                        header('Location: ?page=purchases&action=view&id=' . $poId);
                        exit;
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $_SESSION['flash_msg'] = 'Error: ' . $e->getMessage();
                        $_SESSION['flash_type'] = 'danger';
                    }
                }
                header('Location: ?page=requisitions');
                exit;
            }
        }
        if ($action === 'new') {
            $products = $pdo->query("SELECT id,name,sku,current_stock FROM products WHERE status='active' ORDER BY name")->fetchAll();
            ob_start(); ?>
    <div class="page-header">
        <h1>&#128227; New Requisition</h1>
        <div class="page-header-actions"><a href="?page=requisitions" class="btn btn-secondary btn-sm">Back to List</a>
        </div>
    </div>
    <div class="lf">
        <form method="POST" id="rqForm">
            <?= csrfField() ?>
            <input type="hidden" name="save_requisition" value="1">
            <input type="hidden" name="items_json" id="rqJ">
            <div class="form-group mb-8">
                <label>Notes / Reason</label>
                <textarea name="notes" placeholder="Why is this being requested?"></textarea>
            </div>
            <div class="lf">
                <span class="lf-title">Request Items</span>
                <div class="form-group" style="flex-direction:row;gap:8px;align-items:flex-end">
                    <div style="flex:1">
                        <label>Product</label>
                        <select id="rqP">
                            <option value="">-- Choose Product --</option>
                            <?php foreach ($products as $p) echo "<option value='{$p['id']}' data-sku='{$p['sku']}'>" . h($p['name']) . ' (' . h($p['sku']) . ')</option>'; ?>
                        </select>
                    </div>
                    <div style="width:100px"><label>Qty</label><input type="number" id="rqQ" value="1" min="1"></div>
                    <button type="button" class="btn btn-primary" onclick="rqA()">Add Item</button>
                </div>
                <table class="tbl mt-8" id="rqT">
                    <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th class="text-right">Qty</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="mt-8">
                <button type="submit" class="btn btn-success" onclick="rqS(event)">Submit Requisition</button>
            </div>
        </form>
    </div>
    <script>
        let rqc = [];
        function rqA() {
            let s = document.getElementById('rqP');
            let q = document.getElementById('rqQ');
            if (!s.value) return;
            let opt = s.options[s.selectedIndex];
            let id = +s.value;
            let f = rqc.find(i => i.id === id);
            if (f) {
                f.qty += +q.value;
            } else {
                rqc.push({
                    id: id,
                    sku: opt.dataset.sku,
                    name: opt.text,
                    qty: +q.value
                });
            }
            rqr();
        }
        function rqr() {
            let b = document.querySelector('#rqT tbody');
            b.innerHTML = '';
            rqc.forEach((i, k) => {
                b.innerHTML += `<tr>
                            <td>${h(i.sku)}</td>
                            <td>${h(i.name)}</td>
                            <td class="text-right"><input type="number" value="${i.qty}" min="1" style="width:60px" onchange="rqc[${k}].qty=+this.value;rqr()"></td>
                            <td class="text-center"><button type="button" class="btn btn-danger btn-xs" onclick="rqc.splice(${k},1);rqr()">×</button></td>
                        </tr>`;
            });
        }
        function rqS(e) {
            if (rqc.length === 0) {
                alert('Please add at least one item.');
                e.preventDefault();
                return;
            }
            document.getElementById('rqJ').value = JSON.stringify(rqc);
        }
    </script>
<?php
        } else {
            $reqs = $pdo->query('SELECT r.*, u.full_name as requester FROM requisitions r JOIN users u ON u.id=r.requested_by ORDER BY r.id DESC')->fetchAll();
            $suppliers = $pdo->query('SELECT id, company_name FROM suppliers WHERE status="active"')->fetchAll();
            ob_start();
?>
    <div class="page-header">
        <h1>&#128227; Requisitions</h1>
        <div class="page-header-actions"><a href="?page=requisitions&action=new" class="btn btn-primary btn-sm">+ New
                Requisition</a></div>
    </div>
<?php if (isset($_SESSION['flash_msg'])): ?>
    <div class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?>"><?= h($_SESSION['flash_msg']) ?></div>
<?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
<?php endif; ?>
    <div class="lf">
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Requester</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($reqs as $r): ?>
                    <tr>
                        <td><?= h($r['req_number']) ?></td>
                        <td><?= h($r['requester']) ?></td>
                        <td><?= h($r['created_at']) ?></td>
                        <td>
                            <span class="badge badge-<?= ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'ordered' => 'primary'][$r['status']] ?? 'secondary' ?>"><?= strtoupper($r['status']) ?></span>
                        </td>
                        <td><?= h($r['notes']) ?></td>
                        <td>
                            <div class="tbl-actions">
                                <?php if ($r['status'] === 'pending' && canAccess(['admin', 'manager'])): ?>
                                    <form method="POST" style="display:inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="update_status" value="1">
                                        <input type="hidden" name="req_id" value="<?= $r['id'] ?>">
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="btn btn-success btn-xs">Approve</button>
                                    </form>
                                    <form method="POST" style="display:inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="update_status" value="1">
                                        <input type="hidden" name="req_id" value="<?= $r['id'] ?>">
                                        <input type="hidden" name="status" value="rejected">
                                        <button type="submit" class="btn btn-danger btn-xs">Reject</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($r['status'] === 'approved'): ?>
                                    <button class="btn btn-primary btn-xs"
                                            onclick="openPoModal(<?= $r['id'] ?>, '<?= h($r['req_number']) ?>')">Create
                                        PO
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($reqs)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No requisitions found</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div id="poModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-title-bar"><span>Create PO from Requisition</span>
                <button class="modal-close-btn" onclick="closeModal('poModal')">&times;</button>
            </div>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="create_po" value="1">
                <input type="hidden" name="req_id" id="modal_req_id">
                <div class="modal-body">
                    <p>Select a supplier to create a draft Purchase Order for Requisition <strong
                                id="modal_req_num"></strong>.</p>
                    <div class="form-group mt-8">
                        <label>Supplier</label>
                        <select name="supplier_id" required>
                            <option value="">-- Select Supplier --</option>
                            <?php foreach ($suppliers as $s) echo "<option value='{$s['id']}'>" . h($s['company_name']) . '</option>'; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('poModal')">Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm">Generate PO</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function openPoModal(id, num) {
            document.getElementById('modal_req_id').value = id;
            document.getElementById('modal_req_num').textContent = num;
            openModal('poModal');
        }
    </script>
<?php
        }
        $content = ob_get_clean();
        renderLayout('Requisitions', $content, 'requisitions');
    }

    function renderStockAudit(): void
    {
        requirePermission('stock_audit', 'view');
        $pdo = getPDO();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
            $action = $_POST['action'] ?? '';
            if ($action === 'start_audit') {
                requirePermission('stock_audit', 'manage');
                $an = 'AUD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
                $warehouseId = (int) ($_POST['warehouse_id'] ?? 1);
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare('INSERT INTO stock_audits (audit_number, warehouse_id, audit_date, status, created_by) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([$an, $warehouseId, date('Y-m-d'), 'open', $_SESSION['user_id']]);
                    $auditId = $pdo->lastInsertId();
                    $products = $pdo->query("SELECT id, current_stock FROM products WHERE status='active'")->fetchAll();
                    $stmtItem = $pdo->prepare('INSERT INTO stock_audit_items (audit_id, product_id, system_qty, counted_qty, difference) VALUES (?, ?, ?, ?, ?)');
                    foreach ($products as $p) {
                        $stmtItem->execute([$auditId, $p['id'], (int) $p['current_stock'], (int) $p['current_stock'], 0]);
                    }
                    $pdo->commit();
                    $_SESSION['flash_msg'] = 'New stock audit started: ' . $an;
                    $_SESSION['flash_type'] = 'success';
                    header("Location: ?page=stock_audit&id=$auditId");
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $_SESSION['flash_msg'] = 'Error starting audit: ' . $e->getMessage();
                    $_SESSION['flash_type'] = 'danger';
                    header('Location: ?page=stock_audit');
                    exit;
                }
            } elseif ($action === 'save_counts' || $action === 'finalize_audit') {
                requirePermission('stock_audit', 'manage');
                $auditId = (int) ($_POST['audit_id'] ?? 0);
                $stmtA = $pdo->prepare("SELECT * FROM stock_audits WHERE id=? AND status='open'");
                $stmtA->execute([$auditId]);
                $audit = $stmtA->fetch();
                if ($audit) {
                    $pdo->beginTransaction();
                    try {
                        $counts = $_POST['counts'] ?? [];
                        $stmtUpdate = $pdo->prepare('UPDATE stock_audit_items SET counted_qty=?, difference=? WHERE audit_id=? AND product_id=?');
                        foreach ($counts as $productId => $countedQty) {
                            $productId = (int) $productId;
                            $countedQty = (int) $countedQty;
                            $stmtSys = $pdo->prepare('SELECT system_qty FROM stock_audit_items WHERE audit_id=? AND product_id=?');
                            $stmtSys->execute([$auditId, $productId]);
                            $systemQty = (int) $stmtSys->fetchColumn();
                            $diff = $countedQty - $systemQty;
                            $stmtUpdate->execute([$countedQty, $diff, $auditId, $productId]);
                        }
                        if ($action === 'finalize_audit') {
                            $stmtItems = $pdo->prepare('SELECT * FROM stock_audit_items WHERE audit_id=?');
                            $stmtItems->execute([$auditId]);
                            $items = $stmtItems->fetchAll();
                            foreach ($items as $item) {
                                if ((int) $item['difference'] !== 0) {
                                    $diff = (int) $item['difference'];
                                    $absDiff = abs($diff);
                                    $type = ($diff > 0) ? 'addition' : 'subtraction';
                                    $ref = 'ADJ-' . $audit['audit_number'] . '-' . $item['product_id'];
                                    $stmtProd = $pdo->prepare('SELECT current_stock FROM products WHERE id=?');
                                    $stmtProd->execute([$item['product_id']]);
                                    $before = (int) $stmtProd->fetchColumn();
                                    $after = (int) $item['counted_qty'];
                                    $stmtAdj = $pdo->prepare('INSERT INTO stock_adjustments (reference_no, product_id, adjustment_type, quantity, before_stock, after_stock, reason, status, requested_by, approved_by, approved_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
                                    $stmtAdj->execute([$ref, $item['product_id'], $type, $absDiff, $before, $after, 'Stock Audit Variance: ' . $audit['audit_number'], 'approved', $_SESSION['user_id'], $_SESSION['user_id']]);
                                    $pdo->prepare('UPDATE products SET current_stock=?, updated_at=NOW() WHERE id=?')->execute([$after, $item['product_id']]);
                                }
                            }
                            $pdo->prepare("UPDATE stock_audits SET status='closed' WHERE id=?")->execute([$auditId]);
                            $_SESSION['flash_msg'] = 'Audit finalized and stock levels updated.';
                            $_SESSION['flash_type'] = 'success';
                        } else {
                            $_SESSION['flash_msg'] = 'Audit counts saved.';
                            $_SESSION['flash_type'] = 'info';
                        }
                        $pdo->commit();
                        header("Location: ?page=stock_audit&id=$auditId");
                        exit;
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $_SESSION['flash_msg'] = 'Error: ' . $e->getMessage();
                        $_SESSION['flash_type'] = 'danger';
                        header("Location: ?page=stock_audit&id=$auditId");
                        exit;
                    }
                }
            }
        }
        ob_start();
        if ($id):
            $stmt = $pdo->prepare('SELECT a.*, u.full_name as creator FROM stock_audits a JOIN users u ON u.id=a.created_by WHERE a.id=?');
            $stmt->execute([$id]);
            $audit = $stmt->fetch();
            if (!$audit) {
                header('Location: ?page=stock_audit');
                exit;
            }
            $stmtItems = $pdo->prepare('SELECT sai.*, p.name as product_name, p.sku, p.unit FROM stock_audit_items sai JOIN products p ON p.id=sai.product_id WHERE sai.audit_id=? ORDER BY p.name');
            $stmtItems->execute([$id]);
            $items = $stmtItems->fetchAll();
?>
    <div class="page-header">
        <h1>&#128269; Stock Audit: <?= h($audit['audit_number']) ?></h1>
        <div class="page-header-actions">
            <a href="?page=stock_audit" class="btn btn-secondary btn-sm">Back to List</a>
            <button class="btn btn-primary btn-sm no-print" onclick="window.print()">Print List</button>
        </div>
    </div>
    <?php if (isset($_SESSION['flash_msg'])): ?>
    <div
    class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?>"><?= h($_SESSION['flash_msg']) ?></div><?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
            endif; ?>
    <div class="lf">
        <span class="lf-title">Audit Info</span>
        <div style="display:flex; gap:30px; padding:5px;">
            <div>Date: <strong><?= formatDate($audit['audit_date']) ?></strong></div>
            <div>Status: <span
                        class="badge badge-<?= $audit['status'] === 'open' ? 'warning' : 'success' ?>"><?= strtoupper($audit['status']) ?></span>
            </div>
            <div>Created By: <strong><?= h($audit['creator']) ?></strong></div>
        </div>
    </div>
    <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="audit_id" value="<?= $id ?>">
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                <tr>
                    <th>Product</th>
                    <th class="text-right">System Qty</th>
                    <th class="text-right">Counted Qty</th>
                    <th class="text-right">Difference</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($items as $x):
                    $diff = (int) $x['difference'];
                    $diffClass = '';
                    if ($diff > 0)
                        $diffClass = 'stock-ok';
                    elseif ($diff < 0)
                        $diffClass = 'stock-critical';
                    ?>
                    <tr>
                        <td><strong><?= h($x['product_name']) ?></strong><br><small
                                    style="color:#666"><?= h($x['sku']) ?></small></td>
                        <td class="text-right"><?= number_format($x['system_qty']) ?><small><?= h($x['unit']) ?></small>
                        </td>
                        <td class="text-right">
                            <?php if ($audit['status'] === 'open'): ?>
                                <input type="number" name="counts[<?= $x['product_id'] ?>]"
                                       value="<?= (int) $x['counted_qty'] ?>" class="text-right"
                                       style="width:100px; padding:4px;"
                                       onchange="updDiff(this, <?= (int) $x['system_qty'] ?>)">
                            <?php else: ?>
                                <strong><?= number_format($x['counted_qty']) ?></strong>
                            <?php endif; ?>
                        </td>
                        <td class="text-right diff-val <?= $diffClass ?>" style="font-weight:700">
                            <?= $diff > 0 ? '+' : '' ?><?= number_format($diff) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($audit['status'] === 'open'): ?>
            <div class="mt-8 no-print" style="display:flex; justify-content:flex-end; gap:10px; padding:10px 0;">
                <button type="submit" name="action" value="save_counts" class="btn btn-primary">Save Counts</button>
                <button type="submit" name="action" value="finalize_audit" class="btn btn-success"
                        onclick="return confirm('Are you sure you want to finalize this audit? Product stock levels will be updated immediately.')">
                    Finalize & Post Variance
                </button>
            </div>
        <?php endif; ?>
    </form>
    <script>
        function updDiff(inp, sys) {
            var c = parseInt(inp.value) || 0;
            var d = c - sys;
            var cell = inp.closest('tr').querySelector('.diff-val');
            cell.textContent = (d > 0 ? '+' : '') + d;
            cell.className = 'text-right diff-val ' + (d > 0 ? 'stock-ok' : (d < 0 ? 'stock-critical' : ''));
        }
    </script>
<?php
        else:
            $a = $pdo->query('SELECT a.*, u.full_name as creator, w.name as warehouse_name FROM stock_audits a JOIN users u ON u.id=a.created_by LEFT JOIN warehouses w ON w.id=a.warehouse_id ORDER BY a.id DESC')->fetchAll();
            $warehouses = $pdo->query("SELECT id, name FROM warehouses WHERE status='active' ORDER BY name")->fetchAll();
?>
    <div class="page-header">
        <h1>&#128269; Stock Audits</h1>
        <div class="page-header-actions">
            <form method="POST" style="display:flex;gap:6px"
                  onsubmit="return confirm('Start a new warehouse-wide stock audit? This will capture all current system stock levels.')">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="start_audit">
                <select name="warehouse_id" required class="form-control"
                        style="padding:4px 8px;font-size:12px;border:2px inset #a0a0a0;height:auto">
                    <?php foreach ($warehouses as $w): ?>
                        <option value="<?= $w['id'] ?>"><?= h($w['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">+ Start New Audit</button>
            </form>
        </div>
    </div>
    <?php if (isset($_SESSION['flash_msg'])): ?>
    <div
    class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?>"><?= h($_SESSION['flash_msg']) ?></div><?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
            endif; ?>
    <div class="lf">
        <table class="tbl">
            <thead>
            <tr>
                <th>Ref #</th>
                <th>Warehouse</th>
                <th>Date</th>
                <th>Items</th>
                <th>Created By</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($a)): ?>
                <tr>
                    <td colspan="7" class="text-center" style="padding:20px; color:#888;">No stock audits found.</td>
                </tr><?php endif; ?>
            <?php
            foreach ($a as $x):
                $count = $pdo->prepare('SELECT COUNT(*) FROM stock_audit_items WHERE audit_id=?');
                $count->execute([$x['id']]);
                $numItems = $count->fetchColumn();
                ?>
                <tr>
                    <td><code><?= h($x['audit_number']) ?></code></td>
                    <td><?= h($x['warehouse_name'] ?? 'Main') ?></td>
                    <td><?= formatDate($x['audit_date']) ?></td>
                    <td><?= $numItems ?> items</td>
                    <td><?= h($x['creator']) ?></td>
                    <td>
                        <span class="badge badge-<?= $x['status'] === 'open' ? 'warning' : 'success' ?>"><?= strtoupper($x['status']) ?></span>
                    </td>
                    <td>
                        <a href="?page=stock_audit&id=<?= $x['id'] ?>" class="btn btn-xs btn-secondary">View Details</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif;
        $content = ob_get_clean();
        renderLayout('Stock Audit', $content, 'stock_audit');
    }

    function renderTaxes(): void
    {
        requireRole(['admin']);
        $pdo = getPDO();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id)
                $pdo->prepare('UPDATE taxes SET name=?,rate=?,is_default=? WHERE id=?')->execute([$_POST['name'], $_POST['rate'], isset($_POST['def']) ? 1 : 0, $id]);
            else
                $pdo->prepare('INSERT INTO taxes (name,rate,is_default) VALUES (?,?,?)')->execute([$_POST['name'], $_POST['rate'], isset($_POST['def']) ? 1 : 0]);
            header('Location: ?page=taxes');
            exit;
        }
        $t = $pdo->query('SELECT * FROM taxes')->fetchAll();
        ob_start(); ?>
    <div class="page-header"><h1>Taxes</h1>
        <div class="page-header-actions">
            <button class="btn btn-primary btn-sm" onclick="openModal('txM')">+ Add</button>
        </div>
    </div>
    <div class="lf">
        <table class="tbl">
            <thead>
            <tr>
                <th>Name</th>
                <th>Rate</th>
                <th>Default</th>
                <th></th>
            </tr>
            </thead>
            <tbody><?php foreach ($t as $x): ?>
                <tr>
                <td><?= h($x['name']) ?></td>
                <td><?= $x['rate'] ?>%</td>
                <td><?= $x['is_default'] ? 'Yes' : 'No' ?></td>
                <td>
                    <button class="btn btn-xs"
                            onclick="eT(<?= $x['id'] ?>,'<?= h($x['name']) ?>',<?= $x['rate'] ?>,<?= $x['is_default'] ?>)">
                        Edit
                    </button>
                </td></tr><?php endforeach; ?></tbody>
        </table>
    </div>
    <div id="txM" class="modal-overlay">
        <div class="modal">
            <form method="POST"><?= csrfField() ?><input type="hidden" name="id" id="ti"><input name="name" id="tn"
                                                                                                placeholder="Name"
                                                                                                required><input
                        name="rate" id="tr" type="number" step="0.01" placeholder="Rate" required><label><input
                            type="checkbox" name="def" id="td"> Default</label>
                <button>Save</button>
            </form>
        </div>
    </div>
    <script>function eT(i, n, r, d) {
            ti.value = i;
            tn.value = n;
            tr.value = r;
            td.checked = d == 1;
            openModal('txM')
        }</script>
<?php renderLayout('Taxes', ob_get_clean(), 'taxes');
    }

    function renderCurrencies(): void
    {
        requireRole(['admin']);
        $pdo = getPDO();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
            $pdo->prepare('REPLACE INTO currencies (code,name,symbol,rate_to_base,is_base) VALUES (?,?,?,?,?)')->execute([$_POST['code'], $_POST['name'], $_POST['symbol'], $_POST['rate'], isset($_POST['base']) ? 1 : 0]);
            header('Location: ?page=currencies');
            exit;
        }
        $c = $pdo->query('SELECT * FROM currencies')->fetchAll();
        ob_start(); ?>
    <div class="page-header"><h1>Currencies</h1>
        <div class="page-header-actions">
            <button class="btn btn-primary btn-sm" onclick="openModal('cuM')">+ Add</button>
        </div>
    </div>
    <div class="lf">
        <table class="tbl">
            <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Rate</th>
                <th>Base</th>
            </tr>
            </thead>
            <tbody><?php foreach ($c as $x): ?>
                <tr>
                <td><?= $x['code'] ?></td>
                <td><?= h($x['name']) ?></td>
                <td><?= $x['rate_to_base'] ?></td>
                <td><?= $x['is_base'] ? 'Yes' : 'No' ?></td></tr><?php endforeach; ?></tbody>
        </table>
    </div>
    <div id="cuM" class="modal-overlay">
        <div class="modal">
            <form method="POST"><?= csrfField() ?><input name="code" placeholder="USD" maxlength="3" required><input
                        name="name" placeholder="US Dollar" required><input name="symbol" placeholder="$"
                                                                            required><input name="rate" type="number"
                                                                                            step="0.000001" value="1"
                                                                                            required><label><input
                            type="checkbox" name="base"> Base</label>
                <button>Save</button>
            </form>
        </div>
    </div>
<?php
        renderLayout('Currencies', ob_get_clean(), 'currencies');
    }

    function renderShifts(): void
    {
        requireRole(['admin', 'manager', 'staff']);
        $pdo = getPDO();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
            if (isset($_POST['action'])) {
                if ($_POST['action'] === 'open_shift') {
                    $float = (float) ($_POST['opening_float'] ?? 0);
                    $pdo
                        ->prepare("INSERT INTO shifts (user_id, open_time, opening_float, status) VALUES (?, NOW(), ?, 'open')")
                        ->execute([$_SESSION['user_id'], $float]);
                    $_SESSION['flash_msg'] = 'Shift opened successfully.';
                    $_SESSION['flash_type'] = 'success';
                } elseif ($_POST['action'] === 'close_shift') {
                    $shiftId = (int) $_POST['shift_id'];
                    $closingCash = (float) ($_POST['closing_cash'] ?? 0);
                    $pdo
                        ->prepare("UPDATE shifts SET close_time=NOW(), closing_cash=?, status='closed' WHERE id=?")
                        ->execute([$closingCash, $shiftId]);
                    $_SESSION['flash_msg'] = 'Shift closed successfully.';
                    $_SESSION['flash_type'] = 'success';
                }
                header('Location: ?page=shifts');
                exit;
            }
        }
        $s = $pdo->query('SELECT s.*,u.full_name FROM shifts s LEFT JOIN users u ON u.id=s.user_id ORDER BY s.id DESC LIMIT 50')->fetchAll();
        $openShift = $pdo->prepare("SELECT * FROM shifts WHERE user_id=? AND status='open' LIMIT 1");
        $openShift->execute([$_SESSION['user_id']]);
        $myOpenShift = $openShift->fetch();
        ob_start();
?>
    <div class="page-header"><h1>&#128336; POS Shifts / Registers</h1></div>
    <?php if (isset($_SESSION['flash_msg'])): ?>
    <div
    class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?>"><?= h($_SESSION['flash_msg']) ?></div><?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
        endif; ?>
    <div class="cols-2">
        <div>
            <div class="lf"><span class="lf-title">My Current Shift</span>
                <?php
                if ($myOpenShift):
                    $expectedCash = $myOpenShift['opening_float'] + $myOpenShift['cash_sales'];
                    ?>
                    <div style="padding:15px; background:#f9f9f9; border:1px solid #ddd; border-radius:4px;">
                        <div style="margin-bottom:10px;">Opened at: <strong><?= $myOpenShift['open_time'] ?></strong>
                        </div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:15px;">
                            <div>Opening Float:<br><strong><?= formatCurrency($myOpenShift['opening_float']) ?></strong>
                            </div>
                            <div>Cash Sales:<br><strong><?= formatCurrency($myOpenShift['cash_sales']) ?></strong></div>
                            <div>Card Sales:<br><strong><?= formatCurrency($myOpenShift['card_sales']) ?></strong></div>
                            <div>Total Sales:<br><strong><?= formatCurrency($myOpenShift['total_sales']) ?></strong>
                            </div>
                            <div style="grid-column: span 2; padding-top:10px; border-top:1px solid #eee;">
                                Expected Cash in Drawer:<br><strong
                                        style="font-size:1.4em; color:var(--primary);"><?= formatCurrency($expectedCash) ?></strong>
                            </div>
                        </div>
                        <button class="btn btn-danger btn-block" onclick="openModal('closeShiftModal')">Close Shift &
                            Reconcile
                        </button>
                    </div>
                <?php else: ?>
                    <div style="padding:20px; text-align:center; background:#f0f0f0; border:2px dashed #ccc; color:#666;">
                        <p style="margin-bottom:15px;">No open shift found for your user account.</p>
                        <button class="btn btn-success" onclick="openModal('openShiftModal')">Open New Shift</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div>
            <div class="lf"><span class="lf-title">Recent Shifts</span>
                <div class="tbl-wrap">
                    <table class="tbl">
                        <thead>
                        <tr>
                            <th>User</th>
                            <th>Opened</th>
                            <th>Closed</th>
                            <th class="text-right">Expected</th>
                            <th class="text-right">Actual</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($s)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No shifts recorded</td>
                            </tr><?php endif; ?>
                        <?php
                        foreach ($s as $x):
                            $exp = $x['opening_float'] + $x['total_sales'];
                            $diff = ($x['closing_cash'] !== null) ? ($x['closing_cash'] - $exp) : 0;
                            ?>
                            <tr>
                                <td><?= h($x['full_name']) ?></td>
                                <td><?= date('d M H:i', strtotime($x['open_time'])) ?></td>
                                <td><?= $x['close_time'] ? date('d M H:i', strtotime($x['close_time'])) : '-' ?></td>
                                <td class="text-right"><?= formatCurrency($exp) ?></td>
                                <td class="text-right">
                                    <?= ($x['closing_cash'] !== null) ? formatCurrency($x['closing_cash']) : '-' ?>
                                    <?php if ($x['status'] === 'closed'): ?>
                                        <br><small
                                                style="color:<?= $diff >= 0 ? 'green' : 'red' ?>"><?= $diff >= 0 ? '+' : '' ?><?= formatCurrency($diff) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $x['status'] === 'open' ? 'success' : 'secondary' ?>"><?= strtoupper($x['status']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div id="openShiftModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-title-bar"><span>Open New Shift</span>
                <button class="modal-close-btn" onclick="closeModal('openShiftModal')">&times;</button>
            </div>
            <form method="POST"><?= csrfField() ?><input type="hidden" name="action" value="open_shift">
                <div class="modal-body">
                    <div class="form-group"><label>Opening Cash Float</label><input type="number" name="opening_float"
                                                                                    step="0.01" value="0.00"
                                                                                    class="form-control" autofocus>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Start Shift</button>
                </div>
            </form>
        </div>
    </div>
    <div id="closeShiftModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-title-bar"><span>Close Shift & Reconcile</span>
                <button class="modal-close-btn" onclick="closeModal('closeShiftModal')">&times;</button>
            </div>
            <form method="POST"><?= csrfField() ?><input type="hidden" name="action" value="close_shift">
                <input type="hidden" name="shift_id" value="<?= $myOpenShift['id'] ?? '' ?>">
                <div class="modal-body">
                    <div style="margin-bottom:15px; padding:10px; background:#eef; border:1px solid #ccd; border-radius:4px;">
                        Expected Cash: <strong><?= formatCurrency($expectedCash ?? 0) ?></strong>
                    </div>
                    <div class="form-group"><label>Actual Cash in Drawer</label><input type="number" name="closing_cash"
                                                                                       step="0.01" class="form-control"
                                                                                       required autofocus></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">Finalize & Close</button>
                </div>
            </form>
        </div>
    </div>
<?php
        $content = ob_get_clean();
        renderLayout('Shifts', $content, 'shifts');
    }

    function renderApiSync(): void
    {
        requireRole(['admin']);
        $pdo = getPDO();
        $pdo->exec("CREATE TABLE IF NOT EXISTS `sync_logs` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `integration_id` INT UNSIGNED NOT NULL,
            `sync_type` ENUM('products_push','products_pull','orders_pull','inventory_sync') NOT NULL,
            `status` ENUM('success','failed','partial') NOT NULL,
            `items_processed` INT NOT NULL DEFAULT 0,
            `message` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`integration_id`) REFERENCES `api_keys`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB");
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
            $action = $_POST['action'] ?? 'save';
            if ($action === 'save') {
                $id = (int) ($_POST['id'] ?? 0);
                $platform = $_POST['platform'];
                $name = trim($_POST['name']);
                $api_key = trim($_POST['api_key']);
                $api_secret = trim($_POST['api_secret'] ?? '');
                $webhook = trim($_POST['webhook_url'] ?? '');
                $active = isset($_POST['is_active']) ? 1 : 0;
                if ($id) {
                    $pdo
                        ->prepare('UPDATE api_keys SET platform=?, name=?, api_key=?, api_secret=?, webhook_url=?, is_active=? WHERE id=?')
                        ->execute([$platform, $name, $api_key, $api_secret, $webhook, $active, $id]);
                    $_SESSION['flash_msg'] = 'Integration updated';
                } else {
                    $pdo
                        ->prepare('INSERT INTO api_keys (platform,name,api_key,api_secret,webhook_url,is_active) VALUES (?,?,?,?,?,?)')
                        ->execute([$platform, $name, $api_key, $api_secret, $webhook, $active]);
                    $_SESSION['flash_msg'] = 'Integration added';
                }
                $_SESSION['flash_type'] = 'success';
                header('Location: ?page=api_sync');
                exit;
            } elseif ($action === 'delete') {
                $pdo->prepare('DELETE FROM api_keys WHERE id=?')->execute([(int) $_POST['id']]);
                $_SESSION['flash_msg'] = 'Integration deleted';
                $_SESSION['flash_type'] = 'success';
                header('Location: ?page=api_sync');
                exit;
            } elseif ($action === 'sync') {
                $id = (int) $_POST['id'];
                $type = $_POST['sync_type'];
                $items = rand(5, 50);
                $status = ['success', 'partial', 'failed'][array_rand(['success', 'partial', 'failed'])];
                $pdo
                    ->prepare('INSERT INTO sync_logs (integration_id,sync_type,status,items_processed,message) VALUES (?,?,?,?,?)')
                    ->execute([$id, $type, $status, $items, "Simulated $type sync"]);
                $pdo->prepare('UPDATE api_keys SET last_sync=NOW() WHERE id=?')->execute([$id]);
                $_SESSION['flash_msg'] = "Sync completed: $items items ($status)";
                $_SESSION['flash_type'] = $status === 'success' ? 'success' : 'warning';
                header('Location: ?page=api_sync');
                exit;
            }
        }
        $integrations = $pdo->query('SELECT * FROM api_keys ORDER BY platform, name')->fetchAll();
        $logs = $pdo->query('SELECT l.*, k.name, k.platform FROM sync_logs l JOIN api_keys k ON k.id=l.integration_id ORDER BY l.created_at DESC LIMIT 20')->fetchAll();
        ob_start();
?>
    <div class="page-header"><h1>&#128279; E-Commerce Sync</h1>
        <div class="page-header-actions">
            <button class="btn btn-primary btn-sm" onclick="openModal('apiM')">+ Add Integration</button>
        </div>
    </div>
    <?php if (isset($_SESSION['flash_msg'])): ?>
    <div
    class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?>"><?= h($_SESSION['flash_msg']) ?></div><?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
        endif; ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div class="lf"><span class="lf-title">Active Integrations</span>
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                    <tr>
                        <th>Platform</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Last Sync</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($integrations)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No integrations configured</td>
                        </tr><?php endif; ?>
                    <?php foreach ($integrations as $i): ?>
                        <tr>
                            <td><span class="badge badge-info"><?= ucfirst($i['platform']) ?></span></td>
                            <td><?= h($i['name']) ?></td>
                            <td>
                                <span class="badge badge-<?= $i['is_active'] ? 'success' : 'secondary' ?>"><?= $i['is_active'] ? 'Active' : 'Inactive' ?></span>
                            </td>
                            <td><?= $i['last_sync'] ? date('M j H:i', strtotime($i['last_sync'])) : 'Never' ?></td>
                            <td>
                                <button class="btn btn-xs btn-success"
                                        onclick="syncNow(<?= $i['id'] ?>,'inventory_sync')">Sync
                                </button>
                                <button class="btn btn-xs"
                                        onclick="editInt(<?= $i['id'] ?>,'<?= h($i['platform']) ?>','<?= h(addslashes($i['name'])) ?>','<?= h(addslashes($i['api_key'])) ?>','<?= h(addslashes($i['api_secret'])) ?>','<?= h(addslashes($i['webhook_url'])) ?>',<?= $i['is_active'] ?>)">
                                    Edit
                                </button>
                                <form method="POST" style="display:inline"
                                      onsubmit="return confirm('Delete?')"><?= csrfField() ?><input type="hidden"
                                                                                                    name="action"
                                                                                                    value="delete"><input
                                            type="hidden" name="id" value="<?= $i['id'] ?>">
                                    <button class="btn btn-xs btn-danger">Del</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="lf"><span class="lf-title">Recent Sync Activity</span>
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                    <tr>
                        <th>Time</th>
                        <th>Integration</th>
                        <th>Type</th>
                        <th>Result</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td><?= date('H:i', strtotime($l['created_at'])) ?></td>
                            <td><?= h($l['name']) ?></td>
                            <td><?= str_replace('_', ' ', $l['sync_type']) ?></td>
                            <td>
                                <span class="badge badge-<?= $l['status'] === 'success' ? 'success' : ($l['status'] === 'partial' ? 'warning' : 'danger') ?>"><?= $l['items_processed'] ?> items</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="lf" style="margin-top:16px"><span class="lf-title">Webhook URLs</span>
        <div style="padding:12px;font-family:monospace;font-size:12px;background:#f8f9fa;border-radius:4px">
            Shopify: <?= h((isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME']) ?>
            ?webhook=shopify<br>
            WooCommerce: <?= h((isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME']) ?>
            ?webhook=woo
        </div>
    </div>
    <div id="apiM" class="modal-overlay">
        <div class="modal">
            <div class="modal-title-bar"><span id="apiTitle">Add Integration</span>
                <button class="modal-close-btn" onclick="closeModal('apiM')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST"><?= csrfField() ?><input type="hidden" name="action" value="save"><input
                            type="hidden" name="id" id="apiId">
                    <div class="form-group"><label>Platform</label><select name="platform" id="apiPlatform" required>
                            <option value="shopify">Shopify</option>
                            <option value="woocommerce">WooCommerce</option>
                            <option value="amazon">Amazon</option>
                        </select></div>
                    <div class="form-group"><label>Store Name</label><input name="name" id="apiName" required
                                                                            placeholder="My Store"></div>
                    <div class="form-group"><label>API Key / Store URL</label><input name="api_key" id="apiKey"
                                                                                     required></div>
                    <div class="form-group"><label>API Secret / Access Token</label><input name="api_secret"
                                                                                           id="apiSecret"></div>
                    <div class="form-group"><label>Webhook URL (optional)</label><input name="webhook_url"
                                                                                        id="apiWebhook"></div>
                    <label><input type="checkbox" name="is_active" id="apiActive" checked> Active</label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('apiM')">Cancel</button>
                <button class="btn btn-success btn-sm">Save</button>
            </div>
            </form></div>
    </div>
    <form id="syncForm" method="POST" style="display:none"><?= csrfField() ?><input type="hidden" name="action"
                                                                                    value="sync"><input type="hidden"
                                                                                                        name="id"
                                                                                                        id="syncId"><input
                type="hidden" name="sync_type" id="syncType"></form>
    <script>
        function editInt(id, p, n, k, s, w, a) {
            document.getElementById('apiTitle').textContent = 'Edit Integration';
            apiId.value = id;
            apiPlatform.value = p;
            apiName.value = n;
            apiKey.value = k;
            apiSecret.value = s;
            apiWebhook.value = w;
            apiActive.checked = a == 1;
            openModal('apiM')
        }
        function syncNow(id, type) {
            if (confirm('Run ' + type.replace('_', ' ') + '?')) {
                syncId.value = id;
                syncType.value = type;
                syncForm.submit()
            }
        }
    </script>
<?php
        $content = ob_get_clean();
        renderLayout('E-Commerce Sync', $content, 'api_sync');
    }

    function renderSms(): void
    {
        requireRole(['admin']);
        $pdo = getPDO();
        $pdo->exec("CREATE TABLE IF NOT EXISTS `sms_logs` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `recipient` VARCHAR(20) NOT NULL,
            `message` TEXT NOT NULL,
            `status` ENUM('sent','failed','pending') NOT NULL DEFAULT 'pending',
            `provider_response` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
        $pdo->exec('CREATE TABLE IF NOT EXISTS `sms_triggers` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `trigger_name` VARCHAR(50) NOT NULL UNIQUE,
            `is_active` TINYINT(1) NOT NULL DEFAULT 0,
            `template` TEXT NOT NULL
        ) ENGINE=InnoDB');
        $pdo->exec("INSERT IGNORE INTO sms_triggers (trigger_name,is_active,template) VALUES
            ('low_stock',0,'Alert: {product} is low on stock ({qty} left)'),
            ('overdue_payment',0,'Reminder: Invoice {invoice} of {amount} is overdue'),
            ('new_sale',0,'Thank you! Your order {invoice} for {amount} is confirmed'),
            ('shipping',0,'Your order {invoice} has shipped')");
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
            $action = $_POST['action'] ?? 'save_settings';
            if ($action === 'save_settings') {
                $pdo
                    ->prepare('REPLACE INTO sms_settings (id,provider,api_key,sender_id,is_active) VALUES (1,?,?,?,?)')
                    ->execute([$_POST['provider'], $_POST['api_key'], $_POST['sender_id'], isset($_POST['is_active']) ? 1 : 0]);
                $_SESSION['flash_msg'] = 'SMS settings saved';
                $_SESSION['flash_type'] = 'success';
            } elseif ($action === 'save_trigger') {
                $pdo
                    ->prepare('UPDATE sms_triggers SET is_active=?, template=? WHERE id=?')
                    ->execute([isset($_POST['is_active']) ? 1 : 0, $_POST['template'], (int) $_POST['id']]);
                $_SESSION['flash_msg'] = 'Trigger updated';
                $_SESSION['flash_type'] = 'success';
            } elseif ($action === 'test_sms') {
                $to = preg_replace('/[^0-9+]/', '', $_POST['test_number']);
                $msg = $_POST['test_message'];
                $status = strlen($to) > 8 ? 'sent' : 'failed';
                $pdo
                    ->prepare('INSERT INTO sms_logs (recipient,message,status,provider_response) VALUES (?,?,?,?)')
                    ->execute([$to, $msg, $status, $status === 'sent' ? 'Simulated success' : 'Invalid number']);
                $_SESSION['flash_msg'] = $status === 'sent' ? "Test SMS sent to $to" : 'Test failed';
                $_SESSION['flash_type'] = $status === 'sent' ? 'success' : 'danger';
            }
            header('Location: ?page=sms');
            exit;
        }
        $settings = $pdo->query('SELECT * FROM sms_settings WHERE id=1')->fetch() ?: ['provider' => 'twilio', 'api_key' => '', 'sender_id' => '', 'is_active' => 0];
        $triggers = $pdo->query('SELECT * FROM sms_triggers ORDER BY trigger_name')->fetchAll();
        $logs = $pdo->query('SELECT * FROM sms_logs ORDER BY created_at DESC LIMIT 30')->fetchAll();
        ob_start();
?>
    <div class="page-header"><h1>&#128241; SMS Alerts</h1>
        <div class="page-header-actions">
            <button class="btn btn-primary btn-sm" onclick="openModal('smsTestM')">Send Test</button>
        </div>
    </div>
    <?php if (isset($_SESSION['flash_msg'])): ?>
    <div
    class="alert alert-<?= h($_SESSION['flash_type'] ?? 'info') ?>"><?= h($_SESSION['flash_msg']) ?></div><?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
        endif; ?>
    <div style="display:grid;grid-template-columns:350px 1fr;gap:16px">
        <div class="lf"><span class="lf-title">Gateway Configuration</span>
            <form method="POST" style="padding:12px"><?= csrfField() ?><input type="hidden" name="action"
                                                                              value="save_settings">
                <div class="form-group"><label>Provider</label><select name="provider">
                        <option value="twilio" <?= $settings['provider'] === 'twilio' ? 'selected' : '' ?>>Twilio
                        </option>
                        <option value="messagebird" <?= $settings['provider'] === 'messagebird' ? 'selected' : '' ?>>
                            MessageBird
                        </option>
                        <option value="vonage" <?= $settings['provider'] === 'vonage' ? 'selected' : '' ?>>Vonage
                        </option>
                        <option value="custom" <?= $settings['provider'] === 'custom' ? 'selected' : '' ?>>Custom API
                        </option>
                    </select></div>
                <div class="form-group"><label>API Key / Token</label><input type="text" name="api_key"
                                                                             value="<?= h($settings['api_key']) ?>"
                                                                             placeholder="SK..."></div>
                <div class="form-group"><label>Sender ID</label><input type="text" name="sender_id"
                                                                       value="<?= h($settings['sender_id']) ?>"
                                                                       placeholder="InventoryPro"></div>
                <label><input type="checkbox" name="is_active" <?= $settings['is_active'] ? 'checked' : '' ?>> Enable
                    SMS</label>
                <button class="btn btn-success btn-sm" style="margin-top:8px;width:100%">Save Settings</button>
            </form>
        </div>
        <div class="lf"><span class="lf-title">Automated Triggers</span>
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                    <tr>
                        <th>Trigger</th>
                        <th>Status</th>
                        <th>Template</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($triggers as $t): ?>
                        <tr>
                            <td><?= ucfirst(str_replace('_', ' ', $t['trigger_name'])) ?></td>
                            <td>
                                <span class="badge badge-<?= $t['is_active'] ? 'success' : 'secondary' ?>"><?= $t['is_active'] ? 'ON' : 'OFF' ?></span>
                            </td>
                            <td style="font-size:12px"><?= h(substr($t['template'], 0, 50)) ?>...</td>
                            <td>
                                <button class="btn btn-xs"
                                        onclick="editTrig(<?= $t['id'] ?>,'<?= h(addslashes($t['trigger_name'])) ?>',<?= $t['is_active'] ?>,`<?= h(addslashes($t['template'])) ?>`)">
                                    Edit
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="lf" style="margin-top:16px"><span class="lf-title">SMS History (last 30)</span>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                <tr>
                    <th>Time</th>
                    <th>To</th>
                    <th>Message</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">No messages sent yet</td>
                    </tr><?php endif; ?>
                <?php foreach ($logs as $l): ?>
                    <tr>
                        <td><?= date('M j H:i', strtotime($l['created_at'])) ?></td>
                        <td><?= h($l['recipient']) ?></td>
                        <td><?= h(substr($l['message'], 0, 60)) ?></td>
                        <td>
                            <span class="badge badge-<?= $l['status'] === 'sent' ? 'success' : 'danger' ?>"><?= $l['status'] ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div id="smsTestM" class="modal-overlay">
        <div class="modal">
            <div class="modal-title-bar"><span>Send Test SMS</span>
                <button class="modal-close-btn" onclick="closeModal('smsTestM')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST"><?= csrfField() ?><input type="hidden" name="action" value="test_sms">
                    <div class="form-group"><label>Phone Number</label><input name="test_number" required
                                                                              placeholder="+923001234567"></div>
                    <div class="form-group"><label>Message</label><textarea name="test_message" required>Test from InventoryPro - <?= date('H:i') ?></textarea>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('smsTestM')">Cancel</button>
                <button class="btn btn-success btn-sm">Send</button>
                </form></div>
        </div>
    </div>
    <div id="trigM" class="modal-overlay">
        <div class="modal">
            <div class="modal-title-bar"><span id="trigTitle">Edit Trigger</span>
                <button class="modal-close-btn" onclick="closeModal('trigM')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST"><?= csrfField() ?><input type="hidden" name="action" value="save_trigger"><input
                            type="hidden" name="id" id="trigId">
                    <div class="form-group"><label>Template</label><textarea name="template" id="trigTemplate" rows="3"
                                                                             required></textarea><small>Variables:
                            {product}, {qty}, {invoice}, {amount}, {customer}</small></div>
                    <label><input type="checkbox" name="is_active" id="trigActive"> Enable this trigger</label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('trigM')">Cancel</button>
                <button class="btn btn-success btn-sm">Save</button>
                </form></div>
        </div>
    </div>
    <script>
        function editTrig(id, name, active, tpl) {
            document.getElementById('trigTitle').textContent = 'Edit: ' + name.replace('_', ' ');
            trigId.value = id;
            trigTemplate.value = tpl;
            trigActive.checked = active == 1;
            openModal('trigM')
        }
    </script>
<?php
        $content = ob_get_clean();
        renderLayout('SMS Alerts', $content, 'sms');
    }

    function renderSupplierLedgers(): void
    {
        requirePermission('suppliers', 'view');
        $pdo = getPDO();
        $suppliers = $pdo->query('SELECT id,company_name FROM suppliers ORDER BY company_name')->fetchAll();
        $sid = (int) ($_GET['supplier_id'] ?? ($suppliers[0]['id'] ?? 0));
        ob_start();
?>
    <div class="page-header"><h1>&#128200; Supplier Ledgers</h1></div>
    <div class="lf"><span class="lf-title">Statement of Account (Payables)</span>
        <form method="GET" class="mb-8" style="display:flex;gap:8px;align-items:flex-end">
            <input type="hidden" name="page" value="supplier_ledgers">
            <div class="form-group" style="width:300px"><label>Select Supplier</label>
                <select name="supplier_id" onchange="this.form.submit()"><?php foreach ($suppliers as $s) {
            $v = $s['id'] == $sid ? 'selected' : '';
            echo "<option value='{$s['id']}' $v>" . h($s['company_name']) . '</option>';
        } ?></select>
            </div>
        </form>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th class="text-right">Debit (Paid)</th>
                    <th class="text-right">Credit (Due)</th>
                    <th class="text-right">Balance</th>
                </tr>
                </thead>
                <tbody>
                <?php $stmt = $pdo->prepare('SELECT * FROM supplier_ledgers WHERE supplier_id=? ORDER BY transaction_date, id');
                $stmt->execute([$sid]);
                $ledgers = $stmt->fetchAll();
                if (empty($ledgers)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">No ledger entries found</td>
                    </tr><?php endif;
                foreach ($ledgers as $l): ?>
                    <tr>
                        <td><?= h($l['transaction_date']) ?></td>
                        <td><?= h(ucfirst($l['reference_type'])) ?></td>
                        <td class="text-right" style="color:#5cb85c"><?= formatCurrency((float) $l['debit']) ?></td>
                        <td class="text-right" style="color:#d9534f"><?= formatCurrency((float) $l['credit']) ?></td>
                        <td class="text-right font-weight-bold"><?= formatCurrency((float) $l['balance']) ?></td>
                    </tr>
                <?php endforeach; ?></tbody>
            </table>
        </div>
    </div>
    <?php
        $content = ob_get_clean();
        renderLayout('Supplier Ledgers', $content, 'supplier_ledgers');
    }

    match ($currentPage) {
        'dashboard' => renderDashboard(),
        'pos' => renderPOS(),
        'roles' => renderRoles(),
        'income_expenses' => renderIncomeExpenses(),
        'quotations' => renderQuotations(),
        'sales_returns' => renderSalesReturns(),
        'customer_ledgers' => renderCustomerLedgers(),
        'supplier_ledgers' => renderSupplierLedgers(),
        'purchase_returns' => renderPurchaseReturns(),
        'requisitions' => renderRequisitions(),
        'stock_audit' => renderStockAudit(),
        'taxes' => renderTaxes(),
        'currencies' => renderCurrencies(),
        'shifts' => renderShifts(),
        'api_sync' => renderApiSync(),
        'sms' => renderSms(),
        'products' => renderProducts(),
        'categories' => renderCategories(),
        'suppliers' => renderSuppliers(),
        'customers' => renderCustomers(),
        'purchases' => renderPurchases(),
        'sales' => renderSales(),
        'adjustments' => renderAdjustments(),
        'transfers' => renderTransfers(),
        'reports' => renderReports(),
        'barcodes' => renderBarcodes(),
        'notifications' => renderNotifications(),
        'audit_log' => renderAuditLog(),
        'users' => renderUsers(),
        'settings' => renderSettings(),
        'profile' => renderProfile(),
        default => renderDashboard()
    };
}
?>